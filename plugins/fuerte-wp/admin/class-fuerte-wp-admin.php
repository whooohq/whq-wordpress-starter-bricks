<?php

use HyperFields\Field;
use HyperFields\OptionsPage;

class Fuerte_Wp_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register saved hook for HyperFields
        add_action('hf_options_page_saved', [$this, 'handle_settings_saved'], 10, 2);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        // Only load on Fuerte-WP admin pages for performance
        $screen = get_current_screen();

        if (!$screen || !strpos($screen->id, 'fuerte-wp')) {
            return;
        }

        // HyperFields native CSS is loaded automatically
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        // Only load on Fuerte-WP admin pages for performance
        $screen = get_current_screen();

        if (!$screen || !strpos($screen->id, 'fuerte-wp')) {
            return;
        }

        // HyperFields native JS is loaded automatically
        // No need for custom enhancement scripts
    }

    public function fuertewp_plugin_options()
    {
        global $fuertewp;

        // Early exit if not a super admin - fallback capability check for migration compatibility
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle debug actions for troubleshooting cache issues
        if (isset($_GET['fuertewp_action']) && current_user_can('manage_options')) {
            switch ($_GET['fuertewp_action']) {
                case 'debug_cache':
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $debug_info = Fuerte_Wp_Config::debug_cache();
                        echo '<div class="notice notice-info"><pre>';
                        echo 'Fuerte-WP Cache Debug Information:' . "\n";
                        echo '=====================================' . "\n";
                        echo 'Cache Key: ' . $debug_info['cache_key'] . "\n";
                        echo 'Cache Exists: ' . ($debug_info['cache_exists'] ? 'Yes' : 'No') . "\n";
                        echo 'Cache Super Users: ' . print_r($debug_info['cache_super_users'], true) . "\n";
                        echo 'Fresh Super Users: ' . print_r($debug_info['fresh_super_users'], true) . "\n";
                        echo 'Cache Match: ' . ($debug_info['cache_match'] ? 'Yes' : 'No') . "\n";
                        echo 'Current URL: ' . $debug_info['current_url'] . "\n";
                        echo 'Current Screen: ' . $debug_info['current_screen'] . "\n";
                        echo '</pre></div>';
                    }
                    break;

                case 'clear_cache':
                    Fuerte_Wp_Config::invalidate_cache();
                    echo '<div class="notice notice-success"><p>Fuerte-WP configuration cache cleared.</p></div>';
                    break;

                case 'force_refresh':
                    $fresh_config = Fuerte_Wp_Config::force_refresh();
                    echo '<div class="notice notice-success"><p>Fuerte-WP configuration refreshed from database.</p>';
                    echo '<p>Super Users in database: ' . (isset($fresh_config['super_users']) ? print_r($fresh_config['super_users'], true) : 'Not set') . '</p></div>';
                    break;
            }
        }

        /*
         * Allow admin options for super users even if config file exists
         */
        if (
            file_exists(ABSPATH . 'wp-config-fuerte.php')
            && is_array($fuertewp)
            && !empty($fuertewp)
        ) {
            // Check if current user is a super user
            $current_user = wp_get_current_user();
            $is_super_user = isset($fuertewp['super_users']) &&
                           in_array(strtolower($current_user->user_email), $fuertewp['super_users']);

            // Only hide options if not a super user
            if (!$is_super_user) {
                return;
            }

            // Show a read-only notice for super users when config file exists
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('Configuration File Mode', 'fuerte-wp') . '</strong><br>';
            echo __('Fuerte-WP is currently configured via wp-config-fuerte.php file. Some settings may be read-only.', 'fuerte-wp');
            echo '</p></div>';
        }

        // Get site's domain. Avoids error: Undefined array key "SERVER_NAME".
        $domain = parse_url(get_site_url(), PHP_URL_HOST);

        $page = OptionsPage::make(__('Fuerte-WP', 'fuerte-wp'), 'fuerte-wp-options')
            ->setParentSlug('options-general.php')
            ->setOptionName('fuertewp_settings');

        // Define tabs with proper titles
        $page->addTab('main', __('Main', 'fuerte-wp'));
        $page->addTab('emails', __('E-mails', 'fuerte-wp'));
        $page->addTab('login_security', __('Login Security', 'fuerte-wp'));
        $page->addTab('rest_api', __('REST API', 'fuerte-wp'));
        $page->addTab('restrictions', __('Restrictions', 'fuerte-wp'));
        $page->addTab('advanced_restrictions', __('Advanced', 'fuerte-wp'));
        $page->addTab('ip_lists', __('IP Lists', 'fuerte-wp'));
        $page->addTab('failed_logins', __('Logins', 'fuerte-wp'));
        $page->addTab('deferred_updates', __('Updates', 'fuerte-wp'));

        // Main Options Tab
        $main_section = $page->addSectionToTab('main', 'main_section', __('Main Options', 'fuerte-wp'));
        $main_section
            ->addField(
                Field::make('checkbox', 'fuertewp_status', __('Enable Fuerte-WP.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Check the option to enable Fuerte-WP.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_super_users', __('Super Administrators.', 'fuerte-wp'))
                    ->setOptions(fuertewp_get_admin_users())
                    ->setEnhanced(true)
                    ->setHelp(__('Users that will not be affected by Fuerte-WP rules. Format: Username [email]. Search to find users, then click to select.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_separator_general', __('General', 'fuerte-wp'))
            )
            ->addField(
                Field::make('text', 'fuertewp_access_denied_message', __('Access denied message.', 'fuerte-wp'))
                    ->setDefault('Access denied.')
                    ->setHelp(__('General access denied message shown to non super users.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('text', 'fuertewp_recovery_email', __('Recovery email.', 'fuerte-wp'))
                    ->setDefault('')
                    ->addArg('type', 'email')
                    ->addArg('help_is_html', true)
                    ->setHelp(sprintf(
                        __('Admin recovery email. If empty, dev@%s will be used.<br/>This email will receive fatal errors from WP, and not the administration email in the General Settings. Check <a href="https://make.wordpress.org/core/2019/04/16/fatal-error-recovery-mode-in-5-2/" target="_blank">fatal error recovery mode</a>.', 'fuerte-wp'),
                        $domain
                    ))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_sender_email_enable', __('Use a different sender email.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->addArg('help_is_html', true)
                    ->setHelp(sprintf(
                        __('Use a different email (than the <a href="%s">administrator one</a>) for all emails that WordPress sends.', 'fuerte-wp'),
                        admin_url('options-general.php')
                    ))
            )
            ->addField(
                Field::make('text', 'fuertewp_sender_email', __('Sender email.', 'fuerte-wp'))
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        [
                            'field' => 'fuertewp_sender_email_enable',
                            'value' => true,
                            'compare' => '=',
                        ],
                    ])
                    ->setDefault('')
                    ->addArg('type', 'email')
                    ->addArg('help_is_html', true)
                    ->setHelp(sprintf(
                        __('Default site sender email. If empty, no-reply@%1$s will be used.<br/>Emails sent by WP will use this email address. Make sure to check your <a href="https://mxtoolbox.com/SPFRecordGenerator.aspx?domain=%1$s&prefill=true" target="_blank">SPF Records</a> to avoid WP emails going to spam.', 'fuerte-wp'),
                        $domain
                    ))
            )
            ->addField(
                Field::make('heading', 'fuertewp_separator_updates', __('Updates', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_autoupdate_core', __('Auto-update WordPress core.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Auto-update WordPress to the latest stable version.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_autoupdate_plugins', __('Auto-update Plugins.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Auto-update Plugins to their latest stable version.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_autoupdate_themes', __('Auto-update Themes.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Auto-update Themes to their latest stable version.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_autoupdate_translations', __('Auto-update Translations.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Auto-update Translations to their latest stable version.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('select', 'fuertewp_autoupdate_frequency', __('Update check frequency', 'fuerte-wp'))
                    ->setOptions([
                        'six_hours' => __('Every 6 hours', 'fuerte-wp'),
                        'twelve_hours' => __('Every 12 hours', 'fuerte-wp'),
                        'daily' => __('Every 24 hours', 'fuerte-wp'),
                        'twodays' => __('Every 48 hours', 'fuerte-wp'),
                    ])
                    ->setDefault('twelve_hours')
                    ->setHelp(__('How often to check for and apply updates.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_separator_tweaks', __('Tweaks', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_tweaks_use_site_logo_login', __('Use site logo at login.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->addArg('help_is_html', true)
                    ->setHelp(sprintf(
                        __('Use your site logo, uploaded via <a href="%s" target="_blank">Customizer > Site Identity</a>, for WordPress login page.', 'fuerte-wp'),
                        admin_url('customize.php?return=%2Fwp-admin%2Foptions-general.php%3Fpage%3Dfuerte-wp-options')
                    ))
            );
        // Emails Tab
        $emails_section = $page->addSectionToTab('emails', 'emails_section', __('E-mails', 'fuerte-wp'));
        $emails_section
            ->addField(
                Field::make('html', 'fuertewp_emails_header', __('Note:', 'fuerte-wp'))
                    ->addArg('help_is_html', true)
                    ->setHtml(__(
                        '<p>Here you can enable or disable several WordPress built in emails. <strong>Mark</strong> the ones you want to be <strong>enabled</strong>.</p><p><a href="https://github.com/johnbillion/wp_mail" target="_blank">Check here</a> for full documentation of all automated emails WordPress sends.</p>',
                        'fuerte-wp'
                    ))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_fatal_error', __('Fatal Error.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Receipt: site admin or recovery email address (main options).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_automatic_updates', __('Automatic updates.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: site admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_comment_awaiting_moderation', __('Comment awaiting moderation.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: site admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_comment_has_been_published', __('Comment has been published.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: post author.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_user_reset_their_password', __('User reset their password.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: site admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_user_confirm_personal_data_export_request', __('User confirm personal data export request.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: site admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_new_user_created', __('New user created.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Receipt: site admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_network_new_site_created', __('Network: new site created.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: network admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_network_new_user_site_registered', __('Network: new user site registered.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: network admin.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_emails_network_new_site_activated', __('Network: new site activated.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Receipt: network admin.', 'fuerte-wp'))
            );

        // Login Security Tab
        $login_section = $page->addSectionToTab('login_security', 'login_section', __('Login Security', 'fuerte-wp'));
        $login_section
            ->addField(
                Field::make('html', 'fuertewp_login_security_header', __('Login Security Information', 'fuerte-wp'))
                    ->setHtml('<p>' . __('Enable login attempt limiting to protect your site from brute force attacks.', 'fuerte-wp') . '</p>')
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_login_enable', __('Enable Login Security', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Enable login attempt limiting and IP blocking.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_registration_enable', __('Enable Registration Protection', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Enable registration attempt limiting and bot blocking. Uses same settings as login security.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_login_separator_settings', __('Login Attempt Settings', 'fuerte-wp'))
            )
            ->addField(
                Field::make('number', 'fuertewp_login_max_attempts', __('Maximum Login Attempts', 'fuerte-wp'))
                    ->setDefault(5)
                    ->addArg('min', 3)
                    ->addArg('max', 10)
                    ->setHelp(__('Number of failed attempts before lockout (3-10).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('number', 'fuertewp_login_lockout_duration', __('Lockout Duration (minutes)', 'fuerte-wp'))
                    ->setDefault(60)
                    ->addArg('min', 5)
                    ->addArg('max', 1440)
                    ->setHelp(__('How long to lock out after max attempts (5-1440 minutes).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_login_increasing_lockout', __('Increasing Lockout Duration', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Increase lockout duration exponentially (2x, 4x, 8x, etc.) with each lockout.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_login_separator_ip', __('IP Detection', 'fuerte-wp'))
            )
            ->addField(
                Field::make('text', 'fuertewp_login_ip_headers', __('Custom IP Headers', 'fuerte-wp'))
                    ->setDefault('')
                    ->setHelp(__('Comma-separated list of custom IP headers (e.g., HTTP_X_FORWARDED_FOR). Useful for Cloudflare, Sucuri, or other proxy/CDN services.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_login_separator_gdpr', __('GDPR Compliance', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_login_gdpr_message', __('GDPR Privacy Notice', 'fuerte-wp'))
                    ->setDefault('')
                    ->addArg('rows', 3)
                    ->setPlaceholder(__('By proceeding you understand and give your consent that your IP address and browser information might be processed by the security plugins installed on this site.', 'fuerte-wp'))
                    ->setHelp(__('Privacy notice displayed below the login form. Default message will be shown if left empty.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_login_separator_retention', __('Data Retention', 'fuerte-wp'))
            )
            ->addField(
                Field::make('number', 'fuertewp_login_data_retention', __('Data Retention (days)', 'fuerte-wp'))
                    ->setDefault(30)
                    ->addArg('min', 1)
                    ->addArg('max', 365)
                    ->setHelp(__('Number of days to keep login logs (1-365). Old records are automatically deleted.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_login_separator_url_hiding', __('Login URL Hiding', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_login_url_hiding_enabled', __('Enable Login URL Hiding', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Hide the default wp-login.php URL to protect against brute force attacks.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('text', 'fuertewp_custom_login_slug', __('Custom Login Slug', 'fuerte-wp'))
                    ->setDefault('secure-login')
                    ->setHelp(__('Custom slug for accessing the login page (e.g., "secure-login"). Avoid common names like "login" or "admin".', 'fuerte-wp'))
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        ['field' => 'fuertewp_login_url_hiding_enabled', 'value' => true, 'compare' => '='],
                    ])
            )
            ->addField(
                Field::make('select', 'fuertewp_login_url_type', __('Login URL Type', 'fuerte-wp'))
                    ->setOptions([
                        'query_param' => __('Query Parameter (?your-slug)', 'fuerte-wp'),
                        'pretty_url' => __('Pretty URL (/your-slug/)', 'fuerte-wp'),
                    ])
                    ->setDefault('query_param')
                    ->setHelp(__('Query Parameter: https://yoursite.com/?your-slug | Pretty URL: https://yoursite.com/your-slug/', 'fuerte-wp'))
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        ['field' => 'fuertewp_login_url_hiding_enabled', 'value' => true, 'compare' => '='],
                    ])
            )
            ->addField(
                Field::make('html', 'fuertewp_login_url_info', '')
                    ->setHtml('<div class="fuertewp-login-url-info" style="display: none; background: #f9f9f9; border: 1px solid #ddd; padding: 12px; margin: 10px 0; border-radius: 4px;"><strong>' . __('Your New Login URL:', 'fuerte-wp') . '</strong> <span id="fuertewp-preview-url" style="font-family: monospace; background: #e7e7e7; padding: 2px 6px; border-radius: 3px;"></span></div>
                <script>
                jQuery(document).ready(function($) {
                    function updateLoginUrlPreview() {
                        var enabled = $(\'input[name="fuertewp_settings[fuertewp_login_url_hiding_enabled]"]\').prop(\'checked\');
                        var slug = $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').val() || \'secure-login\';
                        var urlType = $(\'select[name="fuertewp_settings[fuertewp_login_url_type]"]\').val() || \'query_param\';
                        var baseUrl = window.location.origin + window.location.pathname.replace(/\/wp-admin.*$/, \'/\');

                        var newUrl;
                        if (urlType === "pretty_url") {
                            newUrl = baseUrl + slug + \'/\';
                        } else {
                            newUrl = baseUrl + \'?\' + slug;
                        }

                        if (enabled) {
                            $(\'.fuertewp-login-url-info\').show();
                            $(\'#fuertewp-preview-url\').text(newUrl);
                        } else {
                            $(\'.fuertewp-login-url-info\').hide();
                        }
                    }

                    // Validate and ensure custom login slug is never empty on form submission
                    $(\'form\').on(\'submit\', function() {
                        var loginHidingEnabled = $(\'input[name="fuertewp_settings[fuertewp_login_url_hiding_enabled]"]\').prop(\'checked\');
                        var customSlug = $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').val();

                        if (loginHidingEnabled && (customSlug === \'\' || customSlug.trim() === \'\')) {
                            $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').val(\'secure-login\');
                            updateLoginUrlPreview();
                        }
                    });

                    $(\'input[name="fuertewp_settings[fuertewp_login_url_hiding_enabled]"]\').on(\'change\', function() {
                        var enabled = $(this).prop(\'checked\');
                        var customSlug = $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').val();

                        if (enabled && (customSlug === \'\' || customSlug.trim() === \'\')) {
                            $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').val(\'secure-login\');
                            updateLoginUrlPreview();
                        }
                    });

                    $(\'input[name="fuertewp_settings[fuertewp_login_url_hiding_enabled]"]\').on(\'change\', updateLoginUrlPreview);
                    $(\'input[name="fuertewp_settings[fuertewp_custom_login_slug]"]\').on(\'input\', updateLoginUrlPreview);
                    $(\'select[name="fuertewp_settings[fuertewp_login_url_type]"]\').on(\'change\', updateLoginUrlPreview);

                    updateLoginUrlPreview();
                });
                </script>')
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        ['field' => 'fuertewp_login_url_hiding_enabled', 'value' => true, 'compare' => '='],
                    ])
            )
            ->addField(
                Field::make('select', 'fuertewp_redirect_invalid_logins', __('Invalid Login Redirect', 'fuerte-wp'))
                    ->setOptions([
                        'home_404' => __('Home Page with 404 Error', 'fuerte-wp'),
                        'custom_page' => __('Custom URL Redirect', 'fuerte-wp'),
                    ])
                    ->setDefault('home_404')
                    ->setHelp(__('Where to redirect users who try to access the login page directly.', 'fuerte-wp'))
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        ['field' => 'fuertewp_login_url_hiding_enabled', 'value' => true, 'compare' => '='],
                    ])
            )
            ->addField(
                Field::make('text', 'fuertewp_redirect_invalid_logins_url', __('Custom Redirect URL', 'fuerte-wp'))
                    ->setPlaceholder('https://example.com/custom-page')
                    ->setHelp(__('Enter the full URL where invalid login attempts should be redirected. Can be any internal or external URL.', 'fuerte-wp'))
                    ->setConditionalLogic([
                        'relation' => 'AND',
                        ['field' => 'fuertewp_login_url_hiding_enabled', 'value' => true, 'compare' => '='],
                        ['field' => 'fuertewp_redirect_invalid_logins', 'value' => 'custom_page', 'compare' => '='],
                    ])
            );

        // REST API Tab
        $restapi_section = $page->addSectionToTab('rest_api', 'restapi_section', __('REST API', 'fuerte-wp'));
        $restapi_section
            ->addField(
                Field::make('html', 'fuertewp_restapi_restrictions_header', __('Note:', 'fuerte-wp'))
                    ->addArg('help_is_html', true)
                    ->setHtml(__('<p>REST API restrictions.</p>', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_restapi_loggedin_only', __('Restrict REST API usage to logged in users only.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->addArg('help_is_html', true)
                    ->setHelp(__('Modern WordPress depends on his REST API. The entire new editor, Gutenberg, uses it. And many more usage instances are common the WP core. You should not disable the REST API entirely, or WordPress will brake. This is the second best option: limit his usage to only logged in users. <a href="https://developer.wordpress.org/rest-api/frequently-asked-questions/" target="_blank">Learn more</a>.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_restapi_disable_app_passwords', __('Disable app passwords.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->addArg('help_is_html', true)
                    ->setHelp(__('Disable generation of App Passwords, used for the REST API. <a href="https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/" target="_blank">Check here</a> for more info.', 'fuerte-wp'))
            );

        // Restrictions Tab
        $restrictions_section = $page->addSectionToTab('restrictions', 'restrictions_section', __('Restrictions', 'fuerte-wp'));
        $restrictions_section
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_xmlrpc', __('Disable XML-RPC API.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->addArg('help_is_html', true)
                    ->setHelp(__('Disable the old and insecure XML-RPC API in WordPress. <a href="https://blog.wpscan.com/is-wordpress-xmlrpc-a-security-problem/" target="_blank">Learn more</a>.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_htaccess_security_rules', __('Enable htaccess security rules', 'fuerte-wp'))
                    ->setDefault(true)
                    ->addArg('help_is_html', true)
                    ->setHelp(__('Disable the usage of /wp-admin/install.php wizard, and the execution of php files inside /wp-content/uploads/ folder, by adding restrictions on the htaccess file on the server. If you are using Nginx, please, <a href="https://github.com/EstebanForge/Fuerte-WP/blob/master/FAQ.md" target="_blank">Add them manually</a>.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_admin_create_edit', __('Disable admin creation/edition.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disable the creation of new admin accounts and the editing of existing admin accounts.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_weak_passwords', __('Disable weak passwords.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disable the use of weak passwords. User can\'t uncheck "Confirm use of weak password". Let users type their own password, but must be somewhat secure (following WP built in recommendation library).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_force_strong_passwords', __('Force strong passwords.', 'fuerte-wp'))
                    ->setDefault(false)
                    ->setHelp(__('Force strong passwords usage, making password field read-only. Users must use WordPress provided strong password.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_restrictions_disable_admin_bar_roles', __('Disable admin bar for roles.', 'fuerte-wp'))
                    ->setOptions(function_exists('fuertewp_get_wp_roles') ? fuertewp_get_wp_roles() : [])
                    ->setDefault(['subscriber', 'customer'])
                    ->setHelp(__('Disable WordPress admin bar for selected roles.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_restrict_permalinks', __('Restrict Permalinks configuration.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Restrict Permalinks configuration access.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_restrict_acf', __('Restrict ACF fields editing.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Restrict Advanced Custom Fields editing access in the backend (Custom Fields menu).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_theme_editor', __('Disable Theme Editor.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disables the built in Theme code editor.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_plugin_editor', __('Disable Plugin Editor.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disables the built in Plugin code editor.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_theme_install', __('Disable Theme install.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disables installation of new Themes.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_plugin_install', __('Disable Plugin install.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disables installation of new Plugins.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_restrictions_disable_customizer_css', __('Disable Customizer CSS Editor.', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Disables Customizer Additional CSS Editor.', 'fuerte-wp'))
            );

        // Advanced Restrictions Tab
        $advanced_section = $page->addSectionToTab('advanced_restrictions', 'advanced_section', __('Advanced Restrictions', 'fuerte-wp'));
        $advanced_section
            ->addField(
                Field::make('html', 'fuertewp_advanced_restrictions_header', __('Note:', 'fuerte-wp'))
                    ->addArg('help_is_html', true)
                    ->setHtml(__('<p>Only for power users. Leave a field blank to not use those restrictions.</p>', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_restricted_scripts', __('Restricted Scripts.', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->addArg('help_is_html', true)
                    ->setDefault("export.php\n//plugins.php\nupdate.php\nupdate-core.php")
                    ->setHelp(__('One per line. Restricted scripts by file name.<br>These file names will be checked against <a href="https://codex.wordpress.org/Global_Variables" target="_blank">$pagenow</a>, and also will be thrown into <a href="https://developer.wordpress.org/reference/functions/remove_menu_page/" target="_blank">remove_menu_page</a>.<br/>You can comment a line with // to not use it.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_restricted_pages', __('Restricted Pages.', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->addArg('help_is_html', true)
                    ->setDefault("wprocket\nupdraftplus\nbetter-search-replace\nbackwpup\nbackwpupjobs\nbackwpupeditjob\nbackwpuplogs\nbackwpupbackups\nbackwpupsettings\nlimit-login-attempts\nwp_stream_settings\ntransients-manager\npw-transients-manager\nenvato-market\nelementor-license")
                    ->setHelp(__('One per line. Restricted pages by "page" URL variable.<br/>In wp-admin, checks for URLs like: <i>admin.php?page=</i>', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_removed_menus', __('Removed Menus.', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->addArg('help_is_html', true)
                    ->setDefault("backwpup\ncheck-email-status\nlimit-login-attempts\nenvato-market")
                    ->setHelp(__('One per line. Menus to be removed. Use menu <i>slug</i>.<br/>These slugs will be thrown into <a href="https://developer.wordpress.org/reference/functions/remove_menu_page/" target="_blank">remove_menu_page</a>.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_removed_submenus', __('Removed Submenus.', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->addArg('help_is_html', true)
                    ->setDefault("options-general.php|updraftplus\noptions-general.php|limit-login-attempts\noptions-general.php|mainwp_child_tab\noptions-general.php|wprocket\ntools.php|export.php\ntools.php|transients-manager\ntools.php|pw-transients-manager\ntools.php|better-search-replace")
                    ->setHelp(__('One per line. Submenus to be removed. Use: <i>parent-menu-slug<strong>|</strong>submenu-slug</i>, separared with a pipe.<br/>These will be thrown into <a href="https://developer.wordpress.org/reference/functions/remove_submenu_page/" target="_blank">remove_submenu_page</a>.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_removed_adminbar_menus', __('Removed Admin Bar menus.', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->addArg('help_is_html', true)
                    ->setDefault("wp-logo\ntm-suspend\nupdraft_admin_node")
                    ->setHelp(__('One per line. Admin bar menus to be removed. Use: <i>adminbar-item-node-id</i>.<br/>These nodes will be thrown into <a href="https://developer.wordpress.org/reference/classes/wp_admin_bar/remove_node/#finding-toolbar-node-ids" target="_blank">remove_node</a>. Check the docs on how to find an admin bar node id.', 'fuerte-wp'))
            );

        // IP & User Lists Tab
        $ip_section = $page->addSectionToTab('ip_lists', 'ip_section', __('IP & User Lists', 'fuerte-wp'));
        $ip_section
            ->addField(
                Field::make('html', 'fuertewp_ip_lists_header', __('IP Whitelist & Blacklist', 'fuerte-wp'))
                    ->addArg('help_is_html', true)
                    ->setHtml('<p>' . __('Manage IP addresses and ranges that are allowed or blocked.', 'fuerte-wp') . '</p><p>' . __('Supports single IPs, IPv4/IPv6 addresses, and CIDR notation (e.g., 192.168.1.0/24).', 'fuerte-wp') . '</p>')
            )
            ->addField(
                Field::make('textarea', 'fuertewp_username_whitelist', __('Username Whitelist', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->setHelp(__('One username per line. Only these users can log in (leave empty for no restriction).', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_username_separator', __('Username Blacklist', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_block_default_users', __('Block Common Admin Usernames', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Automatically block common admin usernames like "admin", "administrator", "root".', 'fuerte-wp'))
            )
            ->addField(
                Field::make('textarea', 'fuertewp_username_blacklist', __('Username Blacklist', 'fuerte-wp'))
                    ->addArg('rows', 4)
                    ->setHelp(__('One username per line. These usernames cannot register or log in.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_registration_separator', __('Registration Protection', 'fuerte-wp'))
            )
            ->addField(
                Field::make('checkbox', 'fuertewp_registration_protect', __('Enable Registration Protection', 'fuerte-wp'))
                    ->setDefault(true)
                    ->setHelp(__('Apply username blacklist to user registrations.', 'fuerte-wp'))
            );

        // Failed Logins Tab
        $logins_section = $page->addSectionToTab('failed_logins', 'logins_section', __('Failed Logins', 'fuerte-wp'));
        $logins_section
            ->addField(
                Field::make('html', 'fuertewp_login_logs_viewer', __('Failed Login Attempts', 'fuerte-wp'))
                    ->setHtml($this->render_login_logs_viewer())
            );

        // Deferred Updates Tab
        $deferred_section = $page->addSectionToTab('deferred_updates', 'deferred_section', __('Deferred Updates', 'fuerte-wp'));
        $deferred_section
            ->addField(
                Field::make('html', 'fuertewp_deferred_header', __('Deferred Updates Information', 'fuerte-wp'))
                    ->addArg('help_is_html', true)
                    ->setHtml('<p>' . __('Prevent specific plugins or themes from auto-updating. When auto-updates are enabled, deferred items will be excluded from automatic updates but can still be manually updated. Blocked items will be completely prevented from ALL updates (both automatic and manual).', 'fuerte-wp') . '</p>')
            )
            ->addField(
                Field::make('heading', 'fuertewp_deferred_plugins_sep', __('Deferred Plugins', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_deferred_plugins', __('Plugins to Defer', 'fuerte-wp'))
                    ->setOptions(function_exists('fuertewp_get_installed_plugins') ? fuertewp_get_installed_plugins() : [])
                    ->setHelp(__('Selected plugins will NOT auto-update. Useful for plugins that may have compatibility issues with newer versions.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_deferred_themes_sep', __('Deferred Themes', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_deferred_themes', __('Themes to Defer', 'fuerte-wp'))
                    ->setOptions(function_exists('fuertewp_get_installed_themes') ? fuertewp_get_installed_themes() : [])
                    ->setHelp(__('Selected themes will NOT auto-update. Useful for themes with customizations that might be affected by updates.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_blocked_plugins_sep', __('Blocked Plugins', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_blocked_plugins', __('Plugins to Block', 'fuerte-wp'))
                    ->setOptions(function_exists('fuertewp_get_installed_plugins') ? fuertewp_get_installed_plugins() : [])
                    ->setHelp(__('Selected plugins will be completely blocked from ALL updates (automatic and manual). Use with caution - updates will not be available for these plugins.', 'fuerte-wp'))
            )
            ->addField(
                Field::make('heading', 'fuertewp_blocked_themes_sep', __('Blocked Themes', 'fuerte-wp'))
            )
            ->addField(
                Field::make('multiselect', 'fuertewp_blocked_themes', __('Themes to Block', 'fuerte-wp'))
                    ->setOptions(function_exists('fuertewp_get_installed_themes') ? fuertewp_get_installed_themes() : [])
                    ->setHelp(__('Selected themes will be completely blocked from ALL updates (automatic and manual). Use with caution - updates will not be available for these themes.', 'fuerte-wp'))
            );

        $page->register();

        // Register Data Tools page for migrations and maintenance
        if (function_exists('hf_register_data_tools_page')) {
            hf_register_data_tools_page('Fuerte-WP', 'fuertewp_settings');
        }
    }

    /**
     * Render login logs viewer HTML.
     *
     * @since 1.7.0
     *
     * @return string HTML content
     */
    private function render_login_logs_viewer()
    {
        // Enqueue admin scripts
        wp_enqueue_script(
            'fuertewp-login-admin',
            FUERTEWP_URL . 'admin/js/fuerte-wp-login-admin.js',
            ['jquery'],
            FUERTEWP_VERSION,
            true
        );

        wp_localize_script('fuertewp-login-admin', 'fuertewp_login_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fuertewp_admin_nonce'),
            'i18n' => [
                'confirm_clear' => __('Are you sure you want to clear all login logs?', 'fuerte-wp'),
                'confirm_reset' => __('Are you sure you want to reset all lockouts?', 'fuerte-wp'),
                'loading' => __('Loading...', 'fuerte-wp'),
                'error' => __('An error occurred', 'fuerte-wp'),
            ],
        ]);

        // Get stats
        $logger = new Fuerte_Wp_Login_Logger();
        $stats = $logger->get_lockout_stats();

        // Build HTML
        ob_start();
        ?>
        <div id="fuertewp-login-logs">
            <!-- Stats Overview -->
            <div class="fuertewp-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                <div class="stat-box" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h4 style="margin: 0 0 5px 0;"><?php esc_html_e('Total Lockouts', 'fuerte-wp'); ?></h4>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo (int) $stats['total_lockouts']; ?></p>
                </div>
                <div class="stat-box" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h4 style="margin: 0 0 5px 0;"><?php esc_html_e('Active Lockouts', 'fuerte-wp'); ?></h4>
                    <p style="font-size: 24px; font-weight: bold; margin: 0; color: #d63638;"><?php echo (int) $stats['active_lockouts']; ?></p>
                </div>
                <div class="stat-box" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h4 style="margin: 0 0 5px 0;"><?php esc_html_e('Failed Today', 'fuerte-wp'); ?></h4>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo (int) $stats['failed_today']; ?></p>
                </div>
                <div class="stat-box" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h4 style="margin: 0 0 5px 0;"><?php esc_html_e('Failed This Week', 'fuerte-wp'); ?></h4>
                    <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo (int) $stats['failed_week']; ?></p>
                </div>
            </div>

            <!-- Actions -->
            <div class="fuertewp-actions" style="margin-bottom: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
                <button type="button" id="fuertewp-export-attempts" class="button button-primary">
                    <?php esc_html_e('Export CSV', 'fuerte-wp'); ?>
                </button>
                <button type="button" id="fuertewp-clear-logs" class="button button-secondary">
                    <?php esc_html_e('Clear All Logs', 'fuerte-wp'); ?>
                </button>
                <button type="button" id="fuertewp-reset-lockouts" class="button button-secondary">
                    <?php esc_html_e('Reset All Lockouts', 'fuerte-wp'); ?>
                </button>
            </div>

            <!-- Logs Table Container -->
            <div id="fuertewp-logs-table-container">
                <p><?php esc_html_e('Loading failed login attempts...', 'fuerte-wp'); ?></p>
            </div>
        </div>

        <style>
        #fuertewp-login-logs .column-ip { width: 120px; }
        #fuertewp-login-logs .column-status { width: 100px; }
        #fuertewp-login-logs .column-actions { width: 100px; }
        #fuertewp-login-logs .status-success { color: #00a32a; font-weight: bold; }
        #fuertewp-login-logs .status-failed { color: #d63638; font-weight: bold; }
        #fuertewp-login-logs .status-blocked { color: #d63638; font-weight: bold; }
        #fuertewp-login-logs .user-agent-cell {
            max-width: 450px;
            overflow-x: auto;
            white-space: nowrap;
            font-family: monospace;
            font-size: 12px;
            background: #f8f9f9;
            padding: 4px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle HyperFields settings saved.
     *
     * @param array $data The saved data.
     * @param string $storage_key The storage key used.
     */
    public function handle_settings_saved($data, $storage_key)
    {
        if ($storage_key !== 'fuertewp_settings') {
            return;
        }

        // Validate and ensure custom login slug is never empty
        $custom_login_slug = Fuerte_Wp_Config::get_field('custom_login_slug', 'secure-login');

        if (empty($custom_login_slug) || trim($custom_login_slug) === '') {
            Fuerte_Wp_Config::set_field('custom_login_slug', 'secure-login');
        }

        // Self-protection: ensure current user stays as super user
        $current_user = wp_get_current_user();
        $super_users = Fuerte_Wp_Config::get_field('super_users', [], true);

        // Normalize to array format
        if (!is_array($super_users)) {
            $super_users = is_string($super_users) && !empty($super_users) ? [$super_users] : [];
        }

        if (empty($super_users) || !in_array($current_user->user_email, $super_users)) {
            $super_users[] = $current_user->user_email;
            Fuerte_Wp_Config::set_field('super_users', array_unique($super_users), true);
        }

        // Invalidate cache
        Fuerte_Wp_Config::invalidate_cache();

        // Flush rewrite rules if login URL might have changed
        if (isset($data['fuertewp_custom_login_slug']) || isset($data['fuertewp_login_url_hiding_enabled'])) {
            flush_rewrite_rules(true);
        }
    }

    /**
     * Plugins list Settings link.
     */
    public function add_action_links($links)
    {
        global $fuertewp, $current_user;

        if (!isset($current_user)) {
            $current_user = wp_get_current_user();
        }

        // Check if fuertewp config exists and has super_users
        if (
            !isset($fuertewp)
            || !is_array($fuertewp)
            || empty($fuertewp['super_users'])
        ) {
            return $links;
        }

        // Use simple string operations for email comparison
        if (
            !in_array(
                strtolower($current_user->user_email),
                $fuertewp['super_users'],
            )
            || (defined('FUERTEWP_FORCE') && true === FUERTEWP_FORCE)
        ) {
            return $links;
        }

        $fuertewp_link = [
            /* translators: %s: plugin settings URL */
            sprintf(
                __('<a href="%s">Settings</a>', 'fuerte-wp'),
                admin_url(
                    'options-general.php?page=fuerte-wp-options',
                ),
            ),
        ];

        return array_merge($links, $fuertewp_link);
    }
}
