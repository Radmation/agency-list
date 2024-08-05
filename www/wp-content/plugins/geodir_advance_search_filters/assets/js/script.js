/*
 original jQuery plugin by http://www.pengoworks.com/workshop/jquery/autocomplete.htm
 just replaced $ with jQuery in order to be complaint with other JavaScript libraries.
 */
jQuery.autocomplete_gd = function(input, options) {
    // Create a link to self
    var me = this;

    // Create jQuery object for input element
    var $input = jQuery(input).attr("autocomplete", "off");

    // Apply inputClass if necessary
    if (options.inputClass) $input.addClass(options.inputClass);

    // Create results
    var results = document.createElement("div");
    // Create jQuery object for results
    var $results = jQuery(results);
    $results.show().addClass(options.resultsClass).css("position", "absolute");
    if (options.width > 0) $results.css("width", options.width);

    // Add to body element
    jQuery("body").append(results);

    input.autocompleter = me;

    var timeout = null;
    var prev = "";
    var active = -1;
    var cache = {};
    var keyb = false;
    var hasFocus = false;
    var lastKeyPressCode = null;

    // flush cache
    function flushCache() {
        cache = {};
        cache.data = {};
        cache.length = 0;
    }

    // flush cache
    flushCache();

    // if there is a data array supplied
    if (options.data != null) {
        var sFirstChar = "",
            stMatchSets = {},
            row = [];

        // no url was specified, we need to adjust the cache length to make sure it fits the local data store
        if (typeof options.url != "string") options.cacheLength = 1;

        // loop through the array and create a lookup structure
        for (var i = 0; i < options.data.length; i++) {
            // if row is a string, make an array otherwise just reference the array
            row = ((typeof options.data[i] == "string") ? [options.data[i]] : options.data[i]);

            // if the length is zero, don't add to list
            if (row[0].length > 0) {
                // get the first character
                sFirstChar = row[0].substring(0, 1).toLowerCase();
                // if no lookup array for this character exists, look it up now
                if (!stMatchSets[sFirstChar]) stMatchSets[sFirstChar] = [];
                // if the match is a string
                stMatchSets[sFirstChar].push(row);
            }
        }

        // add the data items to the cache
        for (var k in stMatchSets) {
            // increase the cache size
            options.cacheLength++;
            // add to the cache
            addToCache(k, stMatchSets[k]);
        }
    }

    $input
        .keydown(function(e) {
            // track last key pressed
            lastKeyPressCode = e.keyCode;
            switch (e.keyCode) {
                case 38: // up
                    e.preventDefault();
                    moveSelect(-1);
                    break;
                case 40: // down
                    e.preventDefault();
                    moveSelect(1);
                    break;
                case 9: // tab
                case 13: // return
                    if (selectCurrent()) {
                        // make sure to blur off the current field
                        $input.get(0).blur();
                        e.preventDefault();
                    }
                    break;
                default:
                    active = -1;
                    if (timeout) clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        onChange();
                    }, options.delay);
                    break;
            }
        })
        .focus(function() {
            // track whether the field has focus, we shouldn't process any results if the field no longer has focus
            hasFocus = true;
        })
        .blur(function() {
            // track whether the field has focus
            hasFocus = false;
            hideResults();
        });

    hideResultsNow();

    function onChange() {
        // ignore if the following keys are pressed: [del] [shift] [capslock]
        if (lastKeyPressCode == 46 || (lastKeyPressCode > 8 && lastKeyPressCode < 32)) return $results.show();
        var v = $input.val();
        if (v == prev) return;
        prev = v;
        if (v.length >= options.minChars) {
            $input.addClass(options.loadingClass);
            requestData(v);
        } else {
            $input.removeClass(options.loadingClass);
            $results.show();
        }
    }

    function moveSelect(step) {

        var lis = jQuery("li", results);
        if (!lis) return;

        active += step;

        if (active < 0) {
            active = 0;
        } else if (active >= lis.size()) {
            active = lis.size() - 1;
        }

        lis.removeClass("ac_over");

        jQuery(lis[active]).addClass("ac_over");

        // Weird behaviour in IE
        // if (lis[active] && lis[active].scrollIntoView) {
        // 	lis[active].scrollIntoView(false);
        // }

    }

    function selectCurrent() {
        var li = jQuery("li.ac_over", results)[0];
        if (!li) {
            var $li = jQuery("li", results);
            if (options.selectOnly) {
                if ($li.length == 1) li = $li[0];
            } else if (options.selectFirst) {
                li = $li[0];
            }
        }
        if (li) {
            selectItem(li);
            return true;
        } else {
            return false;
        }
    }

    function selectItem(li) {
        if (!li) {
            li = document.createElement("li");
            li.extra = [];
            li.selectValue = "";
        }
        var v = jQuery.trim(li.selectValue ? li.selectValue : li.innerHTML);
        input.lastSelected = v;
        prev = v;
        $results.html("");
        $input.val(v);
        hideResultsNow();

        if (options.onItemSelect) setTimeout(function() {
            options.onItemSelect(li, $input.parents("form"))
        }, 1);
    }

    // selects a portion of the input string
    function createSelection(start, end) {
        // get a reference to the input element
        var field = $input.get(0);
        if (field.createTextRange) {
            var selRange = field.createTextRange();
            selRange.collapse(true);
            selRange.moveStart("character", start);
            selRange.moveEnd("character", end);
            selRange.select();
        } else if (field.setSelectionRange) {
            field.setSelectionRange(start, end);
        } else {
            if (field.selectionStart) {
                field.selectionStart = start;
                field.selectionEnd = end;
            }
        }
        field.focus();
    }

    // fills in the input box w/the first match (assumed to be the best match)
    function autoFill(sValue) {
        // if the last user key pressed was backspace, don't autofill
        if (lastKeyPressCode != 8) {
            // fill in the value (keep the case the user has typed)
            $input.val($input.val() + sValue.substring(prev.length));
            // select the portion of the value not typed by the user (so the next character will erase)
            createSelection(prev.length, sValue.length);
        }
    }

    function showResults() {
        $results.appendTo('body');

        //jQuery('.'+options.resultsClass).css({display: "block"}); /* add script on 25-04-2014 */
        $results.css({
            display: "block"
        }); /* added script on 03-08-2017 */

        // get the position of the input field right now (in case the DOM is shifted)
        var pos = findPos(input);

        // either use the specified width, or autocalculate based on form element
        var iWidth = (options.width > 0) ? options.width : $input.outerWidth();

        // reposition
        $results.css({
            width: parseInt(iWidth) + "px",
            top: $input.offset().top + $input.outerHeight(true),
            left: pos.x + "px"
        }).show();
    }

    function hideResults() {

        if (jQuery('.ac_results:hover').length != 0) {
            return setTimeout(hideResults, 100);
        }

        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(hideResultsNow, 200);
    }

    function hideResultsNow() {
        //jQuery('.'+options.resultsClass).css({display: "none"});/* add script on 25-04-2014 */
        $results.css({
            display: "none"
        }); /* added script on 03-08-2017 */

        if (timeout) clearTimeout(timeout);
        $input.removeClass(options.loadingClass);
        if ($results.is(":visible")) {
            $results.show();
        }
        if (options.mustMatch) {
            var v = $input.val();
            if (v != input.lastSelected) {
                selectItem(null);
            }
        }
    }

    function receiveData(q, data) {
        if (data) {
            $input.removeClass(options.loadingClass);
            results.innerHTML = "";

            // if the field no longer has focus or if there are no matches, do not display the drop down
            if (!hasFocus || data.length == 0) return hideResultsNow();

            if (jQuery.browser.msie) {
                // we put a styled iframe behind the calendar so HTML SELECT elements don't show through
                $results.append(document.createElement('iframe'));
            }
            results.appendChild(dataToDom(data));
            // autofill in the complete box w/the first match as long as the user hasn't entered in more data
            if (options.autoFill && ($input.val().toLowerCase() == q.toLowerCase())) autoFill(data[0][0]);
            showResults();
        } else {
            hideResultsNow();
        }
    }

    function parseData(data) {
        if (!data) return null;
        var parsed = [];
        var rows = data.split(options.lineSeparator);
        for (var i = 0; i < rows.length; i++) {
            var row = jQuery.trim(rows[i]);
            if (row) {
                parsed[parsed.length] = row.split(options.cellSeparator);
            }
        }
        return parsed;
    }

    function dataToDom(data) {
        var ul = document.createElement("ul");
        var num = data.length;

        // limited results to a max number
        if ((options.maxItemsToShow > 0) && (options.maxItemsToShow < num)) num = options.maxItemsToShow;

        for (var i = 0; i < num; i++) {
            var row = data[i];
            if (!row) continue;
            var li = document.createElement("li");
            if (options.formatItem) {
                li.innerHTML = options.formatItem(row, i, num);
                li.selectValue = row[0];
            } else {
                li.innerHTML = row[0];
                li.selectValue = row[0];
            }
            var extra = null;
            if (row.length > 1) {
                extra = [];
                for (var j = 1; j < row.length; j++) {
                    extra[extra.length] = row[j];
                }
            }
            li.extra = extra;
            ul.appendChild(li);
            jQuery(li).on("hover",
                function() {
                    jQuery("li", ul).removeClass("ac_over");
                    jQuery(this).addClass("ac_over");
                    active = jQuery("li", ul).indexOf(jQuery(this).get(0));
                },
                function() {
                    jQuery(this).removeClass("ac_over");
                }
            ).on("click",function(e) {
                e.preventDefault();
                e.stopPropagation();
                selectItem(this)
            });
        }
        return ul;
    }

    function requestData(q) {
        if (!options.matchCase) q = q.toLowerCase();
        var data = options.cacheLength ? loadFromCache(q) : null;
        // receive the cached data
        //alert(data);
        if (data && data.length) {
            receiveData(q, data);
            // if an AJAX url has been supplied, try loading the data now
        } else if ((typeof options.url == "string") && (options.url.length > 0)) {

            jQuery.ajax({
                // url: url,
                url: makeUrl(q),
                type: 'GET',
                dataType: 'html',
                beforeSend: function() {
                    geodir_search_wait(1);
                },
                success: function(data, textStatus, xhr) {
                    data = parseData(data);
                    addToCache(q, data);
                    receiveData(q, data);
                    geodir_search_wait(0);
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(textStatus);
                    geodir_search_wait(0);
                }
            });

            // if there's been no data found, remove the loading class
        } else {
            $input.removeClass(options.loadingClass);
        }
    }

    function makeUrl(q) {
        var sform = jQuery($input).closest('form');
        var url = options.url + "&q=" + encodeURI(q);
        for (var i in options.extraParams) {
            url += "&" + i + "=" + encodeURI(options.extraParams[i]);
        }

        if ($input.attr('name') == geodir_search_params.autocomplete_field_name) {
            if (sform.find('[name="set_location_type"]').length) {
                url += "&_ltype=" + encodeURI(sform.find('[name="set_location_type"]').val());
            }
            if (sform.find('[name="set_location_val"]').length) {
                url += "&_lval=" + encodeURI(sform.find('[name="set_location_val"]').val());
            }
            if (sform.find('[name="gd_hood_s"]').length) {
                url += "&_lhood=" + encodeURI(sform.find('[name="gd_hood_s"]').val());
            }
        }
        return url;
    }

    function loadFromCache(q) {
        if (gdNearChanged) { // Remove cache on location change
            flushCache();
            gdNearChanged = false;
            return null;
        }
        if (!q) return null;
        if (cache.data[q]) return cache.data[q];
        if (options.matchSubset) {
            for (var i = q.length - 1; i >= options.minChars; i--) {
                var qs = q.substr(0, i);
                var c = cache.data[qs];
                if (c) {
                    var csub = [];
                    for (var j = 0; j < c.length; j++) {
                        var x = c[j];
                        var x0 = x[0];
                        if (matchSubset(x0, q)) {
                            csub[csub.length] = x;
                        }
                    }
                    return csub;
                }
            }
        }
        return null;
    }

    function matchSubset(s, sub) {
        if (!options.matchCase) s = s.toLowerCase();
        var i = s.indexOf(sub);
        if (i == -1) return false;
        return i == 0 || options.matchContains;
    }

    this.flushCache = function() {
        flushCache();
    };

    this.setExtraParams = function(p) {
        options.extraParams = p;
    };

    this.findValue = function() {
        var q = $input.val();

        if (!options.matchCase) q = q.toLowerCase();
        var data = options.cacheLength ? loadFromCache(q) : null;
        if (data && data.length) {
            findValueCallback(q, data);
        } else if ((typeof options.url == "string") && (options.url.length > 0)) {
            jQuery.get(makeUrl(q), function(data) {
                data = parseData(data)
                addToCache(q, data);
                findValueCallback(q, data);
            });
        } else {
            // no matches
            findValueCallback(q, null);
        }
    };

    function findValueCallback(q, data) {
        if (data) $input.removeClass(options.loadingClass);

        var num = (data) ? data.length : 0;
        var li = null;

        for (var i = 0; i < num; i++) {
            var row = data[i];

            if (row[0].toLowerCase() == q.toLowerCase()) {
                li = document.createElement("li");
                if (options.formatItem) {
                    li.innerHTML = options.formatItem(row, i, num);
                    li.selectValue = row[0];
                } else {
                    li.innerHTML = row[0];
                    li.selectValue = row[0];
                }
                var extra = null;
                if (row.length > 1) {
                    extra = [];
                    for (var j = 1; j < row.length; j++) {
                        extra[extra.length] = row[j];
                    }
                }
                li.extra = extra;
            }
        }

        if (options.onFindValue) setTimeout(function() {
            options.onFindValue(li)
        }, 1);
    }

    function addToCache(q, data) {
        if (!data || !q || !options.cacheLength) return;
        if (!cache.length || cache.length > options.cacheLength) {
            flushCache();
            cache.length++;
        } else if (!cache[q]) {
            cache.length++;
        }
        cache.data[q] = data;
    }

    function findPos(obj) {
        var curleft = obj.offsetLeft || 0;
        var curtop = obj.offsetTop || 0;
        while (obj = obj.offsetParent) {
            curleft += obj.offsetLeft
            curtop += obj.offsetTop
        }
        return {
            x: curleft,
            y: curtop
        };
    }
};

