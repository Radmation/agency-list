<?php

class ElmPro_DatabaseSummaryStore extends ElmPro_SummaryStore {
	const MAX_ITEM_BATCH_SIZE = 50;

	private $maxQuerySize = null;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	private $summaryTable = 'elm_summary';
	private $progressTable = 'elm_summary_progress';
	private $dailyStatsTable = 'elm_daily_stats';
	private $hourlyStatsTable = 'elm_hourly_stats';
	/**
	 * @var array
	 */
	private $statsTables;

	/**
	 * @var ElmPro_SummaryItem[]
	 */
	private $pendingItems = array();
	private $preparedItemKeys = '';

	/**
	 * @var string
	 */
	private $fetchItemIdsTemplate;

	const STATS_BATCH_SIZE = 30;
	private $pendingStats = array();

	private $fileIdCache = array();

	public function __construct() {
		$this->clearPendingStats();
		$this->wpdb = $GLOBALS['wpdb'];

		$this->summaryTable = $this->wpdb->base_prefix . $this->summaryTable;
		$this->progressTable = $this->wpdb->base_prefix . $this->progressTable;
		$this->dailyStatsTable = $this->wpdb->base_prefix . $this->dailyStatsTable;
		$this->hourlyStatsTable = $this->wpdb->base_prefix . $this->hourlyStatsTable;

		$this->statsTables = array(
			ElmPro_SummaryItem::DAILY_STATS_KEY  => $this->dailyStatsTable,
			ElmPro_SummaryItem::HOURLY_STATS_KEY => $this->hourlyStatsTable,
		);

		$this->fetchItemIdsTemplate =
			"SELECT id, summaryKey, firstSeenTimestamp, lastSeenTimestamp
 			FROM {$this->summaryTable} WHERE summaryKey IN (%s)";
	}

	/**
	 * Store a batch of summary data in the database, inserting new items and incrementing
	 * the stats of existing items as necessary.
	 *
	 * @param ElmPro_SummaryItem[] $summaryItems
	 */
	public function appendItems($summaryItems) {
		if ( $this->maxQuerySize === null ) {
			$this->calculateMaxQuerySize();
		}

		$this->preparedItemKeys = '';

		foreach ($summaryItems as $item) {
			$this->addItem($item);
		}

		$this->flush();
	}

	/**
	 * @param ElmPro_SummaryItem $item
	 */
	private function addItem($item) {
		$value = $this->wpdb->prepare('%s', $item->summaryKey);

		$predictedLength = strlen($this->preparedItemKeys) + strlen($value) + strlen($this->fetchItemIdsTemplate);
		if ( ($predictedLength > $this->maxQuerySize) || (count($this->pendingItems) >= self::MAX_ITEM_BATCH_SIZE) ) {
			$this->flush();
		}

		if ( $this->preparedItemKeys !== '' ) {
			$this->preparedItemKeys .= ', ';
		}
		$this->preparedItemKeys .= $value;
		$this->pendingItems[$item->summaryKey] = $item;
	}

	private function calculateMaxQuerySize() {
		$size = $this->wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'", 1, 0);
		if ( is_numeric($size) ) {
			$maxAllowedPacket = intval($size);

			$hardLowerLimit = 10 * 1024;
			$hardUpperLimit = 1024 * 1024;
			$this->maxQuerySize = intval(min(max($maxAllowedPacket / 3, $hardLowerLimit), $hardUpperLimit));
		} else {
			$this->maxQuerySize = 250 * 1024;
		}
	}

