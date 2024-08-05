<?php

/**
 * GeoDir_Location_Widget_Locations class.
 *
 * @since 2.0.0
 */
class GeoDir_Location_Widget_Location_Switcher extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'geodirlocation',
			'block-icon'     => 'location-alt',
			'block-category' => 'geodirectory',
			'block-keywords' => "['geodirlocation','location','locations']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_location_switcher',
			'name'           => __( 'GD > Location Switcher', 'geodirlocation' ),
			'widget_ops'     => array(
				'classname'     => 'geodir-lm-location-switcher ' . geodir_bsui_class(),
				'description'   => esc_html__( 'Displays the location switcher.', 'geodirlocation' ),
				'geodirectory'  => true,
				'gd_show_pages' => array(),
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 */
	public function set_arguments() {

		$design_style = geodir_design_style();

		$arguments = array(
			'title'  => array(
                'title' => __('Title:', 'geodirlocation'),
                'desc' => __('The widget title.', 'geodirlocation'),
                'type' => 'text',
                'default'  => '',
                'desc_tip' => true,
                'advanced' => false
            )
		);

		if($design_style) {
			$arguments['mt']  = geodir_get_sd_margin_input('mt');
			$arguments['mr']  = geodir_get_sd_margin_input('mr');
			$arguments['mb']  = geodir_get_sd_margin_input('mb');
			$arguments['ml']  = geodir_get_sd_margin_input('ml');
		}

		return $arguments;
	}

	public function output( $args = array(), $widget_args = array(), $content = '' ) {

		$design_style = geodir_design_style();
		$template = $design_style ? $design_style."/location-switcher.php" : "legacy/location-switcher.php";

		// wrap class
		$wrap_class = geodir_build_aui_class($args);
		$template_args = array(
			'args'  => $args,
			'wrap_class'    => $wrap_class
		);
		return geodir_get_template_html( $template, $template_args, '', plugin_dir_path( GEODIR_LOCATION_PLUGIN_FILE ). "/templates/" );

	}
}

function geodir_location_enable_dropdown( $args ) {
	global $aui_bs5;

	if ( geodir_get_option( 'lm_enable_search_autocompleter' ) && geodir_design_style() ) {
		$data_bs = $aui_bs5 ? "data-bs-" : "data-";

		$args['extra_attributes'][ $data_bs . 'toggle' ] = 'dropdown';
		$args['extra_attributes'][ $data_bs . 'flip' ] = 'false';
	}

	return $args;
}
add_filter( 'geodir_search_near_input_args', 'geodir_location_enable_dropdown', 10, 1 );

