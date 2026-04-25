# Frequently Asked Questions

## Table of Contents

- [General](#general)
- [Login Security](#login-security)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Technical](#technical)
- [Migration](#migration)

---

## General

### **What is Fuerte-WP?**

Fuerte-WP is a comprehensive WordPress security plugin that provides multiple layers of protection including login security, access control, administrator restrictions, and configuration management.

### **What makes Fuerte-WP different from other security plugins?**

Fuerte-WP focuses on **proactive protection** rather than reactive alerts. Key differentiators include:

- **Intelligent Rate Limiting**: Real-time attack detection and prevention
- **Administrator Control**: Limit what other administrators can do
- **File-Based Configuration**: Deploy settings across multiple sites easily
- **Self-Protection**: Plugin cannot be disabled by non-super users
- **Zero Performance Impact**: Lightweight design that won't slow your site
- **Smart Security Approach**: Focuses on real protection over security by obscurity

### **Is Fuerte-WP suitable for beginners?**

Absolutely! Fuerte-WP is designed with smart defaults that work out of the box. Simply install, add yourself as a super user, and you're protected. Advanced features are optional.

### **Does Fuerte-WP work with multisite networks?**

Yes! Fuerte-WP is fully compatible with WordPress multisite installations and can be network-activated for centralized management.

---

## Login Security

### **What is Login URL Hiding?**

Login URL Hiding (disabled by default) replaces your default `wp-login.php` URL with a custom URL, making it harder for automated bots and attackers to find your login page. This is an optional security-by-obscurity feature for users who want additional layers of protection.

### **How do I set up a custom login URL?**

1. Go to **Settings → Fuerte-WP → Login Security**
2. Enable **Login URL Hiding**
3. Set your **Custom Login Slug** (e.g., `secure-login`)
4. Choose **URL Type**:
   - **Query Parameter**: `yoursite.com/?secure-login`
   - **Pretty URL**: `yoursite.com/secure-login/`
5. Save changes

Your new login URL will be displayed in the admin panel.

### **What happens to the old wp-login.php URL?**

When Login URL Hiding is enabled:
- Direct access to `wp-login.php` is blocked
- Visitors are redirected according to your **Invalid Login Redirect** setting
- `/wp-admin/` access is also protected for non-logged-in users

### **Can I still access wp-admin directly?**

Only **super users** can access `/wp-admin/` directly when logged in. All other users must use the custom login URL.

### **What is Rate Limiting and Lockout Protection?**

Fuerte-WP automatically blocks IP addresses after too many failed login attempts:

- **Default**: 5 failed attempts within 15 minutes
- **Lockout Duration**: Configurable (default 60 minutes)
- **Progressive Lockouts**: Optional exponentially increasing lockout durations
- **IP & Username Tracking**: Blocks both specific IPs and usernames

### **What is the GDPR Privacy Notice?**

A customizable privacy message displayed on login and registration forms to inform users about data processing. If left empty, a default message is shown.

### **How do I view login attempts and lockouts?**

Go to **Settings → Fuerte-WP** and you'll see the **Login Security Dashboard** showing:
- Real-time login attempts
- Active lockouts
- IP addresses and usernames
- Ability to unblock specific entries
- Export functionality for analysis

### **Can I export login attempt data?**

Yes! Use the **Export CSV** button in the Login Security Dashboard to download all security data for analysis or backup purposes.

---

## Configuration

### **What's the difference between database and file configuration?**

**Database Configuration** (Default):
- Managed through WordPress admin interface
- Easy to change for individual sites
- Stored in WordPress database

**File Configuration** (`wp-config-fuerte.php`):
- Stored in a physical file in WordPress root
- **Higher priority** than database settings
- Ideal for mass deployment
- Version control friendly
- Cannot be changed through admin interface

### **How do I use file-based configuration?**

1. Copy `config-sample/wp-config-fuerte.php` to your WordPress root
2. Rename it to `wp-config-fuerte.php`
3. Edit the configuration array with your settings
4. Upload to your WordPress root directory

When the file exists, Fuerte-WP automatically uses it and hides the admin settings interface.

### **What is a Super User?**

A super user is an administrator who bypasses all Fuerte-WP restrictions. They can:
- Access all admin areas
- Install/edit plugins and themes
- Modify Fuerte-WP settings
- Cannot be locked out by security features

Add your email address to the Super Users list immediately after installation!

### **Can I be locked out of my own site?**

No! Super users can never be locked out, even if they:
- Exceed rate limiting thresholds
- Use weak passwords
- Try to access restricted areas
- Attempt to disable the plugin

Always ensure your email is in the Super Users list.

### **How do I reset or clear settings?**

**Database Settings**: Use the **Clear All Logs** button in the Login Security Dashboard.

**File Settings**: Edit or delete the `wp-config-fuerte.php` file.

---

## Troubleshooting

### **I can't access my site after enabling Login URL Hiding!**

Don't worry! As a super user, you have several options:

1. **Direct wp-admin access**: Go to `/wp-admin/` (super users can still access this)
2. **Check your custom URL**: Use the login URL displayed in the admin panel
3. **Edit config file**: If using file config, disable `login_url_hiding_enabled`
4. **Database reset**: Use a database tool to set `fuertewp_login_url_hiding_enabled` to empty

### **The admin interface is missing after installing file config!**

This is normal behavior! When `wp-config-fuerte.php` exists, the admin interface is hidden to prevent conflicts. Edit the file directly to make changes.

### **Login URL Hiding isn't working!**

Check these common issues:

1. **Permalinks**: Ensure your permalink structure is set to "Post name" or "Day and name"
2. **Conflicts**: Deactivate other security or login-related plugins temporarily
3. **Server Configuration**: Some servers may require additional rewrite rules
4. **Cache**: Clear your server cache and browser cache

### **I'm getting 404 errors on custom login URL!**

This usually indicates a server configuration issue:

1. **Check .htaccess**: Ensure WordPress rewrite rules are present
2. **Server Modules**: Verify `mod_rewrite` is enabled (Apache) or rewrite rules work (Nginx)
3. **File Permissions**: Ensure WordPress can write to `.htaccess`
4. **Try query parameter mode** if pretty URLs don't work

### **Rate limiting is too aggressive/lenient!**

Adjust these settings in **Settings → Fuerte-WP → Login Security**:

- **Maximum Login Attempts**: Increase for more lenient, decrease for stricter
- **Lockout Duration**: Adjust how long users are locked out
- **Increasing Lockout**: Enable for progressive penalties

### **My legitimate users are getting locked out!**

Common solutions:

1. **Whitelist usernames**: Add known usernames to the whitelist
2. **Adjust thresholds**: Increase maximum attempts or reduce lockout duration
3. **Check bot protection**: Ensure it's not blocking legitimate traffic
4. **Monitor logs**: Review what's triggering lockouts

---

## Technical

### **What server requirements does Fuerte-WP need?**

- **WordPress**: 6.0 or higher
- **PHP**: 8.1 or higher
- **Memory**: 64MB minimum (128MB recommended)
- **Web Server**: Apache with mod_rewrite OR Nginx with proper rewrite rules

### **Does Fuerte-WP slow down my website?**

No! Fuerte-WP is optimized for performance:
- Intelligent caching minimizes database queries
- Background processing for auto-updates
- Lightweight code without unnecessary bloat
- No impact on page load times

### **Is Fuerte-WP compatible with caching plugins?**

Yes! Fuerte-WP works well with popular caching plugins like:
- WP Rocket
- W3 Total Cache
- WP Super Cache
- LiteSpeed Cache

The plugin automatically handles cache invalidation when settings change.

### **Can I use Fuerte-WP with other security plugins?**

Generally yes, but be aware of potential conflicts:

- **Login Security**: May conflict with other login protection plugins
- **Firewall Plugins**: Usually compatible (Wordfence, Sucuri, etc.)
- **Malware Scanning**: Fully compatible
- **Backup Plugins**: Fully compatible

If you experience issues, try deactivating other security plugins temporarily.

### **Does Fuerte-WP work with CDN services?**

Yes! Fuerte-WP is compatible with CDNs like:
- Cloudflare
- AWS CloudFront
- MaxCDN
- KeyCDN

For proper IP detection, you may need to configure custom IP headers in the advanced settings.

### **What data does Fuerte-WP store?**

Fuerte-WP stores:
- **Login attempts**: IP, username, timestamp, user agent (configurable retention)
- **Lockout records**: IP, lockout duration, reason
- **Configuration**: Plugin settings in database or config file
- **Security logs**: For monitoring and analysis

All data is stored securely in your WordPress database.

---

## Migration

### **How do I migrate from another security plugin?**

1. **Install Fuerte-WP** alongside your current plugin
2. **Configure basic settings** (super users, login security)
3. **Test functionality** with the new plugin active
4. **Deactivate old plugin** once you're satisfied
5. **Clear old plugin data** if desired

### **Can I import settings from other plugins?**

Fuerte-WP doesn't have direct import functionality, but you can manually configure similar settings. Most security concepts (rate limiting, IP blocking, etc.) are supported.

### **How do I backup my Fuerte-WP settings?**

**File Configuration**: Your `wp-config-fuerte.php` file is your backup

**Database Configuration**: Use WordPress export tools or your hosting provider's backup system

**Login Logs**: Use the Export CSV feature in the admin dashboard

---

## Nginx Configuration

For Nginx servers, add these rules to your server block:

```nginx
# BEGIN Fuerte-WP
location ~ wp-admin/install(-helper)?\.php {
    deny all;
}

location ~* /(?:uploads|files)/.*.php$ {
    deny all;
    access_log off;
    log_not_found off;
}

# Custom login URL support (replace 'secure-login' with your slug)
location ~ ^/secure-login/?$ {
    try_files $uri $uri/ /index.php?$args;
}
# END Fuerte-WP
```

Replace `secure-login` with your actual custom login slug if using pretty URLs.

---

## Still Need Help?

If you can't find your answer here:

1. **Check the README**: Comprehensive documentation of all features
2. **Search Issues**: Check [GitHub Issues](https://github.com/EstebanForge/Fuerte-WP/issues) for similar problems
3. **Open a Discussion**: Start a [GitHub Discussion](https://github.com/EstebanForge/Fuerte-WP/discussions)
4. **Report Bugs**: File a new issue if you've found a bug

Remember: As a super user, you always have access to manage Fuerte-WP settings and can never be permanently locked out!