jQuery.fn.autocomplete_gd = function(url, options, data) {
    // Make sure options exists
    options = options || {};
    // Set url as option
    options.url = url;
    // set some bulk local data
    options.data = ((typeof data == "object") && (data.constructor == Array)) ? data : null;

    // Set default values for required options
    options.inputClass = options.inputClass || "ac_input";
    options.resultsClass = options.resultsClass || "ac_results";
    options.lineSeparator = options.lineSeparator || "\n";
    options.cellSeparator = options.cellSeparator || "|";
    options.minChars = options.minChars || 1;
    options.delay = options.delay || 400;
    options.matchCase = options.matchCase || 0;
    options.matchSubset = options.matchSubset || 1;
    options.matchContains = options.matchContains || 0;
    options.cacheLength = options.cacheLength || 1;
    options.mustMatch = options.mustMatch || 0;
    options.extraParams = options.extraParams || {};
    options.loadingClass = options.loadingClass || "ac_loading";
    options.selectFirst = options.selectFirst || false;
    options.selectOnly = options.selectOnly || false;
    options.maxItemsToShow = options.maxItemsToShow || -1;
    options.autoFill = options.autoFill || false;
    options.width = parseInt(options.width, 10) || 0;

    this.each(function() {
        var input = this;
        new jQuery.autocomplete_gd(input, options);
    });

    // Don't break the chain
    return this;
}