// non class stuff
add_action( 'wp_footer', 'geodir_location_autocomplete_script' );
function geodir_location_autocomplete_script() {
	global $geodirectory, $gd_post, $aui_bs5;

	$design_style = geodir_design_style();

	// ComponentRestrictions
	$restrict_countries = apply_filters( 'geodir_location_switcher_autocomplete_restrict_countries', array() );

	$_properties = array();
	if ( ! empty( $restrict_countries ) && is_array( $restrict_countries ) ) {
		$_properties['componentRestrictions'] = array( 'country' => $restrict_countries );
	}

	if ( ! empty( $_properties ) ) {
		$properties = wp_json_encode( $_properties );
		$properties = substr( $properties, 1, -1 );
	} else {
		$properties = '';
	}
	$properties = apply_filters( 'geodir_location_switcher_autocomplete_properties', $properties );
	$properties = ! empty( $properties ) ? ',' . ltrim( $properties, ',' ) : '';

	ob_start();

	if ( $design_style ) {
		$data_bs = $aui_bs5 ? "data-bs-" : "data-";
	?>
		<!-- Modal -->
		<div class="modal fade bsui" id="gdlm-switcher" tabindex="-1" aria-labelledby="dlm-switcher-title" aria-hidden="true">
			<div class="modal-dialog ">
				<div class="modal-content ">
					<div class="modal-header text-center">
						<div class="modal-title text-center w-100">
							<h5 class="w-100" id="dlm-switcher-title"><?php _e("Change Location","geodirlocation");?></h5>
							<h6 class="w-100 h6 text-muted"><?php _e("Find awesome listings near you!","geodirlocation");?></h6>
						</div>
						<?php if ( $aui_bs5 ) { ?>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodirlocation' ); ?>"></button>
						<?php } else { ?>
							<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodirlocation' ); ?>"><span aria-hidden="true">&times;</span></button>
						<?php } ?>
					</div>
					<div class="modal-body text-center">
						<div class="dropdown">
							<?php
							echo aui()->input(
								array(
									'class'              => 'geodir-location-search',
									'label'              => esc_html__( "Change Location","geodirlocation"),
									'type'              => 'text',
									'placeholder'       => esc_html__( "city, region, country" , 'geodirlocation'),
									'extra_attributes'  => array(
										$data_bs . 'toggle' => "dropdown",
										'aria-label'    => esc_html__( 'city, region, country' , 'geodirlocation' ),
									)
								)
							);
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	} else {
	?>
	<div class="geodir-location-search-wrap lity-hide lity-show" style="display: none;">
		<div class="gdlmls-title"><?php _e( 'Change Location', 'geodirlocation' ); ?></div>
		<div class="gdlmls-sub-title"><?php _e( 'Find awesome listings near you!', 'geodirlocation' ); ?></div>

		<div class="geodir-location-search-input-wrap">
			<input type="text" class="geodir-location-search" placeholder="<?php esc_attr_e( 'city, region, country', 'geodirlocation' ); ?>" aria-label="<?php esc_attr_e( 'city, region, country', 'geodirlocation' ); ?>">
		</div>
	</div>
	<?php
	}

	$clear_location_url = '';
	if ( ! empty( $geodirectory ) && ! empty( $geodirectory->location ) && ! empty( $geodirectory->location->type ) ) {
		$post_type = geodir_get_current_posttype();

		if ( $post_type && geodir_is_geodir_page() ) {
			$clear_location_url = GeoDir_Location_SEO::get_post_type_archive_link( $post_type, false );

			if ( geodir_is_page( 'detail' ) ) {
				if ( ! empty( $gd_post ) && ! empty( $gd_post->default_category ) && ( $term_link = GeoDir_Location_SEO::get_term_link( (int)$gd_post->default_category, false ) ) ) {
					if ( ! is_wp_error( $term_link ) ) {
						$clear_location_url = $term_link;
					}
				}
			} else if ( geodir_is_page( 'archive' ) && ( $term = get_queried_object() ) ) {
				if ( ! empty( $term ) && ! empty( $term->term_id ) && ( $term_link = GeoDir_Location_SEO::get_term_link( (int) $term->term_id, false ) ) ) {
					if ( ! is_wp_error( $term_link ) ) {
						$clear_location_url = $term_link;
					}
				}
			}
		}
	}
?>
<script>
var gdlmls_selected = '';
var gdlmls_nearest = [];
var gdlmls_country = [];
var gdlmls_region = [];
var gdlmls_city = [];
var gdlmls_neighbourhood = [];
var gdlmls_google_sessionToken = '';// google session token
var gdlmls_google_service = '';// google service
var gdlmls_do_not_close = false;
var gdlmls_doing_search = 0;
var gdlmls_doing_nearest = false;
var gdlmls_is_search = false;
var gdlmls_keyup_timeout = null;

jQuery(function() {
	// init
	gdlm_ls_init('.geodir-location-search');
	<?php if(geodir_get_option('lm_enable_search_autocompleter',true)){?>gdlm_ls_init('.snear');<?php } // only enable if set to enable ?>
	gdlm_is_search_input_location();

	// on CPT change
	jQuery("body").on("geodir_setup_search_form", function(){
		gdlm_ls_init('.geodir-location-search');
		<?php if(geodir_get_option('lm_enable_search_autocompleter',true)){?>gdlm_ls_init('.snear');<?php } // only enable if set to enable ?>
		gdlm_is_search_input_location();
	});
});

function gdlm_is_search_input_location(){
	/* Check for on change */
	jQuery(".snear").on("change", function(){
		setTimeout(function(){
			if (typeof geodir_search_params !== 'undefined' && geodir_search_params.autocompleter_filter_location) {
				jQuery('.gd-search-field-search .gd-suggestions-dropdown').remove();
			}
			var $type = jQuery('.geodir-location-search-type').attr('name');
			if($type ){
				jQuery('.gd-search-field-near').removeClass('in-location in-neighbourhood in-city in-region in-country').addClass('in-location in-'+$type);
			}else{
				jQuery('.gd-search-field-near').removeClass('in-location in-neighbourhood in-city in-region in-country');
			}
		}, 100);
	}).on("keyup", function () {
		if (!(jQuery(this).val().trim() == geodir_params.txt_near_my_location && jQuery(this).closest('form').find('.geodir-location-search-type').val()=='me')) {
			jQuery('.gd-search-field-near').removeClass('in-location in-neighbourhood in-city in-region in-country');
			jQuery('.geodir-location-search-type').val('').attr('name','');
			jQuery('.sgeo_lat').val('');
			jQuery('.sgeo_lon').val('');
		}
	});
}

function gdlm_ls_init($field){
	jQuery($field).on("focusin", 
		function(){
			gdlmls_selected = this;
			gdlm_ls_focus_in(this);
		}).on("focusout",
		function(){
			gdlmls_selected = '';
			gdlm_ls_focus_out(this);
		});

	// window resize tasks
	jQuery(window).on("resize", function(){
		gdls_ls_resize_suggestions();
	});
}

function gdlm_ls_focus_in($input){
	if(jQuery($input).parent().find(".gdlm-location-suggestions").length){
		<?php if(!$design_style){ ?>jQuery($input).parent().find(".gdlm-location-suggestions").show();<?php } ?>
		gdlm_ls_current_location_suggestion($input);

	}else{
		jQuery($input).after("<div class='<?php if($design_style){ echo "dropdown-menu dropdown-caret-0 w-100 scrollbars-ios overflow-auto p-0 m-0";}?> gd-suggestions-dropdown gdlm-location-suggestions gd-ios-scrollbars'>" +
			"<ul class='gdlmls-near list-unstyled p-0 m-0 '></ul>" +
			"<ul class='gdlmls-neighbourhood list-unstyled p-0 m-0'></ul>" +
			"<ul class='gdlmls-city list-unstyled p-0 m-0'></ul>" +
			"<ul class='gdlmls-region list-unstyled p-0 m-0'></ul>" +
			"<ul class='gdlmls-country list-unstyled p-0 m-0'></ul>" +
			"<ul class='gdlmls-more list-unstyled p-0 m-0'></ul>" +
			"</div>");
		gdlm_ls_init_suggestions($input);
		gdlm_ls_current_location_suggestion($input);
	}

	// resize
	gdls_ls_resize_suggestions();

	// set if is search near
	if(jQuery('.gdlm-location-suggestions:visible').prev().hasClass('snear') || jQuery($input).hasClass('snear')){
		gdlmls_is_search = true;
	}else{
		gdlmls_is_search = false;
	}
}

function gdlm_ls_focus_out($input) {
	setTimeout(function() {
		_ua = navigator.userAgent.toLowerCase();
		isChrome = /chrome/.test(_ua);
		isWin10 = /windows nt 10.0/.test(_ua);
		if (!gdlmls_do_not_close) {
			<?php if(!$design_style){ ?>jQuery($input).parent().find(".gdlm-location-suggestions").hide();<?php } ?>
		}
	}, 200);
}

/**
 * Set the max height for the suggestion div so to never scroll past the bottom of the page.
 */
function gdls_ls_resize_suggestions() {
	setTimeout(function() {
		if (jQuery('.gdlm-location-suggestions:visible').length) {
			var offset = jQuery('.gdlm-location-suggestions:visible').offset().top;
			var windowHeight = jQuery(window).height();
			var maxHeight = windowHeight - (offset - jQuery(window).scrollTop());

			if (jQuery('.gdlm-location-suggestions:visible').prev().hasClass('snear')) {
				jQuery('.gdlm-location-suggestions:visible').css('max-height', windowHeight - 40);
			} else {
				jQuery('.gdlm-location-suggestions:visible').css('max-height', maxHeight);
			}
		}
	}, 50);
}

function gdlm_ls_init_suggestions($input) {
	setTimeout(function() {
		gdls_ls_resize_suggestions();
	}, 250);
	<?php if ( $design_style ) { /* Show location switcher on ENTER key. */ ?>
	jQuery($input).off("keypress").on("keypress",function(e) {
		if(e.keyCode && e.keyCode == 13 && !gdlmls_is_search && !jQuery($input).parent().find(".gdlm-location-suggestions").hasClass('show')) {
			jQuery($input).attr('aria-expanded','true').addClass('show');jQuery($input).parent().find(".gdlm-location-suggestions").addClass('show');
		}
	});
	<?php } ?>
	jQuery($input).on("keyup", function($input) {
		gdlmls_doing_search = 3; // city, region, country
		if (gdlmls_keyup_timeout != null) clearTimeout(gdlmls_keyup_timeout);
		gdlmls_keyup_timeout = setTimeout(gdlm_ls_maybe_fire_suggestions, 500);
	});
}

function gdlm_ls_maybe_fire_suggestions(){
	/* Reset timer */
	gdlmls_keyup_timeout = null;
	/* Do suggestions */
	gdlm_ls_current_location_suggestion();
	_value = gdlmls_selected ? jQuery(gdlmls_selected).val().trim() : '';
	_chars = parseInt( geodir_location_params.autocompleter_min_chars );
	if ((!_value || _chars < 1 || _chars > 0 && _value && parseInt(_value.length) >= _chars) && !(_value == geodir_params.txt_near_my_location && jQuery(gdlmls_selected).closest("form").find(".geodir-location-search-type").val() == "me")) {
		gdlm_ls_city_suggestion();
		<?php if ( GeoDir_Location_Neighbourhood::is_active() ) { echo "gdlm_ls_neighbourhood_suggestion();"; } ?>
		<?php if ( ! geodir_get_option( 'lm_hide_region_part' ) ) { echo "gdlm_ls_region_suggestion();"; } ?>
		<?php if ( ! geodir_get_option( 'lm_hide_country_part') ) { echo "gdlm_ls_country_suggestion();"; } ?>
	}
}

function gdlm_ls_maybe_suggest_more() {
	if (
		gdlmls_doing_search == 0 &&
		gdlmls_country.length == 0 &&
		gdlmls_region.length == 0 &&
		gdlmls_city.length == 0 &&
		gdlmls_neighbourhood.length == 0
	) {
		$input = jQuery(gdlmls_selected).val();
		if ($input) {
			<?php
			$near_add = geodir_get_option( 'search_near_addition' );
			$near_address = '';

			if ( trim( $near_add ) != '' ) {
				$near_address .= ", " . stripslashes( $near_add );
				?>
				$input = $input + "<?php echo $near_address; ?>";
				<?php
			}
			/**
			 * Adds any extra info to the near search box query when trying to geolocate it via google api.
			 *
			 * @since 1.0.0
			 */
			$near_add2 = apply_filters( 'geodir_search_near_addition', '' );

			if ( trim( $near_add2 ) != '' ) {
				$near_address .= $near_add2;
				?>
				$input = $input<?php echo $near_add2; ?>;
				<?php
			}
			?>
			if ((typeof google !== 'undefined' && typeof google.maps !== 'undefined')) {
				<?php
				/**
				 * Add script before geocode address search.
				 *
				 * @since 2.2.5
				 *
				 * @param string $near_address Nearest address filter.
				 */
				do_action( 'geodir_geocode_address_search_script', $near_address );
				?>
				gdlm_ls_google_suggestions($input);
			}
		} else {
			jQuery(gdlmls_selected).parent().find("ul.gdlmls-more").empty();
		}
	}
}

function gdlm_ls_neighbourhood_suggestion() {
	var $search = jQuery(gdlmls_selected).val();
	if ($search) {
		jQuery.ajax({
			type: "GET",
			url: geodir_params.api_url + "locations/neighbourhoods/?search=" + $search,
			success: function(data) {
				gdlmls_neighbourhood = data;
				gdlmls_doing_search--;
				gdlm_ls_maybe_suggest_more();
				html = '';
				jQuery.each(gdlmls_neighbourhood, function(index, value) {
					html = html + gdlm_ls_create_li('neighbourhood', value);
				});
				jQuery(gdlmls_selected).parent().find("ul.gdlmls-neighbourhood").empty().append(html);
				if (html && gdlmls_is_search){
					geodirLMShowDropdown(gdlmls_selected);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
			}
		});
	} else {
		gdlmls_neighbourhood = [];
		gdlmls_doing_search--;
		gdlm_ls_maybe_suggest_more();
		jQuery(gdlmls_selected).parent().find("ul.gdlmls-city").empty();
	}
}

function gdlm_ls_city_suggestion() {
	var $search = jQuery(gdlmls_selected).val();
	if ($search) {
		jQuery.ajax({
			type: "GET",
			url: geodir_params.api_url + "locations/cities/?search=" + $search,
			success: function(data) {
				gdlmls_city = data;
				gdlmls_doing_search--;
				gdlm_ls_maybe_suggest_more();
				html = '';
				jQuery.each(gdlmls_city, function(index, value) {
					html = html + gdlm_ls_create_li('city', value);
				});
				jQuery(gdlmls_selected).parent().find("ul.gdlmls-city").empty().append(html);
				if (html && gdlmls_is_search){
					geodirLMShowDropdown(gdlmls_selected);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
			}
		});
	} else {
		gdlmls_city = [];
		gdlmls_doing_search--;
		gdlm_ls_maybe_suggest_more();
		jQuery(gdlmls_selected).parent().find("ul.gdlmls-city").empty();
	}
}

function gdlm_ls_region_suggestion() {
	var $search = jQuery(gdlmls_selected).val();
	if ($search) {
		jQuery.ajax({
			type: "GET",
			url: geodir_params.api_url + "locations/regions/?search=" + $search,
			success: function(data) {
				gdlmls_region = data;
				gdlmls_doing_search--;
				gdlm_ls_maybe_suggest_more();
				html = '';
				jQuery.each(gdlmls_region, function(index, value) {
					html = html + gdlm_ls_create_li('region', value);
				});
				jQuery(gdlmls_selected).parent().find("ul.gdlmls-region").empty().append(html);
				if (html && gdlmls_is_search){
					geodirLMShowDropdown(gdlmls_selected);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
			}
		});
	} else {
		gdlmls_region = [];
		gdlmls_doing_search--;
		gdlm_ls_maybe_suggest_more();
		jQuery(gdlmls_selected).parent().find("ul.gdlmls-region").empty();
	}
}

function gdlm_ls_country_suggestion() {
	var $search = jQuery(gdlmls_selected).val();
	if ($search) {
		jQuery.ajax({
			type: "GET",
			url: geodir_params.api_url + "locations/countries/?search=" + $search,
			success: function(data) {
				gdlmls_country = data;
				gdlmls_doing_search--;
				gdlm_ls_maybe_suggest_more();
				html = '';
				jQuery.each(gdlmls_country, function(index, value) {
					html = html + gdlm_ls_create_li('country', value);
				});

				jQuery(gdlmls_selected).parent().find("ul.gdlmls-country").empty().append(html);
				if (html && gdlmls_is_search){
					geodirLMShowDropdown(gdlmls_selected);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
			}
		});
	} else {
		gdlmls_country = [];
		gdlmls_doing_search--;
		gdlm_ls_maybe_suggest_more();
		jQuery(gdlmls_selected).parent().find("ul.gdlmls-country").empty();
	}
}

function gdlm_ls_current_location_suggestion() {
	jQuery(gdlmls_selected).parent().find("ul.gdlmls-near").empty();

	// Near me
	jQuery(gdlmls_selected).parent().find("ul.gdlmls-near").empty().append(gdlm_ls_create_li('near', {
		type: "near",
		slug: "me",
		title: geodir_params.txt_form_my_location
	}));

	if (jQuery(gdlmls_selected).val() == '') {
		var $search_history = JSON.parse(gdlm_ls_get_location_history());

		if ($search_history) {
			jQuery.each($search_history, function(index, value) {
				jQuery(gdlmls_selected).parent().find("ul.gdlmls-near").append(gdlm_ls_create_li(value.type, value));
			});
		}

		if ( ! geodir_location_params.disable_nearest_cities ) {
			// Add near cities from ip
			gdlm_ls_nearest_cities();
		}
	}
	console.log(JSON.parse(gdlm_ls_get_location_history()));
}

function gdlm_ls_nearest_cities() {
	if(!gdlmls_doing_nearest){
		gdlmls_doing_nearest = true;
		jQuery.ajax({
			type: "GET",
			url: geodir_params.api_url + "locations/cities/?orderby=ip",
			success: function(data) {
				if (data) {
					jQuery.each(data, function(index, value) {
						jQuery(gdlmls_selected).parent().find("ul.gdlmls-near").append(gdlm_ls_create_li('city', value));
					});
				}
				gdlmls_doing_nearest = false;
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				gdlmls_doing_nearest = false;
			}
		});
	}
}

function gdlm_ls_create_li($type,$data){
	var output;
	var history = '';
	var $delete = '';
	var $common_class = '<?php if($design_style){ echo 'list-group-item-action c-pointer px-1 py-1 m-0 d-flex justify-content-between'; }?>';
	if($data.history){
		history = '<i class="fas fa-history" title="<?php _e('Search history','geodirlocation');?>"></i> ';
		$delete = '<span><i onclick="var event = arguments[0] || window.event; geodir_cancelBubble(event);gdlm_ls_del_location_history(\''+$data.slug+'\');jQuery(this)<?php if($design_style){ echo ".parent()"; }?>.parent().remove();" class="fas fa-times" title="<?php esc_attr_e('Remove from history','geodirlocation');?>"></i></span> ';
	}else if($type == 'neighbourhood' || $type == 'city' || $type == 'region' || $type == 'country'){
		history = '<i class="fas fa-map-marker-alt"></i> ';
	}
	console.log($data);
	if($type=='neighbourhood'){
		if($data.area){$data.city = $data.area;}
		output = '<li class="'+$common_class+'" data-type="'+$type+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.title)+'\',\''+gdlm_ls_slashit($data.city)+'\',\''+$data.country_slug+'\',\''+$data.region_slug+'\',\''+$data.city_slug+'\',\''+$data.slug+'\');"><span>'+history+'<?php esc_attr_e( 'In:', 'geodirectory' ); ?> <b>'+ $data.title + '</b>, '+ $data.city + ' <?php esc_attr_e( '(Neighbourhood)', 'geodirlocation' ); ?></span>'+$delete+'</li>';
	}else if($type=='city'){
		if($data.area){$data.region = $data.area;}
		output = '<li class="'+$common_class+'" data-type="'+$type+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.title)+'\',\''+gdlm_ls_slashit($data.region)+'\',\''+$data.country_slug+'\',\''+$data.region_slug+'\',\''+$data.slug+'\');"><span>'+history+'<?php esc_attr_e( 'In:', 'geodirectory' ); ?> <b>'+ $data.title + '</b>, '+$data.region+' <?php esc_attr_e( '(City)', 'geodirlocation' ); ?></span>'+$delete+'</li>';
	}else if($type=='region'){
		if($data.area){$data.country = $data.area;}
		output = '<li class="'+$common_class+'" data-type="'+$type+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.title)+'\',\''+gdlm_ls_slashit($data.country)+'\',\''+$data.country_slug+'\',\''+$data.slug+'\');"><span>'+history+'<?php esc_attr_e( 'In:', 'geodirectory' ); ?> <b>'+ $data.title + '</b>, '+$data.country_title+' <?php esc_attr_e( '(Region)', 'geodirlocation' ); ?></span>'+$delete+'</li>';
	}else if($type=='country'){
		output = '<li class="'+$common_class+'" data-type="'+$type+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.title)+'\',\'\',\''+$data.slug+'\');"><span>'+history+'<?php esc_attr_e( 'In:', 'geodirectory' ); ?> <b>'+ $data.title + '</b> <?php esc_attr_e( '(Country)', 'geodirlocation' ); ?></span>'+$delete+'</li>';
	}else if($type=='near'){
		output = '<li data-type="'+$type+'" class="gd-near-me text-primary '+$common_class+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.title)+'\',\'\',\''+$data.slug+'\');"><span><i class="fas fa-location-arrow"></i> <?php esc_attr_e( 'Near:', 'geodirectory' ); ?> '+ $data.title + '</span></li>';
	}else if($type=='near-search'){
		output = '<li class="'+$common_class+'" data-type="'+$type+'" ontouchstart="this.click();return false;" onclick="gdlm_click_action(\''+$type+'\',\''+gdlm_ls_slashit($data.description)+'\');"><span><i class="fas fa-search"></i> <?php esc_attr_e( 'Near:', 'geodirectory' ); ?> '+ $data.description + '</span></li>';
	}

	return output;
}

