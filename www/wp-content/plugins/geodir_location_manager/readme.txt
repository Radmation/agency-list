=== GeoDirectory Location Manager ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: geodirectory, location, location manager, locations, multi locations
Requires at least: 4.9
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 2.3.12
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GeoDirectory Location Manager allows you to expand your directory and go global by creating unlimited locations for your listings.

== Description ==

Why the Location Manager?

Because if your directory website cover more than one city, you'll need the Location Manager Add-on.

The Core Plugin that we offer for free on WordPress.org, allows to submit listings only within 1 location (City, Town or Village).

With the Location Manager, you can expand your directory and go global! With the GeoDirectory Locations Manager add-on you can create unlimited locations for your listings.

Obviously there is no need to create all the locations in advance. Users can add new locations while adding a new listing. We query the Google Maps API to do so.

== Installation ==

1. Upload 'geodir_location_manager' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GeoDirectory Location Manager" through the 'Plugins' menu in WordPress
3. Go to WordPress Admin -> GeoDirectory -> Settings -> Locations and customize behaviour as needed

== Changelog ==

= 2.3.12 - 2024-02-26 =
* Changes for better region filling on add listing page if missing (OSM) - ADDED

= 2.3.11 - 2024-02-22 =
* Changes for better region filling on add listing page if missing - ADDED

= 2.3.10 - 2024-02-08 =
* Changes for non required address in core GD - CHANGED

= 2.3.9 - 2023-11-08 =
* SEOPress breadcrumbs compatibility - ADDED

= 2.3.8 - 2023-10-05 =
* Selected countries filter not working with Switzerland & Liechtenstein - FIXED

= 2.3.7 - 2023-09-13 =
* Timezone field should be displayed on default location only - CHANGED
* Google bounds options added to influence geocoder results in search - ADDED

= 2.3.6 - 2023-08-31 =
* Check 404 page for rescued urls - CHANGED

= 2.3.5 - 2023-08-07 =
* Rescue 404 permalinks with CPT + location slug - CHANGED

= 2.3.4 - 2023-07-26 =
* AUI label type top don't hides loader on change of region in add listing form - FIXED
* Filter REST API neighbourhoods results by user location - ADDED
* Hook added to filter location switcher footer script - ADDED

= 2.3.3 - 2023-07-12 =
* Fix PHP notice on blog post page - FIXED

= 2.3.2 - 2023-06-14 =
* Timezone not saved on edit default city - FIXED
* Delete batch locations don't deletes posts - FIXED

= 2.3.1 - 2023-04-05 =
* Location description prevent removing p tags in HTML editor - FIXED

= 2.3 - 2023-03-16 = 
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Unable to choose from selected countries when split UK feature enabled - FIXED
* Conflicts with Tag Cloud block - FIXED
* Keep searched location in search form on CPT change - ADDED
* Location switcher results is not opening on ENTER click - FIXED
* Add city/neighbourhood should show default country - CHANGED
* Location country/region/city show/hide setting not saved - FIXED
* Changed for upcoming BS5 changes - ADDED

= 2.2.8 (2022-11-02) =
* Published listing preview is not working with %postname% permalink structure - FIXED

= 2.2.7 (2022-10-06) =
* Option added to split UK into England, Northern Ireland, Scotland & Wales - ADDED

= 2.2.6 (2022-09-29) =
* Unable to update neighbourhood meta description via import - FIXED

= 2.2.5 (2022-08-09) =
* Option added to allow frontend users to type neighbourhood from add listing form - ADDED
* Autocomplete results popup sometimes remains hidden - FIXED
* Hook added for Google component restrictions in location switcher - ADDED

= 2.2.4 (2022-07-06) =
* Hook added to prevent location type form Yoast sitemap - ADDED
* Shows incorrect location in title on fast AJAX search - FIXED

= 2.2.3 (2022-05-26) =
* Changes for Fast AJAX feature - CHANGED

= 2.2.2 (2022-05-05) =
* Individual location rest endpoint shows error - FIXED

= 2.2.1 (2022-03-28) =
* Option added to sort listing by country/region/city name - ADDED
* Allow HTML editor for location descriptions - CHANGED

= 2.2 (2022-02-22) =
* Breadcrumbs on location pages not working with recent Yoast/RankMath - FIXED
* Location page shows canonical url twice with Rank Math SEO - FIXED
* Neighbourhood SEO variable not working on single page - FIXED
* Show neighbourhood pages in Yoast SEO XML sitemaps - ADDED
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.1.2 =
* GD > CPT Meta don't shows the description on neighbourhood pages - FIXED
* Location sitemap added for Rank Math SEO - ADDED

= 2.1.1.1 =
* Changes for Private Address changed - CHANGED

= 2.1.1.0 =
* Shows 404 error on single post feed for some permalink structure - FIXED
* Add marker to show user position on search page map - ADDED
* Fix pagination for location widget - FIXED

= 2.1.0.15 =
* Changes for the conditional fields compatibility - ADDED

