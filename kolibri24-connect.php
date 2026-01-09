<?php
/**
 * Plugin Name: kolibri24-connect
 * Plugin URI: https://code045.nl/
 * Description:kolibri24-connect Plugin.
 * Author: Code045
 * Author URI: https://code045.nl/
* Version: 1.5.11
 * Requires at least: 6.0
 * Tested up to: 6.7
 *
 * Text Domain: kolibri24-connect
 * Domain Path: /languages/
 *
 * @package Code045/kolibri24-connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define KOLIBRI24_CONNECT_PLUGIN_FILE.
if ( ! defined( 'KOLIBRI24_CONNECT_PLUGIN_FILE' ) ) {
	define( 'KOLIBRI24_CONNECT_PLUGIN_FILE', __FILE__ );
}

// Define KOLIBRI24_CONNECT_ABSPATH.
if ( ! defined( 'KOLIBRI24_CONNECT_ABSPATH' ) ) {
	define( 'KOLIBRI24_CONNECT_ABSPATH', dirname( __FILE__ ) . '/' );
}

// Include the main class.
if ( ! class_exists( 'Kolibri24_Connect' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-kolibri24-connect.php';
}

if ( ! function_exists( 'kolibri24_connect' ) ) {
	/**
	 * Returns the main instance of Kolibri24_Connect.
	 *
	 * @since  1.0.0
	 * @return Kolibri24_Connect
	 */
	function kolibri24_connect() {
		return Kolibri24_Connect::instance();
	}
}

kolibri24_connect();
