jQuery(document).ready(function($) {
    // Sidebar öffnen bei Klick auf "Details ansehen"
    $(document).on('click', '.view-details-link', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        var sidebar = $('#order-details-sidebar');
        var overlay = $('#order-details-overlay');
        var content = sidebar.find('.order-details-content');

        content.html('<p>Lade Details…</p>');
        sidebar.addClass('visible');
        overlay.addClass('visible');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'pv_load_order_sidebar_details',
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                    // Re-initialize accordions for dynamically loaded content
                    produktInitAccordions();
                } else {
                    content.html('<p>Details konnten nicht geladen werden.</p>');
                }
            },
            error: function() {
                content.html('<p>Ein Fehler ist aufgetreten.</p>');
            }
        });
    });

    $('.close-sidebar, #order-details-overlay').on('click', function() {
        $('#order-details-sidebar').removeClass('visible');
        $('#order-details-overlay').removeClass('visible');
    });

    $(document).on('click', '.customer-delete-btn', function() {
        var btn = $(this);
        var card = btn.closest('.customer-card');
        var customerId = btn.data('customer-id');
        if (!customerId) {
            return;
        }
        if (!confirm('Möchtest du diesen Kunden wirklich löschen?')) {
            return;
        }
        btn.prop('disabled', true);
        $.ajax({
            url: (typeof produkt_admin !== 'undefined' ? produkt_admin.ajax_url : ajaxurl),
            method: 'POST',
            data: {
                action: 'pv_delete_customer',
                nonce: typeof produkt_admin !== 'undefined' ? produkt_admin.nonce : '',
                customer_id: customerId
            },
            success: function(response) {
                if (response && response.success) {
                    card.fadeOut(200, function() {
                        $(this).remove();
                    });
                } else {
                    btn.prop('disabled', false);
                    alert((response && response.data && response.data.message) ? response.data.message : 'Kunde konnte nicht gelöscht werden.');
                }
            },
            error: function() {
                btn.prop('disabled', false);
                alert('Fehler beim Löschen des Kunden.');
            }
        });
    });

    // Confirm delete actions
    $('.wp-list-table a[href*="delete"]').on('click', function(e) {
        if (!confirm('Bist du sicher das du Löschen möchtest?')) {
            e.preventDefault();
        }
    });

    // Auto-format price inputs
    $('input[name="base_price"], input[name="price"], input[name="price_from"], input[name="sale_price"], input[name="mietpreis_monatlich"], input[name="verkaufspreis_einmalig"]').on('blur', function() {
        var value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // Auto-format discount percentage
    $('input[name="discount"]').on('blur', function() {
        var value = parseFloat($(this).val());
        if (!isNaN(value)) {
            if (value > 100) {
                $(this).val('100.00');
            } else if (value < 0) {
                $(this).val('0.00');
            } else {
                $(this).val(value.toFixed(2));
            }
        }
    });

    // Simple URL validation for image fields
    $('input[name="image_url"], input[name="default_image"]').on('blur', function() {
        var input = $(this);
        var url = input.val().trim();

        if (url && !isValidImageUrl(url)) {
            input.css('border-color', '#dc3232');
            if (!input.next('.url-error').length) {
                input.after('<p class="url-error" style="color: #dc3232; font-size: 12px; margin: 5px 0 0 0;">⚠️ Bitte geben Sie eine gültige Bild-URL ein (jpg, png, gif, webp)</p>');
            }
        } else {
            input.css('border-color', '');
            input.next('.url-error').remove();
        }
    });

    function isValidImageUrl(url) {
        try {
            new URL(url);
            return /\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i.test(url);
        } catch (e) {
            return false;
        }
    }

    $('form').on('submit', function(e) {
        var hasErrors = false;
        $(this).find('input[name="image_url"], input[name="default_image"]').each(function() {
            var url = $(this).val().trim();
            if (url && !isValidImageUrl(url)) {
                hasErrors = true;
                $(this).css('border-color', '#dc3232');
            }
        });

        if (hasErrors) {
            e.preventDefault();
            alert('Bitte korrigieren Sie die fehlerhaften Bild-URLs bevor Sie fortfahren.');
        }
    });

    $('input[type="color"]').each(function() {
        var swatch = $(this).siblings('.produkt-color-swatch');
        if (swatch.length) {
            swatch.css('background-color', $(this).val());
        }
    }).on('input change', function() {
        var swatch = $(this).siblings('.produkt-color-swatch');
        if (swatch.length) {
            swatch.css('background-color', $(this).val());
        }
    });

    $('.produkt-color-picker').each(function() {
        var container = $(this);
        var preview = container.find('.produkt-color-preview-circle');
        var colorInput = container.find('.produkt-color-input');
        var textInput = container.find('.produkt-color-value');

        preview.on('click', function() {
            colorInput.trigger('click');
        });

        colorInput.on('input change', function() {
            var val = $(this).val();
            preview.css('background-color', val);
            textInput.val(val);
        });

        textInput.on('input change', function() {
            var val = $(this).val();
            if (/^#([A-Fa-f0-9]{6})$/.test(val)) {
                preview.css('background-color', val);
                colorInput.val(val);
            }
        });
    });

    function toggleRatingFields() {
        var checked = $('input[name="show_rating"]').is(':checked');
        $('input[name="rating_value"]').prop('required', checked).prop('disabled', !checked);
        $('input[name="rating_link"]').prop('disabled', !checked).prop('required', false);
        if (!checked) {
            $('input[name="rating_value"], input[name="rating_link"]').val('');
        }
    }
    $('input[name="show_rating"]').on('change', toggleRatingFields);
    toggleRatingFields();

    var accordionIndex = $('#accordion-container .produkt-accordion-group').length;
    $('#add-accordion').on('click', function(e) {
        e.preventDefault();
        var id = 'accordion_content_' + accordionIndex;
        var html = '<div class="produkt-accordion-group removable-block">' +
            '<button type="button" class="icon-btn icon-btn-remove produkt-remove-accordion" aria-label="Accordion entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg></button>' +
            '<div class="produkt-form-row">' +
            '<div class="produkt-form-group" style="flex:1;">' +
            '<label>Titel</label>' +
            '<input type="text" name="accordion_titles[]" />' +
            '</div>' +
            '</div>' +
            '<div class="produkt-form-group">' +
            '<textarea id="' + id + '" name="accordion_contents[]" rows="3"></textarea>' +
            '</div>' +
            '</div>';
        $('#accordion-container').append(html);
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            wp.editor.initialize(id, { tinymce: true, quicktags: true });
        }
        accordionIndex++;
    });

    $('#accordion-container').on('click', '.produkt-remove-accordion', function(e) {
        e.preventDefault();
        var group = $(this).closest('.produkt-accordion-group');
        if (group.length) {
            group.remove();
        }
    });

    var catSelect = $('#category-select');
    if (catSelect.length) {
        var savedCat = localStorage.getItem('produkt_last_category');
        if (savedCat && catSelect.val() !== savedCat && window.location.search.indexOf('category=') === -1) {
            catSelect.val(savedCat);
            if (catSelect.val() === savedCat) {
                catSelect.closest('form').submit();
            }
        }
        catSelect.on('change', function() {
            localStorage.setItem('produkt_last_category', $(this).val());
        });
    }

    var catModal = $('#category-modal');
    if (catModal.length) {
        var nameInput = catModal.find('input[name="name"]');
        var slugInput = catModal.find('input[name="slug"]');
        var slugTouched = false;

        function produktSlugify(str){
            return str.toString().toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
                .replace(/[^a-z0-9]+/g,'-')
                .replace(/^-+|-+$/g,'');
        }

        nameInput.on('input', function(){
            if(!slugTouched){
                slugInput.val(produktSlugify($(this).val()));
            }
        });
        slugInput.on('input', function(){
            slugTouched = true;
        });

        function openCatModal() {
            slugTouched = false;
            catModal.show();
            $('body').addClass('category-modal-open');
        }
        function closeCatModal() {
            catModal.hide();
            $('body').removeClass('category-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }
        $('#add-category-btn').on('click', function(e){
            e.preventDefault();
            catModal.find('input[name="category_id"]').val('');
            catModal.find('input[type="text"], textarea').val('');
            catModal.find('select[name="parent_id"]').val('0');
            openCatModal();
        });
        catModal.on('click', function(e){
            if(e.target === this){
                closeCatModal();
            }
        });
        catModal.find('.modal-close').on('click', closeCatModal);
        if (catModal.data('open') == 1) {
            openCatModal();
        }
    }

    var layoutModal = $('#layout-modal');
    if (layoutModal.length) {
        function openLayoutModal() {
            layoutModal.show();
            $('body').addClass('layout-modal-open');
        }
        function closeLayoutModal() {
            layoutModal.hide();
            $('body').removeClass('layout-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('edit_layout');
            history.replaceState(null, '', url);
        }

        // layout type selection
        var typeGrid = layoutModal.find('.layout-option-grid');
        if (typeGrid.length) {
            var typeInput = layoutModal.find('input[name="layout_type"]');
            function setLayoutActive(val) {
                typeGrid.find('.layout-option-card').each(function(){
                    $(this).toggleClass('active', $(this).data('value') == val);
                });
            }
            typeGrid.on('click', '.layout-option-card', function(){
                var val = $(this).data('value');
                typeInput.val(val);
                setLayoutActive(val);
            });
            setLayoutActive(typeInput.val());
        }

        // image selector
        layoutModal.on('click', '.image-select', function(e){
            e.preventDefault();
            var row = $(this).closest('.layout-cat-row');
            var preview = row.find('.image-preview');
            var input = row.find('input[name="cat_image[]"]');
            var frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                preview.css('background-image', 'url(' + att.url + ')');
                input.val(att.url);
            });
            frame.open();
        });
        layoutModal.on('click', '.image-remove', function(e){
            e.preventDefault();
            var row = $(this).closest('.layout-cat-row');
            row.find('.image-preview').css('background-image', '');
            row.find('input[name="cat_image[]"]').val('');
        });

        $('#add-layout-btn').on('click', function(e){
            e.preventDefault();
            layoutModal.find('input[name="layout_id"]').val('');
            layoutModal.find('input[name="layout_shortcode"]').val('');
            layoutModal.find('input[name="layout_name"]').val('');
            layoutModal.find('select').val('');
            layoutModal.find('input[name="layout_type"]').val('1');
            typeGrid.find('.layout-option-card').removeClass('active');
            typeGrid.find('.layout-option-card[data-value="1"]').addClass('active');
            layoutModal.find('.produkt-color-value').val('#ffffff');
            layoutModal.find('.produkt-color-input').val('#ffffff');
            layoutModal.find('.produkt-color-preview-circle').css('background-color','#ffffff');
            layoutModal.find('input[name="cat_image[]"]').val('');
            layoutModal.find('.image-preview').css('background-image','');
            layoutModal.find('input[name="border_radius"]').prop('checked', false);
            openLayoutModal();
        });
        layoutModal.on('click', function(e){
            if (e.target === this) {
                closeLayoutModal();
            }
        });
        layoutModal.find('.modal-close').on('click', closeLayoutModal);
        if (layoutModal.data('open') == 1) {
            openLayoutModal();
        }
    }

    var blockModal = $('#block-modal');
    if (blockModal.length) {
        function openBlockModal() {
            blockModal.show();
            $('body').addClass('block-modal-open');
        }
        function closeBlockModal() {
            blockModal.hide();
            $('body').removeClass('block-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }
        $('#add-block-btn').on('click', function(e){
            e.preventDefault();
            blockModal.find('input[name="id"]').val('');
            blockModal.find('input[type="text"], input[type="number"], input[type="url"], textarea').val('');
            blockModal.find('input[type="color"]').val('#ffffff');
            blockModal.find('select[name="style"]').val('wide');
            blockModal.find('select[name="category_id"]').val('0');
            openBlockModal();
        });
        blockModal.on('click', function(e){
            if (e.target === this) {
                closeBlockModal();
            }
        });
        blockModal.find('.modal-close').on('click', closeBlockModal);
        if (blockModal.data('open') == 1) {
            openBlockModal();
        }
    }

    var shipModal = $('#shipping-modal');
    if (shipModal.length) {
        function openShipModal() {
            shipModal.show();
            $('body').addClass('shipping-modal-open');
        }
        function closeShipModal() {
            shipModal.hide();
            $('body').removeClass('shipping-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }
        $('#add-shipping-btn').on('click', function(e){
            e.preventDefault();
            shipModal.find('input[name="shipping_id"]').val('');
            shipModal.find('input[type="text"], input[type="number"], textarea').val('');
            shipModal.find('select[name="shipping_provider"]').val('none');
            openShipModal();
        });
        shipModal.on('click', function(e){
            if (e.target === this) {
                closeShipModal();
            }
        });
        shipModal.find('.modal-close').on('click', closeShipModal);
    if (shipModal.data('open') == 1) {
        openShipModal();
    }
}

    var durationModal = $('#duration-modal');
    if (durationModal.length) {
        var durationForm = durationModal.find('form');
        var durationTitle = durationModal.find('[data-duration-modal-title]');
        var deleteWrapper = durationModal.find('[data-delete-wrapper]');
        var defaultStart = durationForm.data('default-gradient-start') || '#ff8a3d';
        var defaultEnd = durationForm.data('default-gradient-end') || '#ff5b0f';
        var defaultText = durationForm.data('default-text-color') || '#ffffff';
        var popularToggle = durationForm.find('#show_popular');

        function updateDurationModalState() {
            var hasId = $.trim(durationForm.find('input[name="id"]').val()).length > 0;
            var titleText = hasId ? durationTitle.data('title-edit') : durationTitle.data('title-add');
            if (titleText) {
                durationTitle.text(titleText);
            }
            if (deleteWrapper.length) {
                deleteWrapper.toggle(hasId);
            }
        }

        function openDurationModal() {
            durationModal.show();
            $('body').addClass('duration-modal-open');
            updateDurationModalState();
        }

        function closeDurationModal() {
            durationModal.hide();
            $('body').removeClass('duration-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('tab');
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }

        $(document).on('click', '.js-open-duration-modal', function(e){
            e.preventDefault();
            durationForm.find('input[name="id"]').val('');
            durationForm.find('input[name="name"]').val('');
            durationForm.find('input[name="months_minimum"]').val('');
            durationForm.find('#show_badge, #show_popular').prop('checked', false);
            durationForm.find('input[name="sort_order"]').val(0);
            durationForm.find('input[name^="variant_custom_price"]').val('');
            if (popularToggle.length) {
                popularToggle.trigger('change');
            }

            var startInput = durationForm.find('[data-popular-start]');
            var endInput = durationForm.find('[data-popular-end]');
            var textInput = durationForm.find('[data-popular-text]');

            startInput.val(defaultStart).trigger('input');
            endInput.val(defaultEnd).trigger('input');
            textInput.val(defaultText).trigger('input');

            updateDurationModalState();
            openDurationModal();
        });

        durationModal.on('click', function(e){
            if (e.target === this) {
                closeDurationModal();
            }
        });
        durationModal.find('.modal-close').on('click', closeDurationModal);

        if (durationModal.data('open') == 1) {
            openDurationModal();
        }
    }

    var conditionModal = $('#condition-modal');
    if (conditionModal.length) {
        var conditionForm = conditionModal.find('form');
        var conditionTitle = conditionModal.find('[data-condition-modal-title]');

        function updateConditionModalState() {
            var hasId = $.trim(conditionForm.find('input[name="id"]').val()).length > 0;
            var titleText = hasId ? conditionTitle.data('title-edit') : conditionTitle.data('title-add');
            if (titleText) {
                conditionTitle.text(titleText);
            }
        }

        function openConditionModal() {
            conditionModal.show();
            $('body').addClass('conditions-modal-open');
            updateConditionModalState();
        }

        function closeConditionModal() {
            conditionModal.hide();
            $('body').removeClass('conditions-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('tab');
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }

        $(document).on('click', '.js-open-condition-modal', function (e) {
            e.preventDefault();
            conditionForm.find('input[name="id"]').val('');
            conditionForm.find('input[name="name"]').val('');
            conditionForm.find('textarea[name="description"]').val('');
            conditionForm.find('input[name="price_modifier"]').val('0');
            conditionForm.find('input[name="sort_order"]').val('0');
            conditionForm.find('.variant-availability-grid input[type="checkbox"]').prop('checked', true);
            openConditionModal();
        });

        conditionModal.on('click', function (e) {
            if (e.target === this) {
                closeConditionModal();
            }
        });

        conditionModal.find('.modal-close').on('click', closeConditionModal);

        if (conditionModal.data('open') == 1) {
            openConditionModal();
        }
    }

    var colorModal = $('#color-modal');
    if (colorModal.length) {
        function openColorModal() {
            colorModal.show();
            $('body').addClass('color-modal-open');
        }
        function closeColorModal() {
            colorModal.hide();
            $('body').removeClass('color-modal-open');
            var url = new URL(window.location);
            url.searchParams.delete('edit');
            url.searchParams.delete('tab');
            history.replaceState(null, '', url);
        }

        function toggleColorCodeField() {
            var isMulticolor = colorModal.find('#color-multicolor').is(':checked');
            var codeGroup = colorModal.find('.produkt-color-code-group');
            var preview = colorModal.find('.produkt-color-preview-circle');

            if (isMulticolor) {
                codeGroup.hide();
                preview.addClass('produkt-color-preview-circle--multicolor');
            } else {
                codeGroup.show();
                preview.removeClass('produkt-color-preview-circle--multicolor');
                preview.css('background-color', colorModal.find('.produkt-color-input').val());
            }
        }
        $(document).on('click', '#add-color-btn', function(e){
            e.preventDefault();
            colorModal.find('input[name="id"]').val('');
            colorModal.find('input[type="text"], input[type="color"], input[type="hidden"]').not('[name="category_id"], [name="produkt_admin_nonce"]').val('');
            colorModal.find('.image-preview').css('background-image','');
            colorModal.find('.variant-availability-grid input[type="checkbox"]').prop('checked', false);
            colorModal.find('.produkt-color-preview-circle').css('background-color','#ffffff');
            colorModal.find('.produkt-color-input').val('#ffffff');
            colorModal.find('.produkt-color-value').val('#ffffff');
            colorModal.find('#color-multicolor').prop('checked', false);
            toggleColorCodeField();
            openColorModal();
        });
        colorModal.on('click', function(e){ if (e.target === this) { closeColorModal(); } });
        colorModal.find('.modal-close').on('click', closeColorModal);
        colorModal.on('click', '.image-select', function(e){
            e.preventDefault();
            var preview = colorModal.find('.image-preview');
            var input = colorModal.find('input[name="image_url"]');
            var frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                preview.css('background-image','url('+att.url+')');
                input.val(att.url);
            });
            frame.open();
        });
        colorModal.on('click', '.image-remove', function(e){
            e.preventDefault();
            colorModal.find('.image-preview').css('background-image','');
            colorModal.find('input[name="image_url"]').val('');
        });
        colorModal.on('change', '#color-multicolor', toggleColorCodeField);

        toggleColorCodeField();
        if (colorModal.data('open') == 1) {
            openColorModal();
        }
    }

    $('.default-shipping-checkbox').on('change', function(){
        var $this = $(this);
        var isChecked = $this.is(':checked');
        if (isChecked) {
            $('.default-shipping-checkbox').not($this).prop('checked', false);
        }

        $.post(produkt_admin.ajax_url, {
            action: 'pv_set_default_shipping',
            nonce: produkt_admin.nonce,
            id: isChecked ? $this.data('id') : 0
        }).fail(function(){
            if (isChecked) {
                $this.prop('checked', false);
            } else {
                $this.prop('checked', true);
            }
        });
    });

    var dayCard = $('#day-orders-card');
    if (dayCard.length) {
        var body = $('#day-orders-body');
        $('.calendar-big-grid .day-cell').on('click', function(){
            var date = $(this).data('date');
            var blocked = $(this).data('blocked') == 1;
            var orders = [];
            try { orders = JSON.parse($(this).attr('data-orders')); } catch (e) {}
            $('#day-orders-date').text(date);
            body.empty();
            if (orders.length) {
                orders.forEach(function(o){
                    var tr = $('<tr>');
                    tr.append('<td>#'+o.num+'</td>');
                    tr.append('<td>'+o.name+'</td>');
                    tr.append('<td>'+o.product+'</td>');
                    tr.append('<td>'+o.variant+'</td>');
                    tr.append('<td>'+(o.extras || '-')+'</td>');
                    tr.append('<td>'+o.action+'</td>');
                    var btn = $('<button type="button" class="icon-btn icon-btn-no-stroke view-details-link" aria-label="Details"></button>');
                    btn.data('order-id', o.id);
                    btn.append('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1"><path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/></svg>');
                    tr.append($('<td>').append(btn));
                    body.append(tr);
                });
            } else {
                body.append('<tr><td colspan="7">Keine Bestellungen</td></tr>');
            }
            $('#block-day').toggle(!blocked).data('date', date).data('cell', this);
            $('#unblock-day').toggle(blocked).data('date', date).data('cell', this);
            dayCard.show();
        });
        $('#block-day').on('click', function(){
            var date = $(this).data('date');
            var cell = $(this).data('cell');
            $.post(produkt_admin.ajax_url, {action:'produkt_block_day', nonce: produkt_calendar_nonce, date: date}, function(res){
                if (res && res.success) {
                    $(cell).addClass('blocked').data('blocked',1);
                }
            });
        });
        $('#unblock-day').on('click', function(){
            var date = $(this).data('date');
            var cell = $(this).data('cell');
            $.post(produkt_admin.ajax_url, {action:'produkt_unblock_day', nonce: produkt_calendar_nonce, date: date}, function(res){
                if (res && res.success) {
                    $(cell).removeClass('blocked').data('blocked',0);
                }
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', produktInitAccordions);
if (document.readyState !== 'loading') {
    produktInitAccordions();
}

function produktInitAccordions() {
    const headers = document.querySelectorAll('.produkt-accordion-header');
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.produkt-accordion-item');
            const content = item.querySelector('.produkt-accordion-content');
            if (item.classList.contains('active')) {
                item.classList.remove('active');
                content.style.maxHeight = null;
            } else {
                document.querySelectorAll('.produkt-accordion-item.active').forEach(open => {
                    open.classList.remove('active');
                    const c = open.querySelector('.produkt-accordion-content');
                    if (c) c.style.maxHeight = null;
                });
                item.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });
    document.querySelectorAll('.produkt-accordion-item.active .produkt-accordion-content').forEach(c => {
        c.style.maxHeight = c.scrollHeight + 'px';
    });
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('produkt-return-confirm')) {
        e.preventDefault();
        var btn = e.target;
        var id = btn.getAttribute('data-id');
        var data = new URLSearchParams();
        data.append('action', 'confirm_return');
        data.append('nonce', produkt_admin.nonce);
        data.append('order_id', id);
        fetch(produkt_admin.ajax_url, {method: 'POST', body: data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    var item = btn.closest('.produkt-return-item');
                    if (item) {
                        item.remove();
                    } else {
                        btn.remove();
                    }
                } else {
                    alert('Fehler beim Bestätigen');
                }
            });
    }
});

// Category accordion selection
document.addEventListener('DOMContentLoaded', function() {
    const tiles = document.querySelectorAll('.category-accordion .category-tile');
    const selectedContainer = document.getElementById('selected-categories');
    if (!tiles.length || !selectedContainer) return;
    tiles.forEach(tile => {
        tile.addEventListener('click', function() {
            const id = this.dataset.id;
            const parent = this.dataset.parent;
            this.classList.toggle('selected');
            if (this.classList.contains('selected')) {
                addInput(id);
                if (parent && parent !== '0') {
                    addInput(parent);
                }
            } else {
                removeInput(id);
                if (parent && parent !== '0' && !document.querySelector('.category-tile.selected[data-parent="' + parent + '"]')) {
                    removeInput(parent);
                }
            }
        });
    });
    function addInput(id) {
        if (!selectedContainer.querySelector('input[value="' + id + '"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_categories[]';
            input.value = id;
            selectedContainer.appendChild(input);
        }
    }
    function removeInput(id) {
        const input = selectedContainer.querySelector('input[value="' + id + '"]');
        if (input) input.remove();
    }
});

function produktSidebarOrderId() {
    var orderIdEl = document.querySelector('.sidebar-wrapper');
    return orderIdEl ? orderIdEl.getAttribute('data-order-id') : '';
}

function produktSetTrackingStatus(message, isError) {
    var statusEl = document.querySelector('.tracking-status');
    if (!statusEl) return;
    statusEl.textContent = message || '';
    statusEl.style.color = isError ? '#b63b3b' : '#1d5c1d';
}

function produktHandleTrackingAction(options) {
    var wrapper = document.querySelector('.tracking-accordion');
    if (!wrapper) return;

    var input = wrapper.querySelector('.tracking-number-input');
    var providerSelect = wrapper.querySelector('.tracking-provider-select');
    var orderId = produktSidebarOrderId();
    if (!orderId) return;

    var trackingValue = input ? input.value.trim() : '';
    var providerValue = providerSelect ? providerSelect.value : '';
    if (options.sendEmail && !options.clear && !trackingValue) {
        produktSetTrackingStatus('Bitte eine Trackingnummer eintragen.', true);
        return;
    }

    var data = new URLSearchParams();
    data.append('action', 'pv_save_tracking_number');
    data.append('nonce', produkt_admin.nonce);
    data.append('order_id', orderId);
    if (trackingValue) {
        data.append('tracking_number', trackingValue);
    }
    if (providerValue) {
        data.append('shipping_provider', providerValue);
    }
    if (options.sendEmail) {
        data.append('send_email', '1');
    }
    if (options.clear) {
        data.append('clear_tracking', '1');
    }

    var trigger = options.trigger;
    if (trigger) {
        trigger.disabled = true;
    }
    produktSetTrackingStatus('Wird gespeichert …', false);

    fetch(produkt_admin.ajax_url, {method: 'POST', body: data})
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.success) {
                var newValue = res.data.tracking_number || '';
                var newProvider = res.data.shipping_provider || '';
                if (input) {
                    input.value = newValue;
                }
                if (providerSelect) {
                    providerSelect.value = newProvider;
                }
                produktSetTrackingStatus(res.data.message || 'Tracking gespeichert.', false);
            } else {
                produktSetTrackingStatus('Fehler beim Speichern des Trackings.', true);
            }
        })
        .catch(function() {
            produktSetTrackingStatus('Fehler beim Speichern des Trackings.', true);
        })
        .finally(function() {
            if (trigger) {
                trigger.disabled = false;
            }
        });
}

// Order note functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.note-icon')) {
        e.preventDefault();
        var form = document.getElementById('order-note-form');
        if (form) {
            form.classList.toggle('visible');
            var ta = form.querySelector('textarea');
            if (ta) ta.focus();
        }
    }
    if (e.target.classList.contains('note-cancel')) {
        e.preventDefault();
        document.getElementById('order-note-form').classList.remove('visible');
    }
    if (e.target.classList.contains('note-save')) {
        e.preventDefault();
        var form = document.getElementById('order-note-form');
        var note = form.querySelector('textarea').value.trim();
        if (!note) return;
        var orderIdEl = document.querySelector('.sidebar-wrapper');
        var orderId = orderIdEl ? orderIdEl.getAttribute('data-order-id') : 0;
        var data = new URLSearchParams();
        data.append('action', 'pv_save_order_note');
        data.append('nonce', produkt_admin.nonce);
        data.append('order_id', orderId);
        data.append('note', note);
        fetch(produkt_admin.ajax_url, {method:'POST', body:data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    var container = document.querySelector('.order-notes-section');
                    if (container) {
                        var div = document.createElement('div');
                        div.className = 'order-note';
                        div.setAttribute('data-note-id', res.data.id);
                        div.innerHTML = '<div class="note-text"></div><div class="note-date"></div><button type="button" class="icon-btn note-delete-btn" title="Notiz löschen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg></button>';
                        div.querySelector('.note-text').textContent = note;
                        div.querySelector('.note-date').textContent = res.data.date;
                        container.prepend(div);
                    }
                    form.querySelector('textarea').value = '';
                    form.classList.remove('visible');
                } else {
                    alert('Fehler beim Speichern');
                }
            });
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.note-delete-btn')) {
        e.preventDefault();
        if (!confirm('Bist du sicher das du Löschen möchtest?')) {
            return;
        }
        var btn = e.target.closest('.note-delete-btn');
        var noteEl = btn.closest('.order-note');
        if (!noteEl) return;
        var noteId = noteEl.getAttribute('data-note-id');
        var data = new URLSearchParams();
        data.append('action', 'pv_delete_order_note');
        data.append('nonce', produkt_admin.nonce);
        data.append('note_id', noteId);
        fetch(produkt_admin.ajax_url, {method:'POST', body:data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    noteEl.remove();
                } else {
                    alert('Fehler beim Löschen');
                }
            });
    }
});

