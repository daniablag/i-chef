jQuery(document).ready(function($) {
    let originalMiniCart = $(".ast-header-woo-cart").first(); // Оригинальный блок корзины

    if (originalMiniCart.length) {
        $(".custom-mini-cart").each(function() {
            let target = $(this);
            if (target.find(".ast-header-woo-cart").length === 0) {
                let miniCartClone = originalMiniCart.clone(); // Клонируем корзину
                target.append(miniCartClone);
            }
        });

        // Отключаем переход по ссылке, оставляя только открытие мини-корзины
        $(document).on("click", ".custom-mini-cart", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let originalCartButton = originalMiniCart.find(".ast-site-header-cart-li a");

            if (originalCartButton.length) {
                originalCartButton[0].click(); // Триггерим открытие мини-корзины
            }
        });
    }
});
