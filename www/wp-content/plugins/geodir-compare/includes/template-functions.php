<?php
/**
 * Template functions
 *
 * @since 2.0.0.4
 * @package GeoDir_Location_Manager
 */

/**
 * Content to display when no compare are found.
 *
 */
function geodir_no_compare_found() {

	$design_style = geodir_design_style();

	$template_args = array();

	ob_start();

	if( $design_style ) {
		geodir_get_template( $design_style . '/loop/no-compare-found.php', $template_args, '', plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ). "/templates/" );
	} else {
		geodir_get_template( 'loop/no-compare-found.php', $template_args, '', plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ). "/templates/" );	
	}

	return ob_get_clean();
}

function geodir_compare_list_init_js() {
	global $geodir_compare_list_init_js;
	if ( $geodir_compare_list_init_js ) {
		return;
	}
	$geodir_compare_list_init_js = true;
?>
<script type="text/javascript">/* <![CDATA[ */ 
	jQuery(function($) {
		// Load flexslider if not loaded
		if (!$.flexslider) {
			$.getScript("<?php echo geodir_plugin_url(); ?>/assets/js/jquery.flexslider.min.js?ver=<?php echo GEODIRECTORY_VERSION; ?>", function(data, textStatus, jqxhr) {
				init_read_more();
				geodir_init_lazy_load();
				geodir_refresh_business_hours();
				try { 
					geodir_init_flexslider();
				} catch(e) {
					console.log(e.message);
				}
			});
		} else {
			init_read_more();
			geodir_init_lazy_load();
			geodir_refresh_business_hours();
			try { 
				geodir_init_flexslider();
			} catch(e) {
				console.log(e.message);
			}
		}
	});
/* ]]> */</script>
<?php
}

/**
 * Compare lists & button script.
 */
