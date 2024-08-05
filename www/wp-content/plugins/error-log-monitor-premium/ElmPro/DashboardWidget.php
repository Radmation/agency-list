<?php

class ElmPro_DashboardWidget extends Elm_DashboardWidget {
	protected $widgetCssPath = 'pro-assets/pro-dashboard-widget.css';

	/**
	 * @var ElmPro_Plugin $plugin
	 */
	protected $plugin;

	private $summaryIntervals = array();
	private $currentSummaryInterval = null;

	public function __construct($settings, $plugin) {
		parent::__construct($settings, $plugin);
		$this->summaryIntervals = array('24h' => 24, '7d' => 7 * 24, '30d' => 30 * 24);

		add_action('elm_after_email_interval_field', array($this, 'displayEmailContentSettings'));
		add_action('elm_after_notification_settings', array($this, 'displaySummarySettings'));
		add_action('elm_handle_widget_settings_form', array($this, 'handleProWidgetSettings'), 10, 1);

		ajaw_v1_CreateAction('elm-save-active-widget-tab')
			->handler(array($this, 'ajaxSaveActiveWidgetTab'))
			->requiredParam('tab')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-delete-summary-data')
			->handler(array($this, 'ajaxDeleteSummaryData'))
			->requiredCap($this->requiredCapability)
			->permissionCallback(array($this, 'userCanDeleteSummary'))
			->register();
	}

	protected function displayContextData($context) {
		$hiddenContextKeys = array(
			'fileName'            => true,
			'lineNumber'          => true,
			'parentEntryPosition' => true,
			'stackTrace'          => true,
			'memoryUsage'         => true,
		);

		static $orderedDisplayNames = [
			'requestUri'         => 'URL',
			'httpReferer'        => 'Referer',
			'httpMethod'         => 'HTTP Method',
			'httpHost'           => 'HTTP Host',
			'httpResponseCode'   => 'HTTP Status',
			'ajaxAction'         => 'ajaxAction',
			'isSSL'              => 'isSSL',
			'currentFilterStack' => 'Current Filter',
			'executionTime'      => 'Execution Time',
			'peakMemoryUsage'    => 'Memory Usage',
			'memoryUsage'        => 'Memory Usage (current)',
			'phpServerApiName'   => 'PHP SAPI',
		];

		//Remove hidden items.
		$context = array_diff_key($context, $hiddenContextKeys);
		if ( empty($context) ) {
			return; //Don't show the context group if there are no visible items.
		}

		//Sort the items in the same order as the above array.
		$context = array_intersect_key(array_merge($orderedDisplayNames, $context), $context);

		$mundaneItems = 0;

		echo '<div class="elm-context-group">',
		'<h3 class="elm-context-group-caption">Context</h3>';
		echo '<table class="elm-context-group-content elm-context-parameters elm-hide-mundane-items">';

		$visibleRowNumber = 0;
		foreach ($context as $name => $value) {
			$displayName = isset($orderedDisplayNames[$name]) ? $orderedDisplayNames[$name] : htmlspecialchars($name);

			$formatted = $this->formatContextItem($name, $value, $context);

			$classes = [];
			if ( $formatted['isMundane'] ) {
				$classes[] = 'elm-is-mundane';
			} else {
				$visibleRowNumber++;
				if ( $visibleRowNumber % 2 === 1 ) {
					$classes[] = 'elm-is-odd-visible-row';
				}
				if ( $visibleRowNumber === 1 ) {
					$classes[] = 'elm-first-non-mundane-item';
				}
			}

			printf('<tr class="%s">', implode(' ', $classes));
			printf(
				'<th scope="row" title="%s">%s</th>',
				htmlspecialchars($name),
				$displayName
			);
			printf('<td>%s</td>', $formatted['value']);
			echo '</tr>';

			if ( $formatted['isMundane'] ) {
				$mundaneItems++;
			}
		}

		if ( $mundaneItems > 0 ) {
			echo '<tr class="elm-more-context-row"><th colspan="2">';
			printf('<a href="#" class="elm-show-mundane-context">Show %d more</a>', $mundaneItems);
			echo '</th></tr>';
		}

		echo '</table>',
		'</div>';
	}