jQuery.fn.autocompleteArray = function(data, options) {
    return this.autocomplete_gd(null, options, data);
}

jQuery.fn.indexOf = function(e) {
    for (var i = 0; i < this.length; i++) {
        if (this[i] == e) return i;
    }
    return -1;
};

// RUN THIS ASAP
(function() {}());

/*
 * jQuery dropdown: A simple dropdown plugin
 *
 * MODIFIED FOR GEODIRECTORY
 *
 * Copyright A Beautiful Site, LLC. (http://www.abeautifulsite.net/)
 *
 * Licensed under the MIT license: http://opensource.org/licenses/MIT
 *
 */
jQuery && function(t) {
    function o(o, d) {
        var n = o ? t(this) : d,
            a = t(n.attr("data-dropdown")),
            s = n.hasClass("dropdown-open");
        if (o) {
            if (t(o.target).hasClass("dropdown-ignore")) return;
            o.preventDefault(), o.stopPropagation()
        } else if (n !== d.target && t(d.target).hasClass("dropdown-ignore")) return;
        r(), s || n.hasClass("dropdown-disabled") || (n.addClass("dropdown-open"), a.data("dropdown-trigger", n).show(), e(), a.trigger("show", {
            dropdown: a,
            trigger: n
        }))
    }

    function r(o) {
        var r = o ? t(o.target).parents().addBack() : null;
        if (r && r.is("div.gd-dropdown")) {
            if (!r.is(".dropdown-menu")) return;
            if (!r.is("A")) return
        }
        t(document).find("div.gd-dropdown:visible").each(function() {
            var o = t(this);
            o.hide().removeData("dropdown-trigger").trigger("hide", {
                dropdown: o
            })
        }), t(document).find(".dropdown-open").removeClass("dropdown-open")
    }

    function e() {
        var o = t(".gd-dropdown:visible").eq(0),
            r = o.data("dropdown-trigger"),
            e = r ? parseInt(r.attr("data-horizontal-offset") || 0, 10) : null,
            d = r ? parseInt(r.attr("data-vertical-offset") || 0, 10) : null;

        jQuery(o).appendTo('body');
        0 !== o.length && r && o.css(o.hasClass("dropdown-relative") ? {
            left: o.hasClass("dropdown-anchor-right") ? r.position().left - (o.outerWidth(!0) - r.outerWidth(!0)) - parseInt(r.css("margin-right"), 10) + e : r.position().left + parseInt(r.css("margin-left"), 10) + e,
            top: r.position().top + r.outerHeight(!0) - parseInt(r.css("margin-top"), 10) + d
        } : {
            left: o.hasClass("dropdown-anchor-right") ? r.offset().left - (o.outerWidth() - r.outerWidth()) + e : r.offset().left + e,
            top: r.offset().top + r.outerHeight(true) + d
        })
    }
    t.extend(t.fn, {
        dropdown: function(e, d) {
            switch (e) {
                case "show":
                    return o(null, t(this)), t(this);
                case "hide":
                    return r(), t(this);
                case "attach":
                    return t(this).attr("data-dropdown", d);
                case "detach":
                    return r(), t(this).removeAttr("data-dropdown");
                case "disable":
                    return t(this).addClass("dropdown-disabled");
                case "enable":
                    return r(), t(this).removeClass("dropdown-disabled")
            }
        }
    }), t(document).on("click.dropdown", "[data-dropdown]", o), t(document).on("click.dropdown", r), t(window).on("resize", e)
}(jQuery);

