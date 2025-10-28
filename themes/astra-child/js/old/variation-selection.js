jQuery(document).ready(function($) {
    function selectFirstAvailableVariations(context) {
        $(context).find('.variations_form').each(function () {
            const form = $(this);
            form.find('select').val('').trigger('change');
            form.find('select').each(function () {
                const select = $(this);
                const firstOption = select.find('option').not(':disabled').eq(1);
                if (firstOption.length) {
                    select.val(firstOption.val()).trigger('change');
                }
            });
            form.trigger('check_variations');
        });
    }
    selectFirstAvailableVariations(document);
    $(document).on('ajaxComplete', function () {
        selectFirstAvailableVariations(document);
    });
});