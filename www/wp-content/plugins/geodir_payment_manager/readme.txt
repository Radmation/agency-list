=== GeoDirectory Pricing Manager ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: geodir pricing, package, price package, pricing, pricing manager
Requires at least: 4.9
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 2.7.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GeoDirectory Pricing Manager is a powerful price manager that allows you to monetize your directory quickly and easily via a pay per listing business model.

== Description ==

Pricing Manager is a powerful price manager that allows you to monetize your directory quickly and easily via a pay per listing business model.

The interface allows efficient management of package features, such as images, website links and telephone numbers. You can set an expiry date for listings and edit the fields that are shown next to each price package.

== Changelog =

= 2.7.9 - 2023-11-08 =
* Unset cancel on period end option when subscription changed to active - FIXED

= 2.7.8 - 2023-10-05 =
* Hook added to prevent creating new package when default package not found - ADDED
* GD > Pricing hide private package from pricing table - CHANGED

= 2.7.7 - 2023-08-31 =
* Restructure caching functionality - CHANGED

= 2.7.6 - 2023-08-10 =
* Use pretty permalinks for renew/upgrade listing link - FIXED

= 2.7.5 - 2023-06-27 =
* Fix: Manually-set minimum price resets when a package is saved

= 2.7.4 - 2023-06-19 =
* PHP deprecated notice "Creation of dynamic property" - FIXED

= 2.7.3 - 2023-06-01 =
* Yith subscription compatibility issue - FIXED
* Emails not adding $email_name, $email_vars to the call which are used in filters later - FIXED
* Don't downgrade/expire listing if the listing already upgraded to another package - CHANGED

= 2.7.2 - 2023-04-27 =
* Option added in package setting to set who can reply reviews - ADDED

= 2.7.1 - 2023-04-19 =
* Invoice note shows revision post title for the listing - FIXED
* Stripe cancel subscription don't updates the listing status - FIXED

= 2.7 - 2023-03-16 =
* Package cache not cleared on package save - FIXED
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Option added to hide package from frontend users - ADDED

= 2.6.6 - 2022-11-02 =
* Option added to disable reviews for specific package - ADDED

= 2.6.5 - 2022-07-07 =
* Allow to link to an invoice using the invoice number - ADDED
* Link to the listing on the frontend subscription page - ADDED

= 2.6.4 - 2022-05-05 =
* Package setting option added to filter upgrade packages list - ADDED
* Packages list bulk actions is not working - FIXED
* Show listing title in invoices table - ADDED

= 2.6.3 - 2022-03-28 =
* Price package select for custom field changed to new style - CHANGED
* New custom field will now select all price packages by default - CHANGED
* Package setting added to enable/disable Events Tickets - ADDED

= 2.6.2 - 2022-02-22 =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.6.1.6 =
* Unable to translate listing renew/upgrade success message - FIXED

= 2.6.1.5 =
* Auto featured now applied to the claimed listing - FIXED
* Using listing address on the invoice, only if the user has no address set - CHANGED

= 2.6.1.4 =
* Failed stripe payment with WooCommerce checkout handles the wrong listing - FIXED
* Edit listing sometimes redirects to checkout page - FIXED
* Recurring listing now supports YITH WooCommerce Subscription plugin - ADDED
* Free recurring package creates new order on every update - FIXED

= 2.6.1.3 =
* Classifieds/Real-estate Sold Functionality changes - ADDED

= 2.6.1.2 =
* Claim listing free recurring package shows wrong message - FIXED
* Link a post to an invoice/order manually - ADDED

= 2.6.1.1 =
* Post limit prevents the upgrade listing when no limit set to package - FIXED
* Changes for the conditional fields compatibility - ADDED

= 2.6.1.0 =
* Package shows incorrect price for RUB currency when decimal numbers set to 0(zero) - FIXED
* Limit number of listings a user can have per price package - ADDED
* On WooCommerce subscription manually cancel don't expires the listing - FIXED

= 2.6.0.10 =
* WooCommerce renewal order entry missing in post packages - FIXED

= 2.6.0.9 =
* Ability to enable custom prices for GetPaid - ADDED
* Description limit not working for editor with AUI - FIXED
* Paid invoices not showing listing name below item name - FIXED

= 2.6.0.8 =
* Quick edit post marks post as a featured - FIXED
* Option added to handle auto redirect to checkout - ADDED

= 2.6.0.7 =
* Franchise cost not applied to GetPaid invoice on add franchise - FIXED
* Unable to complete payment for claim request submitted claim before - FIXED
* Option to assign a different check out form for each GeoDirectory package - ADDED

= 2.6.0.6 =
* Cart caching not updating listing status on order completed - FIXED

