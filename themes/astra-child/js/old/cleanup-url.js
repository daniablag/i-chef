document.addEventListener('DOMContentLoaded', function() {
    if (window.location.href.indexOf('added-to-cart') > -1) {
        var newUrl = window.location.href.split('?')[0];
        window.history.replaceState({}, document.title, newUrl);
    }
    var addToCartButtons = document.querySelectorAll('.single_add_to_cart_button');
    addToCartButtons.forEach(function(button) {
        button.disabled = false;
    });
});