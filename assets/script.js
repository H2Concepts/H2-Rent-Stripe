var pendingCheckoutUrl = '';
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
    let shippingProvider = '';
    let startDate = null;
    let endDate = null;
    let selectedDays = 0;
    let variantWeekendOnly = false;
    let variantMinDays = 0;
    let weekendTariff = false;
    let calendarMonth = new Date();
    let colorNotificationTimeout = null;
    let cart = JSON.parse(localStorage.getItem('produkt_cart') || '[]');
    if (cart.length > 0) {
        startDate = cart[0].start_date || null;
        endDate = cart[0].end_date || null;
    }

    function updateCartBadge() {
        $('.h2-cart-badge').text(cart.length); // alle Instanzen (Desktop/Mobil/Sticky)
    }
    updateCartBadge();
    updateSelectedDays();
    updatePriceAndButton();

    window.addEventListener('storage', function(e){
        if (e.key === 'produkt_cart') {
            try { cart = JSON.parse(localStorage.getItem('produkt_cart') || '[]'); } catch(e){ cart = []; }
            updateCartBadge();
        }
    });

    // Tooltip modal setup
    const tooltipModal = $('<div>', {id: 'produkt-tooltip-modal', class: 'produkt-tooltip-modal'}).append(
        $('<div>', {class: 'modal-content'}).append(
            $('<div>', {class: 'modal-header'}).append(
                $('<button>', {class: 'modal-close', 'aria-label': 'Schließen'}).text('×')
            ),
            $('<div>', {class: 'modal-text'})
        )
    );
    $('body').append(tooltipModal);

    $(document).on('click', '.produkt-tooltip', function(e){
        e.preventDefault();
        const text = $(this).find('.produkt-tooltiptext').text().trim();
        if (!text) return;
        $('#produkt-tooltip-modal .modal-text').text(text);
        $('#produkt-tooltip-modal').css('display', 'flex');
        $('body').addClass('produkt-popup-open');
    });

    $(document).on('click', '#produkt-tooltip-modal', function(e){
        if (e.target === this || $(e.target).hasClass('modal-close')) {
            $('#produkt-tooltip-modal').hide();
            $('body').removeClass('produkt-popup-open');
        }
    });

    function saveCart() {
        localStorage.setItem('produkt_cart', JSON.stringify(cart));
        updateCartBadge();
    }

    function renderCart() {
        const list = $('#produkt-cart-panel .cart-items').empty();
        if (!cart.length) {
            list.append('<p>Ihr Warenkorb ist leer.</p>');
            $('.cart-total-amount').text('0€');
            updateCartBadge();
            return;
        }
        let total = 0;
        cart.forEach((item, idx) => {
            total += parseFloat(item.final_price || 0);
            const row = $('<div>', {class: 'cart-item'});
            const imgWrap = $('<div>', {class: 'cart-item-image'});
            if (item.image) {
                imgWrap.append($('<img>', {src: item.image, alt: 'Produkt'}));
            }
            const details = $('<div>', {class: 'cart-item-details'});
            details.append($('<div>', {class: 'cart-item-name'}).text(item.produkt || 'Produkt'));
            if (item.extra) {
                const extrasContainer = $('<div>', {class: 'cart-item-extras'});
                item.extra.split(',').forEach(function(ex){
                    const trimmed = ex.trim();
                    if (trimmed) extrasContainer.append($('<div>').text(trimmed));
                });
                details.append(extrasContainer);
            }
            if (item.produktfarbe) {
                details.append($('<div>', {class: 'cart-item-color'}).text('Farbe: ' + item.produktfarbe));
            }
            if (item.gestellfarbe) {
                details.append($('<div>', {class: 'cart-item-color'}).text('Gestellfarbe: ' + item.gestellfarbe));
            }
            let period = '';
            if (item.start_date && item.end_date) {
                const startFmt = formatDate(item.start_date);
                const endFmt = formatDate(item.end_date);
                period = startFmt + ' - ' + endFmt + ' (' + item.days + ' Tage)';
            } else if (item.dauer_name) {
                period = item.dauer_name;
            }
            if (period) {
                details.append($('<div>', {class: 'cart-item-period'}).text(period));
            }
            if (item.weekend_tarif) {
                details.append($('<div>', {class: 'cart-item-weekend'}).text('Wochenendtarif'));
            }
            const price = $('<div>', {class: 'cart-item-price'}).text(formatPrice(item.final_price) + '€');
            const rem = $('<span>', {class: 'cart-item-remove', 'data-index': idx}).text('×');
            row.append(imgWrap, details, price, rem);
            list.append(row);
        });
        $('.cart-total-amount').text(formatPrice(total) + '€');
        updateCartBadge();
    }

    function openCart() {
        renderCart();
        $('#produkt-cart-panel').addClass('open');
        $('#produkt-cart-overlay').addClass('open');
        $('body').addClass('produkt-popup-open');
    }

    function closeCart() {
        $('#produkt-cart-panel').removeClass('open');
        $('#produkt-cart-overlay').removeClass('open');
        $('body').removeClass('produkt-popup-open');
    }

    $(document).on('click', '.cart-close', closeCart);
    $(document).on('click', '#produkt-cart-overlay', closeCart);
    $(document).on('click', '.cart-item-remove', function(){
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx)) {
            cart.splice(idx, 1);
            saveCart();
            renderCart();
        }
    });
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
        shippingProvider = container.data('shipping-provider') || '';
        const firstShip = $('.shipping-options .produkt-option.selected').first();
        if (firstShip.length) {
            shippingPriceId = firstShip.data('price-id').toString();
            const cost = parseFloat(firstShip.data('price'));
            if (!isNaN(cost)) {
                currentShippingCost = cost;
            }
            shippingProvider = firstShip.data('provider') || shippingProvider;
        }
        $('#produkt-field-shipping').val(shippingPriceId);
    }

    renderCalendar(calendarMonth);
    if (!Array.isArray(produkt_ajax.variant_blocked_days)) {
        produkt_ajax.variant_blocked_days = [];
    }
    if (!Array.isArray(produkt_ajax.extra_blocked_days)) {
        produkt_ajax.extra_blocked_days = [];
    }
    if (typeof produkt_ajax.variant_weekend_only === 'undefined') {
        produkt_ajax.variant_weekend_only = false;
    }
    if (typeof produkt_ajax.variant_min_days === 'undefined') {
        produkt_ajax.variant_min_days = 0;
    }
    variantWeekendOnly = produkt_ajax.variant_weekend_only;
    variantMinDays = produkt_ajax.variant_min_days;

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

        produkt_ajax.variant_blocked_days = [];
        produkt_ajax.extra_blocked_days = [];

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
        if (available === false || available === 'false' || available === 0 || available === '0' || $(this).hasClass('unavailable')) {
            resetAllSelections();
            $('#produkt-rent-button').prop('disabled', true);
            $('.produkt-mobile-button').prop('disabled', true);
            $('#produkt-button-help').hide();
            $('#produkt-unavailable-help').text('Produkt im Mietzeitraum nicht verfügbar').show();
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
            variantWeekendOnly = $(this).data('weekend') == 1;
            variantMinDays = parseInt($(this).data('min-days'),10) || 0;
            produkt_ajax.variant_weekend_only = variantWeekendOnly;
            produkt_ajax.variant_min_days = variantMinDays;

            // Reset other selections when switching variants so the rent button
            // becomes inactive immediately. Preserve rental dates if a period
            // is already locked by existing cart items.
            selectedCondition = null;
            selectedProductColor = null;
            selectedFrameColor = null;
            selectedExtras = [];
            selectedDuration = null;
            if (cart.length === 0) {
                startDate = null;
                endDate = null;
                selectedDays = 0;
            }
            renderCalendar(calendarMonth);
            updateSelectedDays();

            $('.produkt-option[data-type="condition"]').removeClass('selected');
            $('.produkt-option[data-type="product-color"]').removeClass('selected');
            $('.produkt-option[data-type="frame-color"]').removeClass('selected');
            $('.produkt-option[data-type="extra"]').removeClass('selected');
            $('.produkt-option[data-type="duration"]').removeClass('selected');

            updateExtraImage(null);
            updateColorImage(null);

            updateVariantImages($(this));
            updateVariantOptions(id);
            updateVariantBookings(id);
            updateExtraBookings([]);
        } else if (type === 'extra') {
            const index = selectedExtras.indexOf(id);
            if (index > -1) {
                selectedExtras.splice(index, 1);
            } else {
                selectedExtras.push(id);
            }
            updateExtraImage($(this));
            updateExtraBookings(selectedExtras);
        } else if (type === 'shipping') {
            shippingPriceId = $(this).data('price-id') ? $(this).data('price-id').toString() : '';
            const cost = parseFloat($(this).data('price'));
            if (!isNaN(cost)) {
                currentShippingCost = cost;
            }
            shippingProvider = $(this).data('provider') || '';
            $('#produkt-field-shipping').val(shippingPriceId);
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

        if (produkt_ajax.betriebsmodus === 'kauf') {
            const produktName = $('.produkt-option[data-type="variant"].selected h4').text().trim();
            const extraNames = $('.produkt-option[data-type="extra"].selected .produkt-extra-name')
                .map(function(){ return $(this).text().trim(); })
                .get().join(', ');
            const dauerName = $('.produkt-option[data-type="duration"].selected .produkt-duration-name').text().trim();
            const zustandName = $('.produkt-option[data-type="condition"].selected .produkt-condition-name').text().trim();
            const produktfarbeName = $('.produkt-option[data-type="product-color"].selected').data('color-name');
            const gestellfarbeName = $('.produkt-option[data-type="frame-color"].selected').data('color-name');

            const extraPriceIds = $('.produkt-option[data-type="extra"].selected')
                .map(function(){ return $(this).data('price-id'); })
                .get()
                .filter(id => id);

            const item = {
                price_id: currentPriceId,
                extra_price_ids: extraPriceIds,
                shipping_price_id: shippingPriceId,
                category_id: currentCategoryId,
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                start_date: startDate,
                end_date: endDate,
                days: selectedDays,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor,
                final_price: currentPrice,
                weekend_tarif: weekendTariff ? 1 : 0,
                image: (function(){
                    let img = '';
                    const variantOption = $('.produkt-option[data-type="variant"].selected');
                    if (variantOption.length) {
                        const imgs = variantOption.data('images');
                        if (Array.isArray(imgs) && imgs.length) {
                            img = imgs[0];
                        }
                    }
                    if (!img) {
                        img = $('#produkt-main-image').attr('src') || '';
                    }
                    return img;
                })(),
                produkt: produktName,
                extra: extraNames,
                dauer_name: dauerName,
                zustand: zustandName,
                produktfarbe: produktfarbeName,
                gestellfarbe: gestellfarbeName
            };
            cart.push(item);
            saveCart();
            openCart();
        } else if (produkt_ajax.checkout_url) {
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
            if (weekendTariff) params.set('weekend_tarif', 1);
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
            const targetUrl = produkt_ajax.checkout_url + '?' + params.toString();

            if (produkt_ajax.is_logged_in) {
                window.location.href = targetUrl;
            } else {
                pendingCheckoutUrl = targetUrl;
                $('#checkout-login-modal').css('display', 'flex');
                $('body').addClass('produkt-popup-open');
            }
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
                    // Fetch blocked days and check availability only when dates are chosen
                    if (startDate && endDate) {
                        updateExtraBookings(getZeroStockExtraIds());
                        setTimeout(() => checkExtraAvailability(), 100);
                    } else {
                        // no dates yet -> clear blocked days and re-render calendar
                        produkt_ajax.extra_blocked_days = [];
                        setTimeout(() => renderCalendar(calendarMonth), 100);
                    }

                    updateDiscountBadges(data.duration_discounts || {});
                    updatePriceAndButton();
                }
            }
        });
    }

    function updateVariantBookings(variantId) {
        if (produkt_ajax.betriebsmodus !== 'kauf') {
            produkt_ajax.variant_blocked_days = [];
            renderCalendar(calendarMonth);
            return;
        }
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_variant_booked_days',
                variant_id: variantId,
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    produkt_ajax.variant_blocked_days = response.data.days || [];
                    renderCalendar(calendarMonth);
                    updatePriceAndButton();
                }
            }
        });
    }

    function updateExtraBookings(extraIds) {
        if (produkt_ajax.betriebsmodus !== 'kauf') {
            produkt_ajax.extra_blocked_days = [];
            renderCalendar(calendarMonth);
            return;
        }
        if (!extraIds || !extraIds.length) {
            produkt_ajax.extra_blocked_days = [];
            renderCalendar(calendarMonth);
            return;
        }
        let needsCheck = false;
        extraIds.forEach(function(id){
            const el = $('.produkt-option[data-type="extra"][data-id="' + id + '"]');
            if (parseInt(el.data('stock'), 10) === 0) {
                needsCheck = true;
            }
        });
        if (!needsCheck) {
            produkt_ajax.extra_blocked_days = [];
            renderCalendar(calendarMonth);
            return;
        }
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_extra_booked_days',
                extra_ids: extraIds.join(','),
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    produkt_ajax.extra_blocked_days = []; // keine Blockierung im Kalender
                    checkExtraAvailability(); // nur Extras farblich anpassen
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
                         data-available="${option.available == 0 ? 'false' : 'true'}"
                         data-stock="${option.stock_available}">
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
            if (available === false || available === 'false' || available === 0 || available === '0' || $(this).hasClass('unavailable')) {
                resetAllSelections();
                $('#produkt-rent-button').prop('disabled', true);
                $('.produkt-mobile-button').prop('disabled', true);
                $('#produkt-button-help').hide();
                $('#produkt-unavailable-help').text('Produkt im Mietzeitraum nicht verfügbar').show();
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
        
        const allSelected = requiredSelections.every(selection => selection !== null && selection !== false);
        const minOk = !(variantMinDays > 0 && selectedDays > 0 && selectedDays < variantMinDays);
        const rangeOk = isSelectedRangeAvailable();

        if (allSelected && minOk && rangeOk) {
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
                    start_date: startDate,
                    end_date: endDate,
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
                        if (data.weekend_applied) {
                            weekendTariff = true;
                            $('#produkt-weekend-note').text('Wochenendtarif').show();
                        } else {
                            weekendTariff = false;
                            $('#produkt-weekend-note').hide();
                        }

                        // Update button based on availability
                        currentPriceId = data.price_id || '';
                        const isAvailable = data.available !== false;

                        $('#produkt-rent-button').prop('disabled', !isAvailable);
                        $('.produkt-mobile-button').prop('disabled', !isAvailable);

                        $('#produkt-availability-wrapper').show();
                        if (isAvailable) {
                            $('#produkt-availability-status').removeClass('unavailable').addClass('available');
                            $('#produkt-availability-status .status-text').text('Sofort verfügbar');
                            if (shippingProvider === 'pickup') {
                                $('#produkt-delivery-box').text('Abholung').show();
                            } else {
                                $('#produkt-delivery-box').html('Lieferung <span id="produkt-delivery-time">' + (data.delivery_time || '') + '</span>').show();
                            }
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
                            $('#produkt-unavailable-help').text(data.availability_note || 'Produkt im Mietzeitraum nicht verfügbar').show();
                            $('#produkt-notify').show();
                            $('.produkt-notify-form').show();
                            $('#produkt-notify-success').hide();
                            scrollToNotify();
                        }
                        
                        // Update mobile sticky price
                        updateMobileStickyPrice(data.final_price, data.original_price, data.discount, isAvailable);

                        const label = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '') ? produkt_ajax.button_text : (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : 'Jetzt mieten');
                        if (produkt_ajax.betriebsmodus === 'kauf') {
                            $('.produkt-price-period').hide();
                            $('.produkt-mobile-price-period').hide();
                        } else {
                            $('.produkt-price-period').show().text('/Monat');
                            $('.produkt-mobile-price-period').show().text('/Monat');
                        }
                        $('#produkt-rent-button span').text(label);
                        $('.produkt-mobile-button span').text(label);
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
            currentPrice = 0;

            // Hide mobile sticky price
            hideMobileStickyPrice();

            const label = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '') ? produkt_ajax.button_text : (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : 'Jetzt mieten');
            if (produkt_ajax.betriebsmodus === 'kauf') {
                $('.produkt-price-period').hide();
                $('.produkt-mobile-price-period').hide();
            } else {
                $('.produkt-price-period').show().text('/Monat');
                $('.produkt-mobile-price-period').show().text('/Monat');
            }
            $('#produkt-rent-button span').text(label);
            $('.produkt-mobile-button span').text(label);

            if (allSelected && minOk && !rangeOk) {
                $('#produkt-button-help').hide();
                $('#produkt-unavailable-help').text('Produkt im Mietzeitraum nicht verfügbar').show();
                $('#produkt-notify').show();
                $('.produkt-notify-form').show();
                $('#produkt-notify-success').hide();
                $('#produkt-availability-wrapper').show();
                $('#produkt-availability-status').addClass('unavailable').removeClass('available');
                $('#produkt-availability-status .status-text').text('Nicht auf Lager');
                $('#produkt-delivery-box').hide();
            } else {
                $('#produkt-button-help').show();
                $('#produkt-unavailable-help').hide();
                $('#produkt-notify').hide();
                $('#produkt-notify-success').hide();
                $('.produkt-notify-form').show();
                $('#produkt-availability-wrapper').hide();
            }
        }
    }

    function initMobileStickyPrice() {
        if (window.innerWidth <= 768) {
            // Determine button label and icon from main button
            const mainButton = $('#produkt-rent-button');
            let mainLabel = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '') ? produkt_ajax.button_text : (mainButton.find('span').text().trim() || (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : 'Jetzt mieten'));
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
            if (produkt_ajax.variant_weekend_only && [5,6,0].indexOf(cellDate.getDay()) === -1) cls += ' disabled';
            let bdays = [];
            if (Array.isArray(produkt_ajax.blocked_days)) bdays = bdays.concat(produkt_ajax.blocked_days);
            if (Array.isArray(produkt_ajax.variant_blocked_days)) bdays = bdays.concat(produkt_ajax.variant_blocked_days);
            if (bdays.includes(dateStr)) cls += ' disabled blocked';
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
        if (cart.length > 0 && cart[0].start_date && cart[0].end_date) {
            return;
        }

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
            let blockedList = [];
            if (Array.isArray(produkt_ajax.blocked_days)) blockedList = blockedList.concat(produkt_ajax.blocked_days);
            if (Array.isArray(produkt_ajax.variant_blocked_days)) blockedList = blockedList.concat(produkt_ajax.variant_blocked_days);
            if (blockedList.length) {
                let d = new Date(s.getTime());
                while (d <= e) {
                    const ds = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                    if (blockedList.includes(ds)) {
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
        if (startDate && endDate) {
            updateExtraBookings(getZeroStockExtraIds());
        } else {
            updateExtraBookings([]);
        }
        checkExtraAvailability();
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
            let info = 'Mietzeitraum ' + selectedDays + ' Tag' + (selectedDays > 1 ? 'e' : '');
            if (variantMinDays > 0 && selectedDays < variantMinDays) {
                info += ' (mind. ' + variantMinDays + ' Tage)';
                $('#booking-info').addClass('error');
            } else {
                $('#booking-info').removeClass('error');
            }
            $('#booking-info').text(info);
        } else {
            $('#booking-info').removeClass('error').text('');
        }
}

    function getZeroStockExtraIds() {
        const ids = [];
        $('.produkt-option[data-type="extra"]').each(function(){
            if (parseInt($(this).data('stock'), 10) === 0) {
                ids.push($(this).data('id'));
            }
        });
        return ids;
    }

    function isSelectedRangeAvailable() {
        if (!startDate || !endDate) return true;
        let blocked = [];
        if (Array.isArray(produkt_ajax.blocked_days)) blocked = blocked.concat(produkt_ajax.blocked_days);
        if (Array.isArray(produkt_ajax.variant_blocked_days)) blocked = blocked.concat(produkt_ajax.variant_blocked_days);
        if (Array.isArray(produkt_ajax.extra_blocked_days)) blocked = blocked.concat(produkt_ajax.extra_blocked_days);
        const s = new Date(startDate);
        const e = new Date(endDate);
        for (let d = new Date(s.getTime()); d <= e; d.setDate(d.getDate() + 1)) {
            const ds = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            if (blocked.includes(ds)) return false;
        }
        return true;
    }

    function checkExtraAvailability() {
        const ids = getZeroStockExtraIds();
        if (!ids.length || !startDate || !endDate || !currentCategoryId) {
            $('.produkt-option[data-type="extra"].date-unavailable').each(function(){
                $(this).removeClass('date-unavailable');
                if ($(this).data('available') !== false && $(this).data('available') !== 'false' && $(this).data('available') !== 0 && $(this).data('available') !== '0') {
                    $(this).removeClass('unavailable');
                }
            });
            return;
        }
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_extra_availability',
                category_id: currentCategoryId,
                extra_ids: ids.join(','),
                start_date: startDate,
                end_date: endDate,
                nonce: produkt_ajax.nonce
            },
            success: function(resp){
                if (resp.success) {
                    const unavailable = resp.data.unavailable || [];
                    $('.produkt-option[data-type="extra"]').each(function(){
                        const id = parseInt($(this).data('id'),10);
                        if (unavailable.includes(id)) {
                            $(this).addClass('date-unavailable unavailable');
                            if ($(this).hasClass('selected')) {
                                $(this).removeClass('selected');
                                const idx = selectedExtras.indexOf(id);
                                if (idx > -1) selectedExtras.splice(idx,1);
                            }
                        } else {
                            $(this).removeClass('date-unavailable');
                            if ($(this).data('available') !== false && $(this).data('available') !== 'false' && $(this).data('available') !== 0 && $(this).data('available') !== '0') {
                                $(this).removeClass('unavailable');
                            }
                        }
                    });
                }
                updatePriceAndButton();
            }
        });
    }

    if (startDate && endDate) {
        updateSelectedDays();
        updateExtraBookings(getZeroStockExtraIds());
        checkExtraAvailability();
        renderCalendar(calendarMonth);
        updatePriceAndButton();
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

    function formatDate(dateStr) {
        const parts = dateStr.split('-');
        return parts[2] + '.' + parts[1] + '.' + parts[0];
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
    window.openCartSidebar = openCart;
    $('#checkout-login-btn').on('click', function(e){
        e.preventDefault();
        const email = $('#checkout-login-email').val().trim();
        if (!email) {
            alert('Bitte E-Mail eingeben');
            return;
        }
        const form = $('<form>', {method: 'POST', action: produkt_ajax.account_url});
        form.append($('<input>', {type: 'hidden', name: 'request_login_code_nonce', value: produkt_ajax.login_nonce}));
        form.append($('<input>', {type: 'hidden', name: 'request_login_code', value: '1'}));
        form.append($('<input>', {type: 'hidden', name: 'email', value: email}));
        form.append($('<input>', {type: 'hidden', name: 'redirect_to', value: pendingCheckoutUrl}));
        $('body').append(form);
        form.submit();
    });

    $('#produkt-cart-checkout').on('click', function(e){
        e.preventDefault();
        if (!cart.length) return;
        saveCart();
        const targetUrl = produkt_ajax.checkout_url + '?cart=1' + (shippingPriceId ? '&shipping_price_id=' + encodeURIComponent(shippingPriceId) : '');
        if (produkt_ajax.is_logged_in) {
            window.location.href = targetUrl;
        } else {
            pendingCheckoutUrl = targetUrl;
            $('#checkout-login-modal').css('display', 'flex');
            $('body').addClass('produkt-popup-open');
        }
    });

    $('#checkout-guest-link').on('click', function(e){
        e.preventDefault();
        $('#checkout-login-modal').hide();
        $('body').removeClass('produkt-popup-open');
        if (pendingCheckoutUrl) {
            window.location.href = pendingCheckoutUrl;
        }
    });

});

function produktInitAccordions() {
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

    document.querySelectorAll('.produkt-accordion-item.active .produkt-accordion-content').forEach(content => {
        content.style.maxHeight = content.scrollHeight + 'px';
    });
}

document.addEventListener("DOMContentLoaded", produktInitAccordions);
window.addEventListener("load", () => {
    document.querySelectorAll('.produkt-accordion-item.active .produkt-accordion-content').forEach(content => {
        content.style.maxHeight = content.scrollHeight + 'px';
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
        const ids = Array.from(new Set(
            $('.shop-filter-checkbox:checked').map(function(){ return this.value; }).get()
        ));
        const params = new URLSearchParams(window.location.search);

        // Remove existing filter and filter[] parameters
        [...params.keys()]
            .filter(k => k === 'filter' || k === 'filter[]')
            .forEach(k => params.delete(k));

        // Append current selections
        ids.forEach(id => params.append('filter[]', id));

        const qs = params.toString();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
    }

    $(document).on('change', '.shop-filter-checkbox', function(){
        const val = this.value;
        $('.shop-filter-checkbox').filter(`[value="${val}"]`).prop('checked', this.checked);
        updateFilterQuery();
    });
});
