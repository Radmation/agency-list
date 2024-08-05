<?php
/**
 * Plugin core functions.
 *
 * @link       https://wpgeodirectory.com
 * @since      2.0.0
 *
 * @package    GeoDir_Advance_Search_Filters
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_adv_search_distance_unit() {
	$distance_unit = geodir_get_option( 'search_distance_long' );

	if ( $distance_unit != 'km' ) {
		$distance_unit = 'miles';
	}

	return apply_filters( 'geodir_adv_search_distance_unit', $distance_unit );
}

function geodir_search_get_autocomplete_results( $post_type, $keyword ) {
	global $wpdb;

	$max_results = (int) geodir_get_option( 'advs_autocompleter_max_results', 10 );
	$table = geodir_db_cpt_table( $post_type );

	/**
	 * Lets you filter if terms should be included in the advanced search autocompleter or not.
	 *
	 * @since 1.4.93
	 */
	$include_terms = apply_filters( 'geodir_advance_search_autocompleters_terms', true, $post_type, $keyword );

	if ( $include_terms ) {
		$terms_join = "LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id";
		$terms_join = apply_filters( 'geodir_search_autocomplete_terms_join', $terms_join, $post_type, $keyword );

		$terms_where = $wpdb->prepare( "WHERE t.term_id = tt.term_id AND t.name LIKE %s AND tt.taxonomy IN( '" . $post_type . "category', '" . $post_type . "_tags' )", array( '%' . $keyword . '%' ) );
		$terms_where = apply_filters( 'geodir_search_autocomplete_terms_where', $terms_where, $post_type, $keyword );

		// get matching terms
		$terms_query = "SELECT CONCAT( t.name, '|', SUM( count ) ) name, SUM( count ) cnt FROM {$wpdb->terms} t, {$wpdb->term_taxonomy} tt {$terms_join} {$terms_where} GROUP BY t.name ORDER BY cnt DESC LIMIT {$max_results}";
		$terms_query = apply_filters( 'geodir_search_autocomplete_terms_query', $terms_query, $post_type, $keyword );

		$terms = $wpdb->get_results( $terms_query );
	} else {
		$terms = array();
	}

	$join = apply_filters( 'geodir_search_autocomplete_join', "", $post_type, $keyword );

	$statuses = geodir_get_post_stati( 'search', array( 'post_type' => $post_type ) );

	if ( count( $statuses ) > 1 ) {
		$post_status_where = "p.post_status IN( '" . implode( "', '", $statuses ) . "' )";
	} else {
		$post_status_where = "p.post_status = '{$statuses[0]}'";
	}

	$where = $wpdb->prepare( "WHERE {$post_status_where} AND p.post_type = %s AND p.post_date < %s AND p.post_title LIKE %s", array( $post_type, current_time( 'mysql' ), '%' . $keyword . '%' ) );
	$where = apply_filters( 'geodir_search_autocomplete_where', $where, $post_type, $keyword );

	$query = "SELECT p.post_title AS name, p.ID FROM {$wpdb->posts} AS p {$join} {$where} GROUP BY p.ID ORDER BY p.post_title LIMIT {$max_results}";
	$query = apply_filters( 'geodir_search_autocomplete_query', $query, $post_type, $keyword );

	$posts = $wpdb->get_results( $query );

	$items = ! empty( $terms ) ? $terms : array();
	if ( ! empty( $posts ) ) {
		$items = ! empty( $items ) ? array_merge( $items, $posts ) : $posts;
	}

	$results = array();
	if ( ! empty( $items ) ) {
		asort( $items );

		foreach ( $items as $item ) {
			$name = $item->name;
			$name .= isset( $item->ID ) && isset( $item->ID ) > 0 ? '|' . get_permalink( $item->ID ) : '|';

			if ( ! in_array( $name, $results ) ) {
				$results[] = $name;

				if ( count( $results ) == 100 ) {
					break;
				}
			}
		}
	}

	 /*
	 * Filter the autocomplete search for results array.
	 *
	 * @since 1.3.4
	 * @param array $results The results array of results to return.
	 * @param string $post_type The post type being queried.
	 * @param string $keyword Searched keyword.
	 * @param array $items The array of results from the search query.
	 */
	return apply_filters('geodir_advance_search_autocompleters', $results, $post_type, $keyword, $items );
}