	/**
	 * Flush all pending items and then flush the stats of the flushed items.
	 */
	private function flush() {
		if ( empty($this->pendingItems) ) {
			return;
		}

		$this->consoleLog(sprintf("Flushing %d items", count($this->pendingItems)));
		$fetchItemIds = sprintf($this->fetchItemIdsTemplate, $this->preparedItemKeys);
		$fetchItemIds .= ' LIMIT ' . count($this->pendingItems);

		$rows = $this->wpdb->get_results($fetchItemIds, ARRAY_A);
		$this->consoleLog(sprintf("Found %d existing items\n", count($rows)));

		$hasEarlierTimestamp = array();
		$hasLaterTimestamp = array();

		foreach ($rows as $row) {
			if ( isset($this->pendingItems[$row['summaryKey']]) ) {
				$item = $this->pendingItems[$row['summaryKey']];
				$item->id = intval($row['id']);

				$firstStoredTimestamp = strtotime($row['firstSeenTimestamp'] . ' UTC');
				if ( $item->firstSeenTimestamp < $firstStoredTimestamp ) {
					$hasEarlierTimestamp[$item->id] = true;
				}
				$lastStoredTimestamp = strtotime($row['lastSeenTimestamp'] . ' UTC');
				if ( $item->lastSeenTimestamp > $lastStoredTimestamp ) {
					$hasLaterTimestamp[$item->id] = true;
				}
			}
		}

		$unchangingColumns = array(
			'summaryKey' => '%s',
			'message'    => '%s',
			'level'      => '%s',
			'levelOrder' => '%d',
			'isIgnored'  => '%d',
		);
		$variableColumns = array(
			'count'              => '%d',
			'firstSeenTimestamp' => '%s',
			'lastSeenTimestamp'  => '%s',
			'firstStackTrace'    => '%s',
			'lastStackTrace'     => '%s',
			'firstContext'       => '%s',
			'lastContext'        => '%s',
			'isFixed'            => '%d',
			'markedAsFixedOn'    => '%s',
		);

		$insertColumns = array_merge($unchangingColumns, $variableColumns);

		static $levelOrder = array(
			'deprecated'  => 2,
			'notice'      => 4,
			'warning'     => 6,
			'parse error' => 10,
			'fatal error' => 10,
		);

		foreach ($this->pendingItems as $item) {
			$columnValues = $item->toSerializableArray();
			if ( isset($levelOrder[$item->level]) ) {
				$columnValues['levelOrder'] = $levelOrder[$item->level];
			}

			if ( $item->id === null ) {
				//Insert a new item.
				$selectedValues = array_intersect_key($columnValues, $insertColumns);
				$formats = array();
				foreach ($selectedValues as $key => $value) {
					$formats[$key] = $insertColumns[$key];
				}

				$success = $this->wpdb->insert($this->summaryTable, $selectedValues, $formats);
				if ( $success ) {
					$item->id = $this->wpdb->insert_id;
				} else {
					$this->errorLog(sprintf("Error: Failed to insert item. %s\n", $this->wpdb->last_error));
				}
			} else {
				//Update an existing item.
				/** @noinspection SqlWithoutWhere The WHERE clause is appended later; see below. */
				$query = "UPDATE {$this->summaryTable} SET `count` = (`count` + %d) ";
				$values = array($item->count);

				if ( !empty($hasEarlierTimestamp[$item->id]) ) {
					$query .= ", firstSeenTimestamp = %s, firstStackTrace = %s, firstContext = %s ";
					$values[] = $columnValues['firstSeenTimestamp'];
					$values[] = $columnValues['firstStackTrace'];
					$values[] = $columnValues['firstContext'];
				}

				if ( !empty($hasLaterTimestamp[$item->id]) ) {
					$query .= ", lastSeenTimestamp = %s, lastStackTrace = %s, lastContext = %s ";
					$values[] = $columnValues['lastSeenTimestamp'];
					$values[] = $columnValues['lastStackTrace'];
					$values[] = $columnValues['lastContext'];
				}

				if ( $item->hasFixedStateChanged() ) {
					$query .= ", isFixed = %d, markedAsFixedOn = %s ";
					$values[] = $columnValues['isFixed'];
					$values[] = $columnValues['markedAsFixedOn'];
				}

				$query .= " WHERE id = %d";
				$values[] = $item->id;

				$query = $this->wpdb->prepare($query, $values);
				$this->wpdb->query($query);
			}

			if ( ($item->id !== null) && ($item->id > 0) ) {
				foreach ($item->getHistoricalStats() as $unit => $stats) {
					foreach ($stats as $intervalStart => $eventCount) {
						$this->addStatPoint($unit, $item, $intervalStart, $eventCount);
					}
				}
			}
		}

		$this->flushStats();

		$this->preparedItemKeys = '';
		$this->pendingItems = array();
		$this->clearPendingStats();
	}

	private function addStatPoint($collection, $item, $intervalStart, $eventCount) {
		$this->pendingStats[$collection][] = array($item->id, $intervalStart, $eventCount);
		if ( count($this->pendingStats[$collection]) >= self::STATS_BATCH_SIZE ) {
			$this->flushStats($collection);
		}
	}

