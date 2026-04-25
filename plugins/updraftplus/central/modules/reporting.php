<?php

if (!defined('UPDRAFTCENTRAL_CLIENT_DIR')) die('No access.');

/**
 * - A container for RPC commands (white label reporting UpdraftCentral commands). Commands map exactly onto method names (and hence this class should not implement anything else, beyond the constructor, and private methods)
 * - Return format is array('response' => (string - a code), 'data' => (mixed));
 *
 * RPC commands are not allowed to begin with an underscore. So, any private methods can be prefixed with an underscore.
 */
class UpdraftCentral_Reporting_Commands extends UpdraftCentral_Commands {

	private $valid_statuses = array(
		'recurring',
		'manual',
	);

	private $max_number_of_recipients = 100;

	/**
	 * Update existing reports.
	 *
	 * @param array $reports Reports data which will be saved.
	 *
	 * @return void
	 */
	private function _update_existing_reports($reports) {
		update_option('updraftcentral_reporting_reports', $reports);
	}

	/**
	 * Normalize email address - lowercase and sanitize.
	 *
	 * @param string $email Email address to normalize.
	 *
	 * @return string Normalized email.
	 */
	private function _normalize_email($email) {
		return strtolower(sanitize_email($email));
	}

	/**
	 * Get existing reports.
	 *
	 * @return array List of all reports.
	 */
	private function _get_existing_reports() {
		return (array) get_option('updraftcentral_reporting_reports', array());
	}

	/**
	 * Update existing sent reports.
	 *
	 * @param array $sent_reports Sent reports data which will be saved.
	 *
	 * @return void
	 */
	private function _update_existing_sent_reports($sent_reports) {
		update_option('updraftcentral_reporting_sent_reports', $sent_reports);
	}

	/**
	 * Get existing sent reports.
	 *
	 * @return array List of all sent reports.
	 */
	private function _get_existing_sent_reports() {
		return (array) get_option('updraftcentral_reporting_sent_reports', array());
	}

	/**
	 * Get all the reports (scheduled or not scheduled).
	 *
	 * @param array $data Data array containing report IDs to fetch.
	 *
	 * @return array An array of reports.
	 */
	public function get_reports($data) {
		$all_reports = $this->_get_existing_reports();

		$sent_reports = array_reverse($this->_get_existing_sent_reports());

		foreach ($sent_reports as $key => $sent_report) {
			$sent_report['download_url'] = '';

			if (!empty($sent_report['pdf_attachment_id'])) {
				$sent_report['download_url'] = wp_get_attachment_url(absint($sent_report['pdf_attachment_id']));
			}

			$sent_reports[$key] = $sent_report;
		}

		if (empty($data['report_ids']) || !is_array($data['report_ids'])) {
			return $this->_response(array(
				'reports' => $all_reports,
				'sent_reports' => $sent_reports,
			));
		}

		$report_ids = array();

		foreach ($data['report_ids'] as $report_id) {
			$report_ids[] = sanitize_key(strval($report_id));
		}

		return $this->_response((array(
			'reports' => array_intersect_key($all_reports, array_flip($report_ids)),
			'sent_reports' => $sent_reports,
		)));
	}

	/**
	 * Delete a report.
	 *
	 * @param array $data Data array containing report ID to delete.
	 *
	 * @return array An array containing the result of the current process
	 */
	public function delete_report($data) {
		// Permission check.
		if (!current_user_can('manage_options')) {
			$result = array("error" => true, "message" => "not_allowed");
			return $this->_response($result);
		}

		// Return early if valid data structure is not present.
		if (!is_array($data)) {
			$result = array("error" => true, "message" => "invalid_data");
			return $this->_response($result);
		}

		$report_id = empty($data['id']) ? '' : sanitize_key(strval($data['id']));

		// Return early if ID not supplied.
		if (empty($report_id)) {
			$result = array('error' => true, 'message' => 'missing_id');
			return $this->_response($result);
		}

		// Get the reports from options table.
		$reports = $this->_get_existing_reports();

		// Check if the ID to delete is present.
		if (!isset($reports[$report_id])) {
			$result = array('error' => true, 'message' => 'invalid_id');
			return $this->_response($result);
		}

		unset($reports[$report_id]);

		$this->_update_existing_reports($reports);

		$result = array("error" => false, "message" => "reports_updated");
		return $this->_response($result);
	}

