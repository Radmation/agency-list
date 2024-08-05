<?php

class ElmPro_SummaryItem implements ArrayAccess {
	const HOURLY_STATS_KEY = 'hourly';
	const DAILY_STATS_KEY = 'daily';

	public $id = null;
	public $summaryKey = null;

	public $message = '';
	public $level = null;

	public $isIgnored = false;

	public $isFixed = false;
	public $markedAsFixedOn = null;
	private $fixedStateChanged = false;

	public $lastSeenTimestamp = 0;
	public $firstSeenTimestamp = 0;

	public $firstStackTrace = null;
	public $lastStackTrace = null;

	public $firstContext = null;
	public $lastContext = null;

	private $historicalStats = array();

	private $startOfHourlyStats = 0;
	private $startOfDailyStats = 0;

	public $count = 0;

	public function __construct($logEntry = null, $summaryKey = null) {
		$this->summaryKey = $summaryKey;

		$dt = new DateTime('@' . time());
		$dt->setTimezone(ElmPro_Plugin::getBlogTimezone());
		$dt->setTime($dt->format('G'), 0);
		$dt->modify('-36 hours');
		$this->startOfHourlyStats = $dt->getTimestamp();

		$dt = new DateTime('@' . time());
		$dt->setTimezone(ElmPro_Plugin::getBlogTimezone());
		$dt->modify('-32 days');
		$dt->setTime(0, 0);
		$this->startOfDailyStats = $dt->getTimestamp();

		$this->historicalStats = array(
			self::HOURLY_STATS_KEY => array(),
			self::DAILY_STATS_KEY  => array(),
		);

		$this->firstSeenTimestamp = time();

		if ( $logEntry !== null ) {
			$this->message = $logEntry['message'];

			if ( !empty($logEntry['timestamp']) ) {
				$this->lastSeenTimestamp = $logEntry['timestamp'];
				$this->firstSeenTimestamp = $this->lastSeenTimestamp;
			}

			if ( isset($logEntry['stacktrace']) ) {
				$this->firstStackTrace = $logEntry['stacktrace'];
				$this->lastStackTrace = $this->firstStackTrace;
			}

			if ( isset($logEntry['context']) ) {
				$this->firstContext = $logEntry['context'];
				$this->lastContext = $this->firstContext;
			}

			if ( isset($logEntry['level']) ) {
				$this->level = $logEntry['level'];
			}
		}
	}

	/**
	 * @param array|null $logEntry
	 */
	public function addEvent($logEntry = null) {
		$this->count++;

		if ( $logEntry === null ) {
			return;
		}

		if ( isset($logEntry['timestamp']) ) {
			$timestamp = $logEntry['timestamp'];

			//Mark recurring "fixed" errors as not fixed.
			if ( $this->isFixed && isset($this->markedAsFixedOn) && ($timestamp > $this->markedAsFixedOn) ) {
				$this->markAsNotFixed();
			}

			if ( $timestamp > $this->lastSeenTimestamp ) {
				$this->lastSeenTimestamp = $timestamp;
				$this->lastStackTrace = !empty($logEntry['stacktrace']) ? $logEntry['stacktrace'] : null;
				$this->lastContext = !empty($logEntry['context']) ? $logEntry['context'] : null;
			} else if ( $timestamp < $this->firstSeenTimestamp ) {
				$this->firstSeenTimestamp = $timestamp;
				$this->firstStackTrace = !empty($logEntry['stacktrace']) ? $logEntry['stacktrace'] : null;
				$this->firstContext = !empty($logEntry['context']) ? $logEntry['context'] : null;
			}

			//Daily/hourly stats use the blog timezone.
			$dt = new DateTime('@' . $timestamp);
			$dt->setTimezone(ElmPro_Plugin::getBlogTimezone());
			if ( $timestamp >= $this->startOfHourlyStats ) {
				$key = $dt->format('Y-m-d H') . ':00:00';
				if ( !isset($this->historicalStats[self::HOURLY_STATS_KEY][$key]) ) {
					$this->historicalStats[self::HOURLY_STATS_KEY][$key] = 0;
				}
				$this->historicalStats[self::HOURLY_STATS_KEY][$key]++;
			}

			if ( $timestamp >= $this->startOfDailyStats ) {
				$key = $dt->format('Y-m-d');
				if ( !isset($this->historicalStats[self::DAILY_STATS_KEY][$key]) ) {
					$this->historicalStats[self::DAILY_STATS_KEY][$key] = 0;
				}
				$this->historicalStats[self::DAILY_STATS_KEY][$key]++;
			}
		}
	}

	/**
	 * Append/sum the statistics of another item to this item.
	 *
	 * @param ElmPro_SummaryItem $item
	 */
	public function appendStats(ElmPro_SummaryItem $item) {
		foreach ($item->historicalStats as $unit => $stats) {
			foreach ($stats as $intervalStart => $count) {
				if ( isset($this->historicalStats[$unit][$intervalStart]) ) {
					$this->historicalStats[$unit][$intervalStart] += $count;
				} else {
					$this->historicalStats[$unit][$intervalStart] = $count;
				}
			}
		}

		$this->count += $item->count;

		if ( $item->firstSeenTimestamp < $this->firstSeenTimestamp ) {
			$this->firstSeenTimestamp = $item->firstSeenTimestamp;
			$this->firstStackTrace = $item->firstStackTrace;
			$this->firstContext = $item->firstContext;
		}

		if ( $item->lastSeenTimestamp > $this->lastSeenTimestamp ) {
			$this->lastSeenTimestamp = $item->lastSeenTimestamp;
			$this->lastStackTrace = $item->lastStackTrace;
			$this->lastContext = $item->lastContext;
		}
	}

