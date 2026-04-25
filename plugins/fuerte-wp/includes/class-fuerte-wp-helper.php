<?php

/**
 * Helper Utilities for Fuerte-WP Login Security.
 *
 * Provides static methods for IP validation, CIDR calculations,
 * and range matching operations. Can be extended with other utility methods.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Helper class with static utility methods.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Helper
{
    /**
     * Check if IP is within a CIDR range.
     *
     * Efficient IPv4 CIDR matching with IPv6 support.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $cidr CIDR notation (e.g., 192.168.1.0/24)
     *
     * @return bool True if in range, false otherwise
     */
    public static function ip_in_cidr($ip, $cidr)
    {
        if (empty($ip) || empty($cidr) || strpos($cidr, '/') === false) {
            return false;
        }

        // Validate IP format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $parts = explode('/', $cidr, 2);
        $subnet = trim($parts[0]);
        $mask = isset($parts[1]) ? trim($parts[1]) : '32';

        // Validate subnet
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        // IPv6 handling
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::ipv6_in_cidr($ip, $cidr);
        }

        // IPv4 handling
        return self::ipv4_cidr_match($ip, $subnet, (int) $mask);
    }

    /**
     * Efficient IPv4 CIDR matching.
     *
     * Based on the Limit Login Attempts Reloaded approach.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $subnet Subnet address
     * @param int $mask Subnet mask (0-32)
     *
     * @return bool True if matches, false otherwise
     */
    public static function ipv4_cidr_match($ip, $subnet, $mask)
    {
        // Validate inputs
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            $mask === null || $mask === '' || $mask < 0 || $mask > 32) {
            return false;
        }

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);

        if ($ip_long === false || $subnet_long === false) {
            return false;
        }

        $mask_long = -1 << (32 - $mask);
        $subnet_long &= $mask_long; // Ensure subnet is correctly aligned

        return ($ip_long & $mask_long) == $subnet_long;
    }

    /**
     * Check if IPv6 is in CIDR range.
     *
     * @since 1.7.0
     *
     * @param string $ip IPv6 address
     * @param string $cidr IPv6 CIDR notation
     *
     * @return bool True if in range, false otherwise
     */
    public static function ipv6_in_cidr($ip, $cidr)
    {
        $parts = explode('/', $cidr, 2);
        $subnet = trim($parts[0]);
        $mask = (int) trim($parts[1]);

        if ($mask < 0 || $mask > 128) {
            return false;
        }

        $ip_binary = self::ipv6_to_binary($ip);
        $subnet_binary = self::ipv6_to_binary($subnet);

        // Compare first $mask bits
        return substr($ip_binary, 0, $mask) === substr($subnet_binary, 0, $mask);
    }

    /**
     * Convert IPv6 address to binary string.
     *
     * @since 1.7.0
     *
     * @param string $ipv6 IPv6 address
     *
     * @return string Binary representation (128 bits)
     */
    public static function ipv6_to_binary($ipv6)
    {
        // Expand IPv6 notation (handle :: compression)
        $ipv6 = self::expand_ipv6($ipv6);
        $parts = explode(':', $ipv6);
        $binary = '';

        foreach ($parts as $part) {
            // Convert each 16-bit hextet to binary
            $hex_val = hexdec($part);
            $binary .= str_pad(decbin($hex_val), 16, '0', STR_PAD_LEFT);
        }

        return $binary;
    }

    /**
     * Expand compressed IPv6 notation.
     *
     * @since 1.7.0
     *
     * @param string $ipv6 IPv6 address
     *
     * @return string Expanded IPv6 address
     */
    public static function expand_ipv6($ipv6)
    {
        // Handle :: compression
        if (strpos($ipv6, '::') !== false) {
            $parts = explode('::', $ipv6);
            $left = explode(':', $parts[0]);
            $right = isset($parts[1]) ? explode(':', $parts[1]) : [];

            $missing = 8 - (count($left) + count($right));
            $middle = array_fill(0, $missing, '0');

            $parts = array_merge($left, $middle, $right);
        } else {
            $parts = explode(':', $ipv6);
        }

        // Ensure we have exactly 8 parts
        while (count($parts) < 8) {
            $parts[] = '0';
        }

        // Pad each part to 4 hex digits
        foreach ($parts as &$part) {
            $part = str_pad($part, 4, '0', STR_PAD_LEFT);
        }

        return implode(':', $parts);
    }

    /**
     * Validate CIDR notation.
     *
     * @since 1.7.0
     *
     * @param string $range CIDR range (e.g., 192.168.1.0/24 or 2001:db8::/32)
     *
     * @return bool True if valid, false otherwise
     */
    public static function validate_cidr($range)
    {
        if (empty($range) || strpos($range, '/') === false) {
            return false;
        }

        $parts = explode('/', $range, 2);
        $ip = trim($parts[0]);
        $mask = trim($parts[1]);

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Validate mask
        if (!is_numeric($mask)) {
            return false;
        }

        $mask = (int) $mask;

        // IPv4 validation
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        }

        // IPv6 validation
        return $mask >= 0 && $mask <= 128;
    }

    /**
     * Check if IP matches wildcard pattern.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $pattern Wildcard pattern (e.g., 192.168.1.*)
     *
     * @return bool True if matches, false otherwise
     */
    public static function ip_matches_wildcard($ip, $pattern)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6 wildcard matching would be more complex
            return false;
        }

        // Convert wildcard to regex pattern
        $regex = str_replace('*', '\d+', $pattern);
        $regex = '/^' . str_replace('.', '\.', $regex) . '$/';

        return preg_match($regex, $ip) === 1;
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
    public static function ip_in_dash_range($ip, $range)
    {
        if (strpos($range, '-') === false) {
            return false;
        }

        $parts = explode('-', $range, 2);
        $start = trim($parts[0]);
        $end = trim($parts[1]);

        if (!filter_var($start, FILTER_VALIDATE_IP) || !filter_var($end, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // For IPv6, this would require more complex comparison
            return false;
        }

        // IPv4 comparison
        $ip_long = ip2long($ip);
        $start_long = ip2long($start);
        $end_long = ip2long($end);

        if ($ip_long === false || $start_long === false || $end_long === false) {
            return false;
        }

        return $ip_long >= $start_long && $ip_long <= $end_long;
    }

    /**
     * Check if IP is in reserved/private range.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     *
     * @return bool True if reserved, false otherwise
     */
    public static function is_reserved_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false; // Only check IPv4 for now
        }

        $reserved_ranges = [
            '10.0.0.0/8',        // Private Class A
            '172.16.0.0/12',     // Private Class B
            '192.168.0.0/16',    // Private Class C
            '127.0.0.0/8',       // Loopback
            '169.254.0.0/16',    // Link-local
            '224.0.0.0/4',       // Multicast
            '240.0.0.0/4',       // Reserved
            '0.0.0.0/8',         // This network
        ];

        foreach ($reserved_ranges as $range) {
            if (self::ip_in_cidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize IP address by removing port and cleaning whitespace.
     *
     * @since 1.7.0
     *
     * @param string $ip Raw IP address
     *
     * @return string Normalized IP address
     */
    public static function normalize_ip($ip)
    {
        if (empty($ip)) {
            return '';
        }

        $ip = trim($ip);

        // Remove port if present for IPv4
        if (strpos($ip, ':') !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $ip = $parts[0];
        }

        // Validate final IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Get network address for IP and mask.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param int $mask CIDR mask
     *
     * @return string|false Network address or false on failure
     */
    public static function get_network_address($ip, $mask)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::get_ipv6_network($ip, $mask);
        }

        // IPv4 calculation
        $ip_long = ip2long($ip);

        if ($ip_long === false) {
            return false;
        }

        $mask_long = -1 << (32 - $mask);
        $network_long = $ip_long & $mask_long;

        return long2ip($network_long);
    }

    /**
     * Get IPv6 network address.
     *
     * @since 1.7.0
     *
     * @param string $ip IPv6 address
     * @param int $mask CIDR mask
     *
     * @return string IPv6 network address
     */
    private static function get_ipv6_network($ip, $mask)
    {
        $binary = self::ipv6_to_binary($ip);
        $network_binary = substr($binary, 0, $mask) . str_repeat('0', 128 - $mask);

        // Convert back to IPv6 notation
        $hextets = str_split($network_binary, 16);
        $ipv6_parts = [];

        foreach ($hextets as $hextet) {
            $ipv6_parts[] = dechex(bindec($hextet));
        }

        return implode(':', $ipv6_parts);
    }
}
