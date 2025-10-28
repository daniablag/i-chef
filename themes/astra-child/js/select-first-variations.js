function selectFirstAvailableVariations(context = document) {
    const isProductPage = document.body.classList.contains('single-product');
    const urlParams = new URLSearchParams(window.location.search);
    const rawColorFilter = urlParams.get('wpf_filter_kolir');
    const filterColors = rawColorFilter ? rawColorFilter.split('|').map(c => c.toLowerCase().trim()) : [];

    jQuery(context).find('.variations_form').each(function () {
        const $form = jQuery(this);

        $form.find('.cfvsw-swatches-container').each(function () {
            const $container = jQuery(this);
            let attributeName = $container.attr('swatches-attr');

            // Если swatches-attr нет — вытаскиваем из класса
            if (!attributeName) {
                const classList = $container.attr('class');
                const match = classList.match(/name-(attribute_\w+)/);
                if (match) {
                    attributeName = match[1];
                }
            }

            if (!attributeName) return;

            const isColorAttr = $container.find('.cfvsw-swatches-option[data-slug]').length > 0;

            // Если на странице товара и вариация указана в URL — пропускаем
            const selectedFromURL = urlParams.get(attributeName);
            if (isProductPage && selectedFromURL) {
                return;
            }

            // Если задан фильтр цвета — пробуем применить его
            if (filterColors.length > 0 && isColorAttr) {
                let matched = false;
                for (const color of filterColors) {
                    const $matchingSwatch = $container.find(`.cfvsw-swatches-option[data-slug="${color}"]:not(.disabled)`).first();
                    if ($matchingSwatch.length) {
                        setTimeout(() => $matchingSwatch.trigger('click'), 200);
                        matched = true;
                        break;
                    }
                }
                if (matched) return;
            }

            // Если ничего не выбрано — выбираем первую доступную
            const $firstAvailable = $container.find('.cfvsw-swatches-option:not(.disabled)').first();
            if ($firstAvailable.length) {
                setTimeout(() => $firstAvailable.trigger('click'), 200);
            }
        });
    });
}

jQuery(document).ready(function ($) {
    // При загрузке страницы
    selectFirstAvailableVariations();

    // При появлении новых товаров в DOM (на будущее)
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    if ($(node).find('.variations_form').length > 0 || $(node).hasClass('variations_form')) {
                        setTimeout(() => {
                            selectFirstAvailableVariations(node);
                        }, 300);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});