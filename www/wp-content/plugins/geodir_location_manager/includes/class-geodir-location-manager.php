<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Location_Manager
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    GeoDir_Location_Manager
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
final class GeoDir_Location_Manager {
    
    /**
     * GeoDirectory Location Manager instance.
     *
     * @access private
     * @since  2.0.0
     */
    private static $instance = null;
    
    /**
     * The settings instance variable
     *
     * @access public
     * @since  2.0.0
     * @var    GeoDir_Location_Settings
     */
    public $settings;
    
    /**
     * Main GeoDir_Location_Manager Instance.
     *
     * Ensures only one instance of GeoDirectory Location Manager is loaded or can be loaded.
     *
     * @since 2.0.0
     * @static
     * @see GeoDir()
     * @return GeoDir_Location_Manager - Main instance.
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Location_Manager ) ) {
            self::$instance = new GeoDir_Location_Manager;
            self::$instance->setup_constants();
            
            add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

            if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
                add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

                return self::$instance;
            }

            self::$instance->includes();
            self::$instance->init_hooks();

            do_action( 'geodir_location_manager_loaded' );
        }
        
        return self::$instance;
    }
    
    /**
     * Setup plugin constants.
     *
     * @access private
     * @since 2.0.0
     * @return void
     */
    private function setup_constants() {
        global $plugin_prefix;

		if ( $this->is_request( 'test' ) ) {
            $plugin_path = dirname( GEODIR_LOCATION_PLUGIN_FILE );
        } else {
            $plugin_path = plugin_dir_path( GEODIR_LOCATION_PLUGIN_FILE );
        }
        
        $this->define( 'GEODIR_LOCATION_PLUGIN_DIR', $plugin_path );
        $this->define( 'GEODIR_LOCATION_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_LOCATION_PLUGIN_FILE ) ) );
        $this->define( 'GEODIR_LOCATION_PLUGIN_BASENAME', plugin_basename( GEODIR_LOCATION_PLUGIN_FILE ) );

        // Database tables
		$this->define( 'GEODIR_LOCATIONS_TABLE', $plugin_prefix . 'post_locations' );
        $this->define( 'GEODIR_NEIGHBOURHOODS_TABLE', $plugin_prefix . 'post_neighbourhood' );
        $this->define( 'GEODIR_LOCATION_SEO_TABLE', $plugin_prefix . 'location_seo' );
        $this->define( 'GEODIR_LOCATION_TERM_META', $plugin_prefix . 'term_meta' );
    }
    
    /**
     * Loads the plugin language files
     *
     * @access public
     * @since 2.0.0
     * @return void
     */
    public function load_textdomain() {
        global $wp_version;
        
        $locale = $wp_version >= 4.7 ? get_user_locale() : get_locale();
        
        /**
         * Filter the plugin locale.
         *
         * @since   1.0.0
         * @package GeoDirectory_Location_Manager
         */
        $locale = apply_filters( 'plugin_locale', $locale, 'geodirlocation' );

        load_textdomain( 'geodirlocation', WP_LANG_DIR . '/' . 'geodirlocation' . '/' . 'geodirlocation' . '-' . $locale . '.mo' );
        load_plugin_textdomain( 'geodirlocation', FALSE, basename( dirname( GEODIR_LOCATION_PLUGIN_FILE ) ) . '/languages/' );
    }
    
    /**
     * Show a warning to sites running PHP < 5.3
     *
     * @static
     * @access private
     * @since 2.0.0
     * @return void
     */
    public static function php_version_notice() {
        echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory. Please contact your host and request that your version be upgraded to 5.3 or later.', 'geodirlocation' ) . '</p></div>';
    }
    
    /**
     * Include required files.
     *
     * @access private
     * @since 2.0.0
     * @return void
     */
    private function includes() {
        global $pagenow, $geodir_options, $geodirectory;

	    $design_style = geodir_design_style();
        /**
         * Class autoloader.
         */
        include_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/class-geodir-location-autoloader.php' );

		// AJAX setup
		GeoDir_Location_AJAX::init();

	    // Scripts
	    if( $design_style){
		    GeoDir_Location_Scripts::init();
	    }

        require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/general-functions.php' );
		require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/count-functions.php' );
		require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/template-functions.php' );
		require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/custom-functions.php' );
		require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/shortcode-functions.php' );
		require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/widget-functions.php' );

		GeoDir_Location_API::init();

        if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
            new GeoDir_Location_Admin();

	        require_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Location_Admin_Install::init(); // init the install class

			require_once( GEODIR_LOCATION_PLUGIN_DIR . 'upgrade.php' );	        
        }
	    
    }
    
