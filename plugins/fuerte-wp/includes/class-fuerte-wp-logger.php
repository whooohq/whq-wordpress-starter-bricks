<?php

/**
 * Logger Class for Fuerte-WP.
 *
 * Simple wrapper for logging that can be easily enabled/disabled.
 * Allows for quick debugging without modifying error_log calls throughout the codebase.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Simple logger class for Fuerte-WP.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Logger
{
    /**
     * Whether logging is enabled.
     *
     * @since 1.7.0
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * Log prefix for all messages.
     *
     * @since 1.7.0
     *
     * @var string
     */
    private static $prefix = '[Fuerte-WP]';

    /**
     * Enable or disable logging.
     *
     * @since 1.7.0
     *
     * @param bool $enable Whether to enable logging
     */
    public static function enable($enable = true)
    {
        self::$enabled = (bool) $enable;
    }

    /**
     * Disable logging.
     *
     * @since 1.7.0
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Check if logging is enabled.
     *
     * @since 1.7.0
     *
     * @return bool Whether logging is enabled
     */
    public static function is_enabled()
    {
        return self::$enabled;
    }

    /**
     * Log a message.
     *
     * @since 1.7.0
     *
     * @param string $message Message to log
     * @param string $level Log level (DEBUG, INFO, WARNING, ERROR)
     */
    public static function log($message, $level = 'INFO')
    {
        if (!self::$enabled) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = sprintf('%s [%s] %s: %s', self::$prefix, $timestamp, $level, $message);

        error_log($formatted_message);
    }

    /**
     * Log debug message.
     *
     * @since 1.7.0
     *
     * @param string $message Debug message
     */
    public static function debug($message)
    {
        self::log($message, 'DEBUG');
    }

    /**
     * Log info message.
     *
     * @since 1.7.0
     *
     * @param string $message Info message
     */
    public static function info($message)
    {
        self::log($message, 'INFO');
    }

    /**
     * Log warning message.
     *
     * @since 1.7.0
     *
     * @param string $message Warning message
     */
    public static function warning($message)
    {
        self::log($message, 'WARNING');
    }

    /**
     * Log error message.
     *
     * @since 1.7.0
     *
     * @param string $message Error message
     */
    public static function error($message)
    {
        self::log($message, 'ERROR');
    }

    /**
     * Log an array or object (for debugging).
     *
     * @since 1.7.0
     *
     * @param mixed $data Data to log
     * @param string $message Optional message to prepend
     * @param string $level Log level
     */
    public static function log_data($data, $message = '', $level = 'DEBUG')
    {
        if (!self::$enabled) {
            return;
        }

        $formatted_data = print_r($data, true);
        $full_message = $message ? $message . ': ' . $formatted_data : $formatted_data;

        self::log($full_message, $level);
    }

    /**
     * Set custom prefix.
     *
     * @since 1.7.0
     *
     * @param string $prefix Custom prefix
     */
    public static function set_prefix($prefix)
    {
        self::$prefix = $prefix;
    }

    /**
     * Enable logging via WordPress constant.
     * Call this during plugin initialization if you want to use a constant.
     *
     * @since 1.7.0
     */
    public static function init_from_constant()
    {
        if (defined('FUERTEWP_DEBUG_LOGGING') && FUERTEWP_DEBUG_LOGGING) {
            self::enable(true);
        }

        if (defined('FUERTEWP_LOG_PREFIX')) {
            self::set_prefix(FUERTEWP_LOG_PREFIX);
        }
    }
}
