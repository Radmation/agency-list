=== GeoDirectory Advanced Search Filters ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: advance search, geodirectory, geodirectory search, search
Requires at least: 4.9
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 2.3.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GeoDirectory Advanced Search Filters plugin allows to expands the default GeoDirectory search functionality by adding a range of filters.

== Description ==

The GeoDirectory Advanced Search Filters plugin expands the default GeoDirectory search widget by adding a range of filters such as:
- Search Autocompleter
- GeoLocation
- Proximity Search
- Radius Search
- Filter by any custom field

The possibilities are limitless and your users will love their Advanced Search experience.

The Advanced Search add-on enables Ajax Autocompleter function to both of GeoDirectory search fields: "Search For" and "Near".

The "Search For" field will search for keywords in the listing title, text content, categories and tags.

While the "Near" field will query locations and return results sorted by distance.

== Installation ==

1. Upload 'geodir_advance_search_filters' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GeoDirectory Advance Search Filters" through the 'Plugins' menu in WordPress

== Changelog ==

= 2.3.5 - 2023-12-05 =
* Scroll to loop container results on AJAX pagination click - CHANGED

= 2.3.4 - 2023-10-19 =
* JS error in AJAX response when dataType JSON not set in AJAX request - FIXED

= 2.3.3 - 2023-06-19 =
* PHP deprecated notice "Creation of dynamic property" - FIXED

= 2.3.2 - 2023-05-25 =
* AJAX search next pagination button is not working - FIXED

= 2.3.1 - 2023-03-27 =
* Disable date native datetime input on mobile - CHANGED

= 2.3 - 2023-03-16 =
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Select option with black slash not working in search - FIXED
* AJAX search should shows respective short distance unit - FIXED

= 2.2.11 (2022-11-09) =
* Changes for Booking plugin search compatibility - CHANGED

= 2.2.10 (2022-10-13) =
* Allow multiselect field to show in main search bar as single select - CHANGED

= 2.2.9 (2022-09-27) =
* Conditional fields feature is not working with AJAX loaded advanced fields - FIXED

= 2.2.8 (2022-09-22) =
* GD Booking plugin integration to search booking availability - ADDED
* Elementor WP Image resizer not applied on AJAX search - FIXED

= 2.2.7 (2022-09-07) =
* Option added to show "Search as I move the map" by default checked - ADDED

= 2.2.6 (2022-08-09) =
* Option added to set custom label for Distance in distance filter - ADDED
* Fast AJAX search don't render Divi template correctly - FIXED
* Select current category on category field for taxonomy page - ADDED

= 2.2.5 (2022-07-06) =
* Search field sorting order not saved - FIXED
* AJAX search Beaver Themer compatibility changes - CHANGED
* AJAX search title not updating with YOOtheme - FIXED
* Show search by distance in search bar for AUI - ADDED
* Conditional fields filters added for AUI style - ADDED

= 2.2.4 (2022-05-26) =
* Elementor Ajax search compatibility - FIXED
* Chrome shows replaceState JS error on AJAX search - FIXED
* Changes for Fast AJAX feature - CHANGED

= 2.2.3 (2022-05-06) =
* AJAX search not refreshing the results after searched again with event dates  - FIXED

= 2.2.2 (2022-05-05) =
* Option added to change advance filters button text - ADDED
* AJAX search functionality - ADDED

= 2.2.1 =
* Select dropdown placeholder should show field title instead of "Select option" - CHANGED
* Time search input always shows military time - FIXED
* Search field range type not saved - FIXED

= 2.2 =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.1.1 =
* Open Hours by days shows incorrect results for some timezone - FIXED

= 2.1.1.0 =
* Classifieds/Real-estate Sold Functionality changes - ADDED

= 2.1.0.9 =
* Show checkbox field label instead of "Yes" text in search - CHANGED
* Address field is missing in search field setting - FIXED

= 2.1.0.8 =
* GD > Search block shows block validation error in console - FIXED
* Fieldset in advance search field breaking HTML with AUI - FIXED

= 2.1.0.7 =
* AUI Datepicker is not working date fields loaded via CPT change in search form - FIXED

= 2.1.0.6 =
* Unable to change search bar category label from field setting - FIXED
* Less/more toggle don't shows optgroup labels - FIXED

= 2.1.0.5 =
* Business Hours field web accessibility issue - FIXED
* Field to filter posts by Service Distance added - ADDED

= 2.1.0.4 =
* Show month & year dropdown in search form datepicker - CHANGED

= 2.1.0.3 =
* Mobile scroll over advance search category trigger click event - FIXED
* Search field LINK list shows incorrect url - FIXED

= 2.1.0.2 =
* .hide class in advance search more option create conflict - FIXED
* Search suggestions for AUI styles changed to bootstrap dropdown for better overflow ability - CHANGED
* Checkboxes are not left aligned in Supreme with bootstrap style - FIXED
* Advanced search categories should show multiple levels of sub cats - FIXED

= 2.1.0.1 =
* Change Jquery doc ready to pure JS doc ready so jQuery can be loaded without render blocking  - CHANGED
* Price range field is not working properly with bootstrap style - FIXED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED

= 2.0.1.2 =
* Web accessibility compatibility changes - CHANGED

= 2.0.1.1 =
* Open Now search shows incorrect results when used in advance toggle search bar - FIXED
* Open Now with weekend search shows duplicate results - FIXED

= 2.0.1.0 =
* Open Now search functionality for listing business hours - FIXED

= 2.0.0.17 =
* Datepicker is not working when multiple search forms are on the page - FIXED

= 2.0.0.16 =
* Chrome browser shows category field in main search bar shifted - FIXED

= 2.0.0.15 =
* Search form advance fields layout don't shows labels - FIXED

= 2.0.0.14 =
* REST API allow search posts by GPS, IP and near address - ADDED
* JS variable conflict with Rank Math plugin - FIXED

= 2.0.0.13 =
* Option added to show listings from child categories for searched parent category - CHANGED
* Category should be auto-selected on category archive page - CHANGED
* Datepicker in search form shows untranslated text - FIXED
* Delete subsite removes data from main site on multisite network - FIXED
* Autocomplete results categories should redirect to searched location - FIXED

= 2.0.0.12 =
* Option added to hide posts/categories from search suggestions - ADDED

= 2.0.0.11 =
* Autocomplete given a slight delay between key presses before sending request to minimise requests to the server - CHANGED

= 2.0.0.10 =
* Search for category goes to 404 page when Location Manager not installed - FIXED

= 2.0.0.9 =
* Allow to search listings with special offers & video - ADDED
* v1 to v2 conversion can set autocompleter_max_results to blank - FIXED
* Translations in some cases can break the autocompleter - FIXED

= 2.0.0.8 =
* Add clear db version feature to diagnose plugin data - ADDED

= 2.0.0.7 =
* Static search_sort() method called in a way that does not work with older PHP versions - FIXED

= 2.0.0.6 =
* Some autocomplete search texts are not translated - FIXED
* Search by distance not working - FIXED

= 2.0.0.5 =
* Clearing near searched parameter does not clear GPS info - FIXED

= 2.0.0.4 =
* Advanced search shortcode always open parameter not working - FIXED
* Advanced search block always open parameter not showing - FIXED

= 2.0.0.3-beta =
* Changes for upcoming events addon - CHANGED

= 2.0.0.2-beta =
* Changes for CPT addon compatibility - CHANGED
* Uninstall settings function updated to latest version - CHANGED

= 2.0.0.1-beta =
* Old body class filter can cause problems on search page - FIXED

= 2.0.0.0-beta =
* Initial beta release - INFO
