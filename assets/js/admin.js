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
            $(document).on('click', '.add-zone-btn', this.addZone.bind(this));
            $(document).on('click', '.edit-zone-btn', this.editZone.bind(this));
            $(document).on('click', '.delete-zone-btn', this.deleteZone.bind(this));
            
            // Pricing management
            $(document).on('click', '.add-pricing-tier-btn', this.addPricingTier.bind(this));
            $(document).on('click', '.edit-pricing-tier-btn', this.editPricingTier.bind(this));
            $(document).on('click', '.delete-pricing-tier-btn', this.deletePricingTier.bind(this));
            
            // Product customization toggle
            $(document).on('change', '#customization_enabled', this.toggleCustomizationSettings.bind(this));
            
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
            const zoneId = $(e.target).data('zone-id');
            this.loadZoneData(zoneId);
        }

        deleteZone(e) {
            const zoneId = $(e.target).data('zone-id');
            
            if (!confirm('Are you sure you want to delete this zone?')) {
                return;
            }

            $.ajax({
                url: wcCustomizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_delete_zone',
                    zone_id: zoneId,
                    nonce: wcCustomizerAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.refreshZonesList();
                        this.showMessage('Zone deleted successfully', 'success');
                    } else {
                        this.showMessage(response.data.message || 'Error occurred', 'error');
                    }
                },
                error: () => {
                    this.showMessage('Error occurred', 'error');
                }
            });
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

        showFieldError($field, message) {
            this.hideFieldError($field);
            
            const $error = $(`<div class="field-error">${message}</div>`);
            $field.after($error);
        }

        hideFieldError($field) {
            $field.next('.field-error').remove();
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new CustomizerAdmin();
    });

})(jQuery);
