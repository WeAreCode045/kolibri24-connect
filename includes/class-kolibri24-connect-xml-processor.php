<?php
/**
 * XML Processor
 *
 * Handles ZIP extraction and XML file processing/merging.
 *
 * @package Kolibri24_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Kolibri24_Connect_Xml_Processor' ) ) {
	/**
	 * Kolibri24_Connect_Xml_Processor Class
	 *
	 * Manages ZIP extraction and XML processing operations.
	 */
	class Kolibri24_Connect_Xml_Processor {

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
		 * Extract ZIP file
		 *
		 * @param string $zip_file_path Path to ZIP file.
		 * @param string $extract_to_path Directory to extract files to.
		 * @return array Array with 'success' (bool) and 'message' (string).
		 */
		public function extract_zip( $zip_file_path, $extract_to_path ) {
			// Verify ZIP file exists.
			if ( ! $this->filesystem->exists( $zip_file_path ) ) {
				return array(
					'success' => false,
					'message' => __( 'ZIP file not found.', 'kolibri24-connect' ),
				);
			}

			// Load unzip_file function.
			require_once ABSPATH . 'wp-admin/includes/file.php';

			// Extract the ZIP file.
			$result = unzip_file( $zip_file_path, $extract_to_path );

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Failed to extract ZIP file: %s', 'kolibri24-connect' ),
						$result->get_error_message()
					),
				);
			}

			// Count extracted XML files.
			$xml_files = $this->find_xml_files( $extract_to_path );
			$xml_count = count( $xml_files );

			return array(
				'success'   => true,
				'message'   => sprintf(
					/* translators: %d: number of XML files */
					_n(
						'ZIP extracted successfully. Found %d XML file.',
						'ZIP extracted successfully. Found %d XML files.',
						$xml_count,
						'kolibri24-connect'
					),
					$xml_count
				),
				'xml_count' => $xml_count,
				'xml_files' => $xml_files,
			);
		}

		/**
		 * Find all XML files in a directory (recursive)
		 *
		 * @param string $directory Directory to search.
		 * @return array Array of XML file paths.
		 */
		private function find_xml_files( $directory ) {
			$xml_files = array();

			if ( ! $this->filesystem->exists( $directory ) ) {
				return $xml_files;
			}

			$files = $this->filesystem->dirlist( $directory, false, true );

			if ( empty( $files ) ) {
				return $xml_files;
			}

			foreach ( $files as $file_name => $file_info ) {
				$file_path = trailingslashit( $directory ) . $file_name;

				// If it's a directory, search recursively.
				if ( 'd' === $file_info['type'] ) {
					$xml_files = array_merge( $xml_files, $this->find_xml_files( $file_path ) );
				} elseif ( 'f' === $file_info['type'] && preg_match( '/\.xml$/i', $file_name ) ) {
					// If it's an XML file, add it to the array.
					$xml_files[] = $file_path;
				}
			}

			return $xml_files;
		}

		/**
		 * Extract property preview data from XML files
		 *
		 * @param array $xml_files Array of XML file paths.
		 * @return array Array with 'success' (bool), 'properties' (array) and 'message' (string).
		 */
		public function extract_property_previews( $xml_files ) {
			if ( empty( $xml_files ) ) {
				return array(
					'success' => false,
					'message' => __( 'No XML files found to process.', 'kolibri24-connect' ),
				);
			}

			$properties  = array();
			$error_count = 0;

			foreach ( $xml_files as $index => $xml_file ) {
				$preview = $this->extract_property_preview( $xml_file, $index );

				if ( ! is_wp_error( $preview ) ) {
					$properties[] = $preview;
				} else {
					++$error_count;
				}
			}

			if ( empty( $properties ) ) {
				return array(
					'success' => false,
					'message' => __( 'No valid properties found in XML files.', 'kolibri24-connect' ),
				);
			}

			return array(
				'success'    => true,
				'properties' => $properties,
				'message'    => sprintf(
					/* translators: 1: property count, 2: total count */
					__( 'Found %1$d valid properties out of %2$d XML files.', 'kolibri24-connect' ),
					count( $properties ),
					count( $xml_files )
				),
			);
		}

		/**
		 * Extract preview data from a single XML file
		 *
		 * @param string $xml_file_path Path to XML file.
		 * @param int    $index File index.
		 * @return array|WP_Error Array with preview data on success, WP_Error on failure.
		 */
		private function extract_property_preview( $xml_file_path, $index ) {
			// Read file contents.
			$xml_content = $this->filesystem->get_contents( $xml_file_path );

			if ( false === $xml_content ) {
				return new WP_Error(
					'file_read_error',
					__( 'Unable to read XML file.', 'kolibri24-connect' )
				);
			}

			// Suppress XML errors.
			libxml_use_internal_errors( true );

			// Create DOMDocument and load XML.
			$dom    = new DOMDocument( '1.0', 'UTF-8' );
			$loaded = $dom->loadXML( $xml_content, LIBXML_NOCDATA );

			if ( ! $loaded ) {
				libxml_clear_errors();
				return new WP_Error(
					'xml_parse_error',
					__( 'Failed to parse XML.', 'kolibri24-connect' )
				);
			}

			$xpath = new DOMXPath( $dom );

			// Extract data using XPath.
			$property_id = $this->get_xpath_value( $xpath, '//PropertyInfo/PublicReferenceNumber/text()' );
			$address     = $this->get_xpath_value( $xpath, '//Location/Address/AddressLine1/Translation/text()' );
			$city        = $this->get_xpath_value( $xpath, '//Location/Address/CityName/Translation/text()' );

			// Try purchase price first, then rent price.
			$price = $this->get_xpath_value( $xpath, '//Financials/PurchasePrice/text()' );
			if ( empty( $price ) ) {
				$price = $this->get_xpath_value( $xpath, '//Financials/RentPrice/text()' );
			}

			$image = $this->get_xpath_value( $xpath, '//Attachments/Attachment/URLThumbFile/text()' );

			return array(
				'index'       => $index,
				'file_path'   => $xml_file_path,
				'file_name'   => basename( $xml_file_path ),
				'property_id' => $property_id ? $property_id : __( 'N/A', 'kolibri24-connect' ),
				'address'     => $address ? $address : __( 'N/A', 'kolibri24-connect' ),
				'city'        => $city ? $city : __( 'N/A', 'kolibri24-connect' ),
				'price'       => $price ? $price : __( 'N/A', 'kolibri24-connect' ),
				'image'       => $image ? $image : '',
			);
		}

		/**
		 * Get XPath value safely
		 *
		 * @param DOMXPath $xpath XPath instance.
		 * @param string   $query XPath query.
		 * @return string|null
		 */
		private function get_xpath_value( $xpath, $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes && $nodes->length > 0 ) {
				return trim( $nodes->item( 0 )->nodeValue );
			}
			return null;
		}

		/**
		 * Merge selected XML files into one
		 *
		 * @param array  $selected_files Array of selected file paths.
		 * @param string $output_path Path where merged XML should be saved.
		 * @return array Array with 'success' (bool) and 'message' (string).
		 */
		public function merge_selected_properties( $selected_files, $output_path ) {
			if ( empty( $selected_files ) ) {
				return array(
					'success' => false,
					'message' => __( 'No properties selected for merging.', 'kolibri24-connect' ),
				);
			}

			// Create a new DOMDocument for the merged output.
			$merged_doc               = new DOMDocument( '1.0', 'UTF-8' );
			$merged_doc->formatOutput = true;
			$merged_doc->encoding     = 'UTF-8';

			// Create root element.
			$root = $merged_doc->createElement( 'properties' );
			$merged_doc->appendChild( $root );

			$processed_count = 0;
			$error_count     = 0;
			$errors          = array();

			// Loop through each selected XML file.
			foreach ( $selected_files as $xml_file ) {
				$result = $this->extract_property_node( $xml_file );

				if ( is_wp_error( $result ) ) {
					++$error_count;
					$errors[] = sprintf(
						/* translators: 1: file name, 2: error message */
						__( 'File %1$s: %2$s', 'kolibri24-connect' ),
						basename( $xml_file ),
						$result->get_error_message()
					);
					continue;
				}

				// Import the RealEstateProperty node into merged document.
				$imported_node = $merged_doc->importNode( $result, true );
				$root->appendChild( $imported_node );

				++$processed_count;
			}

			// Save the merged XML.
			$save_result = $this->save_merged_xml( $merged_doc, $output_path );

			if ( is_wp_error( $save_result ) ) {
				return array(
					'success' => false,
					'message' => $save_result->get_error_message(),
				);
			}

			// Prepare success message with stats.
			$message = sprintf(
				/* translators: 1: processed count, 2: total count */
				__( 'Successfully merged %1$d of %2$d selected properties.', 'kolibri24-connect' ),
				$processed_count,
				count( $selected_files )
			);

			if ( $error_count > 0 ) {
				$message .= ' ' . sprintf(
					/* translators: %d: error count */
					_n(
						'%d file had errors.',
						'%d files had errors.',
						$error_count,
						'kolibri24-connect'
					),
					$error_count
				);
			}

			return array(
				'success'         => true,
				'message'         => $message,
				'processed_count' => $processed_count,
				'error_count'     => $error_count,
				'errors'          => $errors,
				'output_file'     => $output_path,
			);
		}

		/**
		 * Extract RealEstateProperty node from an XML file
		 *
		 * @param string $xml_file_path Path to XML file.
		 * @return DOMElement|WP_Error DOMElement on success, WP_Error on failure.
		 */
		private function extract_property_node( $xml_file_path ) {
			// Read file contents.
			$xml_content = $this->filesystem->get_contents( $xml_file_path );

			if ( false === $xml_content ) {
				return new WP_Error(
					'file_read_error',
					__( 'Unable to read XML file.', 'kolibri24-connect' )
				);
			}

			// Suppress XML errors to handle them gracefully.
			libxml_use_internal_errors( true );

			// Create a new DOMDocument and load XML.
			$dom = new DOMDocument( '1.0', 'UTF-8' );
			$loaded = $dom->loadXML( $xml_content, LIBXML_NOCDATA );

			if ( ! $loaded ) {
				$xml_errors = libxml_get_errors();
				libxml_clear_errors();

				$error_messages = array();
				foreach ( $xml_errors as $error ) {
					$error_messages[] = trim( $error->message );
				}

				return new WP_Error(
					'xml_parse_error',
					sprintf(
						/* translators: %s: error messages */
						__( 'Failed to parse XML: %s', 'kolibri24-connect' ),
						implode( ', ', $error_messages )
					)
				);
			}

			// Find RealEstateProperty node.
			$xpath = new DOMXPath( $dom );
			$properties = $xpath->query( '//RealEstateProperty' );

			if ( 0 === $properties->length ) {
				return new WP_Error(
					'property_node_not_found',
					__( 'RealEstateProperty node not found in XML file.', 'kolibri24-connect' )
				);
			}

			// Return the first RealEstateProperty node.
			return $properties->item( 0 );
		}

		/**
		 * Save merged XML document to file
		 *
		 * @param DOMDocument $dom_document DOMDocument to save.
		 * @param string      $output_path Path where file should be saved.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function save_merged_xml( $dom_document, $output_path ) {
			// Ensure output directory exists.
			$output_dir = dirname( $output_path );

			if ( ! $this->filesystem->exists( $output_dir ) ) {
				if ( ! wp_mkdir_p( $output_dir ) ) {
					return new WP_Error(
						'directory_creation_failed',
						sprintf(
							/* translators: %s: directory path */
							__( 'Failed to create output directory: %s', 'kolibri24-connect' ),
							$output_dir
						)
					);
				}
			}

			// Generate XML string with proper UTF-8 encoding.
			$xml_string = $dom_document->saveXML();

			if ( false === $xml_string ) {
				return new WP_Error(
					'xml_generation_error',
					__( 'Failed to generate XML output.', 'kolibri24-connect' )
				);
			}

			// Write to file using WP_Filesystem.
			$written = $this->filesystem->put_contents(
				$output_path,
				$xml_string,
				FS_CHMOD_FILE
			);

			if ( ! $written ) {
				return new WP_Error(
					'file_write_error',
					sprintf(
						/* translators: %s: file path */
						__( 'Failed to write merged XML file: %s', 'kolibri24-connect' ),
						$output_path
					)
				);
			}

			return true;
		}

		/**
		 * Get final output path for merged XML
		 *
		 * @return string
		 */
		public function get_output_file_path() {
			$upload_dir = wp_upload_dir();
			return trailingslashit( $upload_dir['basedir'] ) . 'kolibri/properties.xml';
		}
	}
}