function gdlm_click_action($type,$title,$area,$country_slug,$region_slug,$city_slug,$hood_slug){
	if(gdlmls_is_search){
		if($type=='neighbourhood' || $type=='city' || $type=='region' || $type=='country'){
			$slug = '';
			if($type=='neighbourhood'){$slug = $hood_slug;}
			else if($type=='city'){$slug = $city_slug;}
			else if($type=='region'){$slug = $region_slug;}
			else if($type=='country'){$slug = $country_slug;}
			gdlm_search_fill_location($type,$slug,$title);
		}else if($type=='near-search'){
			gdlm_search_fill_location($type,'',$title);
		}else if($type=='near'){
			gd_get_user_position(gdlm_search_near_me);
		}
	}else{
		if($type=='neighbourhood' || $type=='city' || $type=='region' || $type=='country'){
			gdlm_go_location($type,$title,$area,$country_slug,$region_slug,$city_slug,$hood_slug);
		}else if($type=='near-search'){
			gdlm_go_search($title);
		}else if($type=='near'){
			gd_get_user_position(gdlm_ls_near_me);
		}
	}
	setTimeout(function() {
		_ua = navigator.userAgent.toLowerCase();
		isChrome = /chrome/.test(_ua);
		isWin10 = /windows nt 10.0/.test(_ua);
		if (isChrome && isWin10) {
			jQuery(".gdlm-location-suggestions").hide();
		}
	},200);
}

