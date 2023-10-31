<?php

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define('DISABLE_WP_CRON', true);
define('AUTOMATIC_UPDATER_DISABLED', true);
add_filter('pre_site_transient_update_core','__return_null');


