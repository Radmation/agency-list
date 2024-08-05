<?php
/**
 * GeoDirectory Advance Search Admin
 *
 * @class    GeoDir_Adv_Search_Settings_Advance_Search
 * @author   AyeCode
 * @package  GeoDir_Advance_Search_Filters/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_Adv_Search_Settings_Advance_Search', false ) ) :

	/**
	 * GeoDir_Adv_Search_Settings_Advance_Search class.
	 */
	class GeoDir_Adv_Search_Settings_Advance_Search extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'advance-search';
			$this->label = __( 'Advanced Search', 'geodiradvancesearch' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array();

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
			$settings = array(
				array(
					'type' => 'title', 
					'id' => 'adv_search_autocompleter_settings', 
					'name' => __( 'Autocompleter Settings', 'geodiradvancesearch' ),
				),
				array(
					'type' => 'checkbox',
					'id' => 'advs_enable_autocompleter',
					'name' => __( 'Enable search autocompleter?', 'geodiradvancesearch' ),
					'desc' => __( 'Tick to enable autocompleter for search.', 'geodiradvancesearch' ),
					'default' => '1',
				),
				array(
					'id' => 'advs_search_suggestions_with',
					'type' => 'select',
					'name' => __( 'Show search suggestions with', 'geodiradvancesearch' ),
					'desc' => __( 'If search autocompleter is enabled then it allows to show/hide listings & categories from autocomplete search suggestions.', 'geodiradvancesearch' ),
					'default' => '',
					'class' => 'geodir-select',
					'options' => array(
						'' => __( 'Listings & Categories', 'geodiradvancesearch' ),
						'posts' => __( 'Listings Only', 'geodiradvancesearch' ),
						'terms' => __( 'Categories Only', 'geodiradvancesearch' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				array(
					'type' => 'number',
					'id' => 'advs_autocompleter_min_chars',
					'name' => __( 'Min chars needed to trigger autocomplete', 'geodiradvancesearch' ),
					'desc' => __( 'Enter the minimum characters users need to be typed to trigger auto complete, ex: 2.', 'geodiradvancesearch' ),
					'placeholder' => '3',
					'default'  => '3',
					'custom_attributes' => array(
						'min'           => '1',
						'step'          => '1',
					),
					'desc_tip'   => true
				),
				array(
					'type' => 'number',
					'id' => 'advs_autocompleter_max_results',
					'name' => __( 'Max results to be returned by autocomplete', 'geodiradvancesearch' ),
					'desc' => __( 'Enter the maximum number of results to be returned by autocomplete, ex: 10.', 'geodiradvancesearch' ),
					'placeholder' => '10',
					'default'  => '10',
					'custom_attributes' => array(
						'min'           => '1',
						'step'          => '1',
					),
					'desc_tip'   => true
				),
				array(
					'type' => 'checkbox',
					'id' => 'advs_search_in_child_cats',
					'name' => __( 'Listings from child category?', 'geodiradvancesearch' ),
					'desc' => __( 'Show listings from all child categories of a parent category. Searching with category CAT-A will show all the listings from child categories of CAT-A.', 'geodiradvancesearch' ),
					'default' => '0',
					'advanced' => true
				),
				array( // todo move to LM
					'type' => 'checkbox',
					'id' => 'advs_autocompleter_filter_location',
					'name' => __( 'Enable location filter?', 'geodiradvancesearch' ),
					'desc' => __( 'Tick to filter the autocompleter search results with current location.', 'geodiradvancesearch' ),
					'default' => '1',
				),
				array(
					'type' => 'sectionend',
					'id' => 'adv_search_autocompleter_settings'
				)
			);

			// AJAX search settings.
			if ( geodir_design_style() ) {
				$ajax_search_settings = array(
					array( 
						'type' => 'title', 
						'id' => 'adv_search_ajax_search_settings', 
						'name' => __( 'AJAX Search', 'geodiradvancesearch' ),
					),
					array(
						'type' => 'checkbox',
						'id' => 'advs_ajax_search',
						'name' => __( 'AJAX Search Filters', 'geodiradvancesearch' ),
						'desc' => __( 'Enable AJAX search filters on search page.', 'geodiradvancesearch' ),
						'default' => '',
						'desc_tip' => false,
						'advanced' => false,
					),
					array(
						'type' => 'select',
						'id' => 'advs_search_type',
						'name' => __( 'Update Search Results', 'geodiradvancesearch' ),
						'desc' => __( 'How AJAX search results updated on the search page.', 'geodiradvancesearch' ),
						'class' => 'geodir-select',
						'options' => array(
							'onchange' => __( 'Update only when "Update Results" clicked', 'geodiradvancesearch' ),
							'auto' => __( 'Auto update on any changes', 'geodiradvancesearch' ),
						),
						'default' => 'onchange',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'data-allow-clear' => false
						),
						'element_require' => '[%advs_ajax_search%:checked]',
					),
					array(
						'name' => __( 'Update Results Button Text', 'geodiradvancesearch' ),
						'desc' => __( 'Show the update results button text in search form. Font Awesome class is allowed here.', 'geodiradvancesearch' ),
						'id' => 'advs_update_results_label',
						'type' => 'text',
						'placeholder' => geodir_search_update_results_default_text(),
						'default' => '',
						'desc_tip' => true,
						'advanced' => false,
						'element_require' => '[%advs_ajax_search%:checked]'
					),
					array(
						'type' => 'checkbox',
						'id' => 'advs_map_search',
						'name' => __( 'Search As Move The Map', 'geodiradvancesearch' ),
						'desc' => __( 'Update search results as user move the map.', 'geodiradvancesearch' ),
						'default' => '',
						'element_require' => '[%advs_ajax_search%:checked]'
					),
					array(
						'type' => 'select',
						'id' => 'advs_map_search_type',
						'name' => __( 'Update Map Results', 'geodiradvancesearch' ),
						'desc' => __( 'How update the search results as user move the map.', 'geodiradvancesearch' ),
						'class' => 'geodir-select',
						'options' => array(
							'page' => __( 'Show only page results', 'geodiradvancesearch' ),
							'all' => __( 'Show all results', 'geodiradvancesearch' )
						),
						'default' => 'page',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'data-allow-clear' => false
						),
						'element_require' => '( [%advs_ajax_search%:checked] && [%advs_map_search%:checked] )',
					),
					array(
						'type' => 'checkbox',
						'id' => 'advs_map_search_default',
						'name' => __( 'Default Search As Move The Map', 'geodiradvancesearch' ),
						'desc' => __( 'Show Search As Move The Map option checked by default on the map.', 'geodiradvancesearch' ),
						'default' => '',
						'element_require' => '( [%advs_ajax_search%:checked] && [%advs_map_search%:checked] )',
					),
					array(
						'type' => 'select',
						'id' => 'advs_pagination',
						'name' => __( 'AJAX Pagination', 'geodiradvancesearch' ),
						'desc' => __( 'How to show AJAX pagination on the search results page.', 'geodiradvancesearch' ),
						'class' => 'geodir-select',
						'options' => array(
							'' => __( 'Page Numbers', 'geodiradvancesearch' ),
							'loadmore' => __( 'Load More Button', 'geodiradvancesearch' ),
							'infinite' => __( 'Infinite Scroll', 'geodiradvancesearch' )
						),
						'default' => 'page',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'data-allow-clear' => false
						),
						'element_require' => '[%advs_ajax_search%:checked]',
					),
					array(
						'type' => 'sectionend',
						'id' => 'adv_search_ajax_search_settings'
					)
				);

				$settings = array_merge( $settings, $ajax_search_settings );
			}

			$settings = apply_filters( 'geodir_adv_search_general_settings', $settings );

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}
	}

endif;

return new GeoDir_Adv_Search_Settings_Advance_Search();