= 2.1.0.14 =
* Sometimes slashes causes issue in saving CPT + Location description - FIXED
* Clear location from location switcher should redirect to current post type archive page - CHANGED

= 2.1.0.13 =
* Fix conflict with Events Manager list page - FIXED
* Location switcher shows untranslated country name for region - FIXED
* Multiple search in backend location lists makes query url long and breaks search - FIXED

= 2.1.0.12 =
* Page redirect to different page on bulk delete of location - FIXED
* [gd_location_meta] shows city link instead of region link on single page - FIXED

= 2.1.0.11 =
* Location CPT meta description not saving with HTML editor - FIXED
* Restructure location sitemaps to prevent timeout issue - CHANGED

= 2.1.0.10 =
* GD Listings neighbourhood slug filter is not working - FIXED
* Location CPT descriptions are not translatable - FIXED
* Location image is not responsive on mobile device - FIXED
* Some Locations widget ajax parameters not fully escaped - SECURITY

= 2.1.0.9 =
* Location image opens multiple lightbox if Elementor is active - FIXED
* [gd_location_meta] support added to show location slug, link & cpt link - ADDED

= 2.1.0.8 =
* Address within Greece country shows empty region - FIXED
* Field to filter posts by Service Distance added - ADDED
* Location sitemaps not working with Yoast SEO v16.x - FIXED

= 2.1.0.7 =
* Selecting the near search result redirects to another page - FIXED
* Conflict with GetPaid + UsersWP where saving billing address leads to 404 page - FIXED

= 2.1.0.6 =
* Add tool to merge missing locations from listings to locations database - ADDED

= 2.1.0.5 =
* Add fix for Focus Out of location switcher - ADDED
* Search form autocomplete location selection redirects to location page - FIXED
* Change location suggestions not always showing when closing and opening again - FIXED
* Change location suggestions can show multiples on subsequent openings - FIXED
* Google shows error for addressNeighbourhood property in schema - FIXED

= 2.1.0.4 =
* Show specific locations by using [gd_locations] - ADDED
* Near suggestions for AUI styles changed to bootstrap dropdown for better overflow ability - CHANGED
* Near suggestions for AUI not removing history items - FIXED

= 2.1.0.3 =
* Add listing under default country/region/city not working with bootstrap style - FIXED

= 2.1.0.2 =
* Add listing country field not working when selected countries enabled - FIXED

= 2.1.0.1 =
* Add/edit neighbourhood no longer working - FIXED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED
* CPT Description fields missing in add/edit location form - FIXED
* Lazy Load map feature added - ADDED

= 2.0.1.4 =
* Yoast SEO single page breadcrumbs contains category link without location - FIXED
* WordPress v5.5 compatibility changes - CHANGED
* Rank Math breadcrumbs shows links in wrong order when home link is disabled - FIXED

= 2.0.1.3 =
* Show neighbourhood name using GD > Post Meta - CHANGED

= 2.0.1.2 =
* Yoast Breadcrumb issue for location url - FIXED
* Change country shows JS error when selected countries option is enabled - FIXED
* No way to access post type archive base link in Rank Math breadcrumbs - FIXED

= 2.0.1.1 =
* Near address search shows JS error when default country/region is active - FIXED

= 2.0.1.0 =
* Default location image now given class of `gd-location-image-default` - CHANGED
* Restrict near search autocomplete results within additional location set in search settings - CHANGED
* Post meta Neighborhoods outputs the slug and not the name of the neighbourhood - FIXED
* Breadcrumb not showing exact location name - FIXED
* Compatibility for rankmath breadcrumb - FIXED
* Add ID in nav endpoints to avoid notice - ADDED
* Listings details page can show 404 on install if core GD permalinks have never been changed - FIXED
* Set location wise CPT description from Location + CPT Description - ADDED
* Business hours timezone input replaced with timezones string list - CHANGED

= 2.0.0.25 =
* [gd_locations] ajax pagination doesn't filters current location - FIXED
* [gd_locations] shows matching results with sub part of current location - FIXED
* Option added to limit min chars needed to trigger location search autocompleter - ADDED
* Show translated country name in locations list - FIXED
* Unable to save category location for different location wise - FIXED
* [gd_location_meta] added to show location title, description, image - ADDED
* Sitemap Bug – Showing c instead of time - FIXED
* Add neighbourhood field in post meta keys list - ADDED
* Elementor Pro can break CPT location archive links in some cases - FIXED
* GD > Locations grid view not showing image for neighbourhoods - FIXED
* [gd_locations] show post image as fallback image - CHANGED

= 2.0.0.24 =
* Option added to show current location in locations widget/shortcode - ADDED
* Searched country/region/city not showing in meta title - FIXED
* UsersWP account form submit shows 404 error - FIXED
* Save new location generates same slugs for region & city - FIXED
* JavaScript error breaks Locations block editor - FIXED

