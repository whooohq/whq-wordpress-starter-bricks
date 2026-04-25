=== DoLogin Security ===
Contributors: WPDO
Tags: Login security, 2FA login, Easy login, Cloudflare Turnstile reCAPTCHA, GeoLocation login limit, limit login attempts, passwordless login
Requires at least: 4.0
Tested up to: 6.8.1
Stable tag: 4.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Easy Login. 2FA login. Passwordless login. Cloudflare Turnstile reCAPTCHA. GeoLocation (Continent/Country/City)/IP range to limit login attempts. Support Whitelist and Blacklist. Support WooCommerce.

== Description ==

In one click, your WordPress login page will be pretected with the smart brute force attack protection! Any login attempts more than 6 in 10 minutes (default value) will be limited.

Limit the number of login attempts through both the login and the auth cookies.

* Two-factor Authentication login.

* Text SMS message passcode for 2nd step verification support.

* Cloudflare Turnstile (better than Google reCAPTCHA).

* GeoLocation (Continent/Country/City) or IP range to limit login attempts.

* Passwordless login link.

* Support Whitelist and Blacklist.

* GDPR compliant. With this feature turned on, all logged IPs get obfuscated (md5-hashed).

* WooCommerce Login supported.

* XMLRPC gateway protection.

= API =

* Call the function `$link = function_exists( 'dologin_gen_link' ) ? dologin_gen_link( 'your plugin name or tag' ) : '';` to generate one passwordless login link for the current user.

* Call the function `$link = function_exists( 'dologin_gen_link' ) ? dologin_gen_link( 'note/tip for this generation', $user_id ) : '';` to generate a passwordless login link for the user which ID is `$user_id`.

The generated one-time used link will be expired after 7 days.

* Define const `SILENCE_INSTALL` to avoid redirecting to setting page after installtion.

= CLI =

* List all passwordless links: `wp dologin list`

* Generate a passwordless link for one username (for the login name `root`): `wp dologin gen root`

* Delete a passwordless link w/ the ID in list (for the record w/ ID 5): `wp dologin del 5`

= How GeoLocation works =

When visitors hit the login page, this plugin will lookup the Geolocation info from API, compare the Geolocation setting (if has) with the whitelist/blacklist to decide if allow login attempts.

== Privacy ==

The online IP lookup service is provided by https://www.doapi.us. The provider's privacy policy is https://www.doapi.us/privacy.

Based on the original code from Limit Login Attemps plugin and Limit Login Attemps Reloaded plugin.

== Screenshots ==

1. Plugin Site Connections
2. Plugin Settings
3. Plugin Passwordless Login
4. Plugin Login Attempts Log
5. Login Page (After sent dynamic code to mobile text message)
6. Login Page (2 times left)
7. Login Page (Too many failure)
8. Login Page (Blacklist blocked)
9. WooCommerce login protection

== Changelog ==

= 4.3 - Jun 11 2025 =
* Generating passwordless link will redirect to the corresponding tab now.

= 4.2 - May 31 2025 =
* 🍀 Cloudflare Turnstile reCAPTCHA.
* 🐞 Fixed 2FA conflict w/ reCAPTCHA.

= 4.1.1 - May 27 2025 =
* Resolved WooCommerce HPOS feature warning.

= 4.1 - May 27 2025 =
* Showed the easy login confirmation landing page.
* Disallowed reuse of login link to prevent possible replay attack.
* Fixed root site pk/sk clear issue in easy login when saving conf.
* Restored reCAPTCHA to previous version.

= 4.0 - May 26 2025 =
* 🍀 `Easy Login` feature! Allow one root WordPress to easy login to multi child WordPress sites.

= 3.8 =
* Security patch per patchstack report.

= 3.7.1 =
* IP vulnerability patch for dashboard widget. (Bob@Jetpack)

= 3.7 =
* IP vulnerability patch. (Bob@Jetpack)

= 3.6 =
* Fixed Google reCAPTCHA authentication failure. (mandotr)

= 3.5.2 =
* Fixed auto upgrade PHP warning. (lavacano)

= 3.5.1 =
* Banner to install qrcode plugin to enable 2FA.

