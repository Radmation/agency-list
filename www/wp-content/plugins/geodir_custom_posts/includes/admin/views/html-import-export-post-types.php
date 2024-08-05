<?php
/**
 * Display the page to manage import/export post types.
 *
 * @since 2.2.1
 * @package GeoDir_Custom_Posts
 */

global $aui_bs5;

$section = "post_types";
$post_types = geodir_get_posttypes( 'options-plural' );
$nonce = wp_create_nonce( 'geodir_import_export_nonce' );
wp_enqueue_script( 'jquery-ui-progressbar' );
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
		<div id="gd_ie_im<?php echo $section; ?>" class="metabox-holder accordion">
			<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">
				<div class="card-header bg-white rounded-top"><h2	class="gd-settings-title h5 mb-0 "><?php echo __( 'Import Post Types', 'geodir_custom_posts' ); ?></h2></div>
				<div id="gd_ie_im_<?php echo $section; ?>" class="gd-hndle-pbox card-body gd-imex-box">
					<?php
					echo aui()->select(
							array(
								'id'               => 'gd_im_choice' . $section,
								'name'             => 'gd_im_choice' . $section,
								'label'            => __( 'If Post Type Exists', 'geodir_custom_posts' ),
								'help_text'        => __( 'If the post type exists in the CSV, you can either update the post type or it can be skipped.', 'geodir_custom_posts' ),
								'label_col'        => '3',
								'label_type'       => 'horizontal',
								'label_class'      => 'font-weight-bold fw-bold',
								'class'            => 'mw-100',
								'options'          => array(
									'skip'   => __( 'Skip Row', 'geodir_custom_posts'),
									'update' => __( 'Update Post Type', 'geodir_custom_posts'),
								),
								'select2'          => true,
								'data-allow-clear' => false,
							)
						);
					?>
					<div data-argument="gd_im_<?php echo $section; ?>_file" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
						<label for="gd_im_<?php echo $section; ?>" class="font-weight-bold fw-bold col-sm-3 col-form-label"><?php _e( 'Upload CSV File', 'geodir_custom_posts' ); ?></label>
						<div class="col-sm-9">
							<?php
							echo aui()->button(
								array(
									'type'    => 'a',
									'content' => __( 'Select File', 'geodir_custom_posts' ),
									'href'    => 'javascript:void(0)',
									'onclick' => "jQuery('#gd_im_" . $section . "plupload-browse-button').trigger('click');"
								)
							);
							?>
						</div>
					</div>
					<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row"><div class="col-sm-3"></div><div class="col-sm-9">
						<div class="plupload-upload-uic hide-if-no-js" id="gd_im_<?php echo $section; ?>plupload-upload-ui">
							<input type="hidden" readonly="readonly" name="gd_im_<?php echo $section; ?>_file" class="gd-imex-file gd_im_<?php echo $section; ?>_file" id="gd_im_<?php echo $section; ?>" onclick="jQuery('#gd_im_<?php echo $section; ?>plupload-browse-button').trigger('click');" />
							<input id="gd_im_<?php echo $section; ?>plupload-browse-button" type="hidden" value="<?php esc_attr_e( 'Select & Upload CSV', 'geodir_custom_posts' ); ?>" class="gd-imex-cupload button-primary" />
							<input type="hidden" id="gd_im_<?php echo $section; ?>_allowed_types" data-exts=".csv" value="csv" />
							<span class="ajaxnonceplu" id="ajaxnonceplu<?php echo wp_create_nonce( 'gd_im_' . $section . 'pluploadan' ); ?>"></span>
							<div class="filelist mt-0"></div>
						</div>
						<span id="gd_im_<?php echo $section; ?>upload-error" class="alert alert-danger" style="display:none"></span>
						<span class="description"></span>
						<div id="gd_importer" style="display:none">
							<input type="hidden" id="gd_total" value="0"/>
							<input type="hidden" id="gd_prepared" value="continue"/>
							<input type="hidden" id="gd_processed" value="0"/>
							<input type="hidden" id="gd_skipped" value="0"/>
							<input type="hidden" id="gd_created" value="0"/>
							<input type="hidden" id="gd_updated" value="0"/>
							<input type="hidden" id="gd_invalid" value="0"/>
							<input type="hidden" id="gd_terminateaction" value="continue"/>
						</div>
						<div class="gd-import-progress" id="gd-import-progress" style="display:none"><div class="gd-import-file"><b><?php _e("Import Data Status :", 'geodir_custom_posts');?> </b><font id="gd-import-done">0</font> / <font id="gd-import-total">0</font>&nbsp;( <font id="gd-import-perc">0%</font> ) <div class="gd-fileprogress"></div></div></div>
						<div class="gd-import-msg" id="gd-import-msg" style="display:none"><div id="message" class="message alert alert-success fade show"></div></div>
						<div class="gd-import-csv-msg" id="gd-import-errors" style="display:none"><div id="gd-csv-errors" class="message fade"></div></div>
						<div class="gd-imex-btns" style="display:none;">
							<input type="hidden" class="geodir_import_file" name="geodir_import_file" value="save"/>
							<input onclick="geodir_cp_prepare_import(this, '<?php echo $section; ?>')" type="button" value="<?php esc_attr_e( 'Import Data', 'geodir_custom_posts' ); ?>" id="gd_import_data" class="btn btn-primary" />
							<input onclick="geodir_cp_resume_import(this, '<?php echo $section; ?>')" type="button" value="<?php _e( "Continue Import Data", 'geodir_custom_posts' ); ?>" id="gd_continue_data" class="btn btn-primary" style="display:none"/>
							<input type="button" value="<?php _e("Terminate Import Data", 'geodir_custom_posts');?>" id="gd_stop_import" class="btn btn-danger" name="gd_stop_import" style="display:none" onclick="geodir_cp_terminate_import(this, '<?php echo $section; ?>')"/>
							<div id="gd_process_data" style="display:none"><span class="spinner is-active" style="display:inline-block;margin:0 5px 0 5px;float:left"></span><?php _e( "Wait, processing import data...", 'geodir_custom_posts' );?></div>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="gd_ie_ex<?php echo $section; ?>" class="metabox-holder accordion">
			<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">
				<div class="card-header bg-white rounded-top"><h2 class="gd-settings-title h5 mb-0"><?php echo __( 'Export Post Types', 'geodir_custom_posts' ); ?></h2></div>
				<div id="gd_ie_ex_<?php echo $section; ?>" class="gd-hndle-pbox card-body">
					<input type="hidden" id="gd_chunk_size" value="<?php echo count( $post_types ); ?>">
					<?php
					echo aui()->select(
							array(
								'id'               => 'gd_post_types',
								'name'             => 'gd_imex[post_types][]',
								'label'            => __( 'Post Types', 'geodir_custom_posts' ),
								'placeholder'      => __( 'All', 'geodir_custom_posts' ),
								'label_col'        => '3',
								'label_type'       => 'horizontal',
								'label_class'      => 'font-weight-bold fw-bold',
								'class'            => 'mw-100',
								'wrap_class'       => count( $post_types ) < 2 ? 'd-none' : '',
								'options'          => $post_types,
								'select2'          => true,
								'multiple'         => true,
								'data-allow-clear' => false,
							)
						);
					?>
					<div class="gd-export-reviews-progress <?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row" style="display:none;">
						<div class="col-sm-3"></div>
						<div class="col-sm-9"><div id='gd_progressbar_box'><div id="gd_progressbar" class="gd_progressbar"><div class="gd-progress-label"></div></div></div><p style="display:inline-block" class="mb-0"><?php _e( 'Elapsed Time:', 'geodir_custom_posts' ); ?></p>&nbsp;&nbsp;<p id="gd_timer" class="gd_timer mb-0">00:00:00</p></div>
					</div>
					<div class="gd-ie-actions">
						<div class="<?php echo ( $aui_bs5 ? '' : 'form-group' ); ?> row mb-0"><div class="col-sm-3"></div><div class="col-sm-9"><input data-export="<?php echo $section; ?>" type="submit" value="<?php echo esc_attr( __( 'Export CSV', 'geodir_custom_posts' ) );?>" class="btn btn-primary" name="gd_start_export" id="gd_start_export"></div></div>
						<div class="<?php echo ( $aui_bs5 ? '' : 'form-group' ); ?> row mb-0"><div class="col-sm-3"></div><div class="col-sm-9"><div id="gd_ie_ex_files" class="gd-ie-files mt-3"></div></div></div>
					</div>
				</div>
			</div>
		</div>
		<?php
		/**
		 * Allows you to add more setting to the GD > Import & Export page.
		 *
		 * @param string $nonce Wordpress security token for GD import & export.
		 */
		do_action( 'geodir_cp_import_export_' . $section, $nonce );
		?>
	</div>
</div>
<?php GeoDir_Settings_Import_Export::get_import_export_js( $nonce ); GeoDir_CP_Admin_Import_Export::get_import_export_js( $nonce ); ?>