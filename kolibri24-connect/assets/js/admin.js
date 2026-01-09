/**
 * Kolibri24 Connect Admin JavaScript
 * 
 * Handles AJAX processing for property import functionality with preview/selection.
 */
jQuery(function ($) {
    'use strict';

    /**
     * Property Processing Handler
     */
    var Kolibri24PropertyProcessor = {
        
        // UI Elements
        downloadBtn: null,
        mergeBtn: null,
        cancelBtn: null,
        selectAllCheckbox: null,
        statusDiv: null,
        mergeStatusDiv: null,
        progressDiv: null,
        progressText: null,
        progressFill: null,
        propertyList: null,
        step1Container: null,
        step2Container: null,
        selectionCount: null,
        
        // Data
        properties: [],
        
        /**
         * Initialize the processor
         */
        init: function() {
            this.downloadBtn = $('#kolibri24-download-btn');
            this.mergeBtn = $('#kolibri24-merge-btn');
            this.cancelBtn = $('#kolibri24-cancel-btn');
            this.selectAllCheckbox = $('#kolibri24-select-all');
            this.statusDiv = $('#kolibri24-status-messages');
            this.mergeStatusDiv = $('#kolibri24-merge-status');
            this.progressDiv = $('#kolibri24-progress');
            this.progressText = $('.kolibri24-progress-text');
            this.progressFill = $('.kolibri24-progress-fill');
            this.propertyList = $('#kolibri24-property-list');
            this.step1Container = $('.kolibri24-step-1');
            this.step2Container = $('.kolibri24-step-2');
            this.selectionCount = $('.kolibri24-selection-count');
            
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Download & Extract button
            this.downloadBtn.on('click', function(e) {
                e.preventDefault();
                self.downloadAndExtract();
            });
            
            // Merge button
            this.mergeBtn.on('click', function(e) {
                e.preventDefault();
                self.mergeSelectedProperties();
            });
            
            // Cancel button
            this.cancelBtn.on('click', function(e) {
                e.preventDefault();
                self.resetToStep1();
            });
            
            // Select all checkbox
            this.selectAllCheckbox.on('change', function() {
                self.toggleSelectAll($(this).is(':checked'));
            });
            
            // Property checkbox delegation
            this.propertyList.on('change', '.kolibri24-property-checkbox', function() {
                self.updateSelectionCount();
                self.updateMergeButton();
            });
        },

        /**
         * Show progress indicator
         */
        showProgress: function(message) {
            this.progressDiv.show();
            this.progressText.text(message);
        },

        /**
         * Hide progress indicator
         */
        hideProgress: function() {
            this.progressDiv.hide();
        },

        /**
         * Update progress bar
         */
        updateProgress: function(percent) {
            this.progressFill.css('width', percent + '%');
        },

        /**
         * Display status message
         */
        showMessage: function(message, type, container) {
            container = container || this.statusDiv;
            var messageClass = 'notice notice-' + type;
            var messageHtml = '<div class="' + messageClass + ' is-dismissible"><p>' + message + '</p></div>';
            
            container.html(messageHtml);
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $('.notice', container).fadeOut();
                }, 5000);
            }
        },

        /**
         * Disable download button
         */
        disableDownloadButton: function() {
            this.downloadBtn.prop('disabled', true).addClass('disabled');
            this.downloadBtn.find('.dashicons').removeClass('dashicons-download').addClass('dashicons-update spin');
        },

        /**
         * Enable download button
         */
        enableDownloadButton: function() {
            this.downloadBtn.prop('disabled', false).removeClass('disabled');
            this.downloadBtn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-download');
        },

        /**
         * Download and extract properties
         */
        downloadAndExtract: function() {
            var self = this;
            var nonce = this.downloadBtn.data('nonce');

            // Clear previous messages
            this.statusDiv.empty();
            this.mergeStatusDiv.empty();

            // Disable button and show progress
            this.disableDownloadButton();
            this.showProgress('Downloading and extracting...');
            this.updateProgress(10);

            // Make AJAX request
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kolibri24_download_extract',
                    nonce: nonce
                },
                beforeSend: function() {
                    self.updateProgress(30);
                },
                success: function(response) {
                    self.updateProgress(100);
                    
                    if (response.success) {
                        self.handleDownloadSuccess(response.data);
                    } else {
                        self.handleError(response.data, self.statusDiv);
                    }
                },
                error: function(xhr, status, error) {
                    self.updateProgress(0);
                    self.handleAjaxError(xhr, status, error, self.statusDiv);
                },
                complete: function() {
                    self.enableDownloadButton();
                    setTimeout(function() {
                        self.hideProgress();
                    }, 1000);
                }
            });
        },

        /**
         * Handle successful download/extract
         */
        handleDownloadSuccess: function(data) {
            this.showMessage(data.message, 'success', this.statusDiv);
            
            // Store properties
            this.properties = data.properties;
            
            // Render property list
            this.renderPropertyList(data.properties);
            
            // Show step 2
            this.step2Container.slideDown();
            
            // Scroll to property list
            $('html, body').animate({
                scrollTop: this.step2Container.offset().top - 50
            }, 500);
        },

        /**
         * Render property list
         */
        renderPropertyList: function(properties) {
            var html = '';
            
            properties.forEach(function(property) {
                var imageHtml = property.image ? 
                    '<img src="' + property.image + '" alt="' + property.address + '" />' :
                    '<div class="kolibri24-no-image"><span class="dashicons dashicons-admin-home"></span></div>';
                
                html += '<div class="kolibri24-property-item">';
                html += '  <div class="kolibri24-property-checkbox-container">';
                html += '    <input type="checkbox" class="kolibri24-property-checkbox" value="' + property.file_path + '" id="property-' + property.index + '" />';
                html += '    <label for="property-' + property.index + '"></label>';
                html += '  </div>';
                html += '  <div class="kolibri24-property-image">' + imageHtml + '</div>';
                html += '  <div class="kolibri24-property-details">';
                html += '    <h3 class="kolibri24-property-id">ID: ' + property.property_id + '</h3>';
                html += '    <p class="kolibri24-property-address"><strong>Address:</strong> ' + property.address + '</p>';
                html += '    <p class="kolibri24-property-city"><strong>City:</strong> ' + property.city + '</p>';
                html += '    <p class="kolibri24-property-price"><strong>Price:</strong> ' + property.price + '</p>';
                html += '    <p class="kolibri24-property-file"><small>' + property.file_name + '</small></p>';
                html += '  </div>';
                html += '</div>';
            });
            
            this.propertyList.html(html);
            this.updateSelectionCount();
        },

        /**
         * Toggle select all
         */
        toggleSelectAll: function(checked) {
            $('.kolibri24-property-checkbox').prop('checked', checked);
            this.updateSelectionCount();
            this.updateMergeButton();
        },

        /**
         * Update selection count
         */
        updateSelectionCount: function() {
            var total = $('.kolibri24-property-checkbox').length;
            var selected = $('.kolibri24-property-checkbox:checked').length;
            
            this.selectionCount.text(selected + ' of ' + total + ' selected');
            
            // Update select all checkbox state
            this.selectAllCheckbox.prop('checked', selected === total && total > 0);
        },

        /**
         * Update merge button state
         */
        updateMergeButton: function() {
            var selected = $('.kolibri24-property-checkbox:checked').length;
            this.mergeBtn.prop('disabled', selected === 0);
        },

        /**
         * Merge selected properties
         */
        mergeSelectedProperties: function() {
            var self = this;
            var nonce = this.mergeBtn.data('nonce');
            var selectedFiles = [];
            
            $('.kolibri24-property-checkbox:checked').each(function() {
                selectedFiles.push($(this).val());
            });
            
            if (selectedFiles.length === 0) {
                this.showMessage('Please select at least one property to merge.', 'error', this.mergeStatusDiv);
                return;
            }

            // Clear previous messages
            this.mergeStatusDiv.empty();

            // Disable buttons
            this.mergeBtn.prop('disabled', true).addClass('disabled');
            this.mergeBtn.find('.dashicons').removeClass('dashicons-database-import').addClass('dashicons-update spin');

            // Make AJAX request
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kolibri24_merge_properties',
                    nonce: nonce,
                    selected_files: selectedFiles
                },
                success: function(response) {
                    if (response.success) {
                        self.handleMergeSuccess(response.data);
                    } else {
                        self.handleError(response.data, self.mergeStatusDiv);
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, status, error, self.mergeStatusDiv);
                },
                complete: function() {
                    self.mergeBtn.prop('disabled', false).removeClass('disabled');
                    self.mergeBtn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-database-import');
                }
            });
        },

        /**
         * Handle successful merge
         */
        handleMergeSuccess: function(data) {
            var message = '<strong>' + data.message + '</strong>';
            
            if (data.processed) {
                message += '<p><strong>' + data.processed + '</strong> properties merged successfully.</p>';
            }
            
            if (data.output_file) {
                message += '<p>Output file: <code>' + data.output_file + '</code></p>';
            }
            
            this.showMessage(message, 'success', this.mergeStatusDiv);
            
            // Optionally reset after success
            setTimeout(function() {
                // Could reset to step 1 or just clear selections
            }, 3000);
        },

        /**
         * Handle error response
         */
        handleError: function(data, container) {
            var message = data.message || 'An error occurred. Please try again.';
            
            if (data.step) {
                message = '<strong>Error in ' + data.step + ' step:</strong> ' + message;
            }
            
            this.showMessage(message, 'error', container);
        },

        /**
         * Handle AJAX error
         */
        handleAjaxError: function(xhr, status, error, container) {
            var message = 'An error occurred. Please try again.';
            
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            } else if (error) {
                message += ' (' + error + ')';
            }
            
            this.showMessage(message, 'error', container);
        },

        /**
         * Reset to step 1
         */
        resetToStep1: function() {
            this.step2Container.slideUp();
            this.propertyList.empty();
            this.statusDiv.empty();
            this.mergeStatusDiv.empty();
            this.properties = [];
            this.selectAllCheckbox.prop('checked', false);
        }
    };

    // Initialize on document ready
    if ($('#kolibri24-download-btn').length > 0) {
        Kolibri24PropertyProcessor.init();
    }
});