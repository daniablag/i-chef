<?php
/**
 * Plugin Name: Іменна вишивка WooCommerce
 * Description: Додає кастомну опцію іменної вишивки з динамічним ціноутворенням.
 * Version: 1.0
 * Author: DC Web Studio
 * Text Domain: embroidered-name
 */

defined('ABSPATH') || exit;

// Категории на которых работает плагин
define('EMBROIDERY_ALLOWED_CATEGORIES', ['kiteli', 'fartuhy', 'aprons', 'kiteli-en']);

function is_embroidery_allowed_product($product) {
    if (!$product || !is_a($product, 'WC_Product')) return false;
    return is_embroidery_allowed_product_id($product->get_id());
}

function is_embroidery_allowed_product_id($product_id) {
    $allowed_slugs = EMBROIDERY_ALLOWED_CATEGORIES;

    // Если это вариация, получаем ID родительского товара
    $parent_id = wp_get_post_parent_id($product_id);
    if ($parent_id) {
        $product_id = $parent_id;
    }

    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (empty($terms) || is_wp_error($terms)) return false;

    foreach ($terms as $term) {
        if (in_array($term->slug, $allowed_slugs, true)) return true;

        $ancestors = get_ancestors($term->term_id, 'product_cat');
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if ($ancestor && in_array($ancestor->slug, $allowed_slugs, true)) {
                return true;
            }
        }
    }

    return false;
}

// Подключение стилей плагина
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('embroidered-name-style', plugin_dir_url(__FILE__) . 'style.css');
});

// Создаем в разделе товары в админке раздел с ценами и чекбоксами
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=product',
        'Іменна вишивка',
        'Іменна вишивка',
        'manage_woocommerce',
        'embroidery-settings',
        'render_embroidery_settings_page'
    );
});

function render_embroidery_settings_page() {
    ?>
    <div class="wrap">
        <h1>Налаштування іменної вишивки</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('embroidery_settings_group');
            do_settings_sections('embroidery-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('embroidery_settings_group', 'embroidery_name_price');
    register_setting('embroidery_settings_group', 'embroidery_title_price');
    for ($i = 1; $i <= 10; $i++) {
        register_setting('embroidery_settings_group', "embroidery_title_$i");
    }

    add_settings_section('embroidery_main_section', '', null, 'embroidery-settings');

    add_settings_field('embroidery_name_price', 'Ціна за ім\'я + прізвище', function () {
        echo '<input type="number" step="1" name="embroidery_name_price" value="' . esc_attr(get_option('embroidery_name_price', 200)) . '" />';
    }, 'embroidery-settings', 'embroidery_main_section');

    add_settings_field('embroidery_title_price', 'Ціна за один титул', function () {
        echo '<input type="number" step="1" name="embroidery_title_price" value="' . esc_attr(get_option('embroidery_title_price', 200)) . '" />';
    }, 'embroidery-settings', 'embroidery_main_section');

    for ($i = 1; $i <= 10; $i++) {
        add_settings_field("embroidery_title_$i", "Титул $i", function () use ($i) {
            echo '<input type="text" name="embroidery_title_' . $i . '" value="' . esc_attr(get_option("embroidery_title_$i", '')) . '" />';
        }, 'embroidery-settings', 'embroidery_main_section');
    }
});

