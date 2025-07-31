jQuery(document).ready(function($) {
    // Sidebar öffnen bei Klick auf "Details ansehen"
    $('.view-details-link').on('click', function(e) {
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
                action: 'get_order_details',
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
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
    $('input[name="base_price"], input[name="price"], input[name="price_from"]').on('blur', function() {
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
                        var banner = document.querySelector('.produkt-return-banner');
                        if (banner && !banner.querySelector('.produkt-return-item')) banner.remove();
                    } else {
                        btn.remove();
                    }
                } else {
                    alert('Fehler beim Bestätigen');
                }
            });
    }
});