function gdlm_ls_near_me($lat,$lon){
	window.location = geodir_params.location_base_url+"<?php
			global $geodirectory;
			$near_slug = $geodirectory->permalinks->location_near_slug();
			$me_slug = $geodirectory->permalinks->location_me_slug();
			echo "$near_slug/$me_slug/";
			?>"+$lat+","+$lon+"/";
}

function gdlm_ls_near_gps($lat,$lon){
	window.location = geodir_params.location_base_url+"<?php
			global $geodirectory;
			$near_slug = $geodirectory->permalinks->location_near_slug();
			echo "$near_slug/gps/";
			?>"+$lat+","+$lon+"/";
}

function gdlm_search_near_me($lat,$lon){
	gdlm_search_fill_location('near','me',"<?php esc_attr_e( 'Near:', 'geodirectory' ); ?> "+geodir_params.txt_form_my_location,$lat,$lon)
}

function gdlm_search_fill_location($type,$slug,$title,$lat,$lon){
	if($type=='near'){

	}else if($type=='near-search'){
		$type='';
	}else{
		var txtType;
		if ($type == 'country') {
			txtType = '<?php esc_attr_e( '(Country)', 'geodirlocation' ); ?>';
		} else if ($type == 'region') {
			txtType = '<?php esc_attr_e( '(Region)', 'geodirlocation' ); ?>';
		} else if ($type == 'city') {
			txtType = '<?php esc_attr_e( '(City)', 'geodirlocation' ); ?>';
		} else if ($type == 'neighbourhood') {
			txtType = '<?php esc_attr_e( '(Neighbourhood)', 'geodirlocation' ); ?>';
		} else {
			txtType = "("+$type+")";
		}
		$title = "<?php esc_attr_e( 'In:', 'geodirectory' ); ?> "+$title+" "+txtType;
	}

	jQuery('.geodir-location-search-type').val($slug).attr('name', $type);
	jQuery('.sgeo_lat').val($lat);
	jQuery('.sgeo_lon').val($lon);
	jQuery('.snear').val($title).trigger('change'); // fire change event so we can check if we need to add in-location class
}

