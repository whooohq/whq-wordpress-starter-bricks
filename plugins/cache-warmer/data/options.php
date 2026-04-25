<?php
/**
 * Defines plugin options.
 *
 * Format:
 *  'default':   Default option value.                 If not set, equals false.
 *  'autoload':  Whether to autoload the option.       If not set, equals true.
 *  'shortTerm': Whether the option is short-term.     If not set, equals false.
 *               If true and object cache is enabled,
 *               then uses it to store the option.
 *
 * @package Cache-Warmer
 */

return apply_filters(
    'cache-warmer-options',
    [

        /*
         * Last handled version update.
         */
        'cache-warmer-last-handled-version-update'         => [],

        /*
         * Depth of the site pages visit.
         */
        'cache-warmer-setting-depth'                       => [
            'autoload' => false,
            'default'  => 1,
        ],

        /*
         * User-Agents list.
         */
        'cache-warmer-setting-user-agents'                 => [
            'autoload' => false,
            'default'  => [ [ 'value' => CACHE_WARMER_DEFAULT_UA ] ],
        ],

        /*
         * Cookies.
         */
        'cache-warmer-setting-cookies'                     => [
            'autoload' => false,
            'default'  => [],
        ],

        /*
         * Interval between the automatic warm-ups.
         */
        'cache-warmer-setting-interval'                    => [
            'autoload' => false,
            'default'  => 0,
        ],

        /*
         * URL params.
         */
        'cache-warmer-setting-url-params'                  => [
            'autoload' => false,
            'default'  => [],
        ],

        /*
         * Request headers.
         */
        'cache-warmer-setting-request-headers'             => [
            'autoload' => false,
            'default'  => [],
        ],

        /*
         * Entry points.
         */
        'cache-warmer-setting-entry-points'                => [
            'autoload' => false,
            'default'  => [ [ 'url' => untrailingslashit( CACHE_WARMER_ENTRY_URL ) ] ],
        ],

        /*
         * Connection timeout.
         */
        'cache-warmer-setting-timeout'                     => [
            'autoload' => false,
            'default'  => 15,
        ],

        /*
         * Visit pages second time without custom URL params.
         */
        'cache-warmer-setting-visit-second-time-without-url-params' => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Visit pages second time without cookies.
         */
        'cache-warmer-setting-visit-second-time-without-cookies' => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Rewrite HTTP to HTTPS.
         */
        'cache-warmer-setting-rewrite-to-https'            => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Speed limit (pages per minute).
         */
        'cache-warmer-setting-speed-limit'                 => [
            'autoload' => false,
            'default'  => 1000,
        ],

        /*
         * Whether to exclude the pages with the already warmed canonical.
         */
        'cache-warmer-setting-exclude-pages-with-warmed-canonical' => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * For how many days to keep the logs.
         */
        'cache-warmer-setting-for-how-many-days-to-keep-the-logs' => [
            'autoload' => false,
            'default'  => 30,
        ],

        /*
         * Current warm-up leftovers links tree, equal to false if no warm-up currently running.
         */
        'cache-warmer-links-tree-leftovers'                => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Last external warmup request args warmup ID.
         */
        'cache-warmer-last-external-warmup-request-args-id' => [
            'autoload' => false,
            'default'  => false,
        ],

        /*
         * Last successful warmup ID.
         */
        'cache-warmer-last-success-warmup-id'              => [
            'autoload' => false,
            'default'  => false,
        ],

        /*
         * Last stopped by hand warmup ID.
         */
        'cache-warmer-last-stopped-by-hand-warmup-id'      => [
            'autoload' => false,
            'default'  => false,
        ],

        /*
         * Last successful warmup: External warmup request args.
         */
        'cache-warmer-last-success-warmup-external-warmup-request-args' => [
            'autoload' => false,
            'default'  => [],
        ],

        /*
         * An array of links that were failed to retrieve, where key is link and value is depth.
         */
        'cache-warmer-failed-to-retrieve-links'            => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * An array of links the pages for whose were retrieved, where key is link and value is time() of the retrieval.
         */
        'cache-warmer-retrieved-links'                     => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Whether the last warm-up was manually stopped.
         */
        'cache-warmer-last-warmup-was-stopped-by-hand'     => [
            'autoload'  => false,
            'shortTerm' => true,
        ],

        /*
         * Assets preloading for scripts.
         */
        'cache-warmer-setting-assets-preloading-scripts'   => [
            'autoload' => false,
            'default'  => '1',
        ],

        /*
         * Assets preloading for styles.
         */
        'cache-warmer-setting-assets-preloading-styles'    => [
            'autoload' => false,
            'default'  => '1',
        ],

        /*
         * Assets preloading for images.
         */
        'cache-warmer-setting-assets-preloading-images'    => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Assets preloading for fonts.
         */
        'cache-warmer-setting-assets-preloading-fonts'     => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Array of IDs of posts to warm (for example after their publication).
         */
        'cache-warmer-posts-enqueue'                       => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Unscheduled: Current warm-up leftovers links tree, equal to false if no warm-up currently running.
         */
        'cache-warmer-unscheduled-links-tree-leftovers'    => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Unscheduled: An array of links that were failed to retrieve, where key is link and value is depth.
         */
        'cache-warmer-unscheduled-failed-to-retrieve-links' => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Unscheduled: An array of links the pages for whose were retrieved, where key is link and value is time() of the retrieval.
         */
        'cache-warmer-unscheduled-retrieved-links'         => [
            'autoload'  => false,
            'default'   => [],
            'shortTerm' => true,
        ],

        /*
         * Whether to warm-up newly published posts.
         */
        'cache-warmer-setting-warm-up-posts'               => [
            'autoload' => false,
            'default'  => true,
        ],

        /*
         * Posts warming delay (after their publication or modification).
         */
        'cache-warmer-setting-posts-warming-enqueue-interval' => [
            'default' => 3,
        ],

        /*
         * License key for the external warmer.
         */
        'cache-warmer-setting-external-warmer-license-key' => [
            'default' => '',
        ],

        /*
         * External warmer domain.
         */
        'cache-warmer-setting-external-warmer-domain'      => [
            'default' => '',
        ],

        /*
         * The list of excluded pages.
         */
        'cache-warmer-setting-excluded-pages'              => [
            'default' => [],
        ],

        /*
         * Notifications data.
         */
        'cache-warmer-notifications'                       => [
            'default' => [],
        ],

        /*
         * The list of IDs of viewed notifications.
         */
        'cache-warmer-viewed-notifications'                => [
            'default' => [],
        ],

        /*
         * Add all pages and posts settings.
         */
        'cache-warmer-setting-add-this-site-all-public-posts' => [
            'default' => '',
        ],

        /*
         * Add entry points sites sitemaps.
         */
        'cache-warmer-setting-add-entry-point-sites-sitemaps' => [
            'default' => '',
        ],

        /*
         * Use object cache, when it's available.
         */
        'cache-warmer-setting-use-object-cache'            => [
            'autoload' => false,
            'default'  => '1',
        ],

        /*
         * Use RegEx match for excluded sites.
         */
        'cache-warmer-setting-excluded-pages-use-regex-match' => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Use external warmer servers during the warming.
         */
        'cache-warmer-setting-use-external-warmer-servers-during-the-warming' => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Last processed link.
         */
        'cache-warmer-last-processing-link'                => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Last retrieved link.
         */
        'cache-warmer-last-retrieved-link'                 => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Last failed to retrieve link.
         */
        'cache-warmer-last-failed-to-retrieve-link'        => [
            'autoload' => false,
            'default'  => '',
        ],

        /*
         * Last DB successful migration number.
         */
        'cache-warmer-last-db-success-migration-number'    => [
            'default' => 0,
        ],

        /*
         * Whether the last db init was successful, for all migrations.
         */
        'cache-warmer-last-db-migration-success'           => [
            'default' => true,
        ],

        /*
         * DB migration log.
         */
        'cache-warmer-db-migration-log'                    => [
            'autoload' => false,
            'default'  => [],
        ],
    ]
);
