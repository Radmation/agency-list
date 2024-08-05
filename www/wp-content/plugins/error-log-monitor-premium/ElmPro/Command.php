<?php

class ElmPro_Command {
	/**
	 * @var ElmPro_Plugin
	 */
	private $plugin;

	public function __construct($plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * Test summary generation by creating two summaries using different settings and comparing the results.
	 *
	 * Warning: This will delete all summary data from the database and replace it with test results.
	 * Do not use on a production site.
	 */
	public function test() {
		WP_CLI::line(str_repeat('-', 80));
		WP_CLI::line('# First run');
		WP_CLI::line(str_repeat('-', 80));

		$dbStore = new ElmPro_DatabaseSummaryStore();
		$dbStore->deleteAllSummaries();
		$summary = $this->plugin->generateFullSummary(700 * 1024, $dbStore);
		$total = count($summary);

		WP_CLI::line(str_repeat('-', 80));
		WP_CLI::line('# Second run');
		WP_CLI::line(str_repeat('-', 80));

		$comparisonSummary = $this->plugin->generateFullSummary(3000 * 1024);

		WP_CLI::line(str_repeat('-', 80));
		WP_CLI::line('');

		usort($summary, function ($a, $b) {
			return $a->count - $b->count;
		});
		usort($comparisonSummary, function ($a, $b) {
			return $a->count - $b->count;
		});

		if ( assert($this->summariesAreEqual($summary, $comparisonSummary), 'Summaries are identical') ) {
			WP_CLI::line('OK: Both summaries are the same');
		} else {
			WP_CLI::error('Summaries are different');
		}

		$summary = array_slice($summary, -5);
		foreach ($summary as $item) {
			WP_CLI::line(str_repeat('=', 80));

			$wrappedLines = wordwrap($item->message, 75, "\n| ");
			WP_CLI::line('| ' . $wrappedLines);
			WP_CLI::line('+' . str_repeat('-', 79));
			WP_CLI::line(sprintf(
				'| Total: %3d | %s - %s',
				$item->count,
				gmdate('Y-m-d H:i:s', $item->firstSeenTimestamp),
				gmdate('Y-m-d H:i:s', $item->lastSeenTimestamp)
			));
			WP_CLI::line(str_repeat('=', 80));
			WP_CLI::line('');
		}

		/** @noinspection PhpUndefinedClassInspection */
		WP_CLI::success(sprintf('%d summary items', $total));
	}

	private function summariesAreEqual($a, $b) {
		if ( count($a) !== count($b) ) {
			WP_CLI::error(sprintf("Arrays have a different number of items: %d vs %d", count($a), count($b)));
			return false;
		}

		$diffKeysA = array_diff_key($a, $b);
		$diffKeysB = array_diff_key($a, $b);
		if ( count($diffKeysA) > 0 || count($diffKeysB) > 0 ) {
			WP_CLI::error("Arrays have different keys");
			return false;
		}

		foreach ($a as $key => $value) {
			if ( !$value->isEqual($b[$key]) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Incrementally update the log summary. You may need to run this multiple times to process the whole log.
	 *
	 * @subcommand update-summary
	 */
	public function updateSummary() {
		$this->plugin->updateLogSummary();
		WP_CLI::success('Done');
	}

	/**
	 * Delete summary data that's older than a month.
	 *
	 * @subcommand cleanup-summary
	 */
	public function cleanupSummary() {
		$store = $this->plugin->getDefaultSummaryStore();
		$store->deleteOldData();
		WP_CLI::success('Old summary data and statistics deleted.');
	}

	/**
	 * Delete all summary data from the database. Does not delete ELM tables.
	 *
	 * @subcommand delete-summary
	 */
	public function deleteSummary() {
		$store = $this->plugin->getDefaultSummaryStore();
		$store->deleteAllSummaries();
		WP_CLI::success('All log summaries deleted.');
	}
}