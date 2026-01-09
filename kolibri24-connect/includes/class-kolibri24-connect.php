<?php
/**
 * Plugin main class file.
 *
 * @package StandaloneTech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main plugin class
 */
final class Kolibri24_Connect {
	/**
	 * The single instance of the class.
	 *
	 * @var Kolibri24_Connect
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return class object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->includes();
		$this->load_plugin_textdomain();
	}

	/**
	 * Check request
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Load plugin files
	 */
	public function includes() {
		if ( $this->is_request( 'admin' ) ) {
			include_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-admin.php';
		}

		if ( $this->is_request( 'frontend' ) ) {
			include_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-frontend.php';
		}

		if ( $this->is_request( 'ajax' ) ) {
			include_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-ajax.php';
		}
	}

	/**
	 * Text Domain loader
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'kolibri24-connect' );

		unload_textdomain( 'kolibri24-connect' );
		load_textdomain( 'kolibri24-connect', WP_LANG_DIR . '/kolibri24-connect/kolibri24-connect-' . $locale . '.mo' );
		load_plugin_textdomain( 'kolibri24-connect', false, plugin_basename( dirname( KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Load template
	 *
	 * @param string $template_name Tempate Name.
	 * @param array  $args args.
	 * @param string $template_path Template Path.
	 * @param string $default_path Default path.
	 */
	public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( $args && is_array( $args ) ) {
			extract( $args ); // phpcs:ignore
		}
		$located = $this->locate_template( $template_name, $template_path, $default_path );
		include $located;
	}

	/**
	 * Locate template file
	 *
	 * @param string $template_name template_name.
	 * @param string $template_path template_path.
	 * @param string $default_path default_path.
	 * @return string
	 */
	public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		$default_path = apply_filters( 'kolibri24-connect_template_path', $default_path );
		if ( ! $template_path ) {
			$template_path = 'kolibri24-connect';
		}
		if ( ! $default_path ) {
			$default_path = KOLIBRI24_CONNECT_ABSPATH . 'templates/';
		}
		// Look within passed path within the theme - this is priority.
		$template = locate_template( array( trailingslashit( $template_path ) . $template_name, $template_name ) );
		// Add support of third perty plugin.
		$template = apply_filters( 'kolibri24-connect_locate_template', $template, $template_name, $template_path, $default_path );
		// Get default template.
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}
		return $template;
	}

}
