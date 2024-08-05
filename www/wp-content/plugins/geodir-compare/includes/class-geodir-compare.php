<?php
/**
 * Main plugin class
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Plugin Class
 *
 */
final class GeoDir_Compare {

	/**
	 * The single instance of the class.
	 *
	 * @var GeoDir_Compare
	 * @since 1.0.0
	 */
    protected static $_instance = null;
    
    /**
	 * The current plugin version
	 *
	 * @var string The plugin version
	 * @since 1.0.0
	 */
    public $version = '1.0.0';
    
    /**
	 * The current database version
	 *
	 * @var int The database version
	 * @since 1.0.0
	 */
    public $db_version = 1;
    

	/**
	 * Main GeoDir_Compare Instance.
	 *
	 * Ensures only one instance of GeoDir_Compare is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see geodir_compare()
	 * @return GeoDir_Compare - Main instance.
	 */
	public static function instance() {

		//Maybe initialise the instance
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * GeoDir_Compare Constructor.
	 *
	 * Sets up the environment necessary for GeoDir_Compare to run.
	 *
	 * @since 1.0.0
	 * @return GeoDir_Compare - Main instance.
	 */
	private function __construct() {

        /**
		 * Fires before GeoDir_Compare initializes
		 *
		 * @since 1.0.0
		 *
		*/
        do_action( 'before_geodir_compare_init' );

		//Load plugin files
        $this->includes();

        //Load the plugin text domain
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        
        //Maybe upgrade the db
        add_action( 'init', array( $this, 'maybe_upgrade_db' ) );

        //Register widgets
        add_action( 'geodir_get_widgets', array( $this, 'register_widgets' ), 11, 1 );

        //Load css and js
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		//Add CF location
		add_filter( 'geodir_show_in_locations', array( $this, 'add_fields_location'), 10,3 );

		//Display post state
		add_filter('display_post_states',array( $this, 'set_page_labels' ),10,2);

		//Add menu endpoints
		add_filter('geodirectory_custom_nav_menu_items',array( $this, 'add_nav_menu_items' ));

		//Add unique class to the comparisons page
		add_filter('body_class',array( $this, 'add_body_class' ));
        
        //Init the main admin class
		new GeoDir_Compare_Admin();
		
		//Init the ajax handler
		new GeoDir_Compare_Ajax();

		/**
		 * Fires after GeoDir_Compare initializes
		 *
		 * @since 1.0.0
		 *
		*/
		do_action( 'geodir_compare_init' );
	}


	/**
	 * Loads plugin files and dependancies
	 *
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 */
	protected function includes() {

        $includes_path = plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/';

        //Include the main admin class
        require_once $includes_path . 'admin/admin.php';

        //Include the required template functions file.
        require_once( $includes_path . 'template-functions.php' );

        //Include the widgets class
        require_once $includes_path . 'widgets/class-geodir-widget-compare-button.php';
		require_once $includes_path . 'widgets/class-geodir-widget-compare-list.php';

        //Include ajax handlers
		require_once $includes_path . 'class-geodir-compare-ajax.php';
		
		//Include core functions
		require_once $includes_path . 'functions.php';
	}

	/**
	 * Load Localisation files.
	 *
	 */
	public function load_plugin_textdomain() {
		 load_plugin_textdomain(
			'geodir-compare',
			false,
			plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'languages/'
		);
	}


	/**
	 * Runs installation
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function maybe_upgrade_db() {

        $installed_version = absint( get_option( 'geodir_compare_db_version', 0 ));

        //Upgrade db if installed version is lower than current version
        if( $installed_version < $this->db_version ){
            require plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/class-geodir-compare-install.php';
            new Geodir_Compare_Install( $installed_version );
            update_option( 'geodir_compare_db_version', $this->db_version );
        }

    }
 
	/**
	 * Register widgets.
	 *
	 * @since 2.0.0.0
	 *
	 * @param array $widgets The list of available widgets.
	 * @return array Available GD widgets.
	 */
	public function register_widgets( $widgets ) {
		$widgets[] = 'GeoDir_Widget_Compare_Button'; // Compare button widget
		$widgets[] = 'GeoDir_Widget_Compare_List'; // Comparison table

		return $widgets;
	}
	
	/**
     * Registers a new custom fields location
     *
     * @since 1.0.0
     */
    public function add_fields_location( $show_in_locations, $field_info, $field_type ) {

        $show_in_locations['[compare]'] = __("Comparison Page", 'geodir-compare');
			return $show_in_locations;

	}
	
	/**
     * Sets pages labels
     *
     * @since 1.0.0
     */
    public function set_page_labels(  $post_states, $post ) {

        if ( $post->ID == geodir_get_option( 'geodir_compare_listings_page' ) ) {
			$post_states['geodir_compare_listings_page'] = __( 'GD Comparison Page', 'geodir-compare' ) .
			                                        geodir_help_tip( __( 'This is where users can compare several listings side by side.', 'geodir-compare' ) );
		}

		return $post_states;

	}

	/**
     * Adds new nav menu items
     *
     * @since 1.0.0
     */
    public function add_nav_menu_items(  $items ) {

        // Add the comparison menu item
		$gd_comparison_page_id = geodir_get_option( 'geodir_compare_listings_page' );
		if($gd_comparison_page_id){
			$item = new stdClass();
			$item->object_id 			= $gd_comparison_page_id;
			$item->db_id 				= 0;
			$item->object 				=  'page';
			$item->menu_item_parent 	= 0;
			$item->type 				= 'post_type';
			$item->title 				= __('Compare Listings','geodir-compare');
			$item->url 					= get_page_link($gd_comparison_page_id);
			$item->target 				= '';
			$item->attr_title 			= '';
			$item->classes 				= array('gd-menu-item');
			$item->xfn 					= '';

			$items['pages'][] = $item;
		}

		return $items;

	}

	/**
     * Adds a new class to the body items
     *
     * @since 1.0.0
     */
    public function add_body_class(  $classes ) {
		global $post;

        // Add the comparison menu item
		if( ! empty( $post ) && absint( $post->ID ) == absint( geodir_get_option( 'geodir_compare_listings_page' ) ) ) {
			$classes[] = 'geodir-compare-page';
		}

		return $classes;
	}

	/**
	 * Register and enqueue styles and scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$assets_url = plugin_dir_url( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/assets/';
		$assets_dir = plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/assets/';
		$design_style = geodir_design_style();

		//Javascript
		$vars                   = array(
			'items_full'        => __( 'Your comparision list is full. Please remove one item first.', 'geodir-compare' ),
			'compare'           => __( 'Compare', 'geodir-compare' ),
			'ajax_error'        => __( 'There was an error while processing the request.', 'geodir-compare' ),
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'cookie_domain'     => COOKIE_DOMAIN,
			'cookie_path'       => COOKIEPATH,
			'cookie_time'       => DAY_IN_SECONDS,
			'comparePage'       => esc_url( add_query_arg( 'compareids', '0', get_the_permalink( (int) geodir_get_option('geodir_compare_listings_page') ) ) )
		);
		if( ! $design_style ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script( 'geodir-compare', $assets_url . 'scripts' . $suffix . '.js', array( 'jquery', 'geodir_lity' ), filemtime( $assets_dir . 'scripts' . $suffix . '.js' ), true );
			wp_enqueue_script(  'geodir-compare' );
		}
		$script = $design_style ? 'geodir' : 'geodir-compare';
		wp_localize_script($script , 'GD_Compare', $vars );

		//CSS
		if( ! $design_style ) {
			wp_enqueue_style('geodir-compare', $assets_url . 'styles.css', array(), filemtime( $assets_dir . 'styles.css' ));
		}
	}
}