add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if (!is_embroidery_allowed_product($product)) return;

    $name_price = (int) get_option('embroidery_name_price', 200);
    $title_price = (int) get_option('embroidery_title_price', 200);
    $titles = [];
    for ($i = 1; $i <= 10; $i++) {
        $label = trim(get_option("embroidery_title_$i"));
        if ($label !== '') {
            $titles[] = $label;
        }
    }
    ?>
    <div id="embroidery-block" class="embroidery-block">
       <label class="embroidery-toggle">
    <input type="checkbox" id="enable-embroidery" name="enable_embroidery" value="yes">
    <?php echo sprintf(__('Хочу іменну вишивку (+%d грн)', 'embroidered-name'), $name_price); ?>
        </label>

        <div id="embroidery-fields" class="embroidery-fields">
            <p class="embroidery-field">
                <label><?php _e("Ім'я та прізвище:", 'embroidered-name'); ?><br>
                    <input type="text" name="embroidery_text" id="embroidery-text" class="embroidery-text">
                </label>
            </p>

            <p class="embroidery-field">
                <label><?php _e('Шрифт:', 'embroidered-name'); ?><br>
                    <select name="embroidery_font" id="embroidery-font" class="embroidery-font">
                        <option value="'turbota'">Turbota</option>
                        <option value="'Arsenal'">Arsenal</option>
                        <option value="'Autoproject GOST Type A Light'">Autoproject GOST Type A Light</option>
                        <option value="'Palatino Linotype'">Palatino Linotype</option>
                        <option value="'ArtScript'">ArtScript</option>
                        <option value="'PhillippScript'">PhillippScript</option>
                        <option value="'SecondRoad'">Second Road</option>
                        <option value="'Segoe Script'">Segoe Script</option>
                        <option value="'Yeseva One'">Yeseva One</option>
                        <option value="'Monotype Corsiva Regular'">Monotype Corsiva Regular</option>
                        <option value="'Blacklight'">Blacklight</option>
                    </select>
                </label>
            </p>

            <p class="embroidery-field">
                <strong><?php _e('Перегляд напису:', 'embroidered-name'); ?></strong><br>
                <span id="embroidery-preview" class="embroidery-preview"></span>
            </p>

            <p class="embroidery-field">
    <strong><?php echo sprintf(__('Додати титули (+%d грн):', 'embroidered-name'), $title_price); ?></strong></p>
            <?php foreach ($titles as $title): ?>
                <label class="embroidery-title">
                    <input type="checkbox" name="embroidery_titles[]" value="<?php echo esc_attr($title); ?>">
                    <span class="title-preview" data-title="<?php echo esc_attr($title); ?>"><?php echo esc_html($title); ?></span>
                </label><br>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('enable-embroidery');
        const fields = document.getElementById('embroidery-fields');
        const preview = document.getElementById('embroidery-preview');
        const textInput = document.getElementById('embroidery-text');
        const fontSelect = document.getElementById('embroidery-font');
        const titleSpans = document.querySelectorAll('.title-preview');
        const titleCheckboxes = document.querySelectorAll('input[name="embroidery_titles[]"]');

        const priceBox = document.querySelector('.product .price .woocommerce-Price-amount');
        let originalPrice = null;

        function parsePrice(str) {
            return parseFloat(str.replace(/[^\d.,]/g, '').replace(',', '.'));
        }

        if (priceBox && originalPrice === null) {
            originalPrice = parsePrice(priceBox.textContent);
        }

        function updatePreview() {
            const fullText = textInput.value || '';
            const font = fontSelect.value;

            preview.textContent = fullText;
            preview.style.fontFamily = font;

            titleSpans.forEach(span => {
                span.style.fontFamily = font;
            });
        }

        function updateDisplayedPrice() {
    const bdi = document.querySelector('.product .price .woocommerce-Price-amount bdi');
    if (!bdi || originalPrice === null) return;

    let total = originalPrice;

    const embroideryEnabled = checkbox.checked;
    const textFilled = textInput.value.trim().length > 0;

    if (embroideryEnabled && textFilled) {
        total += <?php echo $name_price; ?>;
    }

    const checkedTitles = document.querySelectorAll('input[name="embroidery_titles[]"]:checked');
    total += checkedTitles.length * <?php echo $title_price; ?>;

    const symbol = document.querySelector('.woocommerce-Price-currencySymbol')?.innerText || 'грн.';
    bdi.textContent = total.toFixed(2).replace('.', ',') + ' ' + symbol;
}

        checkbox.addEventListener('change', () => {
            fields.style.display = checkbox.checked ? 'block' : 'none';
            updatePreview();
            updateDisplayedPrice();
        });

        textInput.addEventListener('input', () => {
    updatePreview();
    updateDisplayedPrice();
});

        fontSelect.addEventListener('change', updatePreview);
        titleCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateDisplayedPrice);
        });
    });

    jQuery(document.body).on('added_to_cart', function () {
        jQuery(document.body).trigger('wc_fragment_refresh');
    });
    </script>
    <?php
});

