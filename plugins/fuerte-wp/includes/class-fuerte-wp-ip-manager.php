<?php

/**
 * IP Management for Fuerte-WP Login Security.
 *
 * Handles IP detection, validation, and whitelist/blacklist functionality.
 * Supports IPv4, IPv6, CIDR ranges, and Cloudflare/Sucuri IP headers.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

// Load the helper class
require_once FUERTEWP_PATH . 'includes/class-fuerte-wp-helper.php';

/**
 * IP Manager class for handling IP address operations.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_IP_Manager
{
    /**
     * Custom IP headers from config.
     *
     * @since 1.7.0
     *
     * @var array
     */
    private $custom_ip_headers = [];

    /**
     * Initialize IP Manager.
     *
     * @since 1.7.0
     */
    public function __construct()
    {
        // Load custom IP headers from settings
        $this->load_custom_ip_headers();
    }

    /**
     * Load custom IP headers from WordPress options.
     *
     * @since 1.7.0
     */
    private function load_custom_ip_headers()
    {
        $custom_headers = Fuerte_Wp_Config::get_field('login_ip_headers');

        if (!empty($custom_headers)) {
            // Use optimized string operations if available
            if (class_exists('Fuerte_Wp_String_Optimizer')) {
                $this->custom_ip_headers = array_map('trim', Fuerte_Wp_String_Optimizer::explode_cached(',', $custom_headers));
            } else {
                // Fallback to basic explode
                $this->custom_ip_headers = array_map('trim', explode(',', $custom_headers));
            }
        }
    }

    /**
     * Get the real client IP address.
     *
     * Supports Cloudflare, Sucuri, and other proxy services.
     * Enhanced with better IP validation and fallback handling.
     *
     * @since 1.7.0
     *
     * @return string IP address
     */
    public function get_client_ip()
    {
        $ip_headers = [
            // Cloudflare
            'HTTP_CF_CONNECTING_IP',
            // Sucuri
            'HTTP_X_SUCURI_CLIENTIP',
            'HTTP_X_FORWARDED_FOR',
            // Standard proxy headers
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            // Custom headers from settings
        ];

        // Add custom headers
        $ip_headers = array_merge($ip_headers, $this->custom_ip_headers);

        foreach ($ip_headers as $header) {
            $value = $this->get_header_value($header);

            if (!empty($value)) {
                $ip = $this->extract_ip_from_header($value);

                if ($this->is_valid_ip($ip)) {
                    return $ip;
                }
            }
        }

        // Fallback to REMOTE_ADDR - use it directly if it's valid
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($this->is_valid_ip($remote_addr)) {
            return $remote_addr;
        }

        return '0.0.0.0';
    }

    /**
     * Get header value safely.
     *
     * @since 1.7.0
     *
     * @param string $header Header name
     *
     * @return string Header value or empty string
     */
    private function get_header_value($header)
    {
        // Headers are typically available in $_SERVER with HTTP_ prefix
        if (class_exists('Fuerte_Wp_String_Optimizer')) {
            if (!Fuerte_Wp_String_Optimizer::starts_with($header, 'HTTP_')) {
                $header = 'HTTP_' . str_replace('-', '_', strtoupper($header));
            }
        } else {
            if (strpos($header, 'HTTP_') !== 0) {
                $header = 'HTTP_' . str_replace('-', '_', strtoupper($header));
            }
        }

        return $_SERVER[$header] ?? '';
    }

    /**
     * Extract and validate IP from header value.
     *
     * Handles headers with multiple IPs, ports, and invalid formats.
     * Uses Fuerte_Wp_IP_Helper for normalization.
     *
     * @since 1.7.0
     *
     * @param string $header_value Header value containing IP(s)
     *
     * @return string First valid IP found
     */
    private function extract_ip_from_header($header_value)
    {
        if (empty($header_value)) {
            return '';
        }

        // Use optimized explode operation if available
        $ips = class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::explode_cached(',', $header_value)
            : explode(',', $header_value);

        foreach ($ips as $ip) {
            // Normalize IP using helper
            $normalized_ip = Fuerte_Wp_Helper::normalize_ip($ip);

            if (!empty($normalized_ip)) {
                // Additional validation: exclude private/local IPs in certain contexts
                if (!Fuerte_Wp_Helper::is_reserved_ip($normalized_ip)) {
                    return $normalized_ip;
                }
            }
        }

        return '';
    }

    /**
     * Validate IP address format.
     *
     * Checks for IPv4 and IPv6 validity.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function is_valid_ip($ip)
    {
        if (empty($ip) || !is_string($ip)) {
            return false;
        }

        // Filter for IP
        $filtered = filter_var($ip, FILTER_VALIDATE_IP);

        return $filtered !== false;
    }

    /**
     * Check if IP is IPv6.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     *
     * @return bool True if IPv6, false if IPv4
     */
    public function is_ipv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate CIDR notation.
     *
     * Uses Fuerte_Wp_IP_Helper for validation.
     *
     * @since 1.7.0
     *
     * @param string $range CIDR range (e.g., 192.168.1.0/24 or 2001:db8::/32)
     *
     * @return bool True if valid, false otherwise
     */
    public function validate_cidr($range)
    {
        return Fuerte_Wp_Helper::validate_cidr($range);
    }

    /**
     * Check if IP is within a CIDR range.
     *
     * Enhanced to support wildcards, CIDR, dash ranges, and single IPs.
     * Uses the Fuerte_Wp_IP_Helper for calculations.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $range Range (IP, CIDR, dash range, or wildcard)
     *
     * @return bool True if in range, false otherwise
     */
    public function ip_in_range($ip, $range)
    {
        if (!$this->is_valid_ip($ip) || empty($range)) {
            return false;
        }

        // Use optimized IP range parsing if available
        if (class_exists('Fuerte_Wp_String_Optimizer')) {
            $parsed_range = Fuerte_Wp_String_Optimizer::parse_ip_range($range);

            switch ($parsed_range['type']) {
                case 'single':
                    return $ip === $parsed_range['value'];
                case 'cidr':
                    return Fuerte_Wp_Helper::ip_in_cidr($ip, $parsed_range['value']);
                case 'range':
                    return Fuerte_Wp_Helper::ip_in_dash_range($ip, $parsed_range['start'] . '-' . $parsed_range['end']);
                case 'wildcard':
                    return Fuerte_Wp_Helper::ip_matches_wildcard($ip, $parsed_range['value']);
                case 'partial':
                    return $this->ip_in_partial_range($ip, $parsed_range['value']);
                default:
                    return false;
            }
        }

        // Fallback to original logic
        $range = class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::trim_optimized($range)
            : trim($range);

        // Direct IP match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation (e.g., 192.168.1.0/24)
        if (strpos($range, '/') !== false) {
            return Fuerte_Wp_Helper::ip_in_cidr($ip, $range);
        }

        // Dash range (e.g., 192.168.1.1-192.168.1.10)
        if (strpos($range, '-') !== false) {
            return Fuerte_Wp_Helper::ip_in_dash_range($ip, $range);
        }

        // Wildcard notation (e.g., 192.168.1.*)
        if (strpos($range, '*') !== false) {
            return Fuerte_Wp_Helper::ip_matches_wildcard($ip, $range);
        }

        // Partial CIDR (e.g., 192.168.1)
        if (count(explode('.', $range)) < 4 && !$this->is_ipv6($range)) {
            return $this->ip_in_partial_range($ip, $range);
        }

        return false;

        return false;
    }

    /**
     * Check if IP is in dash-separated range.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $range Range (e.g., 192.168.1.1-192.168.1.10)
     *
     * @return bool True if in range, false otherwise
     */
    private function ip_in_dash_range($ip, $range)
    {
        $parts = class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::explode_cached('-', $range, 2)
            : explode('-', $range, 2);

        if (count($parts) !== 2) {
            return false;
        }
        $start = class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::trim_optimized($parts[0])
            : trim($parts[0]);
        $end = class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::trim_optimized($parts[1])
            : trim($parts[1]);

        if (!$this->is_valid_ip($start) || !$this->is_valid_ip($end)) {
            return false;
        }

        if ($this->is_ipv6($ip)) {
            return $this->ipv6_in_range($ip, $start . '-' . $end);
        }

        // IPv4
        $ip_long = ip2long($ip);
        $start_long = ip2long($start);
        $end_long = ip2long($end);

        return $ip_long >= $start_long && $ip_long <= $end_long;
    }

    /**
     * Check if IP matches wildcard pattern.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $range Wildcard range (e.g., 192.168.1.*)
     *
     * @return bool True if matches, false otherwise
     */
    private function ip_in_wildcard_range($ip, $range)
    {
        if ($this->is_ipv6($ip)) {
            // IPv6 wildcard matching is more complex, simplified here
            return false;
        }

        // Use optimized regex pattern matching if available
        if (class_exists('Fuerte_Wp_String_Optimizer')) {
            $pattern = Fuerte_Wp_String_Optimizer::replace_optimized('*', '\d+', $range);
            $pattern = Fuerte_Wp_String_Optimizer::replace_optimized('.', '\.', $pattern);
            $pattern = '/^' . $pattern . '$/';

            return Fuerte_Wp_String_Optimizer::preg_match_cached($pattern, $ip) === 1;
        } else {
            // Fallback to original regex
            $pattern = str_replace('*', '\d+', $range);
            $pattern = str_replace('.', '\.', $pattern);
            $pattern = '/^' . $pattern . '$/';

            return preg_match($pattern, $ip) === 1;
        }
    }

    /**
     * Check if IP is in partial range.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $range Partial range (e.g., 192.168.1)
     *
     * @return bool True if matches, false otherwise
     */
    private function ip_in_partial_range($ip, $range)
    {
        if ($this->is_ipv6($ip)) {
            return class_exists('Fuerte_Wp_String_Optimizer')
                ? Fuerte_Wp_String_Optimizer::starts_with($ip, $range)
                : strpos($ip, $range) === 0;
        }

        // For IPv4, partial means matching the beginning octets
        return class_exists('Fuerte_Wp_String_Optimizer')
            ? Fuerte_Wp_String_Optimizer::starts_with($ip, $range . '.')
            : strpos($ip, $range . '.') === 0;
    }

    /**
     * Check if IP is whitelisted.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     *
     * @return bool True if whitelisted, false otherwise
     */
    public function is_whitelisted($ip)
    {
        return $this->check_ip_list($ip, 'whitelist');
    }

    /**
     * Check if IP is blacklisted.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     *
     * @return bool True if blacklisted, false otherwise
     */
    public function is_blacklisted($ip)
    {
        return $this->check_ip_list($ip, 'blacklist');
    }

    /**
     * Check IP against whitelist or blacklist.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $type List type (whitelist or blacklist)
     *
     * @return bool True if found in list, false otherwise
     */
    private function check_ip_list($ip, $type)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_ips';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ip_or_range, range_type FROM $table WHERE type = %s",
                $type
            )
        );

        foreach ($results as $row) {
            if ($this->ip_in_range($ip, $row->ip_or_range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all IPs from whitelist or blacklist.
     *
     * @since 1.7.0
     *
     * @param string $type List type (whitelist or blacklist)
     *
     * @return array Array of IP entries
     */
    public function get_ip_list($type)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_ips';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE type = %s ORDER BY created_at DESC",
                $type
            )
        );
    }

    /**
     * Add IP to whitelist or blacklist.
     *
     * @since 1.7.0
     *
     * @param string $ip_or_range IP or range
     * @param string $type List type (whitelist or blacklist)
     * @param string $note Optional note
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function add_ip_to_list($ip_or_range, $type, $note = '')
    {
        // Validate the IP or range
        if (!$this->is_valid_ip($ip_or_range) && !$this->validate_cidr($ip_or_range) && strpos($ip_or_range, '-') === false) {
            return new WP_Error('invalid_ip', __('Invalid IP address or range format.'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fuertewp_login_ips';

        // Determine range type
        $range_type = 'single';

        if (strpos($ip_or_range, '/') !== false) {
            $range_type = 'cidr';
        } elseif (strpos($ip_or_range, '-') !== false) {
            $range_type = 'range';
        }

        // Check if already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE ip_or_range = %s AND type = %s LIMIT 1",
                $ip_or_range,
                $type
            )
        );

        if ($exists) {
            return new WP_Error('ip_exists', __('IP or range already exists in the list.'));
        }

        // Insert
        $result = $wpdb->insert(
            $table,
            [
                'ip_or_range' => $ip_or_range,
                'type' => $type,
                'range_type' => $range_type,
                'note' => sanitize_text_field($note),
                'created_at' => current_time('mysql'),
            ]
        );

        return $result !== false;
    }

    /**
     * Remove IP from whitelist or blacklist.
     *
     * @since 1.7.0
     *
     * @param int $id IP entry ID
     *
     * @return bool True on success, false on failure
     */
    public function remove_ip_from_list($id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_ips';

        return $wpdb->delete(
            $table,
            ['id' => (int) $id],
            ['%d']
        ) !== false;
    }
}