function geodir_compare_aui_script() {
	global $geodir_compare_aui_script;

	if ( ! empty( $geodir_compare_aui_script ) ) {
		return;
	}

	$geodir_compare_aui_script = true;
	$compare_page = add_query_arg( 'compareids', '0', get_the_permalink( (int) geodir_get_option('geodir_compare_listings_page') ) );
?>
<script type="text/javascript">/* <![CDATA[ */ 
GD_Compare.loader = null;
GD_Compare.addPopup = null;
/**
 * Fetches items from the server
 *
 *
 * @param $items The items to fetch from the server
 */
function geodir_compare_fetch(data) {
    //Close any instance of the popup
    if (GD_Compare.addPopup) {
        GD_Compare.addPopup.remove();
    }
    //Show loading screen
    GD_Compare.loader = aui_modal();
    //Fetch the items from the server
    jQuery.get(geodir_params.ajax_url, data)
        .done(function(html) {
            GD_Compare.addPopup = aui_modal('', html, '', '', '', 'modal-xl');
            // set the title
            setTimeout(function() {
                //do what you need here
            }, 50);
        })
        .fail(function() {
            GD_Compare.addPopup = aui_modal(' ', GD_Compare.ajax_error, '', true);
        })
        .always(function() {
            jQuery('.aui-modal').modal('hide')
        })
}
/**
 * Adds an item to the comparison list
 *
 *
 * @param $post_id The id of the listing to add to the comparison table
 */
function geodir_compare_add($post_id, $post_type, openIn) {
    if ($post_id) {
        var items = {},
            in_compare = false //True if this item is already in a comparison list
        //Are there any items saved in the localstorage?
        if (localStorage.GD_Compare_Items) {
            items = JSON.parse(localStorage.GD_Compare_Items)
        }
        if (!items[$post_type]) {
            items[$post_type] = {}
        }
        //Ajax data
        var data = {
            action: 'geodir_compare_get_items',
            post_type: $post_type,
            added: $post_id
        }
        if (items[$post_type][$post_id] == 1) {
            data.exists = '1'
            in_compare = true
        } else {
            items[$post_type][$post_id] = 1
        }
        localStorage.GD_Compare_Items = JSON.stringify(items)
        data.items = localStorage.GD_Compare_Items
        //Only display a lightbox if the user clicks an item that is already in the comparison list
        if (in_compare) {
            if (openIn == 'tab' || openIn == 'window') {
				var comparePage = '<?php echo esc_url( $compare_page ); ?>';
				comparePage = comparePage.replace("compareids=0", "compareids=" + Object.keys(items[$post_type]).join());
				if (openIn == 'window') {
					window.open(comparePage, '_blank');
				} else {
					window.location = comparePage;
				}
            } else {
                geodir_compare_fetch(data)
            }
        }
    }
    //Update the buttons on the page
    geodir_compare_update_states($post_id);
}
/**
 * Removes an item from the comparison list
 *
 *
 * @param $post_id The id of the listing to remove from the comparison table
 */
function geodir_compare_remove($post_id, $post_type) {
    if ($post_id) {
        var items = {}
        //Are there any items saved in the localstorage?
        if (localStorage.GD_Compare_Items) {
            items = JSON.parse(localStorage.GD_Compare_Items)
        }
        //Are there any items saved in the localstorage?
        if (items[$post_type]) {
            delete items[$post_type][$post_id];
            localStorage.GD_Compare_Items = JSON.stringify(items)
        }
        var data = {
            removed: $post_id,
            action: 'geodir_compare_get_items',
            items: localStorage.GD_Compare_Items,
            post_type: $post_type
        }
        //Update the buttons on the page
        geodir_compare_update_states()
        geodir_compare_fetch(data)
    }
}
/**
 * Removes an item from the comparison table and list
 *
 *
 * @param $post_id The id of the listing to remove from the comparison table
 */
function geodir_compare_remove_from_table($post_id, $post_type) {
    if ($post_id) {
        var items = {},
            is_comparison_page = jQuery(".geodir-compare-page").length,
            listing_ids = []
        //Are there any items saved in the localstorage?
        if (localStorage.GD_Compare_Items) {
            items = JSON.parse(localStorage.GD_Compare_Items)
        }
        //is this saved in local storage
        if (items[$post_type] && items[$post_type][$post_id]) {
            delete items[$post_type][$post_id];
            listing_ids = Object.keys(items[$post_type])
            localStorage.GD_Compare_Items = JSON.stringify(items)
        }
        //Remove it from the table
        jQuery('.geodir-compare-' + $post_id).remove();
        var count = jQuery('.geodir-compare-listing-header.geodir-compare-post').length;
        if (count > 0) {
            jQuery('.geodir-compare-listing-header.geodir-compare-post').css('width', (90 / count) + '%');
        }
        //Trigger resize to recalculate image widths
        jQuery(window).trigger('resize')
        //Update the buttons on the page
        geodir_compare_update_states()
        //Change the window location
        var urlQueryString = document.location.search,
            base_url = [location.protocol, '//', location.host, location.pathname].join(''),
            compareParams = 'compareids=' + listing_ids.join()
        urlQueryString = urlQueryString.replace(new RegExp("\\bcompareids=[^&;]+[&;]?", "gi"), compareParams);
        // remove any leftover crud
        urlQueryString = urlQueryString.replace(/[&;]$/, "");
        //Reload the page, unless the shortcode items are hardcoded
        if (is_comparison_page) {
            window.location = base_url + urlQueryString
        }
    }
}
//If we are on the comparison page and there is nothing to compare, e.g if the user visited the comparison page directly, redirect
if (jQuery(".geodir-compare-page .geodir-compare-page-empty-list").length) {
    var url = window.location.href
    var items = {}
    //Are there any items saved in the localstorage?
    if (localStorage.GD_Compare_Items) {
        items = JSON.parse(localStorage.GD_Compare_Items)
    }
    //Ensure there are compare ids
    if (window.location.href.indexOf('compareids=') == -1 && Object.keys(items).length) {
        var post_type = Object.keys(items)[0],
            params = 'compareids=' + Object.keys(items[post_type]).join(),
            base_url = [location.protocol, '//', location.host, location.pathname].join(''),
            urlQueryString = document.location.search;
        // If the "search" string exists, then build params from it
        if (urlQueryString) {
            params = urlQueryString + '&' + params;
        } else {
            params = '?' + params
        }
        window.location = base_url + params
    }
}
/**
 * Retrieves meta relating to a listing
 *
 *
 * @param el The el of the compare button
 */
function geodir_compare_get_meta(el) {
    return {
        post_type: jQuery(el).data('geodir-compare-post_type'),
        post_id: jQuery(el).data('geodir-compare-post_id'),
        text: jQuery(el).data('geodir-compare-text'),
        icon: jQuery(el).data('geodir-compare-icon'),
        text2: jQuery(el).data('geodir-compared-text'),
        icon2: jQuery(el).data('geodir-compared-icon'),
        add_title: jQuery(el).data('add-title'),
        view_title: jQuery(el).data('view-title')
    }
}
/**
 * Updates the states of the comparison button
 *
 *
 */
function geodir_compare_update_states($post_id) {
    //Abort early if the comparison list is empty
    if (!localStorage.GD_Compare_Items) {
        return;
    }
    var items = JSON.parse(localStorage.GD_Compare_Items);
    //Loop through each button...
    jQuery('.geodir-compare-button').each(function() {
        var meta = geodir_compare_get_meta(this);
        //If this listing has already been added to local storage...
        if (items[meta.post_type] && items[meta.post_type][meta.post_id]) {
            //Opacity
            jQuery(this).css('opacity', '0.8');
            //Change the icon
            jQuery(this).find('i').removeClass(meta.icon).addClass(meta.icon2);
            //Change the text
            jQuery(this).find('.gd-secondary').text(meta.text2);
            // change the title
            jQuery(this).attr("data-original-title", meta.view_title).attr("title", meta.view_title);
        } else {
            //Opacity
            jQuery(this).css('opacity', '1');
            //Change the icon
            jQuery(this).find('i').removeClass(meta.icon2).addClass(meta.icon);
            //Change the text
            jQuery(this).find('.gd-secondary').text(meta.text);
            // change the title
            jQuery(this).attr("data-original-title", meta.add_title).attr("title", meta.add_title);
        }
    });
    // force the tooltip to show new value without mouseout
    if ($post_id) {
        jQuery('[data-geodir-compare-post_id~="' + $post_id + '"]').tooltip('show');
    }
}
//Update the buttons on the page
geodir_compare_update_states();
/* ]]> */</script>
<?php
}