=== Error Log Monitor ===
Contributors: whiteshadow
Tags: dashboard widget, administration, error reporting, admin, maintenance, php
Requires at least: 4.5
Tested up to: 6.2.2
Stable tag: 1.8.5

Adds a Dashboard widget that displays the latest messages from your PHP error log. It can also send logged errors to email.

== Description ==

This plugin adds a Dashboard widget that displays the latest messages from your PHP error log. It can also send you email notifications about newly logged errors.

**Features**

* Automatically detects error log location.
* Explains how to configure PHP error logging if it's not enabled yet.
* The number of displayed log entries is configurable.
* Sends you email notifications about logged errors (optional).
* Configurable email address and frequency.
* You can easily clear the log file.
* The dashboard widget is only visible to administrators.
* Optimized to work well even with very large log files.

**Usage**

Once you've installed the plugin, go to the Dashboard and enable the "PHP Error Log" widget through the "Screen Options" panel. The widget should automatically display the last 20 lines from your PHP error log. If you see an error message like "Error logging is disabled" instead, follow the displayed instructions to configure error logging.

Email notifications are disabled by default. To enable them, click the "Configure" link in the top-right corner of the widget and enter your email address in the "Periodically email logged errors to:" box. If desired, you can also change email frequency by selecting the minimum time interval between emails from the "How often to send email" drop-down.

== Installation ==

Follow these steps to install the plugin on your site: 

1. Download the .zip file to your computer.
2. Go to *Plugins -> Add New* and select the "Upload" option.
3. Upload the .zip file.
4. Activate the plugin through the *Plugins -> Installed Plugins" page.
5. Go to the Dashboard and enable the "PHP Error Log" widget through the "Screen Options" panel.
6. (Optional) Click the "Configure" link in the top-right of the widget to configure the plugin.

== Screenshots ==

1. The "PHP Error Log" widget added by the plugin. 
2. Dashboard widget configuration screen.

== Changelog ==

= 1.8.5 =
* Updated the Freemius SDK to the latest version.
* Tested with WP 6.2.2 and WP 6.3-beta.

= 1.8.4 =
* When sorting by "highest level first", errors that have the same severity will be sorted both by count and by timestamp. For example, if there are two unique fatal errors where each has only happened once, the most recent error will be shown first.
* Updated the Freemius SDK to version 2.5.3 in the hopes of fixing a couple of PHP 8.1 deprecation notices that appear to be triggered by the SDK.

= 1.8.3 =
* Fixed a number of PHP 8 deprecation warnings.
* Tested up to WP 6.1.

= 1.8.2 =
* Added an "Ignored regular expressions" setting. Enter one or more regex patterns in the box and the plugin will hide log entries that match any of those patterns.
* Added a "Delete Summary Data" feature to the configuration screen. Normally, if an error stops happening and doesn't show up again for one month, the plugin will automatically remove it from the summary. The "Delete Summary Data" feature lets you delete all summary entries immediately instead. The plugin will then build a new summary based on the current contents of the log file.

= 1.8.1 =
* Added a "Clear Fixed Messages" button.
* Fixed a scheduling bug where, in certain configurations, the plugin would send some email notifications too late.
* Fixed a security issue.
* Tested with WP 5.9.1 and 6.0-alpha (briefly).

= 1.8 =
* Added a "mark as fixed" option. Like the "ignore" option, "mark as fixed" hides all existing copies of a specific error. However, if the same error happens again in the future, the plugin will make it visible again.
* Added a "Clear Ignored Messages" button. It un-ignores all previously ignored messages.
* Large event counts will now be rounded to the nearest hundred or thousand. For example: "1.2k", "15k", "200k". The actual number is still shown in a tooltip.
* Fixed a bug where unselecting all Dashboard widget filter options except "Other" would cause the plugin to throw an SQL error.
* Fixed a couple of PHP 8 deprecation warnings about a required parameter following an optional parameter.
* Tested with WP 5.6.1 and 5.7-beta.

