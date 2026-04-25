# Changelog

# 1.9.1 / 2026-04-25
- **Bug Fix**: Fixed multiselect fields not saving data on deferred updates and restriction settings pages.
- Fixed `fuertewp_restrictions_disable_admin_bar_roles`, `fuertewp_deferred_plugins`, `fuertewp_deferred_themes`, `fuertewp_blocked_plugins`, and `fuertewp_blocked_themes` fields now properly persist selections.
- Updated HyperFields enhanced multiselect template to include hidden select element for form submission.
- Updated HyperFields JavaScript to properly find and update the hidden select element.
- Added HyperFields initialization with version parameter for proper cache busting.
- **Bug Fix**: Fixed plugin status not being enabled by default on fresh installations.
- Added `setup_default_status()` method to activator to ensure `fuertewp_status` defaults to 'enabled' on new installs.
- Enhanced migrator to default plugin status to 'enabled' if not present in legacy options during upgrades.
- Ensured seamless upgrade path from Carbon Fields (1.7.x) to HyperFields (1.8+) with proper status migration.

# 1.9.0 / 2026-04-17
- Added **Blocked Updates** feature to completely prevent updates for specific plugins and themes.
- **Critical Security Feature**: Protect against supply chain attacks by locking plugins at safe versions when developer accounts are compromised
- Prevents auto-installing malicious code during supply chain attacks or developer account breaches
- Implemented full update blocking system that removes items from update transients.
- Blocked items are prevented from both automatic and manual updates.
- Added admin interface fields for selecting plugins/themes to block.
- Implemented update notice hiding for blocked items in WordPress admin.
- Added comprehensive configuration storage and migration support.
- Added unit and integration tests for blocked updates functionality.
- Enhanced documentation with supply chain attack scenarios and security guidance.
- Maintained backward compatibility with existing deferred updates feature.

# 1.8.1 / 2026-04-11
- Migrated entire configuration system from Carbon Fields to the new HyperFields library.
- Implemented `Fuerte_Wp_Config` as a centralized, thread-safe configuration manager.
- Introduced single-array storage pattern (`fuertewp_settings`) for improved performance and cleaner database footprint.
- Added recursive legacy configuration fallback to ensure 0-downtime migrations.
- Integrated HyperFields Data Tools for settings maintenance and migration.
- Removed legacy dependency on `htmlburger/carbon-fields`.
- Purged technical debt: removed manual Carbon Fields asset injection and Elementor conflict workarounds.
- Full PHP 8.2+ compatibility audit and strict types implementation.
- Added comprehensive unit tests for the new configuration and storage logic.

# 1.7.5 / 2025-11-18
- Prevent Carbon Fields from booting in Elementor editor to avoid JS conflicts.

# 1.7.4 / 2025-11-13
- Added comprehensive Login Security system with rate limiting and IP lockout functionality.
- Implemented failed login attempt tracking with configurable thresholds and lockout durations.
- Added real-time AJAX-powered admin interface for monitoring login attempts and managing lockouts.
- Introduced GDPR Privacy Notice feature with customizable message display on login and registration forms.
- Enhanced security with IP-based and username-based lockout mechanisms.
- Added support for blacklisted usernames during registration process.
- Implemented increasing lockout durations for repeated security violations.
- Added comprehensive logging system for security monitoring and forensic analysis.
- Introduced individual unblock functionality for specific IP/username combinations.
- Enhanced admin interface with export capabilities for security data.
- Performance optimizations and database cleanup automation for security logs.
- Improved code organization with dedicated login management and logging classes.
- Added comprehensive Login URL Hiding functionality to obscure wp-login.php access points.
- Implemented support for both query parameter mode (?custom-slug) and pretty URL mode (/custom-slug/).
- Added customizable login slug with default 'secure-login' option for easy configuration.
- Integrated wp-admin protection to redirect unauthorized admin area requests to custom login URL.
- Added hidden field validation to login forms for enhanced security against direct POST attacks.
- Comprehensive URL filtering system covering site_url, login_url, logout_url, lostpassword_url, and register_url.
- Full integration with existing super user bypass system and security logging.
- Implemented proper redirect handling with 404-like behavior for blocked login attempts.
- Enhanced security through obscurity while maintaining WordPress core compatibility.

