<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package StandaloneTech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Kolibri24_Connect_Admin' ) ) {
	/**
	 * Plugin Kolibri24_Connect_Admin Class.
	 */
	class Kolibri24_Connect_Admin {
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings with WordPress Settings API
	 *
	 * @since 1.1.0
	 */
	public function register_settings() {
		register_setting(
			'kolibri24_settings_group',
			'kolibri24_api_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_api_url' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize API URL option
	 *
	 * @param string $value The URL value to sanitize.
	 * @return string Sanitized URL.
	 *
	 * @since 1.1.0
	 */
	public function sanitize_api_url( $value ) {
		if ( empty( $value ) ) {
			return '';
		}
		return esc_url( $value );
	}

	/**
	 * Add admin menu page
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
			add_menu_page(
				__( 'Kolibri Properties Import', 'kolibri24-connect' ), // Page title.
				__( 'Kolibri Import', 'kolibri24-connect' ),            // Menu title.
				'manage_options',                                        // Capability.
				'kolibri24-properties-import',                           // Menu slug.
				array( $this, 'render_admin_page' ),                     // Callback.
				'dashicons-download',                                    // Icon.
				30                                                       // Position.
			);
		}

		/**
		 * Render admin page
		 *
		 * @since 1.0.0
		 */
		public function render_admin_page() {
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'kolibri24-connect' ) );
			}

			// Generate nonce for security.
			$nonce = wp_create_nonce( 'kolibri24_process_properties' );
			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'import';
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				
				<nav class="nav-tab-wrapper">
					<a href="?page=kolibri24-properties-import&tab=import" class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Import', 'kolibri24-connect' ); ?>
					</a>
					<a href="?page=kolibri24-properties-import&tab=archive" class="nav-tab <?php echo 'archive' === $active_tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Archives', 'kolibri24-connect' ); ?>
					</a>
					<a href="?page=kolibri24-properties-import&tab=history" class="nav-tab <?php echo 'history' === $active_tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Import History', 'kolibri24-connect' ); ?>
					</a>
					<a href="?page=kolibri24-properties-import&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Settings', 'kolibri24-connect' ); ?>
					</a>
			</nav>
			
			<div class="kolibri24-import-container">
			<?php if ( 'import' === $active_tab ) : ?>
				<?php 
					$props_info = get_option( 'kolibri24_properties_info' );
				?>
				<!-- Step Navigation Indicator -->
				<div class="kolibri24-step-indicator" style="margin-bottom: 30px; text-align: center;">
					<span class="kolibri24-step-badge kolibri24-step-active" data-step="1">
						<strong>Step 1:</strong> Extract
					</span>
					<span class="kolibri24-step-separator">→</span>
					<span class="kolibri24-step-badge" data-step="2">
						<strong>Step 2:</strong> Select
					</span>
					<span class="kolibri24-step-separator">→</span>
					<span class="kolibri24-step-badge" data-step="3">
						<strong>Step 3:</strong> Import
					</span>
				</div>

				<!-- Step 1: Select Import Source & Download & Extract -->
				<div class="card kolibri24-step-1 kolibri24-step-container kolibri24-card-full">
					<h2><?php esc_html_e( 'Step 1: Select Import Source & Extract Properties', 'kolibri24-connect' ); ?></h2>
					
					<!-- Default Kolibri24 Display -->
					<div class="kolibri24-default-source">
						<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
							<span class="dashicons dashicons-cloud" style="font-size: 40px; width: 40px; height: 40px; color: #0073aa;"></span>
							<div>
								<h3 style="margin: 0;"><?php esc_html_e( 'Download from Kolibri24', 'kolibri24-connect' ); ?></h3>
								<p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Download the latest property data directly from the Kolibri24 API.', 'kolibri24-connect' ); ?></p>
							</div>
						</div>
						<input type="hidden" name="kolibri24-import-source" value="kolibri24" />
					</div>

					<!-- Change Source Button -->
					<div style="margin-bottom: 20px; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
						<button type="button" id="kolibri24-change-source-btn" class="button button-secondary">
							<span class="dashicons dashicons-image-rotate" style="margin-right: 5px;"></span>
							<?php esc_html_e( 'Change Source', 'kolibri24-connect' ); ?>
						</button>
						<button type="button" id="kolibri24-skip-step1-btn" class="button button-link">
							<?php esc_html_e( 'Use Existing Archive (skip download)', 'kolibri24-connect' ); ?>
						</button>
					</div>

					<!-- Source Options (Hidden by default) -->
					<div id="kolibri24-source-selector" class="kolibri24-source-selection" style="display: none; border: 1px solid #ddd; padding: 20px; background: #f9f9f9; border-radius: 4px; margin-bottom: 20px;">
						<p style="margin-top: 0;"><strong><?php esc_html_e( 'Select a different import source:', 'kolibri24-connect' ); ?></strong></p>
						
						<div class="kolibri24-source-options">
							<div class="kolibri24-source-option">
								<label>
									<span class="dashicons dashicons-cloud"></span>
									<input type="radio" name="kolibri24-import-source-radio" value="kolibri24" checked />
									<strong><?php esc_html_e( 'Download from Kolibri24', 'kolibri24-connect' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Download the latest property data directly from the Kolibri24 API.', 'kolibri24-connect' ); ?></p>
							</div>

							<div class="kolibri24-source-option">
								<label>
									<span class="dashicons dashicons-admin-links"></span>
									<input type="radio" name="kolibri24-import-source-radio" value="remote-url" />
									<strong><?php esc_html_e( 'Download from Remote URL', 'kolibri24-connect' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Provide a custom URL to download a ZIP file.', 'kolibri24-connect' ); ?></p>
								<div id="kolibri24-remote-url-field" class="kolibri24-collapsible">
									<input type="url" id="kolibri24-remote-url" class="regular-text" placeholder="https://example.com/properties.zip" />
								</div>
							</div>

							<div class="kolibri24-source-option">
								<label>
									<span class="dashicons dashicons-upload"></span>
									<input type="radio" name="kolibri24-import-source-radio" value="upload" />
									<strong><?php esc_html_e( 'Upload Local ZIP File', 'kolibri24-connect' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Upload a ZIP file from your computer.', 'kolibri24-connect' ); ?></p>
								<div id="kolibri24-file-upload-field" class="kolibri24-collapsible">
									<input type="file" id="kolibri24-file-upload" accept=".zip" />
									<p class="description"><?php esc_html_e( 'Maximum file size: 100MB', 'kolibri24-connect' ); ?></p>
								</div>
							</div>
						</div>

						<!-- Source Selector Actions -->
						<div style="margin-top: 15px; text-align: center;">
							<button type="button" id="kolibri24-confirm-source-btn" class="button button-primary">
								<?php esc_html_e( 'Confirm Selection', 'kolibri24-connect' ); ?>
							</button>
							<button type="button" id="kolibri24-cancel-source-btn" class="button button-secondary">
								<?php esc_html_e( 'Cancel', 'kolibri24-connect' ); ?>
							</button>
						</div>
					</div>

					<div id="kolibri24-status-messages"></div>
					
					<div id="kolibri24-progress" style="display:none;">
						<div class="kolibri24-progress-bar">
							<div class="kolibri24-progress-fill"></div>
						</div>
						<p class="kolibri24-progress-text"></p>
					</div>

					<!-- Step 1 Actions -->
					<div class="kolibri24-step-actions" style="margin-top: 20px; text-align: center;">
						<button type="button" id="kolibri24-download-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
							<?php esc_html_e( 'Download & Extract Properties', 'kolibri24-connect' ); ?>
						</button>
					</div>
				</div>

				<!-- Step 2: Property Selection (Hidden initially) -->
				<div class="card kolibri24-step-2 kolibri24-step-container kolibri24-card-full" style="display:none;">
					<h2><?php esc_html_e( 'Step 2: Select Records to Import', 'kolibri24-connect' ); ?></h2>
					<p><?php esc_html_e( 'Choose an archive created in Step 1. The properties.xml inside that archive will be used to build the preview grid and the dynamic import URL.', 'kolibri24-connect' ); ?></p>

					<div class="kolibri24-archive-select" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
						<label for="kolibri24-step2-archive-select" style="margin-right: 6px;">
							<strong><?php esc_html_e( 'Select archive:', 'kolibri24-connect' ); ?></strong>
						</label>
						<select id="kolibri24-step2-archive-select" class="regular-text" style="min-width: 260px;"></select>
						<button type="button" id="kolibri24-step2-load-archive" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<?php esc_html_e( 'Load Archive', 'kolibri24-connect' ); ?>
						</button>
						<span id="kolibri24-step2-archive-status"></span>
					</div>
					
					<div class="kolibri24-selection-controls">
						<label>
							<input type="checkbox" id="kolibri24-select-all" />
							<strong><?php esc_html_e( 'Select All', 'kolibri24-connect' ); ?></strong>
						</label>
						<span class="kolibri24-selection-count"></span>
					</div>

					<div id="kolibri24-property-list" class="kolibri24-property-grid"></div>

					<div id="kolibri24-merge-status"></div>

					<!-- Step 2 Actions -->
					<div class="kolibri24-step-actions" style="margin-top: 20px; text-align: center;">
						<button type="button" id="kolibri24-step-2-prev-btn" class="button button-secondary">
							<?php esc_html_e( '← Back', 'kolibri24-connect' ); ?>
						</button>
						<button type="button" id="kolibri24-merge-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>" disabled>
							<span class="dashicons dashicons-database-import" style="margin-top: 3px;"></span>
							<?php esc_html_e( 'Save & Continue →', 'kolibri24-connect' ); ?>
						</button>
					</div>
				</div>

				<!-- Step 3: Confirm & Import (Hidden initially) -->
				<div class="card kolibri24-step-3 kolibri24-step-container kolibri24-card-full" style="display:none;">
					<h2><?php esc_html_e( 'Step 3: Confirm & Start Import', 'kolibri24-connect' ); ?></h2>
					<p><?php esc_html_e( 'Review your selection and start the WP All Import process.', 'kolibri24-connect' ); ?></p>
					
					<!-- Properties Info Display -->
					<div class="kolibri24-run-import-section">
						<h3><?php esc_html_e( 'Merged Properties Information', 'kolibri24-connect' ); ?></h3>
						<?php $props_info = get_option( 'kolibri24_properties_info' ); ?>
						<?php if ( is_array( $props_info ) && ! empty( $props_info ) ) : ?>
							<ul style="margin: 15px 0;">
								<li>
									<strong><?php esc_html_e( 'Total properties in file:', 'kolibri24-connect' ); ?></strong>
									<?php echo intval( $props_info['total_properties'] ?? 0 ); ?>
								</li>
								<li>
									<strong><?php esc_html_e( 'Source archive:', 'kolibri24-connect' ); ?></strong>
									<?php echo esc_html( $props_info['archive_name'] ?? __( 'Unknown', 'kolibri24-connect' ) ); ?>
								</li>
								<li>
									<strong><?php esc_html_e( 'Output path:', 'kolibri24-connect' ); ?></strong>
									<?php echo esc_html( $props_info['output_file'] ); ?>
								</li>
							</ul>
						<?php else : ?>
							<p><?php esc_html_e( 'No merged properties file found. Complete Steps 1 and 2 first.', 'kolibri24-connect' ); ?></p>
						<?php endif; ?>
					</div>

					<div id="kolibri24-run-import-status" style="margin-top: 15px;"></div>

					<!-- Step 3 Actions -->
					<div class="kolibri24-step-actions" style="margin-top: 20px; text-align: center;">
						<button type="button" id="kolibri24-step-3-prev-btn" class="button button-secondary">
							<?php esc_html_e( '← Back', 'kolibri24-connect' ); ?>
						</button>
						<?php if ( is_array( $props_info ) && ! empty( $props_info['output_file'] ) ) : ?>
							<button type="button" id="kolibri24-run-import-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Start WP All Import →', 'kolibri24-connect' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>

				<!-- Info Card -->
				<div class="card" style="max-width: 800px; margin-top: 30px;">
					<h3><?php esc_html_e( 'Process Information', 'kolibri24-connect' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'ZIP file will be downloaded from Kolibri24 API', 'kolibri24-connect' ); ?></li>
							<li><?php esc_html_e( 'Files are stored in dated folders for archival purposes', 'kolibri24-connect' ); ?></li>
							<li><?php esc_html_e( 'You can select which properties to include in the merge', 'kolibri24-connect' ); ?></li>
							<li><?php esc_html_e( 'Selected properties are merged into a single properties.xml file', 'kolibri24-connect' ); ?></li>
							<li><?php esc_html_e( 'Output location: /wp-content/uploads/kolibri/properties.xml', 'kolibri24-connect' ); ?></li>
						</ul>
					</div>
				<?php elseif ( 'archive' === $active_tab ) : ?>
					<!-- Archive Tab -->
					<div class="card" style="max-width: 100%; margin-top: 20px;">
						<h2><?php esc_html_e( 'Archived Properties', 'kolibri24-connect' ); ?></h2>
						<p><?php esc_html_e( 'View and manage archived property imports.', 'kolibri24-connect' ); ?></p>
						
						<div id="kolibri24-archive-status"></div>
						<div id="kolibri24-archive-list" class="kolibri24-archive-list"></div>
					</div>
					
					<!-- Archive Preview Modal (Hidden initially) -->
					<div class="card kolibri24-archive-preview" style="max-width: 100%; margin-top: 20px; display:none;">
						<h2><?php esc_html_e( 'Archive Preview', 'kolibri24-connect' ); ?></h2>
						<p><strong id="kolibri24-archive-preview-name"></strong></p>
						
						<div id="kolibri24-archive-property-list" class="kolibri24-property-grid"></div>
						
						<div class="kolibri24-archive-actions">
							<button type="button" id="kolibri24-archive-download-media-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<span class="dashicons dashicons-format-image"></span>
								<?php esc_html_e( 'Download Media', 'kolibri24-connect' ); ?>
							</button>
							<button type="button" id="kolibri24-archive-delete-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Delete Archive', 'kolibri24-connect' ); ?>
							</button>
							<button type="button" id="kolibri24-archive-back-btn" class="button button-primary">
								<?php esc_html_e( 'Back to List', 'kolibri24-connect' ); ?>
							</button>
						</div>
					</div>
				<?php elseif ( 'history' === $active_tab ) : ?>
					<!-- Import History Tab -->
					<div class="card" style="max-width: 100%; margin-top: 20px;">
						<h2><?php esc_html_e( 'Import History', 'kolibri24-connect' ); ?></h2>
						<p><?php esc_html_e( 'View all imported properties with their modification dates.', 'kolibri24-connect' ); ?></p>
						
						<div id="kolibri24-history-status"></div>
						
						<!-- Search and Filter -->
						<div style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
							<input type="text" id="kolibri24-history-search" placeholder="Search by ID or address..." class="regular-text" style="flex: 1; max-width: 400px;" />
							<button type="button" id="kolibri24-history-load-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<?php esc_html_e( 'Load History', 'kolibri24-connect' ); ?>
							</button>
						</div>
						
						<!-- History Table -->
						<div id="kolibri24-history-container" style="overflow-x: auto;">
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th style="width: 15%;"><?php esc_html_e( 'Property ID', 'kolibri24-connect' ); ?></th>
										<th style="width: 45%;"><?php esc_html_e( 'Address', 'kolibri24-connect' ); ?></th>
										<th style="width: 20%;"><?php esc_html_e( 'Last Imported', 'kolibri24-connect' ); ?></th>
										<th style="width: 20%;"><?php esc_html_e( 'Last Modified', 'kolibri24-connect' ); ?></th>
									</tr>
								</thead>
								<tbody id="kolibri24-history-list">
									<tr><td colspan="4" style="text-align: center; padding: 40px; color: #999;"><?php esc_html_e( 'Click "Load History" to display imported properties.', 'kolibri24-connect' ); ?></td></tr>
								</tbody>
							</table>
						</div>
					</div>
				<?php elseif ( 'settings' === $active_tab ) : ?>
					<!-- Settings Tab -->
					<div class="card" style="max-width: 800px; margin-top: 20px;">
						<h2><?php esc_html_e( 'Plugin Settings', 'kolibri24-connect' ); ?></h2>
						<p><?php esc_html_e( 'Configure plugin settings and API endpoints.', 'kolibri24-connect' ); ?></p>
						
						<form id="kolibri24-settings-form" method="post" action="">
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="kolibri24-api-url">
												<?php esc_html_e( 'Kolibri24 API URL', 'kolibri24-connect' ); ?>
											</label>
										</th>
										<td>
											<input 
												type="url" 
												id="kolibri24-api-url" 
												name="kolibri24_api_url" 
												class="regular-text" 
												placeholder="https://sitelink.kolibri24.com/..." 
												value="<?php echo esc_attr( get_option( 'kolibri24_api_url' ) ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'The URL to download the properties ZIP file from Kolibri24 API. Used when importing with "Download from Kolibri24" source.', 'kolibri24-connect' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="kolibri24-trigger-url">
												<?php esc_html_e( 'WP All Import Trigger URL', 'kolibri24-connect' ); ?>
											</label>
										</th>
										<td>
											<input 
												type="url" 
												id="kolibri24-trigger-url" 
												name="kolibri24_trigger_url" 
												class="regular-text" 
												placeholder="https://example.com/..." 
												value="<?php echo esc_attr( get_option( 'kolibri24_trigger_url' ) ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'The URL to trigger WP All Import. This URL is called first to initiate the import process.', 'kolibri24-connect' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="kolibri24-processing-url">
												<?php esc_html_e( 'WP All Import Processing URL', 'kolibri24-connect' ); ?>
											</label>
										</th>
										<td>
											<input 
												type="url" 
												id="kolibri24-processing-url" 
												name="kolibri24_processing_url" 
												class="regular-text" 
												placeholder="https://example.com/..." 
												value="<?php echo esc_attr( get_option( 'kolibri24_processing_url' ) ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'The URL to process the WP All Import. This URL is called to process the imported data and can be called repeatedly until import is complete.', 'kolibri24-connect' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="kolibri24-import-id">
												<?php esc_html_e( 'WP All Import Import ID', 'kolibri24-connect' ); ?>
											</label>
										</th>
										<td>
											<input 
												type="text" 
												id="kolibri24-import-id" 
												name="kolibri24_import_id" 
												class="regular-text" 
												placeholder="1" 
												value="<?php echo esc_attr( get_option( 'kolibri24_import_id' ) ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'The ID of the WP All Import configuration to use for importing properties.', 'kolibri24-connect' ); ?>
											</p>
										</td>
									</tr>
								</tbody>
							</table>
							
							<p>
								<button type="button" id="kolibri24-save-settings-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
									<?php esc_html_e( 'Save Settings', 'kolibri24-connect' ); ?>
								</button>
							</p>
							
							<div id="kolibri24-settings-status"></div>
						</form>
						
						<!-- Required Theme Functions Notice -->
						<div style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #d63638; padding: 20px; margin-top: 30px; border-radius: 4px;">
							<h3 style="margin-top: 0;">⚠️ <?php esc_html_e( 'Required: Add Functions to Theme', 'kolibri24-connect' ); ?></h3>
							<p><?php esc_html_e( 'WP All Import requires custom functions to be in your active theme\'s functions.php file. Add the following code:', 'kolibri24-connect' ); ?></p>
							
							<div style="background: #f6f7f7; padding: 15px; border: 1px solid #c3c4c7; border-radius: 4px; margin: 15px 0;">
								<p style="margin: 0 0 10px 0;"><strong><?php esc_html_e( 'Path:', 'kolibri24-connect' ); ?></strong> <code><?php echo esc_html( get_stylesheet_directory() ); ?>/functions.php</code></p>
								<button type="button" id="kolibri24-copy-functions-btn" class="button button-secondary" style="margin-bottom: 10px;">
									<span class="dashicons dashicons-admin-page" style="margin-top: 3px;"></span>
									<?php esc_html_e( 'Copy Code to Clipboard', 'kolibri24-connect' ); ?>
								</button>
								<pre id="kolibri24-functions-code" style="background: #fff; padding: 15px; overflow: auto; max-height: 400px; border: 1px solid #ddd; font-size: 12px; line-height: 1.6;"><code><?php echo esc_html( file_get_contents( KOLIBRI24_CONNECT_ABSPATH . 'includes/wpai-functions/functions.php' ) ); ?></code></pre>
							</div>
							
							<div class="notice notice-warning inline" style="margin: 15px 0;">
								<p>
									<strong><?php esc_html_e( 'Important:', 'kolibri24-connect' ); ?></strong>
									<?php esc_html_e( 'Without these functions in your theme, WP All Import will not be able to locate the properties.xml file or process the import correctly.', 'kolibri24-connect' ); ?>
								</p>
							</div>
							
							<h4><?php esc_html_e( 'After adding the code:', 'kolibri24-connect' ); ?></h4>
							<ol>
								<li><?php esc_html_e( 'Save your theme\'s functions.php file', 'kolibri24-connect' ); ?></li>
								<li><?php esc_html_e( 'Configure your WP All Import to use the dynamic function wpai_importfile() for the file path', 'kolibri24-connect' ); ?></li>
								<li><?php esc_html_e( 'Test the import from Step 3', 'kolibri24-connect' ); ?></li>
							</ol>
						</div>
					</div>
				<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 * @param    string $hook_suffix The current admin page hook suffix.
		 */
		public function enqueue_styles( $hook_suffix ) {
			wp_enqueue_style( 'kolibri24-connect-admin', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/css/admin.css', array(), '1.1.0', 'all' );
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 * @param    string $hook_suffix The current admin page hook suffix.
		 */
		public function enqueue_scripts( $hook_suffix ) {
		wp_enqueue_script( 'kolibri24-connect-admin', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/js/admin.js', array( 'jquery' ), '1.3.0', true );
			// Localize script with AJAX data.
			wp_localize_script(
				'kolibri24-connect-admin',
				'kolibri24Ajax',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'strings' => array(
						'processing'       => __( 'Processing...', 'kolibri24-connect' ),
						'downloading'      => __( 'Downloading ZIP file...', 'kolibri24-connect' ),
						'extracting'       => __( 'Extracting ZIP file...', 'kolibri24-connect' ),
						'merging'          => __( 'Merging XML files...', 'kolibri24-connect' ),
						'complete'         => __( 'Process completed successfully!', 'kolibri24-connect' ),
						'error'            => __( 'An error occurred. Please try again.', 'kolibri24-connect' ),
						'confirmProcess'   => __( 'This will download and process property data. Continue?', 'kolibri24-connect' ),
					),
				)
			);
		}
	}
}

new Kolibri24_Connect_Admin();
