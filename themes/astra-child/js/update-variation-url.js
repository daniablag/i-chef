document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.variations_form');
    if (!form) return;

    const selects = form.querySelectorAll('select');

    // Функция обновления URL
    function updateURL() {
        const params = new URLSearchParams(window.location.search);

        selects.forEach(select => {
            const name = select.name;
            const value = select.value;

            if (value) {
                params.set(name, value);
            } else {
                params.delete(name);
            }
        });

        const newUrl = window.location.pathname + '?' + params.toString();
        history.replaceState({}, '', newUrl);
    }

    // Слушатель на скрытых <select> (обновляются при выборе свотча)
    selects.forEach(select => {
        select.addEventListener('change', updateURL);
    });

    // Также отслеживаем клики по swatches (визуальные кнопки)
    document.querySelectorAll('.cfvsw-swatches-option').forEach(option => {
        option.addEventListener('click', function () {
            setTimeout(updateURL, 100);
        });
    });
});
