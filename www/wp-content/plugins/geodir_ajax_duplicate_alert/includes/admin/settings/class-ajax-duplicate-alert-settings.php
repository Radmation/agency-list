<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.2.0
 * @package    GD_Duplicate_Alert
 * @subpackage GD_Duplicate_Alert/admin/settings
 *
 * Class GD_Duplicate_Alert_Settings
 */

class GD_Duplicate_Alert_Settings extends GeoDir_Settings_Page {

	public $id;

	public $title;

	public $post_type;

	/**
	 * Constructor.
	 *
	 * @since 1.2.1
	 *
	 * GD_Duplicate_Alert_Settings constructor.
	 */
	public function __construct() {
		$this->id = 'cpt_duplicate_alert';
		$this->title = __( 'Duplicate Alert', 'geodir-duplicate-alert' );
		$this->post_type = ! empty( $_REQUEST['post_type'] ) ? esc_attr( $_REQUEST['post_type'] ) : '';

		add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ),99 );
		//add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );
		add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_filter( 'geodir_get_settings_'.$this->id , array( $this, 'set_fields_value' ),10,1 );
	}

	/**
	 * Add GD duplicate alert settings tab.
	 *
	 * Add setting tab in each gd custom post type setting page.
	 *
	 * @since 1.2.1
	 *
	 * @param array $pages GD cpt settings page tab page array.
	 * @return array $pages.
	 */
	public function add_settings_page($pages) {
		$gd_posttypes = geodir_get_posttypes();

		$current_posttype = $this->post_type;

		if( in_array( $current_posttype,$gd_posttypes ) ) {
			$pages[ $this->id ] = $this->title;
		}

		return $pages;
	}

	/**
	 * Display GD duplicate alert output.
	 *
	 * @since 1.2.1
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Get GD duplicate alert settings array.
	 *
	 * @since 1.2.1
	 *
	 * @param string $current_section Optional. GD custom post type current section. Default null.
	 * @return array $settings.
	 */
	public function get_settings( $current_section = '' ) {
		$post_type = $this->post_type;
		$post_type_name = geodir_post_type_singular_name($this->post_type);

		$settings  = apply_filters( "gd_duplicate_alert_settings_{$post_type}", array(

			array(
				'name' => __( 'Duplicate Alert', 'geodir-duplicate-alert' ),
				'type' => 'title',
				'desc' => __( 'Show alert when duplicate value is entered in below fields for this post type on add new listing page.','geodir-duplicate-alert' ),
				'id'   => 'duplicate_alert_settings'
			),

			array(
				'name' => __( 'Fields to check', 'geodir-duplicate-alert' ),
				'type' => 'multiselect',
				'options' => array(
						'post_title' => __( 'Post Title','geodir-duplicate-alert' ),
						'post_address' => __( 'Address','geodir-duplicate-alert' ),
						'post_zip' => __( 'Zip/Post Code','geodir-duplicate-alert' ),
						'geodir_contact' => __( 'Phone','geodir-duplicate-alert' ),
						'geodir_email' => __( 'Email','geodir-duplicate-alert' ),
						'geodir_website' => __( 'Website','geodir-duplicate-alert' ),
				),
				'desc' => __( 'Select one or more fields to check duplicate value.','geodir-duplicate-alert' ),
				'placeholder' => __( 'Please select fields','geodir-duplicate-alert' ),
				'default' => array('post_title'),
				'id'   => 'duplicate_alert_fields',
				'class'      => 'geodir-select',
				'desc_tip' => true,
				'advanced' => false,
			),

			array(
				'id' => 'duplicate_alert_skip_fields',
				'type' => 'multiselect',
				'name' => __( 'Submit Form Even When Dulicate Found For', 'geodir-duplicate-alert' ),
				'options' => array(
						'post_title' => __( 'Post Title','geodir-duplicate-alert' ),
						'post_address' => __( 'Address','geodir-duplicate-alert' ),
						'post_zip' => __( 'Zip/Post Code','geodir-duplicate-alert' ),
						'geodir_contact' => __( 'Phone','geodir-duplicate-alert' ),
						'geodir_email' => __( 'Email','geodir-duplicate-alert' ),
						'geodir_website' => __( 'Website','geodir-duplicate-alert' ),
				),
				'desc' => __( 'Allow form submit even when duplicate record found for the selected fields.','geodir-duplicate-alert' ),
				'placeholder' => __( 'Select Fields', 'geodir-duplicate-alert' ),
				'default' => '',
				'class' => 'geodir-select',
				'desc_tip' => true,
				'advanced' => false,
			),

			array(
				'name'     => __( 'Validation message', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Default duplicate value error message for all fields.', 'geodir-duplicate-alert' ),
				'id'       => 'duplicate_alert_validation_message',
				'type'     => 'text',
				'default'  => '',
				'placeholder' => GD_Duplicate_Alert_Defaults::duplicate_alert_validation_message(),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => false
			),

			array(
				'name'     => __( 'Post title validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add post title field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_post_title',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter post title validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),

			array(
				'name'     => __( 'Address validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add address field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_post_address',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter address validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),

			array(
				'name'     => __( 'Zip/Post code validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add zip/postal code field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_post_zip',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter Zip/Post code validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),

			array(
				'name'     => __( 'Phone validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add phone field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_geodir_contact',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter phone validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),

			array(
				'name'     => __( 'Email validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add email field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_geodir_email',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter email validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),
			
			array(
				'name'     => __( 'Website validation error', 'geodir-duplicate-alert' ),
				'desc'     => __( 'Add Website field duplication validation message', 'geodir-duplicate-alert' ),
				'id'       => 'alert_message_geodir_website',
				'type'     => 'text',
				'default' =>'',
				'placeholder' => __( 'Please enter Website validation message' ,'geodir-duplicate-alert' ),
				'class'    => 'large-text',
				'desc_tip' => true,
				'advanced' => true
			),

			array( 'type' => 'sectionend', 'id' => 'gd_duplicate_alert_settings' ),

		));

		return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Save GD duplicate alert fields options.
	 *
	 * @since 1.2.1
	 */
	public function save() {
		global $current_section;

		$gd_duplicte_alert_val = $this->sanitize_fields( $_POST );

		$post_types = geodir_get_option("duplicate_alert", array());

		if(empty($post_types)){

			$post_types = $gd_duplicte_alert_val;

		}else{

			$post_types = array_merge($post_types,$gd_duplicte_alert_val);

		}

		geodir_update_option( "duplicate_alert", $post_types );
	}

	/**
	 * Set GD duplicate alert values.
	 *
	 * @since 1.2.1
	 *
	 * @param array $settings Get GD duplicate alert options array.
	 * @return array $settings.
	 */
	public function set_fields_value( $settings ) {
		$get_posttype = $this->post_type;

		if( $get_posttype ) {

			$cpt_duplicate_alert = geodir_get_option('duplicate_alert', array());

			if( isset( $cpt_duplicate_alert[$get_posttype] ) ) {

				$cpt_alert_arr = $cpt_duplicate_alert[$get_posttype];

				foreach($settings as $key => $setting){

					if( isset( $setting['id'] ) ) {

						$settings[$key]['default'] =  !empty( $cpt_alert_arr[$setting['id']] ) ? $cpt_alert_arr[$setting['id']] :'' ;

					}

				}

			}

		}

		return $settings;
	}

	/**
	 * Sanatize GD duplicate alert fields array.
	 *
	 * @since 1.2.1
	 *
	 * @param array $post Get GD duplicate alert post data.
	 * @return array $output
	 */
	public function sanitize_fields( $post ) {
		$post_type = $this->post_type;

		if ( empty( $post ) ) {
			return;
		}

		$output = array();
		$output[ $post_type ] = array(
			'duplicate_alert_fields' => ! empty( $_POST['duplicate_alert_fields'] ) ? $_POST['duplicate_alert_fields'] : '',
			'duplicate_alert_skip_fields' => ! empty( $_POST['duplicate_alert_skip_fields'] ) ? $_POST['duplicate_alert_skip_fields'] : '',
			'duplicate_alert_validation_message' => ! empty( $_POST['duplicate_alert_validation_message'] ) ? sanitize_text_field( stripslashes( $_POST['duplicate_alert_validation_message'] ) ) : '',
			'alert_message_post_title' => ! empty( $_POST['alert_message_post_title'] ) ? sanitize_text_field( stripslashes( $_POST['alert_message_post_title'] ) ) : '',
			'alert_message_post_address' => ! empty( $_POST['alert_message_post_address'] ) ? sanitize_text_field( stripslashes( $_POST['alert_message_post_address'] ) ) : '',
			'alert_message_post_zip' => ! empty( $_POST['alert_message_post_zip'] ) ? sanitize_text_field( stripslashes( $_POST['alert_message_post_zip'] ) ) : '',
			'alert_message_geodir_contact' => ! empty( $_POST['alert_message_geodir_contact'] ) ? sanitize_text_field( stripslashes( $_POST['alert_message_geodir_contact'] ) ) : '',
			'alert_message_geodir_email' => ! empty( $_POST['alert_message_geodir_email'] ) ? sanitize_text_field( stripslashes( $_POST['alert_message_geodir_email'] ) ) : '',
		);

		return $output;
	}
}

new GD_Duplicate_Alert_Settings();