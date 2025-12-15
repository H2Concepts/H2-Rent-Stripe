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
    let selectedSalePriceId = '';
    let selectedSalePrice = 0;
    let selectedVariantSaleEnabled = false;
    let shippingPriceId = '';
    let shippingProvider = '';
    let freeShippingActive = false;
    let startDate = null;
    let endDate = null;
    let selectedDays = 0;
    let variantWeekendOnly = false;
    let variantMinDays = 0;
    let weekendTariff = false;
    let calendarMonth = new Date();
    let colorNotificationTimeout = null;
    let cart = JSON.parse(localStorage.getItem('produkt_cart') || '[]');
    let emailCheckTimer = null;
    let emailExists = false;
    let reviewPayload = null;

    function saveShippingSelection(priceId, cost) {
        try {
            localStorage.setItem('produkt_shipping_selection', JSON.stringify({
                priceId: priceId || '',
                cost: !isNaN(parseFloat(cost)) ? parseFloat(cost) : 0
            }));
        } catch (e) {
            // ignore storage errors
        }
    }

    function loadShippingSelection() {
        try {
            const stored = JSON.parse(localStorage.getItem('produkt_shipping_selection') || '{}');
            if (stored && typeof stored === 'object') {
                if (stored.priceId) {
                    shippingPriceId = stored.priceId.toString();
                }
                if (!isNaN(parseFloat(stored.cost))) {
                    currentShippingCost = parseFloat(stored.cost);
                }
            }
        } catch (e) {
            // ignore parse errors
        }
    }

    function isFreeShippingEnabled() {
        return typeof produkt_ajax !== 'undefined'
            && parseInt(produkt_ajax.free_shipping_enabled || 0, 10) === 1
            && !isNaN(parseFloat(produkt_ajax.free_shipping_threshold))
            && parseFloat(produkt_ajax.free_shipping_threshold) > 0;
    }

    function getCartSubtotal() {
        return cart.reduce((sum, item) => sum + parseFloat(item.final_price || 0), 0);
    }

    function isFreeShippingActive(subtotal) {
        if (!isFreeShippingEnabled()) return false;
        const threshold = parseFloat(produkt_ajax.free_shipping_threshold || 0);
        if (isNaN(subtotal) || subtotal <= 0) return false;
        return subtotal >= threshold;
    }

    function getShippingPriceIdForValue(amount) {
        return isFreeShippingActive(amount) ? '' : shippingPriceId;
    }

    loadShippingSelection();

    const $loginModal = $('#checkout-login-modal');
    const $loginEmail = $('#checkout-login-email');
    const $emailWarning = $('#checkout-email-warning');
    const $guestLink = $('#checkout-guest-link');

    function getStoredCheckoutEmail() {
        try {
            return localStorage.getItem('produkt_checkout_email') || '';
        } catch (e) {
            return '';
        }
    }

    function setStoredCheckoutEmail(email) {
        try {
            localStorage.setItem('produkt_checkout_email', email);
        } catch (e) {
            // ignore storage errors
        }
    }

    /**
     * Validiert eine E-Mail-Adresse
     * @param {string} email - Die zu prüfende E-Mail-Adresse
     * @returns {boolean} - true wenn gültig, false wenn ungültig
     */
    function isValidEmail(email) {
        if (!email || typeof email !== 'string') {
            return false;
        }
        // RFC 5322 kompatible E-Mail-Validierung
        const emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
        return emailRegex.test(email.trim());
    }

    function showCheckoutLoginModal() {
        // Warenkorb schließen, bevor das Login-Modal geöffnet wird
        closeCart();
        
        const defaultEmail = (typeof produkt_ajax !== 'undefined' && produkt_ajax.current_user_email)
            ? produkt_ajax.current_user_email
            : '';
        const storedEmail = getStoredCheckoutEmail();
        const existing = $loginEmail.val().trim();
        const fillEmail = existing || defaultEmail || storedEmail;
        if (fillEmail) {
            $loginEmail.val(fillEmail);
        }
        emailExists = false;
        updateGuestLinkState();
        $loginModal.css('display', 'flex');
        $('body').addClass('produkt-popup-open');
        triggerEmailCheck();
    }

    function appendEmailToCheckoutUrl(url, email) {
        if (!url) return url;
        try {
            const built = new URL(url, window.location.origin);
            built.searchParams.set('customer_email', email);
            return built.toString();
        } catch (e) {
            const separator = url.includes('?') ? '&' : '?';
            return url + separator + 'customer_email=' + encodeURIComponent(email);
        }
    }

    function triggerEmailCheck() {
        if (emailCheckTimer) {
            clearTimeout(emailCheckTimer);
        }
        emailCheckTimer = setTimeout(() => {
            const email = $loginEmail.val().trim();
            if (!email) {
                $emailWarning.hide();
                emailExists = false;
                updateGuestLinkState();
                return;
            }
            $.post(produkt_ajax.ajax_url, {
                action: 'produkt_check_customer_email',
                nonce: produkt_ajax.nonce,
                email
            }, function(res){
                if (res && res.success && res.data && res.data.exists) {
                    $emailWarning.show();
                    emailExists = true;
                } else {
                    $emailWarning.hide();
                    emailExists = false;
                }
                updateGuestLinkState();
            }).fail(function(){
                $emailWarning.hide();
                emailExists = false;
                updateGuestLinkState();
            });
        }, 200);
    }

    function updateGuestLinkState() {
        if (emailExists) {
            $guestLink.addClass('disabled').css({
                'opacity': '0.5',
                'cursor': 'not-allowed'
            });
        } else {
            $guestLink.removeClass('disabled').css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        }
    }

    $loginEmail.on('input blur', triggerEmailCheck);

    function getStickyHeaderMode() {
        const allowed = ['disabled', 'header', 'footer'];
        const mode = (typeof produkt_ajax !== 'undefined' && typeof produkt_ajax.sticky_header_mode === 'string')
            ? produkt_ajax.sticky_header_mode.toLowerCase()
            : 'header';
        return allowed.includes(mode) ? mode : 'header';
    }

    function updateCartBadge() {
        $('.h2-cart-badge').text(cart.length); // alle Instanzen (Desktop/Mobil/Sticky)
    }
    updateCartBadge();

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

    const reviewModal = $('<div>', { id: 'produkt-review-modal', class: 'produkt-tooltip-modal produkt-review-modal' }).append(
      $('<div>', { class: 'modal-content' }).append(
        $('<div>', { class: 'modal-header' }).append(
          $('<div>', { class: 'modal-title' }).text('Bewertung abgeben'),
          $('<button>', { class: 'modal-close', 'aria-label': 'Schließen' }).text('×')
        ),
        $('<div>', { class: 'modal-text' }).append(
          $('<div>', { class: 'review-product-name' }),
          $('<div>', { class: 'review-stars', 'data-rating': 0 }),
          $('<textarea>', { class: 'review-text', rows: 3, placeholder: 'Kurzer Text (optional)…' }),
          $('<button>', { class: 'review-submit-btn' }).text('Bewertung einreichen'),
          $('<div>', { class: 'review-hint' })
        )
      )
    );

    $('body').append(reviewModal);

    function renderReviewStars($wrap, rating) {
      $wrap.empty();
      for (let i = 1; i <= 5; i++) {
        const $s = $('<button>', { type: 'button', class: 'review-star', 'data-star': i, 'aria-label': i + ' Sterne' }).text('★');
        if (i <= rating) $s.addClass('active');
        $wrap.append($s);
      }
    }
    renderReviewStars($('#produkt-review-modal .review-stars'), 0);

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

    $(document).on('click', '.open-review-modal', function () {
      const $btn = $(this);

      reviewPayload = {
        subscription_key: $btn.data('subscription-key'),
        order_id: parseInt($btn.data('order-id'), 10) || 0,
        product_index: parseInt($btn.data('product-index'), 10) || 0,
        product_id: parseInt($btn.data('product-id'), 10) || 0,
        rating: 0
      };

      $('#produkt-review-modal .review-product-name').text($btn.data('product-name') || '');
      $('#produkt-review-modal .review-text').val('');
      $('#produkt-review-modal .review-hint').text('');
      renderReviewStars($('#produkt-review-modal .review-stars'), 0);

      $('#produkt-review-modal').css('display', 'flex');
      $('body').addClass('produkt-popup-open');
    });

    $(document).on('click', '#produkt-review-modal', function(e){
      if (e.target === this || $(e.target).hasClass('modal-close')) {
        $('#produkt-review-modal').hide();
        $('body').removeClass('produkt-popup-open');
      }
    });

    $(document).on('click', '#produkt-review-modal .review-star', function(){
      const val = parseInt($(this).data('star'), 10) || 0;
      reviewPayload.rating = val;
      renderReviewStars($('#produkt-review-modal .review-stars'), val);
    });

    $(document).on('click', '#produkt-review-modal .review-submit-btn', function(){
      if (!reviewPayload || !reviewPayload.subscription_key) return;

      const text = ($('#produkt-review-modal .review-text').val() || '').trim();

      $.post(produkt_ajax.ajax_url, {
        action: 'submit_product_review',
        nonce: produkt_ajax.nonce,
        subscription_key: reviewPayload.subscription_key,
        order_id: reviewPayload.order_id,
        product_index: reviewPayload.product_index,
        product_id: reviewPayload.product_id,
        rating: reviewPayload.rating,
        review_text: text
      }).done(function(res){
        if (!res || !res.success) {
          $('#produkt-review-modal .review-hint').text((res && res.data && res.data.message) ? res.data.message : 'Fehler.');
          return;
        }

        const selector = '.open-review-modal[data-subscription-key="' + reviewPayload.subscription_key + '"]';
        $(selector).replaceWith('<button type="button" class="review-btn reviewed" disabled>Bewertet ✓</button>');

        $('#produkt-review-modal').hide();
        $('body').removeClass('produkt-popup-open');
      });
    });

    function saveCart() {
        localStorage.setItem('produkt_cart', JSON.stringify(cart));
        updateCartBadge();
    }

    function getCartTotalSuffix() {
        if (typeof produkt_ajax !== 'undefined' && produkt_ajax.betriebsmodus === 'kauf') {
            return '';
        }
        const suffixAttr = $('.cart-total-amount').data('suffix');
        if (typeof suffixAttr === 'string') {
            return suffixAttr;
        }
        return ' / Monat';
    }

    function updateFreeShippingBanner() {
        const $banner = $('.cart-free-shipping-banner');
        if (!$banner.length) return;

        const threshold = (typeof produkt_ajax !== 'undefined' && produkt_ajax.free_shipping_threshold !== undefined)
            ? parseFloat(produkt_ajax.free_shipping_threshold)
            : 0;
        const enabled = isFreeShippingEnabled() && threshold > 0;

        if (!enabled) {
            $banner.hide();
            return;
        }

        const subtotal = getCartSubtotal();
        const remaining = Math.max(threshold - subtotal, 0);
        const reached = subtotal >= threshold && subtotal > 0;

        const $text = $banner.find('.cart-free-shipping-text');
        const $progress = $banner.find('.cart-free-shipping-progress');
        const $bar = $banner.find('.cart-free-shipping-progress-bar');

        if (reached) {
            $text.text('Du hast Anspruch auf kostenlosen Versand.');
        } else {
            $text.text('Gib noch ' + formatPrice(remaining) + '€ mehr aus, um kostenlosen Versand zu erhalten!');
        }

        const percent = Math.max(0, Math.min(100, threshold > 0 ? (subtotal / threshold) * 100 : 0));
        $progress.attr('aria-valuenow', Math.round(percent));
        $progress.attr('aria-valuetext', reached ? 'Kostenloser Versand erreicht' : 'Noch ' + formatPrice(remaining) + '€');
        $bar.css('width', percent + '%');
        $bar.toggleClass('achieved', reached);

        $banner.show();
    }

    function updateCartShippingDisplay() {
        const $shippingAmount = $('.cart-shipping-amount');
        if (!$shippingAmount.length) return;
        const displayText = freeShippingActive ? 'Kostenlos' : (formatPrice(currentShippingCost) + '€');
        $shippingAmount.text(displayText);
    }

    function updateCartShippingCost(callback) {
        if (cart.length === 0) {
            // Warenkorb leer - Versand auf 0 setzen
            currentShippingCost = 0;
            freeShippingActive = false;
            updateCartShippingDisplay();
            if (callback) callback();
            return;
        }

        const subtotal = getCartSubtotal();
        if (isFreeShippingActive(subtotal)) {
            freeShippingActive = true;
            currentShippingCost = 0;
            updateCartShippingDisplay();
            if (callback) callback();
            return;
        }

        freeShippingActive = false;

        // Versandpreis aus dem ersten Item im Warenkorb nehmen
        const firstItem = cart[0];
        const itemShippingPriceId = firstItem.shipping_price_id || shippingPriceId;

        if (!itemShippingPriceId) {
            // Keine shipping_price_id - auf 0 setzen oder Standard verwenden
            currentShippingCost = 0;
            updateCartShippingDisplay();
            if (callback) callback();
            return;
        }

        // Versandpreis über AJAX abrufen
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_shipping_price',
                shipping_price_id: itemShippingPriceId,
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.shipping_cost !== undefined) {
                    currentShippingCost = parseFloat(response.data.shipping_cost) || 0;
                    saveShippingSelection(itemShippingPriceId, currentShippingCost);
                    updateCartShippingDisplay();
                } else {
                    currentShippingCost = 0;
                    updateCartShippingDisplay();
                }
                if (callback) callback();
            },
            error: function() {
                currentShippingCost = 0;
                updateCartShippingDisplay();
                if (callback) callback();
            }
        });
    }

    function updateCartTotal() {
        let total = 0;
        cart.forEach((item) => {
            total += parseFloat(item.final_price || 0);
        });
        
        // Im Vermietungsmodus wird der Versand separat angezeigt und nicht zur Gesamtsumme hinzugefügt
        // Im Kaufmodus wird der Versand zur Gesamtsumme hinzugefügt
        const isRentalMode = (typeof produkt_ajax !== 'undefined' && produkt_ajax.betriebsmodus !== 'kauf');
        if (!isRentalMode) {
            total += currentShippingCost;
        }
        
        $('.cart-total-amount').text(formatPrice(total) + '€' + getCartTotalSuffix());
    }

    function renderCart() {
        const list = $('#produkt-cart-panel .cart-items').empty();
        if (!cart.length) {
            list.append('<p>Ihr Warenkorb ist leer.</p>');
            currentShippingCost = 0;
            $('.cart-total-amount').text('0€' + getCartTotalSuffix());
            updateCartShippingDisplay();
            updateFreeShippingBanner();
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
            // Kategoriename als Hauptname (Fallback auf produkt oder 'Produkt')
            const categoryName = item.category_name || item.produkt || 'Produkt';
            const nameElement = item.product_url
                ? $('<a>', {class: 'cart-item-name', href: item.product_url, text: categoryName})
                : $('<div>', {class: 'cart-item-name'}).text(categoryName);
            details.append(nameElement);
            
            // Ausführung (nur wenn vorhanden)
            if (item.variant_name && item.variant_name.trim()) {
                details.append($('<div>', {class: 'cart-item-variant'}).text(item.variant_name));
            }
            
            // Extras (nur wenn vorhanden)
            if (item.extra && item.extra.trim()) {
                const extrasContainer = $('<div>', {class: 'cart-item-extras'});
                item.extra.split(',').forEach(function(ex){
                    const trimmed = ex.trim();
                    if (trimmed) extrasContainer.append($('<div>').text(trimmed));
                });
                if (extrasContainer.children().length > 0) {
                    details.append(extrasContainer);
                }
            }
            
            // Produktfarbe (nur wenn vorhanden)
            if (item.produktfarbe && item.produktfarbe.trim()) {
                const colorRow = $('<div>', {class: 'cart-item-color'});
                colorRow.append($('<span>', {class: 'cart-item-label'}).text('Farbe: '));
                colorRow.append($('<span>', {class: 'cart-item-value'}).text(item.produktfarbe));
                details.append(colorRow);
            }

            // Gestellfarbe (nur wenn vorhanden)
            if (item.gestellfarbe && item.gestellfarbe.trim()) {
                const frameRow = $('<div>', {class: 'cart-item-color'});
                frameRow.append($('<span>', {class: 'cart-item-label'}).text('Gestellfarbe: '));
                frameRow.append($('<span>', {class: 'cart-item-value'}).text(item.gestellfarbe));
                details.append(frameRow);
            }

            // Zustand (nur wenn vorhanden)
            if (item.zustand && item.zustand.trim()) {
                const conditionRow = $('<div>', {class: 'cart-item-condition'});
                conditionRow.append($('<span>', {class: 'cart-item-label'}).text('Zustand: '));
                conditionRow.append($('<span>', {class: 'cart-item-value'}).text(item.zustand));
                details.append(conditionRow);
            }
            
            // Mietdauer (nur wenn vorhanden)
            let period = '';
            let isRentalMode = (typeof produkt_ajax !== 'undefined' && produkt_ajax.betriebsmodus !== 'kauf');
            if (item.start_date && item.end_date) {
                period = item.start_date + ' - ' + item.end_date + ' (' + item.days + ' Tage)';
            } else if (item.dauer_name && item.dauer_name.trim()) {
                period = item.dauer_name;
            }
            if (period && isRentalMode && item.duration_id && item.variant_id) {
                // Editierbares Mietdauer-Element mit Pfeilen
                const periodRow = $('<div>', {class: 'cart-item-period-editable'});
                const durationSelector = $('<div>', {
                    class: 'cart-duration-selector',
                    'data-cart-index': idx,
                    'data-variant-id': item.variant_id,
                    'data-current-duration-id': item.duration_id,
                    'data-extra-ids': item.extra_ids || '',
                    'data-condition-id': item.condition_id || '',
                    'data-product-color-id': item.product_color_id || '',
                    'data-frame-color-id': item.frame_color_id || ''
                });
                
                const leftArrow = $('<button>', {
                    type: 'button',
                    class: 'cart-duration-arrow cart-duration-arrow-left',
                    'aria-label': 'Vorherige Mietdauer'
                }).html('&lt;');
                
                const durationDisplay = $('<span>', {
                    class: 'cart-duration-display'
                }).text(period);
                
                const rightArrow = $('<button>', {
                    type: 'button',
                    class: 'cart-duration-arrow cart-duration-arrow-right',
                    'aria-label': 'Nächste Mietdauer'
                }).html('&gt;');
                
                durationSelector.append(leftArrow, durationDisplay, rightArrow);
                periodRow.append(durationSelector);
                details.append(periodRow);
            } else if (period) {
                const periodRow = $('<div>', {class: 'cart-item-period'});
                periodRow.append($('<span>', {class: 'cart-item-label'}).text('Mietdauer: '));
                periodRow.append($('<span>', {class: 'cart-item-value'}).text(period));
                details.append(periodRow);
            }
            
            // Wochenendtarif (nur wenn vorhanden)
            if (item.weekend_tarif) {
                details.append($('<div>', {class: 'cart-item-weekend'}).text('Wochenendtarif'));
            }
            const price = $('<div>', {class: 'cart-item-price'}).text(formatPrice(item.final_price) + '€');
            const rem = $('<span>', {
                class: 'cart-item-remove',
                'data-index': idx,
                'aria-label': 'Artikel entfernen',
                title: 'Artikel entfernen'
            }).html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.56 65.83" role="img" aria-hidden="true"><path d="M62.78,10.94h-13v-4c0-3.31-2.69-6-6-6h-22c-3.31,0-6,2.69-6,6v4H2.78c-1.11,0-2,.89-2,2s.89,2,2,2h3.19l4.46,44.59c.29,3.07,2.88,5.42,5.96,5.41h32.77c3.08.01,5.67-2.33,5.96-5.4l4.46-44.6h3.19c1.11,0,2-.89,2-2s-.89-2-2-2h0ZM19.78,6.94c0-1.11.89-2,2-2h22c1.11,0,2,.89,2,2v4h-26v-4ZM51.14,59.15c-.1,1.02-.96,1.8-1.98,1.8H16.39c-1.03,0-1.89-.78-1.98-1.8L9.99,14.94h45.58l-4.42,44.2Z"/></svg>');
            row.append(imgWrap, details, price, rem);
            list.append(row);
        });

        updateFreeShippingBanner();

        // Versandpreis aktualisieren und dann Gesamtsumme berechnen
        updateCartShippingCost(function() {
            updateCartTotal();
        });
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
            // Versandpreis wird in renderCart() über updateCartShippingCost() aktualisiert
        }
    });

    // Mietdauer im Warenkorb ändern
    $(document).on('click', '.cart-duration-arrow', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        const $selector = $(this).closest('.cart-duration-selector');
        const cartIndex = parseInt($selector.data('cart-index'));
        const variantId = parseInt($selector.data('variant-id'));
        const currentDurationId = parseInt($selector.data('current-duration-id'));
        const isLeft = $(this).hasClass('cart-duration-arrow-left');
        
        if (isNaN(cartIndex) || isNaN(variantId) || isNaN(currentDurationId) || cartIndex < 0 || cartIndex >= cart.length) {
            return;
        }

        const $display = $selector.find('.cart-duration-display');
        $display.text('Lädt...');
        $selector.find('.cart-duration-arrow').prop('disabled', true);

        // Verfügbare Mietdauern abrufen
        $.ajax({
            url: produkt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_variant_durations',
                variant_id: variantId,
                nonce: produkt_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.durations) {
                    const durations = response.data.durations;
                    if (durations.length === 0) {
                        $display.text(cart[cartIndex].dauer_name || '');
                        $selector.find('.cart-duration-arrow').prop('disabled', false);
                        return;
                    }

                    // Aktuellen Index finden
                    let currentIndex = -1;
                    for (let i = 0; i < durations.length; i++) {
                        if (durations[i].id === currentDurationId) {
                            currentIndex = i;
                            break;
                        }
                    }

                    if (currentIndex === -1) {
                        currentIndex = 0;
                    }

                    // Nächste oder vorherige Mietdauer bestimmen
                    let newIndex;
                    if (isLeft) {
                        newIndex = currentIndex > 0 ? currentIndex - 1 : durations.length - 1;
                    } else {
                        newIndex = currentIndex < durations.length - 1 ? currentIndex + 1 : 0;
                    }

                    const newDuration = durations[newIndex];
                    const newDurationId = newDuration.id;

                    // Preis neu berechnen
                    const item = cart[cartIndex];
                    $.ajax({
                        url: produkt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_cart_item_duration',
                            cart_index: cartIndex,
                            variant_id: variantId,
                            duration_id: newDurationId,
                            extra_ids: (typeof item.extra_ids === 'string' ? item.extra_ids : (item.extra_ids || '')),
                            condition_id: item.condition_id || '',
                            product_color_id: item.product_color_id || '',
                            frame_color_id: item.frame_color_id || '',
                            nonce: produkt_ajax.nonce
                        },
                        success: function(priceResponse) {
                            if (priceResponse.success && priceResponse.data) {
                                // Warenkorb-Item aktualisieren
                                cart[cartIndex].duration_id = newDurationId;
                                cart[cartIndex].dauer_name = priceResponse.data.duration_name;
                                cart[cartIndex].final_price = priceResponse.data.final_price;
                                cart[cartIndex].price_id = priceResponse.data.price_id;
                                
                                saveCart();
                                // renderCart() wird aufgerufen, was updateCartShippingCost() und updateCartTotal() aufruft
                                renderCart();
                            } else {
                                $display.text(cart[cartIndex].dauer_name || '');
                                $selector.find('.cart-duration-arrow').prop('disabled', false);
                                alert('Fehler beim Aktualisieren der Mietdauer');
                            }
                        },
                        error: function() {
                            $display.text(cart[cartIndex].dauer_name || '');
                            $selector.find('.cart-duration-arrow').prop('disabled', false);
                            alert('Fehler beim Aktualisieren des Preises');
                        }
                    });
                } else {
                    $display.text(cart[cartIndex].dauer_name || '');
                    $selector.find('.cart-duration-arrow').prop('disabled', false);
                }
            },
            error: function() {
                $display.text(cart[cartIndex].dauer_name || '');
                $selector.find('.cart-duration-arrow').prop('disabled', false);
            }
        });
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
        const initialSubtotal = getCartSubtotal() || currentPrice || selectedSalePrice || 0;
        $('#produkt-field-shipping').val(getShippingPriceIdForValue(initialSubtotal));
        saveShippingSelection(shippingPriceId, currentShippingCost);
        updateCartShippingDisplay();
    }

    if (produkt_ajax.betriebsmodus === 'kauf') {
        renderCalendar(calendarMonth);
    }
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

    updateDirectBuyButton();

    function resetAllSelections() {
        selectedVariant = null;
        selectedExtras = [];
        selectedDuration = null;
        selectedCondition = null;
        selectedProductColor = null;
        selectedFrameColor = null;
        selectedSalePriceId = '';
        selectedSalePrice = 0;
        selectedVariantSaleEnabled = false;
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

    function canDirectBuy() {
        const requiredSelections = [];
        if ($('.produkt-options.variants').length > 0) requiredSelections.push(selectedVariant);
        if ($('#condition-section').is(':visible') && $('.produkt-options.conditions .produkt-option').length > 0) {
            requiredSelections.push(selectedCondition);
        }
        if ($('#product-color-section').is(':visible') && $('.produkt-options.product-colors .produkt-option').length > 0) {
            requiredSelections.push(selectedProductColor);
        }
        if ($('#frame-color-section').is(':visible') && $('.produkt-options.frame-colors .produkt-option').length > 0) {
            requiredSelections.push(selectedFrameColor);
        }
        return requiredSelections.every(selection => selection !== null && selection !== false);
    }

    function isSaleSelectionAllowed($option) {
        if (!$option || !$option.length) {
            return true;
        }
        const flag = $option.data('sale-available');
        if (typeof flag === 'undefined') {
            return true;
        }
        return !(flag === 0 || flag === '0' || flag === false || flag === 'false');
    }

    function saleOptionsAllowed() {
        const conditionOk = isSaleSelectionAllowed($('.produkt-options.conditions .produkt-option.selected'));
        const productColorOk = isSaleSelectionAllowed($('.produkt-options.product-colors .produkt-option.selected'));
        const frameColorOk = isSaleSelectionAllowed($('.produkt-options.frame-colors .produkt-option.selected'));
        return conditionOk && productColorOk && frameColorOk;
    }

    function updateDirectBuyButton() {
        const saleReady = selectedVariantSaleEnabled && selectedSalePriceId;
        const showButton = saleReady;
        const $directBuyButton = $('#produkt-direct-buy-button');
        if (showButton) {
            const canBuy = canDirectBuy() && saleOptionsAllowed();
            const priceText = selectedSalePrice > 0 ? `oder für ${formatPrice(selectedSalePrice)}€ kaufen` : 'oder direkt kaufen';
            $directBuyButton.find('span').text(priceText);
            $directBuyButton.show().prop('disabled', !canBuy);
        } else {
            $directBuyButton.hide().prop('disabled', true).find('span').text('oder direkt kaufen');
        }
    }

    // Initialize sticky price bar
    initMobileStickyPrice();
    $(window).on('resize', function() {
        if (!$('#mobile-sticky-price').length) {
            initMobileStickyPrice();
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
            $('#produkt-unavailable-help').show();
            $('#produkt-notify').show();
            $('.produkt-notify-form').show();
            $('#produkt-notify-success').hide();
            $('#produkt-availability-wrapper').show();
            $('#produkt-availability-status').addClass('unavailable').removeClass('available');
            $('#produkt-availability-status .status-text').html('<span class="stock-count"></span>Nicht auf Lager');
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
            selectedSalePriceId = $(this).data('sale-price-id') ? $(this).data('sale-price-id').toString() : '';
            selectedSalePrice = parseFloat($(this).data('sale-price')) || 0;
            selectedVariantSaleEnabled = $(this).data('sale-enabled') == 1 || $(this).data('sale-enabled') === true || $(this).data('sale-enabled') === '1';
            variantWeekendOnly = $(this).data('weekend') == 1;
            variantMinDays = parseInt($(this).data('min-days'),10) || 0;
            produkt_ajax.variant_weekend_only = variantWeekendOnly;
            produkt_ajax.variant_min_days = variantMinDays;

            // Reset selections when switching variants so the rent button
            // becomes inactive immediately
            selectedCondition = null;
            selectedProductColor = null;
            selectedFrameColor = null;
            selectedExtras = [];
            selectedDuration = null;
            startDate = null;
            endDate = null;
            selectedDays = 0;
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
            const amountForShipping = cart.length ? getCartSubtotal() : (currentPrice || selectedSalePrice || 0);
            $('#produkt-field-shipping').val(getShippingPriceIdForValue(amountForShipping));
            saveShippingSelection(shippingPriceId, currentShippingCost);
            updateCartShippingDisplay();
        } else if (type === 'duration') {
            selectedDuration = id;
        } else if (type === 'condition') {
            selectedCondition = id;
        } else if (type === 'product-color') {
            selectedProductColor = id;
            $('#selected-product-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
            // Availability status (incl. optional stock count) is finalized by the get_product_price AJAX response.
        } else if (type === 'frame-color') {
            selectedFrameColor = id;
            $('#selected-frame-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
        }

        // Update price and button state
        updatePriceAndButton();
        updateDirectBuyButton();
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

        if (produkt_ajax.cart_enabled) {
            const categoryName = $('.produkt-product-info h1').text().trim() || $('.produkt-container').data('category-name') || '';
            const variantName = $('.produkt-option[data-type="variant"].selected h4').text().trim();
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
                produkt: categoryName || variantName,
                category_name: categoryName,
                variant_name: variantName,
                extra: extraNames,
                dauer_name: dauerName,
                zustand: zustandName,
                produktfarbe: produktfarbeName,
                gestellfarbe: gestellfarbeName,
                product_url: window.location.href
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
            const subtotalForShipping = cart.length ? getCartSubtotal() : currentPrice;
            const shippingIdForCheckout = getShippingPriceIdForValue(subtotalForShipping);
            if (shippingIdForCheckout) {
                params.set('shipping_price_id', shippingIdForCheckout);
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
                showCheckoutLoginModal();
            }
        }
    });

    $('#produkt-direct-buy-button').on('click', function(e) {
        if ($(this).prop('disabled') || !selectedSalePriceId) {
            return;
        }

        e.preventDefault();

        const extraPriceIds = $('.produkt-option[data-type="extra"].selected')
            .map(function(){
                const salePid = $(this).data('sale-price-id');
                const basePid = $(this).data('price-id');
                return salePid || basePid;
            })
            .get()
            .filter(id => id);

        const params = new URLSearchParams();
        params.set('price_id', selectedSalePriceId);
        if (extraPriceIds.length) {
            params.set('extra_price_ids', extraPriceIds.join(','));
        }
        const saleSubtotal = selectedSalePrice || currentPrice;
        const shippingIdForSale = getShippingPriceIdForValue(saleSubtotal);
        if (shippingIdForSale) {
            params.set('shipping_price_id', shippingIdForSale);
        }
        if (currentCategoryId) {
            params.set('category_id', currentCategoryId);
        }
        if (selectedVariant) params.set('variant_id', selectedVariant);
        if (selectedCondition) params.set('condition_id', selectedCondition);
        if (selectedProductColor) params.set('product_color_id', selectedProductColor);
        if (selectedFrameColor) params.set('frame_color_id', selectedFrameColor);
        if (selectedSalePrice) params.set('final_price', selectedSalePrice);
        params.set('sale_mode', '1');

        const produktName = $('.produkt-option[data-type="variant"].selected h4').text().trim();
        const extraNames = $('.produkt-option[data-type="extra"].selected .produkt-extra-name')
            .map(function(){ return $(this).text().trim(); })
            .get().join(', ');
        const zustandName = $('.produkt-option[data-type="condition"].selected .produkt-condition-name').text().trim();
        const produktfarbeName = $('.produkt-option[data-type="product-color"].selected').data('color-name');
        const gestellfarbeName = $('.produkt-option[data-type="frame-color"].selected').data('color-name');

        if (produktName) params.set('produkt', produktName);
        if (extraNames) params.set('extra', extraNames);
        if (zustandName) params.set('zustand', zustandName);
        if (produktfarbeName) params.set('produktfarbe', produktfarbeName);
        if (gestellfarbeName) params.set('gestellfarbe', gestellfarbeName);

        const targetUrl = produkt_ajax.checkout_url + '?' + params.toString();

        if (produkt_ajax.is_logged_in) {
            window.location.href = targetUrl;
        } else {
            pendingCheckoutUrl = targetUrl;
            showCheckoutLoginModal();
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
                    <div class="produkt-option ${option.available == 0 ? 'unavailable' : ''}" data-type="condition" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}" data-sale-available="${typeof option.sale_available !== 'undefined' ? option.sale_available : ''}">
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
                const previewClass = option.is_multicolor == 1 ? 'produkt-color-preview produkt-color-preview--multicolor' : 'produkt-color-preview';
                const previewStyle = option.is_multicolor == 1 ? '' : `style="background-color: ${option.color_code};"`;
                
                // Check if inventory is enabled and stock_available is 0
                const $selectedVariant = $('.produkt-option[data-type="variant"].selected');
                const inventoryEnabled = $selectedVariant.data('inventory-enabled') === true || $selectedVariant.data('inventory-enabled') === 'true';
                const stockAvailable = typeof option.stock_available !== 'undefined' ? parseInt(option.stock_available, 10) : null;
                const isAvailable = inventoryEnabled && stockAvailable !== null 
                    ? (stockAvailable > 0 && option.available != 0)
                    : (option.available != 0);
                
                optionHtml = `
                    <div class="produkt-option ${!isAvailable ? 'unavailable' : ''}" data-type="${optionType}" data-id="${option.id}" data-available="${isAvailable ? 'true' : 'false'}" data-sale-available="${typeof option.sale_available !== 'undefined' ? option.sale_available : ''}" data-stock="${stockAvailable !== null ? stockAvailable : ''}" data-inventory-enabled="${inventoryEnabled ? 'true' : 'false'}" data-color-name="${option.name}" data-color-image="${option.image_url || ''}">
                        <div class="produkt-option-content">
                            <div class="produkt-color-display">
                                <div class="${previewClass}" ${previewStyle}></div>
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
        
        const allSelected = requiredSelections.every(selection => selection !== null && selection !== false);
        const minOk = !(variantMinDays > 0 && selectedDays > 0 && selectedDays < variantMinDays);

        if (allSelected && minOk) {
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
                        
                        const inventoryEnabled = data.inventory_enabled === 1 || data.inventory_enabled === '1' || data.inventory_enabled === true;
                        const stockAvailable = (typeof data.stock_available !== 'undefined' && data.stock_available !== null)
                            ? parseInt(data.stock_available, 10)
                            : null;
                        const showStockCount = (data.show_stock_count === 1 || data.show_stock_count === '1' || data.show_stock_count === true);
                        
                        if (isAvailable) {
                            $('#produkt-availability-status').removeClass('unavailable').addClass('available');
                            // Show stock count before "Sofort verfügbar" if inventory is enabled and stock > 0
                            const stockDisplay = (inventoryEnabled && showStockCount && stockAvailable !== null && stockAvailable > 0) ? stockAvailable + ' ' : '';
                            $('#produkt-availability-status .status-text').html('<span class="stock-count">' + stockDisplay + '</span>Sofort verfügbar');
                            if (shippingProvider === 'pickup') {
                                $('#produkt-delivery-box').text('Abholung').show();
                            } else {
                                $('#produkt-delivery-box').html('Lieferung <span id="produkt-delivery-time">' + (data.delivery_time || '') + '</span>').show();
                            }
                        } else {
                            $('#produkt-availability-status').addClass('unavailable').removeClass('available');
                            $('#produkt-availability-status .status-text').html('<span class="stock-count"></span>Nicht auf Lager');
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

                        const label = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '')
                            ? produkt_ajax.button_text
                            : (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : (produkt_ajax.cart_enabled ? 'In den Warenkorb' : 'Jetzt mieten'));
                        if (produkt_ajax.betriebsmodus === 'kauf') {
                            $('.produkt-price-period').hide();
                            $('.produkt-mobile-price-period').hide();
                        } else {
                            $('.produkt-price-period').show().text('/Monat');
                            $('.produkt-mobile-price-period').show().text('/Monat');
                        }
                        $('#produkt-rent-button span').text(label);
                        $('.produkt-mobile-button span').text(label);
                        updateDirectBuyButton();
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

            const label = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '')
                ? produkt_ajax.button_text
                : (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : (produkt_ajax.cart_enabled ? 'In den Warenkorb' : 'Jetzt mieten'));
            if (produkt_ajax.betriebsmodus === 'kauf') {
                $('.produkt-price-period').hide();
                $('.produkt-mobile-price-period').hide();
            } else {
                $('.produkt-price-period').show().text('/Monat');
                $('.produkt-mobile-price-period').show().text('/Monat');
            }
            $('#produkt-rent-button span').text(label);
            $('.produkt-mobile-button span').text(label);
            updateDirectBuyButton();
        }
    }

    function initMobileStickyPrice() {
        if ($('#mobile-sticky-price').length) return;

        const stickyMode = getStickyHeaderMode();
        if (stickyMode === 'disabled') return;

        // Determine button label and icon from main button
        const mainButton = $('#produkt-rent-button');
        let mainLabel = (produkt_ajax.button_text && produkt_ajax.button_text.trim() !== '')
            ? produkt_ajax.button_text
            : (mainButton.find('span').text().trim() || (produkt_ajax.betriebsmodus === 'kauf' ? 'Jetzt kaufen' : (produkt_ajax.cart_enabled ? 'In den Warenkorb' : 'Jetzt mieten')));
        const mainIcon = mainButton.data('icon') ? `<img src="${mainButton.data('icon')}" class="produkt-button-icon-img" alt="Button Icon">` : '';

        // Create sticky price bar
        const suffix = produkt_ajax.betriebsmodus === 'kauf' ? '' : (produkt_ajax.price_period === 'month' ? '/Monat' : '');
        const stickyClass = stickyMode === 'footer' ? ' sticky-footer' : '';
        const stickyHtml = `
            <div class="produkt-mobile-sticky-price${stickyClass}" id="mobile-sticky-price" data-mode="${stickyMode}">
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

    function updateMobileStickyPrice(finalPrice, originalPrice, discount, isAvailable) {
        if ($('#mobile-sticky-price').length) {
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
        if ($('#mobile-sticky-price').length) {
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
    const triggerConfig = popupData.triggers || {};

    function isTriggerEnabled(value, fallback = true) {
        if (typeof value === 'undefined' || value === null) {
            return fallback;
        }
        if (typeof value === 'string') {
            return value !== '0';
        }
        return Boolean(value);
    }

    const desktopExitEnabled = isTriggerEnabled(triggerConfig.desktop_exit);
    const mobileScrollEnabled = isTriggerEnabled(triggerConfig.mobile_scroll);
    const mobileInactivityEnabled = isTriggerEnabled(triggerConfig.mobile_inactivity);
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

        if (desktopExitEnabled) {
            $(document).on('mouseleave', function(e){
                if (!exitShown && e.clientY <= 0) {
                    showPopup();
                }
            });
        }

        if (window.matchMedia('(max-width: 768px)').matches && (mobileScrollEnabled || mobileInactivityEnabled)) {
            let lastScroll = window.scrollY;
            let downEnough = lastScroll > 300;
            let inactivityTimer;
            const limit = 60000;

            function resetInactivity() {
                if (!mobileInactivityEnabled) return;
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(function(){
                    if (!exitShown) {
                        showPopup();
                    }
                }, limit);
            }

            if (mobileScrollEnabled || mobileInactivityEnabled) {
                $(window).on('scroll', function(){
                    const current = window.scrollY;
                    if (mobileScrollEnabled) {
                        if (current > lastScroll && current > 300) {
                            downEnough = true;
                        } else if (!exitShown && downEnough && lastScroll - current > 50 && current < 150) {
                            showPopup();
                        }
                    }
                    lastScroll = current;
                    if (mobileInactivityEnabled) {
                        resetInactivity();
                    }
                });
            }

            if (mobileInactivityEnabled) {
                $(document).on('touchstart keydown click', resetInactivity);
                resetInactivity();
            }
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
        const email = $loginEmail.val().trim();
        if (!email) {
            alert('Bitte E-Mail eingeben');
            return;
        }
        if (!isValidEmail(email)) {
            alert('Bitte geben Sie eine gültige E-Mail-Adresse ein');
            return;
        }
        
        try {
            const cb = document.getElementById('checkout-newsletter-optin');
            localStorage.setItem('produkt_newsletter_optin', (cb && cb.checked) ? '1' : '0');
        } catch(e){}
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Wird gesendet...');
        
        $.post(produkt_ajax.ajax_url, {
            action: 'produkt_request_login_code',
            nonce: produkt_ajax.nonce,
            email: email
        }, function(response) {
            if (response && response.success) {
                // Code wurde gesendet - Code-Eingabefelder anzeigen
                $('#checkout-login-email-section').hide();
                $('#checkout-login-code-section').show();
                setStoredCheckoutEmail(email);
                // Code-Eingabe initialisieren und Fokus setzen
                // Warte etwas länger, damit das DOM vollständig gerendert ist
                setTimeout(function() {
                    // Markiere als nicht initialisiert, damit initCodeInputs() es neu initialisiert
                    $('#checkout-login-code-section .code-input-group').removeData('code-inputs-initialized');
                    initCodeInputs();
                    const $firstInput = $('#checkout-login-code-section .code-input').first();
                    if ($firstInput.length) {
                        $firstInput.focus();
                    }
                }, 200);
            } else {
                alert(response && response.data && response.data.message ? response.data.message : 'Fehler beim Senden des Codes. Bitte versuchen Sie es erneut.');
                $btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            alert('Fehler beim Senden des Codes. Bitte versuchen Sie es erneut.');
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    $('#checkout-verify-code-btn').on('click', function(e){
        e.preventDefault();
        const $btn = $(this);
        const $codeSection = $('#checkout-login-code-section');
        const $codeInputs = $codeSection.find('.code-input');
        const $hiddenInput = $('#checkout-login-code-combined');
        const $errorDiv = $('#checkout-code-error');
        const email = $loginEmail.val().trim();
        
        // Code aus den einzelnen Feldern zusammenfügen
        let code = '';
        $codeInputs.each(function() {
            code += $(this).val() || '';
        });
        
        if (code.length !== 6) {
            $errorDiv.text('Bitte geben Sie den vollständigen 6-stelligen Code ein.').show();
            $codeInputs.first().focus();
            return;
        }
        
        $hiddenInput.val(code);
        $errorDiv.hide();
        
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Wird verifiziert...');
        
        $.post(produkt_ajax.ajax_url, {
            action: 'produkt_verify_login_code',
            nonce: produkt_ajax.nonce,
            email: email,
            code: code,
            redirect_to: pendingCheckoutUrl
        }, function(response) {
            if (response && response.success) {
                // Erfolgreich eingeloggt - weiterleiten
                if (response.data && response.data.redirect_to) {
                    window.location.href = response.data.redirect_to;
                } else if (pendingCheckoutUrl) {
                    window.location.href = pendingCheckoutUrl;
                } else {
                    window.location.reload();
                }
            } else {
                $errorDiv.text(response && response.data && response.data.message ? response.data.message : 'Der Code ist ungültig oder abgelaufen.').show();
                $btn.prop('disabled', false).text(originalText);
                // Code-Felder leeren
                $codeInputs.val('');
                $codeInputs.first().focus();
            }
        }).fail(function() {
            $errorDiv.text('Fehler bei der Verifizierung. Bitte versuchen Sie es erneut.').show();
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    $('#checkout-back-email').on('click', function(e){
        e.preventDefault();
        $('#checkout-login-code-section').hide();
        $('#checkout-login-email-section').show();
        $('#checkout-code-error').hide();
        $('#checkout-login-code-section .code-input').val('');
        // Button-Status zurücksetzen
        const $loginBtn = $('#checkout-login-btn');
        $loginBtn.prop('disabled', false).text('Code zum einloggen anfordern');
    });

    $('#produkt-cart-checkout').on('click', function(e){
        e.preventDefault();
        if (!cart.length) return;
        saveCart();
        const cartSubtotal = getCartSubtotal();
        freeShippingActive = isFreeShippingActive(cartSubtotal);
        const shippingIdForCart = getShippingPriceIdForValue(cartSubtotal);
        updateCartShippingDisplay();
        const targetUrl = produkt_ajax.checkout_url + '?cart=1' + (shippingIdForCart ? '&shipping_price_id=' + encodeURIComponent(shippingIdForCart) : '');
        if (produkt_ajax.is_logged_in) {
            window.location.href = targetUrl;
        } else {
            pendingCheckoutUrl = targetUrl;
            showCheckoutLoginModal();
        }
    });

    $('#checkout-guest-link').on('click', function(e){
        e.preventDefault();
        
        // Prüfe ob E-Mail existiert
        if (emailExists) {
            alert('Bestellung als Gast mit der Email Adresse nicht möglich');
            return;
        }
        
        const email = $loginEmail.val().trim();
        if (!email) {
            alert('Bitte E-Mail eingeben');
            return;
        }
        if (!isValidEmail(email)) {
            alert('Bitte geben Sie eine gültige E-Mail-Adresse ein');
            return;
        }
        try {
            const cb = document.getElementById('checkout-newsletter-optin');
            localStorage.setItem('produkt_newsletter_optin', (cb && cb.checked) ? '1' : '0');
        } catch(e){}
        setStoredCheckoutEmail(email);
        $loginModal.hide();
        $('body').removeClass('produkt-popup-open');
        if (pendingCheckoutUrl) {
            const redirectUrl = appendEmailToCheckoutUrl(pendingCheckoutUrl, email);
            window.location.href = redirectUrl;
        }
    });

    $('#checkout-back-shop').on('click', function(e){
        e.preventDefault();
        $loginModal.hide();
        $('body').removeClass('produkt-popup-open');
        pendingCheckoutUrl = '';
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
    const $dropdownToggle = $('#shop-filter-dropdown-toggle');
    const $dropdown = $('#shop-filter-dropdown');

    if ($dropdownToggle.length && $dropdown.length) {
        $dropdownToggle.on('click', function() {
            const isOpen = $(this).attr('aria-expanded') === 'true';
            $(this).attr('aria-expanded', (!isOpen).toString());
            $(this).toggleClass('open', !isOpen);

            if ($dropdown.is(':visible')) {
                $dropdown.slideUp(200, function() {
                    $dropdown.attr('hidden', true);
                });
            } else {
                $dropdown.hide().removeAttr('hidden').slideDown(200);
            }
        });
    }

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

    // Code input handling for 6-digit login code
    function initCodeInputs() {
        $('.code-input-group').each(function() {
            const $group = $(this);
            const $inputs = $group.find('.code-input');
            
            // Prüfe ob bereits initialisiert (hat data-Attribut)
            if ($group.data('code-inputs-initialized')) {
                return;
            }
            
            if ($inputs.length !== 6) return;
            
            // Verstecktes Input-Feld finden (kann in Form oder direkt im Modal sein)
            let $hiddenInput = $group.closest('form').find('input[type="hidden"][name="code"]');
            if (!$hiddenInput.length) {
                // Fallback: Suche nach verstecktem Input in der Nähe (z.B. im Modal)
                $hiddenInput = $group.siblings('input[type="hidden"][name="code"]');
                if (!$hiddenInput.length) {
                    // Suche im Modal-Content oder in der Code-Sektion
                    $hiddenInput = $group.closest('.modal-content, .login-code-form, #checkout-login-code-section').find('input[type="hidden"][name="code"]');
                }
                // Speziell für das Checkout-Modal
                if (!$hiddenInput.length) {
                    $hiddenInput = $('#checkout-login-code-combined');
                }
            }
            
            // Wenn kein verstecktes Input gefunden, erstelle eines
            if (!$hiddenInput.length) {
                $hiddenInput = $('<input>', {
                    type: 'hidden',
                    name: 'code',
                    id: 'code-input-combined-' + Date.now()
                });
                $group.after($hiddenInput);
            }

            function updateCombinedCode() {
                let code = '';
                $inputs.each(function() {
                    code += $(this).val() || '';
                });
                $hiddenInput.val(code);
            }

            // Event-Handler entfernen, falls bereits vorhanden, dann neu anhängen
            $inputs.off('input.code-input-handler keydown.code-input-handler paste.code-input-handler');

            $inputs.on('input.code-input-handler', function() {
                const $this = $(this);
                let value = $this.val();
                
                // Nur Zahlen erlauben
                value = value.replace(/[^0-9]/g, '');
                if (value.length > 1) {
                    value = value.charAt(0);
                }
                $this.val(value);
                
                updateCombinedCode();
                
                // Automatisch zum nächsten Feld springen
                // Index relativ zu den Input-Feldern berechnen (ohne Separator)
                const currentIndex = $inputs.index($this);
                if (value && currentIndex < $inputs.length - 1) {
                    // Kurze Verzögerung, damit der Wert gesetzt wird
                    setTimeout(function() {
                        $inputs.eq(currentIndex + 1).focus();
                    }, 10);
                }
            });

            $inputs.on('keydown.code-input-handler', function(e) {
                const $this = $(this);
                // Index relativ zu den Input-Feldern berechnen (ohne Separator)
                const index = $inputs.index($this);
                
                // Backspace: Wenn Feld leer ist, zum vorherigen Feld springen
                if (e.key === 'Backspace' && !$this.val() && index > 0) {
                    e.preventDefault();
                    $inputs.eq(index - 1).focus().val('');
                    updateCombinedCode();
                }
                
                // Pfeiltasten für Navigation
                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    $inputs.eq(index - 1).focus();
                }
                if (e.key === 'ArrowRight' && index < $inputs.length - 1) {
                    e.preventDefault();
                    $inputs.eq(index + 1).focus();
                }
                
                // Paste-Handling
                if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                    e.preventDefault();
                    navigator.clipboard.readText().then(function(text) {
                        const digits = text.replace(/[^0-9]/g, '').substring(0, 6);
                        $inputs.each(function(i) {
                            $(this).val(digits[i] || '');
                        });
                        updateCombinedCode();
                        if (digits.length === 6) {
                            $inputs.last().focus();
                        } else if (digits.length > 0) {
                            $inputs.eq(digits.length).focus();
                        }
                    }).catch(function() {
                        // Clipboard-Zugriff fehlgeschlagen, ignorieren
                    });
                }
            });

            $inputs.on('paste.code-input-handler', function(e) {
                e.preventDefault();
            });
            
            // Markiere als initialisiert
            $group.data('code-inputs-initialized', true);

            // Form-Submit validieren (falls in einem Form)
            const $form = $group.closest('form');
            if ($form.length) {
                $form.off('submit.code-input-validation');
                $form.on('submit.code-input-validation', function(e) {
                    updateCombinedCode();
                    const code = $hiddenInput.val();
                    if (code.length !== 6) {
                        e.preventDefault();
                        alert('Bitte geben Sie den vollständigen 6-stelligen Code ein.');
                        $inputs.first().focus();
                        return false;
                    }
                });
            }

            // Fokus auf erstes Feld setzen, wenn Code-Form erscheint
            if ($group.is(':visible')) {
                setTimeout(function() {
                    $inputs.first().focus();
                }, 100);
            }
        });
    }

    // Initialisierung beim Laden und bei dynamischen Änderungen
    initCodeInputs();
    
    // Re-initialisieren, wenn das Code-Form erscheint (z.B. nach AJAX)
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).find('.code-input-group').length || $(e.target).is('.code-input-group')) {
            setTimeout(initCodeInputs, 50);
        }
    });
    
    // MutationObserver für moderne Browser
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let shouldInit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.nodeType === 1 && ($(node).find('.code-input-group').length || $(node).is('.code-input-group'))) {
                            shouldInit = true;
                            break;
                        }
                    }
                }
            });
            if (shouldInit) {
                setTimeout(initCodeInputs, 50);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});