	private function formatContextItem($name, $value, $allContextItems) {
		static $currentSAPI = null;
		if ( $currentSAPI === null ) {
			$currentSAPI = php_sapi_name();
		}

		$isMundane = false;

		switch ($name) {
			case 'memoryUsage':
			case 'peakMemoryUsage':
				$value = Elm_Plugin::formatByteCount($value);
				$isMundane = true;
				break;
			case 'executionTime':
				$value = sprintf('%.3f s', $value);
				$isMundane = true;
				break;
			case 'requestUri':
				$value = htmlspecialchars($value);
				$value = $this->insertUrlBreaks($value);
				$isMundane = ($value === '');
				break;
			case 'currentFilterStack':
				if ( !empty($value) ) {
					$value = implode(', ', array_map('htmlspecialchars', $value));
				} else {
					$isMundane = true;
					$value = '-';
				}
				break;
			case 'httpResponseCode':
				$isMundane = empty($value) || ($value === 200);
				$value = intval($value);
				break;
			case 'isSSL':
				$isMundane = ($value === is_ssl());
				$value = $value ? 'Yes' : 'No';
				break;
			case 'httpHost':
				$isMundane = isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === $value);
				$value = htmlspecialchars($value);
				break;
			case 'phpServerApiName':
				$isMundane = ($value === $currentSAPI);
				$value = htmlspecialchars($value);
				break;
			case 'httpMethod':
				if ( isset($allContextItems['phpServerApiName']) && ($allContextItems['phpServerApiName'] === 'cli') ) {
					$isMundane = true;
				}
				$value = htmlspecialchars($value);
				break;
			case 'httpReferer':
				$isMundane = true;
				$value = htmlspecialchars($value);
				$value = $this->insertUrlBreaks($value);
				break;
			default:
				if ( is_string($value) ) {
					$value = htmlspecialchars($value);
				} else {
					$value = htmlspecialchars(var_export($value, true));
				}
		}

