<?php
/**
 * Template for admin menu
 *
 * @package Cache-Warmer
 */

use Cache_Warmer\Cache_Warmer;
use Cache_Warmer\Utils;
use WP_Plugins_Core\Setting_Fields;

?>

<div class="wrap">
    <h1 class="cache-warmer-header">
        <?php esc_html_e( 'Cache Warmer', 'cache-warmer' ); ?> <?php esc_html_e( 'Settings', 'cache-warmer' ); ?>
    </h1>

    <form class="cache-warmer-form">
        <div class="cache-warmer-container">
            <div class="wp-plugins-core-tabs-container">
                <div class="wp-plugins-core-tab-content" data-tab-name="crawling-behavior">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Crawling Behavior', 'cache-warmer' ); ?></h2>

                    <?php
                    ob_start();
                    ?>
                    <div class="mb-15">
                        <table class="cache-warmer-entry-points mv-05em">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( 'Entry Point #', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'URL', 'cache-warmer' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $table_html = ob_get_clean();

                    $install_simplexml_extension_html = '';
                    if ( ! extension_loaded( 'simplexml' ) ) {
                        ob_start();
                        ?>
                        <b>
                            <?php esc_html_e( 'SimpleXML PHP extension is missing. Install it to make it work.', 'cache-warmer' ); ?>
                        </b>
                        <?php

                        $install_simplexml_extension_html = ob_get_clean();
                    }

                    $settings = [
                        [
                            'title' => __( 'Entry Points', 'cache-warmer' ),
                            'type'  => 'title',
                            'desc'  => __( 'Specify either a relative (e.g. "blog") or absolute URL. Can also specify an external site.', 'cache-warmer' ),
                            'id'    => 'crawling-behavior',
                        ],
                        [
                            'type'  => 'pure-html',
                            'value' => $table_html,
                        ],
                        [
                            'title'         => __( 'Additional entry points', 'cache-warmer' ),
                            'id'            => 'add-this-site-all-public-posts',
                            'desc'          => __( 'Add all public posts (of any type) and taxonomies of this site as entry points', 'cache-warmer' ),
                            'desc_tip'      => __( 'Set depth to 0 to warm only them.', 'cache-warmer' ),
                            'value'         => '1' === Cache_Warmer::$options->get( 'setting-add-this-site-all-public-posts' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => 'start',
                        ],
                        [
                            'id'            => 'add-entry-point-sites-sitemaps',
                            'desc'          => __( 'Add sitemaps of entry points as entry points', 'cache-warmer' ),
                            'desc_tip'      => extension_loaded( 'simplexml' ) ?
                                __( 'Min recommended depth for sitemaps is 2.', 'cache-warmer' ) :
                                $install_simplexml_extension_html,
                            'value'         => '1' === Cache_Warmer::$options->get( 'setting-add-entry-point-sites-sitemaps' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => 'end',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'crawling-behavior',
                        ],
                        [
                            'title' => __( 'Pages Exclusion', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'pages-exclusion',
                        ],
                        [
                            'title'             => __( 'Exclude Particular Pages', 'cache-warmer' ),
                            'id'                => 'excluded-pages',
                            'desc_tip'          => __( 'Separate by a new-line.', 'cache-warmer' ),
                            'value'             => implode( PHP_EOL, Cache_Warmer::$options->get( 'cache-warmer-setting-excluded-pages' ) ),
                            'type'              => 'textarea',
                            'custom_attributes' => [
                                'rows' => 10,
                                'cols' => 50,
                            ],
                            'placeholder'       => "contact-\ncheckout",
                        ],
                        [
                            'title' => __( 'Exclude by regex', 'cache-warmer' ),
                            'id'    => 'excluded-pages-use-regex-match',
                            'desc'  => __( 'Use <a href="https://www.php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">regex</a> match (not a plain substring match)', 'cache-warmer' ),
                            'value' => '1' === Cache_Warmer::$options->get( 'setting-excluded-pages-use-regex-match' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'title' => __( 'Canonical', 'cache-warmer' ),
                            'id'    => 'exclude-pages-with-warmed-canonical',
                            'desc'  => __( 'Skip pages with warmed canonical URLs', 'cache-warmer' ),
                            'value' => '1' === Cache_Warmer::$options->get( 'setting-exclude-pages-with-warmed-canonical' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'pages-exclusion',
                        ],
                        [
                            'title' => __( 'Depth', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'depth',
                        ],
                        [
                            'id'                => 'depth',
                            'desc_tip'          => __( 'Depth "0" means visiting no children pages (only entry points).', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'cache-warmer-setting-depth' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 60px;',
                            'custom_attributes' => [
                                'maxlength' => 1,
                                'min'       => 0,
                                'max'       => 9,
                            ],
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'depth',
                        ],
                        [
                            'title' => __( 'Assets Preloading', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'assets-preloading',
                        ],
                        [
                            'title'         => __( 'Visit assets of type', 'cache-warmer' ),
                            'id'            => 'assets-preloading-scripts',
                            'desc'          => __( 'Scripts', 'cache-warmer' ),
                            'value'         => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-assets-preloading-scripts' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => 'start',
                        ],
                        [
                            'id'            => 'assets-preloading-styles',
                            'desc'          => __( 'Styles', 'cache-warmer' ),
                            'value'         => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-assets-preloading-styles' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => '',
                        ],
                        [
                            'id'            => 'assets-preloading-fonts',
                            'desc'          => __( 'Fonts', 'cache-warmer' ),
                            'value'         => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-assets-preloading-fonts' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => '',
                        ],
                        [
                            'id'            => 'assets-preloading-images',
                            'desc'          => __( 'Images', 'cache-warmer' ),
                            'value'         => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-assets-preloading-images' ) ? 'yes' : 'no',
                            'type'          => 'checkbox',
                            'checkboxgroup' => 'end',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'assets-preloading',
                        ],
                    ];

                    Setting_Fields::output_fields( $settings );
                    ?>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="crawler-settings">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Crawler Settings', 'cache-warmer' ); ?></h2>

                    <?php
                    ob_start();
                    ?>
                    <div class="mb-15">
                        <table class="cache-warmer-url-params mv-05em">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( '#', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Value', 'cache-warmer' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $url_params_table_html = ob_get_clean();

                    ob_start();
                    ?>
                    <div class="mb-15">
                        <table class="cache-warmer-request-headers mv-05em">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( '#', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Value', 'cache-warmer' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $request_headers_table_html = ob_get_clean();

                    ob_start();
                    ?>
                    <div class="mb-15">
                        <table class="cache-warmer-user-agents mv-05em">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( '#', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'User-Agent', 'cache-warmer' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <?php submit_button( __( 'Insert my User-Agent', 'cache-warmer' ), [ 'secondary', 'cache-warmer-paste-my-ua', 'ml-0' ] ); ?>
                    </div>
                    <?php
                    $user_agents_table_html = ob_get_clean();

                    ob_start();
                    ?>
                    <div class="mb-15">
                        <table class="cache-warmer-cookies mv-05em">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( 'Cookie #', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'cache-warmer' ); ?></th>
                                <th><?php esc_html_e( 'Value', 'cache-warmer' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <?php submit_button( __( 'Insert my cookies', 'cache-warmer' ), [ 'secondary', 'cache-warmer-paste-my-cookies', 'ml-0' ] ); ?>
                    </div>
                    <?php
                    $cookies_table_html = ob_get_clean();

                    $settings = [
                        [
                            'title' => '',
                            'type'  => 'title',
                            'id'    => 'crawler-settings',
                        ],
                        [
                            'id'                => 'timeout',
                            'title'             => __( 'Request Timeout', 'cache-warmer' ),
                            'desc'              => __( 'Seconds', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'cache-warmer-setting-timeout' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 60px;',
                            'custom_attributes' => [
                                'min' => 1,
                                'max' => CACHE_WARMER_MAX_REQUEST_TIMEOUT,
                            ],
                        ],
                        [
                            'id'                => 'speed-limit',
                            'title'             => __( 'Speed limit', 'cache-warmer' ),
                            'desc'              => __( 'Max pages to visit per minute', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'cache-warmer-setting-speed-limit' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 75px;',
                            'custom_attributes' => [
                                'min' => 1,
                            ],
                        ],
                        [
                            'title' => __( 'Rewrite URLs to HTTPS', 'cache-warmer' ),
                            'id'    => 'rewrite-to-https',
                            'value' => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-rewrite-to-https' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'title' => __( 'Object Cache', 'cache-warmer' ),
                            'desc'  => __( "Use object cache for tree storage, when it's available", 'cache-warmer' ),
                            'id'    => 'use-object-cache',
                            'value' => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-use-object-cache' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'crawler-settings',
                        ],
                        [
                            'title' => __( 'URL Params', 'cache-warmer' ),
                            'type'  => 'title',
                            'desc'  => __( 'These URL params will be added to all pages during the warming.', 'cache-warmer' ),
                            'id'    => 'url-param',
                        ],
                        [
                            'type'  => 'pure-html',
                            'value' => $url_params_table_html,
                        ],
                        [
                            'title' => __( 'Behavior', 'cache-warmer' ),
                            'id'    => 'visit-second-time-without-url-params',
                            'desc'  => __( 'Visit pages second time without custom URL params (if they are set)', 'cache-warmer' ),
                            'value' => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-visit-second-time-without-url-params' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'url-params',
                        ],
                        [
                            'title' => __( 'User-Agents', 'cache-warmer' ),
                            'type'  => 'title',
                            'desc'  => __( 'First visit the page with User-Agent #1, then with #2 (if set), and so on.', 'cache-warmer' ),
                            'id'    => 'user-agents',
                        ],
                        [
                            'type'  => 'pure-html',
                            'value' => $user_agents_table_html,
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'user-agents',
                        ],
                        [
                            'title' => __( 'Cookies', 'cache-warmer' ),
                            'type'  => 'title',
                            'desc'  => __( 'These cookies will be used on every page visit.', 'cache-warmer' ),
                            'id'    => 'cookies',
                        ],
                        [
                            'type'  => 'pure-html',
                            'value' => $cookies_table_html,
                        ],
                        [
                            'title' => __( 'Behavior', 'cache-warmer' ),
                            'id'    => 'visit-second-time-without-cookies',
                            'desc'  => __( 'Visit pages second time without cookies (if they are set)', 'cache-warmer' ),
                            'value' => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-visit-second-time-without-cookies' ) ? 'yes' : 'no',
                            'type'  => 'checkbox',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'cookies',
                        ],
                        [
                            'title' => __( 'Additional Request Headers', 'cache-warmer' ),
                            'type'  => 'title',
                            'desc'  => __( 'These request headers will be sent to all pages during the warming. Example: <i>Accept, Accept-Encoding</i>', 'cache-warmer' )
                                    . '<br><br><b>' . __( 'For <a href="#user-agents-description">User-Agent</a> and <a href="#cookies-description">Cookies</a> use the tables above.', 'cache-warmer' ) . '</b>',
                            'id'    => 'request-headers',
                        ],
                        [
                            'type'  => 'pure-html',
                            'value' => $request_headers_table_html,
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'request-headers',
                        ],
                    ];

                    Setting_Fields::output_fields( $settings );
                    ?>

                    <div class="cache-warmer-container mt-40">
                        <div class="cache-warmer-row">
                            <a class="cache-warmer-link cache-warmer-export-settings-link"><?php esc_html_e( 'Export Settings', 'cache-warmer' ); ?></a>
                            <a class="cache-warmer-link cache-warmer-import-settings-link"><?php esc_html_e( 'Import Settings', 'cache-warmer' ); ?></a>
                            <input type="file" name="cache-warmer-import-settings-link-file-input" accept="application/JSON">
                        </div>
                        <div class="cache-warmer-container">
                            <div class="cache-warmer-row">
                                <a class="cache-warmer-link cache-warmer-reset-settings-link mt-15"><?php esc_html_e( 'Reset All Settings', 'cache-warmer' ); ?></a>
                            </div>
                        </div>

                        <div class="cache-warmer-container">
                            <div class="cache-warmer-row">
                                <a class="cache-warmer-link cache-warmer-reschedule-intervals-link mt-15">
                                    <?php esc_html_e( 'Fix background tasks', 'cache-warmer' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="schedule">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Schedule', 'cache-warmer' ); ?></h2>
                    <?php
                    $warm_up_posts_url        = esc_url( admin_url( 'admin.php?page=cache-warmer-logs&plugin-selected-tab=unscheduled' ) );
                    $warm_up_posts_link_label = __( 'Logs', 'cache-warmer' ) . ' -> ' . __( 'Triggered', 'cache-warmer' );
                    $warm_up_posts_link       = sprintf( '<a target="_blank" href="%s">%s</a>', $warm_up_posts_url, $warm_up_posts_link_label );
                    $warm_up_posts_desc_tip   = sprintf(
                        /* translators: %s is the link */
                        __( 'Can be seen in %s.', 'cache-warmer' ),
                        $warm_up_posts_link
                    );

                    $settings = [
                        [
                            'title' => __( 'Schedule', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'schedule',
                        ],
                        [
                            'id'                => 'interval',
                            'title'             => __( 'Start warming automatically', 'cache-warmer' ),
                            'desc_tip'          => __( '0 = disable auto start', 'cache-warmer' ),
                            'desc'              => __( 'Every N minutes', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'cache-warmer-setting-interval' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 75px;',
                            'custom_attributes' => [
                                'min' => 0,
                            ],
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'schedule',
                        ],
                        [
                            'title' => __( 'Posts warming after their update', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'posts-after-on-update',
                        ],
                        [
                            'title'    => __( 'Warm-up posts', 'cache-warmer' ),
                            'id'       => 'warm-up-posts',
                            'value'    => '1' === Cache_Warmer::$options->get( 'cache-warmer-setting-warm-up-posts' ) ? 'yes' : 'no',
                            'type'     => 'checkbox',
                            'desc'     => __( 'On their publication and edit', 'cache-warmer' ),
                            'desc_tip' => $warm_up_posts_desc_tip,
                        ],
                        [
                            'id'                => 'posts-warming-enqueue-interval',
                            'title'             => __( 'Posts warming enqueue interval', 'cache-warmer' ),
                            'desc'              => __( 'Minutes', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'cache-warmer-setting-posts-warming-enqueue-interval' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 75px;',
                            'custom_attributes' => [
                                'min' => 1,
                            ],
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'posts-after-on-update',
                        ],
                    ];

                    Setting_Fields::output_fields( $settings );
                    ?>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="Logging">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Logging', 'cache-warmer' ); ?></h2>
                    <?php
                    $settings = [
                        [
                            'title' => __( 'Logging', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'logging',
                        ],
                        [
                            'id'                => 'for-how-many-days-to-keep-the-logs',
                            'title'             => __( 'Keep logs for', 'cache-warmer' ),
                            'desc'              => __( 'Days', 'cache-warmer' ),
                            'desc_tip'          => __( '0 = keep only the last warmup', 'cache-warmer' ),
                            'value'             => Cache_Warmer::$options->get( 'setting-for-how-many-days-to-keep-the-logs' ),
                            'type'              => 'number',
                            'class'             => 'tmm-wp-plugins-core-number-input',
                            'css'               => 'width: 75px;',
                            'custom_attributes' => [
                                'min' => 0,
                            ],
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'logging',
                        ],
                    ];

                    Setting_Fields::output_fields( $settings );
                    ?>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="external-warmer">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'External Warmer', 'cache-warmer' ); ?></h2>

                    <?php
                    $use_external_warmer_servers_during_the_warming = Cache_Warmer::$options->get( 'setting-use-external-warmer-servers-during-the-warming' );

                    $settings = [
                        [
                            'title' => __( 'External Warmer', 'cache-warmer' ),
                            'type'  => 'title',
                            'id'    => 'external-warmer',
                        ],
                        [
                            'id'       => 'use-external-warmer-servers-during-the-warming',
                            'title'    => __( 'Use External Warmer servers during the warming (in addition to this server)', 'cache-warmer' ),
                            'desc_tip' => __( 'This will slow down the warming, as extra requests will be made from the external warming servers in addition to the standard visits.<br><br>Or you can keep this off, and just use the warming on interval (configurable below).' ),
                            'type'     => 'checkbox',
                            'default'  => '1' === $use_external_warmer_servers_during_the_warming ? 'yes' : 'no',
                        ],
                        [
                            'type' => 'sectionend',
                            'id'   => 'external-warmer',
                        ],
                    ];

                    Setting_Fields::output_fields( $settings );

                    // Returns entry points unique links.

                    foreach ( Utils::get_unique_domains_from_entry_points() as $unique_domain ) {
                        $last_response_code = (int) get_option(
                            'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $unique_domain
                        );
                        $last_response_body = get_option(
                            'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-body' . $unique_domain
                        );

                        $servers_to_use = get_option(
                            'cache-warmer-setting-external-warmer-servers-to-use' . $unique_domain,
                            []
                        );

                        if ( 200 === $last_response_code ) {
                            $locations = (array) json_decode( $last_response_body, true );

                            $cache_warmer_available_locations = [];

                            if ( count( $locations ) > 1 ) {
                                foreach ( $locations as $location ) {
                                    foreach ( $location as $location_name => $location_url ) {
                                        $data = [
                                            'desc'    => $location_name,
                                            'id'      => $location_url . $unique_domain,
                                            'default' => in_array( $location_url, $servers_to_use, true ) ? 'yes' : 'no',
                                            'type'    => 'checkbox',
                                        ];

                                        if ( 0 === count( $cache_warmer_available_locations ) ) {
                                            $data['title'] = __( 'Server(s) To Use', 'cache-warmer' );
                                        }

                                        $cache_warmer_available_locations[] = $data;
                                    }
                                }
                            } else {
                                foreach ( $locations as $location_name => $location_url ) {
                                    $data = [
                                        'desc'    => $location_name,
                                        'id'      => $location_url,
                                        'default' => in_array( $location_url, $servers_to_use, true ) ? 'yes' : 'no',
                                        'type'    => 'checkbox',
                                    ];

                                    if ( 0 === count( $cache_warmer_available_locations ) ) {
                                        $data['title'] = __( 'Server(s) To Use', 'cache-warmer' );
                                    }

                                    $cache_warmer_available_locations[] = $data;
                                }
                            }

                            $external_warmer_interval = get_option( 'cache-warmer-setting-external-warmer-interval' . $unique_domain );

                            $cache_warmer_available_locations[] = [
                                'title'    => __( 'How often to warm posts', 'cache-warmer' ),
                                'desc_tip' => __( 'This will use this website pages list from the last successful standard warming.', 'cache-warmer' ),
                                'id'       => 'external-warmer-interval' . $unique_domain,
                                'value'    => $external_warmer_interval,
                                'type'     => 'select',
                                'options'  => [
                                    '0'   => __( 'Never', 'cache-warmer' ),
                                    '1'   => __( 'Hourly', 'cache-warmer' ),
                                    '4'   => __( '4-hourly', 'cache-warmer' ),
                                    '24'  => __( 'Daily', 'cache-warmer' ),
                                    '168' => __( 'Weekly', 'cache-warmer' ),
                                ],
                            ];
                        } else {
                            $cache_warmer_available_locations = [
                                [
                                    'title' => __( 'External Server', 'cache-warmer' ),
                                    'type'  => 'html',
                                    'value' =>
                                        $last_response_code ?
                                            sprintf(
                                                __( 'Server returned code <b>"%1$s"</b> with text: <b>%2$s</b>', 'cache-warmer' ),
                                                $last_response_code,
                                                $last_response_body
                                            ) :
                                            __( 'Please specify External server API key first.', 'cache-warmer' ),
                                ],
                            ];
                        }

                        $settings = [
                            [
                                'title' => $unique_domain,
                                'type'  => 'title',
                                'id'    => 'external-warmer' . $unique_domain,
                            ],
                            [
                                'id'                => 'external-warmer-license-key' . $unique_domain,
                                'title'             => __( 'API Key', 'cache-warmer' ),
                                'value'             => get_option( 'cache-warmer-setting-external-warmer-license-key' . $unique_domain ),
                                'type'              => 'text',
                                'custom_attributes' => [
                                    'data-server-domain' => $unique_domain,
                                ],
                            ],
                            ... $cache_warmer_available_locations,
                            [
                                'type' => 'sectionend',
                                'id'   => 'external-warmer' . $unique_domain,
                            ],
                        ];

                        Setting_Fields::output_fields( $settings );
                    }
                    ?>
                </div>
            </div>
        </div>
        <hr>
        <div class="cache-warmer-container">
            <div class="cache-warmer-column mt-m20">
                <?php submit_button( __( 'Save', 'cache-warmer' ), [ 'primary', 'cache-warmer-submit' ] ); ?>
            </div>
        </div>
    </form>
</div>
