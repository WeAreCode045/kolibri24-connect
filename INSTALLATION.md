# Kolibri24 Connect - Installation & Quick Start Guide

## Quick Installation

### Step 1: Upload Plugin
1. Navigate to your WordPress admin dashboard
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the `kolibri24-connect.zip` file
4. Click **Install Now**

### Step 2: Activate
1. Click **Activate Plugin** after installation
2. You'll see a new menu item **"Kolibri Import"** in your WordPress admin sidebar

### Step 3: Run First Import
1. Click on **Kolibri Import** in the admin menu
2. Click the **"Download & Process Properties"** button
3. Wait for the process to complete (typically 1-5 minutes)
4. View the success message with import statistics

## What Happens During Import?

1. **Download**: ZIP file is downloaded from Kolibri24 API
2. **Archive**: File is saved to `/wp-content/uploads/kolibri/archived/DD-MM-YYYY/`
3. **Extract**: ZIP is extracted in the same dated folder
4. **Process**: All XML files are read and `<RealEstateProperty>` nodes are extracted
5. **Merge**: Properties are combined into a single XML file
6. **Save**: Merged file is saved to `/wp-content/uploads/kolibri/properties.xml`

## Accessing the Output

The merged XML file can be accessed at:
```
/wp-content/uploads/kolibri/properties.xml
```

Or via URL:
```
https://yoursite.com/wp-content/uploads/kolibri/properties.xml
```

## Directory Structure After First Run

```
wp-content/
└── uploads/
    └── kolibri/
        ├── properties.xml                    # ← Main output file
        └── archived/
            └── 08-01-2026/                   # ← Today's dated folder
                ├── properties.zip            # Downloaded ZIP
                ├── property_001.xml          # Extracted XMLs
                ├── property_002.xml
                └── ...
```

## Scheduling Automated Imports (Optional)

To run imports automatically, you can set up a WordPress cron job or external cron.

### Using WordPress Cron

Add to your theme's `functions.php` or create a custom plugin:

```php
<?php
// Schedule daily import at 2 AM
add_action( 'wp', 'kolibri24_schedule_import' );
function kolibri24_schedule_import() {
    if ( ! wp_next_scheduled( 'kolibri24_daily_import' ) ) {
        wp_schedule_event( strtotime('02:00:00'), 'daily', 'kolibri24_daily_import' );
    }
}

// Handle the scheduled import
add_action( 'kolibri24_daily_import', 'kolibri24_run_scheduled_import' );
function kolibri24_run_scheduled_import() {
    if ( ! class_exists( 'Kolibri24_Connect_Zip_Handler' ) ) {
        return;
    }
    
    // Run the import process programmatically
    require_once WP_PLUGIN_DIR . '/kolibri24-connect/includes/class-kolibri24-connect-zip-handler.php';
    require_once WP_PLUGIN_DIR . '/kolibri24-connect/includes/class-kolibri24-connect-xml-processor.php';
    
    $zip_handler = new Kolibri24_Connect_Zip_Handler();
    $download = $zip_handler->download_zip();
    
    if ( $download['success'] ) {
        $xml_processor = new Kolibri24_Connect_Xml_Processor();
        $extract = $xml_processor->extract_zip( $download['file_path'], $download['dated_dir'] );
        
        if ( $extract['success'] ) {
            $output = $xml_processor->get_output_file_path();
            $xml_processor->process_and_merge_xml( $extract['xml_files'], $output );
        }
    }
}
```

### Using Server Cron (Advanced)

Add to your server's crontab to run daily at 2 AM:

```bash
0 2 * * * wget -q -O - https://yoursite.com/wp-admin/admin-ajax.php?action=kolibri24_process_properties
```

Note: This requires adding a no-nonce version for server cron or using WP-CLI.

## System Requirements Checklist

Before installation, verify:

- [ ] WordPress 6.0 or higher
- [ ] PHP 7.4 or higher
- [ ] Administrator account with `manage_options` capability
- [ ] Write permissions on `/wp-content/uploads/` directory
- [ ] Sufficient disk space (at least 100MB recommended)
- [ ] PHP extensions: `libxml`, `dom`, `zip`
- [ ] `allow_url_fopen` enabled or cURL available
- [ ] Max execution time: 300+ seconds recommended
- [ ] Memory limit: 128MB+ recommended

## Verifying Installation

### Check File Permissions
```bash
# Should be writable by WordPress
ls -la wp-content/uploads/
```

### Check PHP Extensions
```bash
php -m | grep -E '(dom|libxml|zip)'
```

### Check WordPress Version
```bash
wp core version
```

## Troubleshooting First Install

### Plugin Not Appearing
- Clear WordPress object cache
- Deactivate and reactivate plugin
- Check for PHP errors in debug.log

### Menu Item Not Showing
- Ensure you're logged in as Administrator
- Check user capabilities: `manage_options`
- Clear browser cache

### Download Fails
- Test API endpoint manually:
  ```bash
  curl -I https://sitelink.kolibri24.com/v3/3316248a-1295-4a05-83c4-cfc287a4af72/zip/properties.zip
  ```
- Check WordPress can make external requests
- Verify firewall isn't blocking requests

### Permission Errors
```bash
# Fix uploads directory permissions
chmod -R 755 wp-content/uploads/
chown -R www-data:www-data wp-content/uploads/
```

## Uninstallation

To remove the plugin:

1. Deactivate plugin from WordPress admin
2. Delete plugin from **Plugins → Installed Plugins**
3. Manually remove uploaded files (optional):
   ```bash
   rm -rf wp-content/uploads/kolibri/
   ```

## Getting Help

### Enable Debug Mode

Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check logs at: `wp-content/debug.log`

### Common Issues

**Issue**: "Failed to create directory"
- **Solution**: Fix permissions on uploads directory

**Issue**: "ZIP download failed with HTTP code: 403"
- **Solution**: API credentials may be invalid or IP blocked

**Issue**: "Maximum execution time exceeded"
- **Solution**: Increase `max_execution_time` in php.ini

**Issue**: "Allowed memory size exhausted"
- **Solution**: Increase `memory_limit` in php.ini or wp-config.php

### Support

For additional support:
- Review the [README.md](README.md) documentation
- Check WordPress debug.log for detailed errors
- Contact Code045: https://code045.nl/

## Next Steps

After successful installation:

1. **Test the import** - Run your first import manually
2. **Verify output** - Check that properties.xml is created
3. **Schedule automation** (optional) - Set up cron for regular imports
4. **Integrate with your theme** - Use the XML file in your property listings
5. **Monitor performance** - Check import times and adjust PHP settings if needed

## Security Recommendations

- [ ] Keep WordPress core updated
- [ ] Keep plugin updated
- [ ] Use strong administrator passwords
- [ ] Enable WordPress auto-updates
- [ ] Regular backups of wp-content/uploads/kolibri/
- [ ] Monitor file sizes to prevent disk space issues
- [ ] Review archived files periodically and clean up old folders

---

**Plugin Version**: 1.0.0  
**Last Updated**: January 8, 2026  
**Developer**: Code045
