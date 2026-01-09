# Kolibri24 Connect - AI Coding Agent Instructions

## Architecture Overview

This is a **WordPress plugin** (not a standalone app) that downloads property data from Kolibri24 API, processes XML files, and manages archives. Key architectural decisions:

- **Singleton Pattern**: Main plugin class (`Kolibri24_Connect`) uses singleton via `instance()` method
- **Separated Concerns**: Each class handles one responsibility (ZIP download, XML processing, AJAX, Admin UI, Archives)
- **WordPress Filesystem API**: Never use native PHP file operations (`file_get_contents`, `fopen`, etc.) - always use `WP_Filesystem` via `$this->filesystem`
- **No Direct Instantiation**: Helper classes auto-instantiate at bottom of file (e.g., `new Kolibri24_Connect_Admin();`)

## Critical Constants & Paths

**Always use these defined constants:**
- `KOLIBRI24_CONNECT_PLUGIN_FILE` - Main plugin file path (`__FILE__` from main plugin)
- `KOLIBRI24_CONNECT_ABSPATH` - Plugin directory with trailing slash
- **Never use** `KOLIBRI24_CONNECT_PLUGIN_DIR` (doesn't exist - causes fatal errors)

**File paths pattern:**
```php
require_once KOLIBRI24_CONNECT_ABSPATH . 'includes/class-name.php';
```

## WordPress-Specific Patterns

### Security (Applied to EVERY AJAX handler)
```php
// 1. Nonce verification (always first)
wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'kolibri24_process_properties' )

// 2. Capability check
current_user_can( 'manage_options' )

// 3. Input sanitization
sanitize_text_field( wp_unslash( $_POST['field'] ) )

// 4. Output escaping in templates
esc_html__(), esc_attr(), esc_html_e()
```

### AJAX Response Pattern
```php
// Success
wp_send_json_success( array( 'message' => '...', 'data' => $data ) );

// Error
wp_send_json_error( array( 'message' => '...', 'step' => 'download' ) );
```

### Naming Conventions
- **PHP Classes**: `Kolibri24_Connect_Feature_Name` (underscores, PascalCase)
- **AJAX Actions**: `kolibri24_action_name` (hyphens convert to underscores in hook names)
- **CSS Classes**: `kolibri24-feature-name` (hyphens)
- **JS Variables**: `camelCase` for local, `PascalCase` for object singletons

## Two-Step Import Workflow with Multiple Sources

The plugin uses a **two-step user interaction** (not single-click automation) with **three import source options**:

### Import Sources
1. **Download from Kolibri24**: Download latest data directly from the Kolibri24 API (existing behavior)
2. **Download from Remote URL**: User provides a custom URL to download a ZIP file
3. **Upload Local ZIP**: User uploads a ZIP file from their computer

### Two-Step Process
1. **Step 1** (Select Source & Download & Extract): 
   - User selects import source (radio buttons: Kolibri24/Remote URL/Upload)
   - For remote URL: user enters URL in text field
   - For upload: user selects file via file input
   - User clicks "Download & Extract" → AJAX action `kolibri24_download_extract` with `source` parameter
   - Returns property preview data with checkboxes

2. **Step 2** (Merge Selection): User selects properties → Clicks "Merge Selected Properties" → AJAX action `kolibri24_merge_properties` → Merges only selected files

**Never auto-merge after download** - the preview/selection step is intentional UX.

### Implementation Details
- **AJAX Handler**: `download_and_extract()` method checks `$_POST['source']` and calls appropriate method:
  - `$zip_handler->download_zip()` for Kolibri24
  - `$zip_handler->download_from_url($url)` for remote URL
  - `$zip_handler->handle_file_upload()` for file upload
- **Zip Handler Methods**:
  - `download_zip()` - Downloads from Kolibri24 API (existing)
  - `download_from_url($url)` - Downloads from custom URL with validation
  - `handle_file_upload()` - Processes uploaded ZIP with error handling
- **JavaScript**: `Kolibri24PropertyProcessor.handleSourceChange()` shows/hides URL and file input fields based on selection

## XML Processing Specifics

### XPath Queries (Do NOT change these paths)
```php
'//PropertyInfo/PublicReferenceNumber/text()'  // Property ID
'//Location/Address/AddressLine1/Translation/text()'  // Address
'//Location/Address/CityName/Translation/text()'  // City
'//Financials/PurchasePrice/text()' OR '//Financials/RentPrice/text()'  // Price
'//Attachments/Attachment/URLThumbFile/text()'  // Image
```

### XML Merging Pattern
```php
// Extract individual <RealEstateProperty> nodes
$properties = $xpath->query( '//RealEstateProperty' );

// Merge into new document with <properties> root
$merged_doc = new DOMDocument( '1.0', 'UTF-8' );
$root = $merged_doc->createElement( 'properties' );
$imported_node = $merged_doc->importNode( $property_node, true );
$root->appendChild( $imported_node );
```

## Directory Structure & File Locations

**Archive structure** (dated folders for audit trail):
```
/wp-content/uploads/kolibri/archived/DD-MM-YYYY/
    ├── properties.zip  (original download)
    └── *.xml           (extracted files)
```

**Output location** (merged file):
```
/wp-content/uploads/kolibri/properties.xml
```

**Never hardcode paths** - use:
```php
$upload_dir = wp_upload_dir();
$base_path = trailingslashit( $upload_dir['basedir'] ) . 'kolibri/';
```

## Archive Tab Functionality

The plugin has **Import** and **Archive** tabs (added recently):

- **Archive List**: Shows all dated directories in `/uploads/kolibri/archived/`
- **Archive Preview**: Clicking "View" shows property grid (same UI as import step 2)
- **Delete**: Removes entire dated directory
- **AJAX Actions**: `kolibri24_get_archives`, `kolibri24_view_archive`, `kolibri24_delete_archive`

## JavaScript Architecture

### jQuery Object Singletons
```javascript
var Kolibri24PropertyProcessor = {
    init: function() { /* bind events */ },
    downloadAndExtract: function() { /* AJAX */ },
    // ...
};

// Initialize conditionally based on page elements
if ($('#kolibri24-download-btn').length > 0) {
    Kolibri24PropertyProcessor.init();
}
```

### AJAX Pattern
```javascript
$.ajax({
    url: kolibri24Ajax.ajaxUrl,  // Localized from wp_localize_script
    data: {
        action: 'kolibri24_action_name',
        nonce: nonce
    }
});
```

## Common Pitfalls to Avoid

1. **Fatal Error**: Using `KOLIBRI24_CONNECT_PLUGIN_DIR` instead of `KOLIBRI24_CONNECT_ABSPATH`
2. **Security**: Forgetting nonce verification or capability checks in AJAX handlers
3. **File Operations**: Using `file_get_contents()` instead of `$this->filesystem->get_contents()`
4. **Class Names**: Using hyphens in PHP class names (e.g., `KOLIBRI24-CONNECT_AJAX` is invalid)
5. **Tab Rendering**: Forgetting to check `$active_tab` variable in `render_admin_page()` method

## Performance & Resource Management

**Always set these at start of AJAX handlers:**
```php
set_time_limit( 600 );  // 10 minutes
wp_raise_memory_limit( 'admin' );
```

**Large file handling:**
```php
wp_remote_get( $url, array(
    'timeout' => 300,
    'stream'  => true,  // Important for large ZIPs
    'filename' => $file_path
));
```

## Debugging Commands

**Check syntax:**
```bash
php -l includes/class-kolibri24-connect-ajax.php
```

**Enable WordPress debug:**
```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Check permissions:**
```bash
ls -la wp-content/uploads/kolibri/
```

## Version Management

**Bump plugin version at each commit** following semantic versioning:

- **Major (1.0.0 → 2.0.0)**: Breaking changes, major refactoring
- **Minor (1.0.0 → 1.1.0)**: New features, functionality additions
- **Patch (1.0.0 → 1.0.1)**: Bug fixes, security patches

**Update version in both locations:**
```php
// kolibri24-connect.php header
* Version: 1.2.3

// class-kolibri24-connect.php constant
public $version = '1.2.3';
```

## When Adding New Features

1. **New AJAX action**: Add to `Kolibri24_Connect_Ajax::__construct()` with `add_action()` hook
2. **New tab**: Update `render_admin_page()` with `if/elseif` for `$active_tab`
3. **New CSS/JS**: Add to respective files, increment version in `enqueue_styles`/`enqueue_scripts`
4. **New class**: Follow pattern: class definition → check `! class_exists()` → auto-instantiate at bottom
5. **Bump plugin version**: Use correct semantic version (minor for features, patch for bugs)

## Translation-Ready

All user-facing strings use:
```php
__( 'Text', 'kolibri24-connect' )        // Return translated
esc_html__( 'Text', 'kolibri24-connect' ) // Return escaped
esc_html_e( 'Text', 'kolibri24-connect' ) // Echo escaped
_n( 'Singular', 'Plural', $count, 'kolibri24-connect' ) // Pluralization
```

## Key Files Reference

- `kolibri24-connect.php` - Main plugin file, defines constants, initializes singleton
- `class-kolibri24-connect.php` - Main plugin class with conditional loading
- `class-kolibri24-connect-ajax.php` - All AJAX handlers (5 actions: download_extract, merge_properties, get_archives, view_archive, delete_archive)
- `class-kolibri24-connect-xml-processor.php` - ZIP extraction, XML parsing, XPath queries, property preview extraction
- `class-kolibri24-connect-zip-handler.php` - Downloads ZIP from API, validates magic bytes, creates dated directories
- `class-kolibri24-connect-admin.php` - Admin UI with tabs, nonce generation, asset enqueuing
- `assets/js/admin.js` - Two object singletons: `Kolibri24PropertyProcessor` and `Kolibri24ArchiveManager`