// Добавление данных в корзину
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    if (!is_embroidery_allowed_product_id($product_id)) return $cart_item_data;

    if (!empty($_POST['enable_embroidery'])) {
        $embroidery_text = sanitize_text_field($_POST['embroidery_text'] ?? '');
        $embroidery_font = sanitize_text_field($_POST['embroidery_font'] ?? '');
        $titles = $_POST['embroidery_titles'] ?? [];

        $cart_item_data['embroidery'] = [
            'text' => $embroidery_text,
            'font' => $embroidery_font,
            'titles' => array_map('sanitize_text_field', (array)$titles),
        ];

        $cart_item_data['unique_key'] = md5(microtime() . rand());
    }

    return $cart_item_data;
}, 10, 2);

// Отображение в корзине
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    $product_id = $cart_item['product_id'] ?? $cart_item['data']->get_id();
    if (!is_embroidery_allowed_product_id($product_id)) return $item_data;

    if (!empty($cart_item['embroidery'])) {
        $item_data[] = ['name' => __('Іменна вишивка', 'embroidered-name'), 'value' => __('Так', 'embroidered-name')];

        $text = trim($cart_item['embroidery']['text'] ?? '');
        if (!empty($text)) {
            $item_data[] = ['name' => __('Текст', 'embroidered-name'), 'value' => stripslashes($text)];
        }

        $font = trim($cart_item['embroidery']['font'] ?? '');
        if (!empty($font)) {
            $item_data[] = ['name' => __('Шрифт', 'embroidered-name'), 'value' => $font];
        }

        if (!empty($cart_item['embroidery']['titles'])) {
            $titles = implode(', ', array_map('sanitize_text_field', $cart_item['embroidery']['titles']));
            $item_data[] = ['name' => __('Титули', 'embroidered-name'), 'value' => $titles];
        }
    }

    return $item_data;
}, 10, 2);

// Пересчет цены
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'] ?? $cart_item['data']->get_id();
        if (!is_embroidery_allowed_product_id($product_id)) continue;

        if (!empty($cart_item['embroidery'])) {
            $name_price = (int) get_option('embroidery_name_price', 200);
            $title_price = (int) get_option('embroidery_title_price', 200);

            $base_price = $cart_item['data']->get_price();
            $adjustment = 0;
            if (!empty(trim($cart_item['embroidery']['text']))) {
                $adjustment += $name_price;
            }
            $adjustment += count($cart_item['embroidery']['titles']) * $title_price;

            $cart_item['data']->set_price($base_price + $adjustment);
        }
    }
});

// Исправление отображения в мини-корзине
add_filter('woocommerce_cart_item_name', function ($name, $cart_item, $cart_item_key) {
     $product_id = $cart_item['product_id'] ?? $cart_item['data']->get_id();
    if (!is_embroidery_allowed_product_id($product_id)) return $name;

    if (!empty($cart_item['embroidery'])) {
        $base_price = $cart_item['data']->get_regular_price();
        $name_price = (int) get_option('embroidery_name_price', 200);
        $title_price = (int) get_option('embroidery_title_price', 200);
        $adjustment = 0;
        if (!empty(trim($cart_item['embroidery']['text']))) {
            $adjustment += $name_price;
        }
        $adjustment += count($cart_item['embroidery']['titles']) * $title_price;

        $cart_item['data']->set_price($base_price + $adjustment);
    }

    return $name;
}, 9, 3);

