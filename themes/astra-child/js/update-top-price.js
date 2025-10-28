jQuery(document).ready(function ($) {
    function updateTopPrice() {
        var lowerPriceHTML = $('.woocommerce-variation-price .price').html();
        if (lowerPriceHTML) {
            $('div.summary.entry-summary p.price').html(lowerPriceHTML);
        }
    }

    $('.variations_form')
        .on('found_variation', function (e, variation) {
            setTimeout(updateTopPrice, 350);
        })
        .on('reset_data', function () {
            setTimeout(updateTopPrice, 350);
        });

    var target = document.querySelector('.woocommerce-variation-price');
    if (target) {
        var observer = new MutationObserver(function (mutations) {
            setTimeout(updateTopPrice, 200);
        });
        observer.observe(target, { childList: true, subtree: true });
    }

    setTimeout(updateTopPrice, 150);
});
