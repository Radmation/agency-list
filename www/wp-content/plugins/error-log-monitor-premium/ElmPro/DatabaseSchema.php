<?php

class ElmPro_DatabaseSchema {
	const VERSION = 2;

	private $lockFileHandle;
	private $maxLockAttempts = 3;

	/**
	 * @param int|null $fromVersion
	 * @return int|WP_Error Returns the new schema version number, or false if there was an error.
	 */
	public function upgrade($fromVersion = null) {
		global $wpdb;
		/** @var wpdb $wpdb */

		if ( $fromVersion === self::VERSION ) {
			//The database structure is already up to date.
			return self::VERSION;
		}
		if ( ($fromVersion !== null) && ($fromVersion > self::VERSION) ) {
			//We cannot downgrade from a higher version.
			return new WP_Error(
				'elm_cannot_downgrade',
				'The database contains information from a newer version of the plugin. '
				. ' It\'s not possible to downgrade. Please install the latest version or reinstall the plugin.'
			);
		}

		if ( $fromVersion !== null ) {
			if ( ($fromVersion === 1) && (self::VERSION === 2) ) {
				return $this->upgradeFrom1To2();
			}

			//We don't know how to upgrade from that version.
			return new WP_Error(
				'elm_cannot_upgrade',
				sprintf('Cannot upgrade database schema from version %d to version %d.', $fromVersion, self::VERSION)
			);
		}

		if ( !$this->serverSupportsInnoDb() ) {
			return new WP_Error(
				'elm_no_innodb',
				'This plugin requires the InnoDB database engine.'
			);
		}
		if ( !$this->serverSupportsRealUtf8() ) {
			return new WP_Error(
				'elm_no_utf8',
				'This plugin requires a database that supports the utf8mb4_bin collation, such as MySQL 5.5.3 or later.'
			);
		}

		if ( !$this->acquireLock() ) {
			return new WP_Error(
				'elm_lock_failed',
				'Failed to acquire an exclusive lock.'
			);
		}

		$success = false;
		if ( $fromVersion === null ) {
			$success = $this->doInitialSetup();
		}

		$this->releaseLock();

		if ( $success ) {
			return self::VERSION;
		} else {
			return new WP_Error(
				'elm_unknown_schema_upgrade_error',
				sprintf('An unexpected database error occurred: "%s"', $wpdb->last_error)
			);
		}
	}