function gdlm_go_search($text){
	if (window.gdMaps === 'google') {
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({'address': $text},
			function (results, status) {
				if (status == 'OK') {
//							console.log(results);
					$lat = results[0].geometry.location.lat();
					$lon = results[0].geometry.location.lng();
					gdlm_ls_near_gps($lat,$lon);
				} else {
					alert("<?php esc_attr_e('Search was not successful for the following reason :', 'geodirectory');?>" + status);
				}
			});
	} else if (window.gdMaps === 'osm') {
		geocodePositionOSM(false, $text, false, false,
			function(geo) {
				if (typeof geo !== 'undefined' && geo.lat && geo.lon) {
					console.log(results);
				} else {
					alert("<?php esc_attr_e('Search was not successful for the requested address.', 'geodirectory');?>");
				}
			});
	}
}

function gdlm_ls_search_location($type,$term){
	jQuery.ajax({
		type: "GET",
		url: geodir_params.api_url+$type+"/?search="+$term,
		success: function(data) {
			console.log(data);
			return data;
			//jQuery('#' + map_canvas_var + '_loading_div').hide();
			//parse_marker_jason(data, map_canvas_var);
		},
		error: function(xhr, textStatus, errorThrown) {
			console.log(errorThrown);
		}
	});
}

function gdlm_go_location($type,$title,$area,$country_slug,$region_slug,$city_slug,$hood_slug){
	// save search history before redirect
	gdlm_ls_set_location_history($type,$title,$area,$country_slug,$region_slug,$city_slug,$hood_slug);
	window.location = gdlm_ls_location_url($country_slug,$region_slug,$city_slug,$hood_slug);
	//console.log( gdlm_ls_location_url($country_slug,$region_slug,$city_slug,$hood_slug));
}

