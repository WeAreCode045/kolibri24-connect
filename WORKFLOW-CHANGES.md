# Workflow Changes - Record Position-Based Import

## Overview
The plugin has been redesigned to merge ALL XML files immediately after extraction, then allow users to select specific record positions to import via WP All Import's `wp_all_import_specified_records` filter.

## Previous Workflow
1. Download & Extract → Show property grid
2. User selects properties → Merge selected files into properties.xml
3. Run WP All Import on merged file (imports all records)

## New Workflow
1. Download & Extract → **Merge ALL files immediately** → Show property grid with record positions
2. User selects records → **Save positions as comma-separated list** (e.g., "1,2,3,5,7")
3. Run WP All Import → **Filter applies positions** → Only selected records imported

## Key Changes

### 1. PHP Backend Changes

#### `class-kolibri24-connect-ajax.php`
- **download_and_extract()**: 
  - STEP 3: Now calls `merge_selected_properties()` with ALL files immediately after extraction
  - STEP 4: Calls new `extract_property_previews_from_merged()` method to read from merged file
  
- **merge_selected_properties()**: Completely rewritten
  - Old behavior: Merged selected XML files into properties.xml
  - New behavior: Validates and saves comma-separated record positions to `kolibri24_selected_records` option
  - Returns: `count` of positions saved
  
- **get_selected_records()**: New AJAX handler
  - Retrieves saved positions from `kolibri24_selected_records` option
  - Returns: `count` and formatted `positions` string for display

#### `class-kolibri24-connect-xml-processor.php`
- **extract_property_previews_from_merged()**: New method
  - Reads merged `properties.xml` file
  - Parses all `<RealEstateProperty>` nodes
  - Extracts preview data with 1-based `record_position` for each property
  - Returns: Array of properties with `record_position`, `property_id`, `address`, `city`, `price`, `image_url`

#### `kolibri24-after-import-hook.php`
- **wp_all_import_specified_records filter**: New addition
  - Checks if import ID matches configured `kolibri24_import_id`
  - Returns comma-separated positions from `kolibri24_selected_records` option
  - WP All Import uses this to import only selected record positions

### 2. JavaScript Changes

#### `assets/js/admin.js`

**renderPropertyList()**: Updated property grid rendering
- Changed: `value="' + property.file_path + '"` 
- To: `data-record="' + property.record_position + '"`
- Changed: `id="property-' + property.index + '"`
- To: `id="property-' + property.record_position + '"`
- Added: Position number in heading: `"Position " + property.record_position + " - ID: " + property.property_id`

**mergeSelectedProperties()**: Updated to collect positions
- Changed: Collects `file_path` values
- To: Collects `record` data attributes using `$(this).data('record')`
- Changed: Sends `selected_files` array
- To: Sends `selected_records` as comma-separated string: `selectedRecords.join(',')`

**handleMergeSuccess()**: Updated success message
- Changed: Shows `data.processed` properties merged
- To: Shows `data.count` record positions saved
- Added: Auto-scroll to Run Import section after 500ms

**Run Import button handler**: Added confirmation dialog
- New: First calls `kolibri24_get_selected_records` AJAX action
- Shows confirmation: "You are about to import X records at positions: 1,2,3,5,7"
- Only proceeds if user confirms
- Shows warning if no records selected

### 3. UI Text Changes

#### `class-kolibri24-connect-admin.php`
- **Step 2 heading**: 
  - Old: "Step 2: Select Properties to Merge"
  - New: "Step 2: Select Records to Import"
  
- **Step 2 description**: 
  - Old: "Select the properties you want to merge into the final properties.xml file."
  - New: "All properties have been merged. Select the record positions you want to import via WP All Import."
  
- **Merge button text**: 
  - Old: "Merge Selected Properties"
  - New: "Save Selected Records for Import"

- **JavaScript version**: Bumped from 1.2.5 to 1.2.6 for cache busting

## New WordPress Options

| Option Name | Type | Description |
|------------|------|-------------|
| `kolibri24_selected_records` | string | Comma-separated list of 1-based record positions (e.g., "1,2,3,5,7") |

## WP All Import Integration

The plugin now uses WP All Import's `wp_all_import_specified_records` filter to control which records are imported:

```php
add_filter( 'wp_all_import_specified_records', function( $specified_records, $import_id, $root_nodes ) {
    $configured_id = get_option( 'kolibri24_import_id' );
    if ( $configured_id && $import_id == $configured_id ) {
        $selected_records = get_option( 'kolibri24_selected_records', '' );
        if ( ! empty( $selected_records ) ) {
            return $selected_records; // e.g., "1,2,3,5,7"
        }
    }
    return $specified_records;
}, 10, 3 );
```

## User Experience Flow

1. **Download & Extract**: User clicks "Download & Extract" → All files merged automatically
2. **Property Grid**: Shows all properties with Position numbers (1-based indices)
3. **Selection**: User checks desired properties → Clicks "Save Selected Records for Import"
4. **Confirmation**: Success message shows count and scrolls to Run Import section
5. **Import**: User clicks "Run WP All Import" → Confirmation dialog shows positions → Import runs with filter applied
6. **Result**: Only selected positions are imported from the merged XML file

## Technical Notes

- **Record positions are 1-based**: First property = position 1 (not 0)
- **All files merged once**: Merging happens immediately after extraction, not after selection
- **Filter-based import control**: WP All Import filter controls which records import, not file merging
- **Comma-separated format**: Positions stored as "1,2,3,5,7" (integers only, comma-separated)
- **Validation**: AJAX handler validates that positions are comma-separated integers before saving

## Testing Checklist

- [ ] Download from Kolibri24 API merges all files correctly
- [ ] Property grid displays with Position numbers
- [ ] Selecting/deselecting checkboxes updates count
- [ ] "Save Selected Records" button saves positions to option
- [ ] Success message shows correct count
- [ ] "Run WP All Import" button shows confirmation with positions
- [ ] WP All Import filter receives correct positions
- [ ] Only selected positions are imported
- [ ] pmxi_after_xml_import hook still fires correctly
