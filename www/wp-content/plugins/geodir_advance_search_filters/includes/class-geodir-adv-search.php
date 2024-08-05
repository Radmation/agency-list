<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://wpgeodirectory.com
 * @since      2.0.0
 *
 * @package    GeoDir_Advance_Search_Filters
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
 * @since      2.0.0
 * @package    GeoDir_Adv_Search
 * @author     AyeCode Ltd
 */
final class GeoDir_Adv_Search {

	/**
	 * GeoDirectory Advance Search Filters instance.
	 *
	 * @access private
	 * @since  2.0.0
	 */
	private static $instance = null;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_Adv_Search_Query
	 */
	public $query = null;

	/**
	 * Main GeoDir_Adv_Search Instance.
	 *
	 * Ensures only one instance of GeoDirectory Advance Search Filters is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 * @see GeoDir()
	 * @return GeoDir_Adv_Search - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Adv_Search ) ) {
			self::$instance = new GeoDir_Adv_Search;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_advance_search_filters_loaded' );
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
			$plugin_path = dirname( GEODIR_ADV_SEARCH_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_ADV_SEARCH_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_ADV_SEARCH_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_BASENAME', plugin_basename( GEODIR_ADV_SEARCH_PLUGIN_FILE ) );

		// Database tables
		$this->define( 'GEODIR_ADVANCE_SEARCH_TABLE', $plugin_prefix . 'custom_advance_search_fields' );
		$this->define( 'GEODIR_BUSINESS_HOURS_TABLE', $plugin_prefix . 'business_hours' ); // business hours table
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
	 */
	public function load_textdomain() {
		// Determines the current locale.
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else if ( function_exists( 'get_user_locale' ) ) {
			$locale = get_user_locale();
		} else {
			$locale = get_locale();
		}

		/**
		 * Filter the plugin locale.
		 *
		 * @since   1.0.0
		 * @package GeoDir_Advance_Search_Filters
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geodiradvancesearch' );

		unload_textdomain( 'geodiradvancesearch' );
		load_textdomain( 'geodiradvancesearch', WP_LANG_DIR . '/geodiradvancesearch/geodiradvancesearch-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodiradvancesearch', false, basename( dirname( GEODIR_ADV_SEARCH_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function geodirectory_notice() {
		echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the GeoDirectory Advance Search Filters plugin to work properly.', 'geodiradvancesearch' ) . '</p></div>';
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
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Advance Search Filters. Please contact your host and request that your version be upgraded to 5.3 or later.', 'geodiradvancesearch' ) . '</p></div>';
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/class-geodir-adv-search-autoloader.php' );

		GeoDir_Adv_Search_AJAX::init();
		GeoDir_Adv_Search_Business_Hours::init(); // Business Hours
		GeoDir_Adv_Search_Fields::init();

		require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/functions.php' );
		require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/template-functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Adv_Search_Admin();

			require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Adv_Search_Admin_Install::init();

			require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'upgrade.php' );
		}

		$this->query = new GeoDir_Adv_Search_Query();
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		//add_filter( 'wp_super_duper_options_gd_search', 'geodir_search_widget_options' );
		add_filter( 'wp_super_duper_arguments', 'geodir_search_widget_options', 10, 3 );
		add_filter( 'geodir_register_block_pattern_search_attrs', 'geodir_search_block_pattern_attrs', 10, 1 );
		add_filter( 'geodir_search_form_template_params', 'geodir_search_form_template_params', 10, 3 );
		add_filter( 'geodir_params', array( $this, 'localize_core_params' ), 10, 1 );

		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
			
			// aui
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_aui' ), 10 );
			
			add_filter( 'wp_super_duper_div_classname_gd_search', 'geodir_search_widget_add_class', 10, 3 );
			add_filter( 'wp_super_duper_div_attrs_gd_search', 'geodir_search_widget_add_attr', 10, 3 );
			add_filter( 'wp_super_duper_before_widget_gd_search', 'geodir_search_before_widget_content', 10, 4 );
			add_filter( 'wp_footer' , 'geodir_search_form_add_script' , 10 );

			add_filter( 'body_class', 'geodir_search_body_class' ); // let's add a class to the body so we can style the new addition to the search
			
			if ( geodir_get_option( 'advs_search_display_searched_params' ) ) {
				add_action( 'geodir_extra_loop_actions', 'geodir_search_show_searched_params', 9999, 1 );
			}

			// AJAX Search
			add_action( 'geodir_search_handle_ajax_request', 'geodir_search_handle_ajax_search_request' );
			add_filter( 'geodir_search_ajax_loop', 'geodir_search_ajax_loop', 10, 1 );
			add_filter( 'geodir_search_ajax_pagination', 'geodir_search_ajax_pagination', 10, 1 );
			add_filter( 'wp_super_duper_widget_output', 'geodir_search_set_loop_params', 20, 4 );
			add_filter( 'geodir_map_params', 'geodir_search_set_map_params', 9999, 2 );
			add_filter( 'wp_head', array( $this, 'enqueue_inline_style' ), 10 );
			//add_filter( 'geodir_search_default_search_button_text', 'geodir_search_ajax_search_button_text', 20, 1 );
			add_filter( 'geodir_search_ajax_search_data', 'geodir_search_ajax_search_data', 10, 1 );
		}
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_adv_search_before_init' );

		// Init action.
		do_action( 'geodir_adv_search_init' );
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
	 * Enqueue styles.
	 */
	public function add_styles() {
		$design_style = geodir_design_style();

		if ( ! $design_style ) {
			// Register stypes
			wp_register_style( 'geodir-adv-search', GEODIR_ADV_SEARCH_PLUGIN_URL . '/assets/css/style.css', array(), GEODIR_ADV_SEARCH_VERSION );

			wp_enqueue_style( 'geodir-adv-search' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$design_style = geodir_design_style();
		if(!$design_style) {
			// Register scripts
			wp_register_script( 'geodir-adv-search', GEODIR_ADV_SEARCH_PLUGIN_URL . '/assets/js/script' . $suffix . '.js', array(
				'jquery',
				'geodir',
				'geodir-jquery-ui-timepicker'
			), GEODIR_ADV_SEARCH_VERSION );

			wp_enqueue_script( 'geodir-adv-search' );
		}
		$script = $design_style ? 'geodir' : 'geodir-adv-search';
		wp_localize_script($script , 'geodir_search_params', geodir_adv_search_params() );
	}

	public function enqueue_aui(){
		// core
		wp_add_inline_script( 'geodir', $this->add_scripts_aui() );
	}


	public function add_scripts_aui() {
		global $wp_query, $aui_bs5, $aui_conditional_js;

		$design_style = geodir_design_style();
		$current_page = ! empty( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1;
		$total_pages = ! empty( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;

		$has_ajax_search = (bool) geodir_search_has_ajax_search( true );

		if ( $has_ajax_search ) {
			$map_search_label = __( 'Search as I move the map', 'geodiradvancesearch' );
			$redo_search_label = __( 'Redo search in map', 'geodiradvancesearch' );
			$pagination = geodir_search_ajax_pagi_type();
			$map_move_checked = ( (bool) geodir_get_option( 'advs_map_search' ) && (bool) geodir_get_option( 'advs_map_search_default' ) ) ? ' checked="checked"' : '';
		} else {
			$map_search_label = '';
			$pagination = '';
			$map_move_checked = '';
		}

		// Conditional fields JS.
		$conditional_inline_js = '';
		if ( $design_style && empty( $aui_conditional_js ) && class_exists( 'AyeCode_UI_Settings' ) && ! geodir_is_page( 'add-listing' ) ) {
			$aui_settings = AyeCode_UI_Settings::instance();

			if ( is_callable( array( $aui_settings, 'conditional_fields_js' ) ) ) {
				$conditional_fields_js = $aui_settings->conditional_fields_js();

				if ( ! empty( $conditional_fields_js ) ) {
					$conditional_inline_js = $conditional_fields_js;
				}
			}
		}

		$post_type = geodir_get_current_posttype();
ob_start();
if (0) { ?><script><?php } ?>
document.addEventListener("DOMContentLoaded", function() {
	/* Setup advanced search form on load */
	geodir_search_setup_advance_search();

	/* Setup advanced search form on form ajax load */
	jQuery("body").on("geodir_setup_search_form", function() {
		geodir_search_setup_advance_search();
	});

	if (jQuery('.geodir-search-container form').length) {
		geodir_search_setup_searched_filters();
	}

	/* Refresh Open Now time */
	if (jQuery('.geodir-search-container select[name="sopen_now"]').length) {
		setInterval(function(e) {
			geodir_search_refresh_open_now_times();
		}, 60000);
		geodir_search_refresh_open_now_times();
	}

	if (!window.gdAsBtnText) {
		window.gdAsBtnText = jQuery('.geodir_submit_search').html();
		window.gdAsBtnTitle = jQuery('.geodir_submit_search').data('title');
	}

	<?php if ( $has_ajax_search ) { ?>
	var $loop_container = '';window.gdAjaxSearch = [];window.isSubmiting = false;var gdCurCpt = jQuery('[name="geodir-listing-search"]').find('[name="stype"]').val();
	<?php if ( geodir_search_ajax_search_type() != 'auto' ) { ?>
	window.gdiTarget = null;window.gdoTarget = false;
	jQuery('form[name="geodir-listing-search"] [type="text"]:visible').off("input").on("input",function(e){jQuery(this).closest('form').trigger('change');window.gdiTarget=e.target});
	jQuery("button.geodir_submit_search").on({mouseenter:function(){window.gdoTarget=true},mouseleave:function(){window.gdoTarget=false}});
	<?php } ?>
	jQuery('form[name="geodir-listing-search"]').off('change').on("change", function(e) {
		<?php if ( (bool) geodir_get_option( 'advs_map_search' ) ) { ?>
		/* Unset map search when near search active */
		if (jQuery(this).find('[name="snear"]').length && jQuery(this).find('[name="snear"]').val()) {
			jQuery('#geodir_map_move').prop('checked', false).trigger('change');
			window.gdAsIsMapSearch = false;
			window.gdAsSearchBounds = false;
			geodir_params.gMarkerReposition = false;
		}
		<?php } ?>
		if (jQuery(this).find('[name="stype"]').length) {
			gdFrmCpt = jQuery(this).find('[name="stype"]').val();
		} else if (jQuery('[name="geodir-listing-search"] [name="stype"]').length) {
			gdFrmCpt = jQuery('[name="geodir-listing-search"] [name="stype"]').val();
		} else {
			gdFrmCpt = gdCurCpt;
		}
		if ((gdCurCpt && gdFrmCpt == gdCurCpt) || window.gdAsCptChanged) {
			if (window.gdAsCptChanged) {
				window.gdAsBtnText = geodir_search_update_button();
			}
		<?php if ( geodir_search_ajax_search_type() != 'auto' ) { ?>
			if (!window.gdAsCptChanged) {
				jQuery('.geodir_submit_search').html(geodir_search_update_button());
				if(window.gdiTarget && e.target && window.gdiTarget==e.target&&window.gdoTarget){geodir_search_trigger_submit(jQuery(this))};
			}
		<?php } else { ?>
			geodir_search_trigger_submit(jQuery(this));
		<?php } ?>
		}
		window.gdoTarget = false;
		window.gdiTarget = null;
	});
	jQuery('form[name="geodir-listing-search"]').on('submit', function(e){
		e.preventDefault();
		if (!window.isSubmiting && !window.gdAsCptChanged) {
			window.isSubmiting = true;
			geodir_search_ajax_submit(this);
		}
		return false;
	});

	jQuery(window).on('geodir_search_show_ajax_results', function(e,rdata) {
		if (jQuery('.gd-list-view-select .dropdown-item.active').length) {
			jQuery('.gd-list-view-select .dropdown-item.active').trigger('click');
		}
		init_read_more();
		geodir_init_lazy_load();
		geodir_refresh_business_hours();
		geodir_load_badge_class();
		if(typeof aui_init==="function"){aui_init();}
		geodir_search_pagi_init(rdata.paged, rdata.max_num_pages);
		if (rdata.page_title) {
			var $pageTitleEl = '';
			if (jQuery('.geodir-ajax-search-title').length) {
				$pageTitleEl = jQuery('.geodir-ajax-search-title');
			} else if (rdata.page_title_el && jQuery(rdata.page_title_el).length) {
				$pageTitleEl = jQuery(rdata.page_title_el);
			} else if (jQuery('#main header .page-title').length) {
				$pageTitleEl = jQuery('#main header .page-title');
			} else if (jQuery('#sd-archive-map .entry-title').length) {
				$pageTitleEl = jQuery('#sd-archive-map .entry-title:first');
			} else if (jQuery('.elementor-widget-theme-archive-title .elementor-heading-title').length) {
				$pageTitleEl = jQuery('.elementor-widget-theme-archive-title .elementor-heading-title:first');
			} else if (jQuery('.uk-article .uk-article-title').length) {
				$pageTitleEl = jQuery('.uk-article .uk-article-title:first');
			} else if (jQuery('.entry-title').length) {
				$pageTitleEl = jQuery('.entry-title:first');
			}
			if ($pageTitleEl) {
				jQuery($pageTitleEl).html(rdata.page_title);
			}
		}
		if (rdata.meta_title) {
			jQuery('head title').text(rdata.meta_title);
		}
		if(window.gdAsLoadPagi&&!window.gdAsLoadMore){if(rdata.loopContainer&&jQuery(rdata.loopContainer).length){var pagiScroll=jQuery(rdata.loopContainer).offset().top;if(pagiScroll>100){jQuery("html,body").animate({scrollTop:pagiScroll-100},300)}}}
		<?php if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) { ?>
		if (jQuery('[name="snear"]') && typeof rdata.near != 'undefined') {
			jQuery('[name="snear"]').val(rdata.near);
		}
		if (jQuery('.geodir_map_container').length) {
			var gdMapCanvas = geodir_search_map_canvas();
			var loadMap = false;
		<?php if ( geodir_lazy_load_map() ) { ?>
			if (window.geodirMapAllScriptsLoaded) {
				loadMap = true;
			}
		<?php } else { ?>
			loadMap = true;
		<?php } ?>
			if (loadMap && gdMapCanvas && !rdata.no_map_update) {
				geodir_params.gMarkerAnimation = true;
				if (window.gdAsIsMapSearch) {
					geodir_params.gMarkerReposition = true;
				}
				is_zooming = true;
				<?php if ( (bool) geodir_get_option( 'advs_map_search' ) ) { ?>
				jQuery('.geodir-map-search-btn').removeClass('d-none');
				jQuery('.geodir-map-search-load').removeClass('d-inline-block').addClass('d-none');
				if (!jQuery('input#geodir_map_move').is(':checked')) {
					geodir_search_on_redo_search('hide');
				}
				<?php } ?>
				map_ajax_search(gdMapCanvas, '', rdata.markers, true, rdata.gdLoadMore);
				setTimeout(function() {
					geodir_animate_markers();
					is_zooming = false;
				}, 1000);
			}
			<?php if ( (bool) geodir_get_option( 'advs_map_search' ) && geodir_get_option( 'advs_map_search_type' ) == 'all' ) { ?>
			if (rdata.no_map_update) {
				is_zooming = true;
				setTimeout(function() {
					geodir_animate_markers();
					is_zooming = false;
				}, 1000);
			}
			<?php } ?>
		}
		<?php } ?>
	});

	geodir_search_pagi_init(<?php echo absint( $current_page ); ?>, <?php echo absint( $total_pages ); ?>);

	jQuery(document).on("click", ".geodir-loop-paging-container a.page-link", function(e) {
		pagenum = parseInt(jQuery(this).data('geodir-apagenum'));
		if (pagenum > 0) {
			window.gdAsLoadPagi = true;
			window.gdAsPaged = pagenum;
			geodir_search_trigger_submit();
		}
	});
	jQuery(document).on("click", ".geodir-search-load-more", function(e) {
		var iNext = parseInt(jQuery(this).data('next-page'));
		var iPages = parseInt(jQuery(this).data('total-pages'));
		if (iNext > 0 && iPages >= iNext) {
			window.gdAsLoadPagi = true;
			window.gdAsLoadMore = true;
			<?php if ( $pagination == 'infinite' ) { ?>
			jQuery(".geodir-search-load-more").closest('.geodir-ajax-paging').removeClass('d-none');
			<?php } else { ?>
			jQuery(".geodir-search-load-more").html('<i class="fas fa-circle-notch fa-spin <?php echo ( $aui_bs5 ? 'me-1' : 'mr-1' ); ?>"></i> ' + geodir_search_params.txt_loading).prop('disabled', true);
			<?php } ?>
			window.gdAsPaged = iNext;
			geodir_search_trigger_submit();
		}
	});
	if (jQuery('.geodir-loop-event-filter .dropdown-menu').length) {
		jQuery('.geodir-loop-event-filter .dropdown-menu').find('a').prop('href', 'javascript:void(0);');
		jQuery('.geodir-loop-event-filter .dropdown-item').on("click", function(){
			var cEtype = jQuery('.geodir-loop-event-filter .dropdown-item.active').data('etype');
			jQuery('.geodir-loop-event-filter .dropdown-item').removeClass('active');
			jQuery(this).addClass('active');
			jQuery('.geodir-loop-event-filter #geodir-sort-by').html(jQuery(this).text() + ' <i class="fas fa-sort"></i>');
			if (cEtype != jQuery(this).data('etype')) {
				geodir_search_trigger_submit();
			}
		});
	}

	<?php if ( (bool) geodir_get_option( 'advs_map_search' ) ) { ?>setTimeout(function(){geodir_search_map_search_init()},250);<?php if ( $map_move_checked ) { ?>jQuery(document).on('geodir.mapMoveAdded',function(){setTimeout(function(){if(jQuery("#geodir_map_move").length){jQuery("#geodir_map_move").prop("checked",true).trigger("change")}},2000)});<?php } } ?>
	<?php } else { if ( geodir_is_page( 'search' ) ) { ?>
	jQuery('form[name="geodir-listing-search"]').off('change').on("change", function(e) {
		if (window.gdAsCptChanged) {
			window.gdAsBtnText = geodir_search_update_button();
		} else {
			jQuery('.geodir_submit_search').html(geodir_search_update_button());
		}
	});
	<?php } } ?>
	jQuery(document).on("click", ".geodir-clear-filters", function(e) {
		window.isClearFilters = true;
		jQuery('.gd-adv-search-labels .gd-adv-search-label').each(function(e) {
			if (!jQuery(this).hasClass('geodir-clear-filters')) {
				jQuery(this).trigger('click');
			}
		});
		window.isClearFilters = false;
		geodir_search_trigger_submit();
	});
	<?php if ( $pagination == 'infinite' ) { ?>
	var $_el = geodir_search_archive_loop_el();
	if ($_el.hasClass('elementor-posts-container')) {
		$_el = $_el.parent();
	}
	var _isWindow = ($_el.css('overflow-y') === 'visible'), _$window = jQuery(window), _$body = jQuery('body'), _$scroll = _isWindow ? _$window : $_el;
	_$scroll.on('scroll', function() {
		geodir_search_setup_infinite_scroll($_el, _$scroll);
	});
	<?php } ?>

	<?php if ( $design_style ) { ?>
	geodir_distance_popover_trigger();
	var bsDash = '<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>';
	jQuery(document).on('change', '.geodir-distance-trigger', function(){
		var $cont = jQuery(this).closest('.geodir-popover-content'), $_distance = jQuery('#' + $cont.attr('data-' + bsDash + 'container'));
		<?php if ( $aui_bs5 ) { ?>
		if (jQuery(this).val()=='km' || jQuery(this).val()=='mi') {
			jQuery('.geodir-units-wrap .btn', $cont).removeClass('active');
			jQuery('.geodir-units-wrap .btn.geodir-unit-' + jQuery(this).val(), $cont).addClass('active');
		}
		<?php } ?>
		if ($_distance.length) {
			var dist = parseInt($cont.find('[name="_gddist"]').val());
			var unit = $cont.find('[name="_gdunit"]:checked').val();
			if (!unit) {
				unit = '<?php echo esc_js( strip_tags( geodir_get_option( 'search_distance_long' ) ) ) ; ?>';
				if (unit=='miles') {
					unit = 'mi';
				}
			}
			var title = dist + ' ' + $cont.find('[name="_gdunit"]:checked').parent().attr('title');
			jQuery('[name="dist"]', $_distance).remove();
			jQuery('[name="_unit"]', $_distance).remove();
			var $btn = $_distance.find('.geodir-distance-show');
			$_distance.append('<input type="hidden" name="_unit" value="' + unit + '" data-ignore-rule>');
			if (dist > 0) {
				$_distance.append('<input type="hidden" name="dist" value="' + dist + '">');
				$btn.removeClass('btn-secondary').addClass('btn-primary');
				jQuery('.-gd-icon', $btn).addClass('d-none');
				jQuery('.-gd-range', $btn).removeClass('d-none').text(dist + ' ' + unit).attr('title', title);
			} else {
				$_distance.append('<input type="hidden" name="dist" value="">');
				$btn.removeClass('btn-primary').addClass('btn-secondary');
				jQuery('.-gd-icon', $btn).removeClass('d-none');
				jQuery('.-gd-range', $btn).addClass('d-none');
			}
			if ($_distance.closest('form').find('[name="snear"]').val()) {
				jQuery('[name="dist"]', $_distance).trigger('change');
			}
			geodir_popover_show_distance($_distance.closest('form'), dist, unit);
		}
	});
	jQuery(document).on('input', '.geodir-distance-range', function(){
		var $cont = jQuery(this).closest('.geodir-popover-content'), $_distance = jQuery('#' + $cont.attr('data-' + bsDash + 'container'));
		geodir_popover_show_distance($_distance.closest('form'), parseInt(jQuery(this).val()));
	});
	jQuery('body').on('click', function (e) {
		if (e && !e.isTrigger && jQuery('.geodir-distance-popover[aria-describedby]').length) {
			jQuery('.geodir-distance-popover[aria-describedby]').each(function () {
				if (!jQuery(this).is(e.target) && jQuery(this).has(e.target).length === 0 && jQuery('.popover').has(e.target).length === 0) {
					jQuery(this).popover('hide');
				}
			});
		}
	});
	jQuery("body").on("geodir_setup_search_form",function($_form){if(typeof aui_cf_field_init_rules==="function"){setTimeout(function(){aui_cf_field_init_rules(jQuery),100})}});
	<?php } ?>
});

<?php if ( $design_style ) { ?>
function geodir_distance_popover_trigger() {
	if (!jQuery('.geodir-distance-popover').length) {
		return;
	}
	var bsDash = '<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>';
	jQuery('.geodir-distance-popover').popover({
		html: true,
		placement: 'top',
		sanitize: false,
		customClass: 'geodir-popover',
		template: '<div class="popover" role="tooltip"><div class="<?php echo ( $aui_bs5 ? 'popover-arrow' : 'arrow' ); ?>"></div><div class="popover-body<?php echo ( $aui_bs5 ? ' p-2' : '' ); ?>"></div></div>'
	}).on('hidden.bs.popover', function(e) {
		var dist = parseInt(jQuery(this).closest('.gd-search-field-distance').find('[name="dist"]').val());
		var unit = jQuery(this).closest('.gd-search-field-distance').find('[name="_unit"]').val();
		var content = jQuery(this).attr('data-' + bsDash + 'content');
		content = content.replace(' geodir-unit-mi active"', ' geodir-unit-mi"');
		content = content.replace(' geodir-unit-km active"', ' geodir-unit-km"');
		content = content.replace("checked='checked'", '');
		content = content.replace('checked="checked"', '');
		content = content.replace('geodir-drange-values', 'geodir-drange-values d-none');
		content = content.replace(' d-none d-none', ' d-none');
		content = content.replace('value="' + unit + '"', 'value="' + unit + '" checked="checked"');
		content = content.replace(' geodir-unit-' + unit + '"', ' geodir-unit-' + unit + ' active"');
		content = content.replace(' value="' + jQuery(this).attr('data-value') + '" ', ' value="' + dist + '" ');
		jQuery(this).attr('data-' + bsDash + 'content',content);
		jQuery(this).attr('data-value', dist);
	}).on('shown.bs.popover', function(e) {
		geodir_popover_show_distance(jQuery(this).closest('form'));
	});
}
function geodir_popover_show_distance($form, dist, unit) {
	if (!$form) {
		$form = jQuer('body');
	}
	if (typeof dist == 'undefined') {
		dist = parseInt(jQuery('[name="dist"]', $form).val());
	}
	jQuery('.geodir-drange-dist').text(dist);
	if (typeof unit == 'undefined') {
		unit = jQuery('[name="_unit"]', $form).val();
		<?php if ( $aui_bs5 ) { ?>
		if (unit && jQuery('.btn.geodir-unit-' + unit, $form).length && !jQuery('.btn.geodir-unit-' + unit, $form).hasClass('active')) {
			jQuery('.geodir-units-wrap .geodir-distance-trigger', $form).removeAttr('checked');
			jQuery('.geodir-units-wrap .geodir-distance-trigger[value="' + unit + '"]', $form).attr('checked', 'checked');
			jQuery('.geodir-units-wrap .btn', $form).removeClass('active');
			jQuery('.btn.geodir-unit-' + unit, $form).addClass('active');
		}
		<?php } ?>
	}
	if (unit) {
		jQuery('.geodir-drange-unit').text(unit);
	}
	if (dist > 0) {
		if (jQuery('.geodir-drange-values').hasClass('d-none')) {
			jQuery('.geodir-drange-values').removeClass('d-none');
		}
	} else {
		if (!jQuery('.geodir-drange-values').hasClass('d-none')) {
			jQuery('.geodir-drange-values').addClass('d-none');
		}
	}
}
<?php } ?>

function geodir_search_setup_advance_search() {
	jQuery('.geodir-search-container.geodir-advance-search-searched').each(function() {
		var $el = this;
		if (jQuery($el).attr('data-show-adv') == 'search') {
			jQuery('.geodir-show-filters', $el).trigger('click');
		}
	});

	jQuery('.geodir-more-filters', '.geodir-filter-container').each(function() {
		var $cont = this;
		var $form = jQuery($cont).closest('form');
		var $adv_show = jQuery($form).closest('.geodir-search-container').attr('data-show-adv');
		if ($adv_show == 'always' && typeof jQuery('.geodir-show-filters', $form).html() != 'undefined') {
			jQuery('.geodir-show-filters', $form).remove();
			if (!jQuery('.geodir-more-filters', $form).is(":visible")) {
				jQuery('.geodir-more-filters', $form).slideToggle(500);
			}
		}
	});
	<?php if ( $design_style ) { ?>
	geodir_distance_popover_trigger();
	<?php } ?>
}

function geodir_search_setup_searched_filters() {
	jQuery(document).on('click', '.gd-adv-search-labels .gd-adv-search-label', function(e) {
		if (!jQuery(this).hasClass('geodir-clear-filters')) {
			var $this = jQuery(this), $form, name, to_name;
			name = $this.data('name');
			to_name = $this.data('names');

			if ((typeof name != 'undefined' && name) || $this.hasClass('gd-adv-search-near')) {
				jQuery('.geodir-search-container form').each(function() {
					$form = jQuery(this);
					if ($this.hasClass('gd-adv-search-near')) {
						name = 'snear';
						jQuery('.sgeo_lat,.sgeo_lon,.geodir-location-search-type', $form).val('');
						jQuery('.geodir-location-search-type', $form).attr('name','');
					}
					if (jQuery('[name="' + name + '"]', $form).closest('.gd-search-has-date').length) {
						jQuery('[name="' + name + '"]', $form).closest('.gd-search-has-date').find('input').each(function(){
							geodir_search_deselect(jQuery(this));
						});
					} else {
						geodir_search_deselect(jQuery('[name="' + name + '"]', $form));
						if (typeof to_name != 'undefined' && to_name) {
							geodir_search_deselect(jQuery('[name="' + to_name + '"]', $form));
						}
						if ((name == 'snear' || name == 'dist') && jQuery('.geodir-distance-popover', $form).length) {
							if (jQuery('[name="_unit"]', $form).length) {
								jQuery('[name="dist"]', $form).remove();
								var $btn = jQuery('.geodir-distance-show', $form);
								$btn.removeClass('btn-primary').addClass('btn-secondary');
								jQuery('.-gd-icon', $btn).removeClass('d-none');
								jQuery('.-gd-range', $btn).addClass('d-none');
							}
						}
					}
				});
				if (!window.isClearFilters) {
					$form = jQuery('.geodir-search-container form');
					if($form.length > 1) {$form = jQuery('.geodir-current-form:visible').length ? jQuery('.geodir-current-form:visible:first') : jQuery('.geodir-search-container:visible:first form');}
					geodir_search_trigger_submit($form);
				}
			}
			$this.remove();
		}
	});
}

function geodir_search_refresh_open_now_times() {
	jQuery('.geodir-search-container select[name="sopen_now"]').each(function() {
		geodir_search_refresh_open_now_time(jQuery(this));
	});
}

function geodir_search_refresh_open_now_time($this) {
	var $option = $this.find('option[value="now"]'), label, value, d, date_now, time, $label, open_now_format = geodir_search_params.open_now_format;
	if ($option.length && open_now_format) {
		if ($option.data('bkp-text')) {
			label = $option.data('bkp-text');
		} else {
			label = $option.text();
			$option.attr('data-bkp-text', label);
		}
		d = new Date();
		date_now = d.getFullYear() + '-' + (("0" + (d.getMonth()+1)).slice(-2)) + '-' + (("0" + (d.getDate())).slice(-2)) + 'T' + (("0" + (d.getHours())).slice(-2)) + ':' + (("0" + (d.getMinutes())).slice(-2)) + ':' + (("0" + (d.getSeconds())).slice(-2));
		time = geodir_search_format_time(d);
		open_now = geodir_search_params.open_now_format;
		open_now = open_now.replace("{label}", label);
		open_now = open_now.replace("{time}", time);
		$option.text(open_now);
		$option.closest('select').data('date-now',date_now);
		/* Searched label */
		$label = jQuery('.gd-adv-search-open_now .gd-adv-search-label-t');
		if (jQuery('.gd-adv-search-open_now').length && jQuery('.gd-adv-search-open_now').data('value') == 'now') {
			if ($label.data('bkp-text')) {
				label = $label.data('bkp-text');
			} else {
				label = $label.text();
				$label.attr('data-bkp-text', label);
			}
			open_now = geodir_search_params.open_now_format;
			open_now = open_now.replace("{label}", label);
			open_now = open_now.replace("{time}", time);
			$label.text(open_now);
		}
	}
}

function geodir_search_format_time(d) {
	var format = geodir_search_params.time_format, am_pm = eval(geodir_search_params.am_pm), hours, aL, aU;

	hours = d.getHours();
	if (hours < 12) {
		aL = 0;
		aU = 1;
	} else {
		hours = hours > 12 ? hours - 12 : hours;
		aL = 2;
		aU = 3;
	}

	time = format.replace("g", hours);
	time = time.replace("G", (d.getHours()));
	time = time.replace("h", ("0" + hours).slice(-2));
	time = time.replace("H", ("0" + (d.getHours())).slice(-2));
	time = time.replace("i", ("0" + (d.getMinutes())).slice(-2));
	time = time.replace("s", '');
	time = time.replace("a", am_pm[aL]);
	time = time.replace("A", am_pm[aU]);

	return time;
}

function geodir_search_deselect(el) {
	var fType = jQuery(el).prop('type');
	switch (fType) {
		case 'checkbox':
		case 'radio':
			jQuery(el).prop('checked', false);
			jQuery(el).trigger('gdclear');
			break;
		default:
			jQuery(el).val('');
			jQuery(el).trigger('gdclear');
			break;
	}
}

function geodir_search_trigger_submit($form) {
	if (!$form) {
		$form = jQuery('.geodir-current-form').length ? jQuery('.geodir-current-form') : jQuery('form[name="geodir-listing-search"]');
	}
	if ($form.data('show') == 'advanced') {
		if (jQuery('form.geodir-search-show-all:visible').length) {
			$form = jQuery('form.geodir-search-show-all');
		} else if (jQuery('form.geodir-search-show-main:visible').length) {
			$form = jQuery('form.geodir-search-show-main');
		} else if (jQuery('[name="geodir_search"]').closest('form:visible').length) {
			$form = jQuery('[name="geodir_search"]').closest('form');
		}
	}
	geodir_click_search($form.find('.geodir_submit_search'));
}
<?php if ( $has_ajax_search ) { ?>
function geodir_search_archive_loop_el() {
	var $archiveEl = jQuery('.geodir-loop-container');
	<?php if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) { ?>
	var $ele = jQuery('[data-widget_type="archive-posts.gd_archive_custom"] .elementor-posts-container');
	if ($ele.length) {
		$archiveEl = $ele;
	}
	<?php } ?>
	return $archiveEl;
}
function geodir_search_map_canvas() {
	var canvas = '';
	if (jQuery('.geodir_map_container').length) {
		$canvas = jQuery('.geodir_map_container:visible').length ? jQuery('.geodir_map_container:visible') : jQuery('.geodir_map_container');
		if ($canvas.length) {
			canvas = $canvas.find('.geodir-map-canvas').prop('id');
		}
	}
	return canvas;
}
function geodir_search_ajax_submit(form) {
	jQuery('[name="geodir-listing-search"]').removeClass('geodir-current-form');
	var $form = jQuery(form);
	jQuery(form).addClass('geodir-current-form');
	var formData = $form.serializeArray();
	if ($form.data('show') == 'main' && jQuery('form.geodir-search-show-advanced:visible').length) {
		_formData = jQuery('form.geodir-search-show-advanced:visible').serializeArray();
		if (_formData && typeof _formData == 'object' && _formData.length) {
			formData = formData.concat(_formData);
		}
	} else if ($form.data('show') == 'advanced' && jQuery('form.geodir-search-show-main:visible').length) {
		_formData = jQuery('form.geodir-search-show-main:visible').serializeArray();
		if (_formData && typeof _formData == 'object' && _formData.length) {
			formData = _formData.concat(formData);
		}
	}
	if (jQuery('.geodir-loop-event-filter .dropdown-item.active').length) {
		var etype = jQuery('.geodir-loop-event-filter .dropdown-item.active').data('etype');
		if (etype) {
			formData.push({"name":"etype","value":etype});
		}
	}
	var formAction = $form.prop('action').split('?' )[0] + '?' + jQuery.param(formData);
	if (window.gdAsPaged > 0 && !geodir_search_params.ajaxPagination) {
		formAction += '&paged=' + parseInt(window.gdAsPaged);
	}
	/* Prevent error: Failed to execute replaceState on History */
	if ((document.location.href).indexOf("http://") == 0 && formAction.indexOf("https://") == 0) {
		formAction = formAction.replace(/https:\/\//g, 'http://');
	} else if ((document.location.href).indexOf("https://") == 0 && formAction.indexOf("http://") == 0) {
		formAction = formAction.replace(/http:\/\//g, 'https://');
	}
	if ( window.history && window.history.replaceState) {
		window.history.replaceState(null, '', formAction);
	}
	formData.push({"name":"action","value":'geodir_ajax_search'}, {"name":"_nonce","value":geodir_params.basic_nonce});
	if (jQuery('.geodir-loop-attrs').length) {
		var attrs = jQuery('.geodir-loop-attrs').data();
		if (attrs) {
			for(var k in attrs){
				formData.push({"name":'_gd_loop[' + (k.replace("gdl_", "")) + ']',"value":attrs[k]});
			}
		}
	}
	if (jQuery('.geodir-pagi-attrs').length) {
		var attrs = jQuery('.geodir-pagi-attrs').data();
		if (attrs) {
			for(var k in attrs){
				formData.push({"name":'_gd_pagi[' + (k.replace("gdl_", "")) + ']',"value":attrs[k]});
			}
		}
	}
	if (window.gdAsPaged > 0) {
		formData.push({"name":"paged","value":window.gdAsPaged});
	}
	<?php if ( (bool) geodir_get_option( 'advs_map_search' ) ) { ?>
	<?php if ( geodir_get_option( 'advs_map_search_type' ) == 'all' ) { ?>
		formData.push({"name":"_gd_via","value":"pagination"});
	<?php } ?>
	if (window.gdAsIsMapSearch && window.gdAsSearchBounds) {
		jQuery.each(window.gdAsSearchBounds, function(itm, val) {
			formData.push({"name":itm,"value":val});
		});
	}
	<?php } ?>
	<?php if ( ! empty( $total_pages ) ) { ?>
	formData.push({"name":"_max_pages","value":<?php echo absint( $total_pages ); ?>});
	<?php } ?>
	<?php if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) { ?>
	var $elLoop = jQuery('[data-widget_type="archive-posts.gd_archive_custom"]');
	if ($elLoop.length) {
		formData.push({"name":"_ele_tmpl","value":$elLoop.closest('.elementor').data('elementor-id')});
		formData.push({"name":"_ele_id","value":$elLoop.data('id')});
	}
	<?php } ?>
	jQuery.ajax({
		url: geodir_params.gd_ajax_url,
		type: 'POST',
		data: formData,
		dataType: 'json',
		beforeSend: function(xhr, obj) {
			jQuery('form[name="geodir-listing-search"]').each(function(){
				if (!jQuery(this).hasClass('geodir-submitting')) {
					jQuery('form[name="geodir-listing-search"]').addClass('geodir-submitting');
				}
			});
			if (window.gdAsIsMapSearch) {
				window.gdAsMapSearching = true;
			}
			geodir_search_wait(1);
			jQuery('body').addClass('gdas-ajax-loading');
			if (!window.gdAsLoadMore) {
				$archiveEl = geodir_search_archive_loop_el();
				$archiveEl.find('.card').addClass('gd-shimmer');
				if ($archiveEl.find('.elementor-post .elementor-row').length) {
					$archiveEl.find('.elementor-post .elementor-row').parent().addClass('gd-shimmer');
					$archiveEl.find('.elementor-post .gd-shimmer .gd-shimmer').removeClass('gd-shimmer');
					$archiveEl.find('.elementor-post .gd-shimmer .elementor-element').addClass('gd-shimmer-el');
				}
			}
			gdMapCanvas = geodir_search_map_canvas();
			if (gdMapCanvas && jQuery('#' + gdMapCanvas + '_loading_div').length) {
				gdMapLoading = true;
				<?php if ( geodir_lazy_load_map() ) { ?>
					if (!window.geodirMapAllScriptsLoaded) {
						gdMapLoading = false;
					}
				<?php } ?>
				<?php if ( (bool) geodir_get_option( 'advs_map_search' ) && geodir_get_option( 'advs_map_search_type' ) == 'all' ) { ?>
				if (window.gdAsLoadPagi) {
					gdMapLoading = false;
				}
				<?php } ?>
				if (gdMapLoading) {
					<?php if ( (bool) geodir_get_option( 'advs_map_search' ) ) { ?>
					jQuery('.geodir-map-search-btn').addClass('d-none');
					jQuery('.geodir-map-search-load').addClass('d-inline-block').removeClass('d-none');
					<?php } else { ?>
					jQuery('#' + gdMapCanvas + '_loading_div').show();
					<?php } ?>
				}
			}
		}
	})
	.done(function(data, textStatus, jqXHR) {
		jQuery('form[name="geodir-listing-search"]').removeClass('geodir-submitting');
		window.isSubmiting = false;
		if (typeof data == 'object') {
			if (data.data && data.data.loop && data.data.loop.content) {
				var _data = data.data;
				$loop_container = _data.loop && _data.loop.container && jQuery(_data.loop.container).length ? jQuery(_data.loop.container) : geodir_search_archive_loop_el();
				_data.loopContainer = $loop_container;
				if (window.gdAsLoadMore) {
					$loop_container.append(_data.loop.content);
				} else {
					$loop_container.html(_data.loop.content);
				}

				var $pagi_container = _data.pagination && _data.pagination.container && jQuery(_data.pagination.container).length ? jQuery(_data.pagination.container) : jQuery('.geodir-loop-paging-container');
				_data.pagiContainer = $pagi_container;
				if (_data.pagination && _data.pagination.content) {
					if (window.gdAsLoadMore) {
						$pagi_container.append(_data.pagination.content).show();
					} else {
						if ($pagi_container.length) {
							$pagi_container.html(_data.pagination.content).show();
						} else {
							$loop_container.after(_data.pagination.content);
						}
					}
				} else {
					$pagi_container.hide();
				}
				$pagi_container.removeClass('geodir-ajx-paging-setup');
				var $filt_container = _data.filters && _data.filters.container && jQuery(_data.filters.container).length ? jQuery(_data.filters.container) : jQuery('.gd-adv-search-labels');
				_data.filtContainer = $filt_container;
				if (_data.filters && _data.filters.content) {
					if ($filt_container.length) {
						$filt_container.show().replaceWith(_data.filters.content);
					} else {
						if (jQuery('.geodir-loop-actions-container').length) {
							if (jQuery('.geodir-loop-actions-container .justify-content-end').length) {
								jQuery('.geodir-loop-actions-container .justify-content-end').append(_data.filters.content);
							} else {
								jQuery('.geodir-loop-actions-container').append(_data.filters.content);
							}
						} else {
							$filt_container.before(_data.filters.content);
						}
					}
				} else {
					$filt_container.html('').hide();
				}
				_data.gdLoadMore = window.gdAsLoadMore;

				jQuery(window).trigger('geodir_search_show_ajax_results', _data);
			}
		}
	})
	.always(function(data, textStatus, jqXHR) {
		jQuery('form[name="geodir-listing-search"]').removeClass('geodir-submitting');
		window.isSubmiting = false;
		geodir_search_wait(0);
		window.gdAsPaged = 0;
		window.gdAsLoadMore = false;
		window.gdAsLoadPagi = false;
		jQuery(".geodir-search-load-more").text(geodir_search_params.txt_loadMore).prop('disabled', false);
		jQuery('body').removeClass('gdas-ajax-loading');
		$archiveEl = geodir_search_archive_loop_el();
		$archiveEl.find('.gd-shimmer').removeClass('gd-shimmer');
	});
}

function geodir_search_pagi_init(iCurrent, iPages, pageWrap) {
	if (!pageWrap) {
		pageWrap = '.geodir-loop-paging-container';
	}
	<?php if ( $pagination == 'infinite' ) { ?>
	if (jQuery(pageWrap).length == 1) {
		$archiveEl = geodir_search_archive_loop_el();
		if (jQuery(pageWrap).offset().top && parseInt(jQuery(pageWrap).offset().top) < parseInt($archiveEl.offset().top)) {
			var moveWrap = jQuery(pageWrap).addClass('w-100').detach();
			if ($archiveEl.hasClass('elementor-posts-container')) {
				$archiveEl.closest('.elementor-element').after(moveWrap);
			} else {
				$archiveEl.after(moveWrap);
			}
		}
	}
	<?php } ?>
	jQuery(pageWrap).each(function() {
		var $paging = jQuery(this);
		$paging.addClass('geodir-ajax-paging');
		if (!$paging.hasClass('geodir-ajx-paging-setup')) {
			<?php if ( $pagination ) { ?>
			<?php if ( $pagination == 'infinite' ) { ?>
			jQuery(this).addClass('d-none');
			if (iCurrent < iPages) {
				$paging.html('<div class="text-center p-3" title="<?php esc_attr_e( 'Loading...', 'geodiradvancesearch' ); ?>"><button type="button" class="d-none btn btn-primary btn-sm geodir-search-load-more" data-next-page="' + (iCurrent + 1) + '" data-total-pages="' + iPages + '">' + geodir_search_params.txt_loadMore + '</button><i class="fas fa-circle-notch fa-spin fa-2x fa-fw"></i><span class="mt-1 font-weight-bold fw-bold d-block"><?php esc_attr_e( 'Loading...', 'geodiradvancesearch' ); ?></span></div>');
			} else {
				$paging.html('');
			}
			<?php } else { ?>
			if (iCurrent < iPages) {
				jQuery(this).show();
				$paging.html('<div class="text-center p-3"><button type="button" class="btn btn-primary btn-sm geodir-search-load-more" data-next-page="' + (iCurrent + 1) + '" data-total-pages="' + iPages + '">' + geodir_search_params.txt_loadMore + '</button></div>');
			} else {
				jQuery(this).hide();
			}
			<?php } ?>
			<?php } else { ?>
			if (jQuery('.pagination .page-link', $paging).length) {
				jQuery('.pagination a.page-link', $paging).each(function() {
					href = jQuery(this).attr('href');
					hrefs = href.split("#");
					page = (hrefs.length > 1 && parseInt(hrefs[1]) > 0 ? parseInt(hrefs[1]) : (parseInt(jQuery(this).text()) > 0 ? parseInt(jQuery(this).text()) : 0));
					if (!page > 0) {
						var ePage = jQuery(this).closest('.pagination').find('[aria-current="page"]');
						if (!ePage.length) {
							ePage = jQuery(this).closest('.pagination').find('.page-link.current');
						}
						if (!ePage.length) {
							ePage = jQuery(this).closest('.pagination').find('.page-link.active');
						}
						var cpage = ePage.length ? parseInt(ePage.text()) : 0;
						if (cpage > 0) {
							if (jQuery(this).hasClass('next')) {
								page = cpage + 1;
							} else if (jQuery(this).hasClass('prev')) {
								page = cpage - 1;
							}
						}
					}
					if (!page > 0) {
						page = 1;
					}
					jQuery(this).attr('data-geodir-apagenum', page);
					jQuery(this).attr('href', 'javascript:void(0)');
				});
			}
			<?php } ?>
			$paging.addClass('geodir-ajx-paging-setup');
		}
	});
}
function geodir_search_map_control() {
	if (window.gdMaps == 'google' || window.gdMaps == 'osm') {
		var gdMapCanvas = geodir_search_map_canvas();
		if (!gdMapCanvas) {
			return;
		}
		if (window.gdMaps == 'osm') {
			ctrlClass = 'm-0';
			ctrlStyle = '';
		} else {
			ctrlClass = 'm-2';
			ctrlStyle = 'margin-left:3.5em!important;';
		}
		var ctrlHtml = '<div class="geodir-map-search-' + window.gdMaps + ' geodir-map-search text-center" style="font:400 14px Roboto,Arial,sans-serif;min-width:160px;' + ctrlStyle + '"><div class="geodir-map-search-btn px-2 py-2 bg-light text-dark rounded-sm rounded-1 shadow-sm ' + ctrlClass + '" style="padding-left:.75rem!important;padding-right:.75rem!important;"><label title="<?php echo esc_attr( $map_search_label ); ?>" class="m-0 p-0 c-pointer geodir-map-move-search" for="geodir_map_move" style="vertical-align:middle;display:table-cell;line-height:1rem"><input class="mt-0 mb-0 p-0<?php echo ( $aui_bs5 ? 'me-1 ms-0' : 'mr-1 ml-0' ); ?>" type="checkbox" value="1" id="geodir_map_move"<?php echo $map_move_checked; ?>><?php echo esc_js( $map_search_label ); ?></label><label title="<?php echo esc_attr( $redo_search_label ); ?>" class="m-0 p-0 c-pointer geodir-map-redo-search d-none" style="vertical-align:middle;display:table-cell;line-height:1rem"><i class="fas fa-redo" aria-hidden="true"></i> <?php echo esc_js( $redo_search_label ); ?></label></div><div class="d-none geodir-map-search-load px-4 bg-light text-dark rounded-sm rounded-1 shadow-sm ' + ctrlClass + '" style="padding-top:.15rem !important;padding-bottom:.15rem !important"><label title="<?php echo esc_attr__( 'Loading...', 'geodiradvancesearch' ); ?>" class="m-0 p-0 geodir-map-load-search text-center" style="vertical-align:middle;display:table-cell;line-height:1rem"><i class="fas fa-ellipsis-h fa-fw fa-2x fa-beat-fade" style="--fa-animation-duration:.66s;"></i></label></div></div>';

		if (!jQuery.goMap.map) {
			try {var gdMapCanvasO = eval(gdMapCanvas);} catch(e) {var gdMapCanvasO = {};}
			if (typeof gdMapCanvasO != 'object' && typeof gdMapCanvasO != 'array') {gdMapCanvasO = {};}
			jQuery("#" + gdMapCanvas).goMap(gdMapCanvasO);
		}

		var mapMove = false;
		if (window.gdMaps == 'google') {
			var mDiv = document.createElement("div");
			jQuery(mDiv).html(ctrlHtml);
			var cPos = geodir_params.gSearchPos ? parseInt(geodir_params.gSearchPos) : google.maps.ControlPosition.TOP_LEFT;
			jQuery.goMap.map.controls[cPos].push(mDiv);
			mapMove = true;
		} else if (window.gdMaps == 'osm') {
			var ourCustomControl = L.Control.extend({
				options: {
					position: 'topleft'
				},
				onAdd: function (map) {
					var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom geodir-map-control-o text-nowrap position-absolute');
					container.innerHTML = ctrlHtml;
					container.style.marginLeft = '52px';
					return container;
				}
			});
			jQuery.goMap.map.addControl(new ourCustomControl());
			mapMove = true;
		}
		if (mapMove) {
			window.geodirMapMoveAdded = true;
			jQuery(document).trigger('geodir.mapMoveAdded');
		}
		<?php if ( $map_move_checked ) { ?>
		setTimeout(function(){if(jQuery("#geodir_map_move").length){jQuery("#geodir_map_move").prop("checked",true).trigger("change")}},2000);
		<?php } ?>
	}
}
function geodir_search_map_search_init() {
	<?php if ( geodir_lazy_load_map() ) { ?>
	jQuery(window).on('geodirMapAllScriptsLoaded', function(){
		geodir_search_map_search_load();
	});
	setTimeout(function(){
		if (!(window.geodirMapMoveAdded && typeof gdMapSearchLoad)) {
			geodir_search_map_search_load();
		}
	},3000);
	<?php } else { ?>
	geodir_search_map_search_load();
	<?php } ?>

	jQuery(document).on("change", 'input#geodir_map_move', function(e) {
		if (jQuery(this).is(':checked')) {
			window.gdAsMapChecked = true;
			remCls = 'bg-light text-dark';
			addCls = 'bg-primary text-light';
		} else {
			window.gdAsMapChecked = false;
			remCls = 'bg-primary text-light';
			addCls = 'bg-light text-dark';
		}
		jQuery(this).closest('.geodir-map-search-btn').removeClass(remCls).addClass(addCls);
	});
	jQuery(document).on("click", '.geodir-map-redo-search', function(e) {
		geodir_search_trigger_map_search();
		setTimeout(function(){
			geodir_search_on_redo_search('hide');
		},1000);
	});
}

function geodir_search_map_search_load() {
    geodir_search_map_control();
    if (typeof gdMapSearchLoad == 'undefined') {
        gdMapSearchLoad = true;
    }
    if (typeof is_zooming == 'undefined') {
        is_zooming = true;
    }
    setTimeout(function() {
        if (window.gdMaps == 'google') {
            google.maps.event.addListener(jQuery.goMap.map, 'idle', function() {
                geodir_search_on_map_idle();
            });
        } else if (window.gdMaps == 'osm') {
            L.DomEvent.addListener(jQuery.goMap.map, 'moveend', function() {
                geodir_search_on_map_idle();
            });
        }
    }, 1000);
}

function geodir_search_on_map_idle() {
    if (gdMapSearchLoad) {
        gdMapSearchLoad = false;
    } else {
        if (!is_zooming) {
            is_zooming = true;
            geodir_search_on_map_update();
            is_zooming = false;
        }
    }
}

function geodir_search_on_map_update() {
    window.gdAsMapChanged = true;
    if (jQuery('input#geodir_map_move').is(':checked')) {
        geodir_search_trigger_map_search();
    } else {
        geodir_search_on_redo_search('show');
    }
}

function geodir_search_trigger_map_search() {
    gdBounds = jQuery.goMap.map.getBounds();
    gdZoom = jQuery.goMap.map.getZoom();
    if (window.gdMaps == 'osm') {
        gdNELat = gdBounds.getNorthEast().lat;
        gdNELng = gdBounds.getNorthEast().lng;
        gdSWLat = gdBounds.getSouthWest().lat;
        gdSWLng = gdBounds.getSouthWest().lng;
    } else {
        gdNELat = gdBounds.getNorthEast().lat();
        gdNELng = gdBounds.getNorthEast().lng();
        gdSWLat = gdBounds.getSouthWest().lat();
        gdSWLng = gdBounds.getSouthWest().lng();
    }
    window.gdAsIsMapSearch = true;
    window.gdAsMapSearching = true;
    searchBounds = { "zl": gdZoom, "lat_ne": gdNELat, "lon_ne": gdNELng, "lat_sw": gdSWLat, "lon_sw": gdSWLng };
    if ((window.gdAsSearchBounds && window.gdAsSearchBounds != searchBounds) || !window.gdAsSearchBounds) {
        window.gdAsSearchBounds = searchBounds;
        window.isClearFilters = true;
        jQuery('.gd-search-field-near .geodir-search-input-label-clear').trigger('click');
        window.isClearFilters = false;
        geodir_search_trigger_submit();
    }
}

function geodir_search_on_redo_search(a) {
    if (a == 'show') {
        remCls = 'bg-light text-dark';
        addCls = 'bg-primary text-light';
        showAct = ".geodir-map-redo-search";
        hideAct = ".geodir-map-move-search";
    } else {
        remCls = 'bg-primary text-light';
        addCls = 'bg-light text-dark';
        showAct = ".geodir-map-move-search";
        hideAct = ".geodir-map-redo-search";
    }
    jQuery(".geodir-map-redo-search").closest('.geodir-map-search-btn').removeClass(remCls).addClass(addCls);
    jQuery(showAct).removeClass('d-none');
    jQuery(hideAct).addClass('d-none');
}
<?php } ?>
<?php if ( $pagination == 'infinite' ) { ?>
function geodir_search_setup_infinite_scroll($el, _$scroll) {
    var $inner = $el.children().first(), borderTopWidth = parseInt($el.css('borderTopWidth'), 10), borderTopWidthInt = isNaN(borderTopWidth) ? 0 : borderTopWidth, iContainerTop = parseInt($el.css('paddingTop'), 10) + borderTopWidthInt, iTopHeight = ($el.css('overflow-y') === 'visible') ? _$scroll.scrollTop() : $el.offset().top, innerTop = $inner.length ? $inner.offset().top : 0, iTotalHeight = Math.ceil(iTopHeight - innerTop + _$scroll.height() + iContainerTop);
    if (iTotalHeight >= $inner.outerHeight()) {
        jQuery(".geodir-search-load-more").trigger('click');
    }
}
<?php } ?>
function geodir_search_update_button() {
	return '<?php echo addslashes( geodir_search_update_results_button_content() ); ?>';
}
<?php do_action( 'geodir_adv_search_inline_script', $post_type ); ?>
<?php if ( ! empty( $conditional_inline_js ) ) { echo $conditional_inline_js; } ?>
<?php if(0){ ?></script><?php }

		return ob_get_clean();
	}

	public function enqueue_inline_style() {
		global $geodir_search_inline_style;

		if ( ! $geodir_search_inline_style && geodir_design_style() && ! wp_doing_ajax() && geodir_search_has_ajax_search( true ) ) {
			$geodir_search_inline_style = true;

			$inline_css = '<style>';
			$inline_css .= '@-webkit-keyframes gdshimmer{0%{background-position-x:120%}to{background-position-x:-100%}}@keyframes gdshimmer{0%{background-position-x:120%}to{background-position-x:-100%}}.gd-shimmer .gd-shimmer-el,.gd-shimmer-anim,.gd-shimmer .card-body>*,.gd-shimmer .card-footer>*,.gd-shimmer .card-img-top>*,.gdas-ajax-loading .geodir-loop-container>.alert,.gdas-ajax-loading .gd-adv-search-labels>label{animation-duration:1.5s;animation-iteration-count:infinite;animation-name:gdshimmer;animation-timing-function:linear;background-color:#f6f7f8!important;background-image:linear-gradient(90deg,#f6f7f8,#edeff1 30%,#f6f7f8 70%,#f6f7f8)!important;background-repeat:no-repeat!important;background-size:200%!important}.gd-shimmer>*>*>*,.gd-shimmer .elementor-button,.gd-shimmer .gd-badge{background:transparent!important}.gd-shimmer img{visibility:hidden}.gd-shimmer,body.gdas-ajax-loading .geodir-loop-container>.alert{border-color:rgba(0,0,0,0.125)!important;}.gd-shimmer *,.gdas-ajax-loading .gd-adv-search-labels *,.gdas-ajax-loading .geodir-loop-container>.alert{border-color:transparent!important;color:transparent!important}.geodir-map-control-o{border-color:transparent!important}';
			if ( geodir_search_ajax_search_type() == 'auto' ) {
				$inline_css .= 'form.geodir-listing-search.geodir-submitting{cursor:wait!important}form.geodir-listing-search.geodir-submitting>*{pointer-events:none!important}';
			}
			$inline_css .= '</style>';

			echo $inline_css;
		}
	}

	public static function localize_core_params( $params = array() ) {
		$params['hasAjaxSearch'] = (bool) geodir_search_has_ajax_search( true );

		return $params;
	}
}
