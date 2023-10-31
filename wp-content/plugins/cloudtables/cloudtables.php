<?php

/**
 * Plugin Name: CloudTables
 * Plugin URI: cloudtables.com
 * Description: WordPress integration for CloudTables, to embed tables and forms into your WordPress site.
 * Requires PHP: 5.4
 * Author: SpryMedia Ltd
 * Version: 1.3.0
 * License: GPLv2 or later
 */

require('Api.php');

class CloudTables {
	public static function activate () {}
	public static function deactivate () {}
	public static function uninstall () {}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_cloudtables_options;


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */
	public function __construct() {
		add_shortcode('cloudtable', function($atts, $content, $sc) {
			return $this->shortcode($atts, $content, $sc);
		});
		add_action( 'init', [$this, 'register_block'] );
		add_action( 'enqueue_block_editor_assets', [$this, 'editor_variables'] );

		// Register the Ajax handler, for Ajax access requests
		add_action( 'wp_ajax_cloudtables_access', [$this, 'ajax_access'] );
		add_action( 'wp_ajax_nopriv_cloudtables_access', [$this, 'ajax_access'] );

		if ( is_admin() ) {
			add_action( 'admin_menu', [$this, 'add_plugin_page'] );
			add_action( 'admin_init', [$this, 'admin_page_init'] );
			add_filter(
				'plugin_action_links_' . plugin_basename( __FILE__ ),
				[ &$this, 'plugin_manage_link' ],
				10,
				4
			);
		}

		$this->_cloudtables_options = get_option( 'cloudtables_option_name' );		
	}

	/**
	 * Show a link on the plugins page to the settings - based on how Akismet does it
	 */
	function plugin_manage_link( $actions, $plugin_file, $plugin_data, $context ) {
		$args = array( 'page' => 'cloudtables' );
		$url = add_query_arg( $args, class_exists( 'Jetpack' )
			? admin_url( 'admin.php' )
			: admin_url( 'options-general.php' )
		);

		return array_merge(
			[
				'configure' => '<a href="'.$url.'">Settings</a>'
			],
			$actions
		);
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Add the plugin page to admin menu
	 */
	public function add_plugin_page() {
		add_options_page(
			'CloudTables', // page_title
			'CloudTables', // menu_title
			'manage_options', // capability
			'cloudtables', // menu_slug
			[$this, 'admin_page'] // function
		);
	}

	/**
	 * Admin page HTML
	 */
	public function admin_page() {
		$opts = $this->_cloudtables_options;

		?>
		<div class="wrap">
			<h2>CloudTables</h2>
			<p>CloudTables is a table and form builder which can be easily embedded into your WordPress pages using short tags. To allow access to the data via short tags, please enter the CloudTables API key you wish to use to read and write data - this can be found in the "Security -> API Keys" section of your CloudTables application.</p>
			<p>To display a CloudTable in your pages or posts, either use the "CloudTables" option in the block Editor, or if you prefer short codes, use <code>[cloudtable id="..."]</code>, where the <code>id</code> attribute is the ID of the table you wish to show. You may optionally also specify a <code>key</code> option which will be used instead of the API keys specified below.</p>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'cloudtables_option_group' );
					do_settings_sections( 'cloudtables-admin' );
					submit_button();
				?>
			</form>
			<script type="text/javascript">
				function ctSelectsDisplay() {
					var type = jQuery('#type').val();
					var ssl = jQuery('#ssl').val();
					
					if (type === 'hosted') {
						jQuery('#subdomain').closest('tr').css('display', 'table-row');
						jQuery('#host').closest('tr').css('display', 'none');
						jQuery('#port').closest('tr').css('display', 'none');
						jQuery('#ssl').closest('tr').css('display', 'none');
					}
					else {
						jQuery('#subdomain').closest('tr').css('display', 'none');
						jQuery('#host').closest('tr').css('display', 'table-row');
						jQuery('#port').closest('tr').css('display', 'table-row');
						jQuery('#ssl').closest('tr').css('display', 'table-row');
					}

					if (ssl === 'enabled') {
						jQuery('#self_signed').closest('tr').css('display', 'table-row');
					}
					else {
						jQuery('#self_signed').closest('tr').css('display', 'none');
					}
				}

				jQuery(document).ready(function(){
					ctSelectsDisplay();

					jQuery('select.ct-select').on('change', function () {
						ctSelectsDisplay();
					});
				});
			</script>
			<style>
				div.ct-help {
					max-width: 350px;
					font-size: 0.8em;
				}
			</style>
			<?php
				$this->_display_datasets();
			?>
		</div>
	<?php }