/*** CUSTOM FUNCTIONS ***/
// RUN THIS ON LOAD
jQuery(function($) {
    gdsText = jQuery('input[type="button"].geodir_submit_search').val();

    //setup advanced search form on load
    geodir_search_setup_advance_search();

    //setup advanced search form on form ajax load
    jQuery("body").on("geodir_setup_search_form", function() {
        geodir_search_setup_advance_search();
    });

	/* @todo move to LMv2 */
    geodir_search_onload_redirect();

	if ($('.geodir-search-container form').length) {
		$("body").on("geodir_setup_search_form", function(){
			geodir_search_form_setup_dates();
		});

		geodir_search_form_setup_dates();
		geodir_search_setup_searched_filters();
	}

	/* Refresh Open Now time */
	if ($('.geodir-search-container select[name="sopen_now"]').length) {
		setInterval(function(e) {
			geodir_search_refresh_open_now_times();
		}, 60000);
		geodir_search_refresh_open_now_times();
	}
});

function geodir_search_refresh_open_now_times() {
    jQuery('.geodir-search-container select[name="sopen_now"]').each(function() {
        geodir_search_refresh_open_now_time(jQuery(this));
    });
}

function geodir_search_refresh_open_now_time($this) {
	var $option = $this.find('option[value="now"]'), label, value, d, date_now, time, $label, open_now_format = geodir_search_params.open_now_format;
	if ($option.length && open_now_format) {
		if ($option.data('bkp-text')) {
			label = $option.data('bkp-text');
		} else {
			label = $option.text();
			$option.attr('data-bkp-text', label);
		}
		d = new Date();
		date_now = d.getFullYear() + '-' + (("0" + (d.getMonth()+1)).slice(-2)) + '-' + (("0" + (d.getDate())).slice(-2)) + 'T' + (("0" + (d.getHours())).slice(-2)) + ':' + (("0" + (d.getMinutes())).slice(-2)) + ':' + (("0" + (d.getSeconds())).slice(-2));
		time = geodir_search_format_time(d);
		open_now = geodir_search_params.open_now_format;
		open_now = open_now.replace("{label}", label);
		open_now = open_now.replace("{time}", time);
		$option.text(open_now);
		$option.closest('select').data('date-now',date_now);
		/* Searched label */
		$label = jQuery('.gd-adv-search-open_now .gd-adv-search-label-t');
		if (jQuery('.gd-adv-search-open_now').length && jQuery('.gd-adv-search-open_now').data('value') == 'now') {
			if ($label.data('bkp-text')) {
				label = $label.data('bkp-text');
			} else {
				label = $label.text();
				$label.attr('data-bkp-text', label);
			}
			open_now = geodir_search_params.open_now_format;
			open_now = open_now.replace("{label}", label);
			open_now = open_now.replace("{time}", time);
			$label.text(open_now);
		}
	}
}

