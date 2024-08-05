<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Geodir_Review_Rating_Settings', false ) ) :

	class Geodir_Review_Rating_Settings extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'review_rating';
			$this->label = __( 'Multiratings', 'geodir_reviewratings' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 26 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

            add_action( 'geodir_admin_field_styles_page', array( $this, 'styles_page' ) );
            add_action( 'geodir_admin_field_ratings_page', array( $this, 'ratings_page' ) );

            add_action( 'geodir_admin_field_add_styles', array( $this, 'add_styles' ) );
            add_action( 'geodir_admin_field_create', array( $this, 'create_rating' ) );

            add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );

            add_filter( 'geodir_uninstall_options', array($this, 'geodir_review_rating_uninstall_options'), 10, 1);
		}

		/**
		 * Output the toggle show/hide advanced settings.
		 */
		public function output_toggle_advanced(){
			global $hide_advanced_toggle;

			if($hide_advanced_toggle){ return;}

			// check if we need to show advanced or not
			if(!$this->show_advanced()){return;}


			$this->toggle_advanced_button();

		}

		/**
		 * Detect if the advanced settings button should be shown or not.
		 *
		 * @return bool
		 */
		public function show_advanced(){


			$show = false;

			if(isset($_REQUEST['section']) && $_REQUEST['section']=='add_styles'){
				$show = true;
			}
			return $show;
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
            $sections = array(
                ''          => __( 'General', 'geodir_reviewratings' ),
                'styles' 	=> __( 'Rating Styles', 'geodir_reviewratings' ),
                'ratings' 	=> __( 'Ratings', 'geodir_reviewratings' ),
            );

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

            if ( 'create' == $current_section ) {
                $settings = apply_filters( 'geodir_review_rating_create_options',
                    array(
                        array(
                            'name' => __( 'Create Ratings', 'geodir_reviewratings' ),
                            'type' => 'create',
                            'desc' => '',
                            'id' => 'review_rating_create_settings'
                        ),
                        array(
                            'type' => 'sectionend',
                            'id' => 'review_rating_create_settings'
                        )
                    )
                );
            } elseif ( 'ratings' == $current_section ) {
                $settings = apply_filters( 'geodir_review_rating_options',
                    array(
                        array(
                            'name' => __( 'Ratings', 'geodir_reviewratings' ),
                            'type' => 'ratings_page',
                            'desc' => '',
                            'id' => 'review_ratings_settings'
                        ),
//                        array(
//                            'name' => __( 'Ratings', 'geodir_reviewratings' ),
//                            'type' => 'sectionstart',
//                            'id' => 'review_ratings_settings'
//                        ),
//                        array(
//                            'type' => 'sectionend',
//                            'id' => 'review_ratings_settings'
//                        )
                    )
                );
            } elseif ( 'styles' == $current_section ) {
                $settings = apply_filters( 'geodir_review_rating_styles_page_options',
                    array(

//                        array(
//                            'name' => __( 'Manage Rating Style', 'geodir_reviewratings' ),
//                            'type' => 'sectionstart',
//                            'id' => 'review_rating_styles_page_settings'
//                        ),
	                    array(
		                    'name' => __( 'Manage Rating Styles', 'geodir_reviewratings' ),
		                    'type' => 'styles_page',
		                    'desc' => '',
		                    'id' => 'review_rating_styles_page_settings'
	                    ),
//                        array(
//                            'type' => 'sectionend',
//                            'id' => 'review_rating_styles_page_settings'
//                        )
                    )
                );
            } elseif ( 'add_styles' == $current_section ) {
                $settings = array(
                    array(
                        'name' => __( 'Add Rating Style', 'geodir_reviewratings' ),
                        'type' => 'add_styles',
                        'desc' => '',
                        'id' => 'review_rating_styles_settings'
                    ),
                    array(
                        'type' => 'sectionend',
                        'id' => 'review_rating_styles_settings'
                    )
                );

                $settings = apply_filters( 'geodir_review_rating_styles_options', $settings);

            } else {
                $settings = apply_filters( 'geodir_review_rating_general_options', array(
                    array(
                        'name' => __( 'Review Rating Settings', 'geodir_reviewratings' ),
                        'type' => 'title',
                        'desc' => '',
                        'id' => 'review_rating_settings'
                    ),
                    array(
                        'name' => __( 'Enable multiratings', 'geodir_reviewratings' ),
                        'desc' 	=> __('Enable multiratings on listings.', 'geodir_reviewratings' ),
                        'id' => 'rr_enable_rating',
                        'std' => '0',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __( 'Enable comment images upload', 'geodir_reviewratings' ),
                        'desc' 	=> __('Enable upload images in comments for a post.', 'geodir_reviewratings' ),
                        'id' => 'rr_enable_images',
                        'std' => '0',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __( 'Enable optional multiratings', 'geodir_reviewratings' ),
                        'desc'  => __('Enable multiratings as optional.', 'geodir_reviewratings' ),
                        'id' => 'rr_optional_multirating',
                        'std' => '0',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __( 'Comment images limit', 'geodir_reviewratings' ),
                        'desc' 	=> __('Limit number of uploaded images in comments for a post.', 'geodir_reviewratings' ),
                        'id' => 'rr_image_limit',
                        'placeholder' => '10',
                        'type' => 'number',
                        'desc_tip' => true
                    ),
                    array(
                        'name' => __( 'Enable review on comments', 'geodir_reviewratings' ),
                        'desc' 	=> __('Let\'s users rate comments useful or not.', 'geodir_reviewratings' ),
                        'id' => 'rr_enable_rate_comment',
                        'std' => '0',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __( 'Enable comment list sorting', 'geodir_reviewratings' ),
                        'desc' 	=> __('Enable comment list sorting.', 'geodir_reviewratings' ),
                        'id' => 'rr_enable_sorting',
                        'std' => '0',
                        'type' => 'checkbox',
                    ),

                    array( 'type' => 'sectionend', 'id' => 'review_rating_settings' ),
                ));
            }

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

        public function add_styles() {
            // Hide the save button
            $GLOBALS['hide_save_button'] = true;

            $style_id 	= isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            $style  	= self::get_style_data( $style_id );

            include( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/views/html-add-edit-style.php' );
        }

        public function create_rating() {
            // Hide the save button
            $GLOBALS['hide_save_button'] = true;

            $rating_id 	= isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            $rating  	= self::get_rating_data( $rating_id );

            include( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/views/html-add-edit-ratings.php' );
        }

        public static function styles_page( $option ) {
            Geodir_Review_Rating_Styles::page_output();
        }

        public static function ratings_page( $option ) {
            Geodir_Review_Ratings::page_output();
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

            if ( 'styles' == $current_section || 'ratings' == $current_section ) {

                return 'get';
            }

            return 'post';
        }

        /**
         * Get style data.
         *
         * @param  int $style_id
         * @return array
         */
        public static function get_style_data( $style_id ) {

            $empty = array(
                'id' => 0,
                'name' => '',
                's_img_off' => '',
                's_img_width' => '',
                's_img_height' => '',
                'star_color' => '',
                'star_color_off' => '',
                'star_lables' => '',
                'star_number' => '',
                'is_default' => '',
            );

            if ( empty( $style_id ) ) {
                return $empty;
            }

            $row = (array)geodir_get_style_by_id( $style_id );

            if ( empty( $row ) ) {
                return $empty;
            }

            return $row;
        }

        /**
         * Get ratings data.
         *
         * @param  int $rating_id
         * @return array
         */
        public static function get_rating_data( $rating_id ) {

            $empty = array(
                'id' => 0,
                'title' => '',
                'post_type' => '',
                'category' => '',
                'category_id' => '',
                'check_text_rating_cond' => '',
            );

            if ( empty( $rating_id ) ) {
                return $empty;
            }

            $row = (array)geodir_get_rating_by_id( $rating_id );

            if ( empty( $row ) ) {
                return $empty;
            }

            return $row;
        }

        public static function geodir_review_rating_uninstall_options($settings){
            array_pop($settings);
            $settings[] = array(
                'name'     => __( 'Review Rating Manager', 'geodir_reviewratings' ),
                'desc'     => __( 'Check this box if you would like to completely remove all of its data when Review Rating Manager is deleted.', 'geodir_reviewratings' ),
                'id'       => 'uninstall_geodir_review_rating_manager',
                'type'     => 'checkbox',
            );
            $settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );

            return $settings;
        }

	}

endif;

return new Geodir_Review_Rating_Settings();
