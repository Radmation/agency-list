<?php
/**
 * Claim Listings Admin.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Admin class.
 */
class GeoDir_Claim_Admin {
    
    /**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );
		add_action( 'geodir_clear_version_numbers', 'geodir_claim_clear_version_number', 30 );
		//add_action( 'geodir_debug_tools', 'geodir_claim_diagnostic_tools', 10 );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 21, 1 );

		add_filter( 'geodir_uninstall_options', 'geodir_claim_uninstall_settings', 30, 1 );
		add_filter( 'geodir_diagnose_multisite_conversion', 'geodir_claim_diagnose_multisite_conversion', 10, 1 );
		add_filter( 'geodir_gd_options_for_translation', 'geodir_claim_settings_to_translation', 10, 1 );

		add_filter( 'geodir_custom_fields_predefined', 'geodir_claim_predefined_custom_fields', 30, 2 );
		//add_filter( 'geodir_default_custom_fields', 'geodir_claim_filter_default_fields', 100, 3 );
		add_filter( 'geodir_db_cpt_default_columns', 'geodir_claim_cpt_db_columns', 10, 3 );
		/* 
		add_action('add_meta_boxes', 'geodir_add_claim_option_metabox', 12 );
		add_action('geodir_before_admin_panel' , 'geodir_display_claim_messages');
		*/


		// ninja forms stuff
		//add_filter( "manage_edit-nf_sub_columns", array( __CLASS__, 'posts_columns' ), 999, 1 );
		//add_action( "manage_nf_sub_posts_custom_column", array( __CLASS__, 'posts_custom_column' ), 999, 2 );

		// bubble count
		if ( get_option( 'geodir_claim_version' ) ) {
			add_filter( 'add_menu_classes', array(__CLASS__, 'admin_menu_claim_count'));
		}

		// Claim nudge.
		add_action( 'post_submitbox_misc_actions', array( $this, 'claim_nudge_email_action' ), 202 );
	}

	/**
	 * Show a bubble notification next to the GD admin menu item if claims are pending.
	 * 
	 * @param $menu
	 *
	 * @return mixed
	 */
	public static function admin_menu_claim_count($menu)
	{

		$counts = geodir_claim_count_claims();
		$warning_count = $counts->pending;
		$warning_title = esc_attr( sprintf( _n( '%d listing claim pending review', '%d listing claims pending review', $warning_count, 'geodir-claim' ), $warning_count) );

		if($warning_count > 0 && !empty($menu)){
			foreach($menu as $menu_key => $menu_data){
				// check its probably a GD post type
				if($menu_data[2]=='geodirectory'){
					$count = absint($warning_count);
					$menu[$menu_key][0] .= " <span class='awaiting-mod  count-$count' title='".$warning_title."'><span class='pending-count'>" . number_format_i18n($count) . '</span></span>';
				}
			}
		}

		return $menu;
	}

	public static function posts_columns( $columns = array() ) {
		$form_id = !empty($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : '';
		if($form_id){
			global $gd_nf_sub_listing_id;
			$form = Ninja_Forms()->form( $form_id )->get();
			$settings = $form->get_settings();
			$fields = Ninja_Forms()->form( $form_id )->get_fields();
			foreach($fields as $id => $field){
//				echo '###'.$field->get_setting( 'key' )." \n";
				if($field->get_setting( 'key' )=='listing_id'){
					$gd_nf_sub_listing_id = $id;
				}
			}
			//print_r($fields);
			//exit;
			if(isset($settings['key']) && $settings['key']=='geodirectory_claim'){
				$columns['geodirectory_claim_actions'] = __( 'GD Actions', 'geodir-claim' );
			}
		}

//		print_r($columns);echo '###';exit;


		return $columns;
	}

	public static function posts_custom_column($column, $post_id){
		global $gd_nf_sub_listing_id;
		if ( $column == 'geodirectory_claim_status' ) {
			echo '###'.$column.'###';

		}
		echo '###'.$column.'###';



	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/admin/admin-functions.php' );
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['geodir-claim-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-claim-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Activation redirect
		if ( ! get_transient( '_geodir_claim_activation_redirect' ) ) {
			return;
		}
	
		delete_transient( '_geodir_claim_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || apply_filters( 'geodir_claim_prevent_activation_redirect', false ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab=claim' ) );
		exit;
	}

	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

		if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) {
		} else {
			$settings_pages[] = include( GEODIR_CLAIM_PLUGIN_DIR . 'includes/admin/settings/class-geodir-claim-settings-claims.php' );
		}

		return $settings_pages;
	}

	/**
	 * Enqueue styles.
	 */
	public static function admin_styles() {
		global $wp_query, $post, $pagenow;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$gd_screen_id = sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$post_type    = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
		$page 		  = ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		// Register styles
		wp_register_style( 'geodir-claim-admin', GEODIR_CLAIM_PLUGIN_URL . '/assets/css/admin.css', array( 'geodir-admin-css' ), GEODIR_CLAIM_VERSION );

		// Admin styles for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_style( 'geodir-claim-admin' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public static function admin_scripts() {
		global $wp_query, $post, $pagenow;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$gd_screen_id = sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$post_type    = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
		$page 		  = ! empty( $_GET['page'] ) ? $_GET['page'] : '';
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'geodir-claim-admin', GEODIR_CLAIM_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_CLAIM_VERSION );

		// Admin scripts for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_script( 'geodir-claim-admin' );
			wp_localize_script( 'geodir-claim-admin', 'geodir_claim_admin_params', geodir_claim_admin_params() );
		}
	}

	/**
	 * Add button to send email to listing contact email.
	 *
	 * @since 2.2.2
	 */
	public function claim_nudge_email_action() {
		global $post, $gd_post;

		if ( ! empty( $gd_post->ID ) && ! empty( $gd_post->post_type ) && current_user_can( 'manage_options' ) && GeoDir_Claim_Email::claim_nudge_allowed( $gd_post ) ) {
			if ( $sent_on = (int) get_post_meta( (int) $gd_post->ID, '_geodir_claim_sent_on', true ) ) {
				$button = __( 'Resend Email to Claim Listing', 'geodir-claim' );
				$sent_date = wp_sprintf( __( '%1$s at %2$s', 'geodir-claim'), date_i18n( 'M j, Y', $sent_on ), date_i18n( 'H:i', $sent_on ) );
			} else {
				$button = __( 'Send Email to Claim Listing', 'geodir-claim' );
				$sent_date = '';
			}
			?>
			<div class="misc-pub-section misc-pub-claim-nudge">
				<div id="claim-nudge-display"><span class="dashicons dashicons-email" style="margin-top:2px;color:#8c8f94"></span> <span class="button button-primary button-small geodir-claim-nudge-action" data-id="<?php echo absint( $gd_post->ID );?>" data-nonce="<?php echo esc_attr( wp_create_nonce( "claim-nudge-sent-email" ) );?>" data-sent="<?php esc_attr_e( 'Resend Email to Claim Listing', 'geodir-claim' );?>"><?php echo $button ?></span> <span class="geodir-sending-wait" style="display:none;"> <i class="fas fa-spinner fa-spin" aria-hidden="true"></i></span></div>
				<small class="geodir-claim-sent-on" style="margin-left:24px;<?php echo ( $sent_date ? '' : 'display:none;'); ?>"><?php echo __( 'Last sent on:', 'geodir-claim') . ' <strong>' . $sent_date . '</strong>'; ?></small>
			</div>
			<?php
		}
	}
}
