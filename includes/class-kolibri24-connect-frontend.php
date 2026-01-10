<?php
/**
 * The frontend-specific functionality of the plugin.
 *
 * @package StandaloneTech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Kolibri24_Connect_Frontend' ) ) {
	/**
	 * Plugin Kolibri24_Connect_Frontend Class.
	 */
	class Kolibri24_Connect_Frontend {
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Register the stylesheets for the frontend area.s
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {
		wp_enqueue_style( 'kolibri24-connect-frontend', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/css/frontend.css', array(), '1.0.0', 'all' );
	}

	/**
	 * Register the JavaScript for the frontend area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'kolibri24-connect-frontend', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/js/frontend.js', array( 'jquery' ), '1.0.0', false );
	}
}
}

new Kolibri24_Connect_Frontend();

/**
 * Return the relative path to the current properties.xml in the selected archive.
 * Example: wp-content/uploads/kolibri/archived/09-01-2026_00-01-00/properties.xml
 *
 * @return string Relative path from site root, or empty string on failure.
 */
if ( ! function_exists( 'myfunction' ) ) {
	function myfunction() {
		$selected = get_option( 'kolibri24_selected_archive', array() );
		$properties_file = '';

		if ( is_array( $selected ) && ! empty( $selected['properties_file'] ) ) {
			$properties_file = $selected['properties_file'];
		} else {
			$props_info = get_option( 'kolibri24_properties_info', array() );
			$properties_file = isset( $props_info['output_file'] ) ? $props_info['output_file'] : '';
		}

		if ( empty( $properties_file ) ) {
			return '';
		}

		$upload_dir = wp_upload_dir();
		$basedir    = trailingslashit( $upload_dir['basedir'] );
		$relative   = '';

		// If the properties file sits under uploads, strip the basedir to get a relative path.
		if ( 0 === strpos( $properties_file, $basedir ) ) {
			$relative = str_replace( $basedir, '', $properties_file );
			$relative = 'wp-content/uploads/' . ltrim( $relative, '/' );
		} else {
			// Fallback: return basename under kolibri if it cannot be mapped cleanly.
			$relative = ltrim( str_replace( ABSPATH, '', $properties_file ), '/' );
		}

		return $relative;
	}
}

/**
 * Return the full URL to properties.xml for WP All Import dynamic import usage.
 * Example: https://example.com/wp-content/uploads/kolibri/archived/09-01-2026_00-01-00/properties.xml
 *
 * @return string Full URL or empty string on failure.
 */
if ( ! function_exists( 'get_dynamic_import_url' ) ) {
	function get_dynamic_import_url() {
		$path = myfunction();
		if ( empty( $path ) ) {
			return '';
		}

		// Ensure single slash between home_url and the relative path.
		return trailingslashit( home_url() ) . ltrim( $path, '/' );
	}
}
