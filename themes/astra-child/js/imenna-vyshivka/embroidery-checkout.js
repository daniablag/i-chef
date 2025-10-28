jQuery(document).ready(function($) {
    // Обработка изменений в полях вышивки на чекауте
    $(document.body).on('change', '.checkout-embroidery-block input, .checkout-embroidery-block select', function() {
        $('body').trigger('update_checkout');
    });

    // Принудительно показываем блок после загрузки (лог)
    setTimeout(function() {
        if ($('.checkout-embroidery-section').length) {
            console.log('Embroidery section found');
        } else {
            console.log('Embroidery section NOT found');
        }
    }, 1000);
});
