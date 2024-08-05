<?php
/**
 * Claim Listings Admin Settings.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Settings_Claims class.
 */
if ( ! class_exists( 'GeoDir_Claim_Settings_Claims', false ) ) :

	class GeoDir_Claim_Settings_Claims extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'claims';


			$this->label = __( 'Claim Listings', 'geodir-claim' ) . self::get_pending_claims_count_html();

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 21 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );
			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );

			// Claims
			add_action( 'geodir_admin_field_claims_page', array( $this, 'claims_page' ) );

			// Claims filter
			add_action( 'geodir_claim_restrict_manage_claims', array( $this, 'claims_filter' ), 10, 1 );
		}

		/**
		 * Get the pending claims count html for menu label.
		 * 
		 * @return string
		 */
		public function get_pending_claims_count_html(){
			$html = '';
			if ( ! get_option( 'geodir_claim_version' ) ) {
				return $html;
			}

			$counts = geodir_claim_count_claims();
			$warning_count = $counts->pending;
			$warning_title = esc_attr( sprintf( _n( '%d listing claim pending review', '%d listing claims pending review', $warning_count, 'geodir-claim' ), $warning_count) );

			if($warning_count > 0 ){
				$count = absint($warning_count);
				$html .= " <span class='awaiting-mod  count-$count' title='".$warning_title."'><span class='pending-count'>" . number_format_i18n($count) . '</span></span>';
			}

			return $html;
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'Listing Claims', 'geodir-claim' ),
				'settings' => __( 'Settings', 'geodir-claim' ),
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
			if ( '' == $current_section ) {
				$settings = apply_filters( 'geodir_claim_claims_page_options', 
					array(
//						array(
//							'name' => __( 'Listing Claims', 'geodir-claim' ),
//							'type' => 'sectionstart',
//							'id' => 'geodir_claim_claims_page_settings'
//						),
						array(
							'name' => __( 'Listing Claims', 'geodir-claim' ),
							'type' => 'claims_page',
							'desc' => '',
							'id' => 'claims_page',
						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'geodir_claim_claims_page_settings'
//						)
					)
				);
			} elseif('settings' == $current_section ) {
				$settings = apply_filters( 'geodir_claim_options', 
					array(
						array( 
							'name' => __( 'Claim Listing Options', 'geodir-claim' ),
							'type' => 'title', 
							'desc' => '', 
							'id' => 'geodir_claim_options_settings' 
						),
						array(
							'type' => 'checkbox',
							'id'   => 'claim_auto_approve',
							'name' => __( 'Auto approve via email verification?', 'geodir-claim' ),
							'desc' => __( 'Tick to enable auto approve claim listing by sending verification link via email.', 'geodir-claim' ),
							'default' => '',
							'advanced' => false
						),array(
						'type' => 'checkbox',
						'id'   => 'claim_auto_approve_on_payment',
						'name' => __( 'Auto approve on payment received?', 'geodir-claim' ),
						'desc' => __( 'Tick to enable auto approve claim listing when using pay to claim (requires Pricing Manager addon).', 'geodir-claim' ),
						'default' => '1',
						'advanced' => false
						),
						array(
							'type' => 'sectionend', 
							'id' => 'geodir_claim_options_settings'
						)
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

		public static function claims_page( $option ) {
			GeoDir_Claim_Admin_Claims::page_output();
		}

		public function claims_filter( $which ) {
			global $wpdb, $aui_bs5;

			$post_type = isset( $_REQUEST['_claim_post_type'] ) ? sanitize_text_field( $_REQUEST['_claim_post_type'] ) : '';

			$post_type_options = geodir_get_posttypes( 'options' );
			?>
			<label for="filter-by-package" class="screen-reader-text"><?php _e( 'Filter by post type', 'geodir-claim' ); ?></label>
			<select name="_claim_post_type" id="filter-by-post_type"<?php echo ( $aui_bs5 ? ' class="lh-base"' : '' ); ?>>
				<option value=""><?php _e( 'All post types', 'geodir-claim' ); ?></option>
				<?php if ( ! empty( $post_type_options ) ) { ?>
					<?php foreach( $post_type_options as $key => $label ) { ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $post_type ); ?>><?php echo $label; ?></option>
					<?php } ?>
				<?php } ?>
			</select>
			<?php
		}
	}

endif;

return new GeoDir_Claim_Settings_Claims();