		return array('value' => $value, 'isMundane' => $isMundane);
	}

	protected function insertUrlBreaks($text) {
		return preg_replace('@([?&=(_]|[/\\\]++)@', '<wbr>' . '$1', $text);
	}

	protected function displayContentSection($log) {
		$selectedTab = $this->settings->get('selected_widget_tab', 'latest-entries');
		$tabs = array(
			'summary'        => 'Summary',
			'latest-entries' => 'Latest Entries',
		);
		if ( !array_key_exists($selectedTab, $tabs) ) {
			$selectedTab = 'latest-entries';
		}

		$selectedTabIndex = array_search($selectedTab, array_keys($tabs));
		if ( $selectedTabIndex === false ) {
			$selectedTabIndex = 0;
		}
		?>
		<div class="elm-tab-container" data-elm-default-tab="<?php echo esc_attr($selectedTabIndex); ?>">
			<ul class="elm-widget-tabs">
				<?php
				foreach ($tabs as $slug => $title) {
					printf(
						'<li class="elm-widget-tab %1$s elm-widget-tab-%2$s" data-elm-tab-slug="%2$s"><a href="%3$s">%4$s</a></li>',
						($slug === $selectedTab) ? 'ui-tabs-active' : '',
						esc_attr($slug),
						esc_attr('#elm-' . $slug . '-container'),
						$title
					);
				}
				?>
			</ul>
			<div id="elm-summary-container" <?php
			if ( $selectedTab !== 'summary' ) {
				echo 'style="display:none;"';
			}
			?>><?php $this->displaySummary($log); ?></div>
			<div id="elm-latest-entries-container"<?php
			if ( $selectedTab !== 'latest-entries' ) {
				echo 'style="display:none;"';
			}
			?>><?php $this->displayLatestEntries($log); ?></div>
		</div>
		<div id="elm-export-entry-confirmation" style="display: none;">
			<div>&check; <?php _e('Copied to clipboard.', 'error-log-monitor'); ?></div>
		</div>
		<?php
	}

	/**
	 * @param Elm_PhpErrorLog|null $log
	 */
	protected function displaySummary($log = null) {
		if ( $this->settings->get('db_schema_version') !== ElmPro_DatabaseSchema::VERSION ) {
			$message = __(
				'Error: One or more database tables could not be created or upgraded.',
				'error-log-monitor'
			);
			echo '<p>', $message, '</p>';
			return;
		}

		$store = $this->plugin->getDefaultSummaryStore();
		$lastUpdate = $store->getLastUpdate($log);

		//To give the user the latest data, run a short update step before displaying the summary.
		//However, we don't want to overload the site if there's something that changes the log on every request
		//(e.g. a buggy plugin triggering PHP notices), so let's limit it to one update per minute.
		$timeSinceLastUpdate = ($lastUpdate !== null) ? (time() - $lastUpdate) : time();
		if ( $log && ($log->getFileSize() > 0) && ($timeSinceLastUpdate > 60) ) {
			$generator = new ElmPro_SummaryGenerator(
				$log,
				array('minEntryTimestamp' => time() - 30 * 24 * 3600),
				$store->loadProgress($log),
				$this->settings->get('ignored_messages', array()),
				$this->settings->get('fixed_messages', array())
			);

			if ( !$generator->isDone() ) {
				$this->plugin->updateLogSummary(0.5);
				$lastUpdate = $store->getLastUpdate($log);
			}
		}

		$orderOptions = array(
			'lastSeenTimestamp' => array('lastSeenTimestamp'),
			'count'             => array('count', 'lastSeenTimestamp'),
			'level'             => array('level', 'count', 'lastSeenTimestamp'),
		);

		if ( isset($_GET['elm-summary-order']) && array_key_exists($_GET['elm-summary-order'], $orderOptions) ) {
			$selectedOrder = strval($_GET['elm-summary-order']);
			$this->settings->set('summary_order', $selectedOrder);
		} else {
			$selectedOrder = $this->settings->get('summary_order', 'lastSeenTimestamp');
		}

		if ( isset($_GET['elm-summary-interval']) && in_array($_GET['elm-summary-interval'], $this->summaryIntervals) ) {
			$selectedInterval = intval($_GET['elm-summary-interval']);
			$this->settings->set('summary_interval_length', $selectedInterval);
		} else {
			$selectedInterval = $this->settings->get('summary_interval_length', 24);
		}
		$this->currentSummaryInterval = $selectedInterval;

		if ( isset($_GET['elm-summary-limit']) && is_numeric($_GET['elm-summary-limit']) ) {
			$limit = min(max(intval($_GET['elm-summary-limit']), 5), 50);
			$this->settings->set('summary_display_limit', $limit);
		} else {
			$limit = $this->settings->get('summary_display_limit', 20);
		}

		$this->displaySummaryControls($selectedOrder, $selectedInterval);

		$query = array(
			'orderBy'         => $orderOptions[$selectedOrder],
			'intervalStart'   => time() - $selectedInterval * 3600,
			'limit'           => $limit,
			'excludedRegexes' => $this->settings->get('regex_filter_patterns'),
		);

		$includedGroups = $this->plugin->getIncludedGroupsForDashboard();
		//We only need to filter by the severity level if at least one group is excluded.
		$excludedGroups = array_diff(Elm_SeverityFilter::getAvailableOptions(), $includedGroups);
		if ( !empty($excludedGroups) ) {
			$query['includedLevels'] = Elm_SeverityFilter::groupsToLevels($includedGroups);
		}

		$summary = $store->getSummary($query);

		if ( empty($summary) ) {
			$message = __('There are no results for the selected time period.', 'error-log-monitor');
			if ( $lastUpdate === null ) {
				if ( $log->getFileSize() == 0 ) {
					$message = __('The log file is empty.', 'error-log-monitor');
				} else {
					$message = __('The plugin is still analysing the log file. Please check back later.', 'error-log-monitor');
				}
			}
			echo '<p>', $message, '</p>';
		} else {
			$this->displayLogAsList(
				$summary,
				array('elm-summary'),
				array(
					'inContext'       => array($this, 'displayAdditionalContext'),
					'displayMetadata' => array($this, 'displayItemMetadata'),
				)
			);
		}

		if ( $lastUpdate !== null ) {
			echo '<p>';
			printf(__('The summary was last updated %s ago.', 'error-log-monitor'), human_time_diff($lastUpdate));
			echo '</p>';
		}
	}

	protected function displaySummaryControls($selectedOrder, $selectedInterval) {
		echo '<div class="elm-summary-controls">';

		printf(
			'<form class="elm-summary-order-container" method="get" action="%s">',
			esc_attr(self_admin_url())
		);
		echo '<select class="elm-summary-order" name="elm-summary-order">';
		$orderOptions = array(
			'Most recent first'   => 'lastSeenTimestamp',
			'Most common first'   => 'count',
			'Highest level first' => 'level',
		);
		foreach ($orderOptions as $label => $value) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr($value),
				($value === $selectedOrder) ? ' selected="selected" ' : '',
				$label
			);
		}
		echo '</select>';
		echo '</form>';

		echo '<ul class="elm-summary-interval-selector">';
		foreach ($this->summaryIntervals as $label => $hours) {
			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				($hours === $selectedInterval) ? 'elm-current' : '',
				esc_attr(add_query_arg('elm-summary-interval', $hours, self_admin_url())),
				esc_html($label)
			);
		}
		echo '</ul>';

		echo '</div>';
	}

	/**
	 * @param ElmPro_SummaryItem $item
	 * @throws Exception
	 */
	protected function displayAdditionalContext($item) {
		if ( $this->currentSummaryInterval <= 72 ) {
			$period = ElmPro_SummaryItem::HOURLY_STATS_KEY;
			$heading = __('Last 24 Hours', 'error-log-monitor');
		} else {
			$period = ElmPro_SummaryItem::DAILY_STATS_KEY;
			$heading = __('Last 30 Days', 'error-log-monitor');
		}

		echo '<div class="elm-context-group elm-context-chart">
			<h3 class="elm-context-group-caption">', $heading, '</h3>';

		echo '<div class="elm-context-group-content">';
		$this->displayGraph($item, $period);
		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param ElmPro_SummaryItem $item
	 */
	protected function displayItemMetadata($item) {
		echo '<p class="elm-entry-metadata">';

		$this->displayEventCount($item);

		$lastEventDiff = human_time_diff($item->lastSeenTimestamp);
		$firstEventDiff = $item->firstSeenTimestamp ? human_time_diff($item->firstSeenTimestamp) : '';

		printf(
			'<span class="elm-summary-timestamp" title="%s">%s</span>',
			esc_attr('Last seen: ' . ElmPro_Plugin::formatLocalTime('Y-m-d H:i:s e', $item->lastSeenTimestamp)),
			sprintf(
				_x('%s ago', 'human-readable timestamp', 'error-log-monitor'),
				$lastEventDiff
			)
		);

		if ( ($firstEventDiff !== $lastEventDiff) && ($firstEventDiff !== '') ) {
			echo '&nbsp;&middot;&nbsp;';
			printf(
				'<span class="elm-summary-item-age" title="%s">%s</span>',
				esc_attr('First seen: ' . ElmPro_Plugin::formatLocalTime('Y-m-d H:i:s e', $item->firstSeenTimestamp)),
				sprintf(_x('%s old', 'item age', 'error-log-monitor'), human_time_diff($item->firstSeenTimestamp))
			);
		}

		echo '</p>';
	}

	protected function displayEventCount($item) {
		$total = $item->count;
		printf(
			'<span class="elm-severity-bubble elm-summary-event-count" title="%s">%s</span>',
			number_format_i18n($total),
			$this->formatEventCount($total)
		);
	}

	/**
	 * @param int|float $total
	 * @return string
	 */
	protected function formatEventCount($total) {
		if ( $total < 1000 ) {
			return (string)$total;
		}
		if ( $total < 10000 ) {
			$decimals = 1;
		} else {
			$decimals = 0;
		}
		return number_format_i18n(($total / 1000), $decimals) . 'k';
	}

	/**
	 * @param ElmPro_SummaryItem $item
	 * @param $period
	 * @param null $intervalStart
	 * @throws Exception
	 */
	private function displayGraph($item, $period = null, $intervalStart = null) {
		if ( $period === null ) {
			$period = ElmPro_SummaryItem::DAILY_STATS_KEY;
		}
		$stats = $item->getHistoricalStats();
		$stats = $stats[$period];

		$dt = new DateTime('now', ElmPro_Plugin::getBlogTimezone());
		if ( $intervalStart === null ) {
			if ( $period === ElmPro_SummaryItem::DAILY_STATS_KEY ) {
				$dt->modify('-30 days');
				$dt->setTime(0, 0);
				$intervalStart = $dt->getTimestamp();
			} else {
				$dt->modify('-24 hours');
				$dt->setTime($dt->format('H'), 0);
				$intervalStart = $dt->getTimestamp();
			}
		}

		$dt = new DateTime('now', ElmPro_Plugin::getBlogTimezone());
		if ( $period === ElmPro_SummaryItem::DAILY_STATS_KEY ) {
			$dt->setTime(0, 0, 0);
			$intervalEnd = $dt->getTimestamp();
			$keyFormat = 'Y-m-d';
		} else {
			$dt->setTime($dt->format('G'), 0, 0);
			$intervalEnd = $dt->getTimestamp();
			$keyFormat = 'Y-m-d H:i:s';
		}

		$intervalLength = ($period === ElmPro_SummaryItem::DAILY_STATS_KEY) ? (24 * 3600) : 3600;

		$graphPoints = array();
		$maxValue = 0;
		for ($t = $intervalStart; $t <= $intervalEnd; $t = $t + $intervalLength) {
			$key = ElmPro_Plugin::formatLocalTime($keyFormat, $t);
			$value = isset($stats[$key]) ? $stats[$key] : 0;
			$graphPoints[$key] = $value;
			$maxValue = max($maxValue, $value);
		}

		$verticalAxisMax = max($maxValue, 10);

		echo '<ol class="elm-chart">';
		foreach ($graphPoints as $key => $value) {
			printf(
				'<li class="elm-chart-point %s" style="height: %.2f%%" title="%s"></li>',
				($value === 0) ? ' elm-chart-zero-point' : '',
				round(max(($value / $verticalAxisMax) * 100, 5), 2),
				esc_attr($key . ': ' . sprintf(_n('%d event', '%d events', $value, 'error-log-monitor'), $value))
			);
		}
		echo '</ol>';
	}

	protected function getItemActionLinks() {
		$links = parent::getItemActionLinks();
		$links[] = $this->getExportItemLink();
		return $links;
	}

	private function getExportItemLink() {
		static $html = null;
		if ( $html === null ) {
			$html = sprintf(
				'<a href="#" class="elm-export-entry" title="%s">%s</a>',
				esc_attr(__('Copy the log entry to the clipboard as plain text', 'error-log-monitor')),
				_x('Copy', 'action link', 'error-log-monitor')
			);
		}
		return $html;
	}

	/**
	 * Save the active tab so that we can select it again when the user reloads the page.
	 *
	 * @param array $params
	 * @return array
	 */
	public function ajaxSaveActiveWidgetTab($params) {
		$validTabs = array('summary', 'latest-entries');
		if ( in_array($params['tab'], $validTabs) ) {
			$this->settings->set('selected_widget_tab', $params['tab']);
			return array('success' => true);
		} else {
			return array('success' => false);
		}
	}

	public function displayEmailContentSettings() {
		printf(
			'<p>%s <br>',
			__('What to include in the email:', 'error-log-monitor')
		);

		$contentOptions = array(
			'summary'        => __('Summary of recent messages', 'error-log-monitor'),
			'latest-entries' => sprintf(
				__('Last %d messages from the log', 'error-log-monitor'),
				$this->settings->get('email_line_count')
			),
		);
		foreach ($contentOptions as $option => $label) {
			printf(
				'<label><input type="radio" name="%s[email_content]" value="%s" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr($option),
				($this->settings->get('email_content', 'summary') === $option) ? ' checked="checked"' : '',
				$label
			);
		}
		echo '</p>';
	}

	public function displaySummarySettings() {
		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Summary', 'summary settings heading', 'error-log-monitor')
		);

		printf(
			'<p><label>%s <br><input type="text" name="%s[summary_display_limit]" value="%d" size="5"></label></p>',
			_x('Number of items to show:', 'visible summary items', 'error-log-monitor'),
			esc_attr($this->widgetId),
			esc_attr($this->settings->get('summary_display_limit'))
		);

		$store = $this->plugin->getDefaultSummaryStore();
		try {
			$sizeInfo = $store->getDataSize();
		} catch (RuntimeException $ex) {
			//This should never happen if the plugin has been installed successfully
			//and the database is MySQL compatible.
			$sizeInfo = null;
		}

		if ( ($sizeInfo !== null) && ($sizeInfo->totalRows > 1) ) {
			echo '<p id="elm-summary-size-panel">';
			printf(
				_x(
					'Summary size: %1$s',
					'summary data size in the WordPress database',
					'error-log-monitor'
				) . ' ',
				Elm_Plugin::formatByteCount($sizeInfo->sizeInBytes)
			);
			printf(
				_nx(
					'(%d row)',
					'(%d rows)',
					$sizeInfo->totalRows,
					'total number of rows in all database tables that store log summary data',
					'error-log-monitor'
				),
				$sizeInfo->totalRows
			);

			if ( $this->userCanDeleteSummary() ) {
				echo '<br>';

				//Normally, the plugin automatically deletes old summary entries after one month.
				printf(
					'<button class="button-link button-link-delete" id="elm-delete-summary-data" 
				data-progress-text="%s" data-confirmation-text="%s">%s</button>',
					esc_attr(_x(
						'Deleting...',
						'progress text when deleting summary data',
						'error-log-monitor'
					)),
					esc_attr(__(
						"Delete all summary data?\n\nTip: Normally, the plugin deletes old summary entries after one month of inactivity. This action will delete all entries immediately. The plugin will automatically build a new summary.",
						'error-log-monitor'
					)),
					_x('Delete Summary Data', 'button title', 'error-log-monitor')
				);
			}
			echo '</p>';
		}
	}

	public function handleProWidgetSettings($formInputs = array()) {
		$emailContent = strval($formInputs['email_content']);
		if ( in_array($emailContent, array('summary', 'latest-entries')) ) {
			$this->settings->set('email_content', $emailContent);
		}

		if ( isset($formInputs['summary_display_limit']) ) {
			$summaryDisplayLimit = intval($formInputs['summary_display_limit']);
			if ( $summaryDisplayLimit <= 0 ) {
				$summaryDisplayLimit = $this->settings->get_defaults('summary_display_limit');
			}
			$summaryDisplayLimit = max(min($summaryDisplayLimit, 50), 5);
			$this->settings->set('summary_display_limit', $summaryDisplayLimit);
		}
	}

	public function enqueueWidgetDependencies($hook) {
		parent::enqueueWidgetDependencies($hook);

		if ( $hook === 'index.php' ) {
			wp_register_script(
				'elm-pro-clipboard-js',
				plugins_url('pro-assets/clipboard.min.js', $this->plugin->getPluginFile()),
				array(),
				'20190129'
			);

			wp_enqueue_script(
				'elm-pro-dashboard-widget',
				plugins_url('pro-assets/pro-widget.js', $this->plugin->getPluginFile()),
				array('jquery', 'jquery-ui-tabs', 'jquery-ui-position', 'jquery-effects-fade', 'elm-pro-clipboard-js'),
				'20190129'
			);
		}
	}

	public function displayProSection() {
		echo '<p>';

		$links = array();

		if ( wsh_elm_fs()->is_registered() ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_attr(wsh_elm_fs()->get_account_url()),
				_x('Account', 'Freemius account link', 'error-log-monitor')
			);
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_attr(wsh_elm_fs()->contact_url()),
			_x('Contact Us', 'contact link in widget settings', 'error-log-monitor')
		);

		if ( is_callable(array(wsh_elm_fs(), 'get_pricing_cta_label')) ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_attr(wsh_elm_fs()->pricing_url()),
				wsh_elm_fs()->get_pricing_cta_label()
			);
		}

		echo implode(' | ', $links);

		echo '</p>';
	}

	/**
	 * @return bool
	 */
	public function userCanDeleteSummary() {
		return $this->userCanChangeSettings();
	}

	public function ajaxDeleteSummaryData() {
		$store = $this->plugin->getDefaultSummaryStore();
		if ( $store !== null ) {
			$store->deleteAllSummaries();
			return array(
				'success' => true,
				'message' => __('Summary data deleted successfully.', 'error-log-monitor'),
			);
		}
		return new WP_Error('elm_store_not_found', 'Default summary store is not available.', 500);
	}

	public static function getInstance($settings, $plugin) {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self($settings, $plugin);
		}
		return $instance;
	}
}