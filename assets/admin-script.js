jQuery(document).ready(function($) {
    // Admin JavaScript functionality
    
    // Confirm delete actions
    $('.wp-list-table a[href*="delete"]').on('click', function(e) {
        if (!confirm('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?')) {
            e.preventDefault();
            return false;
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
    
    // Form validation before submit
    $('form').on('submit', function(e) {
        var hasErrors = false;
        
        // Check all image URL fields
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
            return false;
        }
    });

    // Update color preview swatches
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

    // Accordion fields
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

    // Remember last selected product across admin pages
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
