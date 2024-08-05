<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    geodir_review_rating_manager
 * @subpackage geodir_review_rating_manager/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

class GeoDir_Review_Rating_Manager_Activator
{

    public static function activate($network_wide = false)
    {
        if ( self::is_v2_upgrade() ) {
			return;
		}

		global $wpdb;

        if ( is_multisite() && $network_wide ) {
            foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
                switch_to_blog( $blog_id );

                $updated = self::run_install();

                do_action( 'geodir_google_analytics_network_activate', $blog_id, $updated );

                restore_current_blog();
            }
        } else {
            $updated = self::run_install();

            do_action( 'geodir_google_analytics_activate', $updated );
        }

        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
    }

    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function deactivate() {
        do_action( 'geodir_reviewrating_deactivate' );
    }

    public static function run_install() {

        if ( self::is_v2_upgrade() ) {
			return;
		}

		// Add Upgraded From Option
        $current_version = get_option( 'geodir_reviewrating_version' );

        if ( $current_version ) {
            update_option( 'geodir_reviewrating_version_upgraded_from', $current_version );
        }

        self::gd_review_rating_db_install();

        add_option('geodir_reviewrating_activation_redirect_opt', 1);

        update_option( 'geodir_reviewrating_version', GEODIR_REVIEWRATING_VERSION );

        do_action( 'geodir_reviewrating_install' );

        return true;
    }

    /**
     * Plugin Database Instalation Function
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     */
    public static function gd_review_rating_db_install() {
        global $wpdb;

		if ( self::is_v2_upgrade() ) {
			return;
		}

        /**
         * Include any functions needed for upgrades.
         *
         * @since 1.1.9
         * @package GeoDirectory_Review_Rating_Manager
         */
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wpdb->hide_errors();

        $collate = '';
        if($wpdb->has_cap( 'collation' )) {
            if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
        }


        $rating_style_table = "CREATE TABLE ".GEODIR_REVIEWRATING_STYLE_TABLE." (
								id int(11) NOT NULL AUTO_INCREMENT,
								name varchar(200) NOT NULL,
								s_rating_type varchar(25) NOT NULL,
								s_rating_icon varchar(100) NOT NULL,
								s_img_off text NOT NULL,
								s_img_width text NOT NULL,
								s_img_height text NOT NULL,
								star_color text NOT NULL,
								star_color_off text NOT NULL,
								star_lables text NOT NULL,
								star_number varchar(200) NOT NULL,
								is_default enum( '0', '1' ) NOT NULL DEFAULT '0',
								PRIMARY KEY  (id)
								) $collate";

        dbDelta( $rating_style_table );

        $default_star_lables = array();
        $default_star_lables[] = 'Terrible';
        $default_star_lables[] = 'Poor';
        $default_star_lables[] = 'Average';
        $default_star_lables[] = 'Very Good';
        $default_star_lables[] = 'Excellent';
        $default_star_lables = maybe_serialize( $default_star_lables );

        $rating_category_table = "CREATE TABLE ".GEODIR_REVIEWRATING_CATEGORY_TABLE." (
									id int( 11 ) NOT NULL AUTO_INCREMENT ,
									title varchar( 500 ) NOT NULL ,
									post_type varchar( 500 ) NOT NULL ,
									category text NOT NULL ,
									category_id text NOT NULL ,
									check_text_rating_cond enum( '0', '1' ) NOT NULL DEFAULT '1',
									`display_order` int(11) unsigned NOT NULL DEFAULT '0',
									PRIMARY KEY  (id)
									) $collate ";

        dbDelta( $rating_category_table );

        $comments_reviews_table = "CREATE TABLE ".GEODIR_COMMENTS_REVIEWS_TABLE." (
									like_id int(11) NOT NULL AUTO_INCREMENT,
									comment_id int(11) NOT NULL,
									ip varchar(100) NOT NULL,
									user_id int(11) NOT NULL DEFAULT '0',
									like_unlike int(11) NOT NULL,
									user_agent text NOT NULL,
									like_date datetime NOT NULL,
									PRIMARY KEY  (like_id)
									) $collate";

        dbDelta( $comments_reviews_table );

        if(!$wpdb->get_var("SHOW COLUMNS FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE field = 'wasthis_review'"))
        {
            $wpdb->query("ALTER TABLE ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." ADD `wasthis_review` int(11)  NOT NULL");
        }

        if(!$wpdb->get_var("SHOW COLUMNS FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE field = 'read_unread'"))
        {
            $wpdb->query("ALTER TABLE ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." ADD `read_unread` VARCHAR( 50 )  NOT NULL");
        }

        if(!$wpdb->get_var("SHOW COLUMNS FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE field = 'total_images'"))
        {
            $wpdb->query("ALTER TABLE ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." ADD `total_images` int( 11 )  NOT NULL");
        }

        // run the create tables function to add our new columns.
        add_action('init',array('GeoDir_Admin_Install','create_tables'));

        update_option( "geodir_reviewrating_db_version", GEODIR_REVIEWRATING_VERSION );
    }

	public static function is_v2_upgrade() {
		if ( ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) || ( get_option( 'geodir_reviewratings_db_version' ) && version_compare( get_option( 'geodir_reviewratings_db_version' ), '2.5.0.0', '<' ) && ( is_null( get_option( 'geodir_reviewratings_db_version', null ) ) || ( get_option( 'geodir_reviewrating_db_version' ) && version_compare( get_option( 'geodir_reviewrating_db_version' ), '2.0.0.0', '<' ) ) ) ) ) {
			return true;
		}

		return false;
	}
}