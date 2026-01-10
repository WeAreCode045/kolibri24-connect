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


