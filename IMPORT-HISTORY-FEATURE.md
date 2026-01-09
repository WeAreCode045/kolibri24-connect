# Import History Feature

## Overview
Added a comprehensive Import History system that tracks all imported properties with their modification dates and detects when property data has been updated after initial import.

---

## Features

### 1. Import History Tab
- New "Import History" tab in the admin interface
- Displays all imported properties in a table format
- Shows: Property ID, Address, Last Imported Date, Last Modified Date
- Search/filter functionality to find specific properties by ID or address

### 2. Modification Date Tracking
- Extracts `ModificationDateTime` from each property's XML (`/PropertyInfo/ModificationDateTime`)
- Stores modification date for each imported record
- Compares current data with previously imported data

### 3. Update Notifications
- When loading properties (Step 2), checks if data has been updated since last import
- Displays yellow warning notification: "⚠️ Data updated after last import"
- Shows notification directly on the property card for easy visibility
- Helps users identify which properties have changed data

### 4. Automatic History Saving
- After successful WP All Import process, history is automatically saved
- Uses WordPress options for persistent storage
- Keyed by Property ID for fast lookup and updates

---

## Database Storage

### Option: `kolibri24_import_history`
Stores all imported records with their metadata:

```php
[
    'PROPERTY_ID_1' => [
        'id'            => 'PROPERTY_ID_1',
        'address'       => '123 Main Street, London',
        'last_imported' => '2026-01-09 14:30:45',
        'last_modified' => '2025-12-15 10:20:00'
    ],
    'PROPERTY_ID_2' => [
        'id'            => 'PROPERTY_ID_2',
        'address'       => '456 Oak Avenue, Amsterdam',
        'last_imported' => '2026-01-08 09:15:30',
        'last_modified' => '2025-12-20 16:45:00'
    ]
]
```

### Option: `kolibri24_preview_data`
Temporarily stores preview data during import (includes update flags):

```php
[
    [
        'record_position' => 1,
        'property_id'     => 'PROPERTY_ID_1',
        'address'         => '123 Main Street, London',
        'last_modified'   => '2025-12-15 10:20:00',
        'is_updated'      => true,
        'update_message'  => 'Data updated after last import'
    ]
]
```

---

## Implementation Details

### Admin Interface (class-kolibri24-connect-admin.php)
- New "Import History" tab between Archive and Settings tabs
- Table structure with 4 columns:
  - Property ID (15% width)
  - Address (45% width)
  - Last Imported (20% width)
  - Last Modified (20% width)
- Search input to filter by ID or address
- "Load History" button to fetch and display records

### AJAX Handlers (class-kolibri24-connect-ajax.php)

**Action: `kolibri24_get_import_history`**
- Returns all stored import history records
- Converts keyed array to indexed array for table display
- Includes count of total records
- Includes error message if no history exists

**Action: `kolibri24_save_import_history` (called internally)**
- Called via after-import hook
- Receives array of imported records
- Updates existing history by Property ID
- Preserves previous imports, adds/updates new ones

### XML Processing (class-kolibri24-connect-xml-processor.php)

**Method: `extract_property_previews_from_merged()`** (Updated)
- Now extracts `ModificationDateTime` using XPath
- Returns modification date in property preview data
- Date format: ISO 8601 from XML file

**Method: `check_for_updates()` (New)**
- Receives array of current properties with modification dates
- Gets import history from WordPress options
- Compares current modification date with last imported date
- Sets `is_updated` flag and `update_message` if newer
- Handles cases where property wasn't previously imported

### After-Import Hook (kolibri24-after-import-hook.php)
- Enhanced `pmxi_after_xml_import` action hook
- After successful import, saves preview data to import history
- Uses preview data which includes modification dates
- Stores last imported timestamp (current date/time)

### JavaScript (assets/js/admin.js)

**Property Rendering** (`renderPropertyList()` - Updated)
- Added update notification display
- Shows yellow warning box if `property.is_updated === true`
- Displays `property.update_message` text
- Positioned directly below property ID