	/**
	 * Admin page parameters
	 */
	public function admin_page_init() {
		register_setting(
			'cloudtables_option_group', // option_group
			'cloudtables_option_name', // option_name
			array( $this, function ($input) {
				$sanitary_values = array();
				$names = array(
					'apikey',
					'apikey_editor',
					'type',
					'subdomain',
					'host',
					'port',
					'ssl',
					'self_signed'
				);

				for ($i=0 ; $i<count($names) ; $i++) {
					if ( isset( $input[$names[$i]] ) ) {
						$sanitary_values[$names[$i]] = sanitize_text_field( $input[$names[$i]] );
					}
				}

				return $sanitary_values;
			} )
		);

		add_settings_section(
			'cloudtables_setting_section', // id
			'Settings', // title
			function () {},
			'cloudtables-admin' // page
		);

		add_settings_field(
			'type',
			'Hosting type',
			function() {
				$this->input_select('type', [
					['label' => 'Hosted (cloudtables.com)', 'value' => 'hosted'],
					['label' => 'Self-hosted', 'value' => 'self-hosted']
				], 'hosted');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		// Shown for hosted service
		add_settings_field(
			'subdomain',
			'Sub-domain',
			function() {
				$this->input_field('subdomain', 'The sub-domain name of your CloudTables application (i.e. the part before the <code>.cloudtables.com</code>.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		// Shown for self-hosted service
		add_settings_field(
			'host',
			'Host',
			function() {
				$this->input_field('host', 'The address for the server that hosts your self-hosted CloudTables install. It might be an IP address or a network name depending on your setup.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		add_settings_field(
			'port',
			'Port',
			function() {
				$this->input_field('port', 'Port for your self-hosted install. If left empty, a default of 80 will be used.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		add_settings_field(
			'ssl',
			'SSL',
			function() {
				$this->input_select('ssl', [
					['label' => 'Enabled', 'value' => 'enabled'],
					['label' => 'Disabled', 'value' => 'disabled']
				], 'disabled', 'When SSL is enabled, a secure connection will be used to your self-hosted CloudTables install.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		add_settings_field(
			'self_signed',
			'Certificate signing',
			function() {
				$this->input_select('self_signed', [
					['label' => 'Central certificate authority', 'value' => 'central'],
					['label' => 'Self signed', 'value' => 'self']
				], 'central', 'With SSL enabled, you can improve security by using a certificate from a signing authority that browsers recognise. This isn\'t always possible though, particularly during development. This option allows you to use a self-signed certificate.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		// Shown for both
		add_settings_field(
			'apikey_editor',
			'Editor API Key',
			function() {
				$this->input_field('apikey_editor', 'This API key will be used for users who have editing access to your site. Typically you should set this using a read / write access key from CloudTables.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		add_settings_field(
			'apikey',
			'Visitor API Key',
			function() {
				$this->input_field('apikey', 'The API key that will be used for non-editor users of your site (i.e. visitors). This will typically be a readonly key.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);

		add_settings_field(
			'access_type',
			'Access request method',
			function() {
				$this->input_select('access_type', [
					['label' => 'Auto', 'value' => 'auto'],
					['label' => 'Server load', 'value' => 'server-load'],
					['label' => 'Ajax', 'value' => 'ajax']
				], 'auto', 'This option determines when the access request to CloudTables is made. Typically the "Server load" option is slightly faster, but cache plug-ins cache as W3 Total Cache, LiteSpeed Cache, and WP-Optimize can result in page errors. Using "Auto" we will attempt to select the correct option for you, but override if you are using a caching plug-in and CloudTables together.');
			},
			'cloudtables-admin',
			'cloudtables_setting_section'
		);
	}

	public function ajax_access () {
		$this->_session();

		$prop = 'ct-embed-' . $_POST['uniq'];

		if (! isset($_SESSION[$prop])) {
			wp_send_json_error([
				'error' => 'Unknown request'
			]);
		}

		$props = json_decode($_SESSION[$prop], true);
		$api = $this->_api_inst($props['key']);

		if (isset($props['conditions'])) {
			// Unescape the HTML entities
			$api->conditions(str_replace('&quot;', '"', $props['conditions']));
		}

		$script = $api->scriptTag($props['datasetId']);

		unset($_SESSION[$prop]);
		wp_send_json_success([
			'src' => $script['url'],
			'insert' => $script['unique'],
			'token' => $script['token']
		]);
	}


	/**
	 * Get the datasets that are available from CloudTables
	 */
	public function datasets () {
		$api = $this->_api_inst();

		return $api
			? $api->datasets()
			: [];
	}

	/**
	 * Setup the variables that will be used by the Javascritp block editor
	 */
	public function editor_variables() {
		$ct = new CloudTables();

		wp_localize_script(
			'cloudtables_block',
			'cloudtables_data',
			[
				'datasets' => $ct->datasets(),
				'img_path' => plugins_url('', __FILE__ )
			]
		);
	}

	/**
	 * Select input type
	 */
	public function input_select($name, $values, $default, $info='') {
		printf('<select class="regular-text ct-select" name="cloudtables_option_name[%s]" id="%s" style="margin-bottom: 0.5em;">', $name, $name);

		$opts = $this->_cloudtables_options;

		for ($i=0 ; $i < count($values) ; $i++) {
			printf(
				'<option value="%s" %s>%s</option>',
				$values[$i]['value'],
				(
					(isset( $opts[$name] ) && $opts[$name] === $values[$i]['value'] ) ||
					(! isset( $opts[$name] ) && $default === $values[$i]['value'])
				)
					? 'selected="selected"'
					: '',
				$values[$i]['label']
			);
		}

		echo '</select>';

		if ($info) {
			echo '<div class="ct-help">'.$info.'</div>';
		}
	}

	public function input_field($name, $info='') {
		printf(
			'<input class="regular-text" type="text" name="cloudtables_option_name[%s]" id="%s" value="%s" style="margin-bottom: 0.5em"><div class="ct-help">%s</div>',
			$name,
			$name,
			isset( $this->_cloudtables_options[$name] )
				? esc_attr( $this->_cloudtables_options[$name])
				: '',
			$info
		);
	}

	/**
	 * Register the CloudTables block for gutenberg
	 */
	public function register_block() {
		$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');
	
		wp_register_script(
			'cloudtables_block',
			plugins_url('build/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version']
		);
	
		wp_enqueue_script('cloudtables_block');
	
		register_block_type(
			'cloudtables/table-block',
			[]
		);
	}

	/**
	 * Convert a shortcode to a CloudTables embed tag
	 */
	public function shortcode($attrs = []) {
		$options = $this->_cloudtables_options;

		$accessType = isset($options['access_type'])
			? $options['access_type']
			: 'auto';

		if ($accessType === 'auto') {
			$accessType = $this->_isCachingPlugin()
				? 'ajax'
				: 'server-load';
		}

		$id = isset($attrs['id'])
			? $attrs['id']
			: '-';
		
		$key = isset($attrs['key'])
			? $attrs['key']
			: null;

		if (! $id) {
			return 'CloudTables: Error - No data set ID given.';
		}

		// For Ajax access types we insert a div that will then trigger an Ajax request to get the
		// access token and url. This is to defeat cache plug-ins for wordpress.
		if ($accessType === 'ajax') {
			// We store the potentially sensitive information of the data set id, key and
			// load conditions in a session variable, to avoid a round trip of that
			// information to the client-side. A unique session var is used to store it.
			$this->_session();

			$rand = rand();
			$_SESSION['ct-embed-' . $rand] = json_encode([
				'datasetId' => $id,
				'key' => $key,
				'conditions' => isset($attrs['conditions'])
					? $attrs['conditions']
					: null
			]);

			// This script will load in the CloudTables loader after requesting the
			// access url and token
			wp_enqueue_script('cloudtables-ajax', plugins_url('ajax.js', __FILE__), ['wp-util']);

			return '<div data-ct-ajax="'.$rand.'"></div>';
		}

		$api = $this->_api_inst($key);

		if (isset($attrs['conditions'])) {
			// Unescape the HTML entities
			$api->conditions(str_replace('&quot;', '"', $attrs['conditions']));
		}

		wp_register_style(
			'cloudtables',
			plugins_url('cloudtables.css', __FILE__ )
		);
	
		wp_enqueue_style('cloudtables');

		if (! $api) {
			return '<p class="cloudtables-error">Sorry - unable to show CloudTable (error code: NOAPI). Please contact your system administrator to resolve this issue.</p>';	
		}

		$script = $api->scriptTag($id);

		wp_register_script(
			'cloudtables-'. $script['unique'],
			$script['url']
		);

		wp_enqueue_script('cloudtables-'. $script['unique']);

		return '<div data-ct-insert="'.htmlspecialchars($script['unique']).'" data-token="'.htmlspecialchars($script['token']).'"></div>';
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Get a CloudTables API instance
	 */
	private function _api_inst($apikeyAttr = null) {
		$options = get_option( 'cloudtables_option_name' );
		$subdomain = isset($options['subdomain'])
			? $options['subdomain']
			: '';
		$type = isset($options['type'])
			? $options['type']
			: 'hosted';
		$host = isset($options['host'])
			? $options['host']
			: '';
		$port = isset($options['port'])
			? $options['port']
			: '';
		$ssl = isset($options['ssl'])
			? $options['ssl'] === 'enabled'
			: true;
		$self_signed = isset($options['self_signed'])
			? $options['self_signed'] === 'self'
			: false;
		$apikey = isset($options['apikey'])
			? $options['apikey']
			: '';
		$apikey_editor = isset($options['apikey_editor'])
			? $options['apikey_editor']
			: '';

		if (! $type) {
			return false;
		}

		if (! $apikeyAttr) {
			$active_key = current_user_can( 'edit_posts' )
				? $apikey_editor
				: $apikey;
		}
		else {
			$active_key = $apikeyAttr;
		}

		if ($type === 'hosted' && $subdomain && $active_key) {
			return new \Cloudtables\Api($subdomain, $active_key);
		}
		else if ($host && $active_key) {
			if ($port) {
				$host .= ':'.$port;
			}

			return new \Cloudtables\Api('', $active_key, array(
				'domain' => $host,
				'ssl' => $ssl,
				'selfSigned' => $self_signed
			));
		}

		return false;
	}

	/**
	 * HTML for settings page to show the datasets available
	 */
	private function _display_datasets() {
		echo '<h2>Connection status</h2>';

		// Need to use the api rather than our own datasets method to determine if parameters have been
		// provided
		$api = $this->_api_inst();

		if ($api) {
			$datasets = $api->datasets();

			if ($datasets) {
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );

				?>
					<p style="color: green">Good.</p>
					<h3>Available datasets</h3>
					<p>The following datasets are available based on the editor API Key above.</p>
				
					<table class="widefat">
						<thead>
							<tr>
								<th>Name</th>
								<th>ID</th>
								<th>Number of rows</th>
								<th>Last updated</th>
							</tr>
						</thead>
						<tbody>
							<?php
								for ($i=0 ; $i<count($datasets) ; $i++) {
									$d = strtotime($datasets[$i]['lastData']);
									$formatted = $d
										? date($date_format, $d) .' - '. date($time_format, $d)
										: 'No data yet';

									echo '<tr>';
									echo '<td>'.htmlspecialchars($datasets[$i]['name']).'</td>';
									echo '<td>'.htmlspecialchars($datasets[$i]['id']).'</td>';
									echo '<td>'.htmlspecialchars($datasets[$i]['rowCount']).'</td>';
									echo '<td>'.htmlspecialchars($formatted).'</td>';
									echo '</tr>';
								}
							?>
						</tbody>
					</table>
				<?php
			}
			else {
				echo '<p style="color: red">Failed - please check the settings above including the host name and access keys.</p>';
			}
		}
		else {
			echo '<p style="color: orange">Unavailable - no access details provided.</p>';
		}
	}

	/**
	 * Determine if any of the common caching plug-ins are active on this site
	 */
	private function _isCachingPlugin () {
		$plugins = apply_filters('active_plugins', get_option('active_plugins'));

		if (
			in_array( 'autoptimize/autoptimize.php', $plugins ) ||
			in_array( 'breeze/breeze.php', $plugins ) ||
			in_array( 'cache-enabler/cache-enabler.php', $plugins ) ||
			in_array( 'hummingbird-performance/wp-hummingbird.php', $plugins ) ||
			in_array( 'litespeed-cache/litespeed-cache.php', $plugins ) ||
			in_array( 'sg-cachepress/sg-cachepress.php', $plugins ) ||
			in_array( 'tenweb-speed-optimizer/tenweb_speed_optimizer.php', $plugins ) ||
			in_array( 'w3-total-cache/w3-total-cache.php', $plugins ) ||
			in_array( 'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php', $plugins ) ||
			in_array( 'wp-fastest-cache/wpFastestCache.php', $plugins ) ||
			in_array( 'wp-optimize/wp-optimize.php', $plugins ) ||
			in_array( 'wp-speed-of-light/wp-speed-of-light.php', $plugins ) ||
			in_array( 'wp-super-cache/wp-cache.php', $plugins )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Start a session if we don't already have one
	 */
	private function _session() {
		if (!session_id()) {
			session_start();
		}
	}
}

$ct = new CloudTables();

register_activation_hook( __FILE__, 'CloudTables::activate' );
register_deactivation_hook( __FILE__, 'CloudTables::deactivate' );
register_uninstall_hook( __FILE__, 'CloudTables::uninstall' );