##################################### AUTOCOMPLETE ################################

// non class stuff
add_action('wp_footer','geodir_advanced_search_autocomplete_script');
function geodir_advanced_search_autocomplete_script() {
	global $aui_bs5, $geodirectory;

	if ( ! geodir_get_option( 'advs_enable_autocompleter' ) ) {
		return;
	}
	$design_style = geodir_design_style();
	?>
	<script>
		/*
		 Location suggestion schema

		 var = {
		 type: "city",
		 name: "Belfast"
		 }
		 */

		var gdasac_selected = '';
		var gdasac_li_type = '';
		var gdasac_categories = [];
		var gdasac_listings = [];
		var gdasac_do_not_close = false;
		var gdasac_doing_search = 0;
		var gdasac_is_search = false;
		var gdasac_keyup_timeout = null;
		var gdasac_suggestions_with = '<?php echo geodir_get_option( 'advs_search_suggestions_with' ); ?>';

		jQuery(function($) {
			// init
			gdas_ac_init('.gd_search_text');

			<?php if ( $design_style ) { /* Added to prevent undefined popper JS error */ ?>
			if ($('.gd_search_text').length) {
				$('.gd_search_text').each(function() {
					if ( ! $(this).parent().find(".gdas-search-suggestions").length) {
						jQuery(this).after("<div class='dropdown-menu dropdown-caret-0 w-100 scrollbars-ios overflow-auto p-0 m-0 gd-suggestions-dropdown gdas-search-suggestions gd-ios-scrollbars'><ul class='gdasac-listing list-unstyled p-0 m-0'></ul><ul class='gdasac-category list-unstyled p-0 m-0'></ul></div>");
					}
				});
			}
			<?php } ?>

			// on CPT change
			jQuery("body").on("geodir_setup_search_form", function(){
				gdas_ac_init('.gd_search_text');
			});
		});


		function gdas_ac_init($field){
			jQuery($field).on("focusin",
				function(){
					gdasac_selected = this;
					gdas_ac_focus_in(this);
				}).on("focusout",
				function(){
					gdasac_selected = '';
					gdas_ac_focus_out(this);
				});

			// window resize tasks
			jQuery(window).on("resize",function(){
				gdas_ac_resize_suggestions();
			});
		}

		function gdas_ac_focus_in($input){
			var $suggestions = jQuery($input).parent().find(".gdas-search-suggestions"), gdas_fire = false;
			if ($suggestions.length) {
				<?php if ( ! $design_style ) { ?>$suggestions.show();<?php } else { echo 'gdas_fire = true'; } ?>
			} else {
				jQuery($input).after("<div class='<?php if($design_style){ echo "dropdown-menu dropdown-caret-0 w-100  scrollbars-ios overflow-auto p-0 m-0"; } ?> gd-suggestions-dropdown gdas-search-suggestions gd-ios-scrollbars' >" +
					"<ul class='gdasac-listing list-unstyled p-0 m-0 '></ul>" +
					"<ul class='gdasac-category list-unstyled p-0 m-0 '></ul>" +
					"</div>");
				gdas_fire = true;
			}

			/* Fire search */
			if (gdas_fire && !$suggestions.hasClass('gdasac-focused')) {
				$suggestions.addClass('gdasac-focused');
				gdas_ac_init_suggestions($input);
				if ( gdasac_suggestions_with != 'posts' ) {
					gdas_ac_categories($input);
				}
			}

			// resize
			gdas_ac_resize_suggestions();

			// set if is search near
			if(jQuery('.gdlm-location-suggestions:visible').prev().hasClass('snear')){
				gdasac_is_search = true;
			}else{
				gdasac_is_search = false;
			}
		}

		function gdas_ac_focus_out($input){
			setTimeout(function() {
				if (!gdasac_do_not_close) {
				<?php if(!$design_style){ ?>jQuery($input).parent().find(".gdas-search-suggestions").hide();<?php } ?>
				}
			},200);
		}

		/**
		 * Get the current post_type categories as suggestions.
		 *
		 */
		function gdas_ac_categories(el){
			$input = jQuery(gdasac_selected);
			var post_type = jQuery($input).parent().parent().find("input[name='stype']").val();
			var post_type_slug = jQuery($input).closest('.geodir-search').find("input[name='stype']").data("slug");
			if(!post_type_slug) {
				post_type_slug = jQuery($input).closest('.geodir-search').find(".search_by_post").find(':selected').data("slug");
			}
			if (typeof post_type_slug == 'undefined') {
				post_type_slug = jQuery(".search_by_post").find(':selected').data("slug");
			}
			var search = jQuery($input).val();
			if (typeof search == 'undefined') {
				search = '';
			}

			request_url = geodir_params.api_url + "" + post_type_slug+"/categories/?orderby=count&order=desc&search="+search+"&per_page="+geodir_search_params.autocompleter_max_results;
			if (geodir_search_params.autocompleter_filter_location && el && jQuery(el).closest('form.geodir-listing-search').length) {
				$form = jQuery(el).closest('form.geodir-listing-search');
				lname = jQuery('.geodir-location-search-type', $form).prop('name');
				lval = jQuery('.geodir-location-search-type', $form).val();
				if (lval && (lname == 'country' || lname == 'region' || lname == 'city' || lname == 'neighbourhood')) {
					request_url += '&' + lname + '=' + lval;
				}
			}

			jQuery.ajax({
				type: "GET",
				url: request_url,
				dataType: 'json',
				success: function (data) {
					gdasac_categories = data;gdasac_doing_search--;

					html = '';
					gdasac_li_type = 'category';
					jQuery.each(gdasac_categories, function (index, value) {
						html = html + gdas_ac_create_li('category', value);
					});
					var gdasCe = gdasac_selected ? gdasac_selected : el;
					jQuery(gdasCe).parent().find("ul.gdasac-category").empty().append(html);
					<?php if ( $design_style ) { ?>if(html && gdasac_selected && !jQuery(el).closest('form.geodir-listing-search').find('.gdas-search-suggestions').is(':visible')){try{jQuery(gdasCe).dropdown('show');}catch(err){console.log(err.message);}}<?php } ?>
				},
				error: function (xhr, textStatus, errorThrown) {
					console.log(errorThrown);
				}
			});

		}

		/**
		 * Get the current post_type categories as suggestions.
		 *
		 */
		function gdas_ac_listings(el){
			$input = jQuery(gdasac_selected);
			var post_type = jQuery($input).parent().parent().find("input[name='stype']").val();
			var post_type_slug = jQuery($input).closest('.geodir-search').find("input[name='stype']").data("slug");
			if(!post_type_slug) {
				post_type_slug = jQuery($input).closest('.geodir-search').find(".search_by_post").find(':selected').data("slug");
			}
			var search = jQuery($input).val(), gdasLe = gdasac_selected ? gdasac_selected : el;

			if(search && search.length >= geodir_search_params.autocomplete_min_chars){
				request_url = geodir_params.api_url + "" + post_type_slug+"/?search="+search+"&per_page="+geodir_search_params.autocompleter_max_results;
				if (geodir_search_params.autocompleter_filter_location && el && jQuery(el).closest('form.geodir-listing-search').length) {
					$form = jQuery(el).closest('form.geodir-listing-search');
					lname = jQuery('.geodir-location-search-type', $form).prop('name');
					lval = jQuery('.geodir-location-search-type', $form).val();
					if (lval && (lname == 'country' || lname == 'region' || lname == 'city' || lname == 'neighbourhood')) {
						request_url += '&' + lname + '=' + lval;
					}
				}
				jQuery.ajax({
					type: "GET",
					url: request_url,
					dataType: 'json',
					success: function (data) {
						gdasac_listings = data;gdasac_doing_search--;

						html = '';
						gdasac_li_type = 'listing';
						jQuery.each(gdasac_listings, function (index, value) {
							html = html + gdas_ac_create_li('listing', value);
						});
						jQuery(gdasLe).parent().find("ul.gdasac-listing").empty().append(html);
						<?php if ( $design_style ) { ?>if(html && gdasac_selected && !jQuery(el).closest('form.geodir-listing-search').find('.gdas-search-suggestions').is(':visible')){try{jQuery(gdasLe).dropdown('show');}catch(err){console.log(err.message);}}<?php } ?>
					},
					error: function (xhr, textStatus, errorThrown) {
						console.log(errorThrown);
					}
				});
			}else{
				jQuery(gdasLe).parent().find("ul.gdasac-listing").empty();
			}
		}

		/**
		 * set the max height for the suggestion div so to never scroll past the bottom of the page.
		 */
		function gdas_ac_resize_suggestions(){
			setTimeout(function(){
				if(jQuery('.gd-suggestions-dropdown:visible').length){

					var offset = jQuery('.gd-suggestions-dropdown:visible').offset().top;
					var windowHeight = jQuery( window ).height();
					var maxHeight = windowHeight - ( offset - jQuery(window).scrollTop() );
					if(jQuery('.gd-suggestions-dropdown:visible').prev().hasClass('gd_search_text')){
						jQuery('.gd-suggestions-dropdown:visible').css('max-height',windowHeight - 40);
					}else{
						jQuery('.gd-suggestions-dropdown:visible').css('max-height',maxHeight);
					}

				}
			}, 50);
		}

		function gdas_ac_init_suggestions($input){

			setTimeout(function(){
				gdas_ac_resize_suggestions();
			}, 250);

			jQuery($input).on("keyup", function (e) {
				gdasac_doing_search = 3;// city, region, country
				if(gdasac_keyup_timeout != null) clearTimeout(gdasac_keyup_timeout);
				gdasac_keyup_timeout = setTimeout(function(){
					gdas_ac_maybe_fire_suggestions($input);
				},500);
			});
		}

		function gdas_ac_maybe_fire_suggestions(el){
			gdasac_keyup_timeout = null;
			if ( gdasac_suggestions_with != 'terms' ) {
				gdas_ac_listings(el);
			}
			if ( gdasac_suggestions_with != 'posts' ) {
				gdas_ac_categories(el);
			}
		}


		function gdas_ac_create_li($type,$data){

			var output = '';
			var history = '';
			var $delete = '';
			var $common_class = '<?php if($design_style){ echo 'list-group-item-action c-pointer p-0 m-0 d-flex justify-content-start  align-items-center text-muted'; }?>';
			var $common_class_icon = '<?php if($design_style){ echo ' d-flex align-items-center justify-content-center p-0 m-0 ' . ( $aui_bs5 ? ' me-2' : 'mr-2' ); }?>';
			var $common_class_title = '<?php if($design_style){ echo 'dropdown-header h6 p-2 m-0 bg-light'; }?>';
			var $icon_size = '<?php if($design_style){ echo 'height:38px;width:38px;'; }?>';

			if(gdasac_li_type != ''){
				if($type=='category'){
					output += '<li class="gdas-section-title '+$common_class_title+'" onclick="var event = arguments[0] || window.event; geodir_cancelBubble(event);"><?php esc_attr_e( 'Categories', 'geodirectory' ); ?></li>';
				}else if($type=='listing'){
					output += '<li class="gdas-section-title '+$common_class_title+'"><?php esc_attr_e( 'Listings', 'geodirectory' ); ?></li>';
				}else{
					output += '<li class="gdas-section-title '+$common_class_title+'">'+$type.charAt(0).toUpperCase() + $type.slice(1)+'</li>';
				}
			}

			gdasac_li_type = '';


			if($data.history){
				history = '<i class="far fa-clock" title="<?php _e('Search history','geodirlocation');?>"></i> ';
				$delete = '<i onclick="var event = arguments[0] || window.event; geodir_cancelBubble(event);gdas_ac_del_location_history(\''+$data.slug+'\');jQuery(this).parent().remove();" class="fas fa-times " title="<?php esc_attr_e('Remove from history','geodirlocation');?>"></i> ';
			}else if($type == 'category' && $data.fa_icon){
				var icon_color = $data.fa_icon_color ? '#fff' : '';
				history = '<span class="gdasac-icon '+$common_class_icon+'" style="background-color:'+$data.fa_icon_color+';color:'+icon_color+';'+$icon_size+'"><i class="'+$data.fa_icon+' fa-fw"></i></span> ';
			}else if($type == 'category'){
				history = '<span class="gdasac-icon '+$common_class_icon+'" style="'+$icon_size+'"><i class="fas fa-folder-open"></i></span> ';
			}else if($type == 'listing' && $data.featured_image.thumbnail){
				history = '<span class="gdasac-icon '+$common_class_icon+'" style="'+$icon_size+'"><img src="'+$data.featured_image.thumbnail+'" class="w-100" /></span> ';
			}else{
				history = '<span class="gdasac-icon '+$common_class_icon+'" style="'+$icon_size+'"><i class="fas fa-map-marker-alt"></i></span> ';
			}
			if($type=='category'){
				if($data.area){$data.city = $data.area;}
				output += '<li class="'+$common_class+'" data-type="'+$type+'" onclick="gdasac_click_action(\''+$type+'\',\''+$data.link+'\');">'+history+'<b>'+ $data.name + '</b>'+$delete+'</li>';
			}else if($type=='listing'){
				if($data.area){$data.region = $data.area;}
				output += '<li class="'+$common_class+'" data-type="'+$type+'" onclick="gdasac_click_action(\''+$type+'\',\''+$data.link+'\');">'+history+'<b>'+ $data.title.rendered + '</b>'+$delete+'</li>';
			}

			return output;
		}

		function gdasac_click_action($type,$url){

			if($type=='category'){
				// add location
				/*if(geodir_search_params.autocompleter_filter_location) {
					var current_location = geodir_params.location_url;
					$location_string = current_location.replace(geodir_params.location_base_url, "");
					$url += $url.endsWith("/") ? "" : "/";
					$url += $location_string;
				}*/
				window.location = $url
			}else if($type=='listing'){
				window.location = $url
			}
		}

	</script>
	<?php
}

