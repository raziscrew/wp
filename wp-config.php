<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME','wordpress');

/** MySQL database username */
define( 'DB_USER','wordpress_user');

/** MySQL database password */
define( 'DB_PASSWORD','@Razis026239');

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY','ornyXkARveLblvg8T0ExjSk1W41Z/3QxSa2KufeOSIfdNgoz1JpJ84LM5Eqtwp+E');
define('SECURE_AUTH_KEY','YTIoGwFF+0t/2/jQnwyKl6MmbgjZS3/0eWT2aNC4g1ZcxUOGJL8vgF9OJNt2gbti');
define('LOGGED_IN_KEY','pNVPE7ayK+FujVT7FMwXyyE6WBxWbar6a2PTuf3H7p/At4WFnKDDdzQxiq9dps3N');
define('NONCE_KEY','0R3TPyPS5Jnfq0JnKvPU9+GBSsRU1pUvqNplgCeVZStAWcyPEdNqOHQIMevUX79r');
define('AUTH_SALT','ET1tzPNDBudxGmBiaBg9f9dPxRnKgT0uCVAPG7DEA4TyPRNODlGRVNqJcg4DCR9V');
define('SECURE_AUTH_SALT','17XleU3kRJ2hs6b2PxwlJUoLsQ3/ZEISexFoGPxOXjECpOzsVqZhe91DLKEu+76O');
define('LOGGED_IN_SALT','DZ0VkTGfaEn8kxbDKDsO/4cKVnFl9X0MTbXnqwEy2Uio9CNRK4RajmhAqE8Ck06J');
define('NONCE_SALT','j3kfDjCtcppW6FOQmObaGgC41PZil6cSLOR4DD1xQl65FMreVN+qzLQ+Ef8bTHPM');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
require_once __DIR__ . '/syno-wp-config-custom.php';
