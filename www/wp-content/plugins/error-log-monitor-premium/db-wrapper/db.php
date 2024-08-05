<?php
/*
Description: Helps Error Log Monitor add context information to WordPress database errors.
Version: 1.0
Author: Janis Elsts
Text Domain: error-log-monitor
*/

class ElmPro_WpdbWrapper extends wpdb {
	public function print_error($str = '') {
		if ( function_exists('do_action') ) {
			do_action('elm_wpdb_error', $str, $this->last_query);
		}
		return parent::print_error($str);
	}
}

$dbuser = defined('DB_USER') ? DB_USER : '';
$dbpassword = defined('DB_PASSWORD') ? DB_PASSWORD : '';
$dbname = defined('DB_NAME') ? DB_NAME : '';
$dbhost = defined('DB_HOST') ? DB_HOST : '';

global $wpdb;
$wpdb = new ElmPro_WpdbWrapper($dbuser, $dbpassword, $dbname, $dbhost);