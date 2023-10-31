<?php
/*! CloudTables API client for WordPress
 * ©SpryMedia Ltd - MIT licensed
 */

namespace Cloudtables;

class Api {
	private $_domain = 'cloudtables.io';
	private $_duration = null;
	private $_key;
	private $_roles = [];
	private $_ssl = true;
	private $_selfSigned = false;
	private $_subdomain;
	private $_clientId = null;
	private $_clientName = null;
	private $_accessToken = null;
	private $_conditions = null;

	/**
	 * Create a CloudTables API class instance
	 * @param string $subdomain Application sub-domain
	 * @param string $key API key
	 * @param array $options = [
	 *   'domain' => 'cloudtables.io', // Domain that the API should interface with (prefixed with the application id)
	 *   'duration' => 3600, // Token expire duration
	 *   'roles' => [], // Array of roles (union'ed with the roles for the API key)
	 *   'role' => null, // Single role (takes priority over `roles` if both used)
	 *   'secure' => true, // true for strict ssl certificates, false for self-signed
	 *   'clientId' => null, // Your unique identifier for the user
	 *   'clientName' => null, // Name / label to give the use in the CloudTables configuration UI
	 * ]
	 */
	function __construct($subdomain, $key, $options = null) {
		$this->_key = $key;
		$this->_subdomain = $subdomain;

		if ($options && isset($options['domain'])) {
			$this->_domain = $options['domain'];
		}

		if ($options && isset($options['duration'])) {
			$this->_duration = $options['duration'];
		}

		if ($options && isset($options['roles'])) {
			$this->_roles = $options['roles'];
		}

		if ($options && isset($options['role'])) {
			$this->_roles = array($options['role']);
		}

		if ($options && isset($options['ssl'])) {
			$this->_ssl = $options['ssl'];
		}

		if ($options && isset($options['selfSigned'])) {
			$this->_selfSigned = $options['selfSigned'];
		}

		if ($options && isset($options['clientId'])) {
			$this->_clientId = $options['clientId'];
		}

		if ($options && isset($options['clientName'])) {
			$this->_clientName = $options['clientName'];
		}

		if ($options && isset($options['conditions'])) {
			$this->_conditions = $options['conditions'];
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get an Access Token for CloudTables
	 * @param boolean $echoError Indicate if errors should be shown (true by default)
	 * @return string|false `false` is return if unable to get a token, otherwise
	 *   an access token is given.
	 */
	public function token($echoError = true) {
		// Caching for token reuse
		if ($this->_accessToken !== null) {
			return $this->_accessToken;
		}

		$url = $this->_url('/api/1/access');
		$data = array();

		if ($this->_duration) {
			$data['duration'] = $this->_duration;
		}

		if ($this->_clientId) {
			$data['clientId'] = $this->_clientId;
		}

		if ($this->_clientName) {
			$data['clientName'] = $this->_clientName;
		}

		$json = $this->_http($url, 'post', $data);

		if ($json === false) {
			return false;
		}
		else if (isset($json['errors'])) {
			error_log( print_r($json['errors'], true) );
			return false;
		}
		else {
			$this->_accessToken = $json['token'];

			return $json['token'];
		}
	}

	/**
	 * Set conditions
	 */
	public function conditions($cond) {
		$this->_conditions = $cond;

		return $this;
	}

	/**
	 * Get the data and columns for a given dataset
	 * @return array|false See API documentation for details please. False is returned if
	 *   an error occurs.
	 */
	public function data($dataset) {
		$url = $this->_url('/api/1/dataset/'. $dataset .'/data');
		return $this->_http($url, 'get');
	}

	/**
	 * Get summary information about the available datasets
	 * @return array|false See API documentation for details please. False is returned if
	 *   an error occurs.
	 */
	public function datasets() {
		$url = $this->_url('/api/1/datasets');
		$json = $this->_http($url, 'get');

		return isset($json['datasets'])
			? $json['datasets']
			: false;
	}

	/**
	 * Get information so we can build and enqueue a WP script with an insert point
	 * @param string $arg1 Access token (from `->token()`) or Dataset id
	 * @param string $arg2 Dataset id if a token is given for $arg1, the styling framework otherwise
	 * @param string $arg3 Styling framework to use for the table
	 * @return string `<script>` tag to use
	 */
	public function scriptTag($arg1, $arg2=null, $arg3=null) {
		if ($arg2 === null) {
			$token = $this->token();
			$datasetId = $arg1;
			$style = 'd';
		}
		else if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $arg1) === 1) {
			$token = $this->token();
			$datasetId = $arg1;
			$style = $arg2 !== null ? $arg2 : 'd';
		}
		else {
			$token = $arg1;
			$datasetId = $arg2;
			$style = $arg3 !== null ? $arg3 : 'd';
		}

		$rnd = 'ct-insert-'. rand();

		return [
			'url' => $this->_url("/loader/{$datasetId}/table/{$style}?insert=".$rnd),
			'unique' => $rnd,
			'token' => $token
		];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Perform a GET or POST request to get information. Assumes JSON will be returned
	 * @param string $url URL to query
	 * @param string $method 'get' or 'post'
	 * @param string $data Data to set to the server
	 * @return array|false Resolves JSON array or `false` on error
	 * @private
	 */
	private function _http($url, $method, $data=array()) {
		// Add common parameters
		$data['key'] = $this->_key;

		if (count($this->_roles)) {
			$data['roles'] = $this->_roles;
		}

		if ($this->_conditions) {
			$data['conditions'] = $this->_conditions;
		}

		if ($method === 'get') {
			$response = wp_remote_get( $url, [
				'body' => $data,
				'sslverify' => ! $this->_selfSigned
			] );
		}
		else {
			$response = wp_remote_post( $url, [
				'body' => $data,
				'sslverify' => ! $this->_selfSigned
			] );
		}

		$result = wp_remote_retrieve_body($response);
		$json = json_decode($result, true);

		return $json && isset($json['success'])
			? $json
			: false;
	}
	
	/**
	 * Get a url
	 * @param string $path The path to be prefixed by protocol, subdomain and domain.
	 * @return string The full url
	 * @private
	 */
	private function _url($path) {
		$protocol = $this->_ssl
			? 'https://'
			: 'http://';
		$host = $this->_subdomain
			? $this->_subdomain.'.'.$this->_domain
			: $this->_domain.'/io';

		return $protocol . $host . $path;
	}
}
