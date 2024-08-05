=== GeoDirectory reCAPTCHA ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: anti-spam, captcha, google captcha, google recaptcha, google re-captcha, invisible captcha, invisible recaptcha, re-captcha, recaptcha
Requires at least: 4.5
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 2.3.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Integrates Google reCAPTCHA anti-spam methods with GeoDirectory addon including login, registration, comments, add listing, claim listing etc.

== Description ==

The GeoDirectory reCAPTCHA plugin allows you to implement a super security captcha form into GeoDirectory addon including registration, comments and claim listing forms. GeoDirectory reCAPTCHA is a free CAPTCHA service that protects your site against spam, malicious registrations and other forms of attacks where computers try to disguise themselves as a human.
This captcha can be used for GeoDirectory registration, comments, send to friend, send enquiry and claim listing forms.

GeoDirectory reCAPTCHA provides some of the smartest anti-spam security by protecting your site against spam and malicious registrations available today for WordPress, modded to be 100% GeoDirectory compatible.

[Learn how reCAPTCHA works](https://www.google.com/recaptcha/) and join [forum](https://groups.google.com/forum/#!forum/recaptcha).

== Requirements ==

* You need the Google reCAPTCHA keys from [here](https://www.google.com/recaptcha/admin#whyrecaptcha).

== Changelog ==

= 2.3.3 - 2024-01-03 =
* Claim request sometimes submitted multiple times - FIXED

= 2.3.2 - 2023-08-14 =
* Google reCAPTCHA new types compatibility - CHANGED

= 2.3.1 - 2023-06-19 =
* PHP deprecated notice "Creation of dynamic property" - FIXED

= 2.3 - 2023-03-16 =
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Make frontend reCAPTCHA title translatable - CHANGED
* Comment form recaptcha compatibility with BuddyBoss theme - FIXED

= 2.2 - 2022-02-22 =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.0.2 =
* It fails to validate captcha when form filling takes more than 2 minutes - FIXED
* Fix conflict with UsersWP reCAPTCHA plugin - FIXED
* Fix missing recaptcha form issue for third party theme - FIXED

= 2.1.0.1 =
* Sometime reCAPTCHA breaks claim form with AUI style - FIXED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED

= 2.0.0.4 =
* Recaptcha v2 rendered twice on add review form - FIXED

= 2.0.0.3 =
* Support recaptcha v3 - ADDED

= 2.0.0.2 =
* Conflict with JetPacks comment Subscriptions feature - FIXED
* Loading unused JS file - FIXED

= 2.0.0.1 =
* First non beta release - INFO

= 2.0.0.0-beta =
* Compatible with GDv2, first beta release - CHANGED