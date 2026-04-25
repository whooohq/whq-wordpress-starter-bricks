# Fuerte-WP

<p align="center">
	<img src="https://github.com/EstebanForge/Fuerte-WP/blob/master/.wp-org-assets/icon-256x256.png?raw=true" alt="Fuerte-WP Logo" />
</p>

Take control of your WordPress security & maintenance. Automate plugin updates, manage administrator access, and prevent broken functionality from outdated plugins without proper oversight.

Fuerte-WP is the ultimate WordPress security & maintenance solution that combines automated updates with administrator oversight to prevent plugin conflicts before they break your site.

Available at the official [WordPress.org plugins repository](https://wordpress.org/plugins/fuerte-wp/).

## Why?

Is your WordPress site suffering from plugin neglect? Every day, thousands of sites break due to outdated plugins, untested updates, and lack of proper maintenance oversight.

**⚠️ THE REALITY:**
- 90% of WordPress site failures are caused by outdated plugins, themes or incompatible updates
- Most WordPress downtime happens from untested plugin updates by administrators with too much freedom
- Your WordPress installation is only as reliable as your maintenance routine

**🔥 WHY FUERTE-WP IS DIFFERENT:**

Most maintenance plugins just alert you AFTER something breaks. Fuerte-WP PREVENTS issues before they happen, combining automated updates with access control that works together seamlessly.

**🚨 CRITICAL SECURITY PROTECTION:** Supply chain attacks happen when even trustworthy developers have their accounts compromised. Attackers push malicious updates that thousands of sites auto-install within hours. Fuerte-WP's **Blocked Updates** lets you freeze plugins at safe versions when you learn of compromises, protecting your site when even developers can't protect their own update systems.

Fuerte-WP auto-protects itself and cannot be disabled, unless your account is declared as super user, or you have access to the server (FTP, SFTP, SSH, cPanel/Plesk, etc.).

## Auto-Update Management System

🚨 **Intelligent Update Scheduling & Control**

Fuerte-WP's Auto-Update Management System provides comprehensive control over WordPress maintenance:

### 📅 Update Scheduling
- **Intelligent Update Scheduling**: Configurable update frequency (default: every 12 hours)
- **Selective Updates**: Choose which plugins, themes, and core components to auto-update
- **Compatibility Monitoring**: Track which updates are safe and tested
- **Real-Time Update Dashboard**: Live dashboard showing current update status and scheduled maintenance

### 🛡️ Update Control Modes
- **Deferred Updates**: Exclude specific plugins/themes from auto-updates while allowing manual updates
- **Blocked Updates**: Completely prevent updates for specific plugins/themes (both automatic and manual)
  - Blocked items are removed from update transients entirely
  - No update notices appear in the WordPress admin
  - **Supply Chain Attack Protection**: Lock plugins at safe versions when developer accounts are compromised
  - Use when even trustworthy developers can't protect their own update systems
  - Perfect for responding to security incidents and compromised plugins

### 👑 Administrator Oversight
- **Super User Access**: Designate who has full maintenance control (YOU) while restricting others
- **Role-Based Permissions**: Granular control over what different admin roles can modify
- **Plugin & Theme Management**: Prevent other admins from installing unstable plugins or untested updates
- **Menu Management**: Hide sensitive WordPress settings from inexperienced administrators

### 📊 Maintenance Command Center
- **Live Update Monitoring**: Real-time AJAX dashboard shows plugin/theme updates as they happen
- **Detailed Maintenance Logs**: Comprehensive logging with timestamps, versions, and compatibility notes
- **Export Maintenance Data**: Download update reports for analysis or compliance
- **Smart Notifications**: Get alerted about available updates and maintenance tasks

### 🇪🇺 Email Management
- **Recovery Email Routing**: Route WordPress admin emails to the right maintenance team
- **Custom Sender Configuration**: Professional email sender setup that matches your domain
- **Email Audit Trail**: Logging that helps with maintenance communication tracking

### 🔐 Optional: Admin Access Management
*For organizations with multiple administrators*

- **Custom Login Endpoints**: Create dedicated maintenance access points
- **Smart Redirection**: Guide users to appropriate admin areas based on permissions
- **WP-Admin Access Control**: Restrict direct `/wp-admin/` access for specific user roles

**Note**: These features are optional and should be used based on your specific organizational needs.

## Key Features

### ⚙️ Advanced WordPress Optimization
- **Automated Update Management**: Background updates for core, plugins, themes, and translations
- **Deferred Updates**: Exclude specific plugins/themes from auto-updates while maintaining manual control
- **Blocked Updates**: Completely prevent updates for critical plugins/themes (automatic and manual)
- **API Optimization**: Disable unused XML-RPC endpoints and optimize REST API access
- **Email Configuration**: Customize WordPress recovery and sender email addresses
- **Performance Hardening**: Disable unused features, optimize database performance
- **Background Processing**: Maintenance tasks that don't slow down your site

### 👑 Administrator Oversight System
- **Super User Control**: Designate who has full maintenance access while restricting others
- **Role-Based Permissions**: Granular control over what different admin roles can modify
- **Plugin & Theme Management**: Prevent other admins from installing unstable plugins or untested updates
- **Menu Management**: Hide sensitive WordPress settings from inexperienced administrators
- **User Account Protection**: Protect maintenance accounts from being modified by other admins

### 📊 Maintenance Command Center
- **Live Update Monitoring**: Real-time AJAX dashboard shows plugin/theme updates as they happen
- **Detailed Maintenance Logs**: Comprehensive logging with timestamps, versions, and compatibility notes
- **Export Maintenance Data**: Download update reports for analysis or compliance
- **Smart Notifications**: Get alerted about available updates and maintenance tasks
- **One-Click Management**: Instantly schedule updates, clear logs, or manage maintenance tasks

### 🔧 Developer Features
- **File-Based Configuration**: Support for `wp-config-fuerte.php` for mass deployment
- **Configuration Caching**: Optimized performance with intelligent caching
- **Hook System**: Extensible architecture with comprehensive WordPress hook integration
- **Multisite Support**: Compatible with WordPress multisite installations

**🔒 WHY CHOOSE FUERTE-WP?**

✅ **PROACTIVE MAINTENANCE** - Prevents plugin conflicts BEFORE they break your site
✅ **INTELLIGENT UPDATE MANAGEMENT** - Real-time update scheduling and compatibility checking
✅ **ADMIN OVERSIGHT CONTROL** - Controls what other administrators can modify
✅ **EMAIL MANAGEMENT** - Built-in email routing and configuration features
✅ **PERFORMANCE OPTIMIZED** - Won't slow down your website
✅ **MULTISITE COMPATIBLE** - Works on single sites and WordPress networks
✅ **SELF-PROTECTING** - Cannot be disabled by non-super users
✅ **DEVELOPER FRIENDLY** - File-based configuration for mass deployment
✅ **SMART MAINTENANCE APPROACH** - Focuses on prevention over reactive fixes

**🎯 PERFECT FOR:**
- Multi-author blogs and news sites with frequent content updates
- Client websites built by agencies that need reliable maintenance
- E-commerce stores with critical uptime requirements
- Educational institutions with multiple WordPress installations
- Enterprise WordPress deployments requiring strict maintenance policies
- **Sites concerned about supply chain attacks** - protect yourself when developer accounts are compromised
- Anyone serious about WordPress maintenance and reliability

## How to Install

**⚡ INSTALL IN SECONDS, MAINTAIN FOR YEARS**

1. Click "Install Now" or search for "Fuerte-WP" in your WordPress dashboard
2. Activate the plugin
3. Visit Settings > Fuerte-WP to configure the settings as you like. Defaults are good if you want to leave them like that
4. Congratulations! Your WordPress site is now professionally maintained.

### Harder configuration (optional)

Fuerte-WP allows you to configure it "harder". This way, Fuerte-WP options inside wp-admin panel aren't even shown at all. Useful to mass deploy Fuerte-WP configuration to multiple WordPress installations.

To use the harder configuration, follow this steps:

- Download a copy of [```config-sample/wp-config-fuerte.php```](https://github.com/EstebanForge/Fuerte-WP/blob/master/config-sample/wp-config-fuerte.php) file, and set it up with your desired settings. Edit and tweak the configuration array as needed.

- Upload your tweaked ```wp-config-fuerte.php``` file to your WordPress's root directory. This usually is where your wp-config.php file resides.

- When Fuerte-WP detects that file, it will load the configuration from it. This will bypass the DB values from the options page, completely.

#### Config file updates

To check if your ```wp-config-fuerte.php``` file need an update, follow this steps:

Check the default [```config-sample/wp-config-fuerte.php```](https://github.com/EstebanForge/Fuerte-WP/blob/master/config-sample/wp-config-fuerte.php) file. The header of the sample config will have the version when it was last modified.

Then check out your own ```wp-config-fuerte.php``` file. If yours has a lower version number, then you need to update your settings array.

Compare your config with the [default wp-config-fuerte.php file](https://github.com/EstebanForge/Fuerte-WP/blob/master/config-sample/wp-config-fuerte.php) and add the new/missing settings to your file. You can use [Meld](https://meldmerge.org), [WinMerge](https://winmerge.org), [Beyond Compare](https://www.scootersoftware.com), [Kaleidoscope](https://kaleidoscope.app), [Araxis Merge](https://www.araxis.com/merge/), [Diffchecker](https://www.diffchecker.com) or any similar software diff to help you here.

Upload your updated ```wp-config-fuerte.php``` to your WordPress's root directory and replace the old one.

Don't worry. New Fuerte-WP features that need new configuration values will not run or affect you until you upgrade your config file and add the new/missing settings.

## Documentation

For detailed documentation, please see:

- **[FAQ](docs/FAQ.md)** - Frequently Asked Questions
- **[Changelog](CHANGELOG.md)** - Version history and changes
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Deployment instructions and best practices
- **[Composer Commands](docs/COMPOSER_COMMANDS.md)** - Available composer scripts for development
- **[Security Policy](SECURITY.md)** - Security reporting and policies
- **[CLAUDE.md](CLAUDE.md)** - Project architecture and development guidelines (for contributors)
- **[TODO](docs/TODO.md)** - Planned features and development roadmap

## FAQ

Check the [full FAQ here](docs/FAQ.md).

## Suggestions, Support

Please, open [a discussion](https://github.com/EstebanForge/Fuerte-WP/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/EstebanForge/Fuerte-WP/issues).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
