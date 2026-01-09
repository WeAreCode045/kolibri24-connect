# Kolibri24 Properties Import Plugin

A production-ready WordPress plugin for downloading, extracting, and processing property data from the Kolibri24 API.

## Description

This plugin automatically downloads property data ZIP files from Kolibri24, extracts XML files, and merges all property information into a single unified XML file. The plugin features a clean admin interface with AJAX processing, real-time progress tracking, and comprehensive error handling.

## Features

- **Automated ZIP Download**: Downloads property data from Kolibri24 API endpoint
- **Secure File Handling**: Uses WordPress Filesystem API for all file operations
- **Dated Archive System**: Stores downloads in dated folders (DD-MM-YYYY format) for audit trail
- **XML Processing**: Extracts and merges multiple XML files into a single unified format
- **Real-time Feedback**: AJAX-powered interface with progress tracking
- **Security First**: Nonce verification, capability checks, and input sanitization
- **Production Ready**: Error handling, logging, and WordPress coding standards compliance

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- `manage_options` capability (Administrator role)
- Write permissions for wp-content/uploads directory

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Kolibri Import' in the WordPress admin menu

## Usage

1. Go to **Kolibri Import** in the WordPress admin menu
2. Click the **Download & Process Properties** button
3. Wait for the process to complete (progress bar will show status)
4. View success message with processing details

### Output Location

The merged XML file is saved to:
```
/wp-content/uploads/kolibri/properties.xml
```

### Archive Location

Downloaded ZIP files and extracted XMLs are stored in dated folders:
```
/wp-content/uploads/kolibri/archived/DD-MM-YYYY/
```

## File Structure

```
kolibri24-connect/
├── kolibri24-connect.php                           # Main plugin file
├── README.md                                       # Documentation
├── LICENSE                                         # GPL v3 License
├── assets/
│   ├── css/
│   │   ├── admin.css                              # Admin styles
│   │   └── frontend.css                           # Frontend styles
│   └── js/
│       ├── admin.js                               # Admin JavaScript
│       └── frontend.js                            # Frontend JavaScript
├── includes/
│   ├── class-kolibri24-connect.php                # Main plugin class
│   ├── class-kolibri24-connect-admin.php          # Admin functionality
│   ├── class-kolibri24-connect-ajax.php           # AJAX handlers
│   ├── class-kolibri24-connect-frontend.php       # Frontend functionality
│   ├── class-kolibri24-connect-zip-handler.php    # ZIP download handler
│   └── class-kolibri24-connect-xml-processor.php  # XML processing
├── languages/                                      # Translations
└── templates/                                      # Template files
```

## Technical Details

### Classes

#### `Kolibri24_Connect_Zip_Handler`
Handles ZIP file downloading and validation:
- Downloads ZIP from Kolibri24 API
- Creates dated directory structure
- Validates ZIP file integrity
- Uses WP_Filesystem for file operations

#### `Kolibri24_Connect_Xml_Processor`
Manages XML extraction and processing:
- Extracts ZIP files using WordPress `unzip_file()`
- Recursively finds all XML files
- Extracts `<RealEstateProperty>` nodes using DOMDocument
- Merges nodes into unified XML structure
- Saves with UTF-8 encoding

#### `Kolibri24_Connect_Ajax`
Handles AJAX requests:
- Verifies nonces and capabilities
- Orchestrates download → extract → merge pipeline
- Returns detailed JSON responses
- Implements error handling

#### `Kolibri24_Connect_Admin`
Manages admin interface:
- Creates admin menu page
- Renders dashboard UI
- Enqueues assets with localization
- Implements nonce protection

### Security Features

- ✅ Nonce verification on all AJAX requests
- ✅ Capability checks (`manage_options`)
- ✅ Input sanitization with WordPress functions
- ✅ Output escaping in templates
- ✅ Direct file access prevention
- ✅ ZIP file validation (magic bytes check)
- ✅ WordPress Filesystem API usage

### XML Processing Flow

