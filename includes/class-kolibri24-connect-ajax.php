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
			
			// AJAX handler for getting archived directories.
			add_action( 'wp_ajax_kolibri24_get_archives', array( $this, 'get_archives' ) );
			
			// AJAX handler for viewing archive preview.
			add_action( 'wp_ajax_kolibri24_view_archive', array( $this, 'view_archive' ) );
			
			// AJAX handler for deleting an archive.
			add_action( 'wp_ajax_kolibri24_delete_archive', array( $this, 'delete_archive' ) );
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
		
		/**
		 * AJAX handler for getting archived directories
		 *
		 * @since 1.0.0
		 */
		public function get_archives() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security verification failed.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions.', 'kolibri24-connect' ),
					)
				);
			}
			
			$upload_dir = wp_upload_dir();
			$archive_dir = $upload_dir['basedir'] . '/kolibri/archived';
			
			if ( ! file_exists( $archive_dir ) ) {
				wp_send_json_success(
					array(
						'archives' => array(),
						'message' => __( 'No archives found.', 'kolibri24-connect' ),
					)
				);
			}
			
			$archives = array();
			$directories = glob( $archive_dir . '/*', GLOB_ONLYDIR );
			
			if ( ! empty( $directories ) ) {
				foreach ( $directories as $dir ) {
					$dir_name = basename( $dir );
					$xml_files = glob( $dir . '/*.xml' );
					
					$archives[] = array(
						'name' => $dir_name,
						'path' => $dir,
						'count' => count( $xml_files ),
						'date' => date( 'Y-m-d H:i:s', filemtime( $dir ) ),
					);
				}
				
				// Sort by date, newest first.
				usort( $archives, function( $a, $b ) {
					return strcmp( $b['date'], $a['date'] );
				});
			}
			
			wp_send_json_success(
				array(
					'archives' => $archives,
				)
			);
		}
		
		/**
		 * AJAX handler for viewing archive preview
		 *
		 * @since 1.0.0
		 */
		public function view_archive() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security verification failed.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions.', 'kolibri24-connect' ),
					)
				);
			}
			
			if ( ! isset( $_POST['archive_path'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Archive path is required.', 'kolibri24-connect' ),
					)
				);
			}
			
			$archive_path = sanitize_text_field( wp_unslash( $_POST['archive_path'] ) );
			
			// Validate the path is within uploads directory.
			$upload_dir = wp_upload_dir();
			$base_archive = $upload_dir['basedir'] . '/kolibri/archived';
			
			if ( strpos( realpath( $archive_path ), realpath( $base_archive ) ) !== 0 ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid archive path.', 'kolibri24-connect' ),
					)
				);
			}
			
			if ( ! file_exists( $archive_path ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Archive not found.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Find XML files in the archive directory.
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();
			
			// Get all XML files from the archive directory.
			$xml_files = $xml_processor->get_xml_files_from_directory( $archive_path );
			
			if ( empty( $xml_files ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'No XML files found in this archive.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Get property previews from XML files.
			$preview_result = $xml_processor->extract_property_previews( $xml_files );
			
			if ( ! isset( $preview_result['success'] ) || ! $preview_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $preview_result['message'] ?? __( 'Failed to extract properties from archive.', 'kolibri24-connect' ),
					)
				);
			}
			
			wp_send_json_success(
				array(
					'properties' => $preview_result['properties'],
					'archive_name' => basename( $archive_path ),
				)
			);
		}
		
		/**
		 * AJAX handler for deleting an archive
		 *
		 * @since 1.0.0
		 */
		public function delete_archive() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security verification failed.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions.', 'kolibri24-connect' ),
					)
				);
			}
			
			if ( ! isset( $_POST['archive_path'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Archive path is required.', 'kolibri24-connect' ),
					)
				);
			}
			
			$archive_path = sanitize_text_field( wp_unslash( $_POST['archive_path'] ) );
			
			// Validate the path is within uploads directory.
			$upload_dir = wp_upload_dir();
			$base_archive = $upload_dir['basedir'] . '/kolibri/archived';
			
			if ( strpos( realpath( $archive_path ), realpath( $base_archive ) ) !== 0 ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid archive path.', 'kolibri24-connect' ),
					)
				);
			}
			
			if ( ! file_exists( $archive_path ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Archive not found.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Initialize WordPress Filesystem.
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
			
			// Delete the directory recursively.
			$deleted = $wp_filesystem->delete( $archive_path, true );
			
			if ( ! $deleted ) {
				wp_send_json_error(
					array(
						'message' => __( 'Failed to delete archive.', 'kolibri24-connect' ),
					)
				);
			}
			
			wp_send_json_success(
				array(
					'message' => __( 'Archive deleted successfully.', 'kolibri24-connect' ),
				)
			);
		}
	}
}

new Kolibri24_Connect_Ajax();