# 1.6.0 / 2025-09-20
- Refactored auto-update system into dedicated `Fuerte_Wp_Auto_Update_Manager` class for better code organization and maintainability.
- Updated Carbon Fields dependency to the latest version for better PHP 8.x+ compatibility.
- Bug fixes and performance improvements.

# 1.5.1 / 2024-07-24
- Improved auto-updates. Added a new scheduled task to force WordPress to perform the update routine every 6 hours, only when some auto-updates are enabled.

# 1.4.12 / 2023-11-06
- Enhanced disabling of XML-RPC API.
- Disable re-running of the WP setup wizard.
- Disable execution of PHP files inside uploads folder.
- Updated Carbon Fields to fix PHP 8.x and WP 6.2 compatibility issues.

# 1.4.4 / 2023-02-20
- Avoids a PHP Warning when getting the website's domain name.

# 1.4.3 / 2022-11-28
- REST API enabled by default for all users. Needed for the modern WordPress themes and other plugins.
- Resolved an issue with Elementor Template Kits import.

# 1.4.2 / 2022-07-22
- PHP 8.x compatibility check.
- Tested up to WordPress 6.0.
- Added an option to enable/disble the sender email address.
- Added an option to disable Customizer additional CSS editor.
- Added an option to force REST API usage to logged in users only.
- Fixed a compatibility bug with Elementor's editor.

# 1.3.11 / 2022-01-11
- Fixed bug with un-trimmed advanced restrictions.
- Fixed a bug with cached options values (transient).

# 1.3.8 / 2021-10-08
- Option to configure Theme Editor restriction.
- Option to configure Plugin Editor restriction.
- Option to configure new plugins installation restriction.
- Option to configure new themes installation restriction.
- Fixed some translation errors.

# 1.3.5 / 2021-09-13
- PHP 7.3 compatibility.

# 1.3.1 / 2021-08-27
- Reworked as full featured plugin.
- Added an options page for easy configuration.
- New logo, courtesy of [Nicolás Franz](https://nicolasfranz.com). Many thanks, pal!

# 1.2.0 / 2021-08-13
- Converted to a plugin.
- Added ability to access plugins management, but don't allow install or upload new plugins. Also Fuerte-WP will auto-protect itself from deactivation.
- Added ability to restrict access to Permalinks configuration.
- Added ability to use custom site logo (from Customizer) as WP login logo.

# 1.1.3 / 2021-04-22
- Added ability to disable the old XML-RPC API.
- Now it hides ACF cog inside ACF meta fields UI, and prevent opening ACF custom post type (acf-field-group), to avoid non super admins to access ACF editing UI. So, non super-admin users can't access ACF Custom Fields UI, even if they put the URL directly into the address bar.

# 1.1.2 / 2021-04-16
- Added missing support to disable update emails for plugins and themes.

## 1.1.1 / 2021-04-09
- Added support to control several WP's automatic emails.
- Added support to disable WP admin bar for specific roles.

## 1.1.0 / 2021-04-07
- Fuerte-WP configuration file now lives outside wp-config.php file, into his own wp-config-fuerte.php file. This to make it easier to deploy it to several WP installations, without the need to edit the wp-config.php file in all of them. Check the readme on how to install it.
- Added option to enable or disable strong passwords enforcing.
- Added support to prevent use of weak passwords.
- Added support for remove_menu_page.
- Added ability to disable WordPress's new Application Passwords feature.

## 1.0.1 / 2020-10-29
- Now using a proper Class.
- Added option to change WP sender email address.
- Added configuration to remove custom submenu items (remove_submenu_page).
- Force user creation and editing to use WP default strong password suggestion, for non super users.
- Prevent admin accounts creation or edition, for non super users.
- Customizable not allowed error message.

## 1.0.0 / 2020-10-27
- Initial release.
- Enable and force auto updates for WP core.
- Enable and force auto updates for plugins.
- Enable and force auto updates for themes.
- Enable and force auto updates for translations.
- Disables email triggered when WP auto updates.
- Change [WP recovery email](https://make.wordpress.org/core/2019/04/16/fatal-error-recovery-mode-in-5-2/) so WP crashes will go to a different email than the Administration Email Address in WP General Settings.
- Disables WP theme and plugin editor for non super users.
- Remove items from WP menu for non super users.
- Restrict editing or deleting super users.
- Disable ACF Custom Fields editor access for non super users.
- Restrict access to some pages inside wp-admin, like plugins or theme uploads, for non super users. Restricted pages can be extended vía configuration.