	public function toSerializableArray() {
		$result = array(
			'summaryKey'      => $this->summaryKey,
			'message'         => $this->message,
			'level'           => $this->level,
			'count'           => $this->count,
			'isIgnored'       => $this->isIgnored ? 1 : 0,
			'isFixed'         => $this->isFixed ? 1 : 0,
			'markedAsFixedOn' => isset($this->markedAsFixedOn) ? gmdate('Y-m-d H:i:s', $this->markedAsFixedOn) : null,
		);

		//First/last timestamps use UTC.
		foreach (array('firstSeenTimestamp', 'lastSeenTimestamp') as $property) {
			$result[$property] = gmdate('Y-m-d H:i:s', $this->$property);
		}

		$encodedFields = array('firstStackTrace', 'lastStackTrace', 'firstContext', 'lastContext');
		foreach ($encodedFields as $property) {
			$result[$property] = json_encode($this->$property);
		}

		return $result;
	}

	public static function fromSerializableArray($data) {
		$item = new self();

		if ( isset($data['id']) ) {
			$item->id = intval($data['id']);
		}

		$item->message = $data['message'];
		$item->level = $data['level'];
		$item->summaryKey = $data['summaryKey'];
		$item->count = intval($data['count']);
		$item->isIgnored = !empty($data['isIgnored']);

		$item->isFixed = !empty($data['isFixed']);
		if ( isset($data['markedAsFixedOn']) ) {
			$item->markedAsFixedOn = strtotime($data['markedAsFixedOn'] . ' UTC');
		}

		foreach (array('firstSeenTimestamp', 'lastSeenTimestamp') as $property) {
			if ( !empty($data[$property]) ) {
				$item->$property = strtotime($data[$property] . ' UTC');
			}
		}

		$encodedFields = array('firstStackTrace', 'lastStackTrace', 'firstContext', 'lastContext');
		foreach ($encodedFields as $property) {
			if ( isset($data[$property]) ) {
				$item->$property = json_decode($data[$property], true);
			}
		}

		return $item;
	}

	public function getHistoricalStats() {
		return $this->historicalStats;
	}

	public function getDailyStats() {
		return $this->historicalStats[self::DAILY_STATS_KEY];
	}

	public function getHourlyStats() {
		return $this->historicalStats[self::HOURLY_STATS_KEY];
	}

	/**
	 * @param string $unit One of the *_STATS_KEY constants.
	 * @param string $time
	 * @param int $eventCount
	 */
	public function setStatPoint($unit, $time, $eventCount) {
		if ( isset($this->historicalStats[$unit]) ) {
			$this->historicalStats[$unit][$time] = $eventCount;
		}
	}

	public function markAsNotFixed() {
		$oldState = $this->isFixed;
		$this->isFixed = false;
		$this->fixedStateChanged = $this->fixedStateChanged || ($this->isFixed !== $oldState);
	}

	public function hasFixedStateChanged() {
		return $this->fixedStateChanged;
	}

	/**
	 * @param self $other
	 * @return bool
	 */
	public function isEqual($other) {
		$equal = ($this->count === $other->count)
			&& ($this->firstSeenTimestamp === $other->firstSeenTimestamp)
			&& ($this->lastSeenTimestamp === $other->lastSeenTimestamp)
			&& ($this->message === $other->message);

		if ( !$equal ) {
			//echo "One of the basic fields is different\n";
			return false;
		}

		foreach ($this->historicalStats as $unit => $stats) {
			if ( !$this->arraysAreEqual($this->historicalStats[$unit], $other->historicalStats[$unit]) ) {
				/*printf("Item A: %s\n", $this->message);
				printf("Item B: %s\n", $other->message);
				printf("%s stats are different.\n", $unit);*/
				return false;
			}
		}

		foreach (array('firstContext', 'lastContext', 'firstStackTrace', 'lastStackTrace') as $property) {
			if ( !$this->arraysAreEqual($this->$property, $other->$property) ) {
				//printf("%s are not equal\n", $property);
				return false;
			}
		}
		return true;
	}

	private function arraysAreEqual($a, $b) {
		if ( $a === $b ) {
			return true;
		}

		$diff1 = array_diff_assoc($a, $b);
		$diff2 = array_diff_assoc($a, $b);
		if ( !empty($diff1) || !empty($diff2) ) {
			return false;
		}
		return true;
	}

	private function getLogEntryProperty($property) {
		switch ($property) {
			case 'message':
				return $this->message;
			case 'level':
				return $this->level;
			case 'timestamp':
				return $this->lastSeenTimestamp;
			case 'context':
				return $this->lastContext;
			case 'stacktrace':
				return $this->lastStackTrace;
		}
		return null;
	}

	/**
	 * Whether a offset exists
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset) {
		return ($this->getLogEntryProperty($offset) !== null);
	}

	/**
	 * Offset to retrieve
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->getLogEntryProperty($offset);
	}

	/**
	 * Offset to set
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value) {
		//You cannot set item properties using array syntax.
	}

	/**
	 * Offset to unset
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset) {
		//You cannot delete item properties using array syntax.
	}
}