jQuery(document).ready(function($) {
    $(document).on('change', '.embroidery-checkout-toggle', function() {
        var details = $(this).closest('.embroidery-fields').find('.embroidery-details');
        if ($(this).is(':checked')) {
            details.show();
        } else {
            details.hide();
        }
    });
});
