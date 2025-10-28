jQuery(document).ready(function($) {
    function displayAttributes() {
        var attributesData = window.variationAttributesData || [];
        if (!attributesData.length) return;
        
        // Если мы находим таблицу вариаций (вариативный товар)
        var $variationsTable = $('.variations.cfvsw-variation-disable-logic tbody');
        if ($variationsTable.length) {
            // Если уже добавлены строки атрибутов (на основе класса auto-added-attributes), ничего не делаем
            if ($variationsTable.find('.auto-added-attributes').length) {
                return;
            }
            attributesData.forEach(function(attr) {
                var $tr = $('<tr class="auto-added-attributes">');
                var $th = $('<th>', { class: 'label' }).append($('<label>').text(attr.name));
                var $td = $('<td>', { class: 'value' });
                
                if (attr.archive_url) {
                    var $link = $('<a>', {
                        href: attr.archive_url,
                        text: attr.value,
                        class: 'attribute-link'
                    });
                    $td.append($link);
                } else {
                    $td.append($('<span>').text(attr.value));
                }
                
                $tr.append($th).append($td);
                $variationsTable.append($tr);
            });
        } else {
            // Если таблица вариаций не найдена (например, для простого товара)
            var $priceElem = $('div.summary.entry-summary p.price');
            if ($priceElem.length) {
                // Проверяем, если сразу после блока цены уже существует таблица атрибутов с классом auto-added-attributes
                if ($priceElem.next('.product-attributes-table.auto-added-attributes').length) {
                    return;
                }
                var $attributesTable = $('<table>', { class: 'product-attributes-table auto-added-attributes' });
                attributesData.forEach(function(attr) {
                    var $tr = $('<tr>');
                    var $th = $('<th>', { class: 'label' }).append($('<label>').text(attr.name));
                    var $td = $('<td>', { class: 'value' });
                    
                    if (attr.archive_url) {
                        var $link = $('<a>', {
                            href: attr.archive_url,
                            text: attr.value,
                            class: 'attribute-link'
                        });
                        $td.append($link);
                    } else {
                        $td.append($('<span>').text(attr.value));
                    }
                    
                    $tr.append($th).append($td);
                    $attributesTable.append($tr);
                });
                $priceElem.after($attributesTable);
            }
        }
    }
    
    // Вызываем функцию при загрузке страницы
    displayAttributes();
    
    // При AJAX-обновлении контента вызываем функцию снова
    $(document).ajaxComplete(function() {
        displayAttributes();
    });
});