	/**
	 * Add a new report.
	 *
	 * @param array $data Report information to add
	 *
	 * @return array An array containing the result of the current process
	 */
	public function add_report($data) {
		// Permission check.
		if (!current_user_can('manage_options')) {
			$result = array("error" => true, "message" => "not_allowed");
			return $this->_response($result);
		}

		// Return early if valid report structure is not present.
		if (!is_array($data)) {
			$result = array("error" => true, "message" => "invalid_report_data");
			return $this->_response($result);
		}

		$report_name = isset($data['name']) ? sanitize_text_field(strval($data['name'])) : '';

		if ('' === $report_name) {
			$result = array("error" => true, "message" => "empty_name");
			return $this->_response($result);
		}

		$report_status = empty($data['status']) ? '' : sanitize_text_field(strval($data['status']));

		if (!in_array($report_status, $this->valid_statuses)) {
			$result = array("error" => true, "message" => "status_invalid");
			return $this->_response($result);
		}

		$template_id = empty($data['template_id']) ? '' : sanitize_key($data['template_id']);

		if (empty($template_id)) {
			$result = array("error" => true, "message" => "template_id_invalid");
			return $this->_response($result);
		}

		$recipients = (isset($data['recipients']) && is_array($data['recipients'])) ? $data['recipients'] : array();

		if (count($recipients) > $this->max_number_of_recipients) {
			$result = array("error" => true, "message" => "max_number_of_recipients_exceeded");
			return $this->_response($result);
		}

		$recipients = array_unique(array_map(array($this, '_normalize_email'), $recipients));

		// Sanity check for invalid email.
		foreach ($recipients as $email) {
			if (!is_email($email)) {
				$result = array(
					"error" => true,
					"message" => "recipients_invalid",
					"values" => array(
						'invalid_email' => $email,
					)
				);
				return $this->_response($result);
			}
		}

		if (empty($recipients)) {
			$result = array("error" => true, "message" => "no_recipients_provided");
			return $this->_response($result);
		}

		// Get existing reports from options table.
		$existing_reports = $this->_get_existing_reports();

		// Report.
		$report = array();

		$report_id = empty($data['id']) ? '' : sanitize_key(strval($data['id']));

		if (empty($report_id)) {
			// First report timestamp and formatted date.
			$next_report_timestamp = strtotime("+1 month", time());
			$next_report_formatted_date = date("j M, g:i a", $next_report_timestamp);

			$report_id = UpdraftPlus_Manipulation_Functions::generate_random_string(10);

			$report_id_generation_loops = 0;

			// Sanity check to check if the report ID exists.
			while (isset($existing_reports[$report_id])) {
				$report_id = UpdraftPlus_Manipulation_Functions::generate_random_string(10);
				++$report_id_generation_loops;

				// If we somehow exceed the max generation loops then return error - which will not happen in almost any case.
				if ($report_id_generation_loops > 10) {
					$result = array("error" => true, "message" => "report_id_generation_failed");
					return $this->_response($result);
				}
			}

			$report = array(
				'id' => $report_id,
				'name' => $report_name,
				'status' => $report_status,
				'template_id' => $template_id,
				'recipients' => $recipients,
				'last_report_timestamp' => 0,
				'last_report_formatted_date' => __('N/A', 'updraftplus'),
				'next_report_timestamp' => $next_report_timestamp,
				'next_report_formatted_date' => $next_report_formatted_date,
			);
		} elseif (!empty($existing_reports[$report_id])) {
			$report = $existing_reports[$report_id];

			$report['name'] = $report_name;
			$report['status'] = $report_status;
			$report['template_id'] = $template_id;
			$report['recipients'] = $recipients;
		} else {
			$result = array("error" => true, "message" => "report_does_not_exist");
			return $this->_response($result);
		}

		// Add the new report.
		$existing_reports[$report_id] = $report;

		// Update the reports.
		$this->_update_existing_reports($existing_reports);

		$result = array(
			"error" => false,
			"message" => "reports_updated",
			"values" => array(
				'report' => $report,
			)
		);

		return $this->_response($result);
	}

