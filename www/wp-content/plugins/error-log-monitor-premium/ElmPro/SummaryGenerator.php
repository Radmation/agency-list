<?php

class ElmPro_SummaryGenerator {
	const STATE_INTERVAL_START_REACHED = 'timeIntervalStartReached';
	const STATE_TIME_LIMIT_EXCEEDED = 'runTimeLimitExceeded';
	const STATE_MEMORY_USAGE_EXCEEDED = 'memoryUsageExceeded';
	const STATE_ALL_DONE = 'allEntriesProcessed';

	const FILE_SAMPLE_LENGTH = 1024;

	const MAX_SUMMARY_KEY_SIZE = 10240;

	private $log;
	private $timeIntervalStart = 0;

	private $ignoredMessages = array();
	private $fixedMessages;

	private $endState = null;
	private $runTimeLimit = null;
	private $memoryUsageLimit = null;

	private $progress = array();

	public function __construct(
		Elm_PhpErrorLog $log, $constraints = array(), $progress = array(),
		$ignoredMessages = array(), $fixedMessages = array()
	) {
		$this->log = $log;
		$this->ignoredMessages = $ignoredMessages;
		$this->fixedMessages = $fixedMessages;
		$this->setProgress($progress);

		if ( isset($constraints['minEntryTimestamp']) ) {
			$this->timeIntervalStart = $constraints['minEntryTimestamp'];
		}
		if ( isset($constraints['runTimeLimit']) ) {
			$this->runTimeLimit = $constraints['runTimeLimit'];
		}
		if ( isset($constraints['memoryUsageLimit']) ) {
			$this->memoryUsageLimit = $constraints['memoryUsageLimit'];
		}
	}

	public function createSummary() {
		$this->endState = null;

		$processedEntries = 0;
		$summary = array();
		/** @var ElmPro_SummaryItem[] $summary */

		$startTime = microtime(true);
		$startMemoryUsage = memory_get_usage();
		list($startPosition, $endPosition) = $this->calculateWorkRange();

		$parser = $this->log->getIterator(null, $startPosition, $endPosition);
		foreach ($parser as $entry) {
			//Have we reached the beginning of the specified time interval?
			if (
				!empty($entry['timestamp']) && isset($this->timeIntervalStart)
				&& ($entry['timestamp'] < $this->timeIntervalStart)
			) {
				$this->endState = self::STATE_INTERVAL_START_REACHED;
				$this->log(sprintf('Time threshold reached: %s', date('c', $entry['timestamp'])));
				break;
			}

			$summaryKey = self::getSummaryKey($entry['message']);
			if ( isset($summary[$summaryKey]) ) {
				$item = $summary[$summaryKey];
			} else {
				$item = new ElmPro_SummaryItem($entry, $summaryKey);
				$summary[$summaryKey] = $item;

				if ( isset($entry['message']) ) {
					if ( !empty($this->ignoredMessages[$entry['message']]) ) {
						$item->isIgnored = true;
					}
					if ( !empty($this->fixedMessages[$entry['message']]) ) {
						$details = $this->fixedMessages[$entry['message']];
						$item->isFixed = true;
						if ( isset($details['fixedOn']) ) {
							$item->markedAsFixedOn = $details['fixedOn'];
						}
					}
				}
			}
			$item->addEvent($entry);

			$processedEntries++;

			//Enforce the memory usage limit.
			if ( isset($this->memoryUsageLimit) ) {
				$usedMemory = memory_get_usage() - $startMemoryUsage;
				if ( $usedMemory > $this->memoryUsageLimit ) {
					$this->endState = self::STATE_MEMORY_USAGE_EXCEEDED;
					$this->log(sprintf(
						"Memory usage limit exceeded: %s here, %s total",
						Elm_Plugin::formatByteCount($usedMemory),
						Elm_Plugin::formatByteCount(memory_get_usage())
					));
					break;
				}
			}

			//Enforce the run time limit.
			if ( isset($this->runTimeLimit) && (microtime(true) - $startTime > $this->runTimeLimit) ) {
				$this->endState = self::STATE_TIME_LIMIT_EXCEEDED;
				$this->log(sprintf('Run time limit of %.2f seconds reached', $this->runTimeLimit));
				break;
			}
		}

		if ( !isset($this->endState) ) {
			$this->endState = self::STATE_ALL_DONE;
			$this->log('The specified file section has been fully processed.');
		}

		//Update progress data.
		if ( !isset($startPosition) ) {
			$startPosition = $parser->getInnerIterator()->getStartPosition();
		}
		$actualEndPosition = $parser->getPositionInFile();

		$this->progress['highRangeStart'] = max($this->progress['highRangeStart'], $startPosition);
		$this->progress['highRangeEnd'] = $actualEndPosition;
		if ( ($actualEndPosition <= $this->progress['lowRangeStart']) || ($this->endState === self::STATE_INTERVAL_START_REACHED) ) {
			$this->progress['lowRangeStart'] = $this->progress['highRangeStart'];
		}

		$this->progress['timeIntervalStart'] = $this->timeIntervalStart;

		$sampleLength = min(self::FILE_SAMPLE_LENGTH, $this->log->getFileSize());
		list($hash, $actualSampleLength) = $this->generateFileHash($sampleLength);
		$this->progress['fileSampleHash'] = $hash;
		$this->progress['fileSampleLength'] = $actualSampleLength;
		$this->progress['processedEntries'] += $processedEntries;

		$this->log(sprintf(
			"Processed log entries: %d, range: (%s -> %s)",
			$processedEntries,
			Elm_Plugin::formatByteCount($startPosition),
			Elm_Plugin::formatByteCount($parser->getPositionInFile())
		));

		return $summary;
	}

