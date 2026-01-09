<?php
/**
 * ZIP Download Handler
 *
 * Handles downloading and extracting ZIP files from Kolibri24 API.
 *
 * @package Kolibri24_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Kolibri24_Connect_Zip_Handler' ) ) {
	/**
	 * Kolibri24_Connect_Zip_Handler Class
	 *
	 * Manages ZIP file download and extraction operations.
	 */
	class Kolibri24_Connect_Zip_Handler {

		/**
		 * ZIP download URL
		 *
		 * @var string
		 */
		private $zip_url = 'https://sitelink.kolibri24.com/v3/3316248a-1295-4a05-83c4-cfc287a4af72/zip/properties.zip';

		/**
		 * Base upload directory path
		 *
		 * @var string
		 */
		private $base_upload_path;

		/**
		 * WordPress filesystem instance
		 *
		 * @var WP_Filesystem_Base
		 */
		private $filesystem;

		/**
		 * Constructor
		 */
		public function __construct() {
			// Initialize WP_Filesystem.
			$this->init_filesystem();

			// Set base upload path.
			$upload_dir            = wp_upload_dir();
			$this->base_upload_path = trailingslashit( $upload_dir['basedir'] ) . 'kolibri/';
		}

		/**
		 * Initialize WordPress Filesystem API
		 *
		 * @return bool True on success, false on failure.
		 */
		private function init_filesystem() {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$this->filesystem = $wp_filesystem;

			return ! empty( $this->filesystem );
		}

		/**
		 * Download ZIP file from Kolibri24 API
		 *
		 * @return array Array with 'success' (bool) and 'message' (string) and optional 'file_path' (string).
		 */
		public function download_zip() {
			// Create dated directory.
			$dated_dir = $this->create_dated_directory();
			if ( is_wp_error( $dated_dir ) ) {
				return array(
					'success' => false,
					'message' => $dated_dir->get_error_message(),
				);
			}

			// Set file path for the downloaded ZIP.
			$file_path = $dated_dir . 'properties.zip';

			// Download the file using wp_remote_get.
			$response = wp_remote_get(
				$this->zip_url,
				array(
					'timeout'  => 300, // 5 minutes timeout for large files.
					'stream'   => true,
					'filename' => $file_path,
				)
			);

			// Check for errors.
			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Failed to download ZIP file: %s', 'kolibri24-connect' ),
						$response->get_error_message()
					),
				);
			}

			// Check HTTP response code.
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %d: HTTP response code */
						__( 'ZIP download failed with HTTP code: %d', 'kolibri24-connect' ),
						$response_code
					),
				);
			}

			// Validate the downloaded file.
			$validation = $this->validate_zip( $file_path );
			if ( is_wp_error( $validation ) ) {
				// Clean up invalid file.
				$this->filesystem->delete( $file_path );
				return array(
					'success' => false,
					'message' => $validation->get_error_message(),
				);
			}

			return array(
				'success'   => true,
				'message'   => __( 'ZIP file downloaded successfully.', 'kolibri24-connect' ),
				'file_path' => $file_path,
				'dated_dir' => $dated_dir,
			);
		}

		/**
		 * Create dated directory for storing downloaded files
		 *
		 * Directory structure: /wp-content/uploads/kolibri/archived/DD-MM-YYYY/
		 *
		 * @return string|WP_Error Directory path on success, WP_Error on failure.
		 */
		private function create_dated_directory() {
			// Get current date in DD-MM-YYYY format.
			$date_folder = gmdate( 'd-m-Y' );

			// Build full directory path.
			$dir_path = $this->base_upload_path . 'archived/' . $date_folder . '/';

			// Create directory if it doesn't exist.
			if ( ! $this->filesystem->exists( $dir_path ) ) {
				if ( ! wp_mkdir_p( $dir_path ) ) {
					return new WP_Error(
						'directory_creation_failed',
						sprintf(
							/* translators: %s: directory path */
							__( 'Failed to create directory: %s', 'kolibri24-connect' ),
							$dir_path
						)
					);
				}
			}

			// Verify directory is writable.
			if ( ! $this->filesystem->is_writable( $dir_path ) ) {
				return new WP_Error(
					'directory_not_writable',
					sprintf(
						/* translators: %s: directory path */
						__( 'Directory is not writable: %s', 'kolibri24-connect' ),
						$dir_path
					)
				);
			}

			return $dir_path;
		}

		/**
		 * Validate downloaded ZIP file
		 *
		 * @param string $file_path Path to ZIP file.
		 * @return bool|WP_Error True if valid, WP_Error on failure.
		 */
		private function validate_zip( $file_path ) {
			// Check if file exists.
			if ( ! $this->filesystem->exists( $file_path ) ) {
				return new WP_Error(
					'file_not_found',
					__( 'Downloaded ZIP file not found.', 'kolibri24-connect' )
				);
			}

			// Check file size (must be greater than 0).
			$file_size = $this->filesystem->size( $file_path );
			if ( ! $file_size || $file_size < 100 ) {
				return new WP_Error(
					'invalid_file_size',
					__( 'Downloaded ZIP file is too small or empty.', 'kolibri24-connect' )
				);
			}

			// Verify it's a valid ZIP file by checking magic bytes.
			$file_contents = $this->filesystem->get_contents( $file_path );
			if ( false === $file_contents ) {
				return new WP_Error(
					'file_read_error',
					__( 'Unable to read downloaded ZIP file.', 'kolibri24-connect' )
				);
			}

			// Check for ZIP file signature (PK).
			if ( 0 !== strpos( $file_contents, 'PK' ) ) {
				return new WP_Error(
					'invalid_zip_format',
					__( 'Downloaded file is not a valid ZIP archive.', 'kolibri24-connect' )
				);
			}

			return true;
		}

		/**
		 * Get base upload path
		 *
		 * @return string
		 */
		public function get_base_upload_path() {
			return $this->base_upload_path;
		}

		/**
		 * Get ZIP URL
		 *
		 * @return string
		 */
		public function get_zip_url() {
			return $this->zip_url;
		}
	}
}