function geodir_search_format_time(d) {
	var format = geodir_search_params.time_format, am_pm = eval(geodir_search_params.am_pm), hours, aL, aU;

	hours = d.getHours();
	if (hours < 12) {
		aL = 0;
		aU = 1;
	} else {
		hours = hours > 12 ? hours - 12 : hours;
		aL = 2;
		aU = 3;
	}

	time = format.replace("g", hours);
	time = time.replace("G", (d.getHours()));
	time = time.replace("h", ("0" + hours).slice(-2));
	time = time.replace("H", ("0" + (d.getHours())).slice(-2));
	time = time.replace("i", ("0" + (d.getMinutes())).slice(-2));
	time = time.replace("s", '');
	time = time.replace("a", am_pm[aL]);
	time = time.replace("A", am_pm[aU]);

	return time;
}

function geodir_search_setup_advance_search() {
	jQuery('.geodir-search-container.geodir-advance-search-searched').each(function() {
        var $this = this;

        if (jQuery($this).attr('data-show-adv') == 'search') {
            jQuery('.geodir-show-filters', $this).trigger('click');
        }
    });

    jQuery('.geodir-more-filters', '.geodir-filter-container').each(function() {
        var $cont = this;
        var $form = jQuery($cont).closest('form');
        var $adv_show = jQuery($form).closest('.geodir-search-container').attr('data-show-adv');
        if ($adv_show == 'always' && typeof jQuery('.geodir-show-filters', $form).html() != 'undefined') {
            jQuery('.geodir-show-filters', $form).remove();
            if (!jQuery('.geodir-more-filters', $form).is(":visible")) {
                jQuery('.geodir-more-filters', $form).slideToggle(500);
            }
        }
    });
}

function geodir_search_deselect(el) {
    var fType = jQuery(el).prop('type');
    switch (fType) {
        case 'checkbox':
        case 'radio':
            jQuery(el).prop('checked', false);
            break;
    }
    jQuery(el).val('');
}



function geodir_search_clear_gps() {
    lat = '';
    lon = '';
    my_location = '';
    userMarkerActive = true; /* trick script to not add marker */
    geodir_search_setUserLocation(lat, lon, my_location);
}

function geodir_search_setUserMarker(new_lat, new_lon, map_id) {
    if (window.gdMaps == 'osm') {
        geodir_search_setUserMarker_osm(new_lat, new_lon, map_id);
        return;
    }
    var image = new google.maps.MarkerImage(
        geodir_search_params.geodir_advanced_search_plugin_url + '/css/map_me.png',
        null, // size
        null, // origin
        new google.maps.Point(8, 8), // anchor (move to center of marker)
        new google.maps.Size(17, 17) // scaled size (required for Retina display icon)
    );
    if (map_id) {
        goMap = jQuery('#' + map_id).goMap();
    }
    if (gdUmarker['visible']) {
        return;
    } // if marker exists bail
    if (typeof goMap == 'undefined') {
        return;
    } // if no map on page bail
    var coord = new google.maps.LatLng(lat, lon);
    gdUmarker = jQuery.goMap.createMarker({
        optimized: false,
        flat: true,
        draggable: true,
        id: 'map_me',
        title: 'Set Location',
        position: coord,
        visible: true,
        clickable: true,
        icon: image
    });
    jQuery.goMap.createListener({
        type: 'marker',
        marker: 'map_me'
    }, 'dragend', function() {
        latLng = gdUmarker.getPosition();
        lat = latLng.lat();
        lon = latLng.lng();
        geodir_search_setUserLocation(lat, lon, 0);
    });
    userMarkerActive = true;
}

