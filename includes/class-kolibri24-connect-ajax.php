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
			// AJAX handler for WP All Import trigger/process URLs.
			add_action( 'wp_ajax_kolibri24_run_all_import_urls', array( $this, 'run_all_import_urls' ) );
		
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
		
		// AJAX handler for saving settings.
		add_action( 'wp_ajax_kolibri24_save_settings', array( $this, 'save_settings' ) );
		
		
		// AJAX handler for downloading archive media.
		add_action( 'wp_ajax_kolibri24_download_archive_media', array( $this, 'download_archive_media' ) );
		
		// AJAX handler for getting selected records.
		add_action( 'wp_ajax_kolibri24_get_selected_records', array( $this, 'get_selected_records' ) );
		
		// AJAX handler for loading a previous archive.
		add_action( 'wp_ajax_kolibri24_load_previous_archive', array( $this, 'load_previous_archive' ) );
		
		// AJAX handler for getting list of previous archives.
		add_action( 'wp_ajax_kolibri24_get_previous_archives', array( $this, 'get_previous_archives' ) );
		
		// AJAX handler for saving import history.
		add_action( 'wp_ajax_kolibri24_save_import_history', array( $this, 'save_import_history' ) );
		
		// AJAX handler for getting import history.
		add_action( 'wp_ajax_kolibri24_get_import_history', array( $this, 'get_import_history' ) );
	}

	/**
	 * AJAX handler to trigger WP All Import via trigger and processing URLs.
	 *
	 * @since 1.2.0
	 */
	public function run_all_import_urls() {
		// Security: Nonce and capability check
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'kolibri24-connect' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'kolibri24-connect' ) ) );
		}

		$trigger_url    = get_option( 'kolibri24_trigger_url' );
		$processing_url = get_option( 'kolibri24_processing_url' );

		if ( empty( $trigger_url ) || empty( $processing_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Trigger or processing URL not configured.', 'kolibri24-connect' ) ) );
		}

		// Call trigger URL first
		$trigger_response = wp_remote_get( $trigger_url, array( 'timeout' => 60 ) );
		if ( is_wp_error( $trigger_response ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to call trigger URL.', 'kolibri24-connect' ), 'error' => $trigger_response->get_error_message() ) );
		}

		// Call processing URL once (the JS will repeat every 2 minutes until import is finished)
		$processing_response = wp_remote_get( $processing_url, array( 'timeout' => 60 ) );
		if ( is_wp_error( $processing_response ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to call processing URL.', 'kolibri24-connect' ), 'error' => $processing_response->get_error_message() ) );
		}

			$body = wp_remote_retrieve_body( $processing_response );
			// Optionally, parse $body for completion status if needed
			wp_send_json_success( array( 'message' => __( 'WP All Import triggered and processing started.', 'kolibri24-connect' ), 'processing_response' => $body ) );
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
	
			// Determine source and get ZIP file path.
			$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'kolibri24';
			
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-zip-handler.php';
			$zip_handler = new Kolibri24_Connect_Zip_Handler();
			
			// STEP 1: Download or prepare ZIP file based on source.
			if ( 'kolibri24' === $source ) {
				// Download from Kolibri24 API.
				$download_result = $zip_handler->download_zip();
			} elseif ( 'remote-url' === $source ) {
				// Download from remote URL.
				if ( ! isset( $_POST['remote_url'] ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'Remote URL is required.', 'kolibri24-connect' ),
							'step'    => 'download',
						)
					);
				}
				$remote_url      = esc_url( wp_unslash( $_POST['remote_url'] ) );
				$download_result = $zip_handler->download_from_url( $remote_url );
			} elseif ( 'upload' === $source ) {
				// Process uploaded file.
				$download_result = $zip_handler->handle_file_upload();
			} elseif ( 'previous-archive' === $source ) {
				// Load from previous archive - no download needed, so create a mock result.
				$download_result = array(
					'success'   => true,
					'file_path' => '',
					'dated_dir' => '',
				);
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid import source.', 'kolibri24-connect' ),
						'step'    => 'download',
					)
				);
			}
	
			if ( 'previous-archive' !== $source && ! $download_result['success'] ) {
				wp_send_json_error(
					array(
						'message' => $download_result['message'],
						'step'    => 'download',
					)
				);
			}
	
			// STEP 2: Extract ZIP file (skip if loading from previous archive).
			if ( 'previous-archive' === $source ) {
				// Validate and load the archive
				if ( ! isset( $_POST['archive_path'] ) || empty( $_POST['archive_path'] ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'No archive selected.', 'kolibri24-connect' ),
							'step'    => 'archive',
						)
					);
				}

				$archive_path = sanitize_text_field( wp_unslash( $_POST['archive_path'] ) );

				// Validate path (security check - must be in archived directory).
				$upload_dir  = wp_upload_dir();
				$archive_dir = $upload_dir['basedir'] . '/kolibri/archived/';

				if ( strpos( $archive_path, $archive_dir ) !== 0 || ! is_dir( $archive_path ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'Invalid archive path.', 'kolibri24-connect' ),
							'step'    => 'archive',
						)
					);
				}

				// Get XML files directly from archive
				$xml_files = glob( $archive_path . '/*.xml' );
				if ( empty( $xml_files ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'No XML files found in archive.', 'kolibri24-connect' ),
							'step'    => 'extract',
						)
					);
				}

				$extract_result = array(
					'success'   => true,
					'xml_files' => $xml_files,
				);
			} else {
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
			}
	
		// STEP 3: Merge ALL XML files into properties.xml immediately.
		if ( ! isset( $xml_processor ) ) {
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();
		}

		$output_path = $xml_processor->get_output_file_path();
		$merge_result = $xml_processor->merge_selected_properties( $extract_result['xml_files'], $output_path );

		if ( ! $merge_result['success'] ) {
			wp_send_json_error(
				array(
					'message' => $merge_result['message'],
					'step'    => 'merge',
				)
			);
		}

		// STEP 4: Extract property previews from the merged properties.xml file.
		$preview_result = $xml_processor->extract_property_previews_from_merged( $output_path );

		if ( ! $preview_result['success'] ) {
			wp_send_json_error(
				array(
					'message' => $preview_result['message'],
					'step'    => 'preview',
				)
			);
		}

		// STEP 5: Check for updates and field-level changes since last import.
		$preview_result['properties'] = $xml_processor->check_for_updates( $preview_result['properties'] );
		$preview_result['properties'] = $xml_processor->compare_with_last_import( $preview_result['properties'] );

		// Persist metadata about the merged properties.xml.
		$first_dir      = dirname( $extract_result['xml_files'][0] );
		$archive_name   = basename( $first_dir );
		$properties_info = array(
			'total_properties' => count( $preview_result['properties'] ),
			'created_at'       => current_time( 'timestamp' ),
			'archive_name'     => $archive_name,
			'archive_path'     => $first_dir,
			'output_file'      => $output_path,
		);
		update_option( 'kolibri24_properties_info', $properties_info, false );
		update_option( 'kolibri24_preview_data', $preview_result['properties'], false );

		// Success - return preview data with record positions.
		wp_send_json_success(
			array(
				'message'    => __( 'Properties merged successfully. Please select which records to import.', 'kolibri24-connect' ),
				'properties' => $preview_result['properties'],
				'total'      => count( $preview_result['properties'] ),
			)
		);
	}

	/**
	 * AJAX handler for saving selected record positions
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

	// Get selected record positions.
	if ( ! isset( $_POST['selected_records'] ) || empty( $_POST['selected_records'] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'No records selected. Please select at least one record to import.', 'kolibri24-connect' ),
			)
		);
	}

	// Sanitize and validate record positions (comma-separated integers).
	$selected_records = sanitize_text_field( wp_unslash( $_POST['selected_records'] ) );
	
	// Validate format: should be comma-separated integers.
	if ( ! preg_match( '/^[0-9,]+$/', $selected_records ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid record positions format.', 'kolibri24-connect' ),
			)
		);
	}

	// Save the selected record positions.
	update_option( 'kolibri24_selected_records', $selected_records, false );

	// Count selected records.
	$record_count = count( explode( ',', $selected_records ) );

	// Success response.
	wp_send_json_success(
		array(
			'message' => sprintf(
				__( '%d records have been marked for import!', 'kolibri24-connect' ),
				$record_count
			),
			'selected_records' => $selected_records,
			'record_count'     => $record_count,
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

		/**
		 * AJAX handler for saving settings
		 *
		 * @since 1.1.0
		 */
		public function save_settings() {
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
			
			// Get and validate the API URL.
			if ( ! isset( $_POST['kolibri24_api_url'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'API URL is required.', 'kolibri24-connect' ),
					)
				);
			}
			
			$api_url = esc_url( wp_unslash( $_POST['kolibri24_api_url'] ) );
			
			if ( empty( $api_url ) ) {
				wp_send_json_error(
					array(
					'message' => __( 'Please enter a valid API URL.', 'kolibri24-connect' ),
				)
			);
		}
		
		// Get and validate trigger/processing URLs.
		$trigger_url    = isset( $_POST['kolibri24_trigger_url'] ) ? esc_url( wp_unslash( $_POST['kolibri24_trigger_url'] ) ) : '';
		$processing_url = isset( $_POST['kolibri24_processing_url'] ) ? esc_url( wp_unslash( $_POST['kolibri24_processing_url'] ) ) : '';
		$import_id      = isset( $_POST['kolibri24_import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['kolibri24_import_id'] ) ) : '';

		if ( empty( $trigger_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Trigger URL is required.', 'kolibri24-connect' ) ) );
		}
		if ( empty( $processing_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Processing URL is required.', 'kolibri24-connect' ) ) );
		}
		if ( empty( $import_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Import ID is required.', 'kolibri24-connect' ) ) );
		}

		// Save the options.
		update_option( 'kolibri24_api_url', $api_url );
		update_option( 'kolibri24_trigger_url', $trigger_url );
		update_option( 'kolibri24_processing_url', $processing_url );
		update_option( 'kolibri24_import_id', $import_id );

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully.', 'kolibri24-connect' ),
			)
		);
	}


	/**
	 * AJAX handler for getting selected records
	 *
	 * @since 1.2.0
	 */
	public function get_selected_records() {
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

		// Get the selected record positions.
		$selected_records = get_option( 'kolibri24_selected_records', '' );

		if ( empty( $selected_records ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No records have been selected yet. Please complete Step 2 first.', 'kolibri24-connect' ),
				)
			);
		}

		// Parse and count records.
		$record_array = explode( ',', $selected_records );
		$record_count = count( $record_array );

		// Get properties info to locate merged file.
		$properties_info = get_option( 'kolibri24_properties_info', array() );
		$merged_file_path = isset( $properties_info['output_file'] ) ? $properties_info['output_file'] : '';

		// Initialize address array.
		$addresses = array();

		// If merged file exists, extract addresses for each selected position.
		if ( ! empty( $merged_file_path ) && file_exists( $merged_file_path ) ) {
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();
			$filesystem   = $xml_processor->get_filesystem();

			// Read the merged XML file.
			$xml_content = $filesystem ? $filesystem->get_contents( $merged_file_path ) : false;

			if ( $xml_content ) {
				// Parse XML.
				libxml_use_internal_errors( true );
				$dom = new DOMDocument( '1.0', 'UTF-8' );
				if ( $dom->loadXML( $xml_content, LIBXML_NOCDATA ) ) {
					$xpath = new DOMXPath( $dom );

					// Get all RealEstateProperty nodes.
					$property_nodes = $xpath->query( '//RealEstateProperty' );

					// Extract addresses for each selected position.
					for ( $i = 0; $i < $property_nodes->length; $i++ ) {
						$position = $i + 1; // 1-based position.

						// Only process selected positions.
						if ( ! in_array( (string) $position, $record_array, true ) ) {
							continue;
						}

						$property_node = $property_nodes->item( $i );

						// Create a new DOMDocument for this property.
						$property_dom = new DOMDocument( '1.0', 'UTF-8' );
						$imported_node = $property_dom->importNode( $property_node, true );
						$property_dom->appendChild( $imported_node );

						$property_xpath = new DOMXPath( $property_dom );

						// Extract address and city using XPath.
						$address_nodes = $property_xpath->query( '//Location/Address/AddressLine1/Translation/text()' );
						$city_nodes    = $property_xpath->query( '//Location/Address/CityName/Translation/text()' );

						$address = ( $address_nodes && $address_nodes->length > 0 ) ? trim( $address_nodes->item( 0 )->nodeValue ) : __( 'N/A', 'kolibri24-connect' );
						$city    = ( $city_nodes && $city_nodes->length > 0 ) ? trim( $city_nodes->item( 0 )->nodeValue ) : __( 'N/A', 'kolibri24-connect' );

						$addresses[ $position ] = $address . ', ' . $city;
					}
				}
				libxml_clear_errors();
			}
		}

		wp_send_json_success(
			array(
				'selected_records' => $selected_records,
				'record_array'     => $record_array,
				'count'            => $record_count,
				'addresses'        => $addresses,
			)
		);
	}

	/**
	 * AJAX handler for downloading archive media
	 *
	 * @since 1.3.0
	 */
	public function download_archive_media() {
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
			
			// Get selected file paths.
			if ( ! isset( $_POST['selected_files'] ) || empty( $_POST['selected_files'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'No properties selected.', 'kolibri24-connect' ),
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
						'message' => __( 'No valid property files found.', 'kolibri24-connect' ),
					)
				);
			}
			
			// Process media downloads.
			require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
			$xml_processor = new Kolibri24_Connect_Xml_Processor();
			
			$media_base_path = trailingslashit( $upload_dir['basedir'] ) . 'properties/media/';
			$downloaded_count = 0;
			$failed_count = 0;
			$errors = array();
			
			foreach ( $valid_files as $xml_file_path ) {
				// Read and parse XML.
				$xml_content = file_get_contents( $xml_file_path );
				if ( ! $xml_content ) {
					$failed_count++;
					continue;
				}
				
				// Parse XML.
				libxml_use_internal_errors( true );
				$dom = new DOMDocument( '1.0', 'UTF-8' );
				if ( ! $dom->loadXML( $xml_content, LIBXML_NOCDATA ) ) {
					libxml_clear_errors();
					$failed_count++;
					continue;
				}
				
				$xpath = new DOMXPath( $dom );
				
				// Extract property ID.
				$property_id_nodes = $xpath->query( '//PropertyInfo/PublicReferenceNumber/text()' );
				$property_id = ( $property_id_nodes && $property_id_nodes->length > 0 ) ? trim( $property_id_nodes->item( 0 )->nodeValue ) : null;
				
				if ( ! $property_id ) {
					$failed_count++;
					continue;
				}
				
				// Extract all image URLs.
				$image_nodes = $xpath->query( '//Attachments/Attachment/URLNormalizedFile/text()' );
				
				if ( ! $image_nodes || $image_nodes->length === 0 ) {
					continue; // No images for this property.
				}
				
				// Create media directory for this property.
				$property_media_dir = $media_base_path . sanitize_file_name( $property_id ) . '/';
				if ( ! wp_mkdir_p( $property_media_dir ) ) {
					$failed_count++;
					$errors[] = sprintf(
						__( 'Failed to create media directory for property %s.', 'kolibri24-connect' ),
						esc_html( $property_id )
					);
					continue;
				}
				
				// Download each image.
				for ( $i = 0; $i < $image_nodes->length; $i++ ) {
					$image_url = trim( $image_nodes->item( $i )->nodeValue );
					
					if ( empty( $image_url ) ) {
						continue;
					}
					
					// Validate URL.
					if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
						continue;
					}
					
					// Get filename from URL.
					$parsed_url = wp_parse_url( $image_url );
					$filename = basename( $parsed_url['path'] );
					$filename = sanitize_file_name( $filename );
					
					if ( empty( $filename ) ) {
						$filename = 'image_' . $i . '.jpg';
					}
					
					$file_path = $property_media_dir . $filename;
					
					// Skip if file already exists.
					if ( file_exists( $file_path ) ) {
						$downloaded_count++;
						continue;
					}
					
					// Download the file.
					$response = wp_remote_get(
						$image_url,
						array(
							'timeout' => 30,
							'stream'  => true,
							'filename' => $file_path,
						)
					);
					
					if ( is_wp_error( $response ) ) {
						$failed_count++;
						$errors[] = sprintf(
							__( 'Failed to download image from %s', 'kolibri24-connect' ),
							esc_url( $image_url )
						);
					} else {
						$response_code = wp_remote_retrieve_response_code( $response );
						if ( 200 === $response_code ) {
							$downloaded_count++;
						} else {
							$failed_count++;
							$errors[] = sprintf(
								__( 'HTTP error %d downloading %s', 'kolibri24-connect' ),
								intval( $response_code ),
								esc_url( $image_url )
							);
						}
					}
				}
			}
			
			// Send success response.
			wp_send_json_success(
				array(
					'message'           => sprintf(
						__( 'Downloaded %d images. %d failed.', 'kolibri24-connect' ),
						intval( $downloaded_count ),
						intval( $failed_count )
					),
					'downloaded_count'  => $downloaded_count,
					'failed_count'      => $failed_count,
					'errors'            => $errors,
				)
			);
		}

	/**
	 * AJAX handler for getting list of previous archives
	 *
	 * @since 1.9.0
	 */
	public function get_previous_archives() {
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

		$upload_dir   = wp_upload_dir();
		$archive_dir  = $upload_dir['basedir'] . '/kolibri/archived';
		$archives     = array();

		if ( file_exists( $archive_dir ) ) {
			$directories = glob( $archive_dir . '/*', GLOB_ONLYDIR );

			if ( ! empty( $directories ) ) {
				foreach ( $directories as $dir ) {
					$dir_name  = basename( $dir );
					$xml_files = glob( $dir . '/*.xml' );

					$archives[] = array(
						'name'  => $dir_name,
						'path'  => $dir,
						'count' => count( $xml_files ),
						'date'  => wp_date( 'Y-m-d H:i:s', filemtime( $dir ) ),
					);
				}

				// Sort by date, newest first.
				usort(
					$archives,
					function( $a, $b ) {
						return strcmp( $b['date'], $a['date'] );
					}
				);
			}
		}

		wp_send_json_success(
			array(
				'archives' => $archives,
			)
		);
	}

	/**
	 * AJAX handler for loading a previous archive
	 *
	 * @since 1.9.0
	 */
	public function load_previous_archive() {
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

		// Get selected archive path.
		if ( ! isset( $_POST['archive_path'] ) || empty( $_POST['archive_path'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No archive selected.', 'kolibri24-connect' ),
				)
			);
		}

		$archive_path = sanitize_text_field( wp_unslash( $_POST['archive_path'] ) );

		// Validate path (security check - must be in archived directory).
		$upload_dir  = wp_upload_dir();
		$archive_dir = $upload_dir['basedir'] . '/kolibri/archived/';

		if ( strpos( $archive_path, $archive_dir ) !== 0 || ! is_dir( $archive_path ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid archive path.', 'kolibri24-connect' ),
				)
			);
		}

		// Copy XML files from archive to working directory.
		$working_dir = trailingslashit( $upload_dir['basedir'] ) . 'kolibri/';

		// Initialize WP Filesystem.
		require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
		$xml_processor = new Kolibri24_Connect_Xml_Processor();
		$filesystem    = $xml_processor->filesystem;

		// Get XML files from archive.
		$xml_files = glob( $archive_path . '/*.xml' );

		if ( empty( $xml_files ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No XML files found in archive.', 'kolibri24-connect' ),
				)
			);
		}

		// Copy XML files to working directory.
		foreach ( $xml_files as $file ) {
			$filename    = basename( $file );
			$destination = $working_dir . $filename;

			$content = $filesystem->get_contents( $file );
			if ( false === $content ) {
				wp_send_json_error(
					array(
						'message' => sprintf( __( 'Failed to read file: %s', 'kolibri24-connect' ), esc_html( $filename ) ),
					)
				);
			}

			if ( ! $filesystem->put_contents( $destination, $content ) ) {
				wp_send_json_error(
					array(
						'message' => sprintf( __( 'Failed to copy file: %s', 'kolibri24-connect' ), esc_html( $filename ) ),
					)
				);
			}
		}

		// Merge the XML files.
		require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-kolibri24-connect-xml-processor.php';
		$xml_processor = new Kolibri24_Connect_Xml_Processor();

		$merge_result = $xml_processor->merge_xml_files( $working_dir );

		if ( ! $merge_result['success'] ) {
			wp_send_json_error(
				array(
					'message' => $merge_result['message'],
				)
			);
		}

		// Extract preview data.
		$preview_data = $xml_processor->extract_preview_data( $merge_result['merged_file'] );

		if ( empty( $preview_data ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No property data extracted from files.', 'kolibri24-connect' ),
				)
			);
		}

		// Save properties info and preview data.
		$archive_name = basename( $archive_path );
		$properties_info = array(
			'total_properties' => count( $preview_data ),
			'archive_name'     => $archive_name,
			'output_file'      => $merge_result['merged_file'],
		);

		update_option( 'kolibri24_properties_info', $properties_info );
		update_option( 'kolibri24_preview_data', $preview_data );

		wp_send_json_success(
			array(
				'message'     => sprintf( __( 'Archive loaded: %d properties found.', 'kolibri24-connect' ), count( $preview_data ) ),
				'properties'  => $preview_data,
				'archive_name' => $archive_name,
			)
		);
	}

	/**
	 * AJAX handler for saving import history
	 *
	 * @since 1.9.0
	 */
	public function save_import_history() {
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

		// Get imported records from POST.
		if ( ! isset( $_POST['records'] ) || empty( $_POST['records'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No records to save.', 'kolibri24-connect' ),
				)
			);
		}

		$records = json_decode( stripslashes( sanitize_text_field( wp_unslash( $_POST['records'] ) ) ), true );
		if ( ! is_array( $records ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid records format.', 'kolibri24-connect' ),
				)
			);
		}

		// Get existing history.
		$history = get_option( 'kolibri24_import_history', array() );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Add new records to history (keyed by property ID).
		foreach ( $records as $record ) {
			if ( isset( $record['id'] ) ) {
				$history[ $record['id'] ] = array(
					'id'           => $record['id'],
					'address'      => $record['address'] ?? 'N/A',
					'last_imported' => current_time( 'mysql' ),
					'last_modified' => $record['last_modified'] ?? '',
				);
			}
		}

		// Save updated history.
		update_option( 'kolibri24_import_history', $history );

		wp_send_json_success(
			array(
				'message' => sprintf( __( 'Saved %d records to import history.', 'kolibri24-connect' ), count( $records ) ),
			)
		);
	}

	/**
	 * AJAX handler for getting import history
	 *
	 * @since 1.9.0
	 */
	public function get_import_history() {
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

		// Get import history.
		$history = get_option( 'kolibri24_import_history', array() );

		if ( empty( $history ) ) {
			wp_send_json_success(
				array(
					'history' => array(),
					'message' => __( 'No import history found.', 'kolibri24-connect' ),
				)
			);
		}

		// Convert to array for display.
		$history_array = array_values( $history );

		wp_send_json_success(
			array(
				'history' => $history_array,
				'count'   => count( $history_array ),
			)
		);
	}}
}

new Kolibri24_Connect_Ajax();