= 2.6.0.5 =
* Update package lifetime field description - ADDED
* Expire date extended multiple times for same payment - FIXED
* Show field description for package field - FIXED
* Sometimes downgrade tags replaces tag names to tag ids - FIXED
* Renew listing sets post status to pending - FIXED
* Hook added to enable auto checkout redirect - CHANGED
* Hook to customize ajax save paid post message - ADDED
* Sometimes it sends pre-expiry reminder for revision post - FIXED
* Show Pending Payment status on UWP author page for post with pending payment - CHANGED

= 2.6.0.4 =
* Listing expire date is not extended on subscription renew via GetPaid - FIXED
* GD > Listings option added to filter listings by package ids - ADDED

= 2.6.0.3 =
* Claim automatically marked as approved if using GetPaid - FIXED
* Set WooCommerce order status to completed instead of processing for paid listing - CHANGED

= 2.6.0.2 =
* Renew, Upgrade buttons are not translatable - FIXED
* Delete invoice/order don't deletes post transactions - FIXED
* Cancel previous active subscription when listing upgraded to another package subscription - CHANGED

= 2.6.0.1 =
* Show package name via [gd_post_meta] & [gd_post_badge] - CHANGED
* Claim listing not redirects to checkout page - FIXED

= 2.6.0.0 =
* Change page title on edit package - CHANGED
* Changes for AyeCode UI compatibility - CHANGED
* Sometime it sends pre-expiry notifications for auto-recurring listings - FIXED

= 2.5.1.2 =
* Saves wrong trial interval value during package to WooCommerce product synchronization - FIXED

= 2.5.1.1 =
* Allow to setup separate add listing page for each CPT - CHANGED

= 2.5.1.0 =
* Cannot unset package icon & remove package description - FIXED
* Downgrade package does not removes images when post_images excluded for package - FIXED
* Clicking on the "Create invoice for this listing" button shows "Renew Listing" even for non-recurring or initial invoices - FIXED
* Link to listing from invoice pages - ADDED
* Add an invoice note with a link to the associated listing - ADDED
* Skip invoices for free packages - ADDED
* Show package in admin posts lists - ADDED
* Filter to change the WooCommerce cart new listing checkout redirect url - ADDED
* Change html of package listing on add listing form - CHANGED
* Frontend Analytics show/hide analytics based on package settings - ADDED
* Upgrade link in email template shows wrong url - FIXED
* WooCommerce now supports recurring packages - ADDED

= 2.5.0.13 =
* Delete subsite removes data from main site on multisite network - FIXED
* Disable HTML Editor should not allow html tags in description - FIXED
* Import listings expire_date should support d/m/Y date format - CHANGED
* Displays warning on add listing page when no packages available - FIXED
* Guest user invoice requires email to checkout after add listing - FIXED
* Woocommerce subscriptions conflicts with listing payments - FIXED
* Listing shows different package in frontend & backend after upgrade - FIXED
* Add more pre expiry email options(10, 15 & 30 days) - ADDED
* Send an invoice after a claim is made - ADDED

= 2.5.0.12 =
* Allow editing of listings in inactive packages without having to change the package or activating the package first.

= 2.5.0.11 =
* Listing with active subscription should not be allowed to renew again - FIXED
* Don't show info box when auto redirecting to checkout - CHANGED
* Custom fields created during dummy data import are not visible on frontend - FIXED

= 2.5.0.10 =
* Allow renew & upgrade links in email notifications - ADDED
* Switching package should not show remove revision message - FIXED
* Image, category, tag limit not working on downgrade package - FIXED
* Show expired notification on single listing page - ADDED

= 2.5.0.9 =
* Skip checkout for free listing claim - ADDED

= 2.5.0.8 =
* GD BuddyPress member area not showing the expired listings - FIXED

= 2.5.0.7 =
* Add listing package id to body class on detail listing page - ADDED

= 2.5.0.6 =
* Category restrictions can make dummy data not have any terms - FIXED
* Changing package sometimes does not save last info entered - FIXED
* Dummy data with no pricing package can break add listing validation - FIXED
* Paid listings will now auto redirect to checkout - CHANGED

= 2.5.0.5 =
* Not submitting the claim listing form if claim package selected - FIXED
* Post updates not adding new images if price is free - FIXED
* Franchise plugin integration - ADDED

= 2.5.0.4 =
* Checkout button in submit listing message should link to checkout page - CHANGED
* Package html editor affects all textareas - FIXED
* Validate package id during post submit - FIXED
* Changes for claim listings addon - CHANGED
* Not able to set featured listing from package - FIXED
* Preview link should open preview in new window - CHANGED

= 2.5.0.3-beta =
* Old version package table fields may cause issue in update package - FIXED

= 2.5.0.2-beta =
* It shows incorrect expire date in backend listings page - FIXED
* Package description limit not working - FIXED

= 2.5.0.1-beta =
Initial beta release - INFO

== Upgrade Notice ==

= 2.5.0.1-beta =
* 2.5.0.1-beta is a major update to work with GDv2 ONLY. Make a full site backup, update your theme and extensions before upgrading.