	private function flushStats($collection = null) {
		if ( $collection !== null ) {
			$keys = array($collection);
		} else {
			$keys = array_keys($this->pendingStats);
		}

		foreach ($keys as $key) {
			if ( empty($this->pendingStats[$key]) ) {
				continue;
			}

			$tableName = $this->statsTables[$key];
			$query = "INSERT INTO `{$tableName}`(summaryItemId, intervalStart, eventCount) VALUES %s 
			ON DUPLICATE KEY UPDATE eventCount = eventCount + VALUES(eventCount)";

			$rows = array();
			foreach ($this->pendingStats[$key] as $data) {
				$rows[] = $this->wpdb->prepare('(%d, %s, %d)', $data);
			}
			$query = sprintf($query, implode(', ', $rows));
			$this->wpdb->query($query);

			$this->pendingStats[$key] = array();
		}
	}

	private function clearPendingStats() {
		//Are these units? Periods? Collections?
		$this->pendingStats = array(
			ElmPro_SummaryItem::DAILY_STATS_KEY  => array(),
			ElmPro_SummaryItem::HOURLY_STATS_KEY => array(),
		);
	}

	public function deleteOldData() {
		$dayThreshold = gmdate('Y-m-d', strtotime('-32 days'));
		$hourThreshold = gmdate('Y-m-d H:00:00', strtotime('-48 hours'));

		//Delete summary items that haven't been seen in a long time.
		$this->wpdb->query($this->wpdb->prepare(
			"DELETE FROM `{$this->summaryTable}` WHERE lastSeenTimestamp IS NOT NULL AND lastSeenTimestamp < %s",
			$dayThreshold
		));

		//Delete old statistics.
		$this->wpdb->query($this->wpdb->prepare(
			"DELETE FROM `{$this->dailyStatsTable}` WHERE intervalStart < %s",
			$dayThreshold
		));
		$this->wpdb->query($this->wpdb->prepare(
			"DELETE FROM `{$this->hourlyStatsTable}` WHERE intervalStart < %s",
			$hourThreshold
		));
	}

	public function deleteAllSummaries() {
		$this->wpdb->query("DELETE FROM `{$this->summaryTable}` WHERE 1");
		$this->wpdb->query("DELETE FROM `{$this->progressTable}` WHERE 1");
		$this->fileIdCache = array();
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 * @return array|null
	 */
	public function loadProgress($log) {
		$fileName = wp_normalize_path($log->getFilename());

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare("SELECT * FROM {$this->progressTable} WHERE fileName = %s", $fileName),
			ARRAY_A
		);
		if ( empty($row) ) {
			return null;
		}

		$this->fileIdCache[$fileName] = $row['id'];

		$progress = json_decode($row['progress'], true);
		if ( !empty($progress) ) {
			return $progress;
		}
		return null;
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 * @param array|null $progress
	 */
	public function saveProgress($log, $progress) {
		$fileName = wp_normalize_path($log->getFilename());

		$this->wpdb->query('START TRANSACTION');

		if ( isset($this->fileIdCache[$fileName]) ) {
			$id = $this->fileIdCache[$fileName];
		} else {
			$id = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT id FROM {$this->progressTable} WHERE fileName = %s LIMIT 1",
				$fileName
			));
			if ( $this->wpdb->last_error ) {
				$this->consoleLog($this->wpdb->last_error);
			}
		}

		$data = array('progress' => json_encode($progress), 'summaryUpdatedOn' => gmdate('Y-m-d H:i:s'));

		if ( empty($id) ) {
			$this->wpdb->insert($this->progressTable, array_merge($data, array('fileName' => $fileName)));
		} else {
			$this->wpdb->update($this->progressTable, $data, array('id' => $id), '%s', '%d');
		}
		if ( $this->wpdb->last_error ) {
			$this->consoleLog($this->wpdb->last_error);
		}

