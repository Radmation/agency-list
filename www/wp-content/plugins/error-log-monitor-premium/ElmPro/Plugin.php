<?php

class ElmPro_Plugin extends Elm_Plugin {
	const SUMMARY_MEMORY_LIMIT = 1000 * 1024;
	const SUMMARY_RUNTIME_LIMIT = 5;
	const MIN_FREE_MEMORY_FOR_SUMMARY = 200 * 1024;

	private $periodicSummaryCronJob = null;
	private $incrementalSummaryCronJob = null;
	private $statsCleanupCronJob = null;

	/**
	 * @var WP_Error|null
	 */
	private $dbUpgradeError = null;

	public function __construct($pluginFile) {
		parent::__construct($pluginFile);

		$this->periodicSummaryCronJob = new scbCron(
			$pluginFile,
			array(
				'interval'      => 3600,
				'action'        => 'elm_start_incremental_summary_update',
				'callback_args' => array(1),
			)
		);
		$this->incrementalSummaryCronJob = new scbCron(
			$pluginFile,
			array(
				'interval' => 1234, //Not actually used. The job is scheduled as needed.
				'action'   => 'elm_continue_incremental_summary_update',
			)
		);
		add_action('elm_start_incremental_summary_update', array($this, 'doIncrementalSummaryUpdate'), 10, 1);
		add_action('elm_continue_incremental_summary_update', array($this, 'doIncrementalSummaryUpdate'), 10, 1);

		$this->statsCleanupCronJob = new scbCron(
			$pluginFile,
			array(
				'interval' => 24 * 3600,
				'callback' => array($this, 'deleteOldSummaryData'),
			)
		);

		add_action('elm_after_widget_footer', array($this, 'installSummaryJobs'));

		if ( class_exists('WP_CLI', false) ) {
			$instance = new ElmPro_Command($this);
			WP_CLI::add_command('error-log-monitor', $instance);
		}

		add_action('admin_init', array($this, 'maybeUpgradeDatabase'));
		if ( function_exists('wsh_elm_fs') ) {
			wsh_elm_fs()->add_action('after_uninstall', array($this, 'deleteDatabaseTables'));
		} else {
			scbUtil::add_uninstall_hook($pluginFile, array($this, 'deleteDatabaseTables'));
		}

		add_action('elm_ignored_status_changed', array($this, 'onIgnoredStatusChanged'), 10, 2);
		add_action('elm_fixed_status_changed', array($this, 'onFixedStatusChanged'), 10, 3);
	}

	protected function getDefaultSettings() {
		$result = parent::getDefaultSettings();

		$result['summary_order'] = 'lastSeenTimestamp';
		$result['summary_interval_length'] = 24 * 30;
		$result['summary_display_limit'] = 20;
		$result['selected_widget_tab'] = 'latest-entries';

		$result['email_content'] = 'summary'; //Either "latest-entries" or "summary".
		$result['db_schema_version'] = null;

		return $result;
	}

	protected function createDashboardWidget() {
		ElmPro_DashboardWidget::getInstance($this->settings, $this);
	}

	/**
	 * @param null $timeLimit
	 * @param null $memoryLimit
	 * @return bool Returns TRUE if there's more work to do, or FALSE if the summary is complete or there was an error.
	 */
	public function updateLogSummary($timeLimit = null, $memoryLimit = null) {
		if ( $this->settings->get('db_schema_version') !== ElmPro_DatabaseSchema::VERSION ) {
			return false;
		}

		$log = Elm_PhpErrorLog::autodetect();
		if ( is_wp_error($log) ) {
			return false;
		}

		if ( $timeLimit === null ) {
			$timeLimit = self::SUMMARY_RUNTIME_LIMIT;
		}
		if ( $memoryLimit === null ) {
			$memoryLimit = self::SUMMARY_MEMORY_LIMIT;
		}

		$remainingMemory = $this->getRemainingMemory();
		if ( $remainingMemory !== null ) {
			//Abort if there's not enough free memory.
			if ( $remainingMemory < self::MIN_FREE_MEMORY_FOR_SUMMARY ) {
				return false;
			}
			//Try not to exceed the memory limit.
			$memoryLimit = min($memoryLimit, $remainingMemory / 2);
		}

		//Use exclusive locks to ensure only one process at a time can update the summary of a specific file.
		//Note that we optimistically assume that we'll eventually get the lock; there's no fallback.
		$lock = new Elm_ExclusiveLock('elm-summary-' . md5($log->getFilename()));
		$lock->acquire();

		$store = $this->getDefaultSummaryStore();
		$progress = $store->loadProgress($log);

		$generator = new ElmPro_SummaryGenerator(
			$log,
			array(
				'minEntryTimestamp' => time() - 30 * 24 * 3600,
				'runTimeLimit'      => $timeLimit,
				'memoryUsageLimit'  => $memoryLimit,
			),
			$progress,
			$this->settings->get('ignored_messages'),
			$this->settings->get('fixed_messages', array())
		);

		$summary = $generator->createSummary();
		$store->appendItems($summary);

		$progress = $generator->getProgress();
		$store->saveProgress($log, $progress);

		$lock->release();

		return !$generator->isDone();
	}

