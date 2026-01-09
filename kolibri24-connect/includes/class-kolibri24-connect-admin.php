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
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				
				<div class="kolibri24-import-container">
					<!-- Step 1: Download & Extract -->
					<div class="card kolibri24-step-1" style="max-width: 800px;">
						<h2><?php esc_html_e( 'Step 1: Download & Extract Properties', 'kolibri24-connect' ); ?></h2>
						<p><?php esc_html_e( 'Click the button below to download the latest property data from Kolibri24 and extract the XML files.', 'kolibri24-connect' ); ?></p>
						
						<p>
							<button type="button" id="kolibri24-download-btn" class="button button-primary button-hero" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Download & Extract Properties', 'kolibri24-connect' ); ?>
							</button>
						</p>

						<div id="kolibri24-status-messages"></div>
						
						<div id="kolibri24-progress" style="display:none;">
							<div class="kolibri24-progress-bar">
								<div class="kolibri24-progress-fill"></div>
							</div>
							<p class="kolibri24-progress-text"></p>
						</div>
					</div>

					<!-- Step 2: Property Selection (Hidden initially) -->
					<div class="card kolibri24-step-2" style="max-width: 100%; margin-top: 20px; display:none;">
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