function geodir_search_setUserMarker_osm(new_lat, new_lon, map_id) {
    if (map_id) {
        goMap = jQuery('#' + map_id).goMap();
    }

    if (jQuery.goMap.gdUmarker) {
        return;
    } // if marker exists bail

    if (typeof goMap == 'undefined') {
        return;
    } // if no map on page bail

    gdUmarker = jQuery.goMap.createMarker({
        optimized: false,
        flat: true,
        draggable: true,
        id: 'map_me',
        title: 'Set Location',
        position: new L.latLng(lat, lon),
        visible: true,
        clickable: true,
        addToMap: true
    });

    gdUmarker.setIcon(L.divIcon({
        iconSize: [17, 17],
        iconAnchor: [8.5, 8.5],
        className: 'gd-user-marker',
        html: '<div class="gd-user-marker-box"><div class="gd-user-marker-animate"></div><img class="gd-user-marker-img" src="' + geodir_search_params.geodir_advanced_search_plugin_url + '/css/map_me.png' + '" /></div>'
    }));

    gdUmarker.on('dragend', function(e) {
        gdULatLng = gdUmarker.getLatLng();
        geodir_search_setUserLocation(gdULatLng.lat, gdULatLng.lng, 0);
    });

    jQuery.goMap.gdUmarker = gdUmarker;

    userMarkerActive = true;
}

function geodir_search_moveUserMarker(lat, lon) {
    if (window.gdMaps == 'google') {
        var coord = new google.maps.LatLng(lat, lon);
        gdUmarker.setPosition(coord);
    } else if (window.gdMaps == 'osm') {
        var coord = new L.latLng(lat, lon);
        gdUmarker.setLatLng(coord);
    }
}

function geodir_search_removeUserMarker() {
    if (typeof goMap != 'undefined') {
        jQuery.goMap.removeMarker('map_me');
    }
    userMarkerActive = false;
}

function geodir_search_onLocationError(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            alert(geodir_search_params.PERMISSION_DENINED);
            break;
        case error.POSITION_UNAVAILABLE:
            alert(geodir_search_params.POSITION_UNAVAILABLE);
            break;
        case error.TIMEOUT:
            alert(geodir_search_params.DEFAUTL_ERROR);
            break;
        case error.UNKNOWN_ERROR:
            alert(geodir_search_params.UNKNOWN_ERROR);
            break;
    }
}


function geodir_search_setUserLocation(lat, lon, my_loc) {
    if (my_loc) {
        my_location = 1;
    } else {
        my_location = 0;
    }

    if (userMarkerActive == false) {
        jQuery.each(map_id_arr, function(key, value) {
            geodir_search_setUserMarker(lat, lon, value); // set marker on map
        });
    } else if (lat && lon) {
        geodir_search_moveUserMarker(lat, lon);
    } else {
        geodir_search_removeUserMarker();
    }
    jQuery.ajax({
        // url: url,
        url: geodir_search_params.geodir_admin_ajax_url,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'geodir_set_user_location',
            lat: lat,
            lon: lon,
            myloc: my_location
        },
        success: function(data, textStatus, xhr) {
            //alert(data);
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log(errorThrown);
        }
    });
}

function geodir_search_showRange(el) {
    jQuery('.gdas-range-value-out').html(jQuery(el).val() + ' ' + geodir_search_params.unom_dist);
}

function geodir_search_setRange(el) {
    range = jQuery(el).val();
    var ajax_url = geodir_search_params.geodir_admin_ajax_url;
    jQuery.post(ajax_url, {
            action: 'geodir_set_near_me_range',
            range: range
        },
        function(data) {
            //alert(data);
        });

}

// SHARE LOCATION SCRIPT
function geodir_search_share_gps_on_load() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(geodir_search_position_success, geodir_search_position_error, {
            timeout: 10000
        });
    } else {
        var error = {
            code: '-1'
        };
        geodir_search_position_error(error);
    }
}

function geodir_search_position_error(err) {
    var ajax_url = geodir_search_params.geodir_admin_ajax_url;
    var msg;
    var default_err = false;

    switch (err.code) {
        case err.UNKNOWN_ERROR:
            msg = geodir_search_params.UNKNOWN_ERROR;
            break;
        case err.PERMISSION_DENINED:
            msg = geodir_search_params.PERMISSION_DENINED;
            break;
        case err.POSITION_UNAVAILABLE:
            msg = geodir_search_params.POSITION_UNAVAILABLE;
            break;
        case err.BREAK:
            msg = geodir_search_params.BREAK;
            break;
        case 3:
            geodir_search_position_success(null);
            break;
        default:
            msg = geodir_search_params.DEFAUTL_ERROR;
            default_err = true;
            break;
    }

    jQuery.post(ajax_url, {
            action: 'geodir_share_location',
            error: true
        },
        function(data) {
            //window.location = data;
        });

    if (!default_err) {
        alert(msg);
    }
}