/**
 * Add the required data attributes to the search input if autocomplete is enabled.
 *
 * @param $args
 *
 * @return mixed
 */
function geodir_search_enable_dropdown( $args ) {
	global $aui_bs5;

	if ( geodir_get_option( 'advs_enable_autocompleter' ) && geodir_design_style() ) {
		$bs = $aui_bs5 ? 'bs-' : '';

		$args['extra_attributes']['data-' . $bs . 'toggle'] = 'dropdown';
		$args['extra_attributes']['data-' . $bs . 'flip'] = 'false';
	}

	return $args;
}
add_filter('geodir_search_for_input_args','geodir_search_enable_dropdown');

/**
 * Schedule events.
 *
 * @since 2.0.1.0
 */
function geodir_search_schedule_events() {
	if ( ! wp_next_scheduled( 'geodir_search_schedule_adjust_business_hours_dst' ) ) {
		// Daily at 1 AM
		wp_schedule_event( strtotime( date( 'Y-m-d 01:00:00' ) ), apply_filters( 'geodir_search_schedule_recurrence_adjust_business_hours_dst', 'daily' ), 'geodir_search_schedule_adjust_business_hours_dst' );
	}
}

/**
 * Merge business hours for posts.
 *
 * @param bool $force True to merge business hours for all posts. Default False.
 * @return int No. of post business hours merged.
 */
