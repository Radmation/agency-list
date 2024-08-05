<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.2.0
 * @package    GD_Duplicate_Alert
 * @subpackage GD_Duplicate_Alert/admin
 *
 * Class GD_Duplicate_Alert_Admin
 */

class GD_Duplicate_Alert_Admin {

    /**
     * Constructor.
     *
     * @since 1.2.1
     *
     * GD_Duplicate_Alert_Admin constructor.
     */
    public function __construct() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
        add_action( 'admin_init', array($this, 'activation_redirect'));
        add_filter( 'geodir_uninstall_options', array($this, 'gd_duplicate_alert_uninstall_options'), 10, 1);
        add_filter( 'geodir_load_db_language', array( $this, 'load_db_language'), 60, 1 );

    }

    /**
     * Register and enqueue duplicate alert styles and scripts.
     *
     * @since 1.2.1
     */
    public function enqueue_styles_and_scripts(){

    }

    /**
     * Plugin activation redirection.
     *
     * When plugin activate then redirect to direct gd_place post type
     * GD duplicate alert settings tab.
     *
     * @since 1.2.1
     *
     */
    public function activation_redirect() {

        // Bail if no activation redirect
        if ( !get_transient( 'gd_duplicate_alert_redirect' ) ) {
            return;
        }

        // Delete the redirect transient
        delete_transient( 'gd_duplicate_alert_redirect' );

        wp_safe_redirect( admin_url( 'edit.php?post_type=gd_place&page=gd_place-settings&tab=cpt_duplicate_alert' ) );
        exit;

    }

    public function gd_duplicate_alert_uninstall_options($settings){
        array_pop($settings);
        $settings[] = array(
            'name'     => __( 'AJAX Duplicate Alert', 'geodir-duplicate-alert' ),
            'desc'     => __( 'Check this box if you would like to completely remove all of its data when AJAX Duplicate alert is deleted.', 'geodir-duplicate-alert' ),
            'id'       => 'geodir_uninstall_ajax_duplicate_alert',
            'type'     => 'checkbox',
        );
        $settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );
        return $settings;
    }

    /**
     * Add validation messages for translation.
     *
     * @since 2.0.0.3
     *
     * @param  array $translations Array of text strings.
     * @return array
     */
    function load_db_language( $translations = array() ) {
        if ( ! is_array( $translations ) ) {
            $translations = array();
        }

        $messages = geodir_get_option( 'duplicate_alert', array() );
        if ( ! empty( $messages ) ) {
            foreach ( $messages as $post_type => $_messages ) {
                if ( ! empty( $post_type ) && is_array( $_messages ) ) {
                    if ( ! empty( $_messages ) ) {
                        foreach ( $_messages as $key => $_message ) {
                            if ( ! empty( $_message ) && is_scalar( $_message ) ) {
                                $translations[] = $_message;
                            }
                        }
                    }
                }
            }
        }

        return $translations;
    }
}