function geodir_search_position_success(position) {
    var ajax_url = geodir_search_params.geodir_admin_ajax_url;
    var request_param = geodir_search_params.request_param;
    var redirect = typeof redirect !== 'undefined' ? redirect : '';
    var lat;
    var long;

    if (position && position !== null) {
        var coords = position.coords || position.coordinate || position;
        lat = coords.latitude;
        long = coords.longitude;
    }

    jQuery.post(ajax_url, {
        action: 'geodir_share_location',
        lat: lat,
        long: long,
        request_param: request_param
    }, function(data) {
        if (data && data !== 'OK') {
            window.location = data;
        }
    });
}

/* @todo move to LMv2 */
function geodir_search_onload_redirect() {
    var onloadRedirect = geodir_search_params.onload_redirect;
    var onloadAskRedirect = geodir_search_params.onload_askRedirect;

    if (!onloadAskRedirect) {
        return;
    }

    // Cache busting local storage if page is cached we don't keep showing redirect popup.
    if (typeof(Storage) !== "undefined" && sessionStorage.getItem('gd_onload_redirect_done') != '1') {

        sessionStorage.setItem('gd_onload_redirect_done', '1');

        switch (onloadRedirect) {
            case 'nearest':
                if (geodir_search_params.geodir_autolocate_ask) {
                    if (confirm(geodir_search_params.geodir_autolocate_ask_msg)) {
                        geodir_search_share_gps_on_load();
                    } else {
                        geodir_search_position_do_not_share();
                    }
                } else {
                    geodir_search_share_gps_on_load();
                }
                break;
            case 'location':
                var redirectLocation = geodir_search_params.onload_redirectLocation;
                if (redirectLocation && redirectLocation !== '') {
                    window.location = redirectLocation;
                }
                break;
            case 'no':
            default:
                geodir_search_position_do_not_share();
                break;
        }

    }
}

/* @todo move to LMv2 */
function geodir_search_position_do_not_share() {
    var ajax_url = geodir_search_params.geodir_admin_ajax_url;

    jQuery.post(ajax_url, {
        action: 'geodir_do_not_share_location',
    }, function(data) {});
}

function geodir_search_onSelectItem(row, $form) {
    if (geodir_search_params.geodir_autocompleter_autosubmit == 1) {
        var link = jQuery(row).find('span').attr('link');
        if (typeof link != 'undefined' && link != '') { // If listing link set then redirect to listing page.
            window.location.href = link;
            return true;
        }

        if ($form.find(' input[name="snear"]').val() == geodir_search_params.default_Near) {
            jQuery('input[name="snear"]').val('');
        }

        if (typeof(jQuery(row).find('span').attr('attr')) != 'undefined') {
            jQuery('input.geodir_submit_search', $form).trigger('click');
            //$form.submit();
        } else {
            jQuery('input.geodir_submit_search', $form).trigger('click');
            //$form.submit();
        }
    } else {
        jQuery(row).parents("form").find('input[name="' + geodir_search_params.autocomplete_field_name + '"]').trigger('focus');
    }
}

function geodir_search_formatItem(row) {
    var attr;
    if (row.length == 3 && row[2] != '') {
        attr = "attr=\"" + row[2] + "\"";
    } else {
        attr = "";
    }
    var link = '';
    var icon = '';
    var exp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
    if (typeof row[1] != 'undefined' && row[1] != '' && exp.test(row[1])) {
        link = ' link="' + row[1] + '" class="gd-search-has-link"';
        icon = '<i class="fas fa-angle-double-right"></i>';
    }
    return row[0] + "<span " + attr + " " + link + ">" + icon + "</span>";
}

function geodir_search_expandmore(el) {
    var moretext = jQuery.trim(jQuery(el).text());
    jQuery(el).closest('ul').find('.more').toggle('slow');
    if (moretext == geodir_search_params.text_more) {
        jQuery(el).text(geodir_search_params.text_less);
    } else {
        jQuery(el).text(geodir_search_params.text_more);
    }
}

function geodir_search_show_filters(el) {
    var $form, $container, $type;

    $form = jQuery(el).closest('form.geodir-listing-search');
    $container = $form.closest('.geodir-search-container');
    mode = $container.attr('data-show-adv');

    if (mode == 'always') {} else {
        jQuery('button.geodir-show-filters svg', $form).addClass('fa-spin');
        jQuery(".geodir-more-filters", $form).slideToggle("slow", function() {
            jQuery('button.geodir-show-filters svg', $form).removeClass('fa-spin');
        });
    }
}

