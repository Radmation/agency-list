<?php

/**
* The admin-specific functionality of the plugin.
*
* @since 2.0.0
* @package    GD_Recaptcha
* @subpackage GD_Recaptcha/admin/settings
*
* Class GD_Recaptcha_Admin_Settings
*/
class GD_Recaptcha_Admin_Settings extends GeoDir_Settings_Page {

	public $id;

	public $title;

	public $page;

	public $tab;

	public $section;

	/**
	* Constructor.
	*
	* @since 2.0.0
	*
	* GD_Recaptcha_Admin_Settings constructor.
	*/
	public function __construct() {
		$this->id = 'gd-recaptcha';
		$this->title = __( 'reCAPTCHA', 'geodir-recaptcha' );
		$this->page = !empty( $_REQUEST['page'] ) ? sanitize_title( $_REQUEST['page'] ) : '';
		$this->tab = !empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : '';
		$this->section = !empty( $_REQUEST['section'] ) ? sanitize_title( $_REQUEST['section'] ) : '';

		add_filter( 'geodir_settings_tabs_array', array( $this, 'recaptcha_add_settings_page' ), 50, 1 );
		add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	* Add GD duplicate alert settings tab.
	*
	* Add setting tab in gd setting page.
	*
	* @since 2.0.0
	*
	* @param array $pages GD Recaptcha settings page tab page array.
	*
	* @return array $pages.
	*/
	public function recaptcha_add_settings_page( $pages ) {
		if ( ! empty( $this->page ) && 'gd-settings' === $this->page ) {
			$pages[ $this->id ] = $this->title;
		}

		return $pages;
	}

	/**
	* Get GD Recaptcha settings array.
	*
	* @since 2.0.0
	*
	* @param string $current_section Optional Get current settings section.
	*
	* @return array $settings.
	*/
	public function get_settings( $current_section = '' ) {
		$settings = array();

		$settings[] = array(
			'name' => __( 'reCAPTCHA Settings', 'geodir-recaptcha' ),
			'type' => 'title',
			'desc' => '',
			'id' => 'gd_recaptcha_settings'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA Type', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Choose the type of Google reCAPTCHA. %sLearn more%s', 'geodir-recaptcha' ), '<a href="https://developers.google.com/recaptcha/docs/versions#checkbox" target="_blank" title="reCAPTCHA Type">', '</a>' ),
			'id'       => 'rc_client_version',
			'type'     => 'select',
			'options'  => array(
				'v3' => __( 'reCAPTCHA v3','geodir-recaptcha' ),
				'v2' => __( 'reCAPTCHA v2 (I\'m not a robot Checkbox)','geodir-recaptcha' ),
				'invisible' => __( 'reCAPTCHA v2 (Invisible reCAPTCHA badge)','geodir-recaptcha' )
			),
			'default'  => 'v3',
			'advanced' => false
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v2(Checkbox) Site Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v2 (Checkbox) site key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v2(Checkbox) Site Key">', '</a>' ),
			'id'       => 'rc_v2_site_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="v2"'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v2(Checkbox) Secret Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v2 (Checkbox) secret key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v2(Checkbox) Secret Key">', '</a>' ),
			'id'       => 'rc_v2_secret_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="v2"'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v2(Invisible) Site Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v2 (Invisible) site key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v2(Invisible) Site Key">', '</a>' ),
			'id'       => 'rc_invisible_site_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="invisible"'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v2(Invisible) Secret Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v2 (Invisible) secret key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v2 (Invisible) Secret Key">', '</a>' ),
			'id'       => 'rc_invisible_secret_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="invisible"'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v3 Site Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v3 site key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v3 Site Key">', '</a>' ),
			'id'       => 'rc_v3_site_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="v3"'
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA v3 Secret Key', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Google reCAPTCHA v3 secret key that you get after site registration at %shere%s', 'geodir-recaptcha' ), '<a href="https://www.google.com/recaptcha/admin#list" target="_blank" title="reCAPTCHA v3 Secret Key">', '</a>' ),
			'id'       => 'rc_v3_secret_key',
			'type'     => 'text',
			'advanced' => false,
			'element_require' => '[%rc_client_version%]=="v3"'
		);

		$settings[] = array(
			'name' => __( 'Enable reCAPTCHA in', 'geodir-recaptcha' ),
			'desc' => __( 'GeoDirectory Add Listing Form', 'geodir-recaptcha' ),
			'id'   => 'rc_add_listing',
			'type' => 'checkbox',
			'default'  => '0'
		);

		if ( defined( 'GEODIR_CLAIM_VERSION' ) ) {
			$settings[] = array(
				'name' => ' ',
				'desc' => __( 'GeoDirectory Claim Listing Form(standard)', 'geodir-recaptcha' ),
				'id'   => 'rc_claim_listing',
				'type' => 'checkbox',
				'default'  => '0'
			);
		}

		// Manage captcha form WP login & registration from UWP settings when UsersWP reCAPTCHA is active.
		if ( defined( 'UWP_RECAPTCHA_VERSION' ) ) {
			$settings[] = array(
				'name' => ' ',
				'desc' => __( 'WordPress Registration Form','geodir-recaptcha' ) . ' '. wp_sprintf( __( '( Manage WordPress Registration reCAPTCHA from %sUsersWP reCAPTCHA%s settings )','geodir-recaptcha' ), '<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=userswp&tab=uwp-addons&section=uwp_recaptcha#recaptcha_score' ) ) . '">', '</a>' ),
				'id'   => 'rc_wp_registration',
				'type' => 'checkbox',
				'default'  => '0',
				'value' => '0',
				'custom_attributes' => array(
					'disabled' => 'disabled'
				)
			);

			$settings[] = array(
				'name' => ' ',
				'desc' => __( 'WordPress Login Form', 'geodir-recaptcha' ) . ' '. wp_sprintf( __( '( Manage WordPress Login reCAPTCHA from %sUsersWP reCAPTCHA%s settings )','geodir-recaptcha' ), '<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=userswp&tab=uwp-addons&section=uwp_recaptcha#recaptcha_score' ) ) . '">', '</a>' ),
				'id'   => 'rc_wp_login',
				'type' => 'checkbox',
				'default'  => '0',
				'value' => '0',
				'custom_attributes' => array(
					'disabled' => 'disabled'
				)
			);
		} else {
			$settings[] = array(
				'name' => ' ',
				'desc' => __('WordPress Registration Form','geodir-recaptcha'),
				'id'   => 'rc_wp_registration',
				'type' => 'checkbox',
				'default'  => '0',
				//'advanced' => true,
			);

			$settings[] = array(
				'name' => ' ',
				'desc' => __( 'WordPress Login Form', 'geodir-recaptcha' ),
				'id'   => 'rc_wp_login',
				'type' => 'checkbox',
				'default'  => '0',
				//'advanced' => true,
			);
		}

		$settings[] = array(
			'name' => ' ',
			'desc' => __( 'WordPress Comments Form(includes GeoDirectory Reviews)', 'geodir-recaptcha' ),
			'id'   => 'rc_comments',
			'type' => 'checkbox',
			'default'  => '0',
			// 'advanced' => true,
		);

		if ( class_exists( 'BuddyPress' ) ) {
			$settings[] = array(
				'name' => ' ',
				'desc' => __( 'BuddyPress Registration', 'geodir-recaptcha' ),
				'id'   => 'rc_buddypress',
				'type' => 'checkbox',
				'default'  => '0',
				'advanced' => true,
			);
		}

		$users_roles = get_editable_roles();

		if ( ! empty( $users_roles ) && $users_roles !='' ) {
			$count = 0;
			foreach ( $users_roles as $role_key => $role_value ){
				$count++;
				$settings[] = array(
					'name' => ( 1 == $count ) ? __( 'Disable reCAPTCHA for', 'geodir-recaptcha' ): ' ',
					'desc' => __( $role_value['name'], 'geodir-recaptcha'),
					'id'   => 'geodir_recaptcha_role_'.$role_key,
					'type' => 'checkbox',
					'default'  => '0',
					'advanced' => true,
				);
			}
		}

		$settings[] = array(
			'name'     => __( 'reCAPTCHA Title', 'geodir-recaptcha' ),
			'desc'     => __('reCAPTCHA title to be displayed above reCAPTCHA code, leave blank to hide.','geodir-recaptcha'),
			'id'       => 'rc_title',
			'type'     => 'text',
		   // 'desc_tip' => true,
			'advanced' => false,
		);

		$settings[] = array(
			'name'     => __( 'reCAPTCHA Theme', 'geodir-recaptcha' ),
			'desc'     => sprintf( __( 'Select color theme of reCAPTCHA widget. %sLearn more%s', 'geodir-recaptcha' ), '<a href="https://developers.google.com/recaptcha/docs/display#render_param" target="_blank" title="GD Recaptcha Secret">', '</a>' ),
			'id'       => 'rc_theme',
			'type'     => 'select',
			'options'  => array(
				'light' => __( 'Light','geodir-recaptcha' ),
				'dark' => __( 'Dark','geodir-recaptcha' ),
			),
			'default'  => 'light_theme',
			//'desc_tip' => true,
			'advanced' => false,
		);

		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'gd_recaptcha_settings',
		);

		$settings = apply_filters( 'geodir_recaptcha_options',$settings );

		return apply_filters( 'geodir_get_settings_' . $this->id, $settings );
	}

	/**
	* Display GD Recaptcha output.
	*
	* @since 2.0.0
	*
	* @global type $current_section Get current settings section.
	*/
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::output_fields( $settings );
	}

	/**
	* Save GD Recaptcha fields options.
	*
	* @since 2.0.0
	*/
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::save_fields( $settings );
	}
}

new GD_Recaptcha_Admin_Settings();