/**
 * WooCommerce Product Customizer - Wizard JavaScript
 */

(function($) {
    'use strict';

    class CustomizationWizard {
        constructor() {
            this.currentStep = 1;
            this.totalSteps = 3;
            this.sessionId = null;
            this.cartItemKey = null;
            this.productId = null;
            this.selectedZones = [];
            this.selectedMethod = null;
            this.uploadedFile = null;
            this.customText = '';
            this.contentType = 'logo'; // 'logo' or 'text'
            this.customizationData = {};
            this.positionConfigs = {}; // Store per-position configurations
            this.currentPositionId = null; // Currently active position tab
            this.availableZones = []; // Available zones for this product
            
            this.init();
        }

        init() {
            // Initialize session ID
            this.loadSessionIfExists();
            if (!this.sessionId) {
                this.sessionId = this.generateSessionId();
            }
            console.log('Session ID initialized:', this.sessionId);
            
            if (this.isCustomizationPage()) {
                this.initializeFromURL();
                this.bindEvents();
                this.initFileUpload();
                this.loadZones();
                // loadMethods() removed - now handled per position in renderPositionTabs()
                this.loadExistingCustomization();
                
                // Delay showing steps to ensure DOM is ready
                setTimeout(() => {
                    this.showAllSteps(); // Show all steps for single-page approach
                }, 200);
                
                // Ensure no step navigation buttons are visible
                setTimeout(() => {
                    this.removeStepNavigationButtons();
                }, 300);
            } else {
            this.bindEvents();
            this.initFileUpload();
            this.loadSessionIfExists();
            }
        }

        bindEvents() {
            // Only bind events for customization page
            if (this.isCustomizationPage()) {
                // Cancel button for customization page
                $(document).on('click', '.cancel-customization', this.handleCancel.bind(this));
            } else {
                // Only bind remove button for cart page (still uses AJAX)
            $(document).on('click', '.remove-customization-btn', this.removeCustomization.bind(this));
            }
            
            // Step navigation removed for single-page approach
            
            // Zone selection
            $(document).on('click', '.zone-card', this.toggleZone.bind(this));
            
            // Method selection
            $(document).on('click', '.method-card', this.selectMethod.bind(this));
            
            // File upload
            $(document).on('click', '#add-logo-btn', this.triggerFileUpload.bind(this));
            $(document).on('change', '#file-input', this.handleFileSelect.bind(this));
            $(document).on('click', '.remove-file-btn', this.removeFile.bind(this));
            
            // Content type selection
            $(document).on('click', '.content-type-card', this.handleContentTypeClick.bind(this));
            $(document).on('change', 'input[name="content_type"]', this.handleContentTypeChange.bind(this));
            
            // Text input
            $(document).on('input', '#custom-text-input', this.handleTextInput.bind(this));
            
            // New text configuration inputs
            $(document).on('input', '#text-line-1, #text-line-2, #text-line-3', this.handleTextLineInput.bind(this));
            $(document).on('change', '#text-font', this.handleTextLineInput.bind(this));
            $(document).on('change', 'input[name="text_color"]', this.handleTextLineInput.bind(this));
            $(document).on('click', '#preview-btn', this.handlePreviewClick.bind(this));
            
            // Final actions
            $(document).on('click', '#add-to-cart-btn', this.addToCart.bind(this));
            
            // Auto-save
            $(document).on('change input', '.wizard-step', this.autoSave.bind(this));
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboard.bind(this));
        }


        removeCustomization(event) {
            event.preventDefault();
            
            const $button = $(event.currentTarget);
            const cartItemKey = $button.data('cart-key');
            
            if (!cartItemKey) {
                console.error('No cart item key found for remove button');
                return;
            }
            
            // Confirm removal
            if (!confirm(wcCustomizerWizard.strings.confirmRemove || 'Are you sure you want to remove this customization?')) {
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text('Removing...');
            
            // Make AJAX call to remove customization
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_remove_customization',
                    cart_item_key: cartItemKey,
                    nonce: wcCustomizerWizard.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Reload the page to update the cart
                        window.location.reload();
                    } else {
                        console.error('Error removing customization:', response.data.message);
                        alert(response.data.message || 'Failed to remove customization');
                        $button.prop('disabled', false).text('Remove');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error removing customization:', error);
                    alert('Failed to remove customization. Please try again.');
                    $button.prop('disabled', false).text('Remove');
                }
            });
        }

        closeWizard() {
            if (this.isCustomizationPage()) {
                // On customization page, redirect to return URL
                window.location.href = this.returnUrl || wcCustomizerWizard.cartUrl;
            } else {
                // Modal behavior (for backward compatibility)
            $('#wc-customizer-wizard-modal').fadeOut(300);
            $('body').removeClass('wc-customizer-modal-open');
            this.resetWizard();
            }
        }

        /**
         * Check if we're on the customization page
         */
        isCustomizationPage() {
            return $('body').hasClass('wc-customization-page');
        }

        /**
         * Initialize from URL parameters
         */
        initializeFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            this.productId = urlParams.get('product_id');
            this.cartItemKey = urlParams.get('cart_key');
            this.returnUrl = urlParams.get('return_url');
            
            console.log('Initializing from URL:', {
                productId: this.productId,
                cartItemKey: this.cartItemKey,
                returnUrl: this.returnUrl
            });
        }

        /**
         * Handle cancel button
         */
        handleCancel() {
            if (confirm(wcCustomizerWizard.strings.confirmCancel)) {
                window.location.href = this.returnUrl || wcCustomizerWizard.cartUrl;
            }
        }

        resetWizard() {
            this.currentStep = 1;
            this.selectedZones = [];
            this.selectedMethod = null;
            this.uploadedFile = null;
            this.customText = '';
            this.contentType = 'logo';
            this.customizationData = {};
            
            // Reset UI
            $('.wizard-step').hide();
            $('#step-1').show();
            $('.zone-card').removeClass('selected');
            $('.method-card').removeClass('selected');
            $('#position-count').text('0');
            $('.selected-count').text('0 ' + wcCustomizerWizard.strings.selected);
            
            // Reset content type selection - hide both sections initially
            $('input[name="content_type"][value="logo"]').prop('checked', true);
            $('.content-type-card').removeClass('selected');
            $('.content-type-card .checkmark').hide();
            
            // Select the logo card by default
            const $logoCard = $('.content-type-card[data-content-type="logo"]');
            if ($logoCard.length > 0) {
                $logoCard.addClass('selected');
                $logoCard.find('.checkmark').show();
            }
            
            $('#logo-upload-section').hide();
            $('#text-input-section').hide();
            $('#custom-text-input').val('');
            this.updateTextPreview();
        }

        // Step navigation methods removed for single-page approach
        
        showAllSteps() {
            // Debug: Check if step-1 element exists
            console.log('All wizard steps found:', $('.wizard-step').length);
            console.log('Step 1 element exists:', $('#step-1').length);
            console.log('All step IDs:', $('.wizard-step').map(function() { return this.id; }).get());
            console.log('All step data-step attributes:', $('.wizard-step').map(function() { return $(this).data('step'); }).get());
            
            // Show only step 1 initially for progressive disclosure
            $('.wizard-step').removeClass('active').hide();
            
            // Try multiple approaches to find step 1
            let $step1 = $('#step-1');
            if ($step1.length === 0) {
                // Try by data-step attribute
                $step1 = $('.wizard-step[data-step="1"]');
                console.log('Step 1 found by data-step:', $step1.length);
            }
            
            if ($step1.length === 0) {
                console.error('Step 1 element not found! Available steps:', $('.wizard-step').map(function() { return this.id; }).get());
                console.error('Available data-step attributes:', $('.wizard-step').map(function() { return $(this).data('step'); }).get());
                // Try to show the first wizard step instead
                const $firstStep = $('.wizard-step').first();
                if ($firstStep.length > 0) {
                    console.log('Showing first available step instead:', $firstStep.attr('id'), $firstStep.data('step'));
                    $firstStep.addClass('active').show().css({
                        'display': 'flex !important',
                        'opacity': '1 !important',
                        'transform': 'translateY(0) !important',
                        'pointer-events': 'auto !important',
                        'visibility': 'visible !important'
                    });
                }
                return;
            }
            
            // Force show step 1 immediately with multiple approaches
            $step1.addClass('active');
            $step1.show();
            $step1.css({
                'display': 'flex !important',
                'opacity': '1 !important',
                'transform': 'translateY(0) !important',
                'pointer-events': 'auto !important',
                'visibility': 'visible !important'
            });
            
            // Additional fallback with longer delay
            setTimeout(() => {
                $step1.attr('style', 'display: flex !important; opacity: 1 !important; transform: translateY(0) !important; pointer-events: auto !important; visibility: visible !important;');
                console.log('Step 1 forced visibility applied');
            }, 100);
            
            console.log('Progressive disclosure initialized - showing step 1 only');
            console.log('Step 1 visible:', $step1.is(':visible'));
            console.log('Step 1 has active class:', $step1.hasClass('active'));
            console.log('Step 1 computed styles:', $step1.css(['display', 'opacity', 'transform', 'visibility']));
            console.log('Step 1 element:', $step1[0]);
            
            // Remove any remaining step navigation buttons
            this.removeStepNavigationButtons();
            
            // Initialize content type selection
            this.initializeContentTypeSelection();
        }
        
        initializeContentTypeSelection() {
            console.log('=== INITIALIZING CONTENT TYPE SELECTION ===');
            console.log('Content type cards found:', $('.content-type-card').length);
            console.log('Logo card found:', $('.content-type-card[data-content-type="logo"]').length);
            console.log('Text card found:', $('.content-type-card[data-content-type="text"]').length);
            console.log('All content type cards:', $('.content-type-card').map(function() { return $(this).data('content-type'); }).get());
            
            // Debug: Check what HTML is actually present
            console.log('Content type selection HTML:', $('.content-type-selection').html());
            console.log('Step 3 HTML:', $('#step-3').html());
            console.log('All elements with content-type in class name:', $('[class*="content-type"]').length);
            console.log('All elements with data-content-type:', $('[data-content-type]').length);
            
            // Check if we have the new button-style cards
            if ($('.content-type-card').length > 0) {
                console.log('Using new button-style cards');
                this.initializeButtonStyleCards();
                } else {
                console.log('Using fallback radio button approach');
                this.initializeRadioButtonFallback();
            }
            
            // Initialize text preview if text section is visible
            setTimeout(() => {
                console.log('=== INITIALIZING TEXT PREVIEW ===');
                console.log('Text input section visible:', $('#text-input-section').is(':visible'));
                console.log('Text line 1 element found:', $('#text-line-1').length);
                console.log('Text line 2 element found:', $('#text-line-2').length);
                console.log('Text line 3 element found:', $('#text-line-3').length);
                console.log('Preview text element found:', $('.preview-text').length);
                console.log('Preview area element found:', $('#text-preview-area').length);
                
                if ($('#text-input-section').is(':visible')) {
                    this.updateTextPreview();
                }
            }, 200);
        }
        
        initializeButtonStyleCards() {
            // Set initial state - hide both sections initially
            $('.content-type-card').removeClass('selected');
            $('.content-type-card .checkmark').hide();
            
            // Select the logo card by default
            const $logoCard = $('.content-type-card[data-content-type="logo"]');
            console.log('Logo card element:', $logoCard[0]);
            
            if ($logoCard.length > 0) {
                $logoCard.addClass('selected');
                $logoCard.find('.checkmark').show();
                console.log('Logo card selected successfully');
            } else {
                console.error('Logo card not found! Available cards:', $('.content-type-card').length);
            }
            
            // Hide both sections initially
            $('#logo-upload-section').hide();
            $('#text-input-section').hide();
            
            console.log('Content type selection initialized - both sections hidden');
            console.log('Logo card selected:', $('.content-type-card[data-content-type="logo"]').hasClass('selected'));
            console.log('Text card selected:', $('.content-type-card[data-content-type="text"]').hasClass('selected'));
            console.log('Selected card count:', $('.content-type-card.selected').length);
            console.log('=== CONTENT TYPE SELECTION INITIALIZATION COMPLETE ===');
        }
        
        initializeRadioButtonFallback() {
            console.log('=== INITIALIZING RADIO BUTTON FALLBACK ===');
            
            // Check for existing radio button structure
            const $logoRadio = $('input[name="content_type"][value="logo"]');
            const $textRadio = $('input[name="content_type"][value="text"]');
            
            console.log('Logo radio found:', $logoRadio.length);
            console.log('Text radio found:', $textRadio.length);
            
            if ($logoRadio.length > 0 && $textRadio.length > 0) {
                // Create button-style wrapper around existing radio buttons
                this.createButtonStyleWrapper();
            } else {
                console.error('No radio buttons found for content type selection');
            }
            
            // Hide both sections initially - target actual elements that exist
            $('.content-upload-area').hide();
            $('.upload-zone').hide();
            $('.content-text-area').hide();
            
            // Also try original IDs as fallback
            $('#logo-upload-section').hide();
            $('#text-input-section').hide();
            
            console.log('Initial state after hiding:');
            console.log('Content upload area found:', $('.content-upload-area').length);
            console.log('Content text area found:', $('.content-text-area').length);
            console.log('Upload zone found:', $('.upload-zone').length);
            console.log('Content upload area display:', $('.content-upload-area').css('display'));
            console.log('Content text area display:', $('.content-text-area').css('display'));
            
            // Trigger change for the initially checked radio button to show the correct section
            const $logoRadioCheck = $('input[name="content_type"][value="logo"]');
            const $textRadioCheck = $('input[name="content_type"][value="text"]');
            
            if ($logoRadioCheck.is(':checked')) {
                console.log('Logo radio is checked, triggering change to show logo section');
                $logoRadioCheck.trigger('change');
            } else if ($textRadioCheck.is(':checked')) {
                console.log('Text radio is checked, triggering change to show text section');
                $textRadioCheck.trigger('change');
            } else {
                console.log('No radio checked, defaulting to logo');
                $logoRadioCheck.prop('checked', true).trigger('change');
            }
            
            console.log('=== RADIO BUTTON FALLBACK INITIALIZATION COMPLETE ===');
        }
        
        createButtonStyleWrapper() {
            console.log('Creating button-style wrapper for radio buttons');
            
            // Find the content type options container - try multiple selectors
            let $optionsContainer = $('.content-type-options');
            if ($optionsContainer.length === 0) {
                // Try to find the parent container of the radio buttons
                $optionsContainer = $('input[name="content_type"]').closest('.content-type-selection');
                console.log('Trying content-type-selection container:', $optionsContainer.length);
            }
            if ($optionsContainer.length === 0) {
                // Try to find any container that has the radio buttons
                $optionsContainer = $('input[name="content_type"]').parent().parent();
                console.log('Trying parent container:', $optionsContainer.length);
            }
            if ($optionsContainer.length === 0) {
                console.error('Content type options container not found');
                return;
            }
            
            console.log('Found options container:', $optionsContainer[0]);
            
            // Add button-style classes to existing options - work with label elements directly
            $optionsContainer.find('label').each(function() {
                const $option = $(this);
                const $radio = $option.find('input[type="radio"]');
                const contentType = $radio.val();
                
                console.log('Processing option for content type:', contentType);
                
                // Add data attribute for easier selection
                $option.attr('data-content-type', contentType);
                
                // Add button-style classes
                $option.addClass('content-type-option');
                
                // Add checkmark element
                if ($option.find('.checkmark').length === 0) {
                    $option.append('<div class="checkmark" style="display: none;">✓</div>');
                }
                
                // Add click handler
                $option.off('click.contentType').on('click.contentType', function(e) {
                    e.preventDefault();
                    console.log('Radio option clicked:', contentType);
                    
                    // Update radio button
                    $radio.prop('checked', true).trigger('change');
                    
                    // Update visual selection
                    $('label[data-content-type]').removeClass('selected');
                    $('label[data-content-type] .checkmark').hide();
                    
                    $option.addClass('selected');
                    $option.find('.checkmark').show();
                    
                    // Show/hide sections
                    if (contentType === 'logo') {
                        $('#text-input-section').hide();
                        $('#logo-upload-section').show();
                    } else if (contentType === 'text') {
                        $('#logo-upload-section').hide();
                        $('#text-input-section').show();
                    }
                });
            });
            
            // Select logo option by default
            const $logoOption = $('label[data-content-type="logo"]');
            if ($logoOption.length > 0) {
                $logoOption.addClass('selected');
                $logoOption.find('.checkmark').show();
                $logoOption.find('input[type="radio"]').prop('checked', true);
                console.log('Logo option selected by default');
            }
        }
        
        removeStepNavigationButtons() {
            // Remove any step navigation buttons that might exist
            $('.continue-btn, .back-btn, #step-1-continue, #step-2-continue, #step-3-continue, #step-2-back, #step-3-back, #final-back').remove();
            
            // Remove any buttons in wizard footers except the main action button
            $('.wizard-footer button:not(.add-to-cart-btn)').remove();
        }

        loadProductInfo() {
            if (!this.productId) {
                console.error('Product ID is not set for loading product info.');
                $('#wizard-product-name').text('Product');
                $('#wizard-product-image').attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik00MCA0MEg2MFY2MEg0MFY0MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN2Zz4K');
                return;
            }

            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_product_info',
                    product_id: this.productId,
                    nonce: wcCustomizerWizard.nonce
                },
                success: (response) => {
                    if (response.success && response.data.product_name) {
                        $('#wizard-product-name').text(response.data.product_name);
                        $('#wizard-product-image').attr('src', response.data.product_image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik00MCA0MEg2MFY2MEg0MFY0MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN2Zz4K');
                    } else {
                        console.error('Error loading product info:', response.data.message);
                        $('#wizard-product-name').text('Product');
                        $('#wizard-product-image').attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik00MCA0MEg2MFY2MEg0MFY0MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN2Zz4K');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error loading product info:', error);
                    $('#wizard-product-name').text('Product');
                    $('#wizard-product-image').attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik00MCA0MEg2MFY2MEg0MFY0MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN2Zz4K');
                }
            });
        }

        loadZones() {
            console.log('Loading zones for product:', this.productId);
            
            if (!this.productId) {
                console.error('No product ID available for loading zones');
                return;
            }
            
            const $grid = $('#zone-grid');
            this.showLoading($grid);
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_zones',
                    product_id: this.productId,
                    nonce: wcCustomizerWizard.nonce
                },
                success: (response) => {
                    console.log('Zones response:', response);
                    this.hideLoading($grid);
                    
                    if (response.success) {
                        this.availableZones = response.data.zones;
                        this.renderPositionTabs();
                        // Don't call updateUIAfterDataLoad here - it's for the old single customization flow
                    } else {
                        // Handle specific error codes
                        const errorCode = response.data.code || 'unknown';
                        let errorMessage = response.data.message || 'Failed to load zones';
                        
                        if (errorCode === 'no_zones_configured') {
                            errorMessage = 'No customization zones are available for this product. Please contact the store administrator.';
                        } else if (errorCode === 'no_configuration') {
                            errorMessage = 'This product does not support customization. Please contact the store administrator.';
                        }
                        
                        this.showError($grid, errorMessage);
                        // Close the wizard if no configuration is available
                        if (errorCode === 'no_configuration') {
                            setTimeout(() => {
                                this.closeWizard();
                            }, 3000);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load zones:', status, error);
                    console.error('Response:', xhr.responseText);
                    this.hideLoading($grid);
                    this.showError($grid, 'Failed to load zones. Please try again.');
                }
            });
        }

        renderZones(zones) {
            const $grid = $('#zone-grid');
            $grid.empty();
            
            zones.forEach(zone => {
                const $card = $(`
                    <div class="zone-card" data-zone-id="${zone.id}" data-zone-name="${zone.name}">
                        <div class="zone-image">
                            <img src="${this.getZoneImageUrl(zone.name)}" alt="${zone.name}">
                        </div>
                        <h4>${zone.name}</h4>
                        <div class="zone-availability">
                            ${zone.methods.includes('print') ? '<span class="method-available print">🔥 Print available</span>' : ''}
                            ${zone.methods.includes('embroidery') ? '<span class="method-available embroidery">🧵 Embroidery available</span>' : ''}
                        </div>
                    </div>
                `);
                $grid.append($card);
            });
        }

        getZoneImageUrl(zoneName) {
            // Fallback if plugin URL is undefined
            let pluginUrl = wcCustomizerWizard.pluginUrl;
            if (!pluginUrl || pluginUrl === 'undefined' || pluginUrl === '') {
                pluginUrl = '/ecom_master/wp-content/plugins/woocommerce-product-customizer/';
            }
            
            // Map zone names to appropriate mockup images
            const zoneImageMap = {
                'big-front': 'tshirt-front-center.png',
                'big-back': 'tshirt-back.png',
                'centre-of-chest': 'tshirt-front-center.png',
                'left-breast': 'tshirt-front-left.png',
                'right-breast': 'tshirt-front-right.png',
                'left-sleeve': 'tshirt-sleeve.png',
                'right-sleeve': 'tshirt-sleeve.png',
                'nape-of-neck': 'tshirt-neck.png'
            };
            
            const imageName = zoneImageMap[zoneName.toLowerCase().replace(/\s+/g, '-')] || 'tshirt-front-center.png';
            return `${pluginUrl}assets/images/zones/${imageName}`;
        }

        toggleZone(e) {
            const $card = $(e.currentTarget);
            const zoneId = parseInt($card.data('zone-id'));
            const zoneName = $card.data('zone-name');
            
            console.log('Zone clicked:', zoneId, zoneName);
            
            if ($card.hasClass('selected')) {
                $card.removeClass('selected');
                this.selectedZones = this.selectedZones.filter(z => z.id !== zoneId);
                console.log('Zone deselected. Remaining zones:', this.selectedZones.length);
            } else {
                $card.addClass('selected');
                this.selectedZones.push({ id: zoneId, name: zoneName });
                console.log('Zone selected. Total zones:', this.selectedZones.length);
            }
            
            this.updateZoneCounter();
            
            // Progressive disclosure: Show step 2 when zones are selected
            if (this.selectedZones.length > 0) {
                console.log('Showing step 2 - zones selected');
                let $step2 = $('#step-2');
                if ($step2.length === 0) {
                    $step2 = $('.wizard-step[data-step="2"]');
                    console.log('Step 2 found by data-step:', $step2.length);
                }
                
                if ($step2.length > 0) {
                    $step2.addClass('active').show().css({
                        'display': 'flex !important',
                        'opacity': '1 !important',
                        'transform': 'translateY(0) !important',
                        'pointer-events': 'auto !important',
                        'visibility': 'visible !important'
                    });
                    console.log('Step 2 shown successfully');
                } else {
                    console.error('Step 2 element not found!');
                }
            } else {
                console.log('Hiding step 2 - no zones selected');
                $('#step-2').removeClass('active').hide();
                $('.wizard-step[data-step="2"]').removeClass('active').hide();
            }
            
            this.autoSave();
        }

        updateZoneCounter() {
            const count = this.selectedZones.length;
            $('#position-count').text(count);
            $('.selected-count').text(`${count} ${wcCustomizerWizard.strings.selected}`);
            // Step navigation removed for single-page approach
        }

        renderPositionTabs() {
            console.log('=== RENDERING POSITION SELECTION ===');
            console.log('Available zones:', this.availableZones);
            
            const $zoneGrid = $('#zone-grid');
            $zoneGrid.empty();
            
            // Create position selection container
            const positionSelection = $('<div class="position-selection-container"></div>');
            
            // Add title and description
            positionSelection.append(`
                <div class="position-selection-header">
                    <h4>Select Customization Positions</h4>
                    <p>Choose where you want to place your customization on the product. You can select multiple positions.</p>
                </div>
            `);
            
            // Create position boxes grid
            const positionGrid = $('<div class="position-grid"></div>');
            
            // Create position boxes for each available zone
            this.availableZones.forEach((zone, index) => {
                const positionBox = $(`
                    <div class="position-box" data-zone-id="${zone.id}" id="position-box-${zone.id}">
                        <div class="position-thumbnail">
                            <img src="${this.getZoneImageUrl(zone.name)}" alt="${zone.name}" onerror="this.src='${wcCustomizerWizard.pluginUrl}assets/images/default-zone.png'">
                        </div>
                        <div class="position-info">
                            <h5 class="position-name">${zone.name}</h5>
                            <p class="position-description">${zone.description || 'Customization position'}</p>
                        </div>
                        <div class="position-checkbox">
                            <input type="checkbox" id="position-${zone.id}" data-zone-id="${zone.id}">
                            <label for="position-${zone.id}"></label>
                        </div>
                    </div>
                `);
                
                positionGrid.append(positionBox);
                
                // Initialize position config
                this.positionConfigs[zone.id] = {
                    zone_id: zone.id,
                    zone_name: zone.name,
                    method: null,
                    content_type: null,
                    file_path: null,
                    text_line_1: '',
                    text_line_2: '',
                    text_line_3: '',
                    text_font: 'arial',
                    text_color: 'white',
                    text_notes: '',
                    logo_alternative: '',
                    logo_notes: ''
                };
            });
            
            positionSelection.append(positionGrid);
            
            // Create configuration section (initially hidden)
            const configSection = $(`
                <div class="position-configuration-section" id="position-configuration-section" style="display: none;">
                    <div class="config-header">
                        <h4>Configure Selected Positions</h4>
                        <p>Configure the method and content for each selected position.</p>
                    </div>
                    <div class="config-content" id="config-content">
                        <!-- Dynamic configuration content will be inserted here -->
                    </div>
                </div>
            `);
            
            $zoneGrid.append(positionSelection);
            $zoneGrid.append(configSection);
            
            // Bind position selection events
            this.bindPositionSelectionEvents();
        }

        bindPositionSelectionEvents() {
            console.log('=== BINDING POSITION SELECTION EVENTS ===');
            
            // Handle position checkbox changes
            $(document).on('change', '.position-box input[type="checkbox"]', (e) => {
                const zoneId = parseInt($(e.target).data('zone-id'));
                const isSelected = $(e.target).is(':checked');
                
                console.log('Position checkbox changed:', zoneId, isSelected);
                
                // Update position box visual state
                const $positionBox = $(e.target).closest('.position-box');
                if (isSelected) {
                    $positionBox.addClass('selected');
                } else {
                    $positionBox.removeClass('selected');
                    // Clear configuration for this position
                    this.positionConfigs[zoneId] = {
                        zone_id: zoneId,
                        zone_name: this.availableZones.find(z => z.id === zoneId)?.name || '',
                        method: null,
                        content_type: null,
                        file_path: null,
                        text_line_1: '',
                        text_line_2: '',
                        text_line_3: '',
                        text_font: 'arial',
                        text_color: 'white',
                        text_notes: '',
                        logo_alternative: '',
                        logo_notes: ''
                    };
                }
                
                // Update configuration section visibility
                this.updateConfigurationSection();
            });
            
            // Handle position box clicks (for better UX)
            $(document).on('click', '.position-box', (e) => {
                // Don't trigger if clicking the checkbox directly
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label')) {
                    return;
                }
                
                const $checkbox = $(e.currentTarget).find('input[type="checkbox"]');
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            });
        }

        updateConfigurationSection() {
            console.log('=== UPDATING CONFIGURATION SECTION ===');
            
            const selectedPositions = this.getSelectedPositions();
            const $configSection = $('#position-configuration-section');
            const $configContent = $('#config-content');
            
            console.log('Selected positions:', selectedPositions);
            
            if (selectedPositions.length === 0) {
                $configSection.hide();
                return;
            }
            
            // Show configuration section
            $configSection.show();
            
            // Clear existing content
            $configContent.empty();
            
            // Create tab navigation
            const tabNav = $('<div class="config-tab-nav"></div>');
            const tabContent = $('<div class="config-tab-content"></div>');
            
            // Create tabs for each selected position
            selectedPositions.forEach((zoneId, index) => {
                console.log('Processing zone ID:', zoneId);
                const zone = this.availableZones.find(z => parseInt(z.id) === parseInt(zoneId));
                if (!zone) {
                    console.log('Zone not found for ID:', zoneId);
                    return;
                }
                
                const isActive = index === 0;
                
                // Create tab button
                const tabButton = $(`
                    <button class="config-tab-btn ${isActive ? 'active' : ''}" data-zone-id="${zoneId}">
                        ${zone.name}
                        <span class="tab-status">Not configured</span>
                    </button>
                `);
                
                tabNav.append(tabButton);
                
                // Create tab content
                const tabPanel = $(`
                    <div class="config-tab-panel ${isActive ? 'active' : ''}" data-zone-id="${zoneId}">
                        <div class="config-position-content">
                            <div class="method-selection" id="method-selection-${zoneId}">
                                <!-- Methods will be loaded here -->
                            </div>
                            <div class="content-selection" id="content-selection-${zoneId}" style="display: none;">
                                <!-- Content selection will be shown here -->
                            </div>
                        </div>
                    </div>
                `);
                
                tabContent.append(tabPanel);
                
                // Load methods for this position if it's the active tab
                if (isActive) {
                    this.loadMethodsForPosition(zoneId);
                }
            });
            
            $configContent.append(tabNav);
            $configContent.append(tabContent);
            
            // Bind tab events
            this.bindConfigTabEvents();
        }

        bindConfigTabEvents() {
            console.log('=== BINDING CONFIG TAB EVENTS ===');
            
            // Handle tab button clicks
            $(document).on('click', '.config-tab-btn', (e) => {
                const zoneId = parseInt($(e.currentTarget).data('zone-id'));
                console.log('Tab clicked for zone:', zoneId);
                
                // Remove active class from all tabs and panels
                $('.config-tab-btn').removeClass('active');
                $('.config-tab-panel').removeClass('active');
                
                // Add active class to clicked tab and corresponding panel
                $(e.currentTarget).addClass('active');
                $(`.config-tab-panel[data-zone-id="${zoneId}"]`).addClass('active');
                
                // Load methods for this position if not already loaded
                const $methodSelection = $(`#method-selection-${zoneId}`);
                if ($methodSelection.children().length === 0) {
                    this.loadMethodsForPosition(zoneId);
                }
            });
        }

        getSelectedPositions() {
            const selected = [];
            $('.position-box input[type="checkbox"]:checked').each(function() {
                selected.push(parseInt($(this).data('zone-id')));
            });
            return selected;
        }

        switchPositionTab(zoneId) {
            console.log('=== SWITCHING TO POSITION TAB ===', zoneId);
            
            // Update active tab
            $('.position-tab-btn').removeClass('active');
            $(`.position-tab-btn[data-zone-id="${zoneId}"]`).addClass('active');
            
            // Update active pane
            $('.tab-pane').removeClass('active');
            $(`.tab-pane[data-zone-id="${zoneId}"]`).addClass('active');
            
            // Set current position
            this.currentPositionId = zoneId;
            
            // Load methods for this position
            this.loadMethodsForPosition(zoneId);
        }

        bindTabEvents() {
            // Tab switching
            $(document).on('click', '.position-tab-btn', (e) => {
                e.preventDefault();
                const zoneId = parseInt($(e.currentTarget).data('zone-id'));
                this.switchPositionTab(zoneId);
            });
            
            // Remove position
            $(document).on('click', '.remove-position-btn', (e) => {
                e.stopPropagation();
                const zoneId = parseInt($(e.currentTarget).data('zone-id'));
                this.removePosition(zoneId);
            });
            
            // Add position
            $(document).on('click', '#add-position-btn', (e) => {
                e.preventDefault();
                this.showAddPositionModal();
            });
        }

        removePosition(zoneId) {
            console.log('=== REMOVING POSITION ===', zoneId);
            
            // Remove from configs
            delete this.positionConfigs[zoneId];
            
            // Remove tab and pane
            $(`.position-tab-btn[data-zone-id="${zoneId}"]`).remove();
            $(`.tab-pane[data-zone-id="${zoneId}"]`).remove();
            
            // Switch to another tab if this was active
            if (this.currentPositionId === zoneId) {
                const remainingTabs = $('.position-tab-btn');
                if (remainingTabs.length > 0) {
                    const nextZoneId = parseInt(remainingTabs.first().data('zone-id'));
                    this.switchPositionTab(nextZoneId);
                } else {
                    this.currentPositionId = null;
                }
            }
        }

        showAddPositionModal() {
            // Create modal for adding positions
            const modal = $(`
                <div class="add-position-modal">
                    <div class="modal-content">
                        <h3>Add Position</h3>
                        <div class="available-positions">
                            ${this.availableZones.map(zone => `
                                <button type="button" class="position-option" data-zone-id="${zone.id}">
                                    ${zone.name}
                                </button>
                            `).join('')}
                        </div>
                        <button type="button" class="close-modal">Close</button>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            // Bind events
            modal.on('click', '.position-option', (e) => {
                const zoneId = parseInt($(e.currentTarget).data('zone-id'));
                this.addPosition(zoneId);
                modal.remove();
            });
            
            modal.on('click', '.close-modal', () => {
                modal.remove();
            });
        }

        addPosition(zoneId) {
            console.log('=== ADDING POSITION ===', zoneId);
            
            const zone = this.availableZones.find(z => z.id === zoneId);
            if (!zone) return;
            
            // Add to configs if not already there
            if (!this.positionConfigs[zoneId]) {
                this.positionConfigs[zoneId] = {
                    zone_id: zone.id,
                    zone_name: zone.name,
                    method: null,
                    content_type: null,
                    file_path: null,
                    text_line_1: '',
                    text_line_2: '',
                    text_line_3: '',
                    text_font: 'arial',
                    text_color: 'white',
                    text_notes: '',
                    logo_alternative: '',
                    logo_notes: ''
                };
                
                // Add tab and pane
                const tabId = `position-tab-${zone.id}`;
                const tabBtn = $(`
                    <button type="button" class="position-tab-btn" data-zone-id="${zone.id}" id="${tabId}">
                        ${zone.name}
                        <span class="remove-position-btn" data-zone-id="${zone.id}">×</span>
                    </button>
                `);
                
                const tabPane = $(`
                    <div class="tab-pane" data-zone-id="${zone.id}" id="position-pane-${zone.id}">
                        <div class="position-config">
                            <h4>Configure ${zone.name}</h4>
                            <div class="method-selection" id="method-selection-${zone.id}"></div>
                            <div class="content-selection" id="content-selection-${zone.id}"></div>
                        </div>
                    </div>
                `);
                
                $('.tab-navigation').append(tabBtn);
                $('.tab-content').append(tabPane);
                
                // Switch to new tab
                this.switchPositionTab(zoneId);
            }
        }

        loadMethodsForPosition(zoneId) {
            console.log('=== LOADING METHODS FOR POSITION ===', zoneId);
            
            const $methodContainer = $(`#method-selection-${zoneId}`);
            this.showLoading($methodContainer);
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_methods',
                    product_id: this.productId,
                    zone_id: zoneId,
                    nonce: wcCustomizerWizard.nonce
                },
                success: (response) => {
                    console.log('Methods response for position', zoneId, ':', response);
                    this.hideLoading($methodContainer);
                    
                    if (response.success) {
                        this.renderMethodsForPosition(zoneId, response.data.methods);
                    } else {
                        console.error('Failed to load methods for position:', response.data.message);
                        $methodContainer.html('<p class="error">Failed to load methods</p>');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading methods for position:', error);
                    this.hideLoading($methodContainer);
                    $methodContainer.html('<p class="error">Error loading methods</p>');
                }
            });
        }

        renderMethodsForPosition(zoneId, methods) {
            console.log('=== RENDERING METHODS FOR POSITION ===', zoneId, methods);
            
            const $container = $(`#method-selection-${zoneId}`);
            $container.empty();
            
            if (!methods || methods.length === 0) {
                $container.html('<p class="no-methods">No methods available for this position</p>');
                return;
            }
            
            const methodGrid = $('<div class="method-grid"></div>');
            
            methods.forEach(method => {
                const methodCard = $(`
                    <div class="method-card" data-method="${method.name}">
                        <div class="method-image">
                            <img src="${this.getMethodImageUrl(method.name)}" alt="${method.name}" onerror="this.style.display='none'">
                        </div>
                        <div class="method-info">
                            <h4>${method.name}</h4>
                            <p>${method.description || ''}</p>
                        </div>
                    </div>
                `);
                
                methodGrid.append(methodCard);
            });
            
            $container.append(methodGrid);
            
            // Bind method selection for this position
            this.bindMethodSelectionForPosition(zoneId);
        }

        bindMethodSelectionForPosition(zoneId) {
            $(document).off(`click.method-${zoneId}`);
            $(document).on(`click.method-${zoneId}`, `#method-selection-${zoneId} .method-card`, (e) => {
                e.preventDefault();
                const method = $(e.currentTarget).data('method');
                this.selectMethodForPosition(zoneId, method);
            });
        }

        selectMethodForPosition(zoneId, method) {
            console.log('=== SELECTING METHOD FOR POSITION ===', zoneId, method);
            
            // Update visual selection
            $(`#method-selection-${zoneId} .method-card`).removeClass('selected');
            $(`#method-selection-${zoneId} .method-card[data-method="${method}"]`).addClass('selected');
            
            // Update config
            this.positionConfigs[zoneId].method = method;
            
            // Show content selection for this position
            this.showContentSelectionForPosition(zoneId);
        }

        showContentSelectionForPosition(zoneId) {
            console.log('=== SHOWING CONTENT SELECTION FOR POSITION ===', zoneId);
            
            const $contentContainer = $(`#content-selection-${zoneId}`);
            if ($contentContainer.length === 0) {
                console.log('Content selection container not found for zone:', zoneId);
                return;
            }
            
            $contentContainer.empty();
            
            // Add content type selection
            const contentTypeSelection = $(`
                <div class="content-type-selection">
                    <h4>Choose Content Type</h4>
                    <div class="content-type-grid">
                        <div class="content-type-card" data-content-type="logo">
                            <div class="content-type-icon">🖼️</div>
                            <h4>Upload Logo</h4>
                            <p>Upload your own logo file</p>
                        </div>
                        <div class="content-type-card" data-content-type="text">
                            <div class="content-type-icon">📝</div>
                            <h4>Custom Text</h4>
                            <p>Add custom text</p>
                        </div>
                    </div>
                </div>
            `);
            
            $contentContainer.append(contentTypeSelection);
            
            // Show the content selection container
            $contentContainer.show();
            
            // Bind content type selection for this position
            this.bindContentTypeSelectionForPosition(zoneId);
        }

        bindContentTypeSelectionForPosition(zoneId) {
            $(document).off(`click.content-${zoneId}`);
            $(document).on(`click.content-${zoneId}`, `#content-selection-${zoneId} .content-type-card`, (e) => {
                e.preventDefault();
                const contentType = $(e.currentTarget).data('content-type');
                this.selectContentTypeForPosition(zoneId, contentType);
            });
        }

        selectContentTypeForPosition(zoneId, contentType) {
            console.log('=== SELECTING CONTENT TYPE FOR POSITION ===', zoneId, contentType);
            
            // Update visual selection
            $(`#content-selection-${zoneId} .content-type-card`).removeClass('selected');
            $(`#content-selection-${zoneId} .content-type-card[data-content-type="${contentType}"]`).addClass('selected');
            
            // Update config
            this.positionConfigs[zoneId].content_type = contentType;
            
            // Show content input for this position
            this.showContentInputForPosition(zoneId, contentType);
        }

        showContentInputForPosition(zoneId, contentType) {
            console.log('=== SHOWING CONTENT INPUT FOR POSITION ===', zoneId, contentType);
            
            const $contentContainer = $(`#content-selection-${zoneId}`);
            
            // Remove existing content input
            $contentContainer.find('.content-input').remove();
            
            if (contentType === 'logo') {
                // Add logo upload section for this position
                const logoSection = $(`
                    <div class="content-input logo-upload-section" id="logo-upload-section-${zoneId}">
                        <div class="upload-container">
                            <div class="upload-header">
                                <div class="upload-title">
                                    <span class="checkmark-icon">✓</span>
                                    <h4>Upload your own logo</h4>
                                </div>
                            </div>
                            
                            <div class="upload-area" id="upload-area-${zoneId}">
                                <button type="button" class="choose-file-btn" id="add-logo-btn-${zoneId}">
                                    <span class="upload-icon">↗</span>
                                    Choose file
                                </button>
                                <input type="file" id="file-input-${zoneId}" style="display: none;" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                                
                                <p class="drag-drop-text">
                                    Drag 'n' drop some files here, or click to select files
                                </p>
                                
                                <p class="file-specs">
                                    JPG, PNG, EPS, AI, PDF Max size: 8MB
                                </p>
                                
                                <p class="reassurance-message">
                                    Don't worry how it looks, we will make it look great and send a proof before we add to your products!
                                </p>
                            </div>
                            
                            <div class="upload-progress" id="upload-progress-${zoneId}" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <span class="progress-text">Uploading...</span>
                            </div>
                            
                            <div class="uploaded-file" id="uploaded-file-${zoneId}" style="display: none;">
                                <div class="file-preview">
                                    <img id="uploaded-image-preview-${zoneId}" src="" alt="Uploaded logo" style="display: none;">
                                    <div class="file-info">
                                        <span class="file-name"></span>
                                        <button type="button" class="remove-file-btn">×</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alternatively section -->
                            <div class="alternative-section">
                                <div class="alternative-divider">
                                    <span class="divider-text">alternatively...</span>
                                </div>
                                
                                <div class="alternative-options">
                                    <label class="alternative-option">
                                        <input type="radio" name="logo_alternative_${zoneId}" value="contact_later">
                                        <span class="radio-custom"></span>
                                        <span class="option-text">
                                            Don't have your logo to hand? Don't worry, select here and we will contact after you place your order.
                                        </span>
                                    </label>
                                    
                                    <label class="alternative-option">
                                        <input type="radio" name="logo_alternative_${zoneId}" value="already_have">
                                        <span class="radio-custom"></span>
                                        <span class="option-text">
                                            You already have my logo, it's just not in my account (no setup fee will be charged)
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Notes section -->
                            <div class="notes-section">
                                <label for="logo-notes-${zoneId}" class="notes-label">
                                    Notes
                                </label>
                                <textarea 
                                    id="logo-notes-${zoneId}" 
                                    name="logo_notes_${zoneId}" 
                                    placeholder="Please let us know if you have any special requirements"
                                    rows="3"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                `);
                
                $contentContainer.append(logoSection);
                
                // Bind file upload events for this position
                this.bindFileUploadForPosition(zoneId);
                
                // Bind alternative option events for this position
                this.bindAlternativeOptionsForPosition(zoneId);
                
            } else if (contentType === 'text') {
                // Add text input section for this position
                const textSection = $(`
                    <div class="content-input text-input-section" id="text-input-section-${zoneId}">
                        <div class="text-config-container">
                            <div class="text-config-header">
                                <h4>Configure your text logo</h4>
                            </div>
                            
                            <div class="text-config-form">
                                <!-- Text Input Fields -->
                                <div class="text-input-fields">
                                    <div class="text-input-group">
                                        <label for="text-line-1-${zoneId}" class="text-input-label required">
                                            Text Line 1*
                                        </label>
                                        <input 
                                            type="text" 
                                            id="text-line-1-${zoneId}" 
                                            name="text_line_1_${zoneId}" 
                                            placeholder="e.g Workwear Express"
                                            maxlength="50"
                                            required
                                        >
                                    </div>
                                    
                                    <div class="text-input-group">
                                        <label for="text-line-2-${zoneId}" class="text-input-label">
                                            Text Line 2 (Optional)
                                        </label>
                                        <input 
                                            type="text" 
                                            id="text-line-2-${zoneId}" 
                                            name="text_line_2_${zoneId}" 
                                            placeholder=""
                                            maxlength="50"
                                        >
                                    </div>
                                    
                                    <div class="text-input-group">
                                        <label for="text-line-3-${zoneId}" class="text-input-label">
                                            Text Line 3 (Optional)
                                        </label>
                                        <input 
                                            type="text" 
                                            id="text-line-3-${zoneId}" 
                                            name="text_line_3_${zoneId}" 
                                            placeholder=""
                                            maxlength="50"
                                        >
                                    </div>
                                </div>
                                
                                <!-- Font and Color Selection -->
                                <div class="text-options">
                                    <div class="option-group">
                                        <label for="text-font-${zoneId}" class="option-label">
                                            Font
                                        </label>
                                        <select id="text-font-${zoneId}" name="text_font_${zoneId}" class="text-select">
                                            <option value="arial" selected>Arial</option>
                                            <option value="helvetica">Helvetica</option>
                                            <option value="times">Times New Roman</option>
                                            <option value="courier">Courier</option>
                                            <option value="verdana">Verdana</option>
                                            <option value="georgia">Georgia</option>
                                        </select>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label">
                                            Colour
                                        </label>
                                        <div class="color-options">
                                            <label class="color-option">
                                                <input type="radio" name="text_color_${zoneId}" value="white" checked>
                                                <span class="color-radio-custom white"></span>
                                                <span class="color-label">White</span>
                                            </label>
                                            <label class="color-option">
                                                <input type="radio" name="text_color_${zoneId}" value="black">
                                                <span class="color-radio-custom black"></span>
                                                <span class="color-label">Black</span>
                                            </label>
                                            <label class="color-option">
                                                <input type="radio" name="text_color_${zoneId}" value="red">
                                                <span class="color-radio-custom red"></span>
                                                <span class="color-label">Red</span>
                                            </label>
                                            <label class="color-option">
                                                <input type="radio" name="text_color_${zoneId}" value="blue">
                                                <span class="color-radio-custom blue"></span>
                                                <span class="color-label">Blue</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Text Preview Section -->
                                <div class="text-preview-section">
                                    <label class="preview-label">
                                        Text Preview
                                    </label>
                                    <div class="text-preview-area" id="text-preview-area-${zoneId}">
                                        <div class="preview-content" id="preview-content-${zoneId}">
                                            <span class="preview-text">Your text will appear here...</span>
                                        </div>
                                        <button type="button" class="preview-btn" id="preview-btn-${zoneId}">
                                            Preview
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Notes Section -->
                                <div class="text-notes-section">
                                    <label for="text-notes-${zoneId}" class="notes-label">
                                        Notes
                                    </label>
                                    <textarea 
                                        id="text-notes-${zoneId}" 
                                        name="text_notes_${zoneId}" 
                                        placeholder="Please let us know if you have any special requirements"
                                        rows="3"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                $contentContainer.append(textSection);
                
                // Bind text input events for this position
                this.bindTextInputForPosition(zoneId);
            }
        }

        bindFileUploadForPosition(zoneId) {
            // File input change
            $(document).off(`change.file-${zoneId}`);
            $(document).on(`change.file-${zoneId}`, `#file-input-${zoneId}`, (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadFileForPosition(zoneId, file);
                }
            });
            
            // Choose file button
            $(document).off(`click.file-${zoneId}`);
            $(document).on(`click.file-${zoneId}`, `#add-logo-btn-${zoneId}`, (e) => {
                e.preventDefault();
                $(`#file-input-${zoneId}`).click();
            });
            
            // Remove file button
            $(document).off(`click.remove-${zoneId}`);
            $(document).on(`click.remove-${zoneId}`, `#uploaded-file-${zoneId} .remove-file-btn`, (e) => {
                e.preventDefault();
                this.removeFileForPosition(zoneId);
            });
        }

        bindAlternativeOptionsForPosition(zoneId) {
            console.log('=== BINDING ALTERNATIVE OPTIONS FOR POSITION ===', zoneId);
            
            // Bind radio button changes for alternative options
            $(document).off(`change.alternative-${zoneId}`);
            $(document).on(`change.alternative-${zoneId}`, `#logo-upload-section-${zoneId} input[name="logo_alternative_${zoneId}"]`, (e) => {
                const selectedValue = $(e.target).val();
                console.log('Alternative option selected:', selectedValue);
                
                // Update position config
                this.positionConfigs[zoneId].logo_alternative = selectedValue;
                
                console.log('Updated position config:', this.positionConfigs[zoneId]);
            });
            
            // Bind logo notes input
            $(document).off(`input.logo-notes-${zoneId}`);
            $(document).on(`input.logo-notes-${zoneId}`, `#logo-notes-${zoneId}`, (e) => {
                const notesValue = $(e.target).val();
                console.log('Logo notes updated:', notesValue);
                
                // Update position config
                this.positionConfigs[zoneId].logo_notes = notesValue;
                
                console.log('Updated position config with notes:', this.positionConfigs[zoneId]);
            });
        }

        bindTextInputForPosition(zoneId) {
            // Text input events
            $(document).off(`input.text-${zoneId}`);
            $(document).on(`input.text-${zoneId}`, `#text-line-1-${zoneId}, #text-line-2-${zoneId}, #text-line-3-${zoneId}, #text-notes-${zoneId}`, (e) => {
                this.updateTextPreviewForPosition(zoneId);
            });
            
            $(document).off(`change.text-${zoneId}`);
            $(document).on(`change.text-${zoneId}`, `#text-font-${zoneId}, input[name="text_color_${zoneId}"]`, (e) => {
                this.updateTextPreviewForPosition(zoneId);
            });
            
            $(document).off(`click.preview-${zoneId}`);
            $(document).on(`click.preview-${zoneId}`, `#preview-btn-${zoneId}`, (e) => {
                e.preventDefault();
                this.updateTextPreviewForPosition(zoneId, true);
            });
        }

        uploadFileForPosition(zoneId, file) {
            console.log('=== UPLOADING FILE FOR POSITION ===', zoneId, file);
            console.log('File details:', {
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: file.lastModified
            });
            
            // Validate file
            if (!this.validateFile(file)) {
                console.log('File validation failed');
                return;
            }
            
            console.log('File validation passed, preparing upload...');
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('session_id', this.sessionId);
            formData.append('action', 'wc_customizer_upload_file');
            formData.append('nonce', wcCustomizerWizard.uploadNonce);
            
            console.log('FormData prepared:', {
                sessionId: this.sessionId,
                action: 'wc_customizer_upload_file',
                nonce: wcCustomizerWizard.uploadNonce,
                ajaxUrl: wcCustomizerWizard.ajaxUrl
            });
            
            // Show progress
            $(`#upload-progress-${zoneId}`).show();
            $(`#upload-progress-${zoneId} .progress-fill`).css('width', '0%');
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            $(`#upload-progress-${zoneId} .progress-fill`).css('width', percentComplete + '%');
                            console.log('Upload progress:', percentComplete + '%');
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    console.log('Upload success response:', response);
                    $(`#upload-progress-${zoneId}`).hide();
                    
                    if (response.success) {
                        console.log('Upload successful, storing file data:', response.data);
                        // Store file data for this position
                        this.positionConfigs[zoneId].uploadedFile = response.data;
                        this.positionConfigs[zoneId].file_path = response.data.filepath;
                        this.showUploadedFileForPosition(zoneId, response.data.original_name);
                    } else {
                        console.log('Upload failed:', response.data);
                        alert(response.data.message || wcCustomizerWizard.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Upload error:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                    $(`#upload-progress-${zoneId}`).hide();
                    alert('Upload failed: ' + error);
                }
            });
        }

        showUploadedFileForPosition(zoneId, filename) {
            $(`#uploaded-file-${zoneId} .file-name`).text(filename);
            
            // Show image preview if it's an image file
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            if (imageExtensions.includes(extension) && this.positionConfigs[zoneId].uploadedFile && this.positionConfigs[zoneId].uploadedFile.url) {
                $(`#uploaded-image-preview-${zoneId}`).attr('src', this.positionConfigs[zoneId].uploadedFile.url).show();
            } else {
                $(`#uploaded-image-preview-${zoneId}`).hide();
            }
            
            $(`#uploaded-file-${zoneId}`).show();
            $(`#upload-area-${zoneId}`).hide();
        }

        removeFileForPosition(zoneId) {
            this.positionConfigs[zoneId].uploadedFile = null;
            this.positionConfigs[zoneId].file_path = null;
            $(`#uploaded-file-${zoneId}`).hide();
            $(`#uploaded-image-preview-${zoneId}`).hide().attr('src', '');
            $(`#upload-area-${zoneId}`).show();
            $(`#file-input-${zoneId}`).val('');
        }

        updateTextPreviewForPosition(zoneId, forceUpdate = false) {
            const $preview = $(`#preview-content-${zoneId} .preview-text`);
            const $previewArea = $(`#text-preview-area-${zoneId}`);
            
            // Get text from this position's fields
            const textLine1 = $(`#text-line-1-${zoneId}`).val() || '';
            const textLine2 = $(`#text-line-2-${zoneId}`).val() || '';
            const textLine3 = $(`#text-line-3-${zoneId}`).val() || '';
            
            // Combine all text lines
            const allTextLines = [textLine1, textLine2, textLine3].filter(line => line.trim());
            const combinedText = allTextLines.join(' ');
            
            // Get font and color
            const selectedFont = $(`#text-font-${zoneId}`).val() || 'arial';
            const selectedColor = $(`input[name="text_color_${zoneId}"]:checked`).val() || 'white';
            
            console.log('Text preview debug:', {
                zoneId: zoneId,
                selectedFont: selectedFont,
                selectedColor: selectedColor,
                colorValue: this.getColorValue(selectedColor),
                previewElement: $preview[0],
                previewAreaElement: $previewArea[0]
            });
            
            // Get notes
            const textNotes = $(`#text-notes-${zoneId}`).val() || '';
            
            // Save form data to positionConfigs
            if (this.positionConfigs[zoneId]) {
                this.positionConfigs[zoneId].text_line_1 = textLine1;
                this.positionConfigs[zoneId].text_line_2 = textLine2;
                this.positionConfigs[zoneId].text_line_3 = textLine3;
                this.positionConfigs[zoneId].text_font = selectedFont;
                this.positionConfigs[zoneId].text_color = selectedColor;
                this.positionConfigs[zoneId].text_notes = textNotes;
                
                console.log('Updated position config for zone', zoneId, ':', this.positionConfigs[zoneId]);
            }
            
            if (combinedText.trim()) {
                // Update preview text
                $preview.text(combinedText).removeClass('empty');
                
                // Apply font and color styling
                $preview.css({
                    'font-family': this.getFontFamily(selectedFont),
                    'color': this.getColorValue(selectedColor),
                    'font-weight': 'bold',
                    'font-size': '18px',
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
                // Apply !important styles using attr method
                $preview.attr('style', 
                    'font-family: ' + this.getFontFamily(selectedFont) + ' !important; ' +
                    'color: ' + this.getColorValue(selectedColor) + ' !important; ' +
                    'font-weight: bold !important; ' +
                    'font-size: 18px !important; ' +
                    'display: block !important; ' +
                    'visibility: visible !important; ' +
                    'opacity: 1 !important;'
                );
                
                // Update preview area styling
                $previewArea.css({
                    'background-color': selectedColor === 'white' ? '#333' : '#fff',
                    'border': selectedColor === 'white' ? '2px solid #333' : '2px solid #ddd'
                });
            } else {
                // Reset to placeholder
                $preview.text('Your text will appear here...').addClass('empty');
                $preview.css({
                    'font-family': 'inherit',
                    'color': '#6b7280',
                    'font-weight': 'normal',
                    'font-size': 'inherit'
                });
                
                $previewArea.css({
                    'background-color': '#fff',
                    'border': '1px solid #d1d5db'
                });
            }
        }

        loadMethods() {
            console.log('Loading methods');
            
            const $selection = $('#method-selection');
            console.log('Method selection container found:', $selection.length);
            this.showLoading($selection);
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_get_methods',
                    product_id: this.productId,
                    nonce: wcCustomizerWizard.nonce
                },
                success: (response) => {
                    console.log('Methods response:', response);
                    this.hideLoading($selection);
                    
                    if (response.success) {
                        console.log('Methods data:', response.data.methods);
                        this.renderMethods(response.data.methods);
                        // Update method availability after methods are rendered
                        this.updateMethodAvailability();
                        // Update method UI after methods are rendered
                        this.updateMethodUI();
                    } else {
                        // Handle specific error codes
                        const errorCode = response.data.code || 'unknown';
                        let errorMessage = response.data.message || 'Failed to load methods';
                        
                        if (errorCode === 'no_types_configured') {
                            errorMessage = 'No customization methods are available for this product. Please contact the store administrator.';
                        } else if (errorCode === 'no_configuration') {
                            errorMessage = 'This product does not support customization. Please contact the store administrator.';
                        }
                        
                        console.error('Methods response failed:', errorMessage);
                        this.showError($selection, errorMessage);
                        // Close the wizard if no configuration is available
                        if (errorCode === 'no_configuration') {
                            setTimeout(() => {
                                this.closeWizard();
                            }, 3000);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load methods:', status, error);
                    console.error('Response:', xhr.responseText);
                    this.hideLoading($selection);
                    this.showError($selection, 'Failed to load methods. Please try again.');
                }
            });
        }

        renderMethods(methods) {
            console.log('Rendering methods:', methods);
            const $selection = $('#method-selection');
            console.log('Method selection container:', $selection.length, $selection);
            
            if ($selection.length === 0) {
                console.error('Method selection container not found!');
                console.log('Available elements with method in ID:', $('[id*="method"]'));
                console.log('All divs:', $('div[id]'));
                return;
            }
            
            $selection.empty();
            
            methods.forEach(method => {
                const $card = $(`
                    <div class="method-card" data-method="${method.name}">
                        <div class="method-image">
                            <img src="${this.getMethodImageUrl(method.name)}" alt="${method.name} Sample">
                        </div>
                        <div class="method-info">
                            <h4>
                                ${this.capitalizeFirst(method.name)}
                                <span class="checkmark" style="display: none;">✓</span>
                                ${method.name === 'print' ? '<span class="info-icon">🔥</span>' : ''}
                            </h4>
                            ${method.name === 'embroidery' ? '<p class="method-subtitle">(Stitching)</p>' : ''}
                            <p class="method-description">${method.description}</p>
                        </div>
                    </div>
                `);
                console.log('Appending method card:', method.name, $card);
                $selection.append($card);
            });
            console.log('Total method cards appended:', $selection.find('.method-card').length);
        }

        getMethodImageUrl(methodName) {
            // Fallback if plugin URL is undefined
            let pluginUrl = wcCustomizerWizard.pluginUrl;
            if (!pluginUrl || pluginUrl === 'undefined' || pluginUrl === '') {
                pluginUrl = '/ecom_master/wp-content/plugins/woocommerce-product-customizer/';
            }
            
            return `${pluginUrl}assets/images/methods/${methodName}-sample.svg`;
        }

        selectMethod(e) {
            const $card = $(e.currentTarget);
            const method = $card.data('method');
            
            console.log('Method clicked:', method);
            
            $('.method-card').removeClass('selected');
            $('.method-card .checkmark').hide();
            
            $card.addClass('selected');
            $card.find('.checkmark').show();
            
            this.selectedMethod = method;
            
            const statusText = `${this.capitalizeFirst(method)} ${wcCustomizerWizard.strings?.selected?.toLowerCase() || 'selected'}`;
            $('#method-selected-text').text(statusText);
            
            // Progressive disclosure: Show step 3 when method is selected
            console.log('Showing step 3 - method selected');
            let $step3 = $('#step-3');
            if ($step3.length === 0) {
                $step3 = $('.wizard-step[data-step="3"]');
                console.log('Step 3 found by data-step:', $step3.length);
            }
            
            if ($step3.length > 0) {
                $step3.addClass('active').show().css({
                    'display': 'flex !important',
                    'opacity': '1 !important',
                    'transform': 'translateY(0) !important',
                    'pointer-events': 'auto !important',
                    'visibility': 'visible !important'
                });
                console.log('Step 3 shown successfully');
            } else {
                console.error('Step 3 element not found!');
            }
            
            this.autoSave();
        }

        updateMethodAvailability() {
            console.log('Updating method availability for selected zones:', this.selectedZones);
            console.log('Available method cards:', $('.method-card').length);
            
            // If no zones selected, show all methods
            if (!this.selectedZones || this.selectedZones.length === 0) {
                console.log('No zones selected, showing all methods');
                $('.method-card').show();
                return;
            }
            
            // Filter methods based on selected zones
            $('.method-card').each((index, card) => {
                const $card = $(card);
                const method = $card.data('method');
                console.log(`Checking method: ${method}`);
                
                // Check if any selected zone supports this method
                const isAvailable = this.selectedZones.some(zoneName => {
                    const $zoneCard = $(`.zone-card[data-zone-name="${zoneName}"]`);
                    const hasMethod = $zoneCard.find(`.method-available.${method}`).length > 0;
                    console.log(`Zone ${zoneName} supports ${method}:`, hasMethod);
                    return hasMethod;
                });
                
                console.log(`Method ${method} is available:`, isAvailable);
                $card.toggle(isAvailable);
            });
            
            // If no methods are visible after filtering, show all methods as fallback
            if ($('.method-card:visible').length === 0) {
                console.log('No methods visible after filtering, showing all methods as fallback');
                $('.method-card').show();
            }
        }

        updateSetupFee() {
            // Calculate and display setup fee
            this.calculateSetupFee().then(fee => {
                // Ensure fee is a number
                const numericFee = typeof fee === 'number' ? fee : parseFloat(fee) || 8.95;
                $('#setup-fee-amount').text(numericFee.toFixed(2));
            }).catch(error => {
                console.error('Error calculating setup fee:', error);
                $('#setup-fee-amount').text('8.95');
            });
        }

        calculateSetupFee() {
            return new Promise((resolve) => {
                $.ajax({
                    url: wcCustomizerWizard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wc_customizer_calculate_pricing',
                        customization_data: {
                            method: this.selectedMethod,
                        content_type: this.contentType,
                        zones: this.selectedZones || []
                        },
                        quantity: 1,
                        nonce: wcCustomizerWizard.pricingNonce
                    },
                    success: (response) => {
                        if (response.success && response.data && response.data.pricing) {
                            const setupCost = response.data.pricing.breakdown?.find(item => item.type === 'setup')?.cost;
                            const numericCost = typeof setupCost === 'number' ? setupCost : parseFloat(setupCost) || 8.95;
                            resolve(numericCost);
                        } else {
                            resolve(8.95); // Default
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Error calculating setup fee:', error);
                        resolve(8.95); // Default
                    }
                });
            });
        }

        initFileUpload() {
            // Drag and drop
            const $uploadArea = $('#upload-area');
            
            $uploadArea.on('dragover', (e) => {
                e.preventDefault();
                $uploadArea.addClass('dragover');
            });
            
            $uploadArea.on('dragleave', (e) => {
                e.preventDefault();
                $uploadArea.removeClass('dragover');
            });
            
            $uploadArea.on('drop', (e) => {
                e.preventDefault();
                $uploadArea.removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    this.uploadFile(files[0]);
                }
            });
        }

        triggerFileUpload() {
            $('#file-input').click();
        }

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) {
                this.uploadFile(file);
            }
        }

        uploadFile(file) {
            console.log('=== UPLOAD FILE STARTED ===');
            console.log('File:', file);
            console.log('File size:', file.size);
            console.log('File type:', file.type);
            console.log('File name:', file.name);
            console.log('Settings:', wcCustomizerWizard.settings);
            
            // Validate file
            if (!this.validateFile(file)) {
                console.log('File validation failed');
                return;
            }
            
            console.log('File validation passed, starting upload...');
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('session_id', this.sessionId);
            formData.append('action', 'wc_customizer_upload_file');
            formData.append('nonce', wcCustomizerWizard.uploadNonce);
            
            console.log('FormData prepared, session ID:', this.sessionId);
            console.log('Upload nonce:', wcCustomizerWizard.uploadNonce);
            
            // Show progress
            $('#upload-progress').show();
            $('.progress-fill').css('width', '0%');
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            $('.progress-fill').css('width', percentComplete + '%');
                            console.log('Upload progress:', percentComplete + '%');
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    console.log('Upload success response:', response);
                    $('#upload-progress').hide();
                    
                    if (response.success) {
                        console.log('Upload successful, file data:', response.data);
                        this.uploadedFile = response.data;
                        this.showUploadedFile(response.data.original_name);
                        // Step navigation removed for single-page approach
                        this.autoSave();
                    } else {
                        console.log('Upload failed:', response.data);
                        alert(response.data.message || wcCustomizerWizard.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Upload error:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                    $('#upload-progress').hide();
                    alert(wcCustomizerWizard.strings.error);
                }
            });
        }

        validateFile(file) {
            // Check file size
            if (file.size > wcCustomizerWizard.settings.maxFileSize) {
                alert(`File too large. Maximum size is ${wcCustomizerWizard.settings.maxFileSize / 1048576}MB`);
                return false;
            }
            
            // Check file type
            const extension = file.name.split('.').pop().toLowerCase();
            if (!wcCustomizerWizard.settings.allowedTypes.includes(extension)) {
                alert(`Invalid file type. Allowed types: ${wcCustomizerWizard.settings.allowedTypes.join(', ')}`);
                return false;
            }
            
            return true;
        }

        showUploadedFile(filename) {
            console.log('=== SHOW UPLOADED FILE ===');
            console.log('Filename:', filename);
            console.log('Uploaded file data:', this.uploadedFile);
            console.log('Uploaded file elements found:', $('#uploaded-file').length);
            console.log('File name element found:', $('#uploaded-file .file-name').length);
            
            $('#uploaded-file .file-name').text(filename);
            
            // Show image preview if it's an image file
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            console.log('File extension:', extension);
            console.log('Is image file:', imageExtensions.includes(extension));
            console.log('Has uploaded file URL:', this.uploadedFile && this.uploadedFile.url);
            
            if (imageExtensions.includes(extension) && this.uploadedFile && this.uploadedFile.url) {
                console.log('Showing image preview with URL:', this.uploadedFile.url);
                $('#uploaded-image-preview').attr('src', this.uploadedFile.url).show();
            } else {
                console.log('Hiding image preview');
                $('#uploaded-image-preview').hide();
            }
            
            console.log('Showing uploaded file section, hiding upload area');
            $('#uploaded-file').show();
            $('#upload-area').hide();
        }

        removeFile() {
            this.uploadedFile = null;
            $('#uploaded-file').hide();
            $('#uploaded-image-preview').hide().attr('src', '');
            $('#upload-area').show();
            $('#file-input').val('');
            // Step navigation removed for single-page approach
            this.autoSave();
        }

        handleContentTypeClick(e) {
            console.log('=== CONTENT TYPE CARD CLICKED ===');
            const $card = $(e.currentTarget);
            const contentType = $card.data('content-type');
            
            console.log('Content type card clicked:', contentType);
            console.log('Card element:', $card[0]);
            console.log('Card classes:', $card.attr('class'));
            console.log('Card data attributes:', $card.data());
            
            // Update visual selection
            $('.content-type-card').removeClass('selected');
            $('.content-type-card .checkmark').hide();
            
            $card.addClass('selected');
            $card.find('.checkmark').show();
            
            // Update hidden radio input
            $('input[name="content_type"]').prop('checked', false);
            $(`input[name="content_type"][value="${contentType}"]`).prop('checked', true);
            
            this.contentType = contentType;
            
            console.log('Content type set to:', this.contentType);
            console.log('Selected card count:', $('.content-type-card.selected').length);
            
            // Show/hide appropriate sections with smooth transition
            if (contentType === 'logo') {
                console.log('Showing logo section, hiding text section');
                $('#text-input-section').hide();
                $('#logo-upload-section').show().css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                }).animate({
                    'opacity': '1'
                }, 300).css('transform', 'translateY(0)');
                
                // Reset text input
                this.customText = '';
                $('#custom-text-input').val('');
                this.updateTextPreview();
            } else if (contentType === 'text') {
                console.log('Showing text section, hiding logo section');
                $('#logo-upload-section').hide();
                $('#text-input-section').show().css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                }).animate({
                    'opacity': '1'
                }, 300).css('transform', 'translateY(0)');
                
                // Reset file upload
                this.uploadedFile = null;
                $('#uploaded-file').hide();
                $('#upload-area').show();
                $('#file-input').val('');
            }
            
            // Progressive disclosure: Show step 4 (final step) when content type is selected
            console.log('Showing final step - content type selected');
            let $finalStep = $('#step-final');
            if ($finalStep.length === 0) {
                $finalStep = $('.wizard-step[data-step="final"]');
                console.log('Final step found by data-step="final":', $finalStep.length);
            }
            if ($finalStep.length === 0) {
                $finalStep = $('.wizard-step[data-step="4"]');
                console.log('Final step found by data-step="4":', $finalStep.length);
            }
            if ($finalStep.length === 0) {
                $finalStep = $('.wizard-step').last();
                console.log('Final step found by last():', $finalStep.length);
            }
            
            if ($finalStep.length > 0) {
                $finalStep.addClass('active').show().css({
                    'display': 'flex !important',
                    'opacity': '1 !important',
                    'transform': 'translateY(0) !important',
                    'pointer-events': 'auto !important',
                    'visibility': 'visible !important'
                });
                console.log('Final step shown successfully');
            } else {
                console.error('Final step element not found!');
            }
            
            this.autoSave();
        }

        handleContentTypeChange(e) {
            const contentType = e.target.value;
            this.contentType = contentType;
            
            console.log('Content type changed via radio button:', contentType);
            
            // Update visual selection for radio button approach
            $('label[data-content-type]').removeClass('selected');
            $('label[data-content-type] .checkmark').hide();
            
            const $selectedOption = $(e.target).closest('label[data-content-type]');
            if ($selectedOption.length > 0) {
                $selectedOption.addClass('selected');
                $selectedOption.find('.checkmark').show();
            }
            
            // Debug: Check elements before show/hide
            console.log('Before show/hide:');
            console.log('Logo section found:', $('#logo-upload-section').length);
            console.log('Text section found:', $('#text-input-section').length);
            console.log('Logo section display before:', $('#logo-upload-section').css('display'));
            console.log('Text section display before:', $('#text-input-section').css('display'));
            
            // Debug: Search for any elements with these IDs or similar
            console.log('All elements with logo-upload in ID:', $('[id*="logo-upload"]').length);
            console.log('All elements with text-input in ID:', $('[id*="text-input"]').length);
            console.log('All elements with upload in class:', $('[class*="upload"]').length);
            console.log('All elements with text in class:', $('[class*="text"]').length);
            
            // Debug: Check if sections are in step 3
            console.log('Step 3 content:', $('#step-3').html());
            
            // Debug: Check all wizard steps and their content
            console.log('All wizard steps:', $('.wizard-step').length);
            $('.wizard-step').each(function(index) {
                const stepId = $(this).attr('id');
                const stepData = $(this).attr('data-step');
                const stepHtml = $(this).html();
                console.log(`Step ${index + 1}:`, stepId, stepData, stepHtml.substring(0, 500) + '...');
                
                // Check if this is step 3 and look for upload container
                if (stepData === '3' || stepId === 'step-3') {
                    console.log('Step 3 upload container found:', $(this).find('.upload-container').length);
                    console.log('Step 3 choose file button found:', $(this).find('.choose-file-btn').length);
                    console.log('Step 3 checkmark icon found:', $(this).find('.checkmark-icon').length);
                }
            });
            
            // Debug: Look for any elements that might be our sections
            console.log('Elements with upload in class:', $('[class*="upload"]').map(function() { return $(this).attr('id') || $(this).attr('class'); }).get());
            console.log('Elements with text in class:', $('[class*="text"]').map(function() { return $(this).attr('id') || $(this).attr('class'); }).get());
            
            // Show/hide appropriate sections - target actual elements that exist
            if (contentType === 'logo') {
                console.log('Showing logo section, hiding text section');
                
                // Target the actual elements that exist based on console logs
                $('.content-text-area').hide();
                $('.content-upload-area').show();
                $('.upload-zone').show();
                
                // Also try the original IDs as fallback
                $('#text-input-section').hide();
                $('#logo-upload-section').show();
                $('[class*="text-input"]').hide();
                $('[class*="logo-upload"]').show();
                $('[id*="text-input"]').hide();
                $('[id*="logo-upload"]').show();
                
                console.log('Logo section display after:', $('.content-upload-area').css('display'));
                console.log('Text section display after:', $('.content-text-area').css('display'));
            } else if (contentType === 'text') {
                console.log('Showing text section, hiding logo section');
                
                // Target the actual elements that exist based on console logs
                $('.content-upload-area').hide();
                $('.upload-zone').hide();
                $('.content-text-area').show();
                
                // Also try the original IDs as fallback
                $('#logo-upload-section').hide();
                $('#text-input-section').show();
                $('[class*="logo-upload"]').hide();
                $('[class*="text-input"]').show();
                $('[id*="logo-upload"]').hide();
                $('[id*="text-input"]').show();
                
                // Initialize text preview
                setTimeout(() => {
                    this.updateTextPreview();
                }, 100);
                
                console.log('Logo section display after:', $('.content-upload-area').css('display'));
                console.log('Text section display after:', $('.content-text-area').css('display'));
            }
            
            this.autoSave();
        }

        handleTextInput(e) {
            this.customText = e.target.value;
            console.log('Text input changed:', this.customText);
            
            // Update character count
            const charCount = this.customText.length;
            $('#text-char-count').text(charCount);
            
            // Update preview
            this.updateTextPreview();
            
            // Update continue button state
            // Step navigation removed for single-page approach
            this.autoSave();
        }

        handleTextLineInput(e) {
            console.log('=== TEXT LINE INPUT CHANGED ===');
            console.log('Target ID:', e.target.id);
            console.log('Target value:', e.target.value);
            console.log('Event type:', e.type);
            this.updateTextPreview();
            this.autoSave();
        }

        handlePreviewClick(e) {
            e.preventDefault();
            console.log('Preview button clicked');
            this.updateTextPreview(true);
        }

        updateTextPreview(forceUpdate = false) {
            console.log('=== UPDATE TEXT PREVIEW ===');
            const $preview = $('.preview-text');
            const $previewArea = $('#text-preview-area');
            
            console.log('Preview element found:', $preview.length);
            console.log('Preview area found:', $previewArea.length);
            
            // Get text from new configuration fields
            const textLine1 = $('#text-line-1').val() || '';
            const textLine2 = $('#text-line-2').val() || '';
            const textLine3 = $('#text-line-3').val() || '';
            
            console.log('Text lines:', { textLine1, textLine2, textLine3 });
            
            // Combine all text lines
            const allTextLines = [textLine1, textLine2, textLine3].filter(line => line.trim());
            const combinedText = allTextLines.join(' ');
            
            console.log('Combined text:', combinedText);
            
            // Get font and color
            const selectedFont = $('#text-font').val() || 'arial';
            const selectedColor = $('input[name="text_color"]:checked').val() || 'white';
            
            console.log('Selected font:', selectedFont);
            console.log('Selected color:', selectedColor);
            
            if (combinedText.trim()) {
                console.log('Updating preview with text:', combinedText);
                
                // Update preview text
                $preview.text(combinedText).removeClass('empty');
                
                // Apply font and color styling
                $preview.css({
                    'font-family': this.getFontFamily(selectedFont),
                    'color': this.getColorValue(selectedColor),
                    'font-weight': 'bold',
                    'font-size': '18px',
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
                // Apply !important styles using attr method
                $preview.attr('style', 
                    'font-family: ' + this.getFontFamily(selectedFont) + ' !important; ' +
                    'color: ' + this.getColorValue(selectedColor) + ' !important; ' +
                    'font-weight: bold !important; ' +
                    'font-size: 18px !important; ' +
                    'display: block !important; ' +
                    'visibility: visible !important; ' +
                    'opacity: 1 !important;'
                );
                
                // Update preview area styling
                $previewArea.css({
                    'background-color': selectedColor === 'white' ? '#333' : '#fff',
                    'border': selectedColor === 'white' ? '2px solid #333' : '2px solid #ddd'
                });
                
                console.log('Text preview updated:', {
                    text: combinedText,
                    font: selectedFont,
                    color: selectedColor,
                    previewElement: $preview[0],
                    previewText: $preview.text()
                });
            } else {
                console.log('Resetting to placeholder text');
                
                // Reset to placeholder
                $preview.text('Your text will appear here...').addClass('empty');
                $preview.css({
                    'font-family': 'inherit',
                    'color': '#6b7280',
                    'font-weight': 'normal',
                    'font-size': 'inherit'
                });
                
                $previewArea.css({
                    'background-color': '#fff',
                    'border': '1px solid #d1d5db'
                });
            }
        }

        getFontFamily(fontValue) {
            const fontMap = {
                'arial': 'Arial, sans-serif',
                'helvetica': 'Helvetica, Arial, sans-serif',
                'times': 'Times New Roman, serif',
                'courier': 'Courier New, monospace',
                'verdana': 'Verdana, sans-serif',
                'georgia': 'Georgia, serif'
            };
            return fontMap[fontValue] || 'Arial, sans-serif';
        }

        getColorValue(colorValue) {
            const colorMap = {
                'white': '#ffffff',
                'black': '#000000',
                'red': '#dc2626',
                'blue': '#2563eb'
            };
            return colorMap[colorValue] || '#000000';
        }

        // Step navigation methods removed for single-page approach

        generateSummary() {
            const summary = {
                zones: this.selectedZones.map(z => z.name).join(', '),
                method: this.capitalizeFirst(this.selectedMethod),
                content_type: this.contentType,
                file: this.contentType === 'logo' ? 
                    (this.uploadedFile ? this.uploadedFile.original_name : 'No file') : 
                    (this.customText || 'No text'),
                text: this.contentType === 'text' ? this.customText : ''
            };
            
            // Check if uploaded file is an image
            const isImage = this.uploadedFile && this.uploadedFile.url;
            const extension = this.uploadedFile ? this.uploadedFile.original_name.split('.').pop().toLowerCase() : '';
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const showImagePreview = isImage && imageExtensions.includes(extension);
            
            const $summary = $('#customization-summary');
            $summary.html(`
                <div class="summary-item">
                    <strong>Positions:</strong> ${summary.zones}
                </div>
                <div class="summary-item">
                    <strong>Method:</strong> ${summary.method}
                </div>
                <div class="summary-item file-summary">
                    <strong>${summary.content_type === 'logo' ? 'Logo File:' : 'Text Content:'}</strong>
                    ${summary.content_type === 'logo' ? (
                        showImagePreview ? `
                            <div class="file-preview-summary">
                                <div class="file-image-preview">
                                    <img src="${this.uploadedFile.url}" alt="Uploaded logo" class="summary-logo-preview">
                                    <div class="file-overlay">
                                        <span class="file-name-summary">${summary.file}</span>
                                    </div>
                                </div>
                            </div>
                        ` : `
                            <span class="file-name-text">${summary.file}</span>
                        `
                    ) : `
                        <div class="text-content-summary">
                            <div class="text-preview-summary">"${summary.text}"</div>
                        </div>
                    `}
                </div>
            `);
        }

        calculatePricing() {
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_calculate_pricing',
                    customization_data: {
                        method: this.selectedMethod,
                        content_type: 'logo',
                        zones: this.selectedZones.map(z => z.id),
                        file_path: this.uploadedFile ? this.uploadedFile.filepath : null
                    },
                    quantity: 1,
                    nonce: wcCustomizerWizard.pricingNonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderPricingBreakdown(response.data.pricing);
                    }
                },
                error: () => {
                    console.error('Failed to calculate pricing');
                }
            });
        }

        renderPricingBreakdown(pricing) {
            const $breakdown = $('#pricing-breakdown');
            let html = '<h4>Pricing Breakdown</h4><div class="pricing-items">';
            
            pricing.breakdown.forEach(item => {
                html += `
                    <div class="pricing-item">
                        <span class="pricing-label">${item.label}</span>
                        <span class="pricing-cost">£${item.cost.toFixed(2)}</span>
                    </div>
                `;
            });
            
            html += `
                </div>
                <div class="pricing-total">
                    <strong>Total: £${pricing.total.toFixed(2)}</strong>
                </div>
            `;
            
            $breakdown.html(html);
        }

        collectAllFormData() {
            console.log('=== COLLECTING ALL FORM DATA ===');
            
            // Collect data for all configured positions
            Object.keys(this.positionConfigs).forEach(zoneId => {
                const config = this.positionConfigs[zoneId];
                console.log('Collecting data for zone', zoneId, ':', config);
                
                if (config.content_type === 'text') {
                    // Collect text form data
                    const textLine1 = $(`#text-line-1-${zoneId}`).val() || '';
                    const textLine2 = $(`#text-line-2-${zoneId}`).val() || '';
                    const textLine3 = $(`#text-line-3-${zoneId}`).val() || '';
                    const textFont = $(`#text-font-${zoneId}`).val() || 'arial';
                    const textColor = $(`input[name="text_color_${zoneId}"]:checked`).val() || 'white';
                    const textNotes = $(`#text-notes-${zoneId}`).val() || '';
                    
                    // Update position config
                    config.text_line_1 = textLine1;
                    config.text_line_2 = textLine2;
                    config.text_line_3 = textLine3;
                    config.text_font = textFont;
                    config.text_color = textColor;
                    config.text_notes = textNotes;
                    
                    console.log('Updated text config for zone', zoneId, ':', config);
                    
                } else if (config.content_type === 'logo') {
                    // Collect logo form data
                    const logoAlternative = $(`#logo-upload-section-${zoneId} input[name="logo_alternative_${zoneId}"]:checked`).val() || '';
                    const logoNotes = $(`#logo-notes-${zoneId}`).val() || '';
                    
                    // Update position config
                    config.logo_alternative = logoAlternative;
                    config.logo_notes = logoNotes;
                    
                    console.log('Updated logo config for zone', zoneId, ':', config);
                    console.log('Logo alternative collected:', logoAlternative);
                }
            });
            
            console.log('Final position configs:', this.positionConfigs);
        }

        addToCart() {
            const $btn = $('#add-to-cart-btn');
            const originalText = $btn.text();
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.html(`
                <div class="loading-spinner" style="width: 1rem; height: 1rem; margin-right: 0.5rem;"></div>
                Adding to Cart...
            `);
            
            // Collect all form data before validation
            this.collectAllFormData();
            
        // Validate all selected positions before proceeding
        const validationResult = this.validateAllPositions();
        if (!validationResult.isValid) {
            // Reset button
            $btn.prop('disabled', false);
            $btn.text(originalText);
            
            // Show validation errors
            this.showValidationErrors(validationResult.errors);
            return;
        }
        
        // Collect all position configurations
        const positionCustomizations = [];
        
        Object.values(this.positionConfigs).forEach(config => {
            if (config.method && config.content_type) {
                const positionData = {
                    zone_id: config.zone_id,
                    zone_name: config.zone_name,
                    method: config.method,
                    content_type: config.content_type,
                    file_path: config.file_path || null,
                    text_content: config.content_type === 'text' ? 
                        [config.text_line_1, config.text_line_2, config.text_line_3].filter(line => line.trim()).join(' ') : null,
                    text_line_1: config.text_line_1 || '',
                    text_line_2: config.text_line_2 || '',
                    text_line_3: config.text_line_3 || '',
                    text_font: config.text_font || 'arial',
                    text_color: config.text_color || 'white',
                    text_notes: config.text_notes || '',
                    logo_alternative: config.logo_alternative || '',
                    logo_notes: config.logo_notes || '',
                    application_fee: 7.99 // Will be calculated properly
                };
                
                positionCustomizations.push(positionData);
            }
        });
        
        console.log('=== ADD TO CART DEBUG ===');
        console.log('Position configurations:', this.positionConfigs);
        console.log('Position customizations:', positionCustomizations);
        
        if (positionCustomizations.length === 0) {
            // Reset button
            $btn.prop('disabled', false);
            $btn.text(originalText);
            alert('Please configure at least one position before adding to cart.');
            return;
        }
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_add_to_cart',
                    cart_item_key: this.cartItemKey,
                position_customizations: positionCustomizations,
                    nonce: wcCustomizerWizard.cartNonce
                },
                success: (response) => {
                    if (response.success) {
                    if (this.isCustomizationPage()) {
                        // On customization page, redirect to return URL
                        $btn.html('✅ Saving...');
                        setTimeout(() => {
                            window.location.href = this.returnUrl || wcCustomizerWizard.cartUrl;
                        }, 1000);
                    } else {
                        // Modal behavior (for backward compatibility)
                        $btn.html('✅ Added Successfully!');
                        setTimeout(() => {
                        this.closeWizard();
                        location.reload(); // Refresh cart
                        }, 1000);
                    }
                    } else {
                        // Reset button
                        $btn.prop('disabled', false);
                        $btn.text(originalText);
                        this.showError($('#customization-summary'), response.data.message || wcCustomizerWizard.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Add to cart error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    // Reset button
                    $btn.prop('disabled', false);
                    $btn.text(originalText);
                    this.showError($('#customization-summary'), 'Failed to add customization to cart. Please try again.');
                }
            });
        }

    validateAllPositions() {
        console.log('=== VALIDATING ALL POSITIONS ===');
        
        const errors = [];
        const selectedPositions = this.getSelectedPositions();
        
        console.log('Selected positions:', selectedPositions);
        console.log('Position configs:', this.positionConfigs);
        
        if (selectedPositions.length === 0) {
            errors.push({
                type: 'no_positions',
                message: 'Please select at least one position to customize.',
                position: null
            });
            return { isValid: false, errors };
        }
        
        selectedPositions.forEach(zoneId => {
            const config = this.positionConfigs[zoneId];
            const zoneName = config?.zone_name || `Position ${zoneId}`;
            
            console.log(`Validating position ${zoneId} (${zoneName}):`, config);
            
            // Check if method is selected
            if (!config.method) {
                errors.push({
                    type: 'missing_method',
                    message: `${zoneName}: Please select an application method (embroidery or print).`,
                    position: zoneId,
                    zoneName: zoneName
                });
                return;
            }
            
            // Check if content type is selected
            if (!config.content_type) {
                errors.push({
                    type: 'missing_content_type',
                    message: `${zoneName}: Please select a content type (logo or text).`,
                    position: zoneId,
                    zoneName: zoneName
                });
                return;
            }
            
            // Validate content based on type
            if (config.content_type === 'logo') {
                // For logo: must have file uploaded OR alternative option selected
                if (!config.file_path && !config.logo_alternative) {
                    errors.push({
                        type: 'missing_logo_content',
                        message: `${zoneName}: Please upload a logo file or select an alternative option.`,
                        position: zoneId,
                        zoneName: zoneName
                    });
                }
            } else if (config.content_type === 'text') {
                // For text: must have at least one text line filled
                const hasText = config.text_line_1?.trim() || 
                               config.text_line_2?.trim() || 
                               config.text_line_3?.trim();
                
                if (!hasText) {
                    errors.push({
                        type: 'missing_text_content',
                        message: `${zoneName}: Please enter at least one line of text.`,
                        position: zoneId,
                        zoneName: zoneName
                    });
                }
            }
        });
        
        console.log('Validation result:', { isValid: errors.length === 0, errors });
        return { isValid: errors.length === 0, errors };
    }

    showValidationErrors(errors) {
        console.log('=== SHOWING VALIDATION ERRORS ===', errors);
        
        // Create error message
        let errorMessage = 'Please complete the following before saving:\n\n';
        
        errors.forEach((error, index) => {
            errorMessage += `${index + 1}. ${error.message}\n`;
        });
        
        // Show alert with validation errors
        alert(errorMessage);
        
        // Highlight problematic positions in the UI
        errors.forEach(error => {
            if (error.position) {
                // Highlight the tab for this position
                const $tab = $(`.config-tab-btn[data-zone-id="${error.position}"]`);
                if ($tab.length) {
                    $tab.addClass('validation-error');
                    
                    // Remove highlight after 3 seconds
                    setTimeout(() => {
                        $tab.removeClass('validation-error');
                    }, 3000);
                }
                }
            });
        }

        autoSave() {
            if (!this.sessionId) return;
            
            const stepData = {
                step: this.currentStep,
                zones: this.selectedZones,
                method: this.selectedMethod,
                file: this.uploadedFile
            };
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_save_session',
                    session_id: this.sessionId,
                    cart_item_key: this.cartItemKey,
                    step_data: stepData,
                    nonce: wcCustomizerWizard.nonce
                }
            });
        }

        loadSessionIfExists() {
            const sessionId = localStorage.getItem('wc_customizer_session');
            if (sessionId) {
                this.sessionId = sessionId;
            }
        }

        generateSessionId() {
            const id = Math.random().toString(36).substr(2, 16);
            localStorage.setItem('wc_customizer_session', id);
            return id;
        }

        capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        handleKeyboard(e) {
            if (!$('#wc-customizer-wizard-modal').is(':visible')) return;
            
            if (e.key === 'Escape') {
                this.closeWizard();
            }
        }

        loadExistingCustomization() {
            console.log('Loading existing customization for product:', this.productId, 'cart item:', this.cartItemKey);
            
            // Load existing customization data first
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_edit_customization',
                    cart_item_key: this.cartItemKey,
                    nonce: wcCustomizerWizard.cartNonce
                },
                success: (response) => {
                    console.log('Edit customization response:', response);
                    if (response.success) {
                        this.populateWizardWithData(response.data.customization_data);
                        
                        // Load product info, zones and methods for the product
                        this.loadProductInfo();
                        this.loadZones();
                        // loadMethods() removed - now handled per position in renderPositionTabs()
                        
                        // Show modal directly instead of calling openWizard with fake event
                        $('#wc-customizer-wizard-modal').fadeIn(300);
                        $('body').addClass('wc-customizer-modal-open');
                        
                        // Fallback: Try to highlight selections after a delay
                        setTimeout(() => {
                            this.highlightExistingSelections();
                        }, 500);
                    } else {
                        // Check if this is a "no data found" error (normal for new customizations)
                        if (response.data.message && response.data.message.includes('No customization data found')) {
                            console.log('No existing customization data found - this is normal for new customizations');
                            // Don't show error for new customizations, just continue normally
                            return;
                        }
                        
                        console.error('Error loading customization data:', response.data.message);
                        const errorMessage = typeof response.data.message === 'string' ? response.data.message : 'Unknown error occurred';
                        this.showError('Error loading customization data: ' + errorMessage);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error loading customization data:', error);
                    this.showError('Error loading customization data. Please try again.');
                }
            });
        }

        populateWizardWithData(data) {
            // Populate wizard with existing data
            if (data.zones) {
                this.selectedZones = data.zones;
            }
            if (data.method) {
                this.selectedMethod = data.method;
            }
            if (data.file_path) {
                // Convert file path to URL for image preview
                // WordPress uploads directory structure: /wp-content/uploads/customization-files/
                const file_url = data.file_path.replace(/^.*\/wp-content\/uploads/, '/wp-content/uploads');
                this.uploadedFile = { 
                    filepath: data.file_path,
                    url: file_url,
                    original_name: data.original_name || 'Uploaded file'
                };
                
                // Show the uploaded file in the UI
                this.showUploadedFile(this.uploadedFile.original_name);
            }
            
            // Note: UI will be updated after zones are loaded in loadZones success callback
        }
        
        updateUIAfterDataLoad() {
            console.log('=== updateUIAfterDataLoad called ===');
            console.log('Selected zones:', this.selectedZones);
            console.log('Selected method:', this.selectedMethod);
            
            // Update zones UI
            if (this.selectedZones && this.selectedZones.length > 0) {
                console.log('Attempting to highlight zones. Selected zones:', this.selectedZones);
                console.log('Available zone cards:', $('.zone-card').length);
                console.log('Zone card data attributes:', $('.zone-card').map(function() { return $(this).attr('data-zone-name'); }).get());
                
                this.selectedZones.forEach(zoneName => {
                    // Try exact match first
                    let $zoneCard = $(`.zone-card[data-zone-name="${zoneName}"]`);
                    console.log(`Checking zone: "${zoneName}", Found card:`, $zoneCard.length > 0);
                    
                    if ($zoneCard.length === 0) {
                        // Try case-insensitive match
                        $zoneCard = $(`.zone-card[data-zone-name*="${zoneName}" i]`);
                        console.log(`Case-insensitive match for "${zoneName}":`, $zoneCard.length > 0);
                    }
                    
                    if ($zoneCard.length === 0) {
                        // Try partial match
                        $zoneCard = $(`.zone-card[data-zone-name*="${zoneName}"]`);
                        console.log(`Partial match for "${zoneName}":`, $zoneCard.length > 0);
                    }
                    
                    if ($zoneCard.length === 0) {
                        // Try text content match
                        $zoneCard = $(`.zone-card:contains("${zoneName}")`);
                        console.log(`Text content match for "${zoneName}":`, $zoneCard.length > 0);
                    }
                    
                    if ($zoneCard.length > 0) {
                        $zoneCard.addClass('selected');
                        console.log(`✅ Added 'selected' class to zone: ${zoneName}`);
                    } else {
                        console.log(`❌ Zone card not found for: "${zoneName}"`);
                    }
                });
                this.updateSelectionCount();
            }
            
            // Update method UI
            if (this.selectedMethod) {
                console.log('Attempting to highlight method:', this.selectedMethod);
                console.log('Available method cards:', $('.method-card').length);
                console.log('Method card data attributes:', $('.method-card').map(function() { return $(this).attr('data-method'); }).get());
                
                const $methodCard = $(`.method-card[data-method="${this.selectedMethod}"]`);
                console.log(`Found method card for "${this.selectedMethod}":`, $methodCard.length > 0);
                
                if ($methodCard.length > 0) {
                    $methodCard.addClass('selected');
                    $('.selected-method').text(this.selectedMethod);
                    console.log(`✅ Added 'selected' class to method: ${this.selectedMethod}`);
                } else {
                    console.log(`❌ Method card not found for: "${this.selectedMethod}"`);
                    // Try alternative method name formats
                    const altMethod = this.selectedMethod.toLowerCase();
                    const $altCard = $(`.method-card[data-method="${altMethod}"]`);
                    if ($altCard.length > 0) {
                        $altCard.addClass('selected');
                        $('.selected-method').text(this.selectedMethod);
                        console.log(`✅ Added 'selected' class to method (alt): ${altMethod}`);
                    }
                }
            }
            
            console.log('=== updateUIAfterDataLoad completed ===');
        }
        
        updateSelectionCount() {
            const count = this.selectedZones ? this.selectedZones.length : 0;
            $('#position-count').text(count);
            $('.selected-count').text(`${count} ${wcCustomizerWizard.strings.selected}`);
        }
        
        updateMethodUI() {
            console.log('=== updateMethodUI called ===');
            console.log('Selected method:', this.selectedMethod);
            
            if (this.selectedMethod) {
                console.log('Attempting to highlight method:', this.selectedMethod);
                console.log('Available method cards:', $('.method-card').length);
                console.log('Method card data attributes:', $('.method-card').map(function() { return $(this).attr('data-method'); }).get());
                
                const $methodCard = $(`.method-card[data-method="${this.selectedMethod}"]`);
                console.log(`Found method card for "${this.selectedMethod}":`, $methodCard.length > 0);
                
                if ($methodCard.length > 0) {
                    $methodCard.addClass('selected');
                    $('.selected-method').text(this.selectedMethod);
                    console.log(`✅ Added 'selected' class to method: ${this.selectedMethod}`);
                } else {
                    console.log(`❌ Method card not found for: "${this.selectedMethod}"`);
                    // Try alternative method name formats
                    const altMethod = this.selectedMethod.toLowerCase();
                    const $altCard = $(`.method-card[data-method="${altMethod}"]`);
                    if ($altCard.length > 0) {
                        $altCard.addClass('selected');
                        $('.selected-method').text(this.selectedMethod);
                        console.log(`✅ Added 'selected' class to method (alt): ${altMethod}`);
                    }
                }
            }
            
            console.log('=== updateMethodUI completed ===');
        }
        
        highlightExistingSelections() {
            console.log('=== highlightExistingSelections called ===');
            console.log('Selected zones:', this.selectedZones);
            console.log('Selected method:', this.selectedMethod);
            
            // Highlight zones
            if (this.selectedZones && this.selectedZones.length > 0) {
                this.selectedZones.forEach(zoneName => {
                    const $zoneCard = $(`.zone-card[data-zone-name="${zoneName}"]`);
                    if ($zoneCard.length > 0) {
                        $zoneCard.addClass('selected');
                        console.log(`✅ Fallback: Added 'selected' class to zone: ${zoneName}`);
                    } else {
                        // Try text content match as fallback
                        const $altCard = $(`.zone-card:contains("${zoneName}")`);
                        if ($altCard.length > 0) {
                            $altCard.addClass('selected');
                            console.log(`✅ Fallback: Added 'selected' class to zone (text): ${zoneName}`);
                        }
                    }
                });
                this.updateSelectionCount();
            }
            
            // Highlight method
            if (this.selectedMethod) {
                const $methodCard = $(`.method-card[data-method="${this.selectedMethod}"]`);
                if ($methodCard.length > 0) {
                    $methodCard.addClass('selected');
                    $('.selected-method').text(this.selectedMethod);
                    console.log(`✅ Fallback: Added 'selected' class to method: ${this.selectedMethod}`);
                } else {
                    // Try alternative method name formats
                    const altMethod = this.selectedMethod.toLowerCase();
                    const $altCard = $(`.method-card[data-method="${altMethod}"]`);
                    if ($altCard.length > 0) {
                        $altCard.addClass('selected');
                        $('.selected-method').text(this.selectedMethod);
                        console.log(`✅ Fallback: Added 'selected' class to method (alt): ${altMethod}`);
                    }
                }
            }
            
            console.log('=== highlightExistingSelections completed ===');
        }

        // Loading and Error Handling Methods
        showLoading($container) {
            $container.addClass('loading');
            $container.html(`
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">${wcCustomizerWizard.strings.loading || 'Loading...'}</p>
                </div>
            `);
        }

        hideLoading($container) {
            $container.removeClass('loading');
        }

        showError($container, message) {
            $container.html(`
                <div class="error-state">
                    <div class="error-icon">⚠️</div>
                    <h4 class="error-title">Error</h4>
                    <p class="error-message">${message}</p>
                    <button type="button" class="retry-btn" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            `);
        }

        showSuccess($container, message) {
            $container.html(`
                <div class="success-state">
                    <div class="success-icon">✅</div>
                    <p class="success-message">${message}</p>
                </div>
            `);
        }

        showError(message) {
            // Show error in the wizard content area
            const $stepContent = $('.step-content');
            $stepContent.html(`
                <div class="error-message" style="text-align: center; padding: 40px; color: #d63638;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: #d63638; margin-bottom: 10px;">Error</h3>
                    <p style="color: #666; margin-bottom: 20px;">${message}</p>
                    <button type="button" class="button button-primary" onclick="location.reload()">Try Again</button>
                </div>
            `);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new CustomizationWizard();
    });

})(jQuery);
