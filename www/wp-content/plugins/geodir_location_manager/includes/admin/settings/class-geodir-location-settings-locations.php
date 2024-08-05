<?php
/**
 * GeoDirectory Location Manager Settings
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDir_Location_Manager/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GeoDir_Location_Settings_Locations', false ) ) :

	/**
	 * GeoDir_Location_Settings_Locations.
	 */
	class GeoDir_Location_Settings_Locations extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'locations';
			$this->label = __( 'Locations', 'geodirlocation' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			// WordPress XML Sitemaps settings
			add_filter( 'geodir_locations_options', array( $this, 'wp_sitemaps_settings' ) );

			// Yoast SEO / Rank Math XML sitemap settings
			add_filter( 'geodir_locations_options', array( $this, 'xml_sitemap_options' ) );

			// Add/edit location
			add_action( 'geodir_admin_field_add_location', array( $this, 'add_location' ) );

			// Countries
			add_action( 'geodir_admin_field_countries_page', array( $this, 'countries_page' ) );

			// Regions
			add_action( 'geodir_admin_field_regions_page', array( $this, 'regions_page' ) );

			// Cities
			add_action( 'geodir_admin_field_cities_page', array( $this, 'cities_page' ) );

			// Neighbourhoods
			add_action( 'geodir_admin_field_neighbourhoods_page', array( $this, 'neighbourhoods_page' ) );

			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );

			// Location filter
			add_action( 'geodir_location_restrict_manage_locations', array( $this, 'locations_filter_actions' ), 10, 2 );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				''					=> __( 'Settings', 'geodirlocation' ),
				'add_location'  	=> __( 'Add Location', 'geodirlocation' ),
				'countries'  		=> __( 'Countries', 'geodirlocation' ),
				'regions' 			=> __( 'Regions', 'geodirlocation' ),
				'cities' 			=> __( 'Cities', 'geodirlocation' )
			);
			if ( GeoDir_Location_Neighbourhood::is_active() ) {
				$sections['neighbourhoods'] = __( 'Neighbourhoods', 'geodirlocation' );
			}

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			if ( 'add_location' == $current_section ) {
				$tab 			= ! empty( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
				$location_id 	= isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
				$in_new 		= $tab == 'locations' && empty( $location_id ) ? true : false;
				$location 		= self::get_location_data( $location_id );
				$no_editor		= geodir_get_option( 'lm_desc_no_editor' );

				if ( ! empty( $no_editor ) ) {
					$wysiwyg = false;
				} else {
					$wysiwyg = array( 'quicktags' => true );
				}

				if ( empty( $location['location_id'] ) ) {
					$title = __( 'Add Location', 'geodirlocation' );
				} else {
					$title =  __( 'Edit Location:', 'geodirlocation' ) . ' #' . $location['location_id'];
				}

				$default_country = geodir_get_option('default_location_country');
				if ( empty( $default_country ) ) {
					$default_country = 'United States';
				}

				$settings = array(
						array(
							'name' => $title,
							'type' => 'title',
							'desc'  => empty($location['location_id']) ? aui()->alert( array(
									'type'=> 'info',
									'content'=> __( 'Locations are automatically added when a user adds a listing, it is not required to manually add them.', 'geodirlocation' )
								)
							) : '',
							'id' => 'geodir_location_add_location_settings',
							'advanced' => false
						),

					array(
						'id'       => 'location_id',
						'type'     => 'hidden',
						'value'    => isset($location['location_id']) ? $location['location_id'] : '',
					),
					array(
						'id'       => 'geodir_save_location_nonce',
						'type'     => 'hidden',
						'value'    => wp_create_nonce( 'geodir-save-location' ),
					),
					array(
						'id'       => 'security',
						'type'     => 'hidden',
						'value'    => wp_create_nonce( 'geodir-save-location' ),
					),
					array(
						'name'     => __( 'City', 'geodirlocation' ),
						'desc'     => __( 'The default location city name.', 'geodirlocation' ),
						'id'       => 'location_city',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'default'  => 'Philadelphia',
						'value'    => !empty($location['city']) ? $location['city'] : (  $in_new ? '' : 'Philadelphia' ),
						'advanced' => true
					),
					array(
						'name'     => __( 'Region', 'geodirlocation' ),
						'desc'     => __( 'The default location region name.', 'geodirlocation' ),
						'id'       => 'location_region',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'default'  => 'Pennsylvania',
						'value'    => !empty($location['region']) ? $location['region'] : (  $in_new ? '' : 'Pennsylvania' ),
						'advanced' => true
					),
					array(
						'name'     => __( 'Country', 'geodirlocation' ),
						'desc'     => __( 'The default location country name.', 'geodirlocation' ),
						'id'       => 'location_country',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'type'     => 'single_select_country',
						'default'  => $default_country,
						'value'    => ( ! empty( $location['country'] ) ? $location['country'] : $default_country ),
						'options'  => geodir_get_countries()

					),
					array(
						'name'     => __( 'City Latitude', 'geodirlocation' ),
						'desc'     => __( 'The latitude of the default location.', 'geodirlocation' ),
						'id'       => 'location_latitude',
						'type' => 'number',
						'custom_attributes' => array(
							'min'           => '-90',
							'max'           => '90',
							'step'          => 'any',
						),
						'desc_tip' => true,
						'default'  => '39.9523894183957',
						'value'    => !empty($location['latitude']) ? $location['latitude'] : (  $in_new ? '' : '39.9523894183957' ),
						'advanced' => true
					),

					array(
						'name'     => __( 'City Longitude', 'geodirlocation' ),
						'desc'     => __( 'The longitude of the default location.', 'geodirlocation' ),
						'id'       => 'location_longitude',
						'type' => 'number',
						'custom_attributes' => array(
							'min'           => '-180',
							'max'           => '180',
							'step'          => 'any',
						),
						'desc_tip' => true,
						'default'  => '-75.16359824536897',
						'value'    => !empty($location['longitude']) ? $location['longitude'] : (  $in_new ? '' : '-75.16359824536897' ),
						'advanced' => true
					),
					array(
						'name'     => __( 'Timezone', 'geodirlocation' ),
						'desc'     => __( 'Select a city/timezone.', 'geodirlocation' ),
						'id'       => 'default_location_timezone_string',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'type'     => ! empty( $location['is_default'] ) ? 'single_select_timezone' : 'none',
						'default'  => geodir_timezone_string(),
						'options'  => array()
					),
					array(
						'type' => 'add_location',
						'desc' => '',
						'id' => 'geodir_location_add_location_settings',
						'advanced' => false
					),
					array(
						'name'     => __( 'Meta Title', 'geodirlocation' ),
						'desc'     => __( 'The meta title.', 'geodirlocation' ),
						'id'       => 'location_meta_title',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'value'    => isset($location['meta_title']) ? $location['meta_title'] : '',
					),
					array(
						'name'     => __( 'Meta Description', 'geodirlocation' ),
						'desc'     => __( 'The meta description.', 'geodirlocation' ),
						'id'       => 'location_meta_description',
						'type'     => 'textarea',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'value'    => isset($location['meta_description']) ? $location['meta_description'] : '',
					),
					array(
						'name'     => __( 'Location Description', 'geodirlocation' ),
						'desc'     => __( 'The location description.', 'geodirlocation' ),
						'id'       => 'location_description',
						'type'     => 'textarea',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'wysiwyg'  => $wysiwyg,
						'value'    => isset($location['description']) ? $location['description'] : '',
					),
					array(
						'name' => __('Featured Image', 'geodirlocation'),
						'desc' => __('This is implemented by some themes to show a location specific image.', 'geodirlocation'),
						'id' => 'location_image',
						'type' => 'image',
						'default' => 0,
						'desc_tip' => true,
						'value'    => isset($location['image']) ? $location['image'] : '0',
						'advanced' => true,
					),
					array(
						'name'     => __( 'Image Tagline', 'geodirlocation' ),
						'desc'     => __( 'The location image tagline.', 'geodirlocation' ),
						'id'       => 'location_image_tagline',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'value'    => isset($location['image_tagline']) ? $location['image_tagline'] : '',
					),
						

					);

				$post_types = geodir_get_posttypes();
				foreach ( $post_types as $post_type ) {
					if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
						continue;
					}

					$id = 'location_cpt_description_' . $post_type;
					$name = 'location_cpt_description[' . $post_type .']';
					$post_type_name = geodir_post_type_name( $post_type, true );
					$_cpt_desc = ! empty( $location['cpt_desc'] ) && isset( $location['cpt_desc'][ $post_type ] ) ? $location['cpt_desc'][ $post_type ] : '';

					$_wysiwyg = $wysiwyg;

					if ( ! empty( $_wysiwyg ) ) {
						$_wysiwyg['textarea_name'] = $name;
						$_wysiwyg['wpautop'] = false; // Prevent removing <p> tags.
						$_wysiwyg = apply_filters( 'geodir_location_cpt_desc_editor_settings', $_wysiwyg, $id, $name );
					}

					$settings[] = array(
						'title'    => wp_sprintf( __( '%s Description', 'geodirlocation' ), $post_type_name ),
						'desc'     => wp_sprintf( __( '%s description to show for this city.', 'geodirlocation' ), $post_type_name ),
						'id'       => $name,
						'name'     => $name,
						'type'     => 'textarea',
						'css'      => 'min-width:300px;',
						'desc_tip' => true,
						'advanced' => true,
						'wysiwyg'  => $_wysiwyg,
						'value'    => $_cpt_desc,
					);
				}

				$settings[] = array(
					'type' => 'sectionend',
					'id' => 'geodir_location_add_location_settings'
				);

				$settings = apply_filters( 'geodir_location_add_location_options', $settings );
			} elseif ( 'countries' == $current_section ) {
				$settings = apply_filters( 'geodir_location_countries_page_options', 
					array(

						array(
							'name' => __( 'Countries', 'geodirlocation' ),
							'type' => 'page-title',
							'desc' => '',
							'id' => 'geodir_location_countries_page_settings',
						),

						array( 
							'name' => __( 'Countries', 'geodirlocation' ), 
							'type' => 'countries_page', 
							'desc' => '', 
							'id' => 'geodir_location_countries_page_settings',
						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'geodir_location_countries_page_settings'
//						)
					)
				);
			} elseif ( 'regions' == $current_section ) {
				$settings = apply_filters( 'geodir_location_regions_page_options', 
					array(
						array( 
							'name' => __( 'Regions', 'geodirlocation' ), 
							'type' => 'page-title',
							'desc' => '', 
							'id' => 'geodir_location_regions_page_settings' 
						),
						array(
							'name' => __( 'Regions', 'geodirlocation' ),
							'type' => 'regions_page',
							'desc' => '',
							'id' => 'geodir_location_regions_page_settings'
						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'geodir_location_regions_page_settings'
//						)
					)
				);
			} elseif ( 'cities' == $current_section ) {
				$settings = apply_filters( 'geodir_location_cities_page_options', 
					array(
						array( 
							'title_html' => __( 'Cities', 'geodirlocation' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=add_location' ) ) . '" class="add-new-h2">' . __( 'Add New', 'geodirlocation' ) . '</a>',
							'type' => 'page-title',
							'desc' => '', 
							'id' => 'geodir_location_cities_page_settings' 
						),
						array(
							'name' => __( 'Cities', 'geodirlocation' ),
							'type' => 'cities_page',
							'desc' => '',
							'id' => 'geodir_location_cities_page_settings'
						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'geodir_location_cities_page_settings'
//						)
					)
				);
			} elseif ( 'neighbourhoods' == $current_section ) {
				if ( isset( $_REQUEST['add_neighbourhood'] ) ) {
					$no_editor		= geodir_get_option( 'lm_desc_no_editor' );

					if ( ! empty( $no_editor ) ) {
						$wysiwyg = false;
					} else {
						$wysiwyg = array( 'quicktags' => true );
					}

					$neighbourhood_id 	= isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
					$location_id 		= isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
					$neighbourhood 		= GeoDir_Location_Admin_Neighbourhoods::get_data( $neighbourhood_id );
					if ( empty( $location_id ) && ! empty( $neighbourhood->location_id ) ) {
						$location_id = $neighbourhood->location_id;
					}
					$location = geodir_get_location_by_id( '' , $location_id );
					$add_link = '';

					$title = empty( $neighbourhood->id ) ? __( 'Add neighbourhood', 'geodirlocation' ) : __( 'Edit neighbourhood:', 'geodirlocation' ) . ' #' . absint( $neighbourhood->id );
					$settings = array(
						array(
							'name' => $title . $add_link,
							'type' => 'title',
							'desc' => '',
							'id' => 'geodir_location_neighbourhoods_page_settings'
						),

						array(
							'id'       => 'neighbourhood_id',
							'type'     => 'hidden',
							'value'    => isset( $neighbourhood->id ) ? $neighbourhood->id : '',
						),
						array(
							'id'       => 'neighbourhood_location_id',
							'type'     => 'hidden',
							'value'    => isset( $location_id ) ? $location_id : '',
						),
						array(
							'id'       => 'geodir_save_neighbourhood_nonce',
							'type'     => 'hidden',
							'value'    => wp_create_nonce( 'geodir-save-neighbourhood' ),
						),
						array(
							'id'       => 'security',
							'type'     => 'hidden',
							'value'    => wp_create_nonce( 'geodir-save-neighbourhood' ),
						),

						array(
							'name'     => __( 'Slug', 'geodirlocation' ),
							'desc'     => __( 'The URL slug', 'geodirlocation' ),
							'id'       => 'neighbourhood_slug',
							'type'     => ( ! empty( $neighbourhood->id ) ? 'text' : 'hidden' ),
							'class'    => 'disable disabled',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'value'    => ! empty( $neighbourhood->slug ) ? $neighbourhood->slug : '',
							'custom_attributes' => array(
								'disabled'  => true
							)
						),
						array(
							'name'     => __( 'Neighbourhood', 'geodirlocation' ),
							'desc'     => __( 'The neighbourhood name.', 'geodirlocation' ),
							'id'       => 'neighbourhood_name',
							'type'     => 'text',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'value'    => ! empty( $neighbourhood->neighbourhood ) ? $neighbourhood->neighbourhood : '',
						),
						array(
							'name'     => __( 'City', 'geodirlocation' ) . ( $location_id ? ' ' . wp_sprintf( __( '( Location ID: %d )', 'geodirlocation' ), $location_id ) : '' ),
							'id'       => 'neighbourhood_city',
							'type'     => 'text',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'value'    => ! empty( $neighbourhood->city ) ? $neighbourhood->city : ( ! empty( $location->city ) ? $location->city : '' ),
							'advanced' => true,
							'custom_attributes' => array(
								'disabled' => true
							)
						),
						array(
							'name'     => __( 'Region', 'geodirlocation' ),
							'id'       => 'neighbourhood_region',
							'type'     => 'text',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'value'    => ! empty( $neighbourhood->region ) ? $neighbourhood->region : ( ! empty( $location->region ) ? $location->region : '' ),
							'advanced' => true,
							'custom_attributes' => array(
								'disabled' => true
							)
						),
						array(
							'name'     => __( 'Country', 'geodirlocation' ),
							'id'       => 'neighbourhood_country',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'advanced' => true,
							'type'     => 'single_select_country',
							'class'    => 'geodir-select',
							'default'  => 'United States',
							'value'    => ! empty( $neighbourhood->country ) ? $neighbourhood->country : ( ! empty( $location->country ) ? $location->country : 'United States' ),
							'options'  => geodir_get_countries(),
							'custom_attributes' => array(
								'disabled' => true
							)
						),
						array(
							'name'     => __( 'Latitude', 'geodirlocation' ),
							'desc'     => __( 'The latitude of the location.', 'geodirlocation' ),
							'id'       => 'neighbourhood_latitude',
							'type' => 'number',
							'custom_attributes' => array(
								'min'           => '-90',
								'max'           => '90',
								'step'          => 'any',
							),
							'desc_tip' => true,
							'default'  => ( ! empty( $location->latitude ) ? $location->latitude : '-75.16359824536897' ),
							'value'    => ! empty( $neighbourhood->latitude ) ? $neighbourhood->latitude : ( ! empty( $location->latitude ) ? $location->latitude : '-75.16359824536897' ),
							'advanced' => true
						),

						array(
							'name'     => __( 'Longitude', 'geodirlocation' ),
							'desc'     => __( 'The longitude of the location.', 'geodirlocation' ),
							'id'       => 'neighbourhood_longitude',
							'type' => 'number',
							'custom_attributes' => array(
								'min'           => '-180',
								'max'           => '180',
								'step'          => 'any',
							),
							'desc_tip' => true,
							'default'  => ( ! empty( $location->longitude ) ? $location->longitude : '-75.16359824536897' ),
							'value'    => ! empty( $neighbourhood->longitude ) ? $neighbourhood->longitude : ( ! empty( $location->longitude ) ? $location->longitude : '-75.16359824536897' ),
							'advanced' => true
						),
						array(
							'title_html' => __( 'Neighbourhoods', 'geodirlocation' ) . $add_link,
							'type' => 'neighbourhoods_page',
							'desc' => '',
							'id' => 'geodir_location_neighbourhoods_page_settings'
						),
						array(
							'name'     => __( 'Meta Title', 'geodirlocation' ),
							'desc'     => __( 'The meta title.', 'geodirlocation' ),
							'id'       => 'neighbourhood_meta_title',
							'type'     => 'text',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'advanced' => true,
							'value'    => isset($neighbourhood->meta_title) ? $neighbourhood->meta_title : '',
						),
						array(
							'name'     => __( 'Meta Description', 'geodirlocation' ),
							'desc'     => __( 'The meta description.', 'geodirlocation' ),
							'id'       => 'neighbourhood_meta_description',
							'type'     => 'textarea',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'advanced' => true,
							'value'    => isset($neighbourhood->meta_description) ? $neighbourhood->meta_description : '',
						),
						array(
							'name'     => __( 'Location Description', 'geodirlocation' ),
							'desc'     => __( 'The location description.', 'geodirlocation' ),
							'id'       => 'neighbourhood_description',
							'type'     => 'textarea',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'advanced' => true,
							'wysiwyg'  => $wysiwyg,
							'value'    => isset($neighbourhood->description) ? $neighbourhood->description : '',
						),
						array(
							'name' => __('Featured Image', 'geodirlocation'),
							'desc' => __('This is implemented by some themes to show a location specific image.', 'geodirlocation'),
							'id' => 'neighbourhood_image',
							'type' => 'image',
							'default' => 0,
							'desc_tip' => true,
							'value'    => isset($neighbourhood->image) ? $neighbourhood->image : '0',
							'advanced' => true,
						),

					);

					$post_types = geodir_get_posttypes();
					foreach ( $post_types as $post_type ) {
						if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
							continue;
						}

						$id = 'neighbourhood_cpt_description_' . $post_type;
						$name = 'neighbourhood_cpt_description[' . $post_type .']';
						$post_type_name = geodir_post_type_name( $post_type, true );
						$_cpt_desc = ! empty( $neighbourhood->cpt_desc ) && isset( $neighbourhood->cpt_desc[ $post_type ] ) ? $neighbourhood->cpt_desc[ $post_type ]: '';

						$_wysiwyg = $wysiwyg;

						if ( ! empty( $_wysiwyg ) ) {
							$_wysiwyg['textarea_name'] = $name;
							$_wysiwyg['wpautop'] = false; // Prevent removing <p> tags.
							$_wysiwyg = apply_filters( 'geodir_location_cpt_desc_editor_settings', $_wysiwyg, $id, $name );
						}

						$settings[] = array(
							'title'    => wp_sprintf( __( '%s Description', 'geodirlocation' ), $post_type_name ),
							'desc'     => wp_sprintf( __( '%s description to show for this neighbourhood.', 'geodirlocation' ), $post_type_name ),
							'id'       => $name,
							'name'     => $name,
							'type'     => 'textarea',
							'css'      => 'min-width:300px;',
							'desc_tip' => true,
							'advanced' => true,
							'wysiwyg'  => $_wysiwyg,
							'value'    => $_cpt_desc,
						);
					}

					$settings[] = array(
						'type' => 'sectionend',
						'id' => 'geodir_location_neighbourhoods_page_settings'
					);
				}else{
					$settings = array(
						array(
							'title_html' => __( 'Neighbourhoods', 'geodirlocation' ) ,
							'type' => 'neighbourhoods_page',
							'desc' => '',
							'id' => 'geodir_location_neighbourhoods_page_settings'
						),
					);
				}


				$settings = apply_filters( 'geodir_location_neighbourhoods_page_options', $settings );
			} else {
				$selected_regions = geodir_get_option( 'lm_selected_regions' );
				$selected_cities = geodir_get_option( 'lm_selected_cities' );
				if ( ! empty( $selected_regions ) && is_array( $selected_regions ) ) {
					$selected_regions = array_combine( $selected_regions, $selected_regions );
				} else {
					$selected_regions = array();
				}
				if ( ! empty( $selected_cities ) && is_array( $selected_cities ) ) {
					$selected_cities = array_combine( $selected_cities, $selected_cities );
				} else {
					$selected_cities = array();
				}
				$settings = apply_filters( 'geodir_locations_options', 
					array(
						array( 
							'name' => __( 'URL Settings', 'geodirlocation' ),
							'type' => 'title', 
							'desc' => '', 
							'id' => 'geodir_location_home_url_settings' 
						),
						array(
							'type'       => 'radio',
							'id'         => 'lm_home_go_to',
							'name'       => __( 'Home page should go to', 'geodirlocation' ),
							'desc'       => '',
							'default'    => 'root',
							'options'    => array(
								'root' => __('Site root (ex: mysite.com/)', 'geodirlocation'),
								'location' => __('Current location page (ex: mysite.com/location/glasgow/)', 'geodirlocation')
							),
							'desc_tip'   => false,
							'advanced' 	 => false
						),
						array(
							'type'       => 'radio',
							'id'         => 'lm_url_filter_archives',
							'name'       => __( 'Archive urls', 'geodirlocation' ),
							'desc'       => '',
							'default'    => '',
							'options'    => array(
								'' => __('Add current url location to the archive page urls', 'geodirlocation'),
								'disable' => __('Disable', 'geodirlocation')
							),
							'desc_tip'   => false,
							'advanced' 	 => true
						),
						array(
							'type'       => 'radio',
							'id'         => 'lm_url_filter_archives_on_single',
							'name'       => __( 'Archive urls on details page', 'geodirlocation' ),
							'desc'       => __('The details page is unique as its url can contain partial locations or none at all so it must be set here.','geodirlocation'),
							'default'    => 'city',
							'options'    => array(
								'city' => __('Add the listings city location to the urls', 'geodirlocation'),
								'region' => __('Add the listings region location to the urls', 'geodirlocation'),
								'country' => __('Add the listings country location to the urls', 'geodirlocation'),
								'disable' => __('Disable', 'geodirlocation'),
							),
							'desc_tip'   => false,
							'advanced' 	 => true
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_location_home_url_settings'
						),
						array( 
							'name' => __( 'Enable locations', 'geodirlocation' ), 
							'type' => 'title', 
							'desc' => '', 
							'id' => 'geodir_location_enable_locations_settings' 
						),
						array(
							'type' => 'radio',
							'id' => 'lm_default_country',
							'name' => __( 'Country', 'geodirlocation' ),
							'desc' => '',
							'default' => 'multi',
							'options' => array(
								'default' => __('Enable default country (country drop-down will not appear on add listing and location switcher).', 'geodirlocation'),
								'multi' => __('Enable Multi Countries', 'geodirlocation'),
								'selected' => __('Enable Selected Countries', 'geodirlocation')
							),
							'desc_tip' => false,
							'advanced' => false
						),
						array(
							'type' => 'multiselect',
							'id' => 'lm_selected_countries',
							'name' => __( 'Select Countries', 'geodirlocation' ),
							'desc' => __( 'Only selected countries will appear in country drop-down on add listing page and location switcher. Make sure to have default country in your selected countries list for proper site functioning.', 'geodirlocation' ) . '<br>' .  wp_sprintf( __( 'Note: You may want to bias search near results to this area, OSM will auto work but you can set Google bounds here: %s.', 'geodirlocation' ), '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=general&section=search#lm_search_bound_sw' ) ) . '">' . __( 'Google LatLngBounds', 'geodirlocation' ) . '</a>'),
							'class' => 'geodir-select',
							'css' => 'width:100%',
							'default'  => '',
							'placeholder' => __( 'Select Countries', 'geodirlocation' ),
							'options' => geodir_get_countries(),
							'desc_tip' => false,
							'advanced' => false,
							'element_require' => '[%lm_default_country%:checked]=="selected"'
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_hide_country_part',
							'name' => __( 'Hide Country Slug', 'geodirlocation' ),
							'desc' => __( 'Hide country part of url for LISTING, CPT and LOCATION pages?', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false,
							'element_require' => '[%lm_default_country%:checked]=="default"'
						),
						array(
							'type' => 'radio',
							'id' => 'lm_default_region',
							'name' => __( 'Region', 'geodirlocation' ),
							'desc' => '',
							'default' => 'multi',
							'options' => array(
								'default' => __('Enable default region (region drop-down will not appear on add listing and location switcher).', 'geodirlocation'),
								'multi' => __('Enable multi regions', 'geodirlocation'),
								'selected' => __('Enable selected regions', 'geodirlocation')
							),
							'desc_tip' => false,
							'advanced' => false,
						),
						array(
							'type' => 'multiselect',
							'id' => 'lm_selected_regions',
							'name' => __( 'Select Regions', 'geodirlocation' ),
							'desc' => __( 'Only selected regions will appear in region drop-down on add listing page and location switcher. Make sure to have default region in your selected regions list for proper site functioning.', 'geodirlocation' ),
							'class' => 'geodir-region-search',
							'css' => 'width:100%',
							'default'  => '',
//							'placeholder' => __( 'Search for a region...', 'geodirlocation' ),
							'options' => $selected_regions,
							'desc_tip' => true,
							'advanced' => false,
							'element_require' => '[%lm_default_region%:checked]=="selected"',
							'custom_attributes' => array(
								'data-placeholder'  =>  __( 'Search for a region...', 'geodirlocation' )
							)
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_hide_region_part',
							'name' => __( 'Hide Region Slug', 'geodirlocation' ),
							'desc' => __( 'Hide region part of url for LISTING, CPT and LOCATION pages?', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false,
							'element_require' => '[%lm_default_region%:checked]=="default"'
						),
						array(
							'type' => 'radio',
							'id' => 'lm_default_city',
							'name' => __( 'City', 'geodirlocation' ),
							'desc' => '',
							'default' => 'multi',
							'options' => array(
								'default' => __('Enable default city (City drop-down will not appear on add listing and location switcher).', 'geodirlocation'),
								'multi' => __('Enable multi cities', 'geodirlocation'),
								'selected' => __('Enable selected cities', 'geodirlocation')
							),
							'desc_tip' => false,
							'advanced' => false
						),
						array(
							'type' => 'multiselect',
							'id' => 'lm_selected_cities',
							'name' => __( 'Select Cities', 'geodirlocation' ),
							'desc' => __( 'Only selected cities will appear in city drop-down on add listing page and location switcher. Make sure to have default city in your selected cities list for proper site functioning.', 'geodirlocation' ),
							'class' => 'geodir-city-search',
							'css' => 'width:100%',
							'default'  => '',
//							'placeholder' => __( 'Search for a city...', 'geodirlocation' ),
							'options' => $selected_cities,
							'desc_tip' => true,
							'advanced' => false,
							'element_require' => '[%lm_default_city%:checked]=="selected"',
							'custom_attributes' => array(
								'data-placeholder'  =>  __( 'Search for a city...', 'geodirlocation' )
							)
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_enable_neighbourhoods',
							'name' => __( 'Enable neighbourhoods?', 'geodirlocation' ),
							'desc' => __( 'Select the option if you wish to enable neighbourhood options.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false,
						),
						array(
							'type' => 'checkbox',
							'id' => 'lm_manual_neighbourhood',
							'name' => __( 'Add New Neighbourhood', 'geodirlocation' ),
							'desc' => __( 'Allow users to enter the new neighbourhood manually in address during add listing when neighbourhood is not retrieved from map address search.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => true,
							'element_require' => '[%lm_enable_neighbourhoods%:checked]',
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_location_enable_locations_settings'
						),
						array( 
							'name' => __( 'Add listing form', 'geodirlocation' ), 
							'type' => 'title', 
							'desc' => '', 
							'id' => 'geodir_location_add_listing_settings' 
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_location_address_fill',
							'name' => __( 'Disable address autocomplete?', 'geodirlocation' ),
							'desc' => __( 'This will stop the address suggestions when typing in address box on add listing page.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_location_dropdown_all',
							'name' => __( 'Show all locations in dropdown?', 'geodirlocation' ),
							'desc' => __( 'This is useful if you have a small directory but can break your site if you have many locations', 'geodirlocation' ),
							'default' => '0',
							'advanced' => true
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_set_address_disable',
							'name' => __( 'Disable set address on map from changing address fields', 'geodirlocation' ),
							'desc' => __( 'This is useful if you have a small directory and you have custom locations or your locations are not known by the Google API and they break the address. (highly recommended not to enable this)', 'geodirlocation' ),
							'default' => '0',
							'advanced' => true
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_set_pin_disable',
							'name' => __( 'Disable move map pin from changing address fields', 'geodirlocation' ),
							'desc' => __( 'This is useful if you have a small directory and you have custom locations or your locations are not known by the Google API and they break the address. (highly recommended not to enable this)', 'geodirlocation' ),
							'default' => '0',
							'advanced' => true
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_location_add_listing_settings'
						),


//						array(// todo move to LM
//							'type' => 'title',
//							'id' => 'lm_redirect_settings',
//							'name' => __( 'Redirect Settings', 'geodiradvancesearch' ),
//						),
//						array(
//							'type' => 'radio',
//							'id' => 'lm_first_load_redirect',
//							'name' => __( 'Home page should go to', 'geodiradvancesearch' ),
//							'desc' => '',
//							'default' => 'no',
//							'options' => array(
//								'no' => __( 'No redirect', 'geodiradvancesearch' ),
//								'nearest' => __( 'Redirect to nearest location <i>(on first time load users will be auto geolocated and redirected to nearest geolocation found)</i>', 'geodiradvancesearch' ),
//								'location' => __( 'Redirect to default location <i>(on first time load users will be redirected to default location</i>', 'geodiradvancesearch' ),
//							),
//						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'lm_redirect_settings'
//						),
//						array(
//							'type' => 'title',
//							'id' => 'lm_geolocation_settings',
//							'name' => __( 'GeoLocation Settings', 'geodiradvancesearch' ),
//						),
//						array(// todo move to LM
//							'type' => 'checkbox',
//							'id' => 'lm_autolocate_ask',
//							'name' => __( 'Ask user if they wish to be geolocated?', 'geodiradvancesearch' ),
//							'desc' => __( 'If this option is selected, users will be asked if they with to be geolocated via a popup.', 'geodiradvancesearch' ),
//							'std' => '0',
//						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'lm_geolocation_settings'
//						),


						array( 
							'name' => __( 'Other', 'geodirlocation' ), 
							'type' => 'title', 
							'desc' => '', 
							'id' => 'geodir_location_other_settings' 
						),
						array(
							'type' => 'number',
							'id'   => 'lm_location_no_of_records',
							'name' => __( 'Load more limit', 'geodirlocation' ),
							'desc' => __( 'Load no of locations by default in [gd_location_switcher] shortcode and then add load more.', 'geodirlocation' ),
							'css'  => 'min-width:300px;',
							'default'  => '50',
							'desc_tip' => true,
							'advanced' => true
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_enable_search_autocompleter',
							'name' => __( 'Enable location search autocompleter?', 'geodirlocation' ),
							'desc' => __( 'This will enable location autocomplete search on the location search bar.', 'geodirlocation' ),
							'default' => '1',
							'advanced' => false
						),
						array(
							'type' => 'number',
							'id' => 'lm_autocompleter_min_chars',
							'name' => __( 'Min chars needed to trigger location autocomplete', 'geodirlocation' ),
							'desc' => __( 'Enter the minimum characters users need to be typed to trigger location autocomplete. Ex: 3.', 'geodirlocation' ),
							'placeholder' => '',
							'default' => '0',
							'custom_attributes' => array(
								'min' => '0',
								'step' => '1',
							),
							'desc_tip' => true
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_hide_map_near_me',
							'name' => __( 'Hide Near Me Marker', 'geodirlocation' ),
							'desc' => __( 'Hide a map marker that showing the user Near Me position on the search page map.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => true
//							'element_require' => '[%lm_autocompleter_min_chars%]!=""'
						),
						array(
							'type' => 'checkbox',
							'id' => 'lm_disable_nearest_cities',
							'name' => __( 'Disable nearest cities?', 'geodirlocation' ),
							'desc' => __( 'In location switcher and search form first time focus to location search input shows nearest city results based on user IP. Tick to disable this nearest city results on first time focus to input.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false
						),
						array(
							'type' => 'checkbox',
							'id'   => 'lm_disable_term_auto_count',
							'name' => __( 'Disable term auto count?', 'geodirlocation' ),
							'desc' => __( 'On shared hosting with lots of listings, saving a listing may take a long time because of auto term counts, if you disable them here you should manually run the GD Tools > Location category counts, often until you can upgrade your hosting and re-enable it here, otherwise your location term and review counts can be wrong.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false
						),
						array(
							'type' => 'checkbox',
							'id' => 'lm_desc_no_editor',
							'name' => __( 'Disable Editor on Location Descriptions', 'geodirlocation' ),
							'desc' => __( 'Tick to disable HTML editor for the location description settings.', 'geodirlocation' ),
							'default' => '0',
							'advanced' => false
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_location_other_settings'
						),
					)
				);
			}

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

		public static function xml_sitemap_options( $settings  ) {
			// Yoast WordPress SEO
			if ( function_exists( 'wpseo_init' ) ) {
				$yoast_seo_options = array(
					array( 
						'name' => __( 'Yoast SEO XML Sitemaps', 'geodirlocation' ), 
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_location_sitemap_settings' 
					),
					array(
						'type' => 'checkbox',
						'id'   => 'lm_sitemap_exclude_location',
						'name' => __( 'Hide Locations', 'geodirlocation' ),
						'desc' => __( 'Tick to hide location pages from Yoast SEO XML sitemaps.', 'geodirlocation' ),
						'css'  => 'min-width:300px;',
						'default'  => '0',
					),
					array(
						'type' => 'checkbox',
						'id'   => 'lm_sitemap_exclude_post_types',
						'name' => __( 'Hide Post Types', 'geodirlocation' ),
						'desc' => __( 'Tick to hide post type pages with location from Yoast SEO XML sitemaps.', 'geodirlocation' ),
						'default' => '0',
						'advanced' => true
					),
					array(
						'type' => 'checkbox',
						'id'   => 'lm_sitemap_exclude_cats',
						'name' => __( 'Hide Categories', 'geodirlocation' ),
						'desc' => __( 'Tick to hide category pages with location from Yoast SEO XML sitemaps.', 'geodirlocation' ),
						'default' => '0',
						'advanced' => true
					),
					array(
						'type' => 'checkbox',
						'id'   => 'lm_sitemap_exclude_tags',
						'name' => __( 'Hide Tags', 'geodirlocation' ),
						'desc' => __( 'Tick to hide tag pages with location from Yoast SEO XML sitemaps.', 'geodirlocation' ),
						'default' => '1',
						'advanced' => true
					),
					array(
						'type' => 'checkbox',
						'id'   => 'lm_sitemap_enable_hoods',
						'name' => __( 'Neighbourhoods', 'geodirlocation' ),
						'desc' => __( 'Tick to show to neighbourhood pages in Yoast SEO XML sitemaps.', 'geodirlocation' ),
						'default' => '0',
						'advanced' => true
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_location_sitemap_settings'
					),
				);

				$settings = array_merge( $settings, $yoast_seo_options );
			}

			// Rank Math
			if ( function_exists( 'rank_math' ) ) {
				$sitemap_settings = array(
					array( 
						'name' => __( 'Rank Math XML Sitemaps', 'geodirlocation' ), 
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_location_rank_math_sitemap_settings' 
					),
					array(
						'type' => 'multiselect',
						'id' => 'lm_rankmath_sitemap_types',
						'name' => __( 'Location Types', 'geodirlocation' ),
						'desc' => __( 'Select location types to show in Rank Math XML sitemaps.', 'geodirlocation' ),
						'class' => 'geodir-select',
						'default' => '',
						'placeholder' => __( 'Select Locations', 'geodirlocation' ),
						'options' => GeoDir_Location_API::get_location_options(),
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'multiselect',
						'id' => 'lm_rankmath_sitemap_cpts',
						'name' => __( 'Post Types', 'geodirlocation' ),
						'desc' => __( 'Select location + post types to show in Rank Math XML sitemaps.', 'geodirlocation' ),
						'class' => 'geodir-select',
						'default' => '',
						'placeholder' => __( 'Select Post Types', 'geodirlocation' ),
						'options' => GeoDir_Location_API::get_cpt_options( true, true ),
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'multiselect',
						'id' => 'lm_rankmath_sitemap_tax',
						'name' => __( 'Taxonomies', 'geodirlocation' ),
						'desc' => __( 'Select location + taxonomies to show in Rank Math XML sitemaps.', 'geodirlocation' ),
						'class' => 'geodir-select',
						'default' => '',
						'placeholder' => __( 'Select Taxonomies', 'geodirlocation' ),
						'options' => GeoDir_Location_API::get_tax_options(),
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_location_rank_math_sitemap_settings'
					),
				);

				$settings = array_merge( $settings, $sitemap_settings );
			}

			return $settings;
		}

		public function wp_sitemaps_settings( $settings ) {
			if ( ! function_exists( 'wp_register_sitemap_provider' ) ) {
				return $settings;
			}

			$location_types = GeoDir_Location_API::get_location_types();

			$location_type_options = array();
			foreach ( $location_types as $type => $data ) {
				$location_type_options[ $type ] = $data['title'];
			}

			$sitemaps_options = array(
				array( 
					'name' => __( 'WordPress XML Sitemaps', 'geodirlocation' ), 
					'type' => 'title', 
					'desc' => '', 
					'id' => 'geodir_location_wp_sitemaps_settings' 
				),
				array(
					'type' => 'multiselect',
					'id' => 'location_sitemaps_locations',
					'name' => __( 'Location Types', 'geodirlocation' ),
					'desc' => __( 'Select location types to show in WordPress core XML sitemaps.', 'geodirlocation' ),
					'class' => 'geodir-select',
					'default' => '',
					'placeholder' => __( 'Select Locations', 'geodirlocation' ),
					'options' => $location_type_options,
					'desc_tip' => true,
					'advanced' => false,
				),
				array(
					'type' => 'sectionend', 
					'id' => 'geodir_location_wp_sitemaps_settings'
				),
			);

			$settings = array_merge( $settings, $sitemaps_options );

			return $settings;
		}

		public static function countries_page( $option ) {
			GeoDir_Location_Admin_Countries::page_output();
		}

		public static function regions_page( $option ) {
			GeoDir_Location_Admin_Regions::page_output();
		}

		public static function cities_page( $option ) {
			GeoDir_Location_Admin_Cities::page_output();
		}

		public static function neighbourhoods_page( $option ) {
			GeoDir_Location_Admin_Neighbourhoods::page_output();
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

			if ( 'countries' == $current_section || 'regions' == $current_section || 'cities' == $current_section || ( 'neighbourhoods' == $current_section && empty( $_REQUEST['add_neighbourhood'] ) ) ) {

				return 'get';
			}

			return 'post';
		}

		public function add_location() {
			// Hide the save button
//			$GLOBALS['hide_save_button'] 		= true;
			

			add_filter( 'geodir_add_listing_map_restrict', '__return_false' );

			include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-add-edit-location.php' );
		}

		/**
		 * Get key data.
		 *
		 * @param  int $key_id
		 * @return array
		 */
		private static function get_location_data( $id ) {
			global $wpdb;

			$empty = array(
				'location_id'		=> 0,
				'country'			=> '',
				'region'			=> '',
				'city'				=> '',
				'country_slug'		=> '',
				'region_slug'		=> '',
				'city_slug'			=> '',
				'latitude'			=> '',
				'longitude'			=> '',
				'is_default'		=> '',
				'meta_title'		=> '',
				'meta_description'	=> '',
				'description'		=> '',
				'image'		        => '',
				'image_tagline'		=> '',
				'cpt_desc'			=> array()
			);

			if ( empty( $id ) ) {
				return $empty;
			}

			$row = (array)geodir_get_location_by_id( '' , $id );

			if ( empty( $row ) ) {
				return $empty;
			}
			
			$seo = GeoDir_Location_SEO::get_seo_by_slug( $row['city_slug'], 'city', $row['country_slug'], $row['region_slug'] );

			$row['meta_title'] = ! empty( $seo->meta_title ) ? $seo->meta_title : '';
			$row['meta_description'] = ! empty( $seo->meta_desc ) ? $seo->meta_desc : '';
			$row['description'] = ! empty( $seo->location_desc ) ? $seo->location_desc : '';
			$row['image'] = ! empty( $seo->image ) ? $seo->image : 0;
			$row['image_tagline'] = isset( $seo->image_tagline ) ? $seo->image_tagline : '';
			$row['cpt_desc'] = ! empty( $seo->cpt_desc ) ? json_decode( $seo->cpt_desc, true ) : array();

			return $row;
		}

		public function locations_filter_actions( $type, $which ) {
			if ( in_array( $type, array( 'region', 'city', 'neighbourhood' ) ) ) {
				$this->country_filter( $type, $which );

				if ( in_array( $type, array( 'city', 'neighbourhood' ) ) ) {
					$this->region_filter( $type, $which );

					if ( $type == 'neighbourhood' ) {
						$this->city_filter( $type, $which );
					}
				}
			}
		}

		public function country_filter( $type, $which ) {
			global $wpdb;

			$country = isset( $_REQUEST['country'] ) ? sanitize_text_field( $_REQUEST['country'] ) : '';

			// Get the results
			$results = $wpdb->get_results( "SELECT DISTINCT `country` FROM " . GEODIR_LOCATIONS_TABLE . " ORDER BY country ASC" );
			?>
			<label for="filter-by-country" class="screen-reader-text"><?php _e( 'Filter by country', 'geodirlocation' ); ?></label>
			<select name="country" id="filter-by-country">
				<option value=""><?php _e( 'All countries', 'geodirlocation' ); ?></option>
				<?php if ( ! empty( $results ) ) { ?>
					<?php foreach ( $results as $row ) { ?>
						<option value="<?php echo esc_attr( $row->country ); ?>" <?php selected( stripslashes( $country ), stripslashes( $row->country ) ); ?>><?php echo __( $row->country, 'geodirlocation' ); ?></option>
					<?php } ?>
				<?php } ?>
			</select>
			<?php
		}

		public function region_filter( $type, $which ) {
			global $wpdb;

			$country = isset( $_REQUEST['country'] ) ? sanitize_text_field( $_REQUEST['country'] ) : '';
			$region = isset( $_REQUEST['region'] ) ? sanitize_text_field( $_REQUEST['region'] ) : '';

			if ( ! empty( $country ) ) {
				$where = array();
				if ( ! empty( $_REQUEST['country'] ) ) {
					$where[] = $wpdb->prepare( "country LIKE %s", wp_unslash( $country ) );
				}
				$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

				// Get the results
				$results = $wpdb->get_results( "SELECT DISTINCT `region` FROM " . GEODIR_LOCATIONS_TABLE . " {$where} ORDER BY region ASC" );
				$disabled = '';
			} else {
				$results = array();
				$disabled = 'disabled="disabled"';
			}
			?>
			<label for="filter-by-region" class="screen-reader-text"><?php _e( 'Filter by region', 'geodirlocation' ); ?></label>
			<select name="region" id="filter-by-region" <?php echo $disabled; ?>>
				<option value=""><?php _e( 'All regions', 'geodirlocation' ); ?></option>
				<?php if ( ! empty( $results ) ) { ?>
					<?php foreach ( $results as $row ) { ?>
						<option value="<?php echo esc_attr( $row->region ); ?>" <?php selected( stripslashes( $region ), stripslashes( $row->region ) ); ?>><?php echo $row->region; ?></option>
					<?php } ?>
				<?php } ?>
			</select>
			<?php
		}

		public function city_filter( $type, $which ) {
			global $wpdb;

			$country = isset( $_REQUEST['country'] ) ? sanitize_text_field( $_REQUEST['country'] ) : '';
			$region = isset( $_REQUEST['region'] ) ? sanitize_text_field( $_REQUEST['region'] ) : '';
			$city = isset( $_REQUEST['city'] ) ? sanitize_text_field( $_REQUEST['city'] ) : '';

			if ( ! empty( $region ) ) {
				$where = array();
				if ( ! empty( $_REQUEST['country'] ) ) {
					$where[] = $wpdb->prepare( "country LIKE %s", wp_unslash( $country ) );
				}
				if ( ! empty( $_REQUEST['region'] ) ) {
					$where[] = $wpdb->prepare( "region LIKE %s", wp_unslash( $region ) );
				}
				$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

				// Get the results
				$results = $wpdb->get_results( "SELECT DISTINCT `city` FROM " . GEODIR_LOCATIONS_TABLE . " {$where} ORDER BY city ASC" );
				$disabled = '';
			} else {
				$results = array();
				$disabled = 'disabled="disabled"';
			}
			?>
			<label for="filter-by-city" class="screen-reader-text"><?php _e( 'Filter by city', 'geodirlocation' ); ?></label>
			<select name="city" id="filter-by-city" <?php echo $disabled; ?>>
				<option value=""><?php _e( 'All cities', 'geodirlocation' ); ?></option>
				<?php if ( ! empty( $results ) ) { ?>
					<?php foreach ( $results as $row ) { ?>
						<option value="<?php echo esc_attr( $row->city ); ?>" <?php selected( stripslashes( $city ), stripslashes( $row->city ) ); ?>><?php echo $row->city; ?></option>
					<?php } ?>
				<?php } ?>
			</select>
			<?php
		}

		public function neighbourhood_filter( $type, $which ) {
			$country = isset( $_REQUEST['country'] ) ? sanitize_text_field( $_REQUEST['country'] ) : '';
			$region = isset( $_REQUEST['region'] ) ? sanitize_text_field( $_REQUEST['region'] ) : '';
			$city = isset( $_REQUEST['city'] ) ? sanitize_text_field( $_REQUEST['city'] ) : '';
			$neighbourhood = isset( $_REQUEST['neighbourhood'] ) ? sanitize_text_field( $_REQUEST['neighbourhood'] ) : '';

			?>
			<label for="filter-by-neighbourhood" class="screen-reader-text"><?php _e( 'Filter by neighbourhood', 'geodirlocation' ); ?></label>
			<select name="neighbourhood" id="filter-by-neighbourhood">
				<option value=""><?php _e( 'All neighbourhoods', 'geodirlocation' ); ?></option>
			</select>
			<?php
		}
	}

endif;

return new GeoDir_Location_Settings_Locations();