= 3.5 =
* 🍀 Two-factor Authentication.

= 3.4 =
* Bypassed version check to speed up WP6 loading.

= 3.3 =
* Fixed potential duration value in string conversion issue. (wpcrono)

= 3.2 =
* API `dologin_admin_menu_access` to allow other users to config dologin settings. (franfal)

= 3.1 =
* Compatibility improvement when communication failed between client wordpress and DoAPI.us API. (@matteocuellar @ecomturbo @thesaintindiano)

= 3.0 =
* 🍀 Dashboard widget.
* New API for free text message gateway.

= 2.9.4 =
* Fixed IXR_Error PHP notice for XMLRPC login failure.

= 2.9.3 =
* Support translation for login text message. (@merkwert)

= 2.9.2 =
* More accurate to detect IP.

= 2.9.1 =
* 🍀 New setting Google reCAPTCHA on Lost Password Page.

= 2.9 =
* WordPress v5.5 Rest compatibility.

= 2.8 =
* Avoid duplicated login attempt records for one IP in a short time.
* GUI enhancement.

= 2.7.1 =
* Added API info to GUI.

= 2.7 =
* Login Attempts log can be cleared now.

= 2.6 =
* Codebase reformated.

= 2.5 =
* CLI supported.

= 2.4 =
* Passwordless link can be copied in one click.

= 2.3 =
* 🍀 Reverse Matching w/ `!:` feature. Now can use `!:` to exclude one rule. (@jacklinkers)

= 2.2.2 =
* Better IP detection.
* Supported empty line and single line comments for whitelist and blacklist.

= 2.2.1 =
* Declared WooCommerce support up to 4.0.1.

= 2.2 =
* Whitelist and Blacklist support comments now.

= 2.1 =
* Passwordless login will now have a confirm page to avoid auto-visited when sharing the link.

= 2.0 =
* Fresh New GUI!

= 1.9 =
* 🍀 New option: Show reCAPTCHA on Register page. (@ach1992)

= 1.8 =
* 🍀 Show Phone Number field on Register page if Force SMS Auth setting is ON. (@ach1992)

= 1.7.1 =
* 🐞 Will now honor the timezone setting when showing date of sent. (@ducpl)

= 1.7 =
* Supported DoDebug now.
* Bypassed whitelist check for WooCommerce clients on checkout page.
* 🐞 WooCommerce checkout page can now login correctly.

= 1.6 =
* 🍀 Google reCAPTCHA.
* 🐞 WooCommerce can now use same login strategy settings.

= 1.5 =
* 🍀 Test SMS Message feature under Settings page.

= 1.4.7 =
* Language supported.

= 1.4.5 =
* PHP5.3 supported.

= 1.4.4 =
* Doc updates.

= 1.4.3 =
* *API* Silent install mode to avoid redirecting to settings by defining const `SILENCE_INSTALL`

= 1.4.2 =
* *API* Generated link defaults to expire in 7 days.

= 1.4.1 =
* *API* New function `dologin_gen_link( 'my_plugin' )` API to generate a link for current user.

= 1.4 =
* 🍀 Passwordless login link.

= 1.3.5 =
* SMS PHP Warning fix.

= 1.3.4 =
* REST warning fix.

= 1.3.3 =
* GUI cosmetic.

= 1.3.2 =
* 🐞 Fixed a bug that caused not enabled SMS WP failed to login.

= 1.3.1 =
* PHP Notice fix.

= 1.3 =
* 🍀 SMS login support.

= 1.2.2 =
* Auto redirect to setting page after activation.

= 1.2.1 =
* Doc improvement.

= 1.2 =
* 🍀 XMLRPC protection.

= 1.1.1 =
* 🐞 Auto upgrade can now check latest version correctly.

= 1.1 =
* 🍀 *New* Display login failure log.
* 🍀 *New* GDPR compliance.
* 🍀 *New* Auto upgrade.
* *GUI* Setting link shortcut from plugin page.
* *GUI* Display security status on login page.
* 🐞 Stale settings shown after successfully saved.
* 🐞 Duration setting can now be saved correctly.
* 🐞 Fully saved geo location failure log.

= 1.0 - Sep 27 2019 =
* Initial Release.