    /**
     * Hook into actions and filters.
     * @since  2.3
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
		add_filter( 'geodir_get_default_location', array( $this, 'get_default_location' ), 10, 1 );
		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );

			// Yoast SEO
			add_filter( 'wpseo_title', array( 'GeoDir_Location_SEO', 'wpseo_title' ), 10, 1 );
			add_filter( 'wpseo_metadesc', array( 'GeoDir_Location_SEO', 'wpseo_metadesc' ), 10, 1 );
			add_action( 'init', array( 'GeoDir_Location_SEO', 'wpseo_sitemap_init' ), 10 );
			add_action( 'init', array( 'GeoDir_Location_SEO', 'wp_sitemaps_init' ), 20 );
			add_filter( 'wpseo_sitemap_index', array( 'GeoDir_Location_SEO', 'wpseo_sitemap_index' ), 10, 1 );
			add_filter( 'wpseo_breadcrumb_links', array('GeoDir_Location_SEO', 'wpseo_breadcrumb_links'));
			add_filter( 'rank_math/frontend/breadcrumb/items', array( 'GeoDir_Location_SEO', 'rank_math_breadcrumb_links' ), 20, 2 );
			add_filter( 'wpseo_breadcrumb_single_link_info', array('GeoDir_Location_SEO', 'wpseo_breadcrumb_pt_link'), 10, 3 );
			add_filter( 'seopress_pro_breadcrumbs_crumbs', array( 'GeoDir_Location_SEO', 'seopress_pro_breadcrumbs_crumbs' ), 21, 1 );

			if ( GeoDir_Location_Neighbourhood::is_active() ) {
				add_filter( 'geodir_location_description', array( 'GeoDir_Location_Neighbourhood', 'location_description' ), 10, 4 );
			}
		}
		add_action( 'geodir_add_listing_geocode_js_vars', 'geodir_location_add_listing_geocode_js_vars', 10 );
		add_filter( 'geodir_save_post_data', array( $this, 'save_location_data' ), 10, 4 );
		add_filter( 'geodir_post_permalinks', array( $this, 'post_permalinks_set_location' ), 10, 2 );
		if ( geodir_get_option( 'lm_set_address_disable' ) ) {
			add_filter( 'geodir_add_listing_geocode_js_vars', 'geodir_location_check_add_listing_geocode', 20 );
			add_filter( 'geodir_add_listing_geocode_response_fail', 'geodir_location_check_add_listing_geocode', 20 );
		}
		add_filter( 'geodir_main_query_posts_where', 'geodir_location_main_query_posts_where', 0, 3 );
		add_filter( 'geodir_replace_variables_neighbourhood', array( 'GeoDir_Location_Neighbourhood', 'replace_neighbourhood_name' ), 10, 2 );
		add_filter( 'geodir_seo_variables', array( 'GeoDir_Location_SEO', 'filter_seo_variables' ), 10, 2 );
		add_filter( 'geodir_seo_meta_title', array( 'GeoDir_Location_SEO', 'filter_meta_title' ), 10, 2 );
		add_filter( 'geodir_seo_meta_description', array( 'GeoDir_Location_SEO', 'filter_meta_desc' ), 10, 1 );
		add_filter( 'geodir_cpt_meta_value', array( 'GeoDir_Location_SEO', 'filter_cpt_description' ), 10, 5 );
		add_filter( 'geodir_replace_location_variables', array( 'GeoDir_Location_SEO', 'pre_replace_location_variables' ), 1, 4 );
		add_filter( 'geodir_seo_pre_replace_variable', array( 'GeoDir_Location_SEO', 'pre_replace_variable' ), 1, 2 );
		add_filter( 'geodir_db_cpt_default_columns', array( 'GeoDir_Location_Neighbourhood', 'db_neighbourhood_column' ), 10, 3 );
		add_filter( 'geodir_post_meta_address_fields', array( 'GeoDir_Location_Neighbourhood', 'post_meta_neighbourhood_field' ), 10, 2 );
		add_filter( 'geodir_custom_field_output_field_value', array( 'GeoDir_Location_Neighbourhood', 'post_meta_neighbourhood_value' ), 10, 4 );
		add_filter( 'geodir_widget_listings_query_args', 'geodir_location_widget_listings_query_args', 10, 2 );
		add_filter( 'geodir_map_params', 'geodir_location_map_params', 20, 2 );
		add_action( 'wp_head', 'geodir_location_head_script', 50 );

	    // extent the locations class
	    add_filter('geodir_class_location',array( $this, 'extend_locations'));

	    // extent the permalinks class
	    add_filter('geodir_class_permalinks',array( $this, 'extend_permalinks'));

	    // extent the taxonomies class
	    add_filter('geodir_class_taxonomies',array( $this, 'extend_taxonomies'));

		// Delete location on location delete
		add_action( 'geodir_location_after_delete_location', array( 'GeoDir_Location_City', 'on_location_deleted' ), 11, 2 );
		add_action( 'geodir_location_after_delete_location', array( 'GeoDir_Location_SEO', 'on_location_deleted' ), 11, 2 );
		add_action( 'geodir_location_after_delete_location', array( 'GeoDir_Location_Neighbourhood', 'on_location_deleted' ), 11, 2 );

		if ( defined( 'EM_VERSION' ) ) {
			add_action( 'geodir_location_set_current_check_404', 'geodir_location_em_check_404', 9999, 1 );
		}

		// Rank Math sitemap providers.
		add_filter( 'rank_math/sitemap/providers', array( 'GeoDir_Location_SEO', 'rank_math_sitemap_providers' ), 11, 2 );
    }

	/**
	 * The new locations class to use.
	 *
	 * @return string
	 */
	public function extend_taxonomies(){
		return "GeoDir_Location_Taxonomies";
	}

