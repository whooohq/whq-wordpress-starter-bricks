<?php

if (!defined('UPDRAFTCENTRAL_CLIENT_DIR')) die('No access.');

/**
 * Handles commands to access the REST API.
 *
 * This action is used to relay any REST API request through UDC command.
 * From UDC we don't have direct authentication to child site's REST APIs, therefore this middleware.
 *
 * UDC authentication logic automatically authenticates the user with which you have added the site to UDC dashboard.
 * And then the request is just relayed to the WordPress rest api handler, which takes care of permission callback and everything as usual.
 */
class UpdraftCentral_REST_API_Access_Commands extends UpdraftCentral_Commands {

	protected $switched = false;

	/**
	 * Function that gets called before every action
	 *
	 * Link to udrpc_action main function in class UpdraftCentral_Listener
	 *
	 * @param string $command a string that corresponds to UDC command to call a certain method for this class.
	 * @param array  $data    an array of data post or get fields
	 */
	public function _pre_action($command, $data) {
		$blog_id = get_current_blog_id();
		if (!empty($data['site_id'])) $blog_id = $data['site_id'];
	
		if (function_exists('switch_to_blog') && is_multisite() && $blog_id) {
			$this->switched = switch_to_blog($blog_id);
		}
	}

	/**
	 * Function that gets called after every action
	 *
	 * Link to udrpc_action main function in class UpdraftCentral_Listener
	 */
	public function _post_action() {
		// Here, we're restoring to the current (default) blog before we switched
		if ($this->switched) restore_current_blog();
	}

	/**
	 * Relays the REST API request.
	 *
	 * @param array $params The parameters for the request.
	 *
	 * @return array The response from the REST API, wrapped in udrpc response structure.
	 */
	public function handle_request($params) {
		$route = untrailingslashit(!empty($params['route']) ? $params['route'] : '');
		$method = !empty($params['method']) ? $params['method'] : 'GET';
		$body = !empty($params['body']) ? $params['body'] : null;

		// Return early if the route is empty.
		if (empty($route)) {
			return $this->_generic_error_response('route_empty', array(
				'prefix' => 'updraftcentral',
				'command' => 'handle_request',
				'class' => 'UpdraftCentral_REST_API_Access_Commands'
			));
		}

		if (!class_exists('WP_REST_Request')) {
			return $this->_generic_error_response('rest_api_not_available_on_this_wordpress_version', array(
				'prefix' => 'updraftcentral',
				'command' => 'handle_request',
				'class' => 'UpdraftCentral_REST_API_Access_Commands'
			));
		}

		$request = new WP_REST_Request($method, '/' . $route);

		if (!empty($body)) {
			$request->set_body(json_encode($body));
			$request->set_header('Content-Type', 'application/json');
		}

		// Do the request.
		$response = rest_do_request($request);

		// Return if error.
		if (true === $response->is_error()) {
			return $this->_generic_error_response('rest_request_failed', $response->as_error());
		}

		// `get_data` should always return JSON-serializable data.
		return $this->_response(array('rest_data' => $response->get_data(), 'headers' => $response->headers));
	}
}
