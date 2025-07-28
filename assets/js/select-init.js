jQuery(document).ready(function ($) {
    // Alle Selects erkennen, die wie Produktkategorie-Auswahlen aussehen
    $('select[name="category-select"]').each(function () {
        const $select = $(this);

        $select.select2({
            placeholder: 'Produkt auswählen...',
            minimumInputLength: 0,
            width: 'resolve',
            allowClear: true
        });

        $select.on('select2:select', function () {
            this.form.submit();
        });
    });

    // Optional: select2 auf andere Felder anwenden, z. B. bei Mehrfach-Auswahl
    $('select.select2-basic').select2({
        width: 'resolve'
    });
});