function geodir_search_form_setup_dates() {
	// Datepicker
	jQuery('.geodir-search-container form .gd-search-date-input').each(function(i){
		var uniqId = 'geodir-uniq-d' + (i+1);
		jQuery(this).attr('id', uniqId);
		var $el = jQuery('#' + uniqId );
		var $form = $el.closest('form');

		if (!$el.hasClass('.hasDatepicker')) {
			var fieldKey = $el.data('field-key'), changeMonth, changeYear, altField, $altField, altFormat;
			changeMonth = typeof $el.data('change-month') === 'undefined' || $el.data('change-month') == '1' ? true : false;
			changeYear = typeof $el.data('change-year') === 'undefined' || $el.data('change-year') == '1' ? true : false;
			dateFormat = $el.data('date-format') ? $el.data('date-format') : 'yy-mm-dd';
			altField = $el.data('alt-field') ? $el.data('alt-field') : '';
			altFormat = $el.data('alt-format') ? $el.data('alt-format') : 'yymmdd';
			$altField = altField && jQuery('[name="' + altField + '"]', $form).length ? jQuery('[name="' + altField + '"]', $form) : '';
			if ($el.prop('name') == fieldKey + '[from]' || (altField && altField == fieldKey + '[from]')) {
				$el.datepicker({
					changeMonth: changeMonth,
					changeYear: changeYear,
					dateFormat: dateFormat,
					altField: $altField,
					altFormat: altFormat,
					onClose: function (selected) {
						if (jQuery('[name="' + fieldKey + '[to]"]', $form).length) {
							if (jQuery('[data-alt-field="' + fieldKey + '[to]"]', $form).length) {
								$toField = jQuery('[data-alt-field="' + fieldKey + '[to]"]', $form);
							} else {
								$toField = jQuery('[name="' + fieldKey + '[to]"]', $form);
							}
							$toField.datepicker("option", "minDate", selected);
						}
					}
				});
			} else {
				$el.datepicker({
					changeMonth: changeMonth,
					changeYear: changeYear,
					dateFormat: dateFormat,
					altField: $altField,
					altFormat: altFormat,
				});
			}
		}
	});

	// Timepicker
	jQuery('.geodir-search-container form .gd-search-time-input').each(function(i){
		var uniqId = 'geodir-uniq-t' + (i+1);
		jQuery(this).attr('id', uniqId);
		var $el = jQuery('#' + uniqId );
		var $form = $el.closest('form');

		if (!$el.hasClass('.hasDatepicker')) {
			var fieldKey = $el.data('field-key'), defaultValue, altField, $altField, altTimeFormat, showSecond;
			timeFormat = $el.data('time-format') ? $el.data('time-format') : 'HH:mm';
			defaultValue = $el.data('default-value') ? $el.data('default-value') : '';
			altField = $el.data('alt-field') ? $el.data('alt-field') : '';
			altTimeFormat = $el.data('alt-format') ? $el.data('alt-format') : 'HHmmss';
			showSecond = $el.data('show-second') ? true : false;
			$altField = altField && jQuery('[name="' + altField + '"]', $form).length ? jQuery('[name="' + altField + '"]', $form) : '';
			if ($el.prop('name') == fieldKey + '[from]' || (altField && altField == fieldKey + '[from]')) {
				$el.timepicker({
					timeFormat: timeFormat,
					defaultValue: defaultValue,
					altField: $altField,
					altTimeFormat: altTimeFormat,
					showSecond: showSecond,
					onSelect: function (selected) {
						if (jQuery('[name="' + fieldKey + '[to]"]', $form).length) {
							if (jQuery('[data-alt-field="' + fieldKey + '[to]"]', $form).length) {
								$toField = jQuery('[data-alt-field="' + fieldKey + '[to]"]', $form);
							} else {
								$toField = jQuery('[name="' + fieldKey + '[to]"]', $form);
							}
							$toField.timepicker("option", "minTime", selected);
						}
					}
				});
			} else {
				$el.timepicker({
					timeFormat: timeFormat,
					defaultValue: defaultValue,
					altField: $altField,
					altTimeFormat: altTimeFormat,
					showSecond: showSecond
				});
			}
		}
	});
}

function geodir_search_setup_searched_filters() {
	jQuery('.gd-adv-search-labels .gd-adv-search-label').on('click', function(e) {
		var $this = jQuery(this), $form = jQuery('.geodir-search-container form'), name, to_name, 
		name = $this.data('name');
		to_name = $this.data('names');

		if ((typeof name != 'undefined' && name) || $this.hasClass('gd-adv-search-near')) {
			if ($this.hasClass('gd-adv-search-near')) {
				name = 'snear';
                // if we are clearing the near then we need to clear up a few more things
                jQuery('.sgeo_lat,.sgeo_lon,.geodir-location-search-type', $form).val('');
			}

			geodir_search_deselect(jQuery('[name="' + name + '"]', $form));

			if (typeof to_name != 'undefined' && to_name) {
				geodir_search_deselect(jQuery('[name="' + to_name + '"]', $form));
			}

			jQuery('.geodir_submit_search', $form).trigger('click');
		}
	});
}