																						   <?php if ( 'import' === $active_tab ) : ?>
																						   <p style="margin-top:2em;">
																							   <button id="kolibri24-run-all-import-btn" type="button" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'kolibri24_process_properties' ) ); ?>">
																								   <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Run WP All Import', 'kolibri24-connect' ); ?>
																							   </button>
																							   <span id="kolibri24-run-all-import-status"></span>
																						   </p>
																						   <?php
																						   // Show last import stats/logs if available
																						   $import_stats = get_transient( 'kolibri24_last_import_stats' );
																						   if ( $import_stats ) : ?>
																							   <div class="notice notice-info kolibri24-import-log" style="margin-top:1em;">
																								   <strong><?php esc_html_e( 'Last Import Stats:', 'kolibri24-connect' ); ?></strong><br />
																								   <?php echo esc_html__( 'Total:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['count'] ); ?><br />
																								   <?php echo esc_html__( 'Imported:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['imported'] ); ?><br />
																								   <?php echo esc_html__( 'Created:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['created'] ); ?><br />
																								   <?php echo esc_html__( 'Updated:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['updated'] ); ?><br />
																								   <?php echo esc_html__( 'Skipped:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['skipped'] ); ?><br />
																								   <?php echo esc_html__( 'Deleted:', 'kolibri24-connect' ) . ' ' . esc_html( $import_stats['deleted'] ); ?><br />
																							   </div>
																						   <?php endif; ?>
																						   <?php endif; ?>
															   <?php if ( 'import' === $active_tab ) : ?>
															   <p style="margin-top:2em;">
																   <button id="kolibri24-run-all-import-btn" type="button" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'kolibri24_process_properties' ) ); ?>">
																	   <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Run WP All Import', 'kolibri24-connect' ); ?>
																   </button>
																   <span id="kolibri24-run-all-import-status"></span>
															   </p>
															   <?php endif; ?>
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
											   placeholder="https://your-site.com/wp-all-import/trigger" 
											   value="<?php echo esc_attr( get_option( 'kolibri24_trigger_url' ) ); ?>"
										   />
										   <p class="description">
											   <?php esc_html_e( 'The trigger URL for WP All Import. This will be called to start the import process.', 'kolibri24-connect' ); ?>
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
											   placeholder="https://your-site.com/wp-all-import/process" 
											   value="<?php echo esc_attr( get_option( 'kolibri24_processing_url' ) ); ?>"
										   />
										   <p class="description">
											   <?php esc_html_e( 'The processing URL for WP All Import. This will be called every 2 minutes until the import is finished.', 'kolibri24-connect' ); ?>
										   </p>
									   </td>
								   </tr>
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
						<?php esc_html_e( 'Archive', 'kolibri24-connect' ); ?>
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
				   <div class="card kolibri24-card-full" style="margin-bottom: 20px;">
					<h2><?php esc_html_e( 'Current Properties File', 'kolibri24-connect' ); ?></h2>
					<?php if ( is_array( $props_info ) && ! empty( $props_info['output_file'] ) ) : ?>
						<ul>
							<li>
								<strong><?php esc_html_e( 'Number of properties:', 'kolibri24-connect' ); ?></strong>
								<?php echo esc_html( (string) ( $props_info['total_properties'] ?? 0 ) ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Created:', 'kolibri24-connect' ); ?></strong>
								<?php 
									$ts = intval( $props_info['created_at'] ?? 0 );
									echo esc_html( $ts ? date_i18n( 'Y-m-d H:i', $ts ) : __( 'N/A', 'kolibri24-connect' ) );
								?>
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
						<p><?php esc_html_e( 'No merged properties file found yet. Complete Step 1 and Step 2 to generate properties.xml.', 'kolibri24-connect' ); ?></p>
					<?php endif; ?>
					<?php if ( is_array( $props_info ) && ! empty( $props_info['output_file'] ) ) : ?>
						   <div class="kolibri24-properties-actions">
							   <button type="button" id="kolibri24-run-import-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								   <span class="dashicons dashicons-update"></span>
								   <?php esc_html_e( 'Run WP All Import', 'kolibri24-connect' ); ?>
							   </button>
							   <div id="kolibri24-run-import-status" style="margin-top:10px;"></div>
						   </div>
					<?php endif; ?>
				</div>
				<!-- Step 1: Select Import Source & Download & Extract -->
				   <div class="card kolibri24-step-1 kolibri24-card-full">
					<h2><?php esc_html_e( 'Step 1: Select Import Source & Extract Properties', 'kolibri24-connect' ); ?></h2>
				<div class="kolibri24-source-selection">
					<div class="kolibri24-source-options">
						<div class="kolibri24-source-option">
							<label>
								<span class="dashicons dashicons-cloud"></span>
								<input type="radio" name="kolibri24-import-source" value="kolibri24" checked />
								<strong><?php esc_html_e( 'Download from Kolibri24', 'kolibri24-connect' ); ?></strong>
							</label>
							<p class="description"><?php esc_html_e( 'Download the latest property data directly from the Kolibri24 API.', 'kolibri24-connect' ); ?></p>
						</div>

						<div class="kolibri24-source-option">
							<label>
								<span class="dashicons dashicons-admin-links"></span>
								<input type="radio" name="kolibri24-import-source" value="remote-url" />
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
								<input type="radio" name="kolibri24-import-source" value="upload" />
								<strong><?php esc_html_e( 'Upload Local ZIP File', 'kolibri24-connect' ); ?></strong>
							</label>
							<p class="description"><?php esc_html_e( 'Upload a ZIP file from your computer.', 'kolibri24-connect' ); ?></p>
							<div id="kolibri24-file-upload-field" class="kolibri24-collapsible">
								<input type="file" id="kolibri24-file-upload" accept=".zip" />
								<p class="description"><?php esc_html_e( 'Maximum file size: 100MB', 'kolibri24-connect' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Download & Extract Button -->
				<div class="kolibri24-actions">
					<button type="button" id="kolibri24-download-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Download & Extract Properties', 'kolibri24-connect' ); ?>
					</button>
				</div>

						<div id="kolibri24-status-messages"></div>
						
						<div id="kolibri24-progress" style="display:none;">
							<div class="kolibri24-progress-bar">
								<div class="kolibri24-progress-fill"></div>
							</div>
							<p class="kolibri24-progress-text"></p>
						</div>
					</div>

					<!-- Step 2: Property Selection (Hidden initially) -->
					   <div class="card kolibri24-step-2 kolibri24-card-full" style="margin-top: 20px; display:none;">
						<h2><?php esc_html_e( 'Step 2: Select Properties to Merge', 'kolibri24-connect' ); ?></h2>
						<p><?php esc_html_e( 'Select the properties you want to merge into the final properties.xml file.', 'kolibri24-connect' ); ?></p>
						
						<div class="kolibri24-selection-controls">
							<label>
								<input type="checkbox" id="kolibri24-select-all" />
								<strong><?php esc_html_e( 'Select All', 'kolibri24-connect' ); ?></strong>
							</label>
							<span class="kolibri24-selection-count"></span>
						</div>

						<div id="kolibri24-property-list" class="kolibri24-property-grid"></div>

						<div class="kolibri24-merge-actions">
							<button type="button" id="kolibri24-merge-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>" disabled>
								<span class="dashicons dashicons-database-import" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Merge Selected Properties', 'kolibri24-connect' ); ?>
							</button>
							<button type="button" id="kolibri24-cancel-btn" class="button button-secondary">
								<?php esc_html_e( 'Cancel', 'kolibri24-connect' ); ?>
							</button>
						</div>

						<div id="kolibri24-merge-status"></div>
					</div>

					<!-- Info Card -->
					<div class="card" style="max-width: 800px; margin-top: 20px;">
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
								</tbody>
							</table>
							
							<p>
								<button type="button" id="kolibri24-save-settings-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
									<?php esc_html_e( 'Save Settings', 'kolibri24-connect' ); ?>
								</button>
							</p>
							
							<div id="kolibri24-settings-status"></div>
						</form>
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
			wp_enqueue_style( 'kolibri24-connect-admin', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/css/admin.css', array(), '1.0.0', 'all' );
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 * @param    string $hook_suffix The current admin page hook suffix.
		 */
		public function enqueue_scripts( $hook_suffix ) {
			wp_enqueue_script( 'kolibri24-connect-admin', untrailingslashit( plugins_url( '/', KOLIBRI24_CONNECT_PLUGIN_FILE ) ) . '/assets/js/admin.js', array( 'jquery' ), '1.0.0', false );
			
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
