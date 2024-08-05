<?php

class ElmPro_MemorySummaryStore extends ElmPro_SummaryStore {
	/**
	 * @var ElmPro_SummaryItem[]
	 */
	private $summary = array();

	public function appendItems($summaryItems) {
		foreach ($summaryItems as $item) {
			if ( isset($this->summary[$item->summaryKey]) ) {
				$baseItem = $this->summary[$item->summaryKey];
				$baseItem->appendStats($item);
			} else {
				$this->summary[$item->summaryKey] = $item;
			}
		}
	}

	public function getSummary() {
		return $this->summary;
	}
}