function geodir_search_merge_business_hours( $force = false ) {
	$post_types = geodir_get_posttypes();

	$merged = 0;

	if ( ! empty( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			$merged += (int) GeoDir_Adv_Search_Business_Hours::cpt_merge_business_hours( $post_type, $force );
		}
	}

	return $merged;
}

function geodir_search_tool_merge_business_hours() {
	$merged = (int) geodir_search_merge_business_hours();

	if ( $merged > 0 ) {
		$message = wp_sprintf( _n( 'Business hours merged for %d post.', 'Business hours merged for %d posts.', $merged, 'geodiradvancesearch' ), $merged );
	} else {
		$message = __( 'No post to merge business hours.', 'geodiradvancesearch' );
	}

	return $message;
}

function geodir_search_tool_adjust_business_hours_dst() {
	$items = (int) GeoDir_Adv_Search_Business_Hours::adjust_business_hours_dst();

	if ( $items > 0 ) {
		$message = wp_sprintf( _n( 'Business hours adjusted for %d item with daylight saving time.', 'Business hours adjusted for %d items with daylight saving time.', $items, 'geodiradvancesearch' ), $items );
	} else {
		$message = __( 'No item to adjust business hours with daylight saving time.', 'geodiradvancesearch' );
	}

	return $message;
}

