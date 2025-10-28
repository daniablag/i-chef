jQuery(document).ready(function($) {
    // Добавляем эффект увеличения для изображений в карточке товара
    $('.wvg-single-gallery-image-container').each(function() {
        // Добавляем оболочку для эффекта увеличения
        $(this).css('overflow', 'hidden');
        
        // Добавляем обработчик события движения мыши
        $(this).on('mousemove', function(e) {
            var $container = $(this);
            var $img = $container.find('img');
            
            // Размеры контейнера
            var containerWidth = $container.width();
            var containerHeight = $container.height();
            
            // Позиция курсора относительно контейнера
            var mouseX = e.pageX - $container.offset().left;
            var mouseY = e.pageY - $container.offset().top;
            
            // Вычисляем смещение для эффекта "следования за курсором"
            var moveX = -(mouseX / containerWidth * 20 - 10); // 20% общего движения
            var moveY = -(mouseY / containerHeight * 20 - 10);
            
            // Применяем увеличение и смещение
            $img.css({
                'transform': 'scale(1.3) translate(' + moveX + '%, ' + moveY + '%)',
                'transition': 'transform 0.1s ease-out'
            });
        });
        
        // Восстанавливаем исходное состояние при уходе курсора
        $(this).on('mouseleave', function() {
            $(this).find('img').css({
                'transform': 'scale(1) translate(0, 0)',
                'transition': 'transform 0.3s ease'
            });
        });
    });
});