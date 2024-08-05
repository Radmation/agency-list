=== GeoDirectory Custom Post Types ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: cpt, custom post type, custom post types, geodirectory, geodirectory custom post types, post type, post types
Requires at least: 4.9
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 2.3.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GeoDirectory Custom Post Types allows to create multiple custom post types as you need, allowing you to divide categories and manage features and parameters per CPT.

== Description ==

Managing a large diverse directory just got way easier. Harness the full power of WordPress custom post types (CPT) to offer and manage multiple listing categories.

Here's how it works: each new listing you make with GeoDirectory is a custom post. By default, GeoDirectory offers a single custom post type, called "Places". This add-on lets you create and add as many CPT's as you need, allowing you to divide categories and manage features and parameters per CPT.

Each CPT has its own form builder, so you can add in any custom fields, prices, custom categories and tags that you need. You can even create listings without a physical location and build a directory of websites or online services.

== Installation ==

1. Upload 'geodir_custom_posts' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GeoDirectory Custom Post Types" through the 'Plugins' menu in WordPress
3. Go to WordPress Admin -> GeoDirectory -> Settings -> Custom Post Types and customize behaviour as needed
4. For detailed setup instructions, visit the official [Documentation](https://wpgeodirectory.com/docs/category/addons/custom-post-types/?utm_source=docs&utm_medium=installation_tab&utm_content=documentation&utm_campaign=readme) page.

== Changelog ==

= 2.3.5 - 2024-02-22 =
* CPT Listings columns are not responsive on mobile - FIXED

= 2.3.4 - 2023-06-19 =
* PHP deprecated notice "Creation of dynamic property" - FIXED

= 2.3.3 - 2023-06-14 =
* Import / Export facility added for CPT tabs - ADDED

= 2.3.2 - 2023-05-25 =
* Issue with select field in assigning linked post data - FIXED

= 2.3.1 - 2023-04-19 =
* Import / export CPT settings listing owner label - ADDED

= 2.3 - 2023-03-16 =
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Validate tab_parent & tab_level during custom field import - CHANGED
* Delete linked custom field on delete CPT - FIXED

= 2.2.1 =
* Import - export facility added for the post types & custom fields - ADDED

= 2.2 =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.0.5 =
* Allow link posts search with wild card to match starting word - CHANGED

= 2.1.0.4 =
* Changes for the conditional fields compatibility - ADDED
* Prevent the block/widget class loading when not required - CHANGED

= 2.1.0.3 =
* Link posts cache not deleted on cpt update - CHANGED
* Locationless CPT don't hides near me search input - FIXED
* Linked Post field is not working for non loggedin user - FIXED

= 2.1.0.2 =
* Linked posts field supports added for Elementor tag - ADDED

= 2.1.0.1 =
* Linked posts supports added for [gd_post_badge] & [gd_dynamic_content] - ADDED
* Sometime link posts field search is not working in add listing - FIXED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED

= 2.0.1.0 =
* Linked posts feature not working with rest API - FIXED
* Disabled link posts field breaks import post - FIXED
* Allow to show linked posts raw value with gd_post_meta - CHANGED
* Fill the post data from selected linked post - ADDED
* GD > Linked Posts now supports Elementor pro skins - ADDED

= 2.0.0.14 =
* Linked posts widget has no option to filter event type - FIXED
* Delete subsite removes data from main site on multisite network - FIXED

= 2.0.0.13 =
* Admin users can't link other user's posts - FIXED

= 2.0.0.12 =
* Admin users should allowed to link posts from users - CHANGED

= 2.0.0.11 =
* Add view all linked posts link in Linked Posts widget - FIXED

= 2.0.0.10 =
* CPT Listings widget should accept comma separated values - FIXED

= 2.0.0.9 =
* Add CPT Listings widget back - CHANGED
* WPML translate slug sometimes not working - FIXED

= 2.0.0.8 =
* Linked Posts block shows error on backend add/edit pages - FIXED

= 2.0.0.7 =
* Linked CPTs not exporting to CSV - FIXED

= 2.0.0.6 =
* Location-less post not saving - CHANGED
* Custom CPT tags should be non-hierarchical - FIXED

= 2.0.0.5 =
* Compatibility changes for Beaver builder - CHANGED

= 2.0.0.4 =
* In obscure circumstances re-enabling location info on a CPT might not add the neighborhoods column - FIXED

= 2.0.0.3-rc =
* Adding new CPT now uses dbDelta() function from core GD - CHANGED

= 2.0.0.2-beta =
* Help-tip wording changed to make settings clearer - CHANGED
* Update ID missing preventing the ability to add licence key - FIXED

= 2.0.0.1-beta =
* Linking not deleted if all removed - FIXED

= 2.0.0.0-beta =
* First beta release - INFO