1. **Download**: ZIP file downloaded from Kolibri24 API
2. **Validate**: Check file size and ZIP signature
3. **Extract**: Unzip to dated directory
4. **Find**: Recursively locate all `.xml` files
5. **Parse**: Extract `<RealEstateProperty>` nodes using DOMDocument
6. **Merge**: Combine all nodes into single `<properties>` root element
7. **Save**: Write unified XML to `/uploads/kolibri/properties.xml`

### Output XML Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<properties>
  <RealEstateProperty>
    <!-- Property data from first XML -->
  </RealEstateProperty>
  <RealEstateProperty>
    <!-- Property data from second XML -->
  </RealEstateProperty>
  <!-- Additional properties... -->
</properties>
```

## Error Handling

The plugin implements comprehensive error handling:

- **Download errors**: Network issues, HTTP errors, invalid responses
- **Extraction errors**: Corrupt ZIP files, permission issues
- **XML parsing errors**: Invalid XML, missing nodes, encoding issues
- **File system errors**: Permission issues, disk space problems

All errors are:
- Logged to WordPress debug.log (if `WP_DEBUG_LOG` enabled)
- Displayed to user with clear, actionable messages
- Returned via AJAX with error details

## Performance Considerations

- **Time Limit**: Increased to 600 seconds (10 minutes) for processing
- **Memory Limit**: Uses `wp_raise_memory_limit()` for large datasets
- **Streaming Download**: Uses `stream` parameter for large ZIP files
- **Progress Tracking**: Real-time AJAX feedback prevents timeouts

## Customization

### Modify ZIP URL

Edit [class-kolibri24-connect-zip-handler.php](includes/class-kolibri24-connect-zip-handler.php):

```php
private $zip_url = 'https://your-custom-url.com/properties.zip';
```

### Change Output Location

Edit [class-kolibri24-connect-xml-processor.php](includes/class-kolibri24-connect-xml-processor.php):

```php
public function get_output_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit( $upload_dir['basedir'] ) . 'custom-path/properties.xml';
}
```

### Modify Archive Directory Format

Edit [class-kolibri24-connect-zip-handler.php](includes/class-kolibri24-connect-zip-handler.php):

```php
$date_folder = gmdate( 'Y-m-d' ); // Change format
```

## Hooks & Filters

The plugin uses WordPress hooks for extensibility:

### Actions
- `admin_menu` - Adds admin menu page
- `admin_enqueue_scripts` - Enqueues admin assets
- `wp_ajax_kolibri24_process_properties` - AJAX handler

### Filters
- `kolibri24-connect_template_path` - Modify template path
- `kolibri24-connect_locate_template` - Override template location

## Troubleshooting

### ZIP Download Fails
- Check network connectivity
- Verify API endpoint is accessible
- Check WordPress site can make external requests
- Enable `WP_DEBUG_LOG` to view detailed errors

### Extraction Fails
- Verify wp-content/uploads directory is writable
- Check available disk space
- Ensure ZIP file is valid (not corrupted)

### XML Processing Errors
- Verify XML files contain `<RealEstateProperty>` nodes
- Check XML files are valid and well-formed
- Enable `WP_DEBUG` for detailed parsing errors

### Permission Errors
- Ensure user has `manage_options` capability
- Check file/directory permissions (should be 644/755)
- Verify WordPress user can write to uploads directory

## Development

### Enable Debug Mode

Add to wp-config.php:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Coding Standards

This plugin follows:
- WordPress Coding Standards (WPCS)
- PHP_CodeSniffer rules
- WordPress best practices
- Object-oriented design patterns

## Changelog

### Version 1.0.0
- Initial release
- ZIP download functionality
- XML extraction and merging
- Admin dashboard interface
- AJAX processing with progress tracking
- Comprehensive error handling
- Security implementation (nonces, capabilities, sanitization)

## Credits

- **Developer**: Code045
- **Website**: [https://code045.nl/](https://code045.nl/)
- **License**: GNU General Public License v3.0

## Support

For support, feature requests, or bug reports, please contact Code045.

## License

This plugin is licensed under the GNU General Public License v3.0. See [LICENSE](LICENSE) file for details.