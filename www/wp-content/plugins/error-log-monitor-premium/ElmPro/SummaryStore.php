<?php

abstract class ElmPro_SummaryStore {
	/**
	 * Store a batch of summary data in the database, inserting new items and incrementing
	 * the stats of existing items as necessary.
	 *
	 * @param ElmPro_SummaryItem[] $summaryItems
	 */
	abstract public function appendItems($summaryItems);

	/**
	 * @return ElmPro_SummaryItem[] $summaryItems
	 */
	abstract public function getSummary();

	/**
	 * @param Elm_PhpErrorLog $log
	 * @param array|null $progress
	 */
	public function saveProgress($log, $progress) {
		//Stub. Does nothing.
	}
}