/**
 * Get conditional field attributes.
 *
 * @since 2.2.5
 *
 * @param object $field Field object.
 * @param string $_key Field key. Default empty.
 * @param string $_type Field type. Default empty.
 * @return string Condition fields attributes.
 */
function geodir_search_conditional_field_attrs( $field, $_key = '', $_type = '' ) {
	$attrs = '';

	if ( ! geodir_design_style() ) {
		return $attrs;
	}

	if ( is_object( $field ) ) {
		$key = ! empty( $field->htmlvar_name ) ? $field->htmlvar_name : '';
		$type = ! empty( $field->field_type ) ? $field->field_type : '';
		$extra_fields = ! empty( $field->extra_fields ) ? $field->extra_fields : '';
	} else if ( is_array( $field ) ) {
		$key = ! empty( $field['htmlvar_name'] ) ? $field['htmlvar_name'] : '';
		$type = ! empty( $field['field_type'] ) ? $field['field_type'] : '';
		$extra_fields = ! empty( $field['extra_fields'] ) ? $field['extra_fields'] : '';
	} else {
		return $attrs;
	}

	if ( $key == 'business_hours' ) {
		$key = 'open_now';
		$type = 'select';
	}  else if ( $key == 'distance' ) {
		$key = 'dist';

		if ( empty( $field->main_search ) ) {
			$type = 'radio';
		} else {
			$type = 'hidden';
		}
	}

	if ( in_array( $type, array( 'event', 'email', 'phone', 'url' ) ) ) {
		$type = 'text';
	}

	if ( $_key ) {
		$key = $_key;
	}

	if ( $_type ) {
		$type = $_type;
	}

	$extra_fields = maybe_unserialize( $extra_fields );

	if ( is_array( $extra_fields ) && ! empty( $extra_fields['cat_display_type'] ) && $key != 'default_category' ) {
		$type = $extra_fields['cat_display_type'];
	}

	$conditions = geodir_parse_field_conditions( $extra_fields );

	return geodir_build_conditional_attrs( $conditions, $key, $type );
}