	protected function doInitialSetup() {
		global $wpdb;
		/** @var wpdb $wpdb */

		$queries = array(
			"CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}elm_summary` (
			  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `summaryKey` text COLLATE utf8mb4_bin NOT NULL,
			  `message` text COLLATE utf8mb4_bin NOT NULL,
			  `level` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
			  `levelOrder` tinyint(4) NOT NULL DEFAULT '0',
			  `count` int(10) UNSIGNED NOT NULL DEFAULT '0',
			  `isIgnored` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
			  `firstSeenTimestamp` datetime DEFAULT NULL,
			  `lastSeenTimestamp` datetime DEFAULT NULL,
			  `firstContext` text COLLATE utf8mb4_bin,
			  `lastContext` text COLLATE utf8mb4_bin,
			  `firstStackTrace` text COLLATE utf8mb4_bin,
			  `lastStackTrace` text COLLATE utf8mb4_bin,
			  `isFixed` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
			  `markedAsFixedOn` datetime DEFAULT NULL,
			  `logFileId` int(10) UNSIGNED DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  KEY `idx_elm_summary_key_prefix` (`summaryKey`(10)),
  			  KEY `idx_elm_lastSeenTimestamp` (`lastSeenTimestamp`),
  			  KEY `idx_elm_level` (`level`(10))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;",

			"CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}elm_summary_progress` (
			  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `fileName` varchar(1000) COLLATE utf8mb4_bin NOT NULL,
			  `summaryUpdatedOn` datetime DEFAULT NULL,
			  `progress` text COLLATE utf8mb4_bin NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `idx_elm_log_file_name` (`fileName`(10))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;",

			"CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}elm_daily_stats` (
			  `summaryItemId` int(10) UNSIGNED NOT NULL,
			  `intervalStart` date NOT NULL,
			  `eventCount` int(10) UNSIGNED NOT NULL DEFAULT '0',
			  UNIQUE KEY `idx_elm_unique_item_day` (`summaryItemId`,`intervalStart`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;",

			"CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}elm_hourly_stats` (
			  `summaryItemId` int(10) UNSIGNED NOT NULL,
			  `intervalStart` datetime NOT NULL,
			  `eventCount` int(10) UNSIGNED NOT NULL DEFAULT '0',
			  UNIQUE KEY `idx_elm_unique_item_hour` (`summaryItemId`,`intervalStart`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;",
		);

		foreach ($queries as $sql) {
			$success = $wpdb->query($sql);
			if ( $success === false ) {
				return false;
			}
		}

		$constraintQueries = array(
			'fk_elm_hourly_stats_to_item' => "ALTER TABLE `{$wpdb->base_prefix}elm_daily_stats`
  			ADD CONSTRAINT `fk_elm_hourly_stats_to_item` 
  				FOREIGN KEY (`summaryItemId`) REFERENCES `{$wpdb->base_prefix}elm_summary` (`id`) ON DELETE CASCADE;",

			'fk_elm_daily_stats_to_item' => "ALTER TABLE `{$wpdb->base_prefix}elm_hourly_stats`
  			ADD CONSTRAINT `fk_elm_daily_stats_to_item` 
  				FOREIGN KEY (`summaryItemId`) REFERENCES `{$wpdb->base_prefix}elm_summary` (`id`) ON DELETE CASCADE;",
		);

		$dbName = $wpdb->dbname;
		foreach ($constraintQueries as $constraintName => $sql) {
			//Check if the constraint already exists.
			$checkSql = $wpdb->prepare(
				"SELECT CONSTRAINT_NAME
				 FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
				 WHERE CONSTRAINT_SCHEMA = %s
				 AND CONSTRAINT_NAME = %s
				 LIMIT 1",
				$dbName,
				$constraintName
			);
			$foundName = $wpdb->get_var($checkSql);
			if ( !empty($foundName) ) {
				continue;
			}

			$success = $wpdb->query($sql);
			if ( $success === false ) {
				return false;
			}
		}

		return true;
	}

	protected function acquireLock() {
		$this->lockFileHandle = @fopen(__FILE__, 'rb');
		if ( !$this->lockFileHandle ) {
			return false;
		}

		for ($attempt = 1; $attempt <= $this->maxLockAttempts; $attempt++) {
			if ( flock($this->lockFileHandle, LOCK_EX) ) {
				return true;
			};
		}

		fclose($this->lockFileHandle);
		return false;
	}

	protected function releaseLock() {
		flock($this->lockFileHandle, LOCK_UN);
		fclose($this->lockFileHandle);
	}

	protected function serverSupportsInnoDb() {
		global $wpdb;
		/** @var wpdb $wpdb */
		$status = $wpdb->get_var("SELECT SUPPORT FROM INFORMATION_SCHEMA.ENGINES WHERE ENGINE = 'InnoDB'");
		return $status && (($status === 'YES') || ($status === 'DEFAULT'));
	}

	protected function serverSupportsRealUtf8() {
		global $wpdb;
		/** @var wpdb $wpdb */
		$rows = $wpdb->get_results("SHOW COLLATION LIKE 'utf8mb4_bin'", ARRAY_A);
		return !empty($rows);
	}

	public function dropPluginTables() {
		global $wpdb;
		/** @var wpdb $wpdb */

		//The order is important: we need to drop dependent tables before the tables that they reference.
		$tables = array('elm_hourly_stats', 'elm_daily_stats', 'elm_summary', 'elm_summary_progress');
		$prefixedTables = array();
		foreach ($tables as $table) {
			$prefixedTables[] = $wpdb->base_prefix . $table;
		}

		return $wpdb->query('DROP TABLE IF EXISTS ' . implode(', ', $prefixedTables));
	}

	protected function upgradeFrom1To2() {
		global $wpdb;

		$summaryTable = $wpdb->base_prefix . 'elm_summary';
		$newSummaryColumns = array(
			'isFixed'         => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
			'markedAsFixedOn' => "datetime DEFAULT NULL",
		);

		if ( !$this->acquireLock() ) {
			return new WP_Error(
				'elm_lock_failed',
				'Failed to acquire an exclusive lock.'
			);
		}

		$allQueriesSucceeded = true;
		$lastError = 'No error';
		foreach ($newSummaryColumns as $column => $definition) {
			if ( $this->columnExists($summaryTable, $column) ) {
				continue;
			}

			$query = "ALTER TABLE `$summaryTable` ADD COLUMN `{$column}` {$definition};";
			$success = $wpdb->query($query);
			if ( $success === false ) {
				$lastError = $wpdb->last_error;
				$allQueriesSucceeded = false;
				break;
			}
		}

		$this->releaseLock();

		if ( $allQueriesSucceeded ) {
			return 2;
		} else {
			return new WP_Error(
				'elm_unknown_schema_upgrade_error',
				sprintf('An unexpected database error occurred while upgrading to version 2 : "%s"', $lastError)
			);
		}
	}

	/**
	 * @param string $table
	 * @param string $column
	 * @return bool
	 */
	protected function columnExists($table, $column) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT * FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s
			 LIMIT 1",
			$table,
			$column
		);

		$row = $wpdb->get_row($query, ARRAY_A);
		return !empty($row);
	}
}