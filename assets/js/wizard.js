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
            
            this.init();
        }

        init() {
            if (this.isCustomizationPage()) {
                this.initializeFromURL();
                this.bindEvents();
                this.initFileUpload();
                this.loadZones();
                this.loadMethods();
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
                        this.renderZones(response.data.zones);
                        // Update UI after zones are rendered
                        this.updateUIAfterDataLoad();
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
                
                // Apply font and color styling with important flags
                $preview.css({
                    'font-family': this.getFontFamily(selectedFont) + ' !important',
                    'color': this.getColorValue(selectedColor) + ' !important',
                    'font-weight': 'bold !important',
                    'font-size': '18px !important',
                    'display': 'block !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important'
                });
                
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

        addToCart() {
            const $btn = $('#add-to-cart-btn');
            const originalText = $btn.text();
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.html(`
                <div class="loading-spinner" style="width: 1rem; height: 1rem; margin-right: 0.5rem;"></div>
                Adding to Cart...
            `);
            
            // Collect all form data
            const customizationData = {
                zones: this.selectedZones.map(z => z.name),
                method: this.selectedMethod,
                content_type: this.contentType,
                file_path: this.contentType === 'logo' ? (this.uploadedFile ? this.uploadedFile.filepath : null) : null,
                text_content: this.contentType === 'text' ? this.customText : null, // Legacy field
                setup_fee: this.contentType === 'logo' ? 8.95 : 2.95, // Different fees for logo vs text
                application_fee: 7.99, // Will be calculated properly
                total_cost: this.contentType === 'logo' ? 16.94 : 10.94 // Will be calculated properly
            };
            
            console.log('=== ADD TO CART DEBUG ===');
            console.log('Content type:', this.contentType);
            console.log('Uploaded file:', this.uploadedFile);
            console.log('File path being sent:', customizationData.file_path);
            console.log('Full customization data:', customizationData);
            
            // Add new text configuration fields
            if (this.contentType === 'text') {
                customizationData.text_line_1 = $('#text-line-1').val() || '';
                customizationData.text_line_2 = $('#text-line-2').val() || '';
                customizationData.text_line_3 = $('#text-line-3').val() || '';
                customizationData.text_font = $('#text-font').val() || 'arial';
                customizationData.text_color = $('input[name="text_color"]:checked').val() || 'white';
                customizationData.text_notes = $('#text-notes').val() || '';
            }
            
            // Add logo alternative options
            if (this.contentType === 'logo') {
                customizationData.logo_alternative = $('input[name="logo_alternative"]:checked').val() || '';
                customizationData.logo_notes = $('#logo-notes').val() || '';
            }
            
            $.ajax({
                url: wcCustomizerWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_customizer_add_to_cart',
                    cart_item_key: this.cartItemKey,
                    customization_data: customizationData,
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
                        this.loadMethods();
                        
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