= 1.7.2 =
* Fixed recoverable fatal errors being incorrectly displayed as an unknown error type.
* Fixed a bug where the plugin could freeze or crash while trying to parse extremely long log entries (e.g. more than a million characters long).
* Tested with WP 5.5.1 and 5.6-beta.

= 1.7.1 =
* Fixed "invalid plugin header" error when activating the Pro version in a certain way for the first time.
* Partially fixed a styling issue where jQuery UI themes loaded by other plugins would change the appearance of the dashboard widget.
* Fixed the plugin repeatedly triggering database errors in situations where initial database setup was interrupted or crashed partway.
* Tested with WP 5.4 and 5.5-alpha.

= 1.7 =
* Added the ability to log stack traces and context information for WordPress database errors. To make this possible, you need to copy the "db.php" file from the "db-wrapper" subdirectory to the global "wp-content" directory.

= 1.6.8 =
* Fixed the erorr "call to undefined function get_blog_list()" when trying to access the network admin on a non-Multisite site.
* Fixed a conflict with plugins that add their own error handler and don't call the previous error handler.
* Tested up to WP 5.3.

= 1.6.7 =
* Fixed a bug where the "Copy" link didn't show up on some sites.
* Probably fixed a crash in Freemius SDK.
* Fixed a conflict with plugins that use old versions of scbFramework.
* Tested up to WP 5.2.2.

= 1.6.6 =
* Fixed a bug where it wasn't possible to filter out log entries that didn't match any of the standard severity levels (notice, warning, error, etc). Now you can hide uncategorized log entries by unchecking the "Other" option in filter settings.
* Fixed a security issue.
* Tested with WP 5.1.

= 1.6.5 =
* Added a "Copy" link to log entries. It copies the error message and context data to the clipboard as plain text.
* Fixed a PHP error: "Uncaught TypeError: Argument 1 passed to Freemius::get_api_user_scope_by_user() must be an instance of FS_User". 
* Changed the capability that users need to gave to access plugin settings from "update_core" to "install_plugins".
* Users that can't access plugin settings will no longer see a non-functional "Submit" button.

= 1.6.4 =
* Fixed a PHP warning: "fread(): Length parameter must be greater than 0".
* Fixed a conflict with "Go Fetch Jobs (for WP Job Manager)" 1.4.6.
* Tested with WP 5.0.

= 1.6.3 =
* Fixed a bug that caused the plugin to store context data even for errors that were suppressed by error_reporting settings. This could cause the log file to grow very quickly even when there were no (visible) errors.

= 1.6.2 =
* Added a setup wizard that helps new users create a log file and enable error logging. You can still do it manually you prefer. The setup notice will automatically disappear if logging is already configured.
* Fixed a bug where activating the plugin on individual sites in a Multisite network could, in some cases, trigger a fatal error.
* Additional testing with WP 5.0-alpha.

= 1.6.1 =
* Fixed the incremental summary update never starting.

= 1.6 =
* First release of the Pro version.
* Added a "Summary" tab. It groups together identical errors from a specific time period and lets you sort them in a few different ways: by frequency, severity, or how recently they were last seen.
* Added stack traces to all warnings and notices. By default, PHP only generates stack traces for fatal errors or uncaught exceptions, so it can be hard to track down what's causing a notice. Now you can get stack traces for everything without having to install XDebug.
* Added more context to error messages. This varies depending on the situation, but it usually includes the page URL, HTTP referrer, current filter or action, and so on.
* Added a colored dot showing the severity level to each error message. Fatal errors are red, warnings are orange, notices and strict-standards messages are grey, and custom or unrecognized messages are blue.
* Added a new setting for email notifications: "how often to check the log new messages". 
* Added a notice explaining how to configure WordPress to log all types of errors (including PHP notices) instead of just fatal errors and warnings.
* Added Freemius integration.
* Added a link to the Pro version to bottom of the widget.
* Improved parsing of multi-line log entries. Now the plugin will show all of the lines as part of the same message instead of treating every line as an entirely separate error.
* Improved stack trace formatting.
* In Multisite, the dashboard widget now also shows up in the network admin dashboard.
* Changed permissions so that only Super Admins can change plugin settings or clear the log file. Regular administrators can still see the widget.

