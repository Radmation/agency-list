<?php

/*
Plugin Name: Error Log Monitor Pro
Plugin URI: http://w-shadow.com/blog/2012/07/25/error-log-monitor-plugin/
Description: Adds a Dashboard widget that displays the last X lines from your PHP error log, and can also send you email notifications about newly logged errors.
Version: 1.8.5
Update URI: https://api.freemius.com
Author: Janis Elsts
Author URI: http://w-shadow.com/
Text Domain: error-log-monitor

@fs_premium_only /pro-assets/, /ElmPro/, /db-wrapper/
*/
if ( !defined( 'ABSPATH' ) ) {
    return;
}

if ( !function_exists( 'wsh_elm_fs' ) ) {
    require dirname( __FILE__ ) . '/scb/load.php';
    require_once dirname( __FILE__ ) . '/vendor/ajax-wrapper/AjaxWrapper.php';
    function error_log_monitor_autoloader( $className )
    {
        $map = array(
            'Elm_'    => dirname( __FILE__ ) . '/Elm/',
            'ElmPro_' => dirname( __FILE__ ) . '/ElmPro/',
        );
        $matchedPrefix = null;
        foreach ( $map as $prefix => $dir ) {
            //Does the class name start with the prefix?
            
            if ( substr( $className, 0, strlen( $prefix ) ) === $prefix ) {
                $matchedPrefix = $prefix;
                break;
            }
        
        }
        if ( $matchedPrefix === null ) {
            return;
        }
        $dir = $map[$matchedPrefix];
        //File name = class name without the prefix + .php.
        $fileName = $dir . substr( $className, strlen( $matchedPrefix ) ) . '.php';
        if ( file_exists( $fileName ) ) {
            /** @noinspection PhpIncludeInspection */
            include $fileName;
        }
    }
    
    spl_autoload_register( 'error_log_monitor_autoloader' );
    //Install the error handler right away instead of waiting for plugins_loaded.
    
    if ( defined( 'ABSPATH' ) ) {
        $wsElmErrorHandler = new ElmPro_ErrorHandler( new ElmPro_WpContext() );
        $wsElmErrorHandler->install();
    }
    
    // Create a helper function for easy SDK access.
    function wsh_elm_fs()
    {
        global  $wsh_elm_fs ;
        
        if ( !isset( $wsh_elm_fs ) ) {
            //Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_2379_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_2379_MULTISITE', true );
            }
            //Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            /** @noinspection PhpUnhandledExceptionInspection */
            $wsh_elm_fs = fs_dynamic_init( array(
                'id'             => '2379',
                'slug'           => 'error-log-monitor',
                'type'           => 'plugin',
                'public_key'     => 'pk_5b9b22d279f81369f3e39d6225e4c',
                'is_premium'     => true,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'first-path' => 'plugins.php',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $wsh_elm_fs;
    }
    
    // Init Freemius.
    wsh_elm_fs();
    // Signal that SDK was initiated.
    do_action( 'wsh_elm_fs_loaded' );
    //Optimization: Run only in the admin and when doing cron jobs.
    if ( !is_admin() && !defined( 'DOING_CRON' ) && !class_exists( 'WP_CLI', false ) ) {
        return;
    }
    function error_log_monitor_init()
    {
        //Compatibility workaround: Ensure initialisation code is only run once. Plugins that call
        //scb_init() in or after plugins_loaded can cause this to be executed multiple times.
        static  $isInitDone = false ;
        if ( $isInitDone ) {
            return;
        }
        $isInitDone = true;
        
        if ( wsh_elm_fs()->is__premium_only() && wsh_elm_fs()->can_use_premium_code() ) {
            new ElmPro_Plugin( __FILE__ );
        } else {
            new Elm_Plugin( __FILE__ );
        }
    
    }
    
    scb_init( 'error_log_monitor_init' );
}
