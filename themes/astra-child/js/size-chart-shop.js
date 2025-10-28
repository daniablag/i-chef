function insertSizeChartLinks(context = document) {
    jQuery(context).find('.cfvsw_variations_form').each(function () {
        const $form = jQuery(this);

        // Уже добавлено — пропускаем
        if ($form.find('.size-chart-row').length > 0) return;

        const rows = $form.find('tr');
        let insertAfterRow;

        if (rows.length >= 2) {
            insertAfterRow = rows.eq(1); // После второго tr
        } else if (rows.length === 1) {
            insertAfterRow = rows.eq(0); // После первого
        }

        if (insertAfterRow && insertAfterRow.length && typeof sizeChartData !== 'undefined') {
            const linkRowHtml = `<tr class="size-chart-row">
                <td colspan="2" style="text-align: center; padding: 10px 0;">
                    <a href="${sizeChartData.size_chart_url}" class="size-chart">${sizeChartData.size_chart_text}</a>
                </td>
            </tr>`;
            insertAfterRow.after(linkRowHtml);
        }
    });
}

// Первый запуск
jQuery(document).ready(function ($) {
    insertSizeChartLinks();

    $(document).on('click', '.size-chart', function (e) {
        const href = $(this).attr('href');
        if (href) window.location.href = href;
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    insertSizeChartLinks(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});