// Для писем — добавление в мета
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    $product_id = $values['product_id'] ?? $values['data']->get_id();
    if (!is_embroidery_allowed_product_id($product_id)) return;

    if (isset($values['embroidery'])) {
        $emb = $values['embroidery'];
        if (!empty($emb['text'])) {
            $item->add_meta_data(__('Іменна вишивка', 'embroidered-name'), __('Так', 'embroidered-name'), true);
            $item->add_meta_data(__('Текст', 'embroidered-name'), $emb['text'], true);
        }
        if (!empty($emb['font'])) {
            $item->add_meta_data(__('Шрифт', 'embroidered-name'), $emb['font'], true);
        }
        if (!empty($emb['titles'])) {
            $item->add_meta_data(__('Титули', 'embroidered-name'), implode(', ', $emb['titles']), true);
        }
    }
}, 10, 4);

// ИСПРАВЛЕННАЯ ФУНКЦИЯ ДЛЯ ЧЕКАУТА

// add_filter('woocommerce_cart_item_name', 'embroidery_cart_item_name_html', 20, 3);
function embroidery_cart_item_name_html($product_name, $cart_item, $cart_item_key) {
    if (is_checkout() && is_embroidery_allowed_product_id($cart_item['product_id'] ?? $cart_item['data']->get_id())) {
        // Не показывать, если уже добавлена вышивка (можно убрать, если нужно показывать всегда)
        if (empty($cart_item['embroidery']) || empty($cart_item['embroidery']['text'])) {
            ob_start();
            echo '<div class="embroidery-row" style="display:block;margin-top:10px;padding:8px 0 0 0;border-top:1px dashed #eee;color:#226;">';
            render_embroidery_form_fields($cart_item_key);
            echo '</div>';
            $product_name .= ob_get_clean();
        }
    }
    return $product_name;
}

// add_action('woocommerce_checkout_after_order_review', 'render_embroidery_checkout_fields', 20);
function render_embroidery_checkout_fields() {
    $has_embroidery_items = false;
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        if (!is_embroidery_allowed_product_id($product_id)) continue;
        if (!empty($cart_item['embroidery']) && !empty($cart_item['embroidery']['text'])) continue;

        $has_embroidery_items = true;
        break;
    }

    if (!$has_embroidery_items) return;

    ?>
    <div class="checkout-embroidery-section">
        <h6><?php _e('Додати іменну вишивку', 'embroidered-name'); ?></h6>
        <?php
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();

            if (!is_embroidery_allowed_product_id($product_id)) continue;
            if (!empty($cart_item['embroidery']) && !empty($cart_item['embroidery']['text'])) continue;

            echo '<div class="checkout-embroidery-block" data-cart-item="' . esc_attr($cart_item_key) . '">';
            echo '<h6>' . __('Для товару:', 'embroidered-name') . ' ' . esc_html($product->get_name()) . '</h6>';
            render_embroidery_form_fields($cart_item_key);
            echo '</div>';
        }
        ?>
    </div>

    <?php
}

// ОБРАБОТКА AJAX ОБНОВЛЕНИЯ ЧЕКАУТА
add_action('woocommerce_checkout_update_order_review', 'handle_embroidery_checkout_submission');

