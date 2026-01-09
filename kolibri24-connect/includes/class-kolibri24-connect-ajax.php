<?php
/**
 * The ajax functionality of the plugin.
 *
 * @package StandaloneTech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Kolibri24_Connect_Ajax' ) ) {
	/**
	 * Plugin Kolibri24_Connect_Ajax Class.
	 */
	class Kolibri24_Connect_Ajax {
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// AJAX handler for downloading and extracting properties.
			add_action( 'wp_ajax_kolibri24_download_extract', array( $this, 'download_and_extract' ) );
			
			// AJAX handler for merging selected properties.
			add_action( 'wp_ajax_kolibri24_merge_properties', array( $this, 'merge_selected_properties' ) );
		}

		/**
		 * AJAX handler for downloading and extracting ZIP
		 *
		 * @since 1.0.0
		 */
		public function download_and_extract() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security verification failed. Please refresh the page and try again.', 'kolibri24-connect' ),
					)
				);
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions to perform this action.', 'kolibri24-connect' ),
					)
				);
			}

			// Increase time limit for processing.
			set_time_limit( 600 ); // 10 minutes.

			// Increase memory limit if needed.
			if ( function_exists( 'wp_raise_memory_limit' ) ) {
				wp_raise_memory_limit( 'admin' );
			}

			// STEP 1: Download ZIP file.
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-zip-handler.php';
			$zip_handler     = new Kolibri24_Connect_Zip_Handler();
			$download_result = $zip_handler->download_zip();

			if ( ! $download_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $download_result['message'],
						'step'    => 'download',
					)
				);
			}

			// STEP 2: Extract ZIP file.
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();

			$extract_result = $xml_processor->extract_zip(
				$download_result['file_path'],
				$download_result['dated_dir']
			);

			if ( ! $extract_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $extract_result['message'],
						'step'    => 'extract',
					)
				);
			}

			// STEP 3: Extract property previews.
			$preview_result = $xml_processor->extract_property_previews( $extract_result['xml_files'] );

			if ( ! $preview_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $preview_result['message'],
						'step'    => 'preview',
					)
				);
			}

			// Success - return preview data.
			wp_send_json_success(
				array(
					'message'    => __( 'Properties extracted successfully. Please select properties to merge.', 'kolibri24-connect' ),
					'properties' => $preview_result['properties'],
					'total'      => count( $preview_result['properties'] ),
				)
			);
		}

		/**
		 * AJAX handler for merging selected properties
		 *
		 * @since 1.0.0
		 */
		public function merge_selected_properties() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security verification failed. Please refresh the page and try again.', 'kolibri24-connect' ),
					)
				);
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions to perform this action.', 'kolibri24-connect' ),
					)
				);
			}

			// Get selected file paths.
			if ( ! isset( $_POST['selected_files'] ) || empty( $_POST['selected_files'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'No properties selected. Please select at least one property to merge.', 'kolibri24-connect' ),
					)
				);
			}

			// Sanitize file paths.
			$selected_files = array_map( 'sanitize_text_field', wp_unslash( $_POST['selected_files'] ) );

			// Validate file paths (security check).
			$upload_dir  = wp_upload_dir();
			$base_path   = trailingslashit( $upload_dir['basedir'] ) . 'kolibri/archived/';
			$valid_files = array();

			foreach ( $selected_files as $file_path ) {
				// Ensure file is within our kolibri directory.
				if ( 0 === strpos( $file_path, $base_path ) && file_exists( $file_path ) ) {
					$valid_files[] = $file_path;
				}
			}

			if ( empty( $valid_files ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'No valid property files found. Please try downloading again.', 'kolibri24-connect' ),
					)
				);
			}

			// Merge the selected properties.
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();
			$output_path   = $xml_processor->get_output_file_path();

			$merge_result = $xml_processor->merge_selected_properties( $valid_files, $output_path );

			if ( ! $merge_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $merge_result['message'],
						'step'    => 'merge',
					)
				);
			}

			// Success response.
			wp_send_json_success(
				array(
					'message'     => __( 'Selected properties have been successfully merged!', 'kolibri24-connect' ),
					'output_file' => $output_path,
					'processed'   => $merge_result['processed_count'],
				)
			);
		}
	}
}

new Kolibri24_Connect_Ajax();
