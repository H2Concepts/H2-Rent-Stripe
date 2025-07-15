jQuery(document).ready(function ($) {
    var $select = $('select[name="category"]');
    if (!$select.length) return;

    $select.select2({
        placeholder: 'Produkt suchen...',
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'fetch_products',
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        width: 'resolve'
    });

    $select.on('select2:select', function () {
        this.form.submit();
    });
});