	public function generateFullSummary($memoryLimit = null, $store = null) {
		$log = Elm_PhpErrorLog::autodetect();
		if ( $store === null ) {
			$store = new ElmPro_MemorySummaryStore();
		}
		$progress = null;

		do {
			$generator = new ElmPro_SummaryGenerator(
				$log,
				array(
					'minEntryTimestamp' => 0,
					'runTimeLimit'      => 10,
					'memoryUsageLimit'  => $memoryLimit,
				),
				$progress,
				$this->settings->get('ignored_messages'),
				$this->settings->get('fixed_messages', array())
			);

			$summary = $generator->createSummary();
			$store->appendItems($summary);

			$progress = $generator->getProgress();
			$store->saveProgress($log, $progress);
		} while (!$generator->isDone());

		return $store->getSummary();
	}

	/**
	 * @return ElmPro_DatabaseSummaryStore
	 */
	public function getDefaultSummaryStore() {
		static $store = null;
		if ( $store === null ) {
			$store = new ElmPro_DatabaseSummaryStore();
		}
		return $store;
	}

	/**
	 * @param int $iteration The number of the current incremental update iteration.
	 */
	public function doIncrementalSummaryUpdate($iteration = 1) {
		$isWorkPending = $this->updateLogSummary();

		//If there's more work to do, let's run this method again in a few minutes.
		//However, a high number of iterations could be a sign of bugs or resource exhaustion,
		//so let's limit that to something reasonable.
		if ( $isWorkPending && ($iteration < 10) && !$this->incrementalSummaryCronJob->is_scheduled() ) {
			$delay = min(max($iteration * 60, 90), 30 * 60);
			$this->incrementalSummaryCronJob->do_once($delay, array($iteration + 1));
		}
	}

	public function deleteOldSummaryData() {
		if ( $this->settings->get('db_schema_version') !== ElmPro_DatabaseSchema::VERSION ) {
			return;
		}
		$store = $this->getDefaultSummaryStore();
		$store->deleteOldData();
	}

	public function installSummaryJobs() {
		if ( !$this->periodicSummaryCronJob->is_scheduled() ) {
			$this->periodicSummaryCronJob->reset();
		}
		if ( !$this->statsCleanupCronJob->is_scheduled() ) {
			$this->statsCleanupCronJob->reset();
		}
	}

	public function maybeUpgradeDatabase() {
		$isAdmin = is_multisite() ? is_super_admin() : current_user_can('activate_plugins');
		if ( $isAdmin && ($this->settings->get('db_schema_version') !== ElmPro_DatabaseSchema::VERSION) ) {
			$schema = new ElmPro_DatabaseSchema();
			$result = $schema->upgrade($this->settings->get('db_schema_version'));

			if ( is_wp_error($result) ) {
				$this->dbUpgradeError = $result;
				add_action('all_admin_notices', array($this, 'showDbUpgradeError'));
			} else {
				$this->settings->set('db_schema_version', $result);
			}
		}
	}

	public function showDbUpgradeError() {
		if ( !is_wp_error($this->dbUpgradeError) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			'<strong>Error Log Monitor encountered an error while trying to create or update its database tables.</strong><br>'
			. esc_html($this->dbUpgradeError->get_error_message())
		);
	}

	public function deleteDatabaseTables() {
		$schema = new ElmPro_DatabaseSchema();
		$schema->dropPluginTables();
	}

	/**
	 * Format a timestamp using the current site's timezone.
	 *
	 * @param string $format
	 * @param int $timestamp
	 * @return string
	 */
	public static function formatLocalTime($format, $timestamp) {
		$dt = new DateTime('@' . $timestamp);
		$dt->setTimezone(self::getBlogTimezone());
		return $dt->format($format);
	}

