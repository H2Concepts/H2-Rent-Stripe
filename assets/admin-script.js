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

    // Confirm delete actions
    $('.wp-list-table a[href*="delete"]').on('click', function(e) {
        if (!confirm('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?')) {
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

    function initBlockClone(containerSel, addBtnSel, removeClass) {
        var container = $(containerSel);
        var addBtn = $(addBtnSel);
        if (!container.length || !addBtn.length) return;
        addBtn.on('click', function(e){
            e.preventDefault();
            var tmpl = container.find('.produkt-page-block').first().clone();
            tmpl.find('input,textarea').val('');
            container.append(tmpl);
        });
        container.on('click','.'+removeClass, function(e){
            e.preventDefault();
            $(this).closest('.produkt-page-block').remove();
        });
    }

    initBlockClone('#page-blocks-container', '#add-page-block', 'produkt-remove-page-block');
    initBlockClone('#details-blocks-container', '#add-detail-block', 'produkt-remove-detail-block');
    initBlockClone('#tech-blocks-container', '#add-tech-block', 'produkt-remove-tech-block');
    initBlockClone('#scope-blocks-container', '#add-scope-block', 'produkt-remove-scope-block');

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

    var accordionIndex = $('#accordion-container .produkt-accordion-group').length;
    $('#add-accordion').on('click', function(e) {
        e.preventDefault();
        var id = 'accordion_content_' + accordionIndex;
        var html = '<div class="produkt-accordion-group">' +
            '<div class="produkt-form-row">' +
            '<div class="produkt-form-group" style="flex:1;">' +
            '<label>Titel</label>' +
            '<input type="text" name="accordion_titles[]" />' +
            '</div>' +
            '<button type="button" class="button produkt-remove-accordion">-</button>' +
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
        function openCatModal() {
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

    $('.default-shipping-checkbox').on('change', function(){
        var $this = $(this);
        if ($this.is(':checked')) {
            $('.default-shipping-checkbox').not($this).prop('checked', false);
            $.post(produkt_admin.ajax_url, {
                action: 'pv_set_default_shipping',
                nonce: produkt_admin.nonce,
                id: $this.data('id')
            });
        } else {
            $this.prop('checked', true);
        }
    });

    function updateCharCounter($input, $counter, min, max) {
        var len = $input.val().length;
        $counter.text(len + ' Zeichen');
        $counter.removeClass('ok warning error');
        if (len > max) { $counter.addClass('error'); }
        else if (len >= min) { $counter.addClass('ok'); }
        else { $counter.addClass('warning'); }
    }
    var $mtInput = $('input[name="meta_title"]');
    var $mtCounter = $('#meta_title_counter');
    if ($mtInput.length && $mtCounter.length) {
        updateCharCounter($mtInput, $mtCounter, 50, 60);
        $mtInput.on('input', function(){ updateCharCounter($mtInput, $mtCounter, 50, 60); });
    }
    var $mdInput = $('textarea[name="meta_description"]');
    var $mdCounter = $('#meta_description_counter');
    if ($mdInput.length && $mdCounter.length) {
        updateCharCounter($mdInput, $mdCounter, 150, 160);
        $mdInput.on('input', function(){ updateCharCounter($mdInput, $mdCounter, 150, 160); });
    }

    $(document).on('click', '.inventory-trigger', function(e){
        e.preventDefault();
        var id = $(this).data('variant') || $(this).data('extra');
        var popup = $('#inv-popup-' + id);
        if (popup.length) {
            $('.inventory-popup').not(popup).hide();
            popup.toggle();
        }
    });
    $(document).on('click', '.inventory-popup .inv-minus', function(){
        var target = $('#' + $(this).data('target'));
        if (target.length) {
            var val = parseInt(target.val()) || 0;
            target.val(Math.max(0, val - 1)).trigger('input');
        }
    });
    $(document).on('click', '.inventory-popup .inv-plus', function(){
        var target = $('#' + $(this).data('target'));
        if (target.length) {
            var val = parseInt(target.val()) || 0;
            target.val(val + 1).trigger('input');
        }
    });
    $(document).on('input', '.inventory-popup input', function(){
        var id = this.id.replace(/^(avail|rent)-/, '');
        var avail = $('#avail-' + id).val();
        var rent = $('#rent-' + id).val();
        $('.inventory-trigger[data-variant="' + id + '"] .inventory-available-count, .inventory-trigger[data-extra="' + id + '"] .inventory-available-count').text(avail);
        $('.inventory-trigger[data-variant="' + id + '"] .inventory-rented-count, .inventory-trigger[data-extra="' + id + '"] .inventory-rented-count').text(rent);
    });
    $(document).on('click', function(e){
        if (!$(e.target).closest('.inventory-cell').length) {
            $('.inventory-popup').hide();
        }
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
        var data = new URLSearchParams();
        data.append('action', 'pv_load_customer_logs');
        data.append('nonce', produkt_admin.nonce);
        data.append('offset', offset);
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
