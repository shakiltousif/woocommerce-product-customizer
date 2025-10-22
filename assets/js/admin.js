/**
 * WooCommerce Product Customizer - Admin JavaScript
 */

(function($) {
    'use strict';

    class CustomizerAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initComponents();
        }

        bindEvents() {
            // Bulk actions
            $(document).on('click', '#bulk-enable-all', this.bulkEnableProducts.bind(this));
            $(document).on('click', '#bulk-disable-all', this.bulkDisableProducts.bind(this));
            
            // Settings form
            $(document).on('submit', '.wc-customizer-settings-form', this.saveSettings.bind(this));
            
            // Zone management
            $(document).on('click', '#upload-thumbnail-btn', this.openMediaUploader.bind(this));
            $(document).on('click', '#remove-thumbnail-btn', this.removeThumbnail.bind(this));
            $(document).on('submit', '#zone-form', this.saveZone.bind(this));
            $(document).on('click', '.edit-zone-btn', this.editZone.bind(this));
            $(document).on('click', '.delete-zone-btn', this.deleteZone.bind(this));
            $(document).on('click', '.toggle-zone-status-btn', this.toggleZoneStatus.bind(this));
            
            // Zone filtering
            $(document).on('click', '.filter-btn', this.filterZones.bind(this));
            
            // Customization types management
            $(document).on('submit', '#customization-type-form', this.saveCustomizationType.bind(this));
            $(document).on('click', '.delete-type-btn', this.deleteCustomizationType.bind(this));
            $(document).on('click', '.toggle-type-status-btn', this.toggleTypeStatus.bind(this));
            $(document).on('input', '#type_name', this.generateSlug.bind(this));
            
            // Pricing management
            $(document).on('submit', '#pricing-tier-form', this.savePricingTier.bind(this));
            $(document).on('click', '.delete-pricing-tier-btn', this.deletePricingTier.bind(this));
            $(document).on('change', '#min_quantity, #max_quantity', this.validateQuantityRanges.bind(this));
            
            // Product customization toggle
            $(document).on('change', '#customization_enabled', this.toggleCustomizationSettings.bind(this));
            
            // Category configuration management
            $(document).on('submit', '#category-config-form', this.saveCategoryConfig.bind(this));
            $(document).on('click', '.delete-config-btn', this.deleteCategoryConfig.bind(this));
            $(document).on('click', '.toggle-config-status-btn', this.toggleConfigStatus.bind(this));
            
            // Category configuration filtering
            $(document).on('click', '.configs-filter-tabs .filter-btn', this.filterConfigs.bind(this));
            
            // Customization types filtering
            $(document).on('click', '.types-filter-tabs .filter-btn', this.filterTypes.bind(this));
            
            // File upload settings
            $(document).on('change', 'input[name*="max_size_mb"]', this.updateMaxFileSize.bind(this));
            
            // Real-time validation
            $(document).on('input', 'input[type="number"]', this.validateNumberInput.bind(this));
        }

        initComponents() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize sortable tables
            this.initSortableTables();
            
            // Initialize color pickers
            this.initColorPickers();
            
            // Load dashboard stats
            this.loadDashboardStats();
        }

        bulkEnableProducts() {
            console.log('bulkEnableProducts called');
            console.log('jQuery available:', typeof $ !== 'undefined');
            console.log('wcCustomizerAdmin available:', typeof wcCustomizerAdmin !== 'undefined');
            
            if (!confirm('Are you sure you want to enable customization for all products?')) {
                return;
            }

            console.log('About to call showLoading with #bulk-enable-all');
            this.showLoading('#bulk-enable-all');

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_bulk_enable_products',
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    console.log('AJAX success response:', response);
                    this.hideLoading('#bulk-enable-all');
                    
                    if (response.success) {
                        this.showMessage(response.data.message, 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', xhr, status, error);
                    this.hideLoading('#bulk-enable-all');
                    this.showMessage('Error occurred: ' + error, 'error');
                }
            });
        }

        bulkDisableProducts() {
            if (!confirm('Are you sure you want to disable customization for all products?')) {
                return;
            }

            this.showLoading('#bulk-disable-all');

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_bulk_disable_products',
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading('#bulk-disable-all');
                    
                    if (response.success) {
                        this.showMessage(response.data.message, 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading('#bulk-disable-all');
                    this.showMessage('Error occurred: ' + error, 'error');
                }
            });
        }

        saveSettings(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = $form.serialize();
            
            this.showLoading($form.find('input[type="submit"]'));

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=wc_customizer_save_settings&nonce=' + wcCustomizerAdmin.nonce,
                success: (response) => {
                    this.hideLoading($form.find('input[type="submit"]'));
                    
                    if (response.success) {
                        this.showMessage(wcCustomizerAdmin.strings.saved, 'success');
                    } else {
                        this.showMessage(response.data.message || wcCustomizerAdmin.strings.error, 'error');
                    }
                },
                error: () => {
                    this.hideLoading($form.find('input[type="submit"]'));
                    this.showMessage(wcCustomizerAdmin.strings.error, 'error');
                }
            });
        }

        addZone() {
            const zoneData = this.getZoneFormData();
            
            if (!this.validateZoneData(zoneData)) {
                return;
            }

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_add_zone',
                    zone_data: zoneData,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.refreshZonesList();
                        this.clearZoneForm();
                        this.showMessage('Zone added successfully', 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: () => {
                    this.showMessage('Error occurred', 'error');
                }
            });
        }

        editZone(e) {
            e.preventDefault();
            const zoneId = $(e.target).data('zone-id');
            if (zoneId) {
                window.location.href = `admin.php?page=wc-customization-zones&action=edit&zone_id=${zoneId}`;
            }
        }


        addPricingTier() {
            const tierData = this.getPricingTierFormData();
            
            if (!this.validatePricingTierData(tierData)) {
                return;
            }

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_add_pricing_tier',
                    tier_data: tierData,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.refreshPricingTiersList();
                        this.clearPricingTierForm();
                        this.showMessage('Pricing tier added successfully', 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: () => {
                    this.showMessage('Error occurred', 'error');
                }
            });
        }

        editPricingTier(e) {
            const tierId = $(e.target).data('tier-id');
            this.loadPricingTierData(tierId);
        }

        deletePricingTier(e) {
            const tierId = $(e.target).data('tier-id');
            
            if (!confirm('Are you sure you want to delete this pricing tier?')) {
                return;
            }

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_delete_pricing_tier',
                    tier_id: tierId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.refreshPricingTiersList();
                        this.showMessage('Pricing tier deleted successfully', 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: () => {
                    this.showMessage('Error occurred', 'error');
                }
            });
        }

        toggleCustomizationSettings() {
            const isEnabled = $('#customization_enabled').is(':checked');
            $('.customization-settings-group').toggle(isEnabled);
        }

        updateMaxFileSize() {
            const sizeMB = parseFloat($('input[name*="max_size_mb"]').val());
            const sizeBytes = sizeMB * 1048576;
            $('input[name*="max_size"]').val(sizeBytes);
        }

        validateNumberInput(e) {
            const $input = $(e.target);
            const value = parseFloat($input.val());
            const min = parseFloat($input.attr('min'));
            const max = parseFloat($input.attr('max'));
            
            if (min !== undefined && value < min) {
                $input.addClass('error');
                this.showFieldError($input, `Minimum value is ${min}`);
            } else if (max !== undefined && value > max) {
                $input.addClass('error');
                this.showFieldError($input, `Maximum value is ${max}`);
            } else {
                $input.removeClass('error');
                this.hideFieldError($input);
            }
        }

        getZoneFormData() {
            return {
                name: $('#zone_name').val(),
                group: $('#zone_group').val(),
                charge: parseFloat($('#zone_charge').val()) || 0,
                methods: $('#zone_methods').val(),
                product_types: $('#zone_product_types').val()
            };
        }

        validateZoneData(data) {
            if (!data.name) {
                this.showMessage('Zone name is required', 'error');
                return false;
            }
            
            if (!data.group) {
                this.showMessage('Zone group is required', 'error');
                return false;
            }
            
            return true;
        }

        getPricingTierFormData() {
            return {
                method: $('#tier_method').val(),
                min_quantity: parseInt($('#tier_min_quantity').val()),
                max_quantity: parseInt($('#tier_max_quantity').val()),
                price: parseFloat($('#tier_price').val())
            };
        }

        validatePricingTierData(data) {
            if (!data.method) {
                this.showMessage('Method is required', 'error');
                return false;
            }
            
            if (data.min_quantity >= data.max_quantity) {
                this.showMessage('Maximum quantity must be greater than minimum quantity', 'error');
                return false;
            }
            
            if (data.price <= 0) {
                this.showMessage('Price must be greater than 0', 'error');
                return false;
            }
            
            return true;
        }

        loadZoneData(zoneId) {
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_zone',
                    zone_id: zoneId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populateZoneForm(response.data.zone);
                    }
                }
            });
        }

        loadPricingTierData(tierId) {
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_pricing_tier',
                    tier_id: tierId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populatePricingTierForm(response.data.tier);
                    }
                }
            });
        }

        populateZoneForm(zone) {
            $('#zone_name').val(zone.name);
            $('#zone_group').val(zone.group);
            $('#zone_charge').val(zone.charge);
            $('#zone_methods').val(zone.methods);
            $('#zone_product_types').val(zone.product_types);
        }

        populatePricingTierForm(tier) {
            $('#tier_method').val(tier.method);
            $('#tier_min_quantity').val(tier.min_quantity);
            $('#tier_max_quantity').val(tier.max_quantity);
            $('#tier_price').val(tier.price);
        }

        clearZoneForm() {
            $('#zone_name, #zone_group, #zone_charge, #zone_methods, #zone_product_types').val('');
        }

        clearPricingTierForm() {
            $('#tier_method, #tier_min_quantity, #tier_max_quantity, #tier_price').val('');
        }

        refreshZonesList() {
            location.reload(); // Simple refresh for now
        }

        refreshPricingTiersList() {
            location.reload(); // Simple refresh for now
        }

        loadDashboardStats() {
            if (!$('.wc-customizer-dashboard').length) {
                return;
            }

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_dashboard_stats',
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardStats(response.data.stats);
                    }
                }
            });
        }

        updateDashboardStats(stats) {
            $('.stat-card').each(function() {
                const $card = $(this);
                const statType = $card.data('stat-type');
                
                if (stats[statType] !== undefined) {
                    $card.find('.stat-number').text(stats[statType]);
                }
            });
        }

        initTooltips() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltip = $element.data('tooltip');
                
                $element.attr('title', tooltip);
            });
        }

        initSortableTables() {
            $('.sortable-table').each(function() {
                const $table = $(this);
                
                $table.find('th').click(function() {
                    const column = $(this).index();
                    const $tbody = $table.find('tbody');
                    const rows = $tbody.find('tr').toArray();
                    
                    rows.sort((a, b) => {
                        const aText = $(a).find('td').eq(column).text();
                        const bText = $(b).find('td').eq(column).text();
                        
                        return aText.localeCompare(bText);
                    });
                    
                    $tbody.empty().append(rows);
                });
            });
        }

        initColorPickers() {
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.color-picker').wpColorPicker();
            }
        }

        showLoading(selector) {
            console.log('showLoading called with selector:', selector);
            console.log('jQuery available:', typeof $ !== 'undefined');
            console.log('wcCustomizerAdmin available:', typeof wcCustomizerAdmin !== 'undefined');
            
            const $element = $(selector);
            console.log('jQuery element created:', $element);
            console.log('Element length:', $element.length);
            console.log('Element type:', typeof $element);
            console.log('Element has text method:', typeof $element.text);
            
            if ($element.length === 0) {
                console.error('Element not found:', selector);
                return;
            }
            
            // Fallback method if jQuery text() doesn't work
            let originalText;
            if (typeof $element.text === 'function') {
                originalText = $element.text();
            } else {
                // Fallback to native DOM methods
                originalText = $element[0].textContent || $element[0].innerText || '';
                console.log('Using fallback method for text content:', originalText);
            }
            
            $element.data('original-text', originalText);
            
            // Set new text using fallback method if needed
            if (typeof $element.text === 'function') {
                $element.text(wcCustomizerAdmin.strings.saving);
            } else {
                // Fallback to native DOM methods
                $element[0].textContent = wcCustomizerAdmin.strings.saving;
                console.log('Using fallback method to set text content');
            }
            
            $element.prop('disabled', true);
            $element.addClass('loading');
        }

        hideLoading(selector) {
            console.log('hideLoading called with selector:', selector);
            
            const $element = $(selector);
            if ($element.length === 0) {
                console.error('Element not found:', selector);
                return;
            }
            
            const originalText = $element.data('original-text');
            
            // Set text using fallback method if needed
            if (typeof $element.text === 'function') {
                $element.text(originalText);
            } else {
                // Fallback to native DOM methods
                $element[0].textContent = originalText;
                console.log('Using fallback method to restore text content');
            }
            
            $element.prop('disabled', false);
            $element.removeClass('loading');
        }

        showMessage(message, type = 'info') {
            // Remove any existing messages
            $('.wc-customizer-message').remove();
            
            const $message = $(`
                <div class="wc-customizer-message ${type}" style="margin: 20px 0; padding: 15px 20px; border-radius: 6px; font-weight: 500;">
                    ${message}
                </div>
            `);
            
            $('.wrap h1').after($message);
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut(() => {
                        $message.remove();
                    });
                }, 5000);
            }
        }

        showFieldError(field, message) {
            if (!field) return;
            
            this.hideFieldError(field);
            
            const error = document.createElement('div');
            error.className = 'field-error';
            error.textContent = message;
            field.parentNode.insertBefore(error, field.nextSibling);
        }

        hideFieldError(field) {
            if (!field) return;
            
            const nextSibling = field.nextSibling;
            if (nextSibling && nextSibling.classList && nextSibling.classList.contains('field-error')) {
                nextSibling.remove();
            }
        }

        // Zone Management Methods
        
        /**
         * Open WordPress media uploader
         */
        openMediaUploader() {
            const mediaUploader = wp.media({
                title: wcCustomizerAdmin.strings.chooseImage || 'Choose Image',
                button: {
                    text: wcCustomizerAdmin.strings.useImage || 'Use Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', () => {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                this.setThumbnail(attachment.url, attachment.id);
            });

            mediaUploader.open();
        }

        /**
         * Set thumbnail image
         */
        setThumbnail(url, attachmentId) {
            const $preview = $('#thumbnail-preview');
            const $thumbnailUrl = $('#thumbnail_url');
            const $removeBtn = $('#remove-thumbnail-btn');

            // Update preview
            $preview.html(`<img src="${url}" alt="Thumbnail" style="max-width: 150px; max-height: 150px; border-radius: 4px;">`);
            
            // Update hidden input
            $thumbnailUrl.val(url);
            
            // Show remove button
            $removeBtn.show();
        }

        /**
         * Remove thumbnail image
         */
        removeThumbnail() {
            const $preview = $('#thumbnail-preview');
            const $thumbnailUrl = $('#thumbnail_url');
            const $removeBtn = $('#remove-thumbnail-btn');

            // Reset preview
            $preview.html(`
                <div class="no-thumbnail" style="width: 150px; height: 150px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">
                    <span class="dashicons dashicons-format-image" style="font-size: 48px;"></span>
                </div>
            `);
            
            // Clear hidden input
            $thumbnailUrl.val('');
            
            // Hide remove button
            $removeBtn.hide();
        }

        /**
         * Save zone form
         */
        saveZone(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $('#save-zone-btn');
            const originalText = $submitBtn.text();
            
            // Validate required fields
            if (!this.validateZoneForm($form)) {
                return;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Saving...');
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'wc_customizer_save_zone');
            formData.append('nonce', wcCustomizerAdmin.nonce);
            
            // Convert methods array to comma-separated string
            const methods = [];
            $form.find('input[name="methods[]"]:checked').each(function() {
                methods.push($(this).val());
            });
            formData.set('methods', methods.join(','));
            
            // Convert product categories array to comma-separated string
            const categories = [];
            $form.find('select[name="product_categories[]"] option:selected').each(function() {
                categories.push($(this).text());
            });
            if (categories.length > 0) {
                const currentProductTypes = formData.get('product_types') || '';
                formData.set('product_types', currentProductTypes + (currentProductTypes ? ', ' : '') + categories.join(', '));
            }
            
            // Submit form
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        
                        // Redirect to zones list after a short delay
                        setTimeout(() => {
                            window.location.href = 'admin.php?page=wc-customization-zones';
                        }, 1500);
                    } else {
                        this.showNotice('error', response.data.message || 'Error saving zone.');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', xhr.responseText);
                    this.showNotice('error', 'Network error occurred: ' + error);
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        /**
         * Validate zone form
         */
        validateZoneForm($form) {
            let isValid = true;
            
            // Clear previous errors
            $form.find('.field-error').remove();
            $form.find('.error').removeClass('error');
            
            // Validate zone name
            const $zoneName = $form.find('#zone_name');
            if (!$zoneName.val() || !$zoneName.val().trim()) {
                this.showFieldError($zoneName, 'Zone name is required.');
                isValid = false;
            }
            
            // Validate methods
            const $methods = $form.find('input[name="methods[]"]:checked');
            if ($methods.length === 0) {
                this.showFieldError($form.find('input[name="methods[]"]').first(), 'Please select at least one method.');
                isValid = false;
            }
            
            return isValid;
        }

        /**
         * Delete zone
         */
        deleteZone(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this zone? This action cannot be undone.')) {
                return;
            }
            
            const $btn = $(e.target);
            const zoneId = $btn.data('zone-id');
            
            if (!zoneId) {
                this.showNotice('error', 'Invalid zone ID.');
                return;
            }
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_delete_zone',
                    nonce: wcCustomizerAdmin.nonce,
                    zone_id: zoneId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        
                        // Remove the row from table
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Reload page after 1 second to ensure database changes are reflected
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotice('error', response.data.message || 'Error deleting zone.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred.');
                }
            });
        }

        /**
         * Toggle zone status
         */
        toggleZoneStatus(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const zoneId = $btn.data('zone-id');
            const currentStatus = $btn.data('status');
            const newStatus = currentStatus ? 0 : 1;
            
            if (!zoneId) {
                this.showNotice('error', 'Invalid zone ID.');
                return;
            }
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_toggle_zone_status',
                    nonce: wcCustomizerAdmin.nonce,
                    zone_id: zoneId,
                    status: newStatus
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        
                        // Update button text and status
                        const statusText = newStatus ? 'Deactivate' : 'Activate';
                        $btn.text(statusText);
                        $btn.data('status', newStatus);
                        
                        // Update status badge
                        const $statusBadge = $btn.closest('tr').find('.status-badge');
                        $statusBadge.removeClass('status-active status-inactive')
                                  .addClass(newStatus ? 'status-active' : 'status-inactive')
                                  .text(newStatus ? 'Active' : 'Inactive');
                        
                        // Update row data attribute
                        $btn.closest('tr').attr('data-status', newStatus ? 'active' : 'inactive');
                        
                        // Update filter counts immediately
                        this.updateFilterCounts();
                        
                        // Reload page after 1 second to ensure database changes are reflected
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotice('error', response.data.message || 'Error updating zone status.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred.');
                }
            });
        }

        /**
         * Show admin notice
         */
        showNotice(type, message) {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Customization Types Management Methods
        
        /**
         * Save customization type form
         */
        saveCustomizationType(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Validate required fields
            if (!this.validateTypeForm($form)) {
                return;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Saving...');
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'wc_customizer_save_customization_type');
            // Check if wcCustomizerAdmin is defined
            if (typeof wcCustomizerAdmin === 'undefined') {
                console.error('wcCustomizerAdmin is not defined, falling back to regular form submission');
                $form[0].submit();
                return;
            }
            
            formData.append('nonce', wcCustomizerAdmin.nonce);
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Redirect to types list after a short delay
                        setTimeout(() => {
                            window.location.href = (wcCustomizerAdmin.adminUrl || '') + 'admin.php?page=wc-customization-types';
                        }, 1500);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while saving the customization type.');
                    console.error('AJAX Error:', error);
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        /**
         * Delete customization type
         */
        deleteCustomizationType(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const typeId = $btn.data('type-id');
            
            if (!confirm('Are you sure you want to delete this customization type? This action cannot be undone.')) {
                return;
            }
            
            // Check if wcCustomizerAdmin is defined
            if (typeof wcCustomizerAdmin === 'undefined') {
                console.error('wcCustomizerAdmin is not defined');
                this.showNotice('error', 'Configuration error. Please refresh the page.');
                return;
            }
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_delete_customization_type',
                    type_id: typeId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Remove the row from the table
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while deleting the customization type.');
                    console.error('AJAX Error:', error);
                }
            });
        }

        /**
         * Toggle customization type status
         */
        toggleTypeStatus(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const typeId = $btn.data('type-id');
            const currentStatus = $btn.data('status');
            
            // Check if wcCustomizerAdmin is defined
            if (typeof wcCustomizerAdmin === 'undefined') {
                console.error('wcCustomizerAdmin is not defined');
                this.showNotice('error', 'Configuration error. Please refresh the page.');
                return;
            }
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_toggle_type_status',
                    type_id: typeId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Update button text and status
                        const newStatus = response.data.new_status;
                        const newText = newStatus ? 'Deactivate' : 'Activate';
                        $btn.text(newText).data('status', newStatus);
                        
                        // Update status display
                        const $row = $btn.closest('tr');
                        const $statusSpan = $row.find('.status-active, .status-inactive');
                        $statusSpan.removeClass('status-active status-inactive')
                                 .addClass('status-' + (newStatus ? 'active' : 'inactive'))
                                 .text(newStatus ? 'Active' : 'Inactive');
                        
                        // Update row data attribute for filtering
                        $row.attr('data-status', newStatus ? 'active' : 'inactive');
                        
                        // Update filter counts
                        this.updateTypesFilterCounts();
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while updating the customization type status.');
                    console.error('AJAX Error:', error);
                }
            });
        }

        /**
         * Generate slug from type name
         */
        generateSlug(e) {
            const $nameInput = $(e.target);
            const $slugInput = $('#type_slug');
            
            if ($slugInput.length && !$slugInput.data('user-modified')) {
                const name = $nameInput.val();
                const slug = name.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                    .replace(/\s+/g, '-') // Replace spaces with hyphens
                    .replace(/-+/g, '-') // Replace multiple hyphens with single
                    .replace(/^-|-$/g, ''); // Remove leading/trailing hyphens
                
                $slugInput.val(slug);
            }
        }

        /**
         * Validate customization type form
         */
        validateTypeForm($form) {
            let isValid = true;
            
            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            const requiredFields = ['type_name', 'type_slug'];
            requiredFields.forEach(fieldName => {
                const $field = $form.find(`[name="${fieldName}"]`);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    $field.after('<span class="error-message" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">This field is required.</span>');
                    isValid = false;
                }
            });
            
            // Validate slug format
            const $slugField = $form.find('[name="type_slug"]');
            const slugValue = $slugField.val();
            if (slugValue && !/^[a-z0-9-]+$/.test(slugValue)) {
                $slugField.addClass('error');
                $slugField.after('<span class="error-message" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">Slug must contain only lowercase letters, numbers, and hyphens.</span>');
                isValid = false;
            }
            
            // Validate setup fees
            const $textFee = $form.find('[name="text_setup_fee"]');
            const $logoFee = $form.find('[name="logo_setup_fee"]');
            
            if ($textFee.val() < 0) {
                $textFee.addClass('error');
                $textFee.after('<span class="error-message" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">Setup fee cannot be negative.</span>');
                isValid = false;
            }
            
            if ($logoFee.val() < 0) {
                $logoFee.addClass('error');
                $logoFee.after('<span class="error-message" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">Setup fee cannot be negative.</span>');
                isValid = false;
            }
            
            return isValid;
        }

        // Pricing Management Methods
        
        /**
         * Save pricing tier form
         */
        savePricingTier(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = document.getElementById('save-pricing-tier-btn');
            const originalText = submitBtn ? submitBtn.textContent : '';
            
            console.log('Pricing tier form submitted', form, submitBtn);
            
            // Check if wcCustomizerAdmin is defined
            if (typeof wcCustomizerAdmin === 'undefined') {
                console.error('wcCustomizerAdmin is not defined, falling back to regular form submission');
                // Fall back to regular form submission
                form.submit();
                return;
            }
            
            // Validate required fields
            if (!this.validatePricingTierForm(form)) {
                return;
            }
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }
            
            // Prepare form data
            const formData = new FormData(form);
            formData.append('action', 'wc_customizer_save_pricing_tier');
            formData.append('nonce', wcCustomizerAdmin.nonce);
            
            console.log('Submitting AJAX request', formData);
            
            // Submit form using fetch API
            fetch(wcCustomizerAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('AJAX success response', data);
                if (data.success) {
                    this.showNotice('success', data.data.message);
                    
                    // Show warnings if any
                    if (data.data.warnings && data.data.warnings.length > 0) {
                        this.showNotice('warning', 'Warning: ' + data.data.warnings.join(', '));
                    }
                    
                    // Redirect to pricing list after a short delay
                    setTimeout(() => {
                        window.location.href = 'admin.php?page=wc-customization-pricing';
                    }, 1500);
                } else {
                    this.showNotice('error', data.data.message || 'Error saving pricing tier.');
                }
            })
            .catch(error => {
                console.error('AJAX error', error);
                this.showNotice('error', 'Network error occurred: ' + error.message);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        /**
         * Validate pricing tier form
         */
        validatePricingTierForm(form) {
            let isValid = true;
            
            // Clear previous errors
            const existingErrors = form.querySelectorAll('.field-error');
            existingErrors.forEach(error => error.remove());
            const errorFields = form.querySelectorAll('.error');
            errorFields.forEach(field => field.classList.remove('error'));
            
            // Validate type_id
            const typeSelect = form.querySelector('#type_id');
            if (!typeSelect || !typeSelect.value) {
                this.showFieldError(typeSelect, 'Please select a customization type.');
                isValid = false;
            }
            
            // Validate quantities
            const minQtyField = form.querySelector('#min_quantity');
            const maxQtyField = form.querySelector('#max_quantity');
            const minQty = parseInt(minQtyField ? minQtyField.value : 0);
            const maxQty = parseInt(maxQtyField ? maxQtyField.value : 0);
            
            if (!minQty || minQty < 1) {
                this.showFieldError(minQtyField, 'Minimum quantity must be at least 1.');
                isValid = false;
            }
            
            if (!maxQty || maxQty < 1) {
                this.showFieldError(maxQtyField, 'Maximum quantity must be at least 1.');
                isValid = false;
            }
            
            if (minQty >= maxQty) {
                this.showFieldError(minQtyField, 'Minimum quantity must be less than maximum quantity.');
                this.showFieldError(maxQtyField, 'Maximum quantity must be greater than minimum quantity.');
                isValid = false;
            }
            
            // Validate price
            const priceField = form.querySelector('#price_per_item');
            const price = parseFloat(priceField ? priceField.value : 0);
            
            if (!price || price <= 0) {
                this.showFieldError(priceField, 'Price per item must be greater than 0.');
                isValid = false;
            }
            
            return isValid;
        }

        /**
         * Delete pricing tier
         */
        deletePricingTier(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this pricing tier? This action cannot be undone.')) {
                return;
            }
            
            const $btn = $(e.target);
            const tierId = $btn.data('tier-id');
            
            if (!tierId) {
                this.showNotice('error', 'Invalid tier ID.');
                return;
            }
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_delete_pricing_tier',
                    nonce: wcCustomizerAdmin.nonce,
                    tier_id: tierId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        
                        // Remove the row from table
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        this.showNotice('error', response.data.message || 'Error deleting pricing tier.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred.');
                }
            });
        }

        /**
         * Validate quantity ranges
         */
        validateQuantityRanges(e) {
            const $field = $(e.target);
            const $form = $field.closest('form');
            
            if (!$form.length || $form.attr('id') !== 'pricing-tier-form') {
                return;
            }
            
            const method = $form.find('#pricing_method').val();
            const minQty = parseInt($form.find('#min_quantity').val());
            const maxQty = parseInt($form.find('#max_quantity').val());
            const tierId = $form.find('input[name="tier_id"]').val();
            
            if (!method || !minQty || !maxQty) {
                return;
            }
            
            // Clear previous validation errors
            $form.find('.quantity-validation-error').remove();
            
            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_validate_pricing_ranges',
                    nonce: wcCustomizerAdmin.nonce,
                    method: method,
                    exclude_id: tierId || null
                },
                success: (response) => {
                    if (response.success) {
                        const validation = response.data;
                        
                        if (!validation.valid) {
                            // Show errors
                            validation.errors.forEach(error => {
                                $form.find('.form-table').append(`<div class="notice notice-error quantity-validation-error"><p>${error}</p></div>`);
                            });
                        }
                        
                        if (validation.warnings && validation.warnings.length > 0) {
                            // Show warnings
                            validation.warnings.forEach(warning => {
                                $form.find('.form-table').append(`<div class="notice notice-warning quantity-validation-error"><p>Warning: ${warning}</p></div>`);
                            });
                        }
                    }
                },
                error: () => {
                    // Silently fail for validation
                }
            });
        }

        /**
         * Show message notification
         */
        showMessage(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Refresh zones list
         */
        refreshZonesList() {
            location.reload();
        }

        /**
         * Load zone data for editing
         */
        loadZoneData(zoneId) {
            // This would typically load zone data via AJAX
            // For now, redirect to edit page
            window.location.href = `admin.php?page=wc-customization-zones&action=edit&zone_id=${zoneId}`;
        }

        /**
         * Filter zones by status
         */
        filterZones(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const status = $btn.data('status');
            
            // Update active button
            $('.filter-btn').removeClass('active');
            $btn.addClass('active');
            
            // Filter zone rows
            const $rows = $('.zone-row');
            
            if (status === 'all') {
                // Show all rows
                $rows.removeClass('hidden filtering-out').show();
            } else {
                // Filter by status
                $rows.each(function() {
                    const $row = $(this);
                    const rowStatus = $row.data('status');
                    
                    if (rowStatus === status) {
                        $row.removeClass('hidden filtering-out').show();
                    } else {
                        $row.addClass('filtering-out');
                        setTimeout(() => {
                            $row.addClass('hidden').hide();
                        }, 300);
                    }
                });
            }
            
            // Update counts
            this.updateFilterCounts();
        }

        /**
         * Update filter button counts
         */
        updateFilterCounts() {
            const $rows = $('.zone-row');
            const totalCount = $rows.length;
            const activeCount = $rows.filter('[data-status="active"]').length;
            const inactiveCount = $rows.filter('[data-status="inactive"]').length;
            
            $('.filter-btn[data-status="all"] .count').text(`(${totalCount})`);
            $('.filter-btn[data-status="active"] .count').text(`(${activeCount})`);
            $('.filter-btn[data-status="inactive"] .count').text(`(${inactiveCount})`);
        }

        // ========================================
        // Category Configuration Methods
        // ========================================

        /**
         * Save category configuration
         */
        saveCategoryConfig(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Saving...');
            
            // Validate form
            if (!this.validateCategoryConfigForm($form)) {
                $submitBtn.prop('disabled', false).text(originalText);
                return;
            }
            
             const formData = new FormData($form[0]);
             formData.append('action', 'wc_customizer_save_category_config');
             
             // Check if wcCustomizerAdmin is defined
             if (typeof wcCustomizerAdmin === 'undefined') {
                 console.error('wcCustomizerAdmin is not defined, falling back to regular form submission');
                 $form[0].submit();
                 return;
             }
             
             formData.append('nonce', wcCustomizerAdmin.nonce);
             
             $.ajax({
                 url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Redirect to list page
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while saving the configuration.');
                    console.error('AJAX Error:', error);
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        /**
         * Delete category configuration
         */
        deleteCategoryConfig(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const configId = $btn.data('config-id');
            
            if (!confirm('Are you sure you want to delete this configuration? This action cannot be undone.')) {
                return;
            }
            
             // Check if wcCustomizerAdmin is defined
             if (typeof wcCustomizerAdmin === 'undefined') {
                 console.error('wcCustomizerAdmin is not defined');
                 this.showNotice('error', 'Configuration error. Please refresh the page.');
                 return;
             }
             
             $.ajax({
                 url: wcCustomizerAdmin.ajaxUrl,
                 type: 'POST',
                 data: {
                     action: 'wc_customizer_delete_category_config',
                     config_id: configId,
                     nonce: wcCustomizerAdmin.nonce
                 },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Remove the row from the table
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while deleting the configuration.');
                    console.error('AJAX Error:', error);
                }
            });
        }

        /**
         * Toggle configuration status
         */
        toggleConfigStatus(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const configId = $btn.data('config-id');
            
             // Check if wcCustomizerAdmin is defined
             if (typeof wcCustomizerAdmin === 'undefined') {
                 console.error('wcCustomizerAdmin is not defined');
                 this.showNotice('error', 'Configuration error. Please refresh the page.');
                 return;
             }
             
             $.ajax({
                 url: wcCustomizerAdmin.ajaxUrl,
                 type: 'POST',
                 data: {
                     action: 'wc_customizer_toggle_config_status',
                     config_id: configId,
                     nonce: wcCustomizerAdmin.nonce
                 },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Update button text and status
                        const newStatus = response.data.new_status;
                        const newText = newStatus ? 'Deactivate' : 'Activate';
                        $btn.text(newText);
                        
                        // Update status display
                        const $row = $btn.closest('tr, .config-card');
                        const $statusSpan = $row.find('.status-enabled, .status-disabled');
                        $statusSpan.removeClass('status-enabled status-disabled')
                                  .addClass('status-' + (newStatus ? 'enabled' : 'disabled'))
                                  .text(newStatus ? 'Active' : 'Inactive');
                        
                        // Update row data attribute for filtering
                        $row.attr('data-status', newStatus ? 'active' : 'inactive');
                        
                        // Update filter counts
                        this.updateConfigFilterCounts();
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'An error occurred while updating the configuration status.');
                    console.error('AJAX Error:', error);
                }
            });
        }

        /**
         * Validate category configuration form
         */
        validateCategoryConfigForm($form) {
            let isValid = true;
            
            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            const requiredFields = ['category_id', 'config_name'];
            requiredFields.forEach(fieldName => {
                const $field = $form.find(`[name="${fieldName}"]`);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    $field.after(`<span class="error-message">This field is required.</span>`);
                    isValid = false;
                }
            });
            
            // Validate at least one zone is selected
            const selectedZones = $form.find('input[name="available_zones[]"]:checked').length;
            if (selectedZones === 0) {
                this.showNotice('error', 'Please select at least one zone.');
                isValid = false;
            }
            
            // Validate at least one type is selected
            const selectedTypes = $form.find('input[name="available_types[]"]:checked').length;
            if (selectedTypes === 0) {
                this.showNotice('error', 'Please select at least one customization type.');
                isValid = false;
            }
            
            return isValid;
        }

        // ========================================
        // Category Configuration Filtering
        // ========================================

        /**
         * Filter category configurations by status
         */
        filterConfigs(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const status = $btn.data('status');
            
            // Update active button
            $('.configs-filter-tabs .filter-btn').removeClass('active');
            $btn.addClass('active');
            
            // Filter config rows
            const $rows = $('.config-row');
            
            if (status === 'all') {
                // Show all rows
                $rows.removeClass('hidden filtering-out').show();
            } else {
                // Filter by status
                $rows.each(function() {
                    const $row = $(this);
                    const rowStatus = $row.data('status');
                    
                    if (rowStatus === status) {
                        $row.removeClass('hidden filtering-out').show();
                    } else {
                        $row.addClass('filtering-out');
                        setTimeout(() => {
                            $row.addClass('hidden').hide();
                        }, 300);
                    }
                });
            }
            
            // Update counts
            this.updateConfigFilterCounts();
        }

        /**
         * Update filter button counts for category configurations
         */
        updateConfigFilterCounts() {
            const $rows = $('.config-row');
            const totalCount = $rows.length;
            const activeCount = $rows.filter('[data-status="active"]').length;
            const inactiveCount = $rows.filter('[data-status="inactive"]').length;
            
            $('.configs-filter-tabs .filter-btn[data-status="all"] .count').text(`(${totalCount})`);
            $('.configs-filter-tabs .filter-btn[data-status="active"] .count').text(`(${activeCount})`);
            $('.configs-filter-tabs .filter-btn[data-status="inactive"] .count').text(`(${inactiveCount})`);
        }

        // ========================================
        // Customization Types Filtering
        // ========================================

        /**
         * Filter customization types by status
         */
        filterTypes(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const status = $btn.data('status');
            
            // Update active button
            $('.types-filter-tabs .filter-btn').removeClass('active');
            $btn.addClass('active');
            
            // Filter type rows
            const $rows = $('.type-row');
            
            if (status === 'all') {
                // Show all rows
                $rows.removeClass('hidden filtering-out').show();
            } else {
                // Filter by status
                $rows.each(function() {
                    const $row = $(this);
                    const rowStatus = $row.data('status');
                    
                    if (rowStatus === status) {
                        $row.removeClass('hidden filtering-out').show();
                    } else {
                        $row.addClass('filtering-out');
                        setTimeout(() => {
                            $row.addClass('hidden').hide();
                        }, 300);
                    }
                });
            }
            
            // Update counts
            this.updateTypesFilterCounts();
        }

        /**
         * Update filter button counts for customization types
         */
        updateTypesFilterCounts() {
            const $rows = $('.type-row');
            const totalCount = $rows.length;
            const activeCount = $rows.filter('[data-status="active"]').length;
            const inactiveCount = $rows.filter('[data-status="inactive"]').length;
            
            $('.types-filter-tabs .filter-btn[data-status="all"] .count').text(`(${totalCount})`);
            $('.types-filter-tabs .filter-btn[data-status="active"] .count').text(`(${activeCount})`);
            $('.types-filter-tabs .filter-btn[data-status="inactive"] .count').text(`(${inactiveCount})`);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new CustomizerAdmin();
    });

})(jQuery);