= 2.0.0.23 =
* Allow search country by translated name in location switcher & search location - ADDED
* Allow filter posts by category, tag, author within a chosen location in backend - ADDED
* REST API allow search posts by GPS, IP and near address - ADDED
* Feed not working on category pages - FIXED
* Region/city links works for any country that not belongs to - FIXED
* Allow to unlink a neighbourhood from the listing - CHANGED
* Add date format compatibility with Yoast 12.8

= 2.0.0.22 =
* Duplicate city search retrieves posts from both cities - FIXED
* OSM autocomplete results shows address outside the default country - FIXED
* Delete subsite removes data from main site on multisite network - FIXED
* Option added in gd_location widget to list neighbourhoods / cities / regions / countries - ADDED
* Autocomplete results categories should redirect to searched location - FIXED

= 2.0.0.21 =
* Single quote not supported in cities in selected cities option - FIXED
* Fix for Latvian regions sometimes not being added - FIXED
* Fix for Hungarian regions sometimes not being added - FIXED

= 2.0.0.20 =
* Disable set address on map from changing address fields not working with OSM - FIXED
* Post address shortcode shows neighbourhood slug instead of name - FIXED
* Backend posts search not working with translated countries - FIXED

= 2.0.0.19 =
* Functionality added to get particular location value - ADDED
* Hiding country or region causes 404 errors with Yoast breadcrumbs - FIXED
* Locations widget now has option to display as image grid - ADDED
* Locations widget loop template can now be edited from theme - ADDED
* Changes for location queries for GPS position - CHANGED
* listings widget sometimes not filtering by GPS position - FIXED

= 2.0.0.18 =
* Post slug with special characters results in listing redirect loop - FIXED
* Tag cloud widget shows wrong count on location pages - FIXED

= 2.0.0.17 =
* Category links adds location term on non-location page - FIXED
* Added filter to be able to change the add listing address autocomplete types - ADDED
* Autocomplete given a slight delay between key presses before sending request to minimise requests to the server - CHANGED
* Single quote in location creates problem in autocomplete search - FIXED

= 2.0.0.16 =
* Single quote in default city/region/country alert message breaks map - FIXED
* CPT + region sitemap shows duplicate rows - FIXED
* Permalink issue with %post_id% rewrite tag - FIXED
* Category top description breaks category page content - FIXED
* Places listings admin page can now be filtered by location - ADDED
* Some Font Awesome v4 classes left over can cause issues on backend - FIXED
* Translations in some cases can break the location switcher - FIXED

= 2.0.0.15 =
* ​Detail page meta description can be overwritten by location page - FIXED​
* Category archive page pagination link has extra slash - FIXED
* Allow location duplicate slugs tool to fix duplicate region slug - ADDED

= 2.0.0.14 = 
* Unable to translate some text in location switcher - FIXED
* Event categories shows wrong location term counts - FIXED
* Pagination not working on CPT + country/region pages - FIXED
* Incorrect canonical url on location pages - FIXED
* Location switcher shortcode text not translatable - FIXED

= 2.0.0.13 =
* New tool added to fix location duplicate slugs - ADDED
* Location slugs with arabic characters results in 404 error - FIXED

= 2.0.0.12 =
* Show CPT + Location urls in Yoast SEO sitemap - ADDED
* Should show 404 if location var set but not found - FIXED
* Listing with city permalink not working when neighbourhood is active - FIXED
* Review count not reflecting on listing page - FIXED
* New location not added when adding listing from front-end - FIXED
* Disabling add listing address autocomplete not working for OSM - FIXED

= 2.0.0.11 =
* Location widget not respecting the location filter option - FIXED
* Location widget prev page link not working - FIXED
* Changes for older version of PHP - CHANGED
* Added option to be able to enable/disable location autocomplete on the search bar - ADDED
* City permalink issue - FIXED

= 2.0.0.10 =
* Location-less post shows undefined property error - FIXED
* Location-less CPT term count raises database error - FIXED

= 2.0.0.9 =
* Search near me and near me button consistency improved - CHANGED

= 2.0.0.8-rc =
* Update hook function for adding neighborhoods DB column - FIXED

= 2.0.0.7-beta =
* Directory map and listings widgets can now be filtered by location slugs - ADDED
* Location posts count should not count revision posts - FIXED
* Not able to add new location during add listing if country is translated - FIXED
* Settings added to be able to disable archive location filtering of URLs - ADDED

= 2.0.0.6-beta =
* Changes for CPT addon compatibility - CHANGED

= 2.0.0.5-beta =
* Changing title of address field changes display of address in sidebar - FIXED
* Location edited later seems to lose its listings - FIXED
* Setup wizard handle save default location - FIXED

= 2.0.0.4-beta =
* CPT link broken if main permalink do not have a ending slash / - FIXED
* Term permalink structure changed to place location vars at the end of the url - CHANGED
* Map not showing markers in neighbourhoods - FIXED

= 2.0.0.3-beta =
* Location switcher JS can conflict with some jQuery versions - FIXED
* Location merge functionality not working - FIXED
* Add listing address autocomplete not just adding street info - FIXED
* Default post permalinks conflict with location permalinks - FIXED

= 2.0.0.2-beta =
* First beta release
