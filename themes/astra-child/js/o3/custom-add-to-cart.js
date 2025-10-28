jQuery(document).ready(function($) {
    setTimeout(function() {
        $('.add_to_cart_button').each(function() {
            var btn = $(this);
            if (btn.attr('data-add_to_cart_text')) {
                btn.text(btn.attr('data-add_to_cart_text'));
            }
        });
    }, 500);
});