document.addEventListener('click', function(e) {
    var sendBtn = e.target.closest('.tracking-send-btn');
    var saveBtn = e.target.closest('.tracking-save-btn');
    var clearBtn = e.target.closest('.tracking-clear-btn');

    if (sendBtn) {
        e.preventDefault();
        produktHandleTrackingAction({sendEmail: true, clear: false, trigger: sendBtn});
    }

    if (saveBtn) {
        e.preventDefault();
        produktHandleTrackingAction({sendEmail: false, clear: false, trigger: saveBtn});
    }

    if (clearBtn) {
        e.preventDefault();
        if (confirm('Trackingnummer wirklich entfernen?')) {
            produktHandleTrackingAction({sendEmail: false, clear: true, trigger: clearBtn});
        }
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.customer-note-icon')) {
        var form = document.getElementById('customer-note-form');
        if (form) form.classList.add('visible');
    }
    if (e.target.classList.contains('customer-note-cancel')) {
        var form = document.getElementById('customer-note-form');
        if (form) form.classList.remove('visible');
    }
    if (e.target.classList.contains('customer-note-save')) {
        var form = document.getElementById('customer-note-form');
        var note = form.querySelector('textarea').value.trim();
        if (!note) return;
        var customerId = form.getAttribute('data-customer-id');
        var data = new URLSearchParams();
        data.append('action', 'pv_save_customer_note');
        data.append('nonce', produkt_admin.nonce);
        data.append('customer_id', customerId);
        data.append('note', note);
        fetch(produkt_admin.ajax_url, {method:'POST', body:data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    var container = document.querySelector('.customer-notes-section');
                    if (container) {
                        var div = document.createElement('div');
                        div.className = 'order-note';
                        div.setAttribute('data-note-id', res.data.id);
                        div.innerHTML = '<div class="note-text"></div><div class="note-date"></div><button type="button" class="icon-btn customer-note-delete-btn" title="Notiz löschen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg></button>';
                        div.querySelector('.note-text').textContent = note;
                        div.querySelector('.note-date').textContent = res.data.date;
                        container.prepend(div);
                    }
                    form.querySelector('textarea').value = '';
                    form.classList.remove('visible');
                } else {
                    alert('Fehler beim Speichern');
                }
            });
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.customer-note-delete-btn')) {
        e.preventDefault();
        if (!confirm('Bist du sicher das du Löschen möchtest?')) {
            return;
        }
        var btn = e.target.closest('.customer-note-delete-btn');
        var noteEl = btn.closest('.order-note');
        if (!noteEl) return;
        var noteId = noteEl.getAttribute('data-note-id');
        var data = new URLSearchParams();
        data.append('action', 'pv_delete_customer_note');
        data.append('nonce', produkt_admin.nonce);
        data.append('note_id', noteId);
        fetch(produkt_admin.ajax_url, {method:'POST', body:data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    noteEl.remove();
                } else {
                    alert('Fehler beim Löschen');
                }
            });
    }
});

