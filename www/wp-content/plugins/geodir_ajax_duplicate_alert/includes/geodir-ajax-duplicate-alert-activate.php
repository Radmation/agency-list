<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.2.1
 */
class GD_Duplicate_Alert_Activate {

    /**
     * Plugin activate.
     *
     * When plugin active then set global options in GD duplicate alert.
     *
     * @since  1.2.1
     */
    public static function activate() {

        $post_types = geodir_get_posttypes();

        $output = array();

        if( !empty( $post_types ) ) {

            foreach ( $post_types as $posttype ) {

                $post_type_name = geodir_post_type_singular_name($posttype);

                $output[$posttype] = array(
                    'duplicate_alert_fields' => array('post_title'),
                    'duplicate_alert_validation_message' => sprintf(__( '%s with this field is already listed! Please make sure you are not adding a duplicate entry.','geodir-duplicate-alert' ), $post_type_name),
                );

                $duplicate_alert_option = geodir_get_option("duplicate_alert", array());

                if( empty( $duplicate_alert_option )) {

                    $duplicate_alert_option = $output;

                } else {

                    $duplicate_alert_option = array_merge($duplicate_alert_option,$output);

                }

                geodir_update_option( "duplicate_alert", $duplicate_alert_option );

            }


        }

        set_transient( 'gd_duplicate_alert_redirect', true, 30 );

    }

}
