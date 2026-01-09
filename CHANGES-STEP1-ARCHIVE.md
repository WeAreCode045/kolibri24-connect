# Step 1 Enhancement: Use Previous Archive Option

## Overview
Added a fourth import source option on Step 1 that allows users to load previously downloaded and archived ZIP files instead of downloading new data.

---

## Changes Made

### 1. Admin Interface (class-kolibri24-connect-admin.php)

**Added new radio button option:**
- Label: "Use Previous Archive"
- Icon: dashicons-archive
- Description: "Load a previously downloaded and archived ZIP file."
- Contains a dropdown select to choose from available archives

**HTML Structure:**
```html
<div class="kolibri24-source-option">
    <label>
        <span class="dashicons dashicons-archive"></span>
        <input type="radio" name="kolibri24-import-source" value="previous-archive" />
        <strong>Use Previous Archive</strong>
    </label>
    <p class="description">Load a previously downloaded and archived ZIP file.</p>
    <div id="kolibri24-previous-archive-field" class="kolibri24-collapsible">
        <select id="kolibri24-previous-archive" class="regular-text">
            <option value="">-- Select an archive --</option>
        </select>
        <p class="description" id="kolibri24-archive-info"></p>
    </div>
</div>
```

---

### 2. JavaScript (assets/js/admin.js)

#### Updated handleSourceChange()
- Now hides/shows the archive field when "previous-archive" source is selected
- Automatically loads the list of available archives when this option is selected
- Uses same collapsible pattern as remote-url and upload fields

#### New Function: loadPreviousArchives()
- Makes AJAX call to `kolibri24_get_previous_archives` action
- Populates the archive dropdown with:
  - Archive name (folder name based on date)
  - Number of XML files
  - Archive date and time
- Shows "No archives available" if no archives exist
- Shows error message if loading fails

#### Updated downloadAndExtract()
- Added validation for previous-archive source
- Checks that an archive is selected before proceeding
- Changes progress message to "Loading archive..." for previous-archive source
- Passes `archive_path` in AJAX data when source is previous-archive

---

### 3. PHP AJAX Handlers (class-kolibri24-connect-ajax.php)

#### New AJAX Action: kolibri24_get_previous_archives
- Returns list of all archived directories in `/uploads/kolibri/archived/`
- Returns for each archive:
  - Directory name (archive date)
  - Full path to directory
  - Count of XML files
  - Last modified date
- Archives are sorted by date (newest first)
- Includes proper nonce verification and capability checks

#### New AJAX Action: kolibri24_load_previous_archive
- Accepts selected archive path from Step 1
- Validates the path is within the archived directory (security check)
- Copies XML files from archive to working directory
- Merges all XML files into properties.xml
- Extracts property preview data
- Returns same success response as download flow
- Includes proper error handling for missing files or invalid paths

#### Updated download_and_extract()
- Added handling for 'previous-archive' source
- When previous-archive is selected:
  - Skips download step
  - Validates archive path
  - Reads XML files directly from archive directory
  - Proceeds with merge and preview extraction
- Same final output and preview data as other sources
- Preserves archive metadata for Step 3 display

---

## User Experience Flow

### Step 1: Select Import Source
1. User selects "Use Previous Archive" radio button
2. Archive dropdown automatically populates with available archives
3. User selects an archive from dropdown
4. User clicks "Download & Extract Properties" button
5. System loads XML files from archive (faster than downloading)
6. Proceeds to Step 2 with property selection

### Archive Information Display
Each archive shows:
```
Archive Name (Number of Files, Date and Time)
Example: 09-01-2026 (12 files, 2026-01-09 14:32:15)
```

---

## Technical Details

### Security
- Archive paths are validated to be within `/uploads/kolibri/archived/` directory
- Prevents directory traversal attacks
- Includes nonce verification on all AJAX calls
- Capability check ensures only admins can use this feature

### Performance
- Loading from previous archive is faster than downloading
- No network requests needed for archive loading
- Direct file copying and XML processing
- Same workflow as other sources after initial load

### File Structure
- Archives are stored in: `/wp-content/uploads/kolibri/archived/DD-MM-YYYY/`
- Each archive contains:
  - `properties.zip` (original ZIP file)
  - Individual `*.xml` files (extracted)
- Previous archive option uses existing archives without copying data to archived directory

---

## Database Options Used
- `kolibri24_properties_info` - Stored after loading archive (same as other sources)
- `kolibri24_preview_data` - Stored after loading archive (same as other sources)
- `kolibri24_selected_records` - Saved in Step 2 (same as other sources)

---

## Testing Checklist

- [ ] "Use Previous Archive" option visible on Step 1
- [ ] Archive dropdown populates when option is selected
- [ ] Archive dropdown shows multiple archives if they exist
- [ ] Archive dropdown shows "No archives available" when none exist
- [ ] Selection of archive and clicking Download works
- [ ] Progress shows "Loading archive..."
- [ ] Step advances to Step 2 after successful load
- [ ] Properties preview displays correctly
- [ ] Archive metadata (name, count) appears in Step 3
- [ ] Back button returns to Step 1
- [ ] Can select different archive and reload
- [ ] Archive validation prevents invalid paths (security test)

---

## Backward Compatibility
✓ No changes to existing import sources (Kolibri24, Remote URL, Upload)
✓ No changes to Step 2, Step 3, or Archive tab functionality
✓ All existing features continue to work as before
✓ No database schema changes required
