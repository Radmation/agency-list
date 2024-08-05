<?php

class ElmPro_SummarySizeInfo {
	public $sizeInBytes;
	public $totalRows;
	public $summaryRows;

	/**
	 * @param int|float $sizeInBytes
	 * @param int|float $totalRows
	 * @param int|float $summaryRows
	 */
	public function __construct($sizeInBytes, $totalRows, $summaryRows) {
		$this->sizeInBytes = $sizeInBytes;
		$this->totalRows = $totalRows;
		$this->summaryRows = $summaryRows;
	}
}