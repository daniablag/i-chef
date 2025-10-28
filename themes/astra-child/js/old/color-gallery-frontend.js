jQuery(document).ready(function ($) {
    function updateGallery() {
        var selectedColor = $('.variations_form select[name="attribute_pa_kolir"]').val();
        $('.color-gallery').hide();
        $('.color-gallery[data-color="' + selectedColor + '"]').show();
    }

    $(document).on('change', '.variations_form select[name="attribute_pa_kolir"]', function () {
        updateGallery();
    });

    updateGallery();
});