**Import History Manager** (New)
- `Kolibri24HistoryManager` object singleton
- `loadHistory()` - Fetches history via AJAX
- `displayHistory()` - Renders table rows from history data
- `filterHistory()` - Real-time search/filter functionality
- HTML escaping for security

---

## Workflow

### Step 1-2: Download and Extract
1. User selects import source and downloads/extracts properties
2. XML properties are parsed and merged
3. Preview data extracted including `ModificationDateTime`

### Step 2: Detect Updates
1. `check_for_updates()` is called on preview data
2. Each property is checked against import history
3. If `ModificationDateTime > last_imported`, flag as updated
4. Update notification displayed on updated properties

### Step 3: Import
1. User selects properties and confirms import
2. WP All Import process begins
3. `pmxi_after_xml_import` hook fires after completion
4. Import history is saved with current timestamp
5. History is keyed by Property ID for future lookups

### Import History Tab
1. User clicks "Load History" button
2. AJAX call fetches all stored history records
3. Table displays all imported properties
4. Search filter allows finding specific properties

---

## Update Detection Logic

```
IF property was previously imported:
    IF current ModificationDateTime > last_imported_ModificationDateTime:
        MARK as "is_updated = true"
        SHOW warning notification
    ELSE:
        MARK as "is_updated = false"
    ENDIF
ELSE:
    MARK as "is_updated = false"
    (New property, no previous import to compare)
ENDIF
```

---

## Security Considerations

✓ Nonce verification on all AJAX actions  
✓ Capability check (manage_options) on all AJAX handlers  
✓ Input sanitization with `sanitize_text_field()`  
✓ HTML escaping in JavaScript with `escapeHtml()` function  
✓ XPath queries properly validated  

---

## Performance

- **Data Keying**: Uses Property ID as array key for O(1) lookups
- **Lazy Loading**: History loaded only when "Load History" clicked
- **Client-side Filtering**: Search filter runs on client, no additional AJAX calls
- **Storage**: Uses WordPress options (no custom tables)

---

## Testing Checklist

- [ ] Import History tab visible in admin menu
- [ ] "Load History" button loads and displays records
- [ ] Search filter works for Property ID
- [ ] Search filter works for Address
- [ ] Table displays correct columns: ID, Address, Last Imported, Last Modified
- [ ] Date formatting is correct (locale-aware)
- [ ] First import of a property doesn't show update notification
- [ ] Subsequent import with unchanged data doesn't show notification
- [ ] Subsequent import with newer modification date shows warning
- [ ] Warning notification appears in yellow box
- [ ] Multiple properties show correct update status
- [ ] History persists after page reload
- [ ] History is cleared/updated after new import

---

## Files Modified

1. **includes/class-kolibri24-connect-admin.php**
   - Added Import History tab to tab navigation
   - Added Import History tab content with table and search

2. **includes/class-kolibri24-connect-ajax.php**
   - Added `kolibri24_get_previous_archives` hook registration
   - Added `kolibri24_save_import_history` hook registration
   - Added `kolibri24_get_import_history` hook registration
   - Added `save_import_history()` method
   - Added `get_import_history()` method
   - Updated `download_and_extract()` to check for updates and save preview data

3. **includes/class-kolibri24-connect-xml-processor.php**
   - Updated `extract_property_previews_from_merged()` to extract ModificationDateTime
   - Added `check_for_updates()` method

4. **includes/kolibri24-after-import-hook.php**
   - Enhanced `pmxi_after_xml_import` hook to save import history

5. **assets/js/admin.js**
   - Updated `renderPropertyList()` to display update notifications
   - Added `Kolibri24HistoryManager` object with history functionality
   - Added `escapeHtml()` helper function

---

## Future Enhancements

- Export history to CSV
- Delete specific history records
- Archive history by date range
- Bulk update notification for multiple properties
- Email alerts for updated properties
- API endpoint for external tools