= 1.5.7 =
* The widget now displays log timestamps in local time instead of UTC.
* Fixed a runtime exception "Backtrack buffer overflow" that was thrown when trying to parse very long log entries.

= 1.5.6 =
* The dashboard widget now shows the log file size and the "Clear Log" button even when all entries are filtered out.
* Tested with WP 4.9 and WP 5.0-alpha.

= 1.5.5 =
* Fixed two PHP notices: "Undefined index: schedule in [...]Cron.php on line 69" and "Undefined index: time in [...]Cron.php on line 76".
* Added "error_reporting(E_ALL)" to the example code to log all errors and notices.
* Tested up to WP 4.9-beta2.

= 1.5.4 =
* Fixed the error "can't use method return value in write context". It was a compatibility issue that only affected PHP versions below 5.5.

= 1.5.3 =
* You can send email notifications to multiple addresses. Just enter a comma-separated list of emails.
* Made sure that email notifications are sent no more often than the configured frequency even when WordPress is unreliable and triggers cron events too frequently.
* Tested up to WP 4.9-alpha-40871.

= 1.5.2 =
* Fixed a fatal error caused by a missing directory. Apparently, SVN externals don't work properly in the wordpress.org plugin repository.

= 1.5.1 =
* Added an option to ignore specific error messages. Ignored messages don't show up in the dashboard widget and don't generate email notifications, but they stay in the log file.
* Added limited support for parsing stack traces generated by PHP 7.
* Made the log output more compact.
* Improved log parsing performance.
* Fixed an "invalid argument supplied for foreach" warning in scbCron.

= 1.5 =
* Added a severity filter. For example, you could use this feature to make the plugin send notifications about fatal errors but not warnings or notices.
* Added limited support for XDebug stack traces. The stack trace will show up as part of the error message instead of as a bunch of separate entries. Also, stack trace items no longer count towards the line limit.

= 1.4.2 =
* Hotfix for a parse error that was introduced in version 1.4.1.

= 1.4.1 =
* Fixed a PHP compatibility issue that caused a parse error in Plugin.php on sites using an old version of PHP.

= 1.4 =
* Added an option to send an email notification when the log file size exceeds the specified threshold.
* Fixed a minor translation bug.
* The widget now shows the full path of the WP root directory along with setup instructions. This should make it easier to figure out the absolute path of the log file.
* Tested with WP 4.6-beta3.

= 1.3.3 =
* Added i18n support.
* Added an `elm_show_dashboard_widget` filter that lets other plugins show or hide the error log widget.
* Tested with WP 4.5.1 and WP 4.6-alpha.

= 1.3.2 =
* Tested up to WP 4.5 (release candidate).

= 1.3.1 =
* Added support for Windows and Mac style line endings.

= 1.3 =
* Added an option to display log entries in reverse order (newest to oldest).
* Added a different error message for the case when the log file exists but is not accessible.
* Only load the plugin in the admin panel and when running cron jobs.
* Fixed the error log sometimes extending outside the widget.
* Tested up to WP 4.4 (alpha version).

= 1.2.4 =
* Tested up to WP 4.2 (final release).
* Added file-based exclusive locking to prevent the plugin occasionally sending duplicate email notifications.

= 1.2.3 =
* Tested up to WP 4.2-alpha.
* Refreshing the page after clearing the log will no longer make the plugin to clear the log again.

= 1.2.2 = 
* Updated Scb Framework to the latest revision.
* Tested up to WordPress 4.0 beta.

= 1.2.1 = 
* Tested up to WordPress 3.9.

= 1.2 = 
* Tested up to WordPress 3.7.1.

= 1.1 = 
* Fixed plugin homepage URL.
* Fix: If no email address is specified, simply skip emailing the log instead of throwing an error.
* Tested with WordPress 3.4.2.

= 1.0 =
* Initial release.
