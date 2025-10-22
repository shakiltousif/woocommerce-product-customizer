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
            this.bindEvents();
            this.initFileUpload();
            this.loadSessionIfExists();
        }

        bindEvents() {
            // Modal controls
            $(document).on('click', '.add-customization-btn, .add-another-customization-btn', this.openWizard.bind(this));
            $(document).on('click', '.edit-customization-btn', this.editCustomization.bind(this));
            $(document).on('click', '.remove-customization-btn', this.removeCustomization.bind(this));
            $(document).on('click', '.wc-customizer-modal-close, .wc-customizer-modal-overlay', this.closeWizard.bind(this));
            
            // Step navigation
            $(document).on('click', '#step-1-continue', this.goToStep2.bind(this));
            $(document).on('click', '#step-2-continue', this.goToStep3.bind(this));
            $(document).on('click', '#step-3-continue', this.goToFinalStep.bind(this));
            $(document).on('click', '#step-2-back', () => this.goToStep(1));
            $(document).on('click', '#step-3-back', () => this.goToStep(2));
            $(document).on('click', '#final-back', () => this.goToStep(3));
            
            // Zone selection
            $(document).on('click', '.zone-card', this.toggleZone.bind(this));
            
            // Method selection
            $(document).on('click', '.method-card', this.selectMethod.bind(this));
            
            // File upload
            $(document).on('click', '#add-logo-btn', this.triggerFileUpload.bind(this));
            $(document).on('change', '#file-input', this.handleFileSelect.bind(this));
            $(document).on('click', '.remove-file-btn', this.removeFile.bind(this));
            
            // Content type selection
            $(document).on('change', 'input[name="content_type"]', this.handleContentTypeChange.bind(this));
            
            // Text input
            $(document).on('input', '#custom-text-input', this.handleTextInput.bind(this));
            
            // Final actions
            $(document).on('click', '#add-to-cart-btn', this.addToCart.bind(this));
            
            // Auto-save
            $(document).on('change input', '.wizard-step', this.autoSave.bind(this));
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboard.bind(this));
        }

        openWizard(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            this.cartItemKey = $button.data('cart-key');
            this.productId = $button.data('product-id');
            
            // If product ID not found on button, try to get it from the container
            if (!this.productId) {
                const $container = $button.closest('.customization-actions');
                this.productId = $container.data('product-id');
            }
            
            console.log('Opening wizard for cart item:', this.cartItemKey, 'product:', this.productId);
            
            // Load product info
            this.loadProductInfo();
            
            // Load zones
            this.loadZones();
            
            // Load methods
            this.loadMethods();
            
            // Show modal
            $('#wc-customizer-wizard-modal').fadeIn(300);
            $('body').addClass('wc-customizer-modal-open');
            
            // Generate session ID
            this.sessionId = this.generateSessionId();
        }

        editCustomization(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            this.cartItemKey = $button.data('cart-key');
            this.productId = $button.data('product-id');
            
            // If product ID not found on button, try to get it from the container
            if (!this.productId) {
                const $container = $button.closest('.customization-actions');
                this.productId = $container.data('product-id');
            }
            
            console.log('Edit customization - Product ID:', this.productId, 'Cart Key:', this.cartItemKey);
            
            // Load existing customization data
            this.loadExistingCustomization();
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
            $('#wc-customizer-wizard-modal').fadeOut(300);
            $('body').removeClass('wc-customizer-modal-open');
            this.resetWizard();
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
            $('#step-1-continue').prop('disabled', true);
            $('#step-2-continue').prop('disabled', true);
            $('#step-3-continue').prop('disabled', true);
            
            // Reset content type selection
            $('input[name="content_type"][value="logo"]').prop('checked', true);
            $('#logo-upload-section').show();
            $('#text-input-section').hide();
            $('#custom-text-input').val('');
            this.updateTextPreview();
        }

        goToStep(step) {
            if (step < 1 || step > this.totalSteps) return;
            
            $('.wizard-step').hide();
            $(`#step-${step}`).show();
            this.currentStep = step;
            
            // Update step-specific UI
            if (step === 2) {
                // Load methods if not already loaded
                if ($('.method-card').length === 0) {
                    this.loadMethods();
                } else {
                this.updateMethodAvailability();
                }
            } else if (step === 3) {
                this.updateSetupFee();
            }
            
            this.autoSave();
        }

        goToStep2() {
            if (this.selectedZones.length === 0) {
                alert(wcCustomizerWizard.strings.selectZones);
                return;
            }
            this.goToStep(2);
        }

        goToStep3() {
            if (!this.selectedMethod) {
                alert(wcCustomizerWizard.strings.selectMethod);
                return;
            }
            this.goToStep(3);
        }

        goToFinalStep() {
            if (this.contentType === 'logo' && !this.uploadedFile) {
                alert(wcCustomizerWizard.strings.uploadFile);
                return;
            }
            
            if (this.contentType === 'text' && !this.customText.trim()) {
                alert('Please enter some text for your customization.');
                return;
            }
            
            this.generateSummary();
            this.calculatePricing();
            this.goToStep('final');
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
            return `${wcCustomizerWizard.pluginUrl}assets/images/zones/${imageName}`;
        }

        toggleZone(e) {
            const $card = $(e.currentTarget);
            const zoneId = parseInt($card.data('zone-id'));
            const zoneName = $card.data('zone-name');
            
            if ($card.hasClass('selected')) {
                $card.removeClass('selected');
                this.selectedZones = this.selectedZones.filter(z => z.id !== zoneId);
            } else {
                $card.addClass('selected');
                this.selectedZones.push({ id: zoneId, name: zoneName });
            }
            
            this.updateZoneCounter();
            this.autoSave();
        }

        updateZoneCounter() {
            const count = this.selectedZones.length;
            $('#position-count').text(count);
            $('.selected-count').text(`${count} ${wcCustomizerWizard.strings.selected}`);
            $('#step-1-continue').prop('disabled', count === 0);
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
            return `${wcCustomizerWizard.pluginUrl}assets/images/methods/${methodName}-sample.svg`;
        }

        selectMethod(e) {
            const $card = $(e.currentTarget);
            const method = $card.data('method');
            
            $('.method-card').removeClass('selected');
            $('.method-card .checkmark').hide();
            
            $card.addClass('selected');
            $card.find('.checkmark').show();
            
            this.selectedMethod = method;
            
            const statusText = `${this.capitalizeFirst(method)} ${wcCustomizerWizard.strings.selected.toLowerCase()}`;
            $('#method-selected-text').text(statusText);
            $('#step-2-continue').prop('disabled', false);
            
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
            // Validate file
            if (!this.validateFile(file)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('session_id', this.sessionId);
            formData.append('action', 'wc_customizer_upload_file');
            formData.append('nonce', wcCustomizerWizard.uploadNonce);
            
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
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    $('#upload-progress').hide();
                    
                    if (response.success) {
                        this.uploadedFile = response.data;
                        this.showUploadedFile(response.data.original_name);
                        $('#step-3-continue').prop('disabled', false);
                        this.autoSave();
                    } else {
                        alert(response.data.message || wcCustomizerWizard.strings.error);
                    }
                },
                error: () => {
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
            $('#uploaded-file .file-name').text(filename);
            
            // Show image preview if it's an image file
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            if (imageExtensions.includes(extension) && this.uploadedFile && this.uploadedFile.url) {
                $('#uploaded-image-preview').attr('src', this.uploadedFile.url).show();
            } else {
                $('#uploaded-image-preview').hide();
            }
            
            $('#uploaded-file').show();
            $('#upload-area').hide();
        }

        removeFile() {
            this.uploadedFile = null;
            $('#uploaded-file').hide();
            $('#uploaded-image-preview').hide().attr('src', '');
            $('#upload-area').show();
            $('#file-input').val('');
            $('#step-3-continue').prop('disabled', true);
            this.autoSave();
        }

        handleContentTypeChange(e) {
            const contentType = e.target.value;
            this.contentType = contentType;
            
            console.log('Content type changed to:', contentType);
            
            // Show/hide appropriate sections
            if (contentType === 'logo') {
                $('#logo-upload-section').show();
                $('#text-input-section').hide();
                // Reset text input
                this.customText = '';
                $('#custom-text-input').val('');
                this.updateTextPreview();
            } else if (contentType === 'text') {
                $('#logo-upload-section').hide();
                $('#text-input-section').show();
                // Reset file upload
                this.uploadedFile = null;
                $('#uploaded-file').hide();
                $('#upload-area').show();
                $('#file-input').val('');
            }
            
            // Update continue button state
            this.updateStep3ContinueButton();
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
            this.updateStep3ContinueButton();
            this.autoSave();
        }

        updateTextPreview() {
            const $preview = $('#text-preview .preview-text');
            
            if (this.customText.trim()) {
                $preview.text(this.customText).removeClass('empty');
            } else {
                $preview.text('Your text will appear here...').addClass('empty');
            }
        }

        updateStep3ContinueButton() {
            let canContinue = false;
            
            if (this.contentType === 'logo') {
                canContinue = this.uploadedFile !== null;
            } else if (this.contentType === 'text') {
                canContinue = this.customText.trim().length > 0;
            }
            
            $('#step-3-continue').prop('disabled', !canContinue);
        }

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
            
            const customizationData = {
                zones: this.selectedZones.map(z => z.name),
                method: this.selectedMethod,
                content_type: this.contentType,
                file_path: this.contentType === 'logo' ? (this.uploadedFile ? this.uploadedFile.filepath : null) : null,
                text_content: this.contentType === 'text' ? this.customText : null,
                setup_fee: this.contentType === 'logo' ? 8.95 : 2.95, // Different fees for logo vs text
                application_fee: 7.99, // Will be calculated properly
                total_cost: this.contentType === 'logo' ? 16.94 : 10.94 // Will be calculated properly
            };
            
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
                        // Show success state briefly
                        $btn.html('✅ Added Successfully!');
                        setTimeout(() => {
                        this.closeWizard();
                        location.reload(); // Refresh cart
                        }, 1000);
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
            
            // Enable/disable continue button based on selection
            const $continueBtn = $('#step-1-continue');
            if (count > 0) {
                $continueBtn.prop('disabled', false);
            } else {
                $continueBtn.prop('disabled', true);
            }
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