	/**
	 * @return DateTimeZone
	 */
	public static function getBlogTimezone() {
		static $timezone = null;
		if ( $timezone === null ) {
			$tzString = get_option('timezone_string');
			if ( !empty($tzString) ) {
				try {
					$timezone = new DateTimeZone($tzString);
					return $timezone;
				} catch (Exception $exception) {
					//Whoops, that's not a valid timezone. Let's try the the GMT offset next.
				}
			}

			$gmtOffset = get_option('gmt_offset');
			if ( !empty($gmtOffset) ) {
				//As of PHP 5.5.10 it's possible to create a DateTimeZone from an offset.
				$gmtOffset = floatval($gmtOffset);
				$formattedOffset = sprintf('%+06.2f', $gmtOffset);
				$formattedOffset = str_replace(
					array('.00', '.25', '.5', '.75'),
					array(':00', ':15', ':30', ':45'),
					$formattedOffset
				);

				try {
					$timezone = new DateTimeZone($formattedOffset);
					return $timezone;
				} catch (Exception $exception) {
					//That didn't work either, fall back to UTC.
				}
			}

			$timezone = new DateTimeZone('UTC');
		}
		return $timezone;
	}

	protected function prepareEmailNotification($intervalStart) {
		if ( $this->settings->get('email_content', 'summary') === 'summary' ) {
			return $this->prepareSummaryNotification($intervalStart);
		}
		return parent::prepareEmailNotification($intervalStart);
	}

	public function prepareSummaryNotification($intervalStart) {
		//Run a short update step to ensure the summary will have the latest data.
		$this->updateLogSummary(0.5);
		$intervalEnd = time();

		$limit = 10;

		$query = array(
			'orderBy'         => array('level', 'lastSeenTimestamp'),
			'intervalStart'   => $intervalStart ? ($intervalStart + 1) : 0,
			'limit'           => $limit,
			'excludedRegexes' => $this->settings->get('regex_filter_patterns'),
		);

		$includedGroups = $this->getIncludedGroupsForEmail();
		$excludedGroups = array_diff(Elm_SeverityFilter::getAvailableOptions(), $includedGroups);
		if ( !empty($excludedGroups) ) {
			$query['includedLevels'] = Elm_SeverityFilter::groupsToLevels($includedGroups);
		}

		$store = $this->getDefaultSummaryStore();
		$summary = $store->getSummary($query);

		if ( empty($summary) ) {
			return null;
		}

		$itemSeparator = str_repeat('-', 70);
		$horizontalSeparator = str_repeat('-', 70);

		$formattedItems = array();
		$index = 0;

		foreach ($summary as $item) {
			$index++;
			$section = '';

			$section .= $itemSeparator . "\n";
			$message = $this->formatLogMessage($item->message, $item->level);
			$section .= $index . ') ' . $message . "\n";

			$section .= $horizontalSeparator . "\n";

			if ( $item->lastSeenTimestamp ) {
				$section .= sprintf(
					__('Last seen: %1$s ago (%2$s)', 'error-log-monitor') . "\n",
					human_time_diff($item->lastSeenTimestamp),
					$this->formatTimestamp($item->lastSeenTimestamp)
				);
			}

			if ( $item->firstSeenTimestamp ) {
				$section .= sprintf(
					__('First seen: %1$s ago (%2$s)', 'error-log-monitor') . "\n",
					human_time_diff($item->firstSeenTimestamp),
					$this->formatTimestamp($item->firstSeenTimestamp)
				);
			}

			$section .= sprintf(
				__('Total events: %1$d', 'error-log-monitor') . "\n",
				number_format_i18n($item->count)
			);

			$formattedItems[] = $section;
		}

		$body = sprintf(
		/* translators: 1: Site URL*/
			__("New PHP errors have been logged on %1\$s", 'error-log-monitor') . "\n",
			site_url()
		);

		if ( count($formattedItems) < $limit ) {
			$body .= __('Here are the most recent log entries, sorted by severity:', 'error-log-monitor');
		} else {
			$body .= sprintf(
				__('Here are the top %d most recent log entries, sorted by severity:', 'error-log-monitor'),
				$limit
			);
		}
		$body .= "\n\n";

		$body .= implode("\n", $formattedItems);

		return array(
			'body'        => $body,
			'intervalEnd' => $intervalEnd,
		);
	}

	/**
	 * @access private
	 * @param string $message
	 * @param bool $isIgnored
	 */
	public function onIgnoredStatusChanged($message, $isIgnored) {
		$store = $this->getDefaultSummaryStore();
		$store->setIgnoredStatus($message, $isIgnored);
	}

	/**
	 * @access private
	 * @param string $message
	 * @param bool $isFixed
	 * @param null $details
	 */
	public function onFixedStatusChanged($message, $isFixed, $details = null) {
		$store = $this->getDefaultSummaryStore();
		$store->setFixedStatus($message, $isFixed, (isset($details['fixedOn']) ? $details['fixedOn'] : null));
	}
}