function gdlm_ls_location_url($country_slug,$region_slug,$city_slug,$hood_slug){
	//$url = geodir_params.location_url.slice(0, -1); // get location url without the ending slash
	$url = geodir_params.location_base_url; // get location url without the ending slash
	var show_country = <?php echo geodir_get_option( 'lm_hide_country_part') ? '0' : '1';?>;
	var show_region = <?php echo geodir_get_option( 'lm_hide_region_part') ? '0' : '1';?>;
	var show_city = <?php echo geodir_get_option( 'lm_hide_city_part') ? '0' : '1';?>;
	var show_hood = <?php echo GeoDir_Location_Neighbourhood::is_active() ? '1' : '0';?>;

	if(show_country && $country_slug){
		$url += ""+$country_slug+"/";
	}

	if(show_region && $region_slug){
		$url += ""+$region_slug+"/";
	}

	if(show_city && $city_slug){
		$url += ""+$city_slug+"/";
	}

	if(show_hood && $hood_slug){
		$url += ""+$hood_slug+"/";
	}

	return $url;
}

function gdlm_ls_get_location_history(){
	if (geodir_is_localstorage() === true) {
		return gdlm_ls_history = localStorage.getItem("gdlm_ls_history");
	}else{
		return '';
	}
}

function gdlm_ls_del_location_history($slug){
	gdlmls_do_not_close = true;
	if (geodir_is_localstorage() === true) {
		gdlm_ls_history = JSON.parse(localStorage.getItem("gdlm_ls_history"));

		var found  = '';
		console.log(gdlm_ls_history);

		jQuery.each(gdlm_ls_history, function(index, value) {
			if($slug && $slug==value.slug){
				// its already in the list so bail.
				//gdlm_ls_history.splice(index, 1);
				found = index;
			}
		});

		if(found!==''){
			gdlm_ls_history.splice(found, 1);
			// store the user selection
			localStorage.setItem("gdlm_ls_history", JSON.stringify(gdlm_ls_history));
		}
	}

	setTimeout(function(){gdlmls_do_not_close = false;}, 200);
}

