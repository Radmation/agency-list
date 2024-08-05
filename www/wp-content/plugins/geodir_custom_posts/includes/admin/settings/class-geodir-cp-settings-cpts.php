<?php
/**
 * GeoDirectory Custom Post Types Settings
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDir_Custom_Posts/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GeoDir_CP_Settings_CPTs', false ) ) :

	/**
	 * GeoDir_CP_Settings_CPTs.
	 */
	class GeoDir_CP_Settings_CPTs extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'cpts';
			$this->label = __( 'Post Types', 'geodir_custom_posts' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );


			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
			
			// List post types
			add_action( 'geodir_admin_field_post_types_page', array( $this, 'post_types_page' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'cpt'			=> __( 'Post Types', 'geodir_custom_posts' ),
				//'add-cpt'		=> __( 'Add Post Type', 'geodir_custom_posts' ),
				//'options'  		=> __( 'Settings', 'geodir_custom_posts' )
			);

			if(isset($_REQUEST['section']) && $_REQUEST['section']=='add-cpt'){
				$sections['add-cpt'] = __( 'Add Post Type', 'geodir_custom_posts' );
			}
			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			if ( $current_section == 'add-cpt' ) {
				$_REQUEST['post_type'] = '_gd_new_cpt';
				$_REQUEST['tab'] = 'cpt';

				$settings_cpt = include( GEODIRECTORY_PLUGIN_DIR . 'includes/admin/settings/class-geodir-settings-cpt.php' );
				$settings = $settings_cpt->get_settings( $current_section );
				foreach ( $settings as $key => $setting ) {
					if ( ! empty( $setting['id'] ) && $setting['id'] == 'post_type' ) {
						$settings[ $key ]['id'] = 'new_post_type';
						$settings[ $key ]['value'] = '';
					}
				}
			}

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			if ( $current_section == 'add-cpt' ) {
				$cpt = GeoDir_Settings_Cpt::sanatize_post_type( $_POST );

				$settings = $this->get_settings( $current_section );
				if ( is_wp_error( $cpt ) ) {
					GeoDir_Admin_Settings::add_error( $cpt->get_error_message() );
					return;
				}
				$current_post_types = geodir_get_option( 'post_types', array() );
				if ( empty( $current_post_types ) ) {
					$post_types = $cpt;
				} else {
					$post_types = array_merge( $current_post_types, $cpt );
				}

				foreach ( $cpt as $post_type => $args ) {
					$cpt_before = ! empty( $current_post_types[ $post_type ] ) ? $current_post_types[ $post_type ] : array();

					/**
					 * Fires before post type updated.
					*
					 * @since 2.0.1.0
					 *
					 * @param string $post_type Post type.
					 * @param array $args Post type array.
					 * @param array $cpt_before Post type array before update.
					 */
					do_action( 'geodir_pre_save_post_type', $post_type, $args, $cpt_before );
				}

				// Update custom post types
				geodir_update_option( 'post_types', $post_types );
				
				// create tables if needed
				GeoDir_Admin_Install::create_tables();

				foreach ( $cpt as $post_type => $args ) {
					do_action( 'geodir_post_type_saved', $post_type, $args, true );
				}

				$post_types = geodir_get_option( 'post_types', array() );

				foreach ( $cpt as $post_type => $args ) {
					$cpt_before = ! empty( $current_post_types[ $post_type ] ) ? $current_post_types[ $post_type ] : array();
					$cpt_after = ! empty( $post_types[ $post_type ] ) ? $post_types[ $post_type ] : array();

					/**
					 * Fires after post type updated.
					 *
					 * @since 2.0.0.71
					 *
					 * @param string $post_type Post type.
					 * @param array $cpt_after Post type array after update.
					 * @param array $cpt_before Post type array before update.
					 */
					do_action( 'geodir_post_type_updated', $post_type, $cpt_after, $cpt_before );
				}

				// flush rewrite rules
				flush_rewrite_rules();
				do_action( 'geodir_flush_rewrite_rules' );
				wp_schedule_single_event( time(), 'geodir_flush_rewrite_rules' );

				GeoDir_Admin_Settings::add_message( __( 'Post type created successfully.', 'geodir_custom_posts' ) );
				wp_redirect( admin_url( 'edit.php?page='.$post_type.'-settings&tab=cpt&post_type=' . $post_type ) );
				geodir_die();
			} else {
				$settings = $this->get_settings( $current_section );

				GeoDir_Admin_Settings::save_fields( $settings );
			}
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

			if ( 'options' == $current_section ) {
				$settings = apply_filters( 'geodir_cp_settings_options', 
					array(
						array( 
							'name' => __( 'Settings', 'geodir_custom_posts' ), 
							'type' => 'title',
							'desc' => '', 
							'id' => 'geodir_cp_section_options' 
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_cp_section_options'
						)
					)
				);
			} else if ( 'add-cpt' == $current_section ) {
				$settings = apply_filters( 'geodir_cp_settings_add_cpt', 
					array(
						array( 
							'name' => __( 'Add Post Type', 'geodir_custom_posts' ), 
							'type' => 'title',
							'desc' => '', 
							'id' => 'geodir_cp_section_add_cpt',
						),
						array( 
							'name' => '', 
							'type' => 'add_cpt_page', 
							'desc' => '', 
							'id' => 'geodir_cp_add_cpt_page_settings',
							'advanced'=> true
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_cp_section_add_cpt'
						)
					)
				);
			} else {
				$settings = apply_filters( 'geodir_cp_settings_post_types', 
					array(
						array(
							'name' => __( 'Post Types', 'geodir_custom_posts' ) ,
							'type' => 'page-title',
							'desc' => '',
							'id' => 'geodir_cp_section_post_types',
							'title_html' => ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=cpts&section=add-cpt' ) ) . '" class="add-new-h2">' . __( 'Add New', 'geodir_custom_posts' ) . '</a></h2>'
						),
						
						array(
							'name' => 'dd',
							'type' => 'post_types_page',
							'desc' => '',
							'id' => 'geodir_cp_post_types_page_settings'
						),

					)
				);
			}

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}
		
		/**
		 * Form method.
		 *
		 * @param  string $method
		 *
		 * @return string
		 */
		public function form_method( $method ) {
			global $current_section;

			return 'post';
		}
		
		public static function post_types_page( $option ) {
			// Hide the save button
			$GLOBALS['hide_save_button'] = true;

			GeoDir_Admin_Settings::show_messages();


			$post_types_list_table = new GeoDir_CP_Admin_Post_Types_List_Table();
			$post_types_list_table->prepare_items();
			$post_types_list_table->display();
		}

		public static function add_cpt_page( $option ) {
			// Hide the save button
			$GLOBALS['hide_save_button'] = true;

			GeoDir_Admin_Settings::show_messages();

			echo '<div class="geodir-table-list geodir-add-cpt-form">';
			echo '<input type="hidden" name="page" value="gd-settings" />';
			echo '<input type="hidden" name="tab" value="cpts" />';
			echo '<input type="hidden" name="section" value="add-cpt" />';

			echo '</div>';
		}
	}

endif;

return new GeoDir_CP_Settings_CPTs();
