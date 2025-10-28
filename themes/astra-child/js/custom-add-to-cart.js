document.addEventListener('DOMContentLoaded', function() {
    // Если в URL есть параметр added-to-cart, удаляем его
    if (window.location.href.indexOf('added-to-cart') > -1) {
        var newUrl = window.location.href.split('?')[0];
        window.history.replaceState({}, document.title, newUrl);
    }

    // Сброс состояния кнопки "Добавить в корзину" после обновления страницы
    var addToCartButtons = document.querySelectorAll('.single_add_to_cart_button');
    addToCartButtons.forEach(function(button) {
        button.disabled = false;
    });
});