	public static function getSummaryKey($message) {
		//This is similar to the DB concept of a "natural key".
		$id = $message;

		//Most messages look like this: "PHP Severity: Lorem ipsum in /path/to/file.php on line 213".
		//Move the file name and line number to the start of the ID to make it work better in a prefix index.
		$separatorPos = strrpos($id, '/');
		$winSeparatorPos = strrpos($id, '\\');
		if ( $winSeparatorPos && ($winSeparatorPos > intval($separatorPos)) ) {
			$separatorPos = $winSeparatorPos;
		}

		if ( $separatorPos ) {
			$suffix = substr($id, $separatorPos + 1);
			if ( preg_match('@^(?P<file>[a-zA-Z\d\-_.\s]+?\.php)(?:\son\sline\s|:)(?P<line>\d++)$@i', $suffix, $matches) ) {
				$id = $matches['file'] . ':' . $matches['line'] . '|' . substr($id, 0, $separatorPos);
			}
		}

		if ( strlen($id) > self::MAX_SUMMARY_KEY_SIZE ) {
			$id = '!' . md5($id);
		}

		return $id;
	}

	private function log($message) {
		if ( class_exists('WP_CLI', false) ) {
			/** @noinspection PhpUndefinedClassInspection */
			WP_CLI::log($message);
		}
	}

	private function calculateWorkRange() {
		//Check if the log file has been cleared or rotated since the last summary update.
		if ( isset($this->progress['fileSampleHash'], $this->progress['fileSampleLength']) ) {
			$hash = $this->generateFileHash($this->progress['fileSampleLength']);
			if ( $hash[0] !== $this->progress['fileSampleHash'] ) {
				$this->discardProgress();
			}
		}

		//Also discard progress if the interval start is earlier (lower) than during the last run.
		//We can't guarantee that we reached the beginning of the new time interval.
		if (
			isset($this->progress['timeIntervalStart'])
			&& ($this->timeIntervalStart < $this->progress['timeIntervalStart'])
			&& ($this->progress['lowRangeStart'] > 0)
		) {
			$this->discardProgress();
		}

		//Should we start reading from the end of the file, or continue from the spot
		//where we stopped the last time? How much of the file should we read?
		$startPosition = null;

		if ( $this->progress['lowRangeStart'] >= $this->progress['highRangeEnd'] ) {
			$endPosition = $this->progress['highRangeStart'];
			return array($startPosition, $endPosition);
		}

		$startPosition = $this->progress['highRangeEnd'];
		$endPosition = $this->progress['lowRangeStart'];
		return array($startPosition, $endPosition);
	}

	private function discardProgress() {
		$this->setProgress(array());
	}

	private function setProgress($progress) {
		if ( $progress === null ) {
			$progress = array();
		}

		$this->progress = array_merge(
			array(
				'lowRangeStart'     => 0,
				'highRangeStart'    => 0,
				'highRangeEnd'      => 0,
				'fileSampleHash'    => null,
				'fileSampleLength'  => null,
				'processedEntries'  => 0,
				'timeIntervalStart' => 0,
			),
			$progress
		);
	}

	private function generateFileHash($sampleLength) {
		$handle = fopen($this->log->getFilename(), 'rb');
		if ( !$handle ) {
			return null;
		}

		fseek($handle, 0, SEEK_SET);
		if ($sampleLength <= 0) {
			$sampleLength = self::FILE_SAMPLE_LENGTH;
		}
		$sample = fread($handle, $sampleLength);
		fclose($handle);

		$hash = md5($sample);

		return array($hash, strlen($sample));
	}

	public function isDone() {
		if  ( !$this->log ) {
			return true;
		}

		//Empty file = there's nothing to do.
		$fileSize = $this->log->getFileSize();
		if ( $fileSize === 0 ) {
			return true;
		}

		list($startPosition, $endPosition) = $this->calculateWorkRange();
		if ( $startPosition === null ) {
			$startPosition = $fileSize;
		}

		return ($endPosition >= $startPosition);
	}

	public function getProgress() {
		return $this->progress;
	}
}