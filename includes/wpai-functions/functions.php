<?php
/**
 * Return the relative path to the current properties.xml in the selected archive.
 * Example: wp-content/uploads/kolibri/archived/09-01-2026_00-01-00/properties.xml
 *
 * @return string Relative path from site root, or empty string on failure.
 */
if ( ! function_exists( 'wpai_importfile' ) ) {
	function wpai_importfile() {
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
		$path = wpai_importfile();
		if ( empty( $path ) ) {
			return '';
		}

		// Ensure single slash between home_url and the relative path.
		return trailingslashit( home_url() ) . ltrim( $path, '/' );
	}
}

 function price($sell, $rent) {
	if(empty($sell)) {
		return $rent;
	} else {
		return $sell;
	}
}
 function is_rent_suffix($element3) {
	if(empty($element3)) {
		return "";
	} else {
		return "per maand";
	}
}




function sellingtype($sell, $sell_price, $rent, $rent_price) {
if ($sell == "true") {
  return $sell_price;
 } elseif ($rent == "true") {
  return $rent_price;
 } else {
  return "";
 } 
}
function after_xml_import( $import_id, $import ) {
    // Only run if import ID is 9.
    if ($import_id == 2) {   
$args = array(
		'post_type' => 'property',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => 'property-status',
				'field' => 'slug',
				'terms' => 'verkocht'
			),
		)
	);
// Delete the attached images and related sizes keep the featured image

	$posts = get_posts($args);
	foreach ($posts as $post) {

		$images = get_attached_media('image', $post->ID);
		foreach ($images as $image) {
			if ($image->ID !== get_post_thumbnail_id($post->ID)) {
				wp_delete_attachment($image->ID, true);
			}
		}
// Delete the postmeta for the images in the Gallery

		delete_post_meta($post->ID, 'REAL_HOMES_property_images');

	}
 }
}
add_action( 'pmxi_after_xml_import', 'after_xml_import', 10, 2 );

/**
 * Kolibri24 Connect: WP All Import Hooks
 * Additional hooks for tracking import stats and filtering records
 */

/**
 * Hook after WP All Import completes to save stats and history
 */
add_action( 'pmxi_after_xml_import', function( $import_id, $import ) {
    // Get the configured import ID from plugin settings
    $configured_id = get_option( 'kolibri24_import_id' );
    if ( $configured_id && $import_id == $configured_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pmxi_imports';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d", $import_id ) );
        if ( $row ) {
            $stats = array(
                'count'    => $row->count,
                'imported' => $row->imported,
                'created'  => $row->created,
                'updated'  => $row->updated,
                'skipped'  => $row->skipped,
                'deleted'  => $row->deleted,
            );
            // Store stats in a transient for later display (e.g. in admin UI)
            set_transient( 'kolibri24_last_import_stats', $stats, 60 * 60 ); // 1 hour
            
            // Save import history with preview data
            $preview_data = get_option( 'kolibri24_preview_data', array() );
            if ( ! empty( $preview_data ) ) {
                $history         = get_option( 'kolibri24_import_history', array() );
                $history_details = get_option( 'kolibri24_import_history_details', array() );

                if ( ! is_array( $history ) ) {
                    $history = array();
                }
                if ( ! is_array( $history_details ) ) {
                    $history_details = array();
                }
                
                // Add each preview record to history
                foreach ( $preview_data as $property ) {
                    if ( isset( $property['property_id'] ) ) {
                        $history[ $property['property_id'] ] = array(
                            'id'             => $property['property_id'],
                            'address'        => $property['address'] ?? 'N/A',
                            'last_imported'  => current_time( 'mysql' ),
                            'last_modified'  => $property['last_modified'] ?? '',
                        );

                        $history_details[ $property['property_id'] ] = array(
                            'address'        => $property['address'] ?? '',
                            'city'           => $property['city'] ?? '',
                            'postal_code'    => $property['postal_code'] ?? '',
                            'country'        => $property['country'] ?? '',
                            'status'         => $property['status'] ?? '',
                            'purchase_price' => $property['purchase_price'] ?? '',
                            'rent_price'     => $property['rent_price'] ?? '',
                            'property_type'  => $property['property_type'] ?? '',
                            'living_area'    => $property['living_area'] ?? '',
                            'plot_area'      => $property['plot_area'] ?? '',
                            'rooms'          => $property['rooms'] ?? '',
                            'bedrooms'       => $property['bedrooms'] ?? '',
                            'bathrooms'      => $property['bathrooms'] ?? '',
                            'description'    => $property['description'] ?? '',
                            'last_modified'  => $property['last_modified'] ?? '',
                        );
                    }
                }
                
                update_option( 'kolibri24_import_history', $history );
                update_option( 'kolibri24_import_history_details', $history_details );
            }
        }
    }
}, 20, 2 );

function kolibri24_use_selected_records($specified_records, $import_id, $root_nodes) {

    // Optioneel: alleen toepassen op specifieke import
    // if ($import_id !== 12) return $specified_records;

    $records = get_option('kolibri24_selected_records');

    if (empty($records)) {
        return $specified_records; // fallback naar WPAI instellingen
    }

    // Schoonmaken (veiligheid)
    $records = preg_replace('/[^0-9,\-]/', '', $records);

    return $records;
}