function gdlm_ls_set_location_history($type,$title,$area,$country_slug,$region_slug,$city_slug,$hood_slug){
	// set a searched location
	if (geodir_is_localstorage() === true) {
		var gdlm_ls_history = localStorage.getItem("gdlm_ls_history");
		var $exists = false;

		if (!gdlm_ls_history || gdlm_ls_history === undefined) {
			gdlm_ls_history = []
		}else{
			gdlm_ls_history = JSON.parse(gdlm_ls_history);
			jQuery.each(gdlm_ls_history, function(index, value) {
				console.log(value);
				if(value.type == $type && value.title==$title){
					// its already in the list so bail.
					$exists = true;
				}
			});
		}

		if(!$exists){
			$slug = $city_slug;
			if($type=='neighbourhood'){
				$slug = $hood_slug;
			}if($type=='city'){
				$slug = $city_slug;
			}else if($type=='region'){
				$slug = $region_slug;
			}else if($type=='country'){
				$slug = $country_slug;
			}

			var $location = {
				history:true, // set it as historical
				type:$type,
				title:$title,
				country_slug:$country_slug,
				region_slug:$region_slug,
				city_slug:$city_slug,
				hood_slug:$hood_slug,
				slug:$slug,
				area:$area
			};
			console.log(gdlm_ls_history);
			console.log($location);
			gdlm_ls_history.unshift($location);
			//gdlm_ls_history = $location;

			// only keep latest 5 searches
			if(gdlm_ls_history.length > 5){
				gdlm_ls_history.pop();
			}
		}

		// store the user selection
		localStorage.setItem("gdlm_ls_history", JSON.stringify(gdlm_ls_history));
	}
}

