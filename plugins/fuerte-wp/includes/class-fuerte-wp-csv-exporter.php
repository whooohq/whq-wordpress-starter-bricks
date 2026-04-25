<?php

/**
 * CSV Exporter for Fuerte-WP Login Security.
 *
 * Handles exporting login logs and IP lists to CSV format.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * CSV Exporter class for generating downloadable CSV files.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_CSV_Exporter
{
    /**
     * Login Logger instance.
     *
     * @since 1.7.0
     *
     * @var Fuerte_Wp_Login_Logger
     */
    private $logger;

    /**
     * Initialize CSV Exporter.
     *
     * @since 1.7.0
     */
    public function __construct()
    {
        $this->logger = new Fuerte_Wp_Login_Logger();
    }

    /**
     * Export login attempts to CSV.
     *
     * @since 1.7.0
     *
     * @param array $args Filter arguments
     */
    public function export_attempts($args = [])
    {
        // Get all attempts (no pagination for export)
        $args['limit'] = 999999;
        $args['offset'] = 0;

        $attempts = $this->logger->get_attempts($args);

        // Generate CSV content
        $csv_data = $this->generate_attempts_csv($attempts);

        // Send to browser
        $filename = 'fuertewp-login-attempts-' . date('Y-m-d-H-i-s') . '.csv';
        $this->send_csv($filename, $csv_data);
    }

    /**
     * Generate CSV content from attempts data.
     *
     * @since 1.7.0
     *
     * @param array $attempts Login attempts data
     *
     * @return string CSV content
     */
    private function generate_attempts_csv($attempts)
    {
        $output = fopen('php://temp', 'r+');

        // Write BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, [
            'ID',
            'Date/Time',
            'IP Address',
            'Username',
            'Status',
            'User Agent',
            'Result Message',
        ]);

        // Data rows
        foreach ($attempts as $attempt) {
            fputcsv($output, [
                $attempt->id,
                $attempt->attempt_time,
                $attempt->ip_address,
                $attempt->username,
                $attempt->status,
                $this->sanitize_user_agent($attempt->user_agent),
                $attempt->result_message,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export IP whitelist/blacklist to CSV.
     *
     * @since 1.7.0
     *
     * @param string $type List type (whitelist or blacklist)
     */
    public function export_ip_list($type)
    {
        $ip_manager = new Fuerte_Wp_IP_Manager();
        $ips = $ip_manager->get_ip_list($type);

        $csv_data = $this->generate_ip_list_csv($ips);

        $filename = 'fuertewp-ip-' . $type . '-' . date('Y-m-d-H-i-s') . '.csv';
        $this->send_csv($filename, $csv_data);
    }

    /**
     * Generate CSV content from IP list data.
     *
     * @since 1.7.0
     *
     * @param array $ips IP entries
     *
     * @return string CSV content
     */
    private function generate_ip_list_csv($ips)
    {
        $output = fopen('php://temp', 'r+');

        // Write BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, [
            'ID',
            'IP or Range',
            'Type',
            'Range Type',
            'Note',
            'Created At',
        ]);

        // Data rows
        foreach ($ips as $ip) {
            fputcsv($output, [
                $ip->id,
                $ip->ip_or_range,
                $ip->type,
                $ip->range_type,
                $ip->note,
                $ip->created_at,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Import IP list from CSV file.
     *
     * @since 1.7.0
     *
     * @param string $csv_content CSV file content
     * @param string $type List type (whitelist or blacklist)
     *
     * @return array|WP_Error Import results
     */
    public function import_ip_list($csv_content, $type)
    {
        $ip_manager = new Fuerte_Wp_IP_Manager();

        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Parse CSV
        $rows = str_getcsv($csv_content, "\n");

        // Skip header row
        array_shift($rows);

        foreach ($rows as $row_num => $row) {
            $data = str_getcsv($row);

            if (count($data) < 2) {
                continue;
            }

            $ip_or_range = trim($data[1]);
            $note = isset($data[4]) ? trim($data[4]) : '';

            if (empty($ip_or_range)) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Row %d: Empty IP/range', 'fuerte-wp'), $row_num + 1);
                continue;
            }

            $result = $ip_manager->add_ip_to_list($ip_or_range, $type, $note);

            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Row %d: %s', 'fuerte-wp'),
                    $row_num + 1,
                    $result->get_error_message()
                );
            } else {
                $results['imported']++;
            }
        }

        return $results;
    }

    /**
     * Sanitize user agent for CSV export.
     *
     * Removes or escapes problematic characters.
     *
     * @since 1.7.0
     *
     * @param string $user_agent User agent string
     *
     * @return string Sanitized user agent
     */
    private function sanitize_user_agent($user_agent)
    {
        if (empty($user_agent)) {
            return '';
        }

        // Remove newlines and carriage returns
        $user_agent = str_replace(["\r", "\n"], ' ', $user_agent);

        // Limit length
        $user_agent = substr($user_agent, 0, 500);

        return $user_agent;
    }

    /**
     * Send CSV to browser with proper headers.
     *
     * @since 1.7.0
     *
     * @param string $filename Filename
     * @param string $csv_content CSV content
     */
    private function send_csv($filename, $csv_content)
    {
        // Clean any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . strlen($csv_content));

        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV content
        echo $csv_content;

        // Exit to prevent WordPress from adding extra content
        exit;
    }

    /**
     * Get all unique IPs from login attempts.
     *
     * Useful for generating reports.
     *
     * @since 1.7.0
     *
     * @param string $status Filter by status (optional)
     * @param string $date_from Start date (optional)
     * @param string $date_to End date (optional)
     *
     * @return array Array of IP addresses
     */
    public function get_unique_ips($status = '', $date_from = '', $date_to = '')
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_attempts';

        $where = [];
        $values = [];

        if (!empty($status)) {
            $where[] = 'status = %s';
            $values[] = $status;
        }

        if (!empty($date_from)) {
            $where[] = 'attempt_time >= %s';
            $values[] = $date_from;
        }

        if (!empty($date_to)) {
            $where[] = 'attempt_time <= %s';
            $values[] = $date_to;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT DISTINCT ip_address FROM $table $where_clause ORDER BY ip_address";

        if (!empty($values)) {
            $query = $wpdb->prepare($sql, ...$values);
        } else {
            $query = $sql;
        }

        return $wpdb->get_col($query);
    }

    /**
     * Generate simple statistics CSV.
     *
     * @since 1.7.0
     */
    public function export_stats()
    {
        $stats = $this->logger->get_lockout_stats();

        $output = fopen('php://temp', 'r+');

        // Write BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, ['Statistic', 'Value']);

        // Data rows
        fputcsv($output, ['Total Lockouts', $stats['total_lockouts']]);
        fputcsv($output, ['Active Lockouts', $stats['active_lockouts']]);
        fputcsv($output, ['Failed Attempts Today', $stats['failed_today']]);
        fputcsv($output, ['Failed Attempts This Week', $stats['failed_week']]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = 'fuertewp-login-stats-' . date('Y-m-d-H-i-s') . '.csv';
        $this->send_csv($filename, $csv);
    }
}
