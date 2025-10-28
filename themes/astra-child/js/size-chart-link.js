document.addEventListener("DOMContentLoaded", function () {
    var variationsTable = document.querySelector('.variations.cfvsw-variation-disable-logic tbody');
    if (variationsTable) {
        var rows = variationsTable.querySelectorAll('tr');
        var insertAfterRow = null;

        // Ищем строку с label for="pa_rozmir"
        rows.forEach(function (row) {
            var label = row.querySelector('label[for="pa_rozmir"]');
            if (label) {
                insertAfterRow = row;
            }
        });

        // Если не нашли строку с размером — вставим после первой строки
        if (!insertAfterRow && rows.length > 0) {
            insertAfterRow = rows[0];
        }

        // Вставляем ссылку после нужной строки
        if (insertAfterRow) {
            var newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td class="label"></td>
                <td class="value">
                    <a href="${sizeChartData.size_chart_url}" class="size-chart">${sizeChartData.size_chart_text}</a>
                </td>
            `;
            insertAfterRow.after(newRow);
        }
    }
});