function handle_embroidery_checkout_submission($post_data) {
    parse_str($post_data, $parsed);
    
    if (!isset($parsed['embroidery_data']) || !is_array($parsed['embroidery_data'])) return;

    foreach ($parsed['embroidery_data'] as $cart_item_key => $embroidery_fields) {
        if (!isset(WC()->cart->cart_contents[$cart_item_key])) continue;

        $item = &WC()->cart->cart_contents[$cart_item_key];
        if (!is_embroidery_allowed_product_id($item['product_id'])) continue;

        // Если уже есть вышивка — не перезаписываем
        if (!empty($item['embroidery']) && !empty($item['embroidery']['text'])) continue;

        // Проверяем, что чекбокс включен
        if (empty($embroidery_fields['enabled'])) {
            // Если чекбокс выключен, удаляем вышивку если она была
            if (isset($item['embroidery'])) {
                unset($item['embroidery']);
            }
            continue;
        }

        $text = sanitize_text_field($embroidery_fields['text'] ?? '');
        $font = sanitize_text_field($embroidery_fields['font'] ?? '');
        $titles = array_map('sanitize_text_field', (array) ($embroidery_fields['titles'] ?? []));

        // Добавляем только если есть текст
        if (!empty($text)) {
            $item['embroidery'] = [
                'text' => $text,
                'font' => $font,
                'titles' => $titles,
            ];

            $item['unique_key'] = md5(microtime() . rand());
        }
    }
}

function render_embroidery_form_fields($cart_item_key) {
    $name_price = (int) get_option('embroidery_name_price', 200);
    $title_price = (int) get_option('embroidery_title_price', 200);

    $titles = [];
    for ($i = 1; $i <= 10; $i++) {
        $label = trim(get_option("embroidery_title_$i"));
        if ($label !== '') {
            $titles[] = $label;
        }
    }

    ?>
    <div class="embroidery-fields">
        <p>
            <label>
                <input type="checkbox" name="embroidery_data[<?php echo esc_attr($cart_item_key); ?>][enabled]" value="1">
                <?php echo sprintf(__('Додати іменну вишивку (+%d грн)', 'embroidered-name'), $name_price); ?>
            </label>
        </p>

        <div class="embroidery-details" style="display: none;">
            <p>
                <label><?php _e("Ім'я та прізвище:", 'embroidered-name'); ?><br>
                    <input type="text" name="embroidery_data[<?php echo esc_attr($cart_item_key); ?>][text]" class="input-text">
                </label>
            </p>

            <p>
                <label><?php _e('Шрифт:', 'embroidered-name'); ?><br>
                    <select name="embroidery_data[<?php echo esc_attr($cart_item_key); ?>][font]" class="select">
                        <option value="Turbota">Turbota</option>
                        <option value="Arsenal">Arsenal</option>
                        <option value="Palatino Linotype">Palatino Linotype</option>
                        <option value="Autoproject GOST Type A Light">Autoproject GOST Type A Light</option>
                        <option value="ArtScript">ArtScript</option>
                        <option value="PhillippScript">PhillippScript</option>
                        <option value="SecondRoad">Second Road</option>
                        <option value="Segoe Script">Segoe Script</option>
                        <option value="Yeseva One">Yeseva One</option>
                        <option value="Monotype Corsiva Regular">Monotype Corsiva Regular</option>
                        <option value="Blacklight">Blacklight</option>
                    </select>
                </label>
            </p>

            <?php if (!empty($titles)): ?>
            <p><strong><?php echo sprintf(__('Додати титули (+%d грн за кожен):', 'embroidered-name'), $title_price); ?></strong></p>
            <?php foreach ($titles as $title): ?>
                <label>
                    <input type="checkbox" name="embroidery_data[<?php echo esc_attr($cart_item_key); ?>][titles][]" value="<?php echo esc_attr($title); ?>">
                    <?php echo esc_html($title); ?>
                </label><br>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php
}
add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        // Подключаем основной js для чекбокса
        wp_enqueue_script(
            'imenna-vyshivka-js',
            plugin_dir_url(__FILE__) . 'js/imenna-vyshivka.js',
            ['jquery'],
            '1.0',
            true
        );
        // Подключаем скрипт для блока на чекауте
        wp_enqueue_script(
            'embroidery-checkout-js',
            plugin_dir_url(__FILE__) . 'js/embroidery-checkout.js',
            ['jquery'],
            '1.0',
            true
        );
    }
});
