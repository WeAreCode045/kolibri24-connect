<?php
/**
 * Kolibri24 Connect: WP All Import Hook
 *
 * This file hooks into WP All Import's pmxi_after_xml_import action to trigger custom logic after import is finished.
 */

add_action( 'pmxi_after_xml_import', function( $import_id, $import ) {
    // Get the configured import ID from plugin settings
    $configured_id = get_option( 'kolibri24_wp_all_import_id' );
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
            // Optionally: do_action( 'kolibri24_after_import', $import_id, $import, $stats );
        }
    }
}, 10, 2 );

/**
 * Filter to specify which records should be imported from the XML file.
 * This uses the record positions saved by the user in the plugin UI.
 */
add_filter( 'wp_all_import_specified_records', function( $specified_records, $import_id, $root_nodes ) {
    // Get the configured import ID from plugin settings
    $configured_id = get_option( 'kolibri24_import_id' );
    
    if ( $configured_id && $import_id == $configured_id ) {
        // Get the selected record positions from plugin options
        $selected_records = get_option( 'kolibri24_selected_records', '' );
        
        if ( ! empty( $selected_records ) ) {
            // Return the comma-separated list of record positions (1-based)
            return $selected_records;
        }
    }
    
    // Return original value if not our import or no selections
    return $specified_records;
}, 10, 3 );
