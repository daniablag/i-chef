jQuery(document).ready(function($) {
    function insertSizeChartLink() {
        // Проверяем наличие формы вариаций
        var $form = $('.variations_form');
        if (!$form.length) return;
        
        // Попытка найти строку таблицы, где заголовок содержит слово "розмір"
        var $sizeRow = $form.find('tr').filter(function() {
            var headerText = $(this).find('th').text().toLowerCase();
            return headerText.indexOf('розмір') !== -1;
        });
        
        // Если строка не найдена, берем вторую строку как запасной вариант
        if (!$sizeRow.length && $form.find('tr').length > 1) {
            $sizeRow = $form.find('tr').eq(1);
        }
        
        // Если нашли строку, добавляем ссылку, если её там ещё нет
        if ($sizeRow.length) {
            var $cell = $sizeRow.find("td.value");
            if ($cell.length && $cell.find('.size-chart').length === 0) {
                var $link = $('<a>', {
                    href: '/rozmirna-sitka', // Укажите корректный URL для размерной сетки
                    text: 'Розмірна сітка',
                    class: 'size-chart'
                });
                $cell.append($link);
            }
        }
    }
    
    // Вызываем функцию при загрузке страницы
    insertSizeChartLink();
    
    // Если контент загружается через AJAX, повторно вызываем функцию
    $(document).ajaxComplete(function() {
        insertSizeChartLink();
    });
});
