jQuery(document).ready(function($) {
    let selectedVariant = null;
    let selectedExtras = [];
    let selectedDuration = null;
    let selectedCondition = null;
    let selectedProductColor = null;
    let selectedFrameColor = null;
    let currentVariantImages = [];
    let currentMainImageIndex = 0;
    let currentProductColorImage = null;
    let currentFrameColorImage = null;
    let currentCategoryId = null;
    let touchStartX = 0;
    let touchEndX = 0;
    let currentPrice = 0;
    let currentShippingCost = 0;
    let currentPriceId = '';
    let shippingPriceId = '';
    let startDate = null;
    let endDate = null;
    let selectedDays = 0;
    let calendarMonth = new Date();
    let colorNotificationTimeout = null;
    // Get category ID from container
    const container = $('.produkt-container');
    if (container.length) {
        currentCategoryId = container.data('category-id');
        const sc = parseFloat(container.data('shipping-cost'));
        if (!isNaN(sc)) {
            currentShippingCost = sc;
        }
        const spid = container.data('shipping-price-id');
        if (spid) {
            shippingPriceId = spid.toString();
        }
    }

    if (produkt_ajax.betriebsmodus === 'kauf') {
        renderCalendar(calendarMonth);
    }

    // Remove old inline color labels if they exist
    $('.produkt-color-name').remove();

    function resetAllSelections() {
        selectedVariant = null;
        selectedExtras = [];
        selectedDuration = null;
        selectedCondition = null;
        selectedProductColor = null;
        selectedFrameColor = null;
        startDate = null;
        endDate = null;
        selectedDays = 0;
        renderCalendar(calendarMonth);
        updateSelectedDays();

        $('.produkt-option.selected').removeClass('selected');
        $('#selected-product-color-name').text('');
        $('#selected-frame-color-name').text('');

        updateExtraImage(null);
        updateColorImage(null);
    }

    // Initialize mobile sticky price bar only on small screens
    initMobileStickyPrice();
    $(window).on('resize', function() {
        if (window.innerWidth <= 768) {
            if (!$('#mobile-sticky-price').length) {
                initMobileStickyPrice();
            }
        } else {
            destroyMobileStickyPrice();
        }
    });


    // Handle option selection
    $('.produkt-option').on('click', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');

        // Prevent selection of unavailable options
        const available = $(this).data('available');
        if (available === false || available === 'false' || available === 0 || available === '0') {
            resetAllSelections();
            $('#produkt-rent-button').prop('disabled', true);
            $('.produkt-mobile-button').prop('disabled', true);
            $('#produkt-button-help').hide();
            $('#produkt-unavailable-help').show();
            $('#produkt-notify').show();
            $('.produkt-notify-form').show();
            $('#produkt-notify-success').hide();
            $('#produkt-availability-wrapper').show();
            $('#produkt-availability-status').addClass('unavailable').removeClass('available');
            $('#produkt-availability-status .status-text').text('Nicht auf Lager');
            $('#produkt-delivery-box').hide();
            scrollToNotify();
            return;
        }

        // Remove selection from same type (except extras which allow multiple)
        if (type !== 'extra') {
            $(`.produkt-option[data-type="${type}"]`).removeClass('selected');
            $(this).addClass('selected');
        } else {
            $(this).toggleClass('selected');
        }

        // Track interaction
        trackInteraction(type.replace('-', '_') + '_select', {
            variant_id: type === 'variant' ? id : selectedVariant,
            extra_ids: selectedExtras.join(','),
            duration_id: type === 'duration' ? id : selectedDuration,
            condition_id: type === 'condition' ? id : selectedCondition,
            product_color_id: type === 'product-color' ? id : selectedProductColor,
            frame_color_id: type === 'frame-color' ? id : selectedFrameColor
        });

        // Update selection variables
        if (type === 'variant') {
            selectedVariant = id;

            // Reset selections when switching variants so the rent button
            // becomes inactive immediately
            selectedCondition = null;
            selectedProductColor = null;
            selectedFrameColor = null;
            selectedExtras = [];
            selectedDuration = null;

            $('.produkt-option[data-type="condition"]').removeClass('selected');
            $('.produkt-option[data-type="product-color"]').removeClass('selected');
            $('.produkt-option[data-type="frame-color"]').removeClass('selected');
            $('.produkt-option[data-type="extra"]').removeClass('selected');
            $('.produkt-option[data-type="duration"]').removeClass('selected');

            updateExtraImage(null);
            updateColorImage(null);

            updateVariantImages($(this));
            updateVariantOptions(id);
        } else if (type === 'extra') {
            const index = selectedExtras.indexOf(id);
            if (index > -1) {
                selectedExtras.splice(index, 1);
            } else {
                selectedExtras.push(id);
            }
            updateExtraImage($(this));
        } else if (type === 'duration') {
            selectedDuration = id;
        } else if (type === 'condition') {
            selectedCondition = id;
        } else if (type === 'product-color') {
            selectedProductColor = id;
            $('#selected-product-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
        } else if (type === 'frame-color') {
            selectedFrameColor = id;
            $('#selected-frame-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
        }

        // Update price and button state
        updatePriceAndButton();
    });

     // Handle rent button click -> redirect with parameters
    $('#produkt-rent-button, .produkt-mobile-button').on('click', function(e) {
        if ($(this).prop('disabled')) {
            return;
        }

        e.preventDefault();

        trackInteraction('rent_button_click', {
            variant_id: selectedVariant,
            extra_ids: selectedExtras.join(','),
            duration_id: selectedDuration,
            condition_id: selectedCondition,
            product_color_id: selectedProductColor,
            frame_color_id: selectedFrameColor
        });

        if (produkt_ajax.checkout_url) {
            const extraPriceIds = $('.produkt-option[data-type="extra"].selected')
                .map(function(){ return $(this).data('price-id'); })
                .get()
                .filter(id => id);

            const extraIds = selectedExtras.join(',');

            const params = new URLSearchParams();
            params.set('price_id', currentPriceId);
            if (extraPriceIds.length) {
                params.set('extra_price_ids', extraPriceIds.join(','));
            }
            if (shippingPriceId) {
                params.set('shipping_price_id', shippingPriceId);
            }
            if (currentCategoryId) {
                params.set('category_id', currentCategoryId);
            }
            if (selectedVariant) params.set('variant_id', selectedVariant);
            if (extraIds) params.set('extra_ids', extraIds);
            if (selectedDuration) params.set('duration_id', selectedDuration);
            if (startDate) params.set('start_date', startDate);
            if (endDate) params.set('end_date', endDate);
            if (selectedDays) params.set('days', selectedDays);
            if (selectedCondition) params.set('condition_id', selectedCondition);
            if (selectedProductColor) params.set('product_color_id', selectedProductColor);
            if (selectedFrameColor) params.set('frame_color_id', selectedFrameColor);
            if (currentPrice) params.set('final_price', currentPrice);

            const produktName = $('.produkt-option[data-type="variant"].selected h4').text().trim();
            const extraNames = $('.produkt-option[data-type="extra"].selected .produkt-extra-name')
                .map(function(){ return $(this).text().trim(); })
                .get().join(', ');
            const dauerName = $('.produkt-option[data-type="duration"].selected .produkt-duration-name').text().trim();
            const zustandName = $('.produkt-option[data-type="condition"].selected .produkt-condition-name').text().trim();
            const produktfarbeName = $('.produkt-option[data-type="product-color"].selected').data('color-name');
            const gestellfarbeName = $('.produkt-option[data-type="frame-color"].selected').data('color-name');

            if (produktName) params.set('produkt', produktName);
            if (extraNames) params.set('extra', extraNames);
            if (dauerName) params.set('dauer_name', dauerName);
            if (zustandName) params.set('zustand', zustandName);
            if (produktfarbeName) params.set('produktfarbe', produktfarbeName);
            if (gestellfarbeName) params.set('gestellfarbe', gestellfarbeName);

            window.location.href = produkt_ajax.checkout_url + '?' + params.toString();
        }
    });

    // Handle thumbnail clicks
    $(document).on('click', '.produkt-thumbnail', function() {
        const index = $(this).data('index');
        showMainImage(index);
    });

    // Touch events for swipe navigation
    const mainImageContainer = document.getElementById('produkt-main-image-container');
    if (mainImageContainer) {
        mainImageContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        mainImageContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }

    function handleSwipe() {
        if (currentVariantImages.length <= 1) return;
        
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next image
                const nextIndex = (currentMainImageIndex + 1) % currentVariantImages.length;
                showMainImage(nextIndex);
            } else {
                // Swipe right - previous image
                const prevIndex = currentMainImageIndex === 0 ? currentVariantImages.length - 1 : currentMainImageIndex - 1;
                showMainImage(prevIndex);
            }
        }
    }

    function updateVariantImages(variantOption, activeIndex = 0) {
        const imagesData = variantOption.data('images');
        currentVariantImages = imagesData ? imagesData.filter(img => img && img.trim() !== '') : [];

        if (currentProductColorImage) {
            currentVariantImages.push(currentProductColorImage);
        }
        if (currentFrameColorImage) {
            currentVariantImages.push(currentFrameColorImage);
        }

        currentMainImageIndex = Math.min(activeIndex, currentVariantImages.length - 1);

        rebuildImageGallery();
    }

    function rebuildImageGallery() {
        const mainImageContainer = $('#produkt-main-image-container');
        const thumbnailsContainer = $('#produkt-thumbnails');

        if (currentVariantImages.length > 0) {
            showMainImage(currentMainImageIndex);

            if (currentVariantImages.length > 1) {
                let thumbnailsHtml = '';
                currentVariantImages.forEach((imageUrl, index) => {
                    thumbnailsHtml += `
                        <div class="produkt-thumbnail ${index === currentMainImageIndex ? 'active' : ''}" data-index="${index}">
                            <img src="${imageUrl}" alt="Bild ${index + 1}">
                        </div>
                    `;
                });
                thumbnailsContainer.html(thumbnailsHtml).show();

                if (window.innerWidth <= 768) {
                    showSwipeIndicator();
                }
            } else {
                thumbnailsContainer.hide();
                hideSwipeIndicator();
            }
        } else {
            showDefaultImage();
            thumbnailsContainer.hide();
            hideSwipeIndicator();
        }
    }

    function showMainImage(index) {
        if (currentVariantImages[index]) {
            currentMainImageIndex = index;
            const mainImageContainer = $('#produkt-main-image-container');
            
            // Update main image with fade effect
            const imageHtml = `<img src="${currentVariantImages[index]}" alt="Produkt" id="produkt-main-image" class="produkt-main-image produkt-fade-in">`;
            
            // Find and replace only the main image, keep extra overlay
            const existingMainImage = mainImageContainer.find('#produkt-main-image, #produkt-placeholder');
            if (existingMainImage.length > 0) {
                existingMainImage.fadeOut(200, function() {
                    $(this).replaceWith(imageHtml);
                    $('#produkt-main-image').fadeIn(200);
                });
            } else {
                mainImageContainer.prepend(imageHtml);
                $('#produkt-main-image').fadeIn(200);
            }
            
            // Update thumbnail active state
            $('.produkt-thumbnail').removeClass('active');
            $(`.produkt-thumbnail[data-index="${index}"]`).addClass('active');
        }
    }

    function showDefaultImage() {
        const mainImageContainer = $('#produkt-main-image-container');
        
        let imageHtml = '<div class="produkt-placeholder-image produkt-fade-in" id="produkt-placeholder">' +
            '<svg viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg" width="70%" height="100%">' +
            '<rect width="100%" height="100%" fill="#f0f0f0" stroke="#ccc" stroke-width="0" rx="8" ry="8"/>' +
            '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#666" font-size="14">Produktbild folgt in K\u00fcrze</text>' +
            '</svg>' +
            '</div>';
        
        // Find and replace only the main image, keep extra overlay
        const existingMainImage = mainImageContainer.find('#produkt-main-image, #produkt-placeholder');
        if (existingMainImage.length > 0) {
            existingMainImage.replaceWith(imageHtml);
        } else {
            mainImageContainer.prepend(imageHtml);
        }
    }

    function showSwipeIndicator() {
        const mainImageContainer = $('#produkt-main-image-container');
        if (mainImageContainer.find('.produkt-swipe-indicator').length === 0) {
            mainImageContainer.append('<div class="produkt-swipe-indicator">Wischen für mehr Bilder</div>');
        }
    }

    function hideSwipeIndicator() {
        $('.produkt-swipe-indicator').remove();
    }

    function updateExtraImage(extraOption) {
        const extraOverlay = $('#produkt-extra-overlay');
        const extraImage = $('#produkt-extra-image');

        let imageUrl = '';
        if (extraOption && extraOption.hasClass('selected')) {
            imageUrl = extraOption.data('extra-image');
        }
        if (!imageUrl) {
            // fallback to first selected extra with image
            $('.produkt-option[data-type="extra"].selected').each(function () {
                const url = $(this).data('extra-image');
                if (url && url.trim() !== '') {
                    imageUrl = url;
                    return false;
                }
            });
        }

        if (imageUrl && imageUrl.trim() !== '') {
            extraImage.attr('src', imageUrl);
            extraOverlay.fadeIn(300);
        } else {
            extraOverlay.fadeOut(300);
        }
    }

    function updateColorImage(colorOption) {
        if (!colorOption) {
            currentProductColorImage = null;
            currentFrameColorImage = null;
        } else if (!selectedVariant) {
            return;
        } else {
            const imageUrl = colorOption.data('color-image') || '';
            const type = colorOption.data('type');
            if (type === 'product-color') {
                currentProductColorImage = imageUrl.trim() !== '' ? imageUrl : null;
            } else if (type === 'frame-color') {
                currentFrameColorImage = imageUrl.trim() !== '' ? imageUrl : null;
            }
        }

        const variantOption = $('.produkt-option[data-type="variant"].selected');
        if (variantOption.length) {
            const baseImages = variantOption.data('images');
            const variantImages = baseImages ? baseImages.filter(img => img && img.trim() !== '') : [];
            let index = 0;
            if (colorOption) {
                const type = colorOption.data('type');
                index = variantImages.length;
                if (type === 'frame-color' && currentProductColorImage) index += 1;
            }
            updateVariantImages(variantOption, index);

            if (colorOption && (currentProductColorImage || currentFrameColorImage)) {
                showGalleryNotification();
            }
        }
    }

    function updateDiscountBadges(discounts) {
        $('.produkt-options.durations .produkt-option').each(function(){
            const id = $(this).data('id');
            const badge = $(this).find('.produkt-discount-badge');
            badge.remove();
            if (discounts && typeof discounts[id] !== 'undefined' && discounts[id] > 0) {
                const pct = (discounts[id] * 100).toFixed(1).replace('.', ',');
                $(this).find('.produkt-duration-header').append(`<span class="produkt-discount-badge">-${pct}%</span>`);
            }
        });
    }

    function updateVariantOptions(variantId) {
        // Get variant-specific options via AJAX
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_variant_options',
                variant_id: variantId,
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update conditions
                    updateOptionsDisplay('#condition-section', '.produkt-options.conditions', data.conditions, 'condition');
                    
                    // Update product colors
                    updateOptionsDisplay('#product-color-section', '.produkt-options.product-colors', data.product_colors, 'product-color');

                    // Update frame colors
                    updateOptionsDisplay('#frame-color-section', '.produkt-options.frame-colors', data.frame_colors, 'frame-color');

                    // Update extras
                    updateOptionsDisplay('#extras-section', '.produkt-options.extras', data.extras, 'extra');

                    // Reset selections for variant-specific options
                    selectedCondition = null;
                    selectedProductColor = null;
                    selectedFrameColor = null;
                    $('#selected-product-color-name').text('');
                    $('#selected-frame-color-name').text('');
                    selectedExtras = [];
                    selectedDuration = null;
                    $('.produkt-options.durations .produkt-option').removeClass('selected');
                    updateExtraImage(null);
                    updateColorImage(null);

                    updateDiscountBadges(data.duration_discounts || {});
                    updatePriceAndButton();
                }
            }
        });
    }

    function updateOptionsDisplay(sectionSelector, containerSelector, options, optionType) {
        const section = $(sectionSelector);
        const container = $(containerSelector);
        
        if (options.length === 0) {
            // Hide section if no options available
            section.hide();
            return;
        }
        
        // Show section and clear container
        section.show();
        container.empty();
        
        options.forEach(function(option) {
            let optionHtml = '';
            
            if (optionType === 'condition') {
                const badgeHtml = option.price_modifier != 0 ?
                    `<span class="produkt-condition-badge">${option.price_modifier > 0 ? '+' : ''}${Math.round(option.price_modifier * 100)}%</span>` : '';

                optionHtml = `
                    <div class="produkt-option ${option.available == 0 ? 'unavailable' : ''}" data-type="condition" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}">
                        <div class="produkt-option-content">
                            <div class="produkt-condition-header">
                                <span class="produkt-condition-name">${option.name}</span>
                                ${badgeHtml}
                            </div>
                            <p class="produkt-condition-info">${option.description}</p>
                        </div>
                        <div class="produkt-option-check">✓</div>
                    </div>
                `;
            } else if (optionType === 'product-color' || optionType === 'frame-color') {
                optionHtml = `
                    <div class="produkt-option ${option.available == 0 ? 'unavailable' : ''}" data-type="${optionType}" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}" data-color-name="${option.name}" data-color-image="${option.image_url || ''}">
                        <div class="produkt-option-content">
                            <div class="produkt-color-display">
                                <div class="produkt-color-preview" style="background-color: ${option.color_code};"></div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (optionType === 'extra') {
                const priceSuffix = produkt_ajax.price_period === 'month' ? '/Monat' : '';
                const priceHtml = option.price > 0 ? `+${parseFloat(option.price).toFixed(2).replace('.', ',')}€${priceSuffix}` : '';
                optionHtml = `
                    <div class="produkt-option ${option.available == 0 ? 'unavailable' : ''}"
                         data-type="extra"
                         data-id="${option.id}"
                         data-price-id="${option.stripe_price_id || ''}"
                         data-extra-image="${option.image_url || ''}"
                         data-available="${option.available == 0 ? 'false' : 'true'}">
                        <div class="produkt-option-content">
                            <span class="produkt-extra-name">${option.name}</span>
                            ${priceHtml ? `<div class="produkt-extra-price">${priceHtml}</div>` : ''}
                        </div>
                        <div class="produkt-option-check">✓</div>
                    </div>
                `;
            }

            container.append(optionHtml);
        });

        // Remove any leftover inline color names
        container.find('.produkt-color-name').remove();

        // Re-bind click events for new options
        container.find('.produkt-option').on('click', function() {
            const type = $(this).data('type');
            const id = $(this).data('id');

            const available = $(this).data('available');
            if (available === false || available === 'false' || available === 0 || available === '0') {
                resetAllSelections();
                $('#produkt-rent-button').prop('disabled', true);
                $('.produkt-mobile-button').prop('disabled', true);
                $('#produkt-button-help').hide();
                $('#produkt-unavailable-help').show();
                $('#produkt-notify').show();
                $('.produkt-notify-form').show();
                $('#produkt-notify-success').hide();
                $('#produkt-availability-wrapper').show();
                $('#produkt-availability-status').addClass('unavailable').removeClass('available');
                $('#produkt-availability-status .status-text').text('Nicht auf Lager');
                $('#produkt-delivery-box').hide();
                scrollToNotify();
                return;
            }

            if (type === 'extra') {
                $(this).toggleClass('selected');
                const index = selectedExtras.indexOf(id);
                if (index > -1) {
                    selectedExtras.splice(index, 1);
                } else {
                    selectedExtras.push(id);
                }
                updateExtraImage($(this));
            } else {
                $(`.produkt-option[data-type="${type}"]`).removeClass('selected');
                $(this).addClass('selected');

                if (type === 'condition') {
                    selectedCondition = id;
                } else if (type === 'product-color') {
                    selectedProductColor = id;
                    $('#selected-product-color-name').text($(this).data('color-name'));
                    updateColorImage($(this));
                } else if (type === 'frame-color') {
                    selectedFrameColor = id;
                    $('#selected-frame-color-name').text($(this).data('color-name'));
                    updateColorImage($(this));
                }
            }
            
            // Track interaction
            trackInteraction(type.replace('-', '_') + '_select', {
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor
            });
            
            updatePriceAndButton();
        });
    }

    function updatePriceAndButton() {
        // Check if all required selections are made
        const requiredSelections = [];
        if ($('.produkt-options.variants').length > 0) requiredSelections.push(selectedVariant);
        if ($('.produkt-options.extras').length > 0) requiredSelections.push(true);
        if (produkt_ajax.betriebsmodus === 'kauf') {
            requiredSelections.push(selectedDays > 0);
        } else if ($('.produkt-options.durations').length > 0) {
            requiredSelections.push(selectedDuration);
        }
        
        // Check for visible optional sections
        if ($('#condition-section').is(':visible') && $('.produkt-options.conditions .produkt-option').length > 0) {
            requiredSelections.push(selectedCondition);
        }
        if ($('#product-color-section').is(':visible') && $('.produkt-options.product-colors .produkt-option').length > 0) {
            requiredSelections.push(selectedProductColor);
        }
        if ($('#frame-color-section').is(':visible') && $('.produkt-options.frame-colors .produkt-option').length > 0) {
            requiredSelections.push(selectedFrameColor);
        }
        
        const allSelected = requiredSelections.every(selection => selection !== null);
        
        if (allSelected) {
            // Show loading state
            $('#produkt-price-display').show();
            $('#produkt-final-price').text('Lädt...');

            // Make AJAX request
            $.ajax({
                url: produkt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_price',
                    variant_id: selectedVariant,
                    extra_ids: selectedExtras.join(','),
                    duration_id: selectedDuration,
                    condition_id: selectedCondition,
                    product_color_id: selectedProductColor,
                    frame_color_id: selectedFrameColor,
                    days: selectedDays,
                    nonce: produkt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        currentPrice = data.final_price;
                        currentShippingCost = data.shipping_cost || 0;
                        
                        // Update price display
                        $('#produkt-final-price').text(formatPrice(data.final_price) + '€');

                        if (data.discount > 0 && data.original_price) {
                            $('#produkt-original-price').text(formatPrice(data.original_price) + '€').show();
                        } else {
                            $('#produkt-original-price').hide();
                        }
                        $('#produkt-savings').hide();

                        // Update button based on availability
                        currentPriceId = data.price_id || '';
                        const isAvailable = data.available !== false;

                        $('#produkt-rent-button').prop('disabled', !isAvailable);
                        $('.produkt-mobile-button').prop('disabled', !isAvailable);

                        $('#produkt-availability-wrapper').show();
                        if (isAvailable) {
                            $('#produkt-availability-status').removeClass('unavailable').addClass('available');
                            $('#produkt-availability-status .status-text').text('Sofort verfügbar');
                            $('#produkt-delivery-time').text(data.delivery_time || '');
                            $('#produkt-delivery-box').show();
                        } else {
                            $('#produkt-availability-status').addClass('unavailable').removeClass('available');
                            $('#produkt-availability-status .status-text').text('Nicht auf Lager');
                            $('#produkt-delivery-box').hide();
                        }

                        if (isAvailable) {
                            $('#produkt-button-help').hide();
                            $('#produkt-unavailable-help').hide();
                            $('#produkt-notify').hide();
                            $('.produkt-notify-form').show();
                            $('#produkt-notify-success').hide();
                        } else {
                            $('#produkt-button-help').hide();
                            $('#produkt-unavailable-help').show();
                            $('#produkt-notify').show();
                            $('.produkt-notify-form').show();
                            $('#produkt-notify-success').hide();
                            if (data.availability_note) {
                                $('#produkt-unavailable-help').text(data.availability_note);
                            }
                            scrollToNotify();
                        }
                        
                        // Update mobile sticky price
                        updateMobileStickyPrice(data.final_price, data.original_price, data.discount, isAvailable);

                        if (produkt_ajax.betriebsmodus === 'kauf') {
                            $('.produkt-price-period').hide();
                            $('.produkt-mobile-price-period').hide();
                            $('#produkt-rent-button span').text('Jetzt kaufen');
                            $('.produkt-mobile-button span').text('Jetzt kaufen');
                        } else {
                            $('.produkt-price-period').show().text('/Monat');
                            $('.produkt-mobile-price-period').show().text('/Monat');
                            $('#produkt-rent-button span').text('Jetzt mieten');
                            $('.produkt-mobile-button span').text('Jetzt mieten');
                        }
                    }
                },
                error: function() {
                    $('#produkt-final-price').text('Fehler');
                }
            });
        } else {
            // Hide price display and disable button
            $('#produkt-price-display').hide();
            $('#produkt-rent-button').prop('disabled', true);
            $('.produkt-mobile-button').prop('disabled', true);
            $('#produkt-button-help').show();
            $('#produkt-unavailable-help').hide();
            $('#produkt-notify').hide();
            $('#produkt-notify-success').hide();
            $('.produkt-notify-form').show();
            currentPrice = 0;

            $('#produkt-availability-wrapper').hide();
            
            // Hide mobile sticky price
            hideMobileStickyPrice();

            if (produkt_ajax.betriebsmodus === 'kauf') {
                $('.produkt-price-period').hide();
                $('.produkt-mobile-price-period').hide();
                $('#produkt-rent-button span').text('Jetzt kaufen');
                $('.produkt-mobile-button span').text('Jetzt kaufen');
            } else {
                $('.produkt-price-period').show().text('/Monat');
                $('.produkt-mobile-price-period').show().text('/Monat');
                $('#produkt-rent-button span').text('Jetzt mieten');
                $('.produkt-mobile-button span').text('Jetzt mieten');
            }
        }
    }

    function initMobileStickyPrice() {
        if (window.innerWidth <= 768) {
            // Determine button label and icon from main button
            const mainButton = $('#produkt-rent-button');
            let mainLabel = mainButton.find('span').text().trim() || 'Jetzt Mieten';
            if (produkt_ajax.betriebsmodus === 'kauf') {
                mainLabel = 'Jetzt kaufen';
            }
            const mainIcon = mainButton.data('icon') ? `<img src="${mainButton.data('icon')}" class="produkt-button-icon-img" alt="Button Icon">` : '';

            // Create mobile sticky price bar
            const suffix = produkt_ajax.betriebsmodus === 'kauf' ? '' : (produkt_ajax.price_period === 'month' ? '/Monat' : '');
            const stickyHtml = `
                <div class="produkt-mobile-sticky-price" id="mobile-sticky-price">
                    <div class="produkt-mobile-sticky-content">
                        <div class="produkt-mobile-price-info">
                            <div class="produkt-mobile-price-label">${produkt_ajax.price_label}</div>
                            <div class="produkt-mobile-price-wrapper">
                                <span class="produkt-mobile-original-price" id="mobile-original-price" style="display:none;"></span>
                                <span class="produkt-mobile-final-price" id="mobile-price-value">0,00€</span>
                                <span class="produkt-mobile-price-period">${suffix}</span>
                            </div>
                        </div>
                        <button class="produkt-mobile-button" disabled>
                            ${mainIcon}
                            <span>${mainLabel}</span>
                        </button>
                    </div>
                </div>
            `;
            $('body').append(stickyHtml);
            if (produkt_ajax.betriebsmodus === 'kauf') {
                $('.produkt-mobile-price-period').hide();
            }
            
            // Show/hide based on scroll position
            $(window).scroll(function() {
                const scrollTop = $(this).scrollTop();
                const priceDisplay = $('#produkt-price-display');
                
                if (priceDisplay.is(':visible') && currentPrice > 0) {
                    const priceDisplayTop = priceDisplay.offset().top;
                    const priceDisplayBottom = priceDisplayTop + priceDisplay.outerHeight();
                    
                    // Show sticky price when price display is scrolled out of view (above viewport)
                    // Keep it visible for the entire page - never hide it once shown
                    const priceOutOfView = scrollTop > priceDisplayBottom;
                    
                    if (priceOutOfView) {
                        showMobileStickyPrice();
                    } else {
                        hideMobileStickyPrice();
                    }
                } else {
                    hideMobileStickyPrice();
                }
            });
        }
    }

    function updateMobileStickyPrice(finalPrice, originalPrice, discount, isAvailable) {
        if (window.innerWidth <= 768 && $('#mobile-sticky-price').length) {
            $('#mobile-price-value').text(formatPrice(finalPrice) + '€');

            if (discount > 0 && originalPrice) {
                $('#mobile-original-price').text(formatPrice(originalPrice) + '€').show();
            } else {
                $('#mobile-original-price').hide();
            }

            $('.produkt-mobile-button').prop('disabled', !isAvailable);
        }
    }

    function showMobileStickyPrice() {
        if (window.innerWidth <= 768 && $('#mobile-sticky-price').length) {
            $('#mobile-sticky-price').addClass('show');
        }
    }

    function hideMobileStickyPrice() {
        if ($('#mobile-sticky-price').length) {
            $('#mobile-sticky-price').removeClass('show');
        }
    }

    function destroyMobileStickyPrice() {
        if ($('#mobile-sticky-price').length) {
            hideMobileStickyPrice();
            $('#mobile-sticky-price').remove();
        }
    }

    function renderCalendar(date) {
        const cal = $('#booking-calendar');
        if (!cal.length) return;

        const month = date.getMonth();
        const year = date.getFullYear();
        calendarMonth = new Date(year, month, 1);

        const monthNames = ['Januar','Februar','M\u00e4rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
        const dayNames = ['Mo','Di','Mi','Do','Fr','Sa','So'];

        let html = '<div class="calendar-header">' +
            '<button class="prev-month">&lt;</button>' +
            '<span class="calendar-title">' + monthNames[month] + ' ' + year + '</span>' +
            '<button class="next-month">&gt;</button>' +
            '</div>';

        html += '<div class="calendar-grid">';
        dayNames.forEach(function(d){ html += '<div class="day-name">' + d + '</div>'; });

        const firstDay = new Date(year, month, 1);
        const startIndex = (firstDay.getDay() + 6) % 7;
        const lastDate = new Date(year, month + 1, 0).getDate();

        for(let i=0;i<startIndex;i++) { html += '<div class="empty"></div>'; }
        for(let d=1; d<=lastDate; d++) {
            const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            const cellDate = new Date(year, month, d);
            const today = new Date();
            today.setHours(0,0,0,0);
            let cls = 'calendar-day';
            if (cellDate < today) cls += ' disabled';
            if (Array.isArray(produkt_ajax.blocked_days) && produkt_ajax.blocked_days.includes(dateStr)) cls += ' disabled blocked';
            if (startDate === dateStr) cls += ' start';
            if (endDate === dateStr) cls += ' end';
            if (startDate && endDate && cellDate > new Date(startDate) && cellDate < new Date(endDate)) cls += ' in-range';
            html += '<div class="' + cls + '" data-date="' + dateStr + '">' + d + '</div>';
        }
        html += '</div>';
        cal.html(html);
    }

    $(document).on('click', '#booking-calendar .prev-month', function(){
        calendarMonth.setMonth(calendarMonth.getMonth() - 1);
        renderCalendar(calendarMonth);
    });
    $(document).on('click', '#booking-calendar .next-month', function(){
        calendarMonth.setMonth(calendarMonth.getMonth() + 1);
        renderCalendar(calendarMonth);
    });
    $(document).on('click', '#booking-calendar .calendar-day:not(.disabled)', function(){
        const dateStr = $(this).data('date');

        if (!startDate || (startDate && endDate)) {
            startDate = dateStr;
            endDate = null;
        } else if (new Date(dateStr) < new Date(startDate)) {
            startDate = dateStr;
            endDate = null;
        } else {
            // check for blocked days between startDate and selected end date
            let s = new Date(startDate);
            let e = new Date(dateStr);
            let hasBlocked = false;
            if (Array.isArray(produkt_ajax.blocked_days)) {
                let d = new Date(s.getTime());
                while (d <= e) {
                    const ds = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                    if (produkt_ajax.blocked_days.includes(ds)) {
                        hasBlocked = true;
                        break;
                    }
                    d.setDate(d.getDate() + 1);
                }
            }

            if (hasBlocked) {
                startDate = dateStr;
                endDate = null;
            } else {
                endDate = dateStr;
            }
        }

        renderCalendar(calendarMonth);
        updateSelectedDays();
        updatePriceAndButton();
    });

    function updateSelectedDays() {
        selectedDays = 0;
        if (startDate && endDate) {
            const s = new Date(startDate);
            const e = new Date(endDate);
            const diff = Math.round((e - s) / (1000 * 60 * 60 * 24)) + 1;
            if (diff > 0) {
                selectedDays = diff;
            }
        }
        $('#produkt-field-start-date').val(startDate || '');
        $('#produkt-field-end-date').val(endDate || '');
        $('#produkt-field-days').val(selectedDays);
        if (selectedDays > 0) {
            $('#booking-info').text('Mietzeitraum ' + selectedDays + ' Tag' + (selectedDays > 1 ? 'e' : ''));
        } else {
            $('#booking-info').text('');
        }
    }

    function showGalleryNotification() {
        if (window.innerWidth > 768) return;

        let toast = $('#produkt-color-toast');
        if (!toast.length) {
            toast = $('<div id="produkt-color-toast" class="produkt-color-toast">Ein Bild zur Farbe wurde der Produktgalerie hinzugefügt</div>');
            $('body').append(toast);
            toast.on('click', function() {
                const gallery = $('#produkt-image-gallery');
                if (gallery.length) {
                    $('html, body').animate({ scrollTop: gallery.offset().top - 100 }, 500);
                }
            });
        }

        toast.stop(true, true).fadeIn(200);
        clearTimeout(colorNotificationTimeout);
        colorNotificationTimeout = setTimeout(function() {
            toast.fadeOut(200);
        }, 3000);
    }


    function trackInteraction(eventType, data = {}) {
        if (!currentCategoryId) return;
        
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'track_interaction',
                category_id: currentCategoryId,
                event_type: eventType,
                variant_id: data.variant_id || null,
                extra_ids: data.extra_ids || null,
                duration_id: data.duration_id || null,
                condition_id: data.condition_id || null,
                product_color_id: data.product_color_id || null,
                frame_color_id: data.frame_color_id || null,
                nonce: produkt_ajax.nonce
            }
        });
    }

    function formatPrice(price) {
        return parseFloat(price).toFixed(2).replace('.', ',');
    }

    function scrollToNotify() {
        const target = $('#produkt-notify');
        if (target.length) {
            $('html, body').animate({ scrollTop: target.offset().top - 100 }, 500);
        }
    }

    // Notify when product becomes available
    $('#produkt-notify-submit').on('click', function(e) {
        e.preventDefault();
        const email = $('#produkt-notify-email').val().trim();
        if (!email) return;

        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'notify_availability',
                email: email,
                category_id: currentCategoryId,
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor,
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.produkt-notify-form').hide();
                    $('#produkt-notify-success').show();
                }
            }
        });
    });

    // Exit intent popup
    let exitShown = false;
    const popupData = produkt_ajax.popup_settings || {};
    const popup = $('#produkt-exit-popup');
    if (popup.length) {
        popup.appendTo('body');
    }
    const legacyExitKey = atob('ZmVkZXJ3aWVnZ2VuX2V4aXRfaGlkZV91bnRpbA==');
    const hideUntil = parseInt(
        localStorage.getItem('produkt_exit_hide_until') ||
        localStorage.getItem(legacyExitKey) ||
        '0',
        10
    );
    const daysSetting = parseInt(popupData.days || '7', 10);

    function showPopup() {
        if (exitShown) return;
        popup.css('display', 'flex');
        $('body').addClass('produkt-popup-open');
        exitShown = true;
    }

    function hidePopup() {
        popup.hide();
        $('body').removeClass('produkt-popup-open');
        if (daysSetting > 0) {
            const expire = Date.now() + daysSetting * 86400000;
            localStorage.setItem('produkt_exit_hide_until', expire.toString());
            exitShown = true;
        } else {
            localStorage.removeItem('produkt_exit_hide_until');
            exitShown = false;
        }
        localStorage.removeItem(legacyExitKey);
    }

    if (popup.length && popupData.enabled && popupData.title && (daysSetting === 0 || Date.now() > hideUntil)) {
        $('#produkt-exit-title').text(popupData.title);
        $('#produkt-exit-message').html(popupData.content);
        let showSend = false;
        if (popupData.options && popupData.options.length) {
            popupData.options.forEach(opt => {
                $('#produkt-exit-select').append(`<option value="${opt}">${opt}</option>`);
            });
            $('#produkt-exit-select-wrapper').show();
            showSend = true;
        }
        if (popupData.email) {
            $('#produkt-exit-email-wrapper').show();
            showSend = true;
        }
        if (showSend) {
            $('#produkt-exit-send').show();
        }

        $(document).on('mouseleave', function(e){
            if (!exitShown && e.clientY <= 0) {
                showPopup();
            }
        });

        if (window.matchMedia('(max-width: 768px)').matches) {
            let lastScroll = window.scrollY;
            let downEnough = lastScroll > 300;
            let inactivityTimer;
            const limit = 60000;

            function resetInactivity() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(function(){
                    if (!exitShown) {
                        showPopup();
                    }
                }, limit);
            }

            $(window).on('scroll', function(){
                const current = window.scrollY;
                if (current > lastScroll && current > 300) {
                    downEnough = true;
                } else if (!exitShown && downEnough && lastScroll - current > 50 && current < 150) {
                    showPopup();
                }
                lastScroll = current;
                resetInactivity();
            });

            $(document).on('touchstart keydown click', resetInactivity);
            resetInactivity();
        }

        $('.produkt-exit-popup-close').on('click', hidePopup);

        $('#produkt-exit-send').on('click', function(){
            const opt = $('#produkt-exit-select').val() || '';
            const emailVal = $('#produkt-exit-email').val() || '';
            $.post(produkt_ajax.ajax_url, {
                action: 'exit_intent_feedback',
                option: opt,
                user_email: emailVal,
                variant_id: selectedVariant || '',
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration || '',
                condition_id: selectedCondition || '',
                product_color_id: selectedProductColor || '',
                frame_color_id: selectedFrameColor || '',
                nonce: produkt_ajax.nonce
            }, function(){
                hidePopup();
            });
        });

    }
});

document.addEventListener("DOMContentLoaded", function () {
    const accordionHeaders = document.querySelectorAll(".produkt-accordion-header");

    accordionHeaders.forEach(header => {
        header.addEventListener("click", () => {
            const item = header.closest(".produkt-accordion-item");
            const content = item.querySelector(".produkt-accordion-content");

            if (item.classList.contains("active")) {
                item.classList.remove("active");
                content.style.maxHeight = null;
            } else {
                document.querySelectorAll(".produkt-accordion-item.active").forEach(openItem => {
                    openItem.classList.remove("active");
                    const openContent = openItem.querySelector(".produkt-accordion-content");
                    if (openContent) {
                        openContent.style.maxHeight = null;
                    }
                });

                item.classList.add("active");
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });
});

jQuery(function($) {
    $('#shop-filter-toggle').on('click', function() {
        $('#shop-filter-overlay').addClass('open');
        $('body').addClass('shop-filter-open');
    });

    $('#shop-filter-close').on('click', function() {
        $('#shop-filter-overlay').removeClass('open');
        $('body').removeClass('shop-filter-open');
    });

    function updateFilterQuery() {
        const ids = $('.shop-filter-checkbox:checked').map(function(){ return this.value; }).get();
        const params = new URLSearchParams(window.location.search);
        if (ids.length) {
            params.set('filter', ids.join(','));
        } else {
            params.delete('filter');
        }
        window.location.search = params.toString();
    }

    $(document).on('change', '.shop-filter-checkbox', updateFilterQuery);
});