	/**
	 * The new locations class to use.
	 *
	 * @return string
	 */
	public function extend_locations(){
		return "GeoDir_Location_Locations";
	}

	/**
	 * The new permalinks class to use.
	 *
	 * @return string
	 */
	public function extend_permalinks(){
		return "GeoDir_Location_Permalinks";
	}
    
    /**
     * Init GeoDirectory when WordPress Initialises.
     */
    public function init() {
        // Before init action.
        do_action( 'geodir_location_before_init' );

        // Init action.
        do_action( 'geodir_location_init' );
    }
    
    /**
     * Define constant if not already set.
     *
     * @param  string $name
     * @param  string|bool $value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }
    
    /**
     * Request type.
     *
     * @param  string $type admin, frontend, ajax, cron, test or CLI.
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
                break;
            case 'ajax' :
                return wp_doing_ajax();
                break;
            case 'cli' :
                return ( defined( 'WP_CLI' ) && WP_CLI );
                break;
            case 'cron' :
                return wp_doing_cron();
                break;
            case 'frontend' :
                return ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron();
                break;
            case 'test' :
                return defined( 'GD_TESTING_MODE' );
                break;
        }
        
        return null;
    }
	
	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return GEODIR_LOCATION_PLUGIN_URL;
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( GEODIR_LOCATION_PLUGIN_DIR );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
	
	public function get_default_location( $default_location ) {
		global $wpdb;

		$location = $wpdb->get_row( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE is_default = '1'" );

		if ( ! empty( $location ) ) {
			unset( $location->is_default );
			$default_location = $location;
		}

		return $default_location;
	}
	
	/**
	 * Enqueue styles.
	 */
	public function add_styles() {

		$design_style = geodir_design_style();

		// Register styles
		if(!$design_style ){
			wp_register_style( 'geodir-location-css', GEODIR_LOCATION_PLUGIN_URL . '/assets/css/geodir-location.css', array(), GEODIRLOCATION_VERSION );
			wp_enqueue_style( 'geodir-location-css' );
		}

	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {

		$design_style = geodir_design_style();

		if(!$design_style){
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register scripts
			wp_register_script( 'geodir-location-script', GEODIR_LOCATION_PLUGIN_URL . '/assets/js/location-common' . $suffix . '.js', array( 'jquery', 'geodir' ), GEODIRLOCATION_VERSION );

			// Admin scripts for GD pages only
			wp_enqueue_script( 'geodir-location-script' );

		}

		$script = $design_style ? 'geodir' : 'geodir-location-script';
		wp_localize_script( $script, 'geodir_location_params', geodir_location_params() );

	}

	public function save_location_data( $postarr, $gd_post, $post, $update ) {
		global $geodirectory;

		if ( ! ( ! empty( $postarr['country'] ) && ! empty( $postarr['region'] ) && ! empty( $postarr['city'] ) && ! empty( $postarr['latitude'] ) && ! empty( $postarr['longitude'] ) ) ) {
			return $postarr;
		}

		if ( $post->post_type == 'revision' ) {
			$post_type = get_post_type( $post->post_parent );
		} else {
			$post_type = $post->post_type;
		}

		if ( ! ( geodir_is_gd_post_type( $post_type ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) ) {
			return $postarr;
		}

		$cache_key = 'gd_post_save_location_' . sanitize_key( $postarr['country'] . '_' . $postarr['region'] . '_' . $postarr['city'] );
		// Get cache
		$location = wp_cache_get( $cache_key, 'gd_post_save_location' );

		if ( $location === false ) {
			$data = array(
				'city' => $postarr['city'],
				'region' => $postarr['region'],
				'country' => $postarr['country'],
				'latitude' => $postarr['latitude'],
				'longitude' => $postarr['longitude']
			);

			$location = $geodirectory->location->add_new_location( $data );

			// Set cache
			wp_cache_set( $cache_key, $location, 'gd_post_save_location' );
		}

		// Set neighbourhood
		if ( isset( $gd_post['neighbourhood'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$post_neighbourhood = '';

			if ( ! empty( $gd_post['neighbourhood'] ) && ! empty( $location->location_id ) ) {
				$cache_key = 'gd_post_save_neighbourhood_' . $location->location_id . ':' . sanitize_key( $gd_post['neighbourhood'] );
				// Get cache
				$neighbourhood_slug = wp_cache_get( $cache_key, 'gd_post_save_neighbourhood' );

				if ( $neighbourhood_slug !== false ) {
					$post_neighbourhood = $neighbourhood_slug;
				} else {
					if ( $neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug( $gd_post['neighbourhood'], $location->location_id ) ) {
						$post_neighbourhood = $neighbourhood->slug;
					} else if ( $neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_name( $gd_post['neighbourhood'], $location->location_id ) ) {
						$post_neighbourhood = $neighbourhood->slug;
					} else {
						$data = array();
						$data['location_id'] 	= $location->location_id;
						$data['name'] 			= stripslashes( $gd_post['neighbourhood'] );
						$data['latitude'] 		= $postarr['latitude'];
						$data['longitude'] 		= $postarr['longitude'];

						if ( $neighbourhood_id = GeoDir_Location_Neighbourhood::save_data( $data ) ) {
							$post_neighbourhood = GeoDir_Location_Neighbourhood::get_slug( $neighbourhood_id );
						}
					}
	
					// Set cache
					wp_cache_set( $cache_key, $post_neighbourhood, 'gd_post_save_neighbourhood' );
				}
			}

			$postarr['neighbourhood'] = $post_neighbourhood;
		}

		return $postarr;
	}

	public function post_permalinks_set_location( $location, $post ) {
		if ( ! empty( $post->location_id ) && ( $found = geodir_get_location_by_id( '' , $post->location_id ) ) ) {
			$location = $found;
		}
		return $location;
	}
}