function gdlm_ls_google_suggestions($search){
	// Create a new session token.
	if(!gdlmls_google_sessionToken){
		gdlmls_google_sessionToken = new google.maps.places.AutocompleteSessionToken();
	}

	// display function
	var displaySuggestions = function(predictions, status) {
		if (status != google.maps.places.PlacesServiceStatus.OK) {
			return;
		}

		console.log(predictions);
		html = '';
		predictions.forEach(function(prediction) {
			html = html + gdlm_ls_create_li('near-search', prediction);
		});
		jQuery(gdlmls_selected).parent().find("ul.gdlmls-more").empty().append(html);
		if (html && gdlmls_is_search){
			geodirLMShowDropdown(gdlmls_selected);
		}
	};

	if(!gdlmls_google_service){
		gdlmls_google_service = new google.maps.places.AutocompleteService();
	}
	gdlmls_google_service.getPlacePredictions({input: $search, sessionToken: gdlmls_google_sessionToken, types: ['geocode']<?php echo $properties; ?>}, displaySuggestions);
}

function gdlm_ls_slashit(str) {
	if (str) {
		str = str.replace(/'/g, "\\'");
	}
	return str;
}

/**
 * Open the location switcher
 */
function geodir_lm_setup_switcher_trigger() {
	var no_show = false;
	// Clear the location and redirect to the base location page
	jQuery(".gdlmls-menu-icon").on("click", function(event){
		no_show = true;
		event.preventDefault();
		<?php if ( $clear_location_url ) { ?>
		window.location = '<?php echo $clear_location_url; ?>';
		<?php } else { ?>
		window.location = geodir_params.location_base_url;
		<?php } ?>
	});

	// detect the menu item location switcher click
	jQuery('a[href$="#location-switcher"]').on("click", function(event){
		// prevent the hash being added to the url
		event.preventDefault();
		// only fire if the click is not the clear location button.
		if(!no_show){
			<?php if ( $design_style ) { ?>
			jQuery('#gdlm-switcher').modal('show').on('shown.bs.modal', function (e) {
				jQuery('.modal .geodir-location-search').trigger("focus").trigger("click");
				// second open might not trigger dropdown so we check.
				setTimeout(function(){
					if( jQuery('.modal .geodir-location-search').attr('aria-expanded')=='false' ){
						jQuery('.modal .geodir-location-search').dropdown('show');
					}
				}, 200);
			});
			<?php } else { ?>
			// init the lightbox
			$lightbox = lity('.geodir-location-search-wrap');
			// add class so we can position
			jQuery($lightbox.element()).addClass('geodir-location-switcher-lightbox');
			// set the focus on the input so it auto loads the nearest cities
			jQuery('.lity-content .geodir-location-search').trigger("focus");
			<?php } ?>
		}
	});
}
function geodirLMShowDropdown(el) {
	/* Windows 11 Edge/Chrome dropdown fix */
	var uA = navigator.userAgent.toLowerCase();
	if (navigator.platform.toLowerCase().indexOf('win') !== -1 && uA.indexOf("windows nt 10") !== -1 && (uA.match(/chrome/i) || uA.match(/edge/i))) {
		jQuery(el).dropdown('show');
		setTimeout(function(){
			jQuery('.gd-search-field-near .gdlm-location-suggestions').css('display',"").show();
		}, 30);
	} else if (jQuery(el).attr('aria-expanded')=='false'){
		jQuery(el).dropdown('show');
	}
}
jQuery(document).ready(function() {geodir_lm_setup_switcher_trigger()});
</script>
<?php
	$script = ob_get_contents();

	ob_end_clean();

	$script = apply_filters( 'geodir_location_switcher_footer_script', trim( $script ) );

	echo $script;
}