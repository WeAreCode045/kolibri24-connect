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
		 * Accessor for WP_Filesystem instance
		 *
		 * @return WP_Filesystem_Base|null
		 */
		public function get_filesystem() {
			return $this->filesystem;
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
		 * Get all XML files from a directory (public wrapper)
		 *
		 * @param string $directory Directory to search.
		 * @return array Array of XML file paths.
		 */
		public function get_xml_files_from_directory( $directory ) {
			return $this->find_xml_files( $directory );
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
		 * Extracts detailed property data for comparison
		 *
		 * @param DOMXPath $property_xpath XPath instance scoped to a single property.
		 * @return array
		 */
		private function extract_detailed_property_data( $property_xpath ) {
			$purchase_price = $this->get_xpath_value( $property_xpath, "//Financials/PurchasePrice/text()" );
			$rent_price     = $this->get_xpath_value( $property_xpath, "//Financials/RentPrice/text()" );

			return array(
				'address'        => $this->get_xpath_value( $property_xpath, "//Location/Address/AddressLine1/Translation/text()" ),
				'city'           => $this->get_xpath_value( $property_xpath, "//Location/Address/CityName/Translation/text()" ),
				'postal_code'    => $this->get_xpath_value( $property_xpath, "//Location/Address/PostalCode/Translation/text()" ),
				'country'        => $this->get_xpath_value( $property_xpath, "//Location/Address/Country/Translation/text()" ),
				'status'         => $this->get_xpath_value( $property_xpath, "//PropertyInfo/Status/Translation/text()" ),
				'purchase_price' => $purchase_price,
				'rent_price'     => $rent_price,
				'property_type'  => $this->get_xpath_value( $property_xpath, "//PropertyInfo/Type/Translation/text()" ),
				'living_area'    => $this->get_xpath_value( $property_xpath, "//Building/Areas/LivingArea/text()" ),
				'plot_area'      => $this->get_xpath_value( $property_xpath, "//Building/Areas/PlotArea/text()" ),
				'rooms'          => $this->get_xpath_value( $property_xpath, "//Building/Rooms/RoomCount/text()" ),
				'bedrooms'       => $this->get_xpath_value( $property_xpath, "//Building/Rooms/BedroomCount/text()" ),
				'bathrooms'      => $this->get_xpath_value( $property_xpath, "//Building/Rooms/BathroomCount/text()" ),
				'description'    => $this->get_xpath_value( $property_xpath, "//PropertyInfo/Description/Translation/text()" ),
				'last_modified'  => $this->get_xpath_value( $property_xpath, "//PropertyInfo/ModificationDateTime/text()" ),
			);
		}


		/**
		 * Extract property previews from merged properties.xml file
		 *
		 * @param string $merged_file_path Path to merged properties.xml file.
		 * @return array Result array with success status and properties data.
		 */
		public function extract_property_previews_from_merged( $merged_file_path ) {
			if ( ! file_exists( $merged_file_path ) ) {
				return array(
					"success" => false,
					"message" => __( "Merged properties file not found.", "kolibri24-connect" ),
				);
			}

			$properties = array();

			// Read the merged XML file.
			$xml_content = $this->filesystem->get_contents( $merged_file_path );
			if ( false === $xml_content ) {
				return array(
					"success" => false,
					"message" => __( "Failed to read merged properties file.", "kolibri24-connect" ),
				);
			}

			// Parse XML.
			libxml_use_internal_errors( true );
			$dom = new DOMDocument( "1.0", "UTF-8" );
			if ( ! $dom->loadXML( $xml_content, LIBXML_NOCDATA ) ) {
				libxml_clear_errors();
				return array(
					"success" => false,
					"message" => __( "Failed to parse merged properties XML.", "kolibri24-connect" ),
				);
			}

			$xpath = new DOMXPath( $dom );

			// Get all RealEstateProperty nodes.
			$property_nodes = $xpath->query( "//RealEstateProperty" );

			if ( ! $property_nodes || $property_nodes->length === 0 ) {
				return array(
					"success" => false,
					"message" => __( "No properties found in merged file.", "kolibri24-connect" ),
				);
			}

			// Extract preview data for each property with position.
			for ( $i = 0; $i < $property_nodes->length; $i++ ) {
				$property_node = $property_nodes->item( $i );
				
				// Create a new DOMDocument for this property.
				$property_dom = new DOMDocument( "1.0", "UTF-8" );
				$imported_node = $property_dom->importNode( $property_node, true );
				$property_dom->appendChild( $imported_node );
				
				$property_xpath = new DOMXPath( $property_dom );
				
				// Extract data using XPath.
				$property_id = $this->get_xpath_value( $property_xpath, "//PropertyInfo/PublicReferenceNumber/text()" );
				$details     = $this->extract_detailed_property_data( $property_xpath );
				
				$price = $details['purchase_price'];
				if ( empty( $price ) ) {
					$price = $details['rent_price'];
				}

				$image_url = $this->get_xpath_value( $property_xpath, "//Attachments/Attachment/URLThumbFile/text()" );
				
				$properties[] = array(
					"record_position" => $i + 1, // 1-based position
					"property_id"     => $property_id ? $property_id : __( "N/A", "kolibri24-connect" ),
					"address"         => $details['address'] ? $details['address'] : __( "N/A", "kolibri24-connect" ),
					"city"            => $details['city'] ? $details['city'] : __( "N/A", "kolibri24-connect" ),
					"postal_code"     => $details['postal_code'] ? $details['postal_code'] : '',
					"country"         => $details['country'] ? $details['country'] : '',
					"price"           => $price ? $price : __( "N/A", "kolibri24-connect" ),
					"purchase_price"  => $details['purchase_price'] ? $details['purchase_price'] : '',
					"rent_price"      => $details['rent_price'] ? $details['rent_price'] : '',
					"property_type"   => $details['property_type'] ? $details['property_type'] : '',
					"status"          => $details['status'] ? $details['status'] : '',
					"living_area"     => $details['living_area'] ? $details['living_area'] : '',
					"plot_area"       => $details['plot_area'] ? $details['plot_area'] : '',
					"rooms"           => $details['rooms'] ? $details['rooms'] : '',
					"bedrooms"        => $details['bedrooms'] ? $details['bedrooms'] : '',
					"bathrooms"       => $details['bathrooms'] ? $details['bathrooms'] : '',
					"description"     => $details['description'] ? $details['description'] : '',
					"image_url"       => $image_url ? $image_url : "",
					"last_modified"   => $details['last_modified'] ? $details['last_modified'] : "",
					"changed_fields"  => array(),
				);
			}

			return array(
				"success"    => true,
				"properties" => $properties,
			);
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

		/**
		 * Check if properties have been updated since last import
		 *
		 * @param array $current_properties Array of current properties with modification dates.
		 * @return array Updated properties with update flag.
		 *
		 * @since 1.9.0
		 */
		public function check_for_updates( $current_properties ) {
			// Get import history.
			$history = get_option( 'kolibri24_import_history', array() );

			if ( empty( $history ) ) {
				return $current_properties;
			}

			// Check each property for updates.
			foreach ( $current_properties as &$property ) {
				$property_id = $property['property_id'] ?? '';

				// Check if this property was previously imported.
				if ( isset( $history[ $property_id ] ) && ! empty( $property['last_modified'] ) ) {
					$last_imported = $history[ $property_id ]['last_modified'] ?? '';
					$current_modified = $property['last_modified'];

					// Compare modification dates.
					if ( ! empty( $last_imported ) && strtotime( $current_modified ) > strtotime( $last_imported ) ) {
						$property['is_updated'] = true;
						$property['update_message'] = __( 'Data updated after last import', 'kolibri24-connect' );
					} else {
						$property['is_updated'] = false;
					}
				} else {
					$property['is_updated'] = false;
				}
			}

			return $current_properties;
		}

		/**
		 * Compare current properties against last import to detect field-level changes
		 *
		 * @param array $current_properties Current property previews including detailed fields.
		 * @return array
		 */
		public function compare_with_last_import( $current_properties ) {
			$history_details = get_option( 'kolibri24_import_history_details', array() );

			if ( empty( $history_details ) ) {
				return $current_properties;
			}

			$fields = array(
				'address'        => $this->get_field_display_name( 'address' ),
				'city'           => $this->get_field_display_name( 'city' ),
				'postal_code'    => $this->get_field_display_name( 'postal_code' ),
				'country'        => $this->get_field_display_name( 'country' ),
				'purchase_price' => $this->get_field_display_name( 'purchase_price' ),
				'rent_price'     => $this->get_field_display_name( 'rent_price' ),
				'property_type'  => $this->get_field_display_name( 'property_type' ),
				'status'         => $this->get_field_display_name( 'status' ),
				'living_area'    => $this->get_field_display_name( 'living_area' ),
				'plot_area'      => $this->get_field_display_name( 'plot_area' ),
				'rooms'          => $this->get_field_display_name( 'rooms' ),
				'bedrooms'       => $this->get_field_display_name( 'bedrooms' ),
				'bathrooms'      => $this->get_field_display_name( 'bathrooms' ),
				'description'    => $this->get_field_display_name( 'description' ),
				'last_modified'  => $this->get_field_display_name( 'last_modified' ),
			);

			foreach ( $current_properties as &$property ) {
				$property_id = $property['property_id'] ?? '';
				$property['changed_fields'] = array();

				if ( empty( $property_id ) || ! isset( $history_details[ $property_id ] ) ) {
					continue;
				}

				$previous = $history_details[ $property_id ];

				foreach ( $fields as $field_key => $label ) {
					$current_value  = isset( $property[ $field_key ] ) ? $property[ $field_key ] : '';
					$previous_value = isset( $previous[ $field_key ] ) ? $previous[ $field_key ] : '';

					if ( $current_value === '' && $previous_value === '' ) {
						continue;
					}

					if ( $current_value !== $previous_value ) {
						$property['changed_fields'][] = array(
							'field' => $label,
							'old'   => $previous_value,
							'new'   => $current_value,
						);
					}
				}

				if ( ! empty( $property['changed_fields'] ) ) {
					$property['is_updated']    = true;
					$property['update_message'] = __( 'Fields changed since last import', 'kolibri24-connect' );
				}
			}

			return $current_properties;
		}

		/**
		 * Friendly label for field keys
		 *
		 * @param string $field_key Field key.
		 * @return string
		 */
		private function get_field_display_name( $field_key ) {
			switch ( $field_key ) {
				case 'status':
					return __( 'Status', 'kolibri24-connect' );
				case 'postal_code':
					return __( 'Postal Code', 'kolibri24-connect' );
				case 'purchase_price':
					return __( 'Purchase Price', 'kolibri24-connect' );
				case 'rent_price':
					return __( 'Rent Price', 'kolibri24-connect' );
				case 'property_type':
					return __( 'Property Type', 'kolibri24-connect' );
				case 'living_area':
					return __( 'Living Area', 'kolibri24-connect' );
				case 'plot_area':
					return __( 'Plot Area', 'kolibri24-connect' );
				case 'rooms':
					return __( 'Rooms', 'kolibri24-connect' );
				case 'bedrooms':
					return __( 'Bedrooms', 'kolibri24-connect' );
				case 'bathrooms':
					return __( 'Bathrooms', 'kolibri24-connect' );
				case 'description':
					return __( 'Description', 'kolibri24-connect' );
				case 'last_modified':
					return __( 'Last Modified', 'kolibri24-connect' );
				case 'country':
					return __( 'Country', 'kolibri24-connect' );
				case 'city':
					return __( 'City', 'kolibri24-connect' );
				case 'address':
				default:
					return __( 'Address', 'kolibri24-connect' );
			}
		}
	}
}
