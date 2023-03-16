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
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Codeplus_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'AMrK;$8D^uSeOR4hQ{_>o}KP_nM6`gxkj|5+q0bUAd=V3BD$$J3I|^)G!N_xDxE9' );
define( 'SECURE_AUTH_KEY',  '7hA09AVNTYF,sVFE,aU3ptY!X!g?B~^gUXMOc|wQP(NQ-,K-]auKaEWCaA*IE&GT' );
define( 'LOGGED_IN_KEY',    'Fyy-{z)+$f5Am^:$hTXG0M(-)RaW$rjq/ezS?b5V6O[pBFtk9d|1a$0yYQWl6qlx' );
define( 'NONCE_KEY',        'Hz0WTU+,]80p?Gw;04XORJ3zA,snO+RSv:}oxbf4`8|7-asd(.l/eva@FXhilnnh' );
define( 'AUTH_SALT',        'e{*%8wnOJBW,f(AIxqnHaCcW>SE>J$W4P~g-52.Uo`6Hf^Q2ISfRduc+%:9u@GQb' );
define( 'SECURE_AUTH_SALT', 'eOBQ pN%rM}k7:X+UvL9dLTZ]KI!7RiW1,|z^KDKE?k%c`N85&y8=z6*+`o!LDlg' );
define( 'LOGGED_IN_SALT',   'mcGak&+A!uc%V%8d7KJ[g}<A0Y$HLp/;,Lt?@lzF`F#+{t|-DzofAaXNo|#JS~ht' );
define( 'NONCE_SALT',       'F83P,6gN$e>O-*lpY :[-5cBS1eO{0VAPd.y4RV&8O)r+]$7-<}3|I4+<0K.zZRp' );

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