/**
 * Check GD Booking plugin active or not.
 *
 * @since 2.2.8
 *
 * @return bool True if active else False.
 */
function geodir_search_booking_active() {
	return class_exists( 'GeoDir_Booking' ) ? true : false;
}

function geodir_search_sanitize_date_range( $dates ) {
	$sanitized = array(
		'start' => '',
		'end' => '',
		'dates' => array(),
		'days' => array()
	);

	if ( empty( $dates ) || ! is_scalar( $dates ) ) {
		return $sanitized;
	}

	if ( strpos( $dates, ' to ' ) > 0 || strpos( $dates, __( ' to ', 'geodirectory' ) ) > 0 ) {
		$_dates = strpos( $dates, __( ' to ', 'geodirectory' ) ) > 0 ? explode( __( ' to ', 'geodirectory' ), $dates, 2 ) : explode( ' to ', $dates, 2 );

		if ( ! empty( $_dates[0] ) && ( $check_in = sanitize_text_field( $_dates[0] ) ) ) {
			$sanitized['start'] = date_i18n( 'Y-m-d', strtotime( $check_in ) );
		}

		if ( ! empty( $_dates[1] ) && ( $check_out = sanitize_text_field( $_dates[1] ) ) ) {
			$sanitized['end'] = date_i18n( 'Y-m-d', strtotime( $check_out ) );
		}
	} else {
		if ( $check_in = sanitize_text_field( $dates ) ) {
			$sanitized['start'] = date_i18n( 'Y-m-d', strtotime( $check_in ) );
		}
	}

	$sanitized['dates'] = geodir_search_days_between_dates( $sanitized['start'], $sanitized['end'] );

	if ( ! empty( $sanitized['dates'] ) ) {
		foreach ( $sanitized['dates'] as $date ) {
			$time = strtotime( $date );

			$sanitized['days'][ (int) date_i18n( 'Y', $time ) ][] = ( (int) date_i18n( 'z', $time ) ) + 1;
		}
	}

	return $sanitized;
}

function geodir_search_days_between_dates( $start_date = '', $end_date = '' ) {
	$days = array();

	if ( ! empty( $start_date ) ) {
		if ( ! empty( $end_date ) ) {
			$start_date_time = strtotime( $start_date );
			$end_date_time = strtotime( $end_date );

			for ( $time = $start_date_time; $time <= $end_date_time; $time += 86400 ) {
				$days[] = date_i18n( 'Y-m-d', $time );
			}

			if ( ! empty( $days ) ) {
				$days = array_unique( $days );
			}
		} else {
			$days[] = $start_date;
		}
	} else if ( ! empty( $end_date ) ) {
		$days[] = $end_date;
	}

	return $days;
}