document.addEventListener('click', function(e) {
    var btn = e.target.closest('.customer-log-load-more');
    if (btn) {
        e.preventDefault();
        var offset = parseInt(btn.getAttribute('data-offset')) || 0;
        var total = parseInt(btn.getAttribute('data-total')) || 0;
        var orderIds = btn.getAttribute('data-order-ids').split(',');
        var initials = btn.getAttribute('data-initials') || '';
        var data = new URLSearchParams();
        data.append('action', 'pv_load_customer_logs');
        data.append('nonce', produkt_admin.nonce);
        data.append('offset', offset);
        data.append('initials', initials);
        orderIds.forEach(function(id){ data.append('order_ids[]', id); });
        fetch(produkt_admin.ajax_url, {method:'POST', body:data})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    var list = document.querySelector('.order-log-list');
                    if (list && res.data.html) {
                        var temp = document.createElement('div');
                        temp.innerHTML = res.data.html;
                        Array.from(temp.children).forEach(function(li){ list.appendChild(li); });
                        offset += res.data.count;
                        btn.setAttribute('data-offset', offset);
                        if (offset >= total || res.data.count < 5) {
                            btn.remove();
                        }
                    }
                }
            });
    }
});

// Price card interactions
document.addEventListener('DOMContentLoaded', function() {
    const locale = 'de-DE';
    function centsToDisplay(cents) {
        if (isNaN(cents)) return '';
        return (cents / 100).toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function displayToCents(str) {
        if (!str) return null;
        const cleaned = String(str).replace(/[^\d,.-]/g, '').trim().replace(/\./g, '').replace(',', '.');
        const value = parseFloat(cleaned);
        if (isNaN(value)) return null;
        return Math.round(value * 100);
    }
    document.querySelectorAll('.price-card').forEach(card => {
        const input = card.querySelector('.price-input');
        const hidden = card.querySelector('.price-hidden');
        const steps = card.querySelectorAll('.price-btn');
        const chips = card.querySelectorAll('.price-chip');
        if (hidden.value) {
            const init = parseInt(hidden.value, 10);
            if (!isNaN(init)) input.value = centsToDisplay(init);
        }
        input.addEventListener('blur', () => {
            const cents = displayToCents(input.value);
            if (cents === null) { input.value = ''; hidden.value = ''; return; }
            input.value = centsToDisplay(cents);
            hidden.value = String(cents);
        });
        input.addEventListener('input', () => {
            const cents = displayToCents(input.value);
            hidden.value = cents === null ? '' : String(cents);
        });
        steps.forEach(btn => {
            btn.addEventListener('click', () => {
                const step = parseInt(btn.dataset.step, 10) || 0;
                const currentCents = displayToCents(input.value) ?? 0;
                const newCents = Math.max(0, currentCents + (step * 100));
                input.value = centsToDisplay(newCents);
                hidden.value = String(newCents);
            });
        });
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const cents = parseInt(chip.dataset.value, 10);
                input.value = centsToDisplay(cents);
                hidden.value = String(cents);
            });
        });
    });

    document.querySelectorAll('.layout-option-grid').forEach(grid => {
        const card = grid.closest('.dashboard-card');
        const inputName = grid.dataset.inputName || 'layout_style';
        let hidden = card ? card.querySelector(`input[name="${inputName}"]`) : null;
        if (!hidden && card) {
            hidden = card.querySelector('input[type="hidden"]');
        }
        function setActive(val) {
            grid.querySelectorAll('.layout-option-card').forEach(card => {
                card.classList.toggle('active', card.dataset.value === val);
            });
        }
        grid.querySelectorAll('.layout-option-card').forEach(card => {
            card.addEventListener('click', () => {
                const val = card.dataset.value;
                if (hidden) hidden.value = val;
                setActive(val);
            });
        });
        if (hidden) {
            setActive(hidden.value || 'default');
        }
    });

    // Load orders incrementally
    const orderRows = document.querySelectorAll('.activity-table tbody tr');
    const loadMoreBtn = document.getElementById('orders-load-more');
    if (orderRows.length > 10 && loadMoreBtn) {
        let visible = 10;
        loadMoreBtn.style.display = '';
        const updateOrders = () => {
            orderRows.forEach((row, idx) => {
                row.style.display = idx < visible ? '' : 'none';
            });
            if (visible >= orderRows.length) {
                loadMoreBtn.style.display = 'none';
            }
        };
        loadMoreBtn.addEventListener('click', () => {
            visible += 10;
            updateOrders();
        });
        updateOrders();
    }

    // Auto-generate slug from category name
    const catForm = document.getElementById('produkt-category-form');
    if (catForm) {
        const nameInput = catForm.querySelector('input[name="name"]');
        const slugInput = catForm.querySelector('input[name="slug"]');
        if (nameInput && slugInput) {
            let slugEdited = false;
            slugInput.addEventListener('input', () => { slugEdited = true; });
            nameInput.addEventListener('input', () => {
                if (slugEdited) return;
                slugInput.value = nameInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            });
        }
    }
});

