/**
 * Kolibri24 Connect Admin JavaScript
 * 
 * Handles AJAX processing for property import functionality with step-based navigation.
 */
jQuery(function ($) {
    'use strict';

    // Step navigation state
    var currentStep = 1;

    function goToStep(stepNum) {
        // Hide all steps
        $('.kolibri24-step-container').hide();
        
        // Show target step
        $('.kolibri24-step-' + stepNum).show();
        
        // Update step indicator
        $('.kolibri24-step-badge').removeClass('kolibri24-step-active');
        $('.kolibri24-step-badge[data-step="' + stepNum + '"]').addClass('kolibri24-step-active');
        
        currentStep = stepNum;
        
        // Scroll to top of step
        $('html, body').animate({
            scrollTop: $('.kolibri24-step-indicator').offset().top - 50
        }, 300);
    }

    // Back buttons
    $(document).on('click', '#kolibri24-step-2-prev-btn', function(e) {
        e.preventDefault();
        goToStep(1);
    });

    $(document).on('click', '#kolibri24-step-3-prev-btn', function(e) {
        e.preventDefault();
        goToStep(2);
    });

    // Run Import button with address confirmation
    $(document).on('click', '#kolibri24-run-import-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var nonce = $btn.data('nonce');
        var statusDiv = $('#kolibri24-run-import-status');
        var pollInterval = 2 * 60 * 1000; // 2 minutes
        var maxPolls = 30; // 1 hour max
        var pollCount = 0;

        function showMessage(message, type) {
            var className = 'notice notice-' + type;
            statusDiv.html('<div class="' + className + '"><p>' + message + '</p></div>');
        }

        function pollProcessingUrl() {
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_run_all_import_urls',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Check if import is finished
                        var finished = false;
                        if (typeof response.data.processing_response === 'string') {
                            finished = response.data.processing_response.indexOf('import complete') !== -1 || 
                                      response.data.processing_response.indexOf('finished') !== -1;
                        }

                        var detail = '';
                        if (response.data.trigger_status || response.data.processing_status) {
                            detail = '<br><small>Trigger (' + (response.data.trigger_method || 'n/a') + '): ' + (response.data.trigger_status || 'n/a') + ', Processing (' + (response.data.processing_method || 'n/a') + '): ' + (response.data.processing_status || 'n/a') + '</small>';
                        }
                        showMessage((response.data.message || 'Import call sent.') + detail, 'success');

                        if (!finished && pollCount < maxPolls) {
                            pollCount++;
                            setTimeout(pollProcessingUrl, pollInterval);
                        } else if (!finished) {
                            showMessage('Import polling stopped after 1 hour. Please check import status manually.', 'warning');
                        } else {
                            showMessage('Import finished!', 'success');
                        }
                    } else {
                        var errorMsg = response.data.message || 'Error during import.';
                        if (response.data.error) {
                            errorMsg += ' (' + response.data.error + ')';
                        }
                        showMessage(errorMsg, 'error');
                    }
                },
                error: function() {
                    showMessage('AJAX error during import processing.', 'error');
                }
            });
        }

        // First check if records are selected
        $.ajax({
            url: kolibri24Ajax.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'kolibri24_get_selected_records',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.count > 0) {
                    // Build confirmation message with addresses
                    var confirmLines = ['Import properties:'];
                    
                    // Iterate through record array in order
                    $.each(response.data.record_array, function(index, position) {
                        var address = response.data.addresses[position] || 'N/A';
                        confirmLines.push(position + '. ' + address);
                    });
                    
                    confirmLines.push('\nContinue with import?');
                    var confirmMsg = confirmLines.join('\n');
                    
                    if (confirm(confirmMsg)) {
                        // Disable button and start polling
                        $btn.prop('disabled', true);
                        showMessage('Starting WP All Import...', 'info');
                        pollProcessingUrl();
                    }
                } else {
                    alert('No record positions selected. Please go back to Step 2 and select records.');
                }
            },
            error: function() {
                showMessage('Error checking selected records.', 'error');
            }
        });
    });

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
            
            // Change Source button
            $('#kolibri24-change-source-btn').on('click', function(e) {
                e.preventDefault();
                $('#kolibri24-source-selector').slideDown(300);
            });

            // Confirm Source button
            $('#kolibri24-confirm-source-btn').on('click', function(e) {
                e.preventDefault();
                var selectedSource = $('input[name="kolibri24-import-source-radio"]:checked').val();
                self.updateSourceDisplay(selectedSource);
                $('input[name="kolibri24-import-source-radio"]').prop('disabled', false);
                $('input[name="kolibri24-import-source-radio"]').each(function() {
                    $('input[name="kolibri24-import-source"]').val($(this).val());
                });
                $('input[name="kolibri24-import-source"]').val(selectedSource);
                self.handleSourceChange(selectedSource);
                $('#kolibri24-source-selector').slideUp(300);
            });

            // Cancel Source button
            $('#kolibri24-cancel-source-btn').on('click', function(e) {
                e.preventDefault();
                $('#kolibri24-source-selector').slideUp(300);
            });

            // Source selection radio buttons
            $('input[name="kolibri24-import-source-radio"]').on('change', function() {
                self.handleSourceChange($(this).val());
            });
            
            // Download & Extract button
            this.downloadBtn.on('click', function(e) {
                e.preventDefault();
                self.downloadAndExtract();
            });
            
            // Load archive for Step 2
            $(document).on('click', '#kolibri24-step2-load-archive', function(e) {
                e.preventDefault();
                self.loadArchiveForSelection();
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
         * Handle source selection change
         */
        updateSourceDisplay: function(source) {
            var icons = {
                'kolibri24': 'dashicons-cloud',
                'remote-url': 'dashicons-admin-links',
                'upload': 'dashicons-upload',
                'previous-archive': 'dashicons-archive'
            };

            var labels = {
                'kolibri24': 'Download from Kolibri24',
                'remote-url': 'Download from Remote URL',
                'upload': 'Upload Local ZIP File',
                'previous-archive': 'Use Previous Archive'
            };

            var descriptions = {
                'kolibri24': 'Download the latest property data directly from the Kolibri24 API.',
                'remote-url': 'Provide a custom URL to download a ZIP file.',
                'upload': 'Upload a ZIP file from your computer.',
                'previous-archive': 'Load a previously downloaded and archived ZIP file.'
            };

            $('.kolibri24-default-source').empty().html(
                '<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">' +
                '    <span class="dashicons ' + icons[source] + '" style="font-size: 40px; width: 40px; height: 40px; color: #0073aa;"></span>' +
                '    <div>' +
                '        <h3 style="margin: 0;">' + labels[source] + '</h3>' +
                '        <p style="margin: 5px 0 0 0; color: #666;">' + descriptions[source] + '</p>' +
                '    </div>' +
                '</div>'
            );
        },

        /**
         * Handle source change
         */
        handleSourceChange: function(source) {
            var remoteField = $('#kolibri24-remote-url-field');
            var uploadField = $('#kolibri24-file-upload-field');

            remoteField.removeClass('is-open');
            uploadField.removeClass('is-open');

            if (source === 'remote-url') {
                remoteField.addClass('is-open');
            } else if (source === 'upload') {
                uploadField.addClass('is-open');
            }
        },

        /**
         * Load list of previous archives
         */
        loadPreviousArchives: function() {
            var nonce = this.downloadBtn.data('nonce');
            var archiveSelect = $('#kolibri24-previous-archive');

            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_get_previous_archives',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data.archives.length > 0) {
                        archiveSelect.empty().append('<option value="">-- Select an archive --</option>');
                        
                        $.each(response.data.archives, function(index, archive) {
                            var option = '<option value="' + archive.path + '">' + archive.name + ' (' + archive.count + ' files, ' + archive.date + ')</option>';
                            archiveSelect.append(option);
                        });
                    } else {
                        archiveSelect.empty().append('<option value="">No archives available</option>');
                    }
                },
                error: function() {
                    archiveSelect.empty().append('<option value="">Error loading archives</option>');
                }
            });
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
            var source = $('input[name="kolibri24-import-source"]').val() || 'kolibri24';
            
            // Validate input based on source
            if (source === 'remote-url') {
                var remoteUrl = $('#kolibri24-remote-url').val();
                if (!remoteUrl) {
                    this.showMessage('Please enter a valid URL', 'error', this.statusDiv);
                    return;
                }
            } else if (source === 'upload') {
                var fileInput = $('#kolibri24-file-upload')[0];
                if (!fileInput.files || fileInput.files.length === 0) {
                    this.showMessage('Please select a ZIP file to upload', 'error', this.statusDiv);
                    return;
                }
            }

            // Clear previous messages
            this.statusDiv.empty();
            this.mergeStatusDiv.empty();

            // Disable button and show progress
            this.disableDownloadButton();
            
            this.showProgress('Downloading and extracting...');
            this.updateProgress(10);

            // Prepare data
            var ajaxData = {
                action: 'kolibri24_download_extract',
                nonce: nonce,
                source: source
            };
            
            // For remote URL, add URL parameter
            if (source === 'remote-url') {
                ajaxData.remote_url = $('#kolibri24-remote-url').val();
            }
            
            // Make AJAX request
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: ajaxData,
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
         * Download and extract properties (for file upload)
         */
        downloadAndExtractFile: function() {
            var self = this;
            var nonce = this.downloadBtn.data('nonce');
            var fileInput = $('#kolibri24-file-upload')[0];
            
            if (!fileInput.files || fileInput.files.length === 0) {
                this.showMessage('Please select a ZIP file to upload', 'error', this.statusDiv);
                return;
            }

            // Clear previous messages
            this.statusDiv.empty();
            this.mergeStatusDiv.empty();

            // Disable button and show progress
            this.disableDownloadButton();
            this.showProgress('Processing uploaded file...');
            this.updateProgress(10);

            // Create FormData for file upload
            var formData = new FormData();
            formData.append('action', 'kolibri24_download_extract');
            formData.append('nonce', nonce);
            formData.append('source', 'upload');
            formData.append('kolibri24_file', fileInput.files[0]);

            // Make AJAX request with FormData
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
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

            // After step 1 completes, move to step 2 and prompt archive selection
            var self = this;
            setTimeout(function() {
                goToStep(2);
                self.loadArchivesForSelection();
            }, 800);
        },

        /**
         * Load archives list for Step 2 archive selector.
         */
        loadArchivesForSelection: function() {
            var nonce = this.downloadBtn.data('nonce');
            var select = $('#kolibri24-step2-archive-select');
            var status = $('#kolibri24-step2-archive-status');
            if (status.length) {
                status.html('<span class="description">Loading archives...</span>');
            }
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_get_archives',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        select.empty();
                        select.append('<option value="">-- Select an archive --</option>');
                        if (response.data.archives && response.data.archives.length) {
                            $.each(response.data.archives, function(_, archive) {
                                select.append('<option value="' + archive.path + '">' + archive.name + ' (' + archive.count + ' files)</option>');
                            });
                            if (status.length) {
                                status.html('');
                            }
                        } else {
                            if (status.length) {
                                status.html('<span class="description">No archives found. Complete Step 1 first.</span>');
                            }
                        }
                    } else if (status.length) {
                        status.html('<span class="description">' + (response.data.message || 'Failed to load archives.') + '</span>');
                    }
                },
                error: function() {
                    if (status.length) {
                        status.html('<span class="description">Error loading archives.</span>');
                    }
                }
            });
        },

        /**
         * Render property list
         */
        renderPropertyList: function(properties) {
            var html = '';
            
            properties.forEach(function(property) {
                var imageHtml = property.image_url ? 
                    '<img src="' + property.image_url + '" alt="' + property.address + '" />' :
                    '<div class="kolibri24-no-image"><span class="dashicons dashicons-admin-home"></span></div>';
                
                var updateNotice = '';
                var changeDetails = '';
                if (property.changed_fields && property.changed_fields.length) {
                    changeDetails = '<ul style="margin: 6px 0 0 16px; padding: 0; color: #666;">';
                    property.changed_fields.forEach(function(change) {
                        changeDetails += '<li>' + change.field + ': ' + (change.old || '—') + ' → ' + (change.new || '—') + '</li>';
                    });
                    changeDetails += '</ul>';
                }

                if (property.is_updated) {
                    updateNotice = '<div class="notice-warning" style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 8px; margin: 10px 0; border-radius: 2px;">' +
                        '<p style="margin: 0; color: #666;"><strong>⚠️ ' + property.update_message + '</strong></p>' +
                        changeDetails +
                        '</div>';
                }
                
                html += '<div class="kolibri24-property-item">';
                html += '  <div class="kolibri24-property-checkbox-container">';
                html += '    <input type="checkbox" class="kolibri24-property-checkbox" data-record="' + property.record_position + '" id="property-' + property.record_position + '" />';
                html += '    <label for="property-' + property.record_position + '"></label>';
                html += '  </div>';
                html += '  <div class="kolibri24-property-image">' + imageHtml + '</div>';
                html += '  <div class="kolibri24-property-details">';
                html += '    <h3 class="kolibri24-property-id">Position ' + property.record_position + ' - ID: ' + property.property_id + '</h3>';
                html += updateNotice;
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
            var selectedRecords = [];

            $('.kolibri24-property-checkbox:checked').each(function() {
                selectedRecords.push($(this).data('record'));
            });

            if (selectedRecords.length === 0) {
                this.showMessage('Please select at least one property to import.', 'error', this.mergeStatusDiv);
                return;
            }

            this.mergeStatusDiv.empty();
            this.mergeBtn.prop('disabled', true).addClass('disabled');
            this.mergeBtn.find('.dashicons').removeClass('dashicons-database-import').addClass('dashicons-update spin');

            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kolibri24_merge_properties',
                    nonce: nonce,
                    selected_records: selectedRecords.join(',')
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
         * Load archive for Step 2: set selected archive and fetch previews from properties.xml
         */
        loadArchiveForSelection: function() {
            var archivePath = $('#kolibri24-step2-archive-select').val();
            var nonce = this.downloadBtn.data('nonce');
            var status = $('#kolibri24-step2-archive-status');
            var self = this;

            if (!archivePath) {
                status.html('<span class="description">Please select an archive.</span>');
                return;
            }

            status.html('<span class="description">Loading archive...</span>');

            // First, set the selected archive option
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_set_selected_archive',
                    nonce: nonce,
                    archive_path: archivePath
                },
                success: function(setResp) {
                    if (!setResp.success) {
                        status.html('<span class="description">' + (setResp.data.message || 'Failed to set archive.') + '</span>');
                        return;
                    }
                    // Then load preview from properties.xml
                    $.ajax({
                        url: kolibri24Ajax.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'kolibri24_view_selected_archive',
                            nonce: nonce
                        },
                        success: function(resp) {
                            if (resp.success) {
                                status.html('<span class="description">Archive loaded.</span>');
                                self.properties = resp.data.properties || [];
                                self.renderPropertyList(self.properties);
                            } else {
                                status.html('<span class="description">' + (resp.data.message || 'Failed to load archive preview.') + '</span>');
                            }
                        },
                        error: function() {
                            status.html('<span class="description">Error loading archive preview.</span>');
                        }
                    });
                },
                error: function() {
                    status.html('<span class="description">Error setting archive.</span>');
                }
            });
        },

        /**
         * Handle successful merge
         */
        handleMergeSuccess: function(data) {
            var message = '<strong>' + data.message + '</strong>';
            
            if (data.count) {
                message += '<p><strong>' + data.count + '</strong> record positions saved for import.</p>';
            }
            
            this.showMessage(message, 'success', this.mergeStatusDiv);
            
            // Auto-navigate to Step 3
            setTimeout(function() {
                goToStep(3);
                // Load Step 3 preview from selected archive's properties.xml
                Kolibri24PropertyProcessor.loadStep3Preview();
            }, 1000);
        },

        /**
         * Load Step 3 preview from the selected archive's properties.xml
         */
        loadStep3Preview: function() {
            var nonce = $('#kolibri24-run-import-btn').data('nonce');
            var container = $('#kolibri24-step-3-property-list');
            var statusDiv = $('#kolibri24-step-3-status');

            if (statusDiv.length) {
                statusDiv.html('<div class="notice notice-info"><p>Loading selected archive preview...</p></div>');
            }

            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_view_selected_archive',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (container.length) {
                            Kolibri24PropertyProcessor.renderStep3PropertyList(response.data.properties);
                        }
                        if (statusDiv.length) {
                            statusDiv.html('');
                        }
                    } else {
                        if (statusDiv.length) {
                            statusDiv.html('<div class="notice notice-error"><p>' + (response.data.message || 'Failed to load preview.') + '</p></div>');
                        }
                    }
                },
                error: function() {
                    if (statusDiv.length) {
                        statusDiv.html('<div class="notice notice-error"><p>AJAX error while loading preview.</p></div>');
                    }
                }
            });
        },

        /**
         * Render Step 3 property list (read-only preview)
         */
        renderStep3PropertyList: function(properties) {
            var container = $('#kolibri24-step-3-property-list');
            if (!container.length) { return; }

            var html = '';
            properties.forEach(function(property) {
                var imageHtml = property.image_url ? 
                    '<img src="' + property.image_url + '" alt="' + (property.address || '') + '" />' :
                    '<div class="kolibri24-no-image"><span class="dashicons dashicons-admin-home"></span></div>';

                var updateNotice = '';
                var changeDetails = '';
                if (property.changed_fields && property.changed_fields.length) {
                    changeDetails = '<ul style="margin: 6px 0 0 16px; padding: 0; color: #666;">';
                    property.changed_fields.forEach(function(change) {
                        changeDetails += '<li>' + change.field + ': ' + (change.old || '—') + ' → ' + (change.new || '—') + '</li>';
                    });
                    changeDetails += '</ul>';
                }
                if (property.is_updated) {
                    updateNotice = '<div class="notice-warning" style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 8px; margin: 10px 0; border-radius: 2px;">' +
                        '<p style="margin: 0; color: #666;"><strong>⚠️ ' + property.update_message + '</strong></p>' +
                        changeDetails +
                        '</div>';
                }

                html += '<div class="kolibri24-property-item">';
                html += '  <div class="kolibri24-property-image">' + imageHtml + '</div>';
                html += '  <div class="kolibri24-property-details">';
                html += '    <h3 class="kolibri24-property-id">ID: ' + (property.property_id || '') + '</h3>';
                html +=      updateNotice;
                html += '    <p class="kolibri24-property-address"><strong>Address:</strong> ' + (property.address || '') + '</p>';
                html += '    <p class="kolibri24-property-city"><strong>City:</strong> ' + (property.city || '') + '</p>';
                html += '    <p class="kolibri24-property-price"><strong>Price:</strong> ' + (property.price || '') + '</p>';
                html += '  </div>';
                html += '</div>';
            });

            container.html(html);
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
    
    /**
     * Archive Manager
     */
    var Kolibri24ArchiveManager = {
        
        // UI Elements
        archiveList: null,
        archiveStatus: null,
        archivePreview: null,
        archivePropertyList: null,
        archivePreviewName: null,
        archiveDeleteBtn: null,
        archiveBackBtn: null,
        
        // Data
        currentArchivePath: null,
        
        /**
         * Initialize the archive manager
         */
        init: function() {
            this.archiveList = $('#kolibri24-archive-list');
            this.archiveStatus = $('#kolibri24-archive-status');
            this.archivePreview = $('.kolibri24-archive-preview');
            this.archivePropertyList = $('#kolibri24-archive-property-list');
            this.archivePreviewName = $('#kolibri24-archive-preview-name');
            this.archiveDeleteBtn = $('#kolibri24-archive-delete-btn');
            this.archiveBackBtn = $('#kolibri24-archive-back-btn');
            
            this.bindEvents();
            this.loadArchives();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // View archive button
            $(document).on('click', '.kolibri24-archive-view-btn', function(e) {
                e.preventDefault();
                var archivePath = $(this).data('archive-path');
                self.viewArchive(archivePath);
            });
            
            // Delete archive button (from list)
            $(document).on('click', '.kolibri24-archive-delete-list-btn', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this archive? This action cannot be undone.')) {
                    return;
                }
                var archivePath = $(this).data('archive-path');
                self.deleteArchive(archivePath, false);
            });
            
            // Delete archive button (from preview)
            this.archiveDeleteBtn.on('click', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this archive? This action cannot be undone.')) {
                    return;
                }
                self.deleteArchive(self.currentArchivePath, true);
            });
            
            // Back button
            this.archiveBackBtn.on('click', function(e) {
                e.preventDefault();
                self.hidePreview();
            });

            // Download Media button
            $(document).on('click', '#kolibri24-download-media-btn', function(e) {
                e.preventDefault();
                self.downloadArchiveMedia();
            });
        },
                /**
                 * Download media for selected properties in archive preview
                 */
                downloadArchiveMedia: function() {
                    var self = this;
                    var nonce = this.archiveDeleteBtn.data('nonce');
                    var selectedFiles = [];
                    // Collect selected property XML files
                    this.archivePropertyList.find('.kolibri24-property-checkbox:checked').each(function() {
                        selectedFiles.push($(this).val());
                    });
                    if (selectedFiles.length === 0) {
                        this.showError('Please select at least one property to download media.');
                        return;
                    }
                    this.showLoading('Downloading media for selected properties...');
                    $.ajax({
                        url: kolibri24Ajax.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'kolibri24_download_archive_media',
                            nonce: nonce,
                            archive_path: this.currentArchivePath,
                            selected_files: selectedFiles
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showSuccess(response.data.message || 'Media downloaded successfully.');
                            } else {
                                self.showError(response.data.message || 'Failed to download media.');
                            }
                        },
                        error: function() {
                            self.showError('An error occurred while downloading media.');
                        }
                    });
                },
        
        /**
         * Load archives list
         */
        loadArchives: function() {
            var self = this;
            var nonce = this.archiveDeleteBtn.data('nonce');
            
            this.showLoading('Loading archives...');
            
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_get_archives',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderArchivesList(response.data.archives);
                    } else {
                        self.showError(response.data.message || 'Failed to load archives.');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading archives.');
                }
            });
        },
        
        /**
         * Render archives list
         */
        renderArchivesList: function(archives) {
            this.archiveStatus.empty();
            
            if (!archives || archives.length === 0) {
                this.archiveList.html('<p>No archives found.</p>');
                return;
            }
            
            var html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>Archive Name</th>';
            html += '<th>Date</th>';
            html += '<th>Properties</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            $.each(archives, function(i, archive) {
                html += '<tr>';
                html += '<td><strong>' + archive.name + '</strong></td>';
                html += '<td>' + archive.date + '</td>';
                html += '<td>' + archive.count + ' properties</td>';
                html += '<td>';
                html += '<button class="button button-small kolibri24-archive-view-btn" data-archive-path="' + archive.path + '">View</button> ';
                html += '<button class="button button-small button-link-delete kolibri24-archive-delete-list-btn" data-archive-path="' + archive.path + '">Delete</button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            this.archiveList.html(html);
        },
        
        /**
         * View archive
         */
        viewArchive: function(archivePath) {
            var self = this;
            var nonce = this.archiveDeleteBtn.data('nonce');
            
            this.currentArchivePath = archivePath;
            this.showLoading('Loading archive preview...');
            
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_view_archive',
                    nonce: nonce,
                    archive_path: archivePath
                },
                success: function(response) {
                    if (response.success) {
                        self.archiveStatus.empty();
                        self.renderArchivePreview(response.data.archive_name, response.data.properties);
                    } else {
                        self.showError(response.data.message || 'Failed to load archive.');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading archive.');
                }
            });
        },
        
        /**
         * Render archive preview
         */
        renderArchivePreview: function(archiveName, properties) {
            this.archivePreviewName.text(archiveName);
            this.archivePropertyList.empty();
            
            if (!properties || properties.length === 0) {
                this.archivePropertyList.html('<p>No properties found in this archive.</p>');
            } else {
                var html = '';
                $.each(properties, function(i, property) {
                    html += '<div class="kolibri24-property-item">';
                    
                    // Image
                    html += '<div class="kolibri24-property-image">';
                    if (property.image) {
                        html += '<img src="' + property.image + '" alt="Property image" />';
                    } else {
                        html += '<div class="kolibri24-no-image"><span class="dashicons dashicons-camera"></span></div>';
                    }
                    html += '</div>';
                    
                    // Details
                    html += '<div class="kolibri24-property-details">';
                    html += '<h3>' + (property.property_id || 'N/A') + '</h3>';
                    if (property.address) {
                        html += '<p><strong>Address:</strong> ' + property.address + '</p>';
                    }
                    if (property.city) {
                        html += '<p><strong>City:</strong> ' + property.city + '</p>';
                    }
                    if (property.price) {
                        html += '<p><strong>Price:</strong> €' + property.price + '</p>';
                    }
                    html += '<p class="kolibri24-property-file"><small>' + property.file + '</small></p>';
                    html += '</div>';
                    
                    html += '</div>';
                });
                this.archivePropertyList.html(html);
            }
            
            this.archiveList.parent().hide();
            this.archivePreview.slideDown();
        },
        
        /**
         * Delete archive
         */
        deleteArchive: function(archivePath, fromPreview) {
            var self = this;
            var nonce = this.archiveDeleteBtn.data('nonce');
            
            this.showLoading('Deleting archive...');
            
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_delete_archive',
                    nonce: nonce,
                    archive_path: archivePath
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data.message || 'Archive deleted successfully.');
                        if (fromPreview) {
                            self.hidePreview();
                        }
                        self.loadArchives();
                    } else {
                        self.showError(response.data.message || 'Failed to delete archive.');
                    }
                },
                error: function() {
                    self.showError('An error occurred while deleting archive.');
                }
            });
        },
        
        /**
         * Hide preview and show list
         */
        hidePreview: function() {
            this.archivePreview.hide();
            this.archiveList.parent().show();
            this.currentArchivePath = null;
        },
        
        /**
         * Show loading message
         */
        showLoading: function(message) {
            this.archiveStatus.html('<div class="notice notice-info"><p>' + message + '</p></div>');
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.archiveStatus.html('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.archiveStatus.html('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        }
    };

    /**
     * Settings Handler
     */
    var Kolibri24SettingsManager = {
        
        // UI Elements
        saveBtn: null,
        
        /**
         * Save settings via AJAX
         */
        saveSettings: function() {
            var apiUrl = $('#kolibri24-api-url').val();
            var triggerUrl = $('#kolibri24-trigger-url').val();
            var processingUrl = $('#kolibri24-processing-url').val();
            var importId = $('#kolibri24-import-id').val();
            var nonce = $('#kolibri24-save-settings-btn').data('nonce');
            var statusDiv = $('#kolibri24-settings-status');

            // Clear previous messages
            statusDiv.empty();

            // Validate URLs
            if (!apiUrl) {
                statusDiv.html('<div class="notice notice-error is-dismissible"><p>Please enter a valid API URL</p></div>');
                return;
            }

            if (!triggerUrl) {
                statusDiv.html('<div class="notice notice-error is-dismissible"><p>Please enter a valid Trigger URL</p></div>');
                return;
            }

            if (!processingUrl) {
                statusDiv.html('<div class="notice notice-error is-dismissible"><p>Please enter a valid Processing URL</p></div>');
                return;
            }

            if (!importId) {
                statusDiv.html('<div class="notice notice-error is-dismissible"><p>Please enter a valid Import ID</p></div>');
                return;
            }

            // Show loading state
            $('#kolibri24-save-settings-btn').prop('disabled', true);
            statusDiv.html('<div class="notice notice-info"><p>Saving settings...</p></div>');

            // Make AJAX request
            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kolibri24_save_settings',
                    nonce: nonce,
                    kolibri24_api_url: apiUrl,
                    kolibri24_trigger_url: triggerUrl,
                    kolibri24_processing_url: processingUrl,
                    kolibri24_import_id: importId,
                },
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                    } else {
                        statusDiv.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    statusDiv.html('<div class="notice notice-error is-dismissible"><p>An error occurred while saving settings</p></div>');
                },
                complete: function() {
                    $('#kolibri24-save-settings-btn').prop('disabled', false);
                }
            });
        }
    };
    
    // Initialize property processor if on import tab
    if ($('#kolibri24-download-btn').length > 0) {
        Kolibri24PropertyProcessor.init();
    }
    
    // Initialize archive manager if on archive tab
    if ($('#kolibri24-archive-list').length > 0) {
        Kolibri24ArchiveManager.init();
    }
    
    // Use event delegation for settings save button - works even if form is hidden initially
    $(document).on('click', '#kolibri24-save-settings-btn', function(e) {
        e.preventDefault();
        Kolibri24SettingsManager.saveSettings();
    });

    /**
     * Import History Manager
     */
    var Kolibri24HistoryManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            
            $(document).on('click', '#kolibri24-history-load-btn', function(e) {
                e.preventDefault();
                self.loadHistory();
            });

            $(document).on('keyup', '#kolibri24-history-search', function() {
                self.filterHistory();
            });
        },

        loadHistory: function() {
            var nonce = $('#kolibri24-history-load-btn').data('nonce');
            var statusDiv = $('#kolibri24-history-status');

            $.ajax({
                url: kolibri24Ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kolibri24_get_import_history',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data.history.length > 0) {
                        Kolibri24HistoryManager.displayHistory(response.data.history);
                        statusDiv.html('');
                    } else {
                        statusDiv.html('<div class="notice notice-info"><p>' + (response.data.message || 'No import history found.') + '</p></div>');
                        $('#kolibri24-history-list').html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #999;">No imports yet.</td></tr>');
                    }
                },
                error: function() {
                    statusDiv.html('<div class="notice notice-error"><p>Error loading import history.</p></div>');
                }
            });
        },

        displayHistory: function(history) {
            var tbody = $('#kolibri24-history-list');
            tbody.empty();

            $.each(history, function(index, record) {
                var lastImportedDate = new Date(record.last_imported).toLocaleString();
                var lastModifiedDate = record.last_modified ? new Date(record.last_modified).toLocaleString() : 'N/A';
                
                var row = '<tr>';
                row += '<td><strong>' + escapeHtml(record.id) + '</strong></td>';
                row += '<td>' + escapeHtml(record.address) + '</td>';
                row += '<td>' + lastImportedDate + '</td>';
                row += '<td>' + lastModifiedDate + '</td>';
                row += '</tr>';
                
                tbody.append(row);
            });
        },

        filterHistory: function() {
            var searchTerm = $('#kolibri24-history-search').val().toLowerCase();
            var rows = $('#kolibri24-history-list tr');

            rows.each(function() {
                var text = $(this).text().toLowerCase();
                if (searchTerm === '' || text.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    };

    // Initialize Import History Manager if on history tab
    if ($('#kolibri24-history-load-btn').length > 0) {
        Kolibri24HistoryManager.init();
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

});