	/**
	 * Add a sent report.
	 *
	 * @param array $data Report information to add
	 *
	 * @return array An array containing the result of the current process
	 */
	public function add_sent_reports($data) {
		// Permission check.
		if (!current_user_can('manage_options')) {
			$result = array("error" => true, "message" => "not_allowed");
			return $this->_response($result);
		}

		// Return early if valid report structure is not present.
		if (!is_array($data) || empty($data['sent_reports_data'])) {
			$result = array("error" => true, "message" => "invalid_sent_report_data");
			return $this->_response($result);
		}

		$reports = $this->_get_existing_reports();
		$sent_reports = $this->_get_existing_sent_reports();

		$return_data = array();

		// Loop through all the sent reports.
		foreach ($data['sent_reports_data'] as $report_data) {
			$report_id = sanitize_key(strval($report_data['report_id']));

			// Skip if no report of this ID exists.
			if (empty($reports[$report_id])) {
				continue;
			}

			$report_sent_at_timestamp = time();
			$report_sent_at_formatted_date = date("j M, g:i a", $report_sent_at_timestamp);

			// Change the last report time of the report.
			$reports[$report_id]['last_report_timestamp'] = $report_sent_at_timestamp;
			$reports[$report_id]['last_report_formatted_date'] = $report_sent_at_formatted_date;

			// Save the PDF as attachment.
			$pdf_attachment_id = 0;
			$uploads_dir = wp_upload_dir();
			$custom_upload_directory = trailingslashit($uploads_dir['basedir']) . 'updraftcentral-white-label-reporting-pdfs/';
			$custom_upload_url = trailingslashit($uploads_dir['baseurl']) . 'updraftcentral-white-label-reporting-pdfs/';
			$filename = sanitize_text_field($reports[$report_id]['name']) . '-' . $report_sent_at_timestamp . '-' . UpdraftPlus_Manipulation_Functions::generate_random_string(5) .  '.pdf';
			$full_path = $custom_upload_directory . basename($filename);

			// Sanity check to test directory exists.
			wp_mkdir_p(dirname($full_path));

			file_put_contents($full_path, base64_decode($report_data['pdf_content']));

			$wp_filetype = wp_check_filetype($filename, null);
			$attachment = array(
				'guid'           => $custom_upload_url . basename($filename),
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$pdf_attachment_id = wp_insert_attachment($attachment, $full_path);

			require_once(ABSPATH . 'wp-admin/includes/image.php');

			$attach_data = wp_generate_attachment_metadata($pdf_attachment_id, $full_path);
			wp_update_attachment_metadata($pdf_attachment_id, $attach_data);

			$new_sent_report = array(
				'report' => $reports[$report_id]['name'],
				'sent' => (bool) $report_data['sent'],
				'template_id_used' => sanitize_key($report_data['template_id']),
				'template_name_used' => sanitize_text_field($report_data['template_name']),
				'services' => array_map('sanitize_text_field', $report_data['services']),
				'sent_at' => $report_sent_at_formatted_date,
				'number_of_recipients' => absint($report_data['number_of_recipients']),
				'pdf_attachment_id' => $pdf_attachment_id,
			);

			$sent_reports[] = $new_sent_report;
			$return_data[] = $new_sent_report;
		}

		$this->_update_existing_reports($reports);
		$this->_update_existing_sent_reports($sent_reports);

		$result = array(
			"error" => false,
			"message" => "sent_reports_updated",
			"data" => $return_data,
		);

		return $this->_response($result);
	}
}
