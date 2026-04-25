=== GenTime: Inspect page generation time ===
Contributors: Cybr
Tags: admin bar, generation, performance, time, php
Requires at least: 5.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GenTime accurately shows the page generation time in your WordPress admin bar.

== Description ==

= GenTime =

This plugin shows site administrators the time in seconds of how fast the page is generated in the WordPress top admin bar.

The generation time is calculated from when the server receives the page request to when the number is printed, which is close to the end of the request.

So, just PHP and the database impact this calculation. The time it takes to send the page from the server to your device is not included in this timer.

That's it, pretty simple!

== Installation ==

1. Install GenTime either via the WordPress.org plugin directory or upload the files to your server.
1. Either Network Activate this plugin or activate it on a single site.
1. That's it!

= Change the number of decimals =

Use this filter to change the number of decimals shown by the timer. The number defaults to 3, but this filter makes it 4.
`
add_filter( 'gentime_decimals', fn( $default = 3 ) => 4 );
`

([Where can I place filters?](https://tsf.fyi/docs/filters#where))

= Change the view capability requirement =

Add this to `wp-config.php` to change the user capability required to view the timer:
`
define( 'GENTIME_VIEW_CAPABILITY', 'manage_options' );
`

([List of capabilities](https://wordpress.org/documentation/article/roles-and-capabilities/#capabilities))

== Changelog ==

= 2.0.0 =

* Changed: The plugin has been rewritten to adhere to my latest coding standards — its old functions are no longer available.
* Changed: This plugin now requires PHP 7.4, from PHP 5.2.
* Changed: The default timer view capability now defaults to `'manage_options'` instead of `'install_plugins'`.
* Removed: Filter `'gentime_minimum_role'` is gone. Use constant `GENTIME_VIEW_CAPABILITY` instead.

= 1.1.0 =
* Added: Now uses WordPress 5.8's more accurate function, when available, `timer_float()`.
* Updated: Now uses HTML5 for the styles output.
* Other: Refactored the plugin to support PHP 5.6 or later, from 5.2 or later.
* Other: Cleaned up code.

= 1.0.4 =
* Fixed: This plugin is now converted to UNIX line feed.
* Improved: Early sanitation of translation strings.
* Updated: POT file.
* Confirmed: WordPress 4.6 support.
* Other: The plugin license has been upgraded to GPLv3.
* Other: Cleaned up code.

= 1.0.3 =
* Fixed: The cache now works as intended.
* Fixed/Improved: Erroneous order of function checking. Which actually had no impact.
* Other: `gentime_minimum_role` filter now converts input to string.

= 1.0.2 =
* Added: POT translation file.
* Improved: Slightly improved performance (every Herz counts) by adding PHP runtime static cache earlier.
* Confirmed: WordPress 4.5+ compatibility.
* Cleaned up code.

= 1.0.1 =
* Changed: Minimum capability from edit_plugins to install_plugins so that the generation time is still shown when the Editor has been disabled.
* Added: PHP Staticvar caching for capability.
* Confirmed: 4.4.0+ support.
* Cleaned up PHP.

= 1.0.0 =
* Initial Release
