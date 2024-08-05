<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 2.0.0
 */
class GD_Recaptcha_Activate {
    
    /**
     * Plugin activate.
     *
     * When plugin active then set global options in GD Recaptcha Manager.
     *
     * @since  2.0.0
     */
    public static function activate() {
        
        set_transient( 'gd_recaptcha_redirect', true, 30 );
        
    }
    
}