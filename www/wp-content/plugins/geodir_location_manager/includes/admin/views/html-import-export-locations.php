<?php
/**
 * Display the page to manage import/export locations.
 *
 * @since 2.0.0
 * @package GeoDirectory_Location_Manager
 */

global $aui_bs5, $wpdb;

wp_enqueue_script( 'jquery-ui-progressbar' );
 
$nonce = wp_create_nonce( 'geodir_import_export_nonce' );
$sample_csv = GEODIR_LOCATION_PLUGIN_URL . '/assets/sample_locations.csv';
/**
* Filter sample locations data csv file url.
*
* @since 2.0.0
*
* @param string $sample_csv Sample locations data csv file url.
*/
$sample_csv = apply_filters( 'geodir_location_export_locations_sample_csv', $sample_csv );

$chunksize_options = geodir_location_chunksizes_options( 1000, true );

$results = $wpdb->get_results( "SELECT country, COUNT(location_id) AS count FROM " . GEODIR_LOCATIONS_TABLE . " GROUP BY country ORDER BY country ASC" );
$country_options = array();
$count = 0;
if ( ! empty( $results ) ) {
	foreach ( $results as $row ) {
		$count += $row->count;
		$country_options[ $row->country ] = __( $row->country, 'geodirectory' ) . ' ' . '(' . $row->count . ')';
	}
}
$country_options = array_merge( array( '' => wp_sprintf( __( 'All (%d)', 'geodirlocation' ), $count ) ), $country_options );
?>
<div class="inner_content_tab_main gd-import-export">
	<div class="gd-content-heading">
		<?php /**
		 * Contains template for import/export requirements.
		 *
		 * @since 2.0.0
		 */
		include_once( GEODIRECTORY_PLUGIN_DIR . 'includes/admin/views/html-admin-settings-import-export-reqs.php' );
		?>
		<div id="gd_ie_imlocations" class="metabox-holder accordion">
			<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">
				<div class="card-header bg-white rounded-top">
					<h2	class="gd-settings-title h5 mb-0 "><?php echo __( 'Locations: Import CSV', 'geodirlocation' ); ?></h2>
				</div>
				<div id="gd_ie_im_locations" class="gd-hndle-pbox card-body">
				<?php
					$settings = array(
						array(
							'name'       => __( 'If location_id/city_slug exists', 'geodirlocation' ),
							'desc'       => __( 'If the location_id/city_slug column exists in the CSV, you can either update the location or it can be skipped.', 'geodirlocation' ),
							'id'         => 'gd_im_choicelocation',
							'default'    => 'skip',
							'type'       => 'select',
							'options' => array(
								'skip' => __( 'Skip row', 'geodirlocation'),
								'update' => __( 'Update Location', 'geodirlocation'),

							),
							'desc_tip' => true,
						)
					);
					GeoDir_Admin_Settings::output_fields( $settings );
					?>
					<div data-argument="gd_im_location_file" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
						<label for="gd_im_location_file" class="font-weight-bold fw-bold col-sm-3 col-form-label form-label"><?php _e( 'Upload CSV file', 'geodirlocation' ); ?></label>
						<div class="col-sm-9">
							<?php
							echo aui()->button(
								array(
									'type'      => 'a',
									'content'   => __( 'Select file', 'geodirlocation' ),
									'href'      => 'javascript:void(0)',
									'onclick'   => "jQuery('#gd_im_locationplupload-browse-button').trigger('click');"
								)
							);

							echo aui()->button(
								array(
									'type'      => 'a',
									'class'     => 'btn btn-outline-primary',
									'content'   => __( 'Download Sample CSV', 'geodirlocation' ),
									'icon'      => 'fas fa-download',
									'href'      => esc_url( $sample_csv ),
								)
							);
							?>
						</div>
					</div>
					<div class="gd-imex-box">
						<div class="plupload-upload-uic hide-if-no-js" id="gd_im_locationplupload-upload-ui">
							<input type="hidden" readonly="readonly" name="gd_im_location_file" class="gd-imex-file gd_im_location_file" id="gd_im_location" onclick="jQuery('#gd_im_locationplupload-browse-button').trigger('click');" />
							<input id="gd_im_locationplupload-browse-button" type="hidden" value="<?php esc_attr_e( 'Select & Upload CSV', 'geodirlocation' ); ?>" class="gd-imex-cupload button-primary" />
							<input type="hidden" id="gd_im_location_allowed_types" data-exts=".csv" value="csv" />
							<?php
							/**
							 * Called just after the sample CSV download link.
							 *
							 * @since 2.0.0
							 */
							do_action('geodir_location_sample_locations_csv_download_link');
							?>
							<span class="ajaxnonceplu" id="ajaxnonceplu<?php echo wp_create_nonce( 'gd_im_locationpluploadan' ); ?>"></span>
							<div class="filelist"></div>
						</div>
						<span id="gd_im_locationupload-error" class="alert alert-danger" style="display:none"></span>
						<span class="description"></span>
						<div id="gd_importer" style="display:none">
							<input type="hidden" id="gd_total" value="0"/>
							<input type="hidden" id="gd_prepared" value="continue"/>
							<input type="hidden" id="gd_processed" value="0"/>
							<input type="hidden" id="gd_created" value="0"/>
							<input type="hidden" id="gd_updated" value="0"/>
							<input type="hidden" id="gd_skipped" value="0"/>
							<input type="hidden" id="gd_invalid" value="0"/>
							<input type="hidden" id="gd_images" value="0"/>
							<input type="hidden" id="gd_terminateaction" value="continue"/>
						</div>
						<div class="gd-import-progress" id="gd-import-progress" style="display:none">
							<div class="gd-import-file"><b><?php _e("Import Data Status :", 'geodirlocation');?> </b><font id="gd-import-done">0</font> / <font id="gd-import-total">0</font>&nbsp;( <font id="gd-import-perc">0%</font> )
								<div class="gd-fileprogress"></div>
							</div>
						</div>
						<div class="gd-import-msg" id="gd-import-msg" style="display:none">
							<div id="message" class="message alert alert-success"></div>
						</div>
						<div class="gd-imex-btns" style="display:none;">
							<input type="hidden" class="geodir_import_file" name="geodir_import_file" value="save"/>
							<input onclick="geodir_location_prepare_import(this, 'location')" type="button" value="<?php esc_attr_e( 'Import data now', 'geodirlocation' ); ?>" id="gd_import_data" class="btn btn-primary" />
							<input onclick="geodir_location_resume_import(this, 'location')" type="button" value="<?php _e( "Continue Import Data", 'geodirlocation' ); ?>" id="gd_continue_data" class="btn btn-primary" style="display:none"/>
							<input type="button" value="<?php _e("Terminate Import Data", 'geodirlocation');?>" id="gd_stop_import" class="btn btn-danger" name="gd_stop_import" style="display:none" onclick="geodir_location_terminate_import(this, 'location')"/>
							<div id="gd_process_data" style="display:none">
								<span class="spinner is-active" style="display:inline-block;margin:0 5px 0 5px;float:left"></span><?php _e("Wait, processing import data...", 'geodirlocation');?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="gd_ie_exlocations" class="metabox-holder accordion">
			<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">
				<div class="card-header bg-white rounded-top">
					<h2	class="gd-settings-title h5 mb-0 "><?php echo __( 'Locations: Export CSV', 'geodirlocation' ); ?></h2>
				</div>
				<div id="gd_ie_ex_locations" class=" gd-hndle-pbox card-body">
					<?php
					echo aui()->select(
						array(
							'label_col'  => '3',
							'label_class'=> 'font-weight-bold fw-bold',
							'label_type' => 'horizontal',
							'label'      => __( 'Country', 'geodirlocation' ),
							'class'      => 'mw-100',
							'wrap_class' => '',
							'id'         => 'gd_country',
							'name'       => 'gd_imex[country]',
							'options'    => $country_options,
						)
					);

					echo aui()->select(
						array(
							'label_col'  => '3',
							'label_class'=> 'font-weight-bold fw-bold',
							'label_type' => 'horizontal',
							'label'      => __( 'Max entries per csv file', 'geodirlocation' ) . geodir_help_tip( __( 'Please select the maximum number of entries per csv file (defaults to 1000, you might want to lower this to prevent memory issues on some installs)', 'geodirlocation' )),
							'class'      => 'mw-100',
							'id'         => 'gd_chunk_size',
							'name'       => 'gd_chunk_size',
							'value'      => 1000,
							'options'    => $chunksize_options
						)
					);
					?>
					<div class="pt-3 gd-export-reviews-progress" style="display:none;">
						<div id='gd_progressbar_box'>
							<div id="gd_progressbar" class="gd_progressbar">
								<div class="gd-progress-label"></div>
							</div>
						</div>
						<p style="display:inline-block"><?php _e( 'Elapsed Time:', 'geodirlocation' ); ?></p>&nbsp;&nbsp;<p
							id="gd_timer" class="gd_timer">00:00:00</p>
					</div>

					<div class="gd-ie-actions d-flex flex-row align-items-center">
						<input data-export="locations" type="submit" value="<?php echo esc_attr( __( 'Export CSV', 'geodirlocation' ) );?>" class="btn btn-primary" name="gd_start_export" id="gd_start_export">
						<div id="gd_ie_ex_files" class="gd-ie-files ml-4 ms-4 mt-2"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
		/**
		 * Allows you to add more setting to the GD > Import & Export page.
		 *
		 * @param array $gd_posttypes GD post types.
		 * @param array $gd_chunksize_options File chunk size options.
		 * @param string $nonce Wordpress security token for GD import & export.
		 */
		do_action( 'geodir_location_import_export_locations', $nonce );
		?>
	</div>
</div>
<?php GeoDir_Settings_Import_Export::get_import_export_js( $nonce ); ?>
<?php GeoDir_Location_Admin_Import_Export::get_import_export_js( $nonce ); ?>