		$this->wpdb->query('COMMIT');
		if ( $this->wpdb->last_error ) {
			$this->consoleLog($this->wpdb->last_error);
		}
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 * @return int|null
	 */
	public function getLastUpdate($log) {
		if ( $log === null ) {
			return null;
		}

		$fileName = wp_normalize_path($log->getFilename());

		$dateTime = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT summaryUpdatedOn FROM {$this->progressTable} WHERE fileName = %s",
			$fileName
		));

		if ( empty($dateTime) ) {
			return null;
		}
		return strtotime($dateTime . ' UTC');
	}

	/**
	 * @param array $query
	 * @return ElmPro_SummaryItem[]
	 */
	public function getSummary($query = array()) {
		if ( $this->maxQuerySize === null ) {
			$this->calculateMaxQuerySize();
		}

		$query = array_merge(
			array(
				'orderBy'         => null,
				'limit'           => null,
				'intervalStart'   => 0,
				'includedLevels'  => array(),
				'includeIgnored'  => false,
				'includeFixed'    => false,
				'excludedRegexes' => array(),
			),
			$query
		);

		$items = array();

		$fetchItems = "SELECT * FROM {$this->summaryTable} WHERE 1";

		if ( $query['intervalStart'] > 0 ) {
			$fetchItems .= $this->wpdb->prepare(
				" AND lastSeenTimestamp >= %s ",
				gmdate('Y-m-d H:i:s', $query['intervalStart'])
			);
		}

		if ( !empty($query['includedLevels']) ) {
			$preparedLevels = array();
			$isNullIncluded = false;
			foreach ($query['includedLevels'] as $level) {
				if ( $level === null ) {
					$isNullIncluded = true;
				} else {
					$preparedLevels[] = $this->wpdb->prepare('%s', $level);
				}
			}

			$levelConditions = array();
			if ( !empty($preparedLevels) ) {
				$levelConditions[] = '(level IN (' . implode(', ', $preparedLevels) . '))';
			}
			if ( $isNullIncluded ) {
				$levelConditions[] = '(level is NULL)';
			}

			if ( !empty($levelConditions) ) {
				$fetchItems .= ' AND (' . implode(' OR ', $levelConditions) . ') ';
			}
		}

		if ( empty($query['includeIgnored']) ) {
			$fetchItems .= ' AND (isIgnored = 0) ';
		}
		if ( empty($query['includeFixed']) ) {
			$fetchItems .= ' AND (isFixed = 0) ';
		}

		if ( !empty($query['excludedRegexes']) ) {
			$regexConditions = array();
			foreach ($query['excludedRegexes'] as $pattern) {
				$regexConditions[] = $this->wpdb->prepare('(`message` NOT REGEXP %s)', $pattern);
			}
			if ( !empty($regexConditions) ) {
				$fetchItems .= ' AND (' . implode(' AND ', $regexConditions) . ') ';
			}
		}

		if ( !empty($query['orderBy']) ) {
			$orderClauses = array(
				'count'             => '`count` DESC',
				'level'             => 'levelOrder DESC',
				'lastSeenTimestamp' => 'lastSeenTimestamp DESC',
			);

			$orderBy = array();
			foreach ($query['orderBy'] as $column) {
				if ( isset($orderClauses[$column]) ) {
					$orderBy[] = $orderClauses[$column];
				}
			}
			if ( !empty($orderBy) ) {
				$fetchItems .= "\nORDER BY " . implode(', ', $orderBy);
			}
		}

		if ( !empty($query['limit']) ) {
			$fetchItems .= "\nLIMIT " . intval($query['limit']);
		}

		$rows = $this->wpdb->get_results($fetchItems, ARRAY_A);

		foreach ($rows as $row) {
			$item = ElmPro_SummaryItem::fromSerializableArray($row);
			$items[$item->id] = $item;
		}

		$this->loadStatsFor($items);
		return $items;
	}

	/**
	 * @param ElmPro_SummaryItem[] $items
	 */
	private function loadStatsFor($items) {
		$batch = array();

		foreach ($items as $item) {
			$batch[$item->id] = $item;
			if ( count($batch) >= 20 ) {
				$this->queryItemStats($batch);
				$batch = array();
			}
		}

		if ( !empty($batch) ) {
			$this->queryItemStats($batch);
		}
	}

	/**
	 * @param ElmPro_SummaryItem[] $items
	 */
	private function queryItemStats($items) {
		$ids = array();
		foreach ($items as $item) {
			$ids[] = intval($item->id);
		}
		$ids = implode(', ', $ids);

		$intervals = array(
			ElmPro_SummaryItem::DAILY_STATS_KEY  => strtotime('-31 days'),
			ElmPro_SummaryItem::HOURLY_STATS_KEY => strtotime('-25 hours'),
		);

		foreach ($intervals as $unit => $intervalStart) {
			$tableName = $this->statsTables[$unit];

			$query = sprintf("SELECT * FROM `{$tableName}` WHERE summaryItemId IN (%s)", $ids);
			if ( $intervalStart > 0 ) {
				$query .= $this->wpdb->prepare(
					' AND intervalStart >= %s ',
					ElmPro_Plugin::formatLocalTime('Y-m-d H', $intervalStart) . ':00:00'
				);
			}

			$rows = $this->wpdb->get_results($query, ARRAY_A);

			foreach ($rows as $row) {
				$id = intval($row['summaryItemId']);
				if ( isset($items[$id]) ) {
					$items[$id]->setStatPoint($unit, $row['intervalStart'], intval($row['eventCount']));
				}
			}
		}
	}

	/**
	 * Set whether a message is ignored or not.
	 *
	 * @param string $message
	 * @param bool $isIgnored
	 */
	public function setIgnoredStatus($message, $isIgnored) {
		$this->wpdb->update(
			$this->summaryTable,
			array('isIgnored' => $isIgnored ? 1 : 0),
			array('summaryKey' => ElmPro_SummaryGenerator::getSummaryKey($message)),
			'%d',
			'%s'
		);
	}

	/**
	 * Mark a message as fixed or not fixed.
	 *
	 * @param string $message
	 * @param bool $isFixed
	 * @param int|null $markedAsFixedOn Timestamp. Defaults to the current time when marking something as fixed.
	 */
	public function setFixedStatus($message, $isFixed, $markedAsFixedOn = null) {
		if ( $isFixed && ($markedAsFixedOn === null) ) {
			$markedAsFixedOn = time();
		}

		$data = array('isFixed' => $isFixed ? 1 : 0);
		$formats = array('%d');

		if ( $markedAsFixedOn !== null ) {
			$data['markedAsFixedOn'] = gmdate('Y-m-d H:i:s', $markedAsFixedOn);
			$formats[] = '%s';
		}

		$this->wpdb->update(
			$this->summaryTable,
			$data,
			array('summaryKey' => ElmPro_SummaryGenerator::getSummaryKey($message)),
			$formats,
			'%s'
		);
	}

	/**
	 * @return ElmPro_SummarySizeInfo
	 * @throws RuntimeException
	 */
	public function getDataSize() {
		$dbName = $this->wpdb->get_var('SELECT DATABASE()');
		if ( !is_string($dbName) || empty($dbName) ) {
			throw new RuntimeException('Failed to retrieve the WordPress database name.');
		}

		$summaryTables = array(
			$this->dailyStatsTable,
			$this->hourlyStatsTable,
			$this->progressTable,
			$this->summaryTable,
		);
		$tablePlaceholders = implode(', ', array_fill(0, count($summaryTables), '%s'));

		$values = $summaryTables;
		$values[] = $dbName;

		$query = $this->wpdb->prepare(
			"SELECT `TABLE_NAME`, `TABLE_ROWS`, (`DATA_LENGTH` + `INDEX_LENGTH`) AS size_in_bytes
			 FROM INFORMATION_SCHEMA.tables 
			 WHERE (TABLE_NAME IN ($tablePlaceholders)) AND (`TABLE_SCHEMA` = %s)
			 LIMIT 20",
			$values
		);
		$results = $this->wpdb->get_results($query, ARRAY_A);

		if ( !is_array($results) ) {
			throw new RuntimeException('Could not get table information from INFORMATION_SCHEMA.');
		}

		$totalSizeInBytes = 0;
		$summaryRows = 0;
		$totalRows = 0;
		foreach ($results as $row) {
			$totalSizeInBytes += floatval($row['size_in_bytes']);
			$estimatedRowCount = floatval($row['TABLE_ROWS']);
			$totalRows += $estimatedRowCount;
			if ( $row['TABLE_NAME'] === 'wpx_elm_summary' ) {
				$summaryRows = $estimatedRowCount;
			}
		}

		return new ElmPro_SummarySizeInfo($totalSizeInBytes, $totalRows, $summaryRows);
	}

	private function consoleLog($message) {
		if ( class_exists('WP_CLI', false) ) {
			WP_CLI::log($message);
		}
	}

	private function errorLog($message) {
		$this->consoleLog($message);
		error_log('[error-log-monitor] ' . $message);
	}
}