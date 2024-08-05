<?php
class ElmPro_WpContext extends ElmPro_Context {
	private $executionStartTime = 0;

	public function __construct() {
		$this->executionStartTime = microtime(true);

		if ( isset($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_URI'] !== '') ) {
			$this->parameters['requestUri'] = strval($_SERVER['REQUEST_URI']);
		}
		if ( isset($_SERVER['HTTP_HOST']) ) {
			$this->parameters['httpHost'] = strval($_SERVER['HTTP_HOST']);
		}
		if ( isset($_SERVER['HTTP_REFERER']) ) {
			$this->parameters['httpReferer'] = strval($_SERVER['HTTP_REFERER']);
		}
		if ( isset($_SERVER['REQUEST_METHOD']) ) {
			$this->parameters['httpMethod'] = strval($_SERVER['REQUEST_METHOD']);
		}
		if ( function_exists('is_ssl') ) {
			$this->parameters['isSSL'] = is_ssl();
		}
	}

	public function snapshot() {
		$now = microtime(true);
		if ( isset($_SERVER['REQUEST_TIME_FLOAT']) ) {
			$executionTime = $now - $_SERVER['REQUEST_TIME_FLOAT'];
		} else {
			$executionTime = $now - $this->executionStartTime;
		}
		$this->parameters['executionTime'] = $executionTime;

		$this->parameters['memoryUsage'] = memory_get_usage();
		$this->parameters['peakMemoryUsage'] = memory_get_peak_usage();
		$this->parameters['phpServerApiName'] = php_sapi_name();

		if ( function_exists('http_response_code') ) {
			$this->parameters['httpResponseCode'] = http_response_code();
		}

		if ( isset($GLOBALS['wp_current_filter']) && is_array($GLOBALS['wp_current_filter']) ) {
			$this->parameters['currentFilterStack'] = $GLOBALS['wp_current_filter'];
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_REQUEST['action']) ) {
			$this->parameters['ajaxAction'] = substr(strval($_REQUEST['action']), 0, 200);
		}

		return parent::snapshot();
	}
}