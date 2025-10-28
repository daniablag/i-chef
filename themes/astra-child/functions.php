<?php
/**
 * Astra child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

function custom_lato_font() {
    wp_enqueue_style( 'custom-lato', 'https://static.ntile.app/fonts/lato/lato.css', false );
}
add_action( 'wp_enqueue_scripts', 'custom_lato_font' );

// Регистрация нового типа записи "Наші відзнаки"
function create_awards_post_type() {
    $labels = array(
        'name'               => 'Наші відзнаки',
        'singular_name'      => 'Відзнака',
        'menu_name'          => 'Наші відзнаки',
        'name_admin_bar'     => 'Відзнака',
        'add_new'            => 'Додати нову',
        'add_new_item'       => 'Додати нову відзнаку',
        'new_item'           => 'Нова відзнака',
        'edit_item'          => 'Редагувати відзнаку',
        'view_item'          => 'Переглянути відзнаку',
        'all_items'          => 'Усі відзнаки',
        'search_items'       => 'Шукати відзнаку',
        'parent_item_colon'  => 'Батьківські відзнаки:',
        'not_found'          => 'Не знайдено відзнак.',
        'not_found_in_trash' => 'У кошику немає відзнак.'
    );
    
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'vidznaky'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-awards',  // Иконка наград
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'show_in_rest'       => true,  // Поддержка Gutenberg (Spectra)
    );
    
    register_post_type('awards', $args);
}
add_action('init', 'create_awards_post_type');


// Создаем галерею для типа записи awards (Наші відзнаки)
function add_spectra_gallery_metabox() {
    add_meta_box(
        'spectra_gallery_metabox',
        'Галерея Spectra',
        'spectra_gallery_metabox_callback',
        ['awards'],  // Доступно для записей типа awards
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_spectra_gallery_metabox');

// Метабокс с медиазагрузчиком
function spectra_gallery_metabox_callback($post) {
    wp_nonce_field(basename(__FILE__), 'spectra_gallery_nonce');
    $gallery = get_post_meta($post->ID, '_spectra_gallery', true);
    ?>
    <div id="spectra-gallery-container">
        <ul class="spectra-gallery-images">
            <?php
            if ($gallery) {
                foreach ($gallery as $attachment_id) {
                    $img_url = wp_get_attachment_image_src($attachment_id, 'thumbnail')[0];
                    echo '<li data-id="' . esc_attr($attachment_id) . '">
                        <img src="' . esc_url($img_url) . '" />
                        <span class="remove">Удалить</span>
                    </li>';
                }
            }
            ?>
        </ul>
        <input type="hidden" id="spectra-gallery-input" name="spectra_gallery" value="<?php echo esc_attr(implode(',', (array)$gallery)); ?>" />
        <button type="button" class="button spectra-upload-button">Добавить изображения</button>
    </div>
    <?php
}
// Подключаем скрипт для загрузки изображений в галлерею наши відзнаки
function enqueue_spectra_gallery_admin_scripts($hook) {
    global $post;
    
    if ($hook == 'post.php' || $hook == 'post-new.php') { // Только в редакторе записи
        if ($post->post_type === 'awards') { // Только для записей "Наші відзнаки"
            wp_enqueue_script(
                'spectra-gallery-metabox',
                get_stylesheet_directory_uri() . '/js/spectra-gallery-metabox.js',
                array('jquery', 'wp-mediaelement', 'wp-api'), 
                null, 
                true
            );
        }
    }
}
add_action('admin_enqueue_scripts', 'enqueue_spectra_gallery_admin_scripts');


// Сохраняем данные галереи
function save_spectra_gallery($post_id) {
    if (!isset($_POST['spectra_gallery_nonce']) || !wp_verify_nonce($_POST['spectra_gallery_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['spectra_gallery'])) {
        $attachment_ids = array_filter(explode(',', $_POST['spectra_gallery']));
        update_post_meta($post_id, '_spectra_gallery', $attachment_ids);
    } else {
        delete_post_meta($post_id, '_spectra_gallery');
    }
}
add_action('save_post', 'save_spectra_gallery');

// Вывод галереи с помощью шорткода
add_shortcode('spectra_gallery', 'display_spectra_gallery');

function display_spectra_gallery($atts) {
    $atts = shortcode_atts(
        array('post_id' => get_the_ID()), 
        $atts
    );

    $gallery = get_post_meta($atts['post_id'], '_spectra_gallery', true);
    if (!$gallery) {
        return '<p>' . __('Галерея відсутня', 'astra') . '</p>';
    }

    ob_start();
    ?>
    <div class="spectra-gallery-wrapper">
        <div class="spectra-gallery-thumbnails">
            <?php foreach ($gallery as $attachment_id) : 
                $thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                $full_img = wp_get_attachment_image_src($attachment_id, 'full');
            ?>
                <img src="<?php echo esc_url($thumbnail[0]); ?>" data-full="<?php echo esc_url($full_img[0]); ?>" />
            <?php endforeach; ?>
        </div>
        <div class="spectra-gallery-main uagb-slick-carousel">
            <img id="spectra-main-image" src="<?php echo esc_url($full_img[0]); ?>" />
            <div class="spectra-gallery-arrows">
                <button class="spectra-gallery-arrow-prev"><svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17 0L17.5189 0.518865L6.03773 12L17.5189 23.4811L17 24L5 12L17 0Z" fill="#171717"></path></svg></button>
                <button class="spectra-gallery-arrow-next"><svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 24L6.48113 23.4811L17.9623 12L6.48114 0.518864L7 -6.91403e-07L19 12L7 24Z" fill="#171717"></path></svg></button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
// Подключаем скрипт галлереи на страницах наши відзнаки на фронтенде
function enqueue_spectra_gallery_scripts() {
    if (is_singular('awards')) { // Загружаем только на странице отдельной записи 'Наші відзнаки'
        wp_enqueue_script(
            'spectra-gallery',
            get_stylesheet_directory_uri() . '/js/spectra-gallery.js',
            array('jquery'), 
            null, 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_spectra_gallery_scripts');


// Регистрация нового размера миниатюры
function custom_thumbnail_size() {
    add_image_size('gallery-', 285, 427, true); // Ширина 300px, высота 200px, обрезка жесткая
}
add_action('after_setup_theme', 'custom_thumbnail_size');

// Регистрация нового типа записей "Відгуки"
function create_reviews_post_type() {
    $labels = array(
        'name' => __('Відгуки', 'textdomain'),
        'singular_name' => __('Відгук', 'textdomain'),
        'menu_name' => __('Відгуки', 'textdomain'),
        'add_new' => __('Добавить відгук', 'textdomain'),
        'add_new_item' => __('Добавить новый відгук', 'textdomain'),
        'edit_item' => __('Редактировать відгук', 'textdomain'),
        'new_item' => __('Новый відгук', 'textdomain'),
        'view_item' => __('Просмотр відгука', 'textdomain'),
        'search_items' => __('Искать відгуки', 'textdomain'),
        'not_found' => __('Відгуков не найдено', 'textdomain'),
        'not_found_in_trash' => __('В корзине відгуков не найдено', 'textdomain'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'vidguky'),
        'supports' => array('title', 'editor', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-star-filled', // Иконка звезды
        'show_in_rest' => true, // Для поддержки Gutenberg и перевода через Polylang
    );

    register_post_type('reviews', $args);
}
add_action('init', 'create_reviews_post_type');

// Добавление звездного рейтинга с помощью метабоксов
function add_reviews_meta_boxes() {
    add_meta_box(
        'reviews_rating',
        __('Звездный рейтинг', 'textdomain'),
        'render_reviews_rating_meta_box',
        'reviews',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_reviews_meta_boxes');

// Рендеринг метабокса для звездного рейтинга
function render_reviews_rating_meta_box($post) {
    $rating = get_post_meta($post->ID, '_reviews_rating', true);
    ?>
    <label for="reviews_rating"><?php _e('Рейтинг (от 1 до 5 звезд)', 'textdomain'); ?></label>
    <select name="reviews_rating" id="reviews_rating">
        <option value=""><?php _e('Выберите рейтинг', 'textdomain'); ?></option>
        <?php for ($i = 1; $i <= 5; $i++) : ?>
            <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <?php
}

// Сохранение значения звездного рейтинга
function save_reviews_meta_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (array_key_exists('reviews_rating', $_POST)) {
        update_post_meta($post_id, '_reviews_rating', sanitize_text_field($_POST['reviews_rating']));
    }
}
add_action('save_post', 'save_reviews_meta_data');

// Рендеринг звезд с использованием SVG
function render_star_rating($rating) {
    $output = '<div class="star-rating-wrapper">';
    for ($i = 1; $i <= 5; $i++) {
        $output .= ($i <= $rating) 
            ? '<svg class="star full" width="20" height="20" viewBox="0 0 24 24" fill="#FFD700" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.782 1.4 8.16L12 18.896l-7.334 3.856 1.4-8.16L.132 9.21l8.2-1.192z"/>
               </svg>'
            : '<svg class="star empty" width="20" height="20" viewBox="0 0 24 24" fill="#E4E4E4" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.782 1.4 8.16L12 18.896l-7.334 3.856 1.4-8.16L.132 9.21l8.2-1.192z"/>
               </svg>';
    }
    $output .= '</div>';
    return $output;
}

// Фильтр для добавления звездного рейтинга в начало контента
function add_rating_to_content($content) {
    if (get_post_type() === 'reviews') {
        $rating = get_post_meta(get_the_ID(), '_reviews_rating', true);
        if ($rating) {
            $stars = render_star_rating($rating);
            $content = '<div class="reviews-rating">' . $stars . '</div>' . $content;
        }
    }
    return $content;
}
add_filter('the_content', 'add_rating_to_content');

// Шорткод для вывода звездного рейтинга
function review_rating_shortcode() {
    global $post;

    // Убедитесь, что текущий объект записи доступен
    if (!isset($post)) {
        return '';
    }

    // Получение рейтинга из метаполя текущей записи
    $rating = get_post_meta($post->ID, '_reviews_rating', true);

    // Если рейтинг есть, рендерим звезды
    if ($rating) {
        return '<div class="star-rating-shortcode">' . render_star_rating($rating) . '</div>';
    }

    return ''; // Если рейтинга нет
}
add_shortcode('review_rating', 'review_rating_shortcode');

// Включить обработку шорткодов в динамическом контенте
function enable_shortcodes_in_dynamic_content($content) {
    return do_shortcode($content);
}
add_filter('the_content', 'enable_shortcodes_in_dynamic_content');


function allow_webp_upload($mime_types) {
    $mime_types['webp'] = 'image/webp'; // Добавляем поддержку WebP
    return $mime_types;
}
add_filter('upload_mimes', 'allow_webp_upload');


// Функция для выбора первых доступных вариаций
/* function enqueue_select_variations_script() {
    if (is_shop() || is_product_category() || is_product_tag() || is_product() || is_search()) {
        wp_enqueue_script(
            'select-first-variations',
            get_stylesheet_directory_uri() . '/js/select-first-variations.js',
            array('jquery'),
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_select_variations_script'); */

// Изменить урл при выборе вариации
/* add_action('wp_enqueue_scripts', function() {
    if (is_product()) {
        wp_enqueue_script(
            'update-variation-url',
            get_stylesheet_directory_uri() . '/js/update-variation-url.js',
            array('jquery'), // зависимости (если нужно дождаться jQuery), иначе можно array()
            null, // без версии
            true // загрузить в футере
        );
    }
}); */

// Функция для обновления URL карточки товаров при выборе вариаций в каталоге
function enqueue_variation_url_sync_script() {
    if (is_shop() || is_product_category() || is_product_tag() || is_product() || is_search()) {
        wp_enqueue_script(
            'variation-url-sync',
            get_stylesheet_directory_uri() . '/js/variation-url-sync.js',
            array('jquery'),
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_variation_url_sync_script');


// Удалить сайдбар с мобильных
 function enqueue_remove_mobile_sidebar_script() {
    if (is_shop() || is_product_category() || is_product_tag() || is_search()) {
        wp_enqueue_script(
            'remove-mobile-sidebar',
            get_stylesheet_directory_uri() . '/js/remove-mobile-sidebar.js',
            array('jquery'),
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_remove_mobile_sidebar_script');

// Изменяем текст кнопки "Добавить в корзину" для вариативных товаров в каталоге
/* 1. Фильтр для изменения текста кнопки в каталоге (для вариативных товаров) */
add_filter( 'woocommerce_loop_add_to_cart_args', 'custom_translate_add_to_cart_button_text', 11, 2 );
function custom_translate_add_to_cart_button_text( $args, $product ) {
    // Применяем для вариативных товаров, только если товар в наличии и цена установлена
    if ( $product->is_type( 'variable' ) && $product->is_in_stock() && $product->get_price() !== '' ) {
        // Добавляем AJAX-класс для корректного обновления кнопки
        $args['class'] .= ' cfvsw_ajax_add_to_cart';
        // Устанавливаем новый текст кнопки через data-атрибут
        $args['attributes']['data-add_to_cart_text'] = pll__('Додати у кошик', 'astra');
    }
    return $args;
}

/* 2. Фильтры для изменения текста кнопки на странице товара и в архиве */
add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'custom_add_to_cart_text', 10, 2 );
function custom_add_to_cart_text( $text, $product = null ) {
    // Если товара нет в наличии или цена не установлена, возвращаем стандартный текст WooCommerce
    if ( $product && ( ! $product->is_in_stock() || $product->get_price() === '' ) ) {
        return $text;
    }
    return pll__('Додати у кошик', 'astra');
}

/* 3. Исправление смены текста после AJAX-загрузки */
add_action('wp_footer', 'custom_fix_ajax_add_to_cart_text');
function custom_fix_ajax_add_to_cart_text() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $('.add_to_cart_button').each(function() {
                    var btn = $(this);
                    // Меняем текст только для тех кнопок, где установлен data-атрибут
                    if ( btn.attr('data-add_to_cart_text') ) {
                        btn.text( btn.attr('data-add_to_cart_text') );
                    }
                });
            }, 500);
        });
    </script>
    <?php
}

// Размерная сетка в вариациях
function enqueue_size_chart_shop_script() {
    wp_enqueue_script(
        'size-chart-shop',
        get_stylesheet_directory_uri() . '/js/size-chart-shop.js',
        array('jquery'),
        null,
        true
    );

    // Получаем текущий язык сайта
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'uk';

    // ID украинской страницы "Розмірна сітка"
    $base_page_id = 7058;

    // Передаём текст и ссылку в JS
    $data = [
        'size_chart_text' => pll__('Розмірна сітка'),
        'size_chart_url'  => get_permalink(pll_get_post($base_page_id, $lang)),
    ];

    wp_localize_script('size-chart-shop', 'sizeChartData', $data);
}
add_action('wp_enqueue_scripts', 'enqueue_size_chart_shop_script');

// Убрать сжатие ВП картинок
add_filter( 'jpeg_quality', 'my_filter_img' );
function my_filter_img( $quality ) {  
	return 100;
}
add_filter('wp_editor_set_quality', function() {
    return 100; // Качество 100%
});

// Показать только подкатегории первого уровня (дети текущей категории)
add_action('woocommerce_archive_description', 'display_direct_child_categories_only', 15);
function display_direct_child_categories_only() {
    if ( is_shop() || is_product_category() ) {
        $category_id = 0;

        if ( is_product_category() ) {
            $category = get_queried_object();
            $category_id = $category->term_id;
        }

        $child_categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'parent'     => $category_id,
            'hide_empty' => true,
        ));

        if (!empty($child_categories) && !is_wp_error($child_categories)) {
            echo '<div class="subcategory-list">'; // ← сохранили твой класс
            foreach ($child_categories as $child_category) {
                $link = get_term_link($child_category);
                $thumbnail_id = get_term_meta($child_category->term_id, 'thumbnail_id', true);
                $image = wp_get_attachment_url($thumbnail_id);
                $image_html = $image ? '<img src="' . esc_url($image) . '" alt="' . esc_attr($child_category->name) . '">' : '';

                echo '<div class="subcategory-item">';
                echo '<a href="' . esc_url($link) . '">' . $image_html;
                echo '<span>' . esc_html($child_category->name) . '</span></a>';
                echo '</div>';
            }
            echo '</div>';
        }
    }
}


// Замена иконки миникорзины
function custom_astra_cart_icon($output, $icon) {
    if ($icon === 'cart') {
        $output = '<span class="ast-icon icon-cart">
            <svg class="" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.67394 16.7655C8.67509 16.7732 8.67638 16.781 8.6778 16.7887L8.97103 18.548C9.23891 20.1553 10.6295 21.3333 12.259 21.3333H24.3333C24.7015 21.3333 25 21.0349 25 20.6667C25 20.2985 24.7015 20 24.3333 20H12.259C11.2813 20 10.4469 19.2932 10.2862 18.3288L10.1203 17.3333H24.3333C24.631 17.3333 24.8926 17.136 24.9743 16.8498L27.641 7.51648C27.7627 7.0906 27.4429 6.66667 27 6.66667H8.34034C8.02537 5.12039 6.66342 4 5.07433 4H4.66667C4.29848 4 4 4.29848 4 4.66667C4 5.03486 4.29848 5.33333 4.66667 5.33333H5.07433C6.05201 5.33333 6.88639 6.04016 7.04712 7.00454L8.67394 16.7655ZM9.89809 16L8.56476 8H26.1162L23.8305 16H9.89809ZM11 28C9.52724 28 8.33334 26.8061 8.33334 25.3333C8.33334 23.8605 9.52724 22.6666 11 22.6666C12.4728 22.6666 13.6667 23.8605 13.6667 25.3333C13.6667 26.8061 12.4728 28 11 28ZM12.3333 25.3333C12.3333 26.0697 11.7364 26.6667 11 26.6667C10.2636 26.6667 9.66666 26.0697 9.66666 25.3333C9.66666 24.597 10.2636 24 11 24C11.7364 24 12.3333 24.597 12.3333 25.3333ZM21 28C19.5272 28 18.3333 26.8061 18.3333 25.3333C18.3333 23.8606 19.5272 22.6667 21 22.6667C22.4728 22.6667 23.6667 23.8606 23.6667 25.3333C23.6667 26.8061 22.4728 28 21 28ZM22.3333 25.3333C22.3333 26.0697 21.7364 26.6667 21 26.6667C20.2636 26.6667 19.6667 26.0697 19.6667 25.3333C19.6667 24.597 20.2636 24 21 24C21.7364 24 22.3333 24.597 22.3333 25.3333Z" fill="#ffffff"></path>
            </svg>
        </span>';
    }
    return $output;
}
add_filter('astra_svg_icon', 'custom_astra_cart_icon', 10, 2);

// Розмірна сітка после вариации размеров
function enqueue_size_chart_script() {
    // Подключаем JS-файл
    wp_enqueue_script(
        'size-chart-link',
        get_stylesheet_directory_uri() . '/js/size-chart-link.js',
        array('jquery'),
        null,
        true
    );
    // Получаем текущий язык
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'uk';
    $base_page_id = 7058; // ID украинской страницы “Розмірна сітка”

    $data = [
        'size_chart_text' => pll__('Розмірна сітка'),
        'size_chart_url'  => get_permalink(pll_get_post($base_page_id, $lang)),
    ];

    // Передаём в скрипт
    wp_localize_script('size-chart-link', 'sizeChartData', $data);
}
add_action('woocommerce_after_variations_form', 'enqueue_size_chart_script');


// Показать аттрибуты под вариациями (для вариативных и простых товаров)
function display_non_variation_attributes_in_table() {
    if (!is_product()) {
        return; // Отключаем на страницах категорий и других страницах
    }

    global $product, $wpdb;

    if (!$product) {
        return;
    }

    $attributes_data = [];

    // Перебираем все атрибуты продукта (для вариативных и простых товаров)
    foreach ($product->get_attributes() as $attribute_name => $attribute) {
        // Если атрибут используется для вариаций, пропускаем его (т.к. он уже выводится в вариативном блоке)
        if (method_exists($attribute, 'get_variation') && $attribute->get_variation()) {
            continue;
        }
        $taxonomy = wc_attribute_taxonomy_name($attribute_name);
        $terms = wc_get_product_terms($product->get_id(), $attribute_name, ['fields' => 'all']);

        if (!empty($terms)) {
            $is_public = $wpdb->get_var($wpdb->prepare(
                "SELECT attribute_public FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                str_replace('pa_', '', $attribute_name)
            ));

            if (!$is_public) {
                continue;
            }

            $attribute_label = wc_attribute_label($attribute_name);
            $attribute_value = esc_html(implode(', ', wp_list_pluck($terms, 'name')));

            $archive_url = '';
            if (taxonomy_exists($taxonomy)) {
                $archive_url = get_term_link($terms[0]->term_id, $taxonomy);
                if (is_wp_error($archive_url)) {
                    $archive_url = '';
                }
            }

            $attributes_data[] = [
                'name'        => $attribute_label,
                'value'       => $attribute_value,
                'slug'        => esc_attr($terms[0]->slug),
                'archive_url' => esc_url($archive_url),
            ];
        }
    }

    if (!empty($attributes_data)) {
    echo '<script>window.variationAttributesData = ' . json_encode($attributes_data) . ';</script>';
	}
}
add_action('wp_footer', 'display_non_variation_attributes_in_table');


function enqueue_variation_attributes_in_table_script() {
    if (is_product()) {
        wp_enqueue_script(
            'variation-attributes', 
            get_stylesheet_directory_uri() . '/js/variation-attributes.js', 
            array('jquery'), 
            null, 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_variation_attributes_in_table_script');


// Скопировать цену текущей вариации в основную цену товара
function enqueue_update_top_price_script() {
    if (is_product()) { // Подключаем только на странице товара
        wp_enqueue_script(
            'update-top-price', 
            get_stylesheet_directory_uri() . '/js/update-top-price.js', 
            array('jquery'), 
            null, 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_update_top_price_script');


// создание дополнительных табов на товарах
add_filter( 'woocommerce_product_tabs', 'remove_additional_information_tab', 98 );
function remove_additional_information_tab( $tabs ) {
    unset( $tabs['additional_information'] );
    return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'add_custom_information_tab' );
function add_custom_information_tab( $tabs ) {
    $tabs['custom_info_tab'] = array(
        'title'    => pll__( 'Додаткова інформація', 'woocommerce' ),
        'priority' => 50,
        'callback' => 'custom_info_tab_content'
    );
    return $tabs;
}

function custom_info_tab_content() {
    global $product;
    echo '<p>' . $product->get_short_description() . '</p>';
}

// Добавляем метабокс с TinyMCE только для украинского языка
// Добавляем метабокс для всех языков
add_action('add_meta_boxes', 'add_shipping_info_meta_box');
function add_shipping_info_meta_box() {
    add_meta_box(
        'shipping_info_meta_box',
        __('Перевезення і доставка', 'astra'),
        'render_shipping_info_meta_box',
        'product',
        'normal',
        'high'
    );
}

// Показываем редактор с поддержкой старого поля для uk
function render_shipping_info_meta_box($post) {
    $lang = function_exists('pll_get_post_language') ? pll_get_post_language($post->ID) : 'uk';

    // Название мета-поля зависит от языка
    $meta_key = ($lang === 'uk') ? '_shipping_tab_content' : "_shipping_tab_content_{$lang}";
    $shipping_tab_content = get_post_meta($post->ID, $meta_key, true);

    wp_editor(
        $shipping_tab_content,
        $meta_key,
        array(
            'textarea_name' => $meta_key,
            'media_buttons' => false,
            'textarea_rows' => 10,
            'teeny'         => true,
        )
    );
}

// Сохраняем контент в зависимости от языка
add_action('save_post', 'save_shipping_info_meta_box');
function save_shipping_info_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $lang = function_exists('pll_get_post_language') ? pll_get_post_language($post_id) : 'uk';
    $meta_key = ($lang === 'uk') ? '_shipping_tab_content' : "_shipping_tab_content_{$lang}";

    if (isset($_POST[$meta_key])) {
        update_post_meta($post_id, $meta_key, wp_kses_post($_POST[$meta_key]));
    }
}

// Устанавливаем дефолт только при создании
add_action('wp_insert_post', 'set_default_shipping_info', 10, 3);
function set_default_shipping_info($post_id, $post, $update) {
    if ($update || $post->post_type !== 'product') return;

    $lang = function_exists('pll_get_post_language') ? pll_get_post_language($post_id) : 'uk';
    $meta_key = ($lang === 'uk') ? '_shipping_tab_content' : "_shipping_tab_content_{$lang}";

    if (!get_post_meta($post_id, $meta_key, true)) {
        $default_texts = [
            'uk' => 'Доставка здійснюється за тарифами компанії-перевізника.
<div class=""><strong>Способи доставки по Україні:</strong></div>
<div class="">- Нова пошта (відділення)</div>
<div class="">- Нова пошта (адресна)</div>
<div class="">- Укрпошта</div>
<div class=""><strong>Способи доставки кордон:</strong></div>
<div class="">- Укрпошта (за тарифами національного перевізника)</div>',

            'en' => 'Delivery is carried out according to the carrier\'s tariffs.
<div class=""><strong>Delivery methods in Ukraine:</strong></div>
<div class="">- Nova Poshta (branch)</div>
<div class="">- Nova Poshta (courier)</div>
<div class="">- Ukrposhta</div>
<div class=""><strong>International delivery:</strong></div>
<div class="">- Ukrposhta (according to the national carrier\'s rates)</div>',
        ];

        $default_text = $default_texts[$lang] ?? '';
        if ($default_text) {
            update_post_meta($post_id, $meta_key, wp_kses_post($default_text));
        }
    }
}

// Добавляем вкладку "Перевезення і доставка" на страницу товара
add_filter('woocommerce_product_tabs', 'add_shipping_info_tab');
function add_shipping_info_tab($tabs) {
    $tabs['shipping_info_tab'] = array(
        'title'    => pll__('Перевезення і доставка'),
        'priority' => 50,
        'callback' => 'render_shipping_info_tab_content'
    );
    return $tabs;
}

// Выводим контент вкладки
function render_shipping_info_tab_content() {
    global $product;

    $lang = function_exists('pll_current_language') ? pll_current_language() : 'uk';
    $meta_key = ($lang === 'uk') ? '_shipping_tab_content' : "_shipping_tab_content_{$lang}";

    $content = get_post_meta($product->get_id(), $meta_key, true);

    if (!empty($content)) {
        echo '<div class="shipping-info-tab-content">';
        echo wp_kses_post($content);
        echo '</div>';
    }
}


// Добавление JavaScript для очищения URL и сброса состояния кнопки "Добавить в корзину". Чтоб не добавлялись товары при обновлении страницы
function enqueue_custom_add_to_cart_script() {
    wp_enqueue_script(
        'custom-add-to-cart', 
        get_stylesheet_directory_uri() . '/js/custom-add-to-cart.js', 
        array(), 
        null, 
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_add_to_cart_script');

add_action( 'wp_footer', 'cf7_anti_spam', 100 );
function cf7_anti_spam() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            const checkboxes = document.querySelectorAll('input.agree');
            checkboxes.forEach(function (cb) {
                cb.checked = false;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });

            const form = document.querySelector('.wpcf7 form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const agree = form.querySelector('input.agree');
                    if (!agree || !agree.checked) {
                        e.preventDefault();
                    }
                });
            }
        }, 1000);
    });
    </script>
    <?php
}

function custom_enqueue_mini_cart_script() {
    if (!wp_is_mobile()) { // Загружаем только для десктопов и планшетов
        wp_enqueue_script(
            'custom-mini-cart', 
            get_stylesheet_directory_uri() . '/js/custom-mini-cart.js', 
            array('jquery'), 
            null, 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'custom_enqueue_mini_cart_script');


function move_account_details_to_dashboard() {
    if ( is_account_page() && !is_wc_endpoint_url() ) {
        wc_get_template( 'myaccount/form-edit-account.php' ); // Загружаем форму редактирования профиля
    }
}
add_action( 'woocommerce_account_dashboard', 'move_account_details_to_dashboard' );


function remove_edit_account_menu_item( $items ) {
    unset( $items['edit-account'] ); // Убираем "Личные данные" из меню
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'remove_edit_account_menu_item' );

function rename_woocommerce_account_menu_items( $items ) {
    $items['dashboard'] = 'Профіль'; // Переименование "Dashboard" (Главная)
	$items['wishlist'] = 'Обране';
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'rename_woocommerce_account_menu_items' );

 // Редактирование плагина Variation Gallery for WooCommerce Settings. Делаем чтоб для каждой вариации с цветом подтягивалась галлерея первой вариации с этим цветом
add_action('woocommerce_save_product_variation', 'custom_copy_variation_gallery_and_thumbnail_by_color', 20, 2);
function custom_copy_variation_gallery_and_thumbnail_by_color( $variation_id, $i ) {
    // Логируем начало обработки вариации
    error_log("custom_copy_variation_gallery_and_thumbnail_by_color: Обработка вариации ID " . $variation_id);
    
    // Получаем текущую галерею вариации (хранится в метаполе woo_variation_gallery_images)
    $current_gallery = get_post_meta( $variation_id, 'woo_variation_gallery_images', true );
    if ( ! empty( $current_gallery ) ) {
        error_log("Вариация $variation_id уже имеет галерею: " . print_r($current_gallery, true));
    }
    
    // Получаем текущее главное изображение (thumbnail) вариации (_thumbnail_id)
    $current_thumbnail = get_post_meta( $variation_id, '_thumbnail_id', true );
    if ( ! empty($current_thumbnail) ) {
        error_log("Вариация $variation_id уже имеет главную картинку: " . $current_thumbnail);
    }
    
    $variation = wc_get_product( $variation_id );
    if ( ! $variation ) {
        error_log("Вариация $variation_id не найдена.");
        return;
    }
    
    // Получаем значение атрибута цвета (pa_kolir) и приводим к нижнему регистру
    $color = strtolower( $variation->get_attribute( 'pa_kolir' ) );
    error_log("Вариация $variation_id, цвет: " . $color);
    if ( empty( $color ) ) {
        error_log("Вариация $variation_id не имеет цвета.");
        return;
    }
    
    // Получаем ID родительского товара
    $parent_id = $variation->get_parent_id();
    
    // Получаем все вариации родительского товара
    $args = array(
        'post_parent' => $parent_id,
        'post_type'   => 'product_variation',
        'numberposts' => -1,
        'post_status' => array( 'private', 'publish' )
    );
    $all_variations = get_posts( $args );
    error_log("Найдено " . count($all_variations) . " вариаций для родительского товара $parent_id");
    
    foreach ( $all_variations as $var_post ) {
        $var_id = $var_post->ID;
        if ( $var_id == $variation_id ) {
            continue;
        }
        $var = wc_get_product( $var_id );
        if ( ! $var ) {
            continue;
        }
        $var_color = strtolower( $var->get_attribute( 'pa_kolir' ) );
        if ( $var_color !== $color ) {
            continue;
        }
        // Если у текущей вариации не заполнена галерея, пробуем скопировать её из найденной вариации
        if ( ( empty( $current_gallery ) || !is_array( $current_gallery ) || count( $current_gallery ) === 0 )
             && ( $gallery = get_post_meta( $var_id, 'woo_variation_gallery_images', true ) )
             && is_array( $gallery ) && count( $gallery ) > 0 ) {
            error_log("Копирование галереи из вариации $var_id в вариацию $variation_id: " . print_r($gallery, true));
            update_post_meta( $variation_id, 'woo_variation_gallery_images', $gallery );
            $current_gallery = $gallery;
        }
        // Если у текущей вариации не заполнено главное изображение, пробуем скопировать его
        if ( empty( $current_thumbnail ) && ( $var_thumbnail = get_post_meta( $var_id, '_thumbnail_id', true ) ) ) {
            error_log("Копирование главного изображения из вариации $var_id в вариацию $variation_id: " . $var_thumbnail);
            update_post_meta( $variation_id, '_thumbnail_id', $var_thumbnail );
            $current_thumbnail = $var_thumbnail;
        }
        // Если оба поля уже заполнены, выходим из цикла
        if ( ! empty( $current_gallery ) && ! empty( $current_thumbnail ) ) {
            break;
        }
    }
}

function disable_ajax_variation_loading() {
    return 9999; // Загружаем все вариации сразу
}
add_filter( 'woocommerce_admin_meta_boxes_variations_per_page', 'disable_ajax_variation_loading' );

// С мобильных устройств разворачиваем список с товарами и доставками на странице чекаута.
function expand_checkout_review() {
    if (is_checkout()) {
        wp_add_inline_script('jquery', "
            window.onload = function() {
                if (window.innerWidth <= 768) { // Только на мобильных
                    let orderReviewToggle = document.getElementById('ast-order-review-toggle');
                    if (orderReviewToggle) {
                        orderReviewToggle.click();
                    }
                }
            };
        ");
    }
}
add_action('wp_enqueue_scripts', 'expand_checkout_review');

// Редирект стандартного поиска вордпресс на шаблон архива товаров
function redirect_search_to_product_type() {
    if (is_search() && !isset($_GET['post_type'])) {
        wp_redirect(home_url("/?s=" . get_query_var('s') . "&post_type=product"));
        exit();
    }
}
add_action('template_redirect', 'redirect_search_to_product_type');


// Показывать в похожих товарах только товары из текущей нижней категории
add_filter( 'woocommerce_related_products', 'custom_related_products_with_fallback', 9999, 3 );
function custom_related_products_with_fallback( $related_posts, $product_id, $args ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error($terms) ) return $related_posts;

	// Определяем самую глубокую категорию
	$deepest_term = null;
	$max_depth = -1;

	foreach ( $terms as $term ) {
		$depth = 0;
		$parent = $term->parent;
		while ( $parent != 0 ) {
			$depth++;
			$parent_term = get_term( $parent, 'product_cat' );
			if ( ! $parent_term || is_wp_error($parent_term) ) break;
			$parent = $parent_term->parent;
		}
		if ( $depth > $max_depth ) {
			$max_depth = $depth;
			$deepest_term = $term;
		}
	}

	if ( ! $deepest_term ) return $related_posts;

	// Функция: пробуем получить связанные товары
	$get_related_from_term = function( $term_id ) use ( $args, $product_id ) {
		$query = new WP_Query([
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $args['posts_per_page'] ?? 20, // безопасно
			'post__not_in'        => [$product_id],
			'ignore_sticky_posts' => true,
			'orderby'             => $args['orderby'] ?? 'rand',   // безопасно
			'tax_query' => [[
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => [$term_id],
				'include_children' => false,
			]]
		]);
		return wp_list_pluck( $query->posts, 'ID' );
	};

	$term = $deepest_term;
	while ( $term ) {
		$related = $get_related_from_term( $term->term_id );
		if ( ! empty($related) ) return $related;

		$term = $term->parent ? get_term( $term->parent, 'product_cat' ) : null;
	}

	return $related_posts;
}


/**
 * Поиск категорий по ID в админке (при добавлении в меню).
 */
function custom_search_product_cat_by_id_pre_get_terms( $query ) {
    if ( ! is_admin() ) {
        return;
    }

    // Проверяем, что таксономия - product_cat, и мы в админке
    $taxonomies = isset( $query->query_vars['taxonomy'] ) ? (array) $query->query_vars['taxonomy'] : array();
    if ( ! in_array( 'product_cat', $taxonomies, true ) ) {
        return;
    }

    // Извлекаем строку поиска
    $search = isset( $query->query_vars['search'] ) ? $query->query_vars['search'] : '';
    // Иногда используется 'name__like'
    if ( empty( $search ) && isset( $query->query_vars['name__like'] ) ) {
        $search = $query->query_vars['name__like'];
    }
    // Иногда используется параметр 's'
    if ( empty( $search ) && isset( $_REQUEST['s'] ) ) {
        $search = sanitize_text_field( $_REQUEST['s'] );
    }

    // Проверяем, состоит ли строка только из цифр
    if ( ctype_digit( $search ) ) {
        $cat_id = intval( $search );
        $term = get_term( $cat_id, 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) {
            // Убираем обычный поиск
            $query->query_vars['search'] = '';
            $query->query_vars['name__like'] = '';

            // Ограничиваем результат только найденной категорией
            $query->query_vars['include'] = array( $cat_id );
        }
    }
}
add_action( 'pre_get_terms', 'custom_search_product_cat_by_id_pre_get_terms', 30 );



/**
 * Автоматическое добавление всех дочерних категорий (и их потомков) в меню,
 * если в меню добавляется категория товара.
 * Работает только для меню с заданным ID.

add_action('wp_update_nav_menu_item', 'auto_add_child_product_cats_to_specific_menu', 20, 3);
function auto_add_child_product_cats_to_specific_menu($menu_id, $menu_item_db_id, $args) {
    // Замените на фактический ID меню, для которого нужно автодобавление
    $allowed_menu_id = 7;
    
    if ($menu_id != $allowed_menu_id) {
        return;
    }
    
    // Получаем данные только если пункт меню — это таксономия product_cat
    $menu_item = wp_setup_nav_menu_item(get_post($menu_item_db_id));
    if ($menu_item->type !== 'taxonomy' || $menu_item->object !== 'product_cat') {
        return;
    }
    
    $parent_cat_id = (int) $menu_item->object_id;
    
    // Получаем прямых детей выбранной категории
    $descendants = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_cat_id,
    ]);
    
    if (!empty($descendants) && !is_wp_error($descendants)) {
        foreach ($descendants as $child_term) {
            add_product_cat_menu_item_recursive($child_term, $menu_id, $menu_item_db_id);
        }
    }
}


function add_product_cat_menu_item_recursive($term, $menu_id, $menu_item_parent = 0) {
    // Проверка на дублирование – не добавляем, если такая категория уже существует в меню.
    $existing_items = wp_get_nav_menu_items($menu_id);
    if (is_array($existing_items)) {
        foreach ($existing_items as $item) {
            if ($item->type === 'taxonomy' && $item->object === 'product_cat' && $item->object_id == $term->term_id) {
                return;
            }
        }
    }
    
    // Формируем данные для нового пункта меню
    $item_data = [
        'menu-item-object-id'   => $term->term_id,
        'menu-item-object'      => 'product_cat',
        'menu-item-type'        => 'taxonomy',
        'menu-item-status'      => 'publish',
        'menu-item-parent-id'   => $menu_item_parent,
    ];
    
    // Добавляем элемент меню, получаем его ID
    $new_item_id = wp_update_nav_menu_item($menu_id, 0, $item_data);
    
    // Рекурсивно добавляем потомков данной категории
    $children = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $term->term_id,
    ]);
    
    if (!empty($children) && !is_wp_error($children)) {
        foreach ($children as $child) {
            add_product_cat_menu_item_recursive($child, $menu_id, $new_item_id);
        }
    }
}

 */

// Слайдер related products в карточке товара
add_action('wp_enqueue_scripts', 'enqueue_related_carousel_script');
function enqueue_related_carousel_script() {
	if (is_product()) {
		wp_enqueue_script(
			'related-carousel',
			get_stylesheet_directory_uri() . '/js/related-carousel.js',
			array(),
			'1.0',
			true
		);
	}
}

// Убрать title при наведению на картинки
add_action( 'wp_footer', 'force_remove_all_title_attributes' );
function force_remove_all_title_attributes() {
	echo "<script>
	(function removeTitlesLoop() {
		document.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));
		setTimeout(removeTitlesLoop, 1000); // повтор каждые 1 сек
	})();
	</script>";
}

// Удалить видео на главной с мобильных
add_action( 'wp_enqueue_scripts', 'enqueue_mobile_video_removal_script' );
function enqueue_mobile_video_removal_script() {
    // Только на главной
    if ( is_front_page() || is_home() ) {

        // Только если ширина экрана < 768 — через условие внутри JS
        wp_enqueue_script(
            'remove-mobile-video',
            get_stylesheet_directory_uri() . '/js/remove-mobile-video.js',
            array(), // зависимости
            null,    // версия
            true     // в футере
        );
    }
}

// Переводы строк
add_action('init', function () {
    if (function_exists('pll_register_string')) {
        pll_register_string('shipping_tab_title', 'Перевезення і доставка', 'woocommerce');
		pll_register_string('custom_info_tab_title', 'Додаткова інформація', 'woocommerce');
		pll_register_string('preorder_availability', 'Доступно за замовленням. Термін виготовлення 15 – 25 робочих днів.', 'woocommerce');
		pll_register_string('add_to_cart_button', 'Додати у кошик', 'woocommerce');
		pll_register_string('size_chart_link_text', 'Розмірна сітка', 'woocommerce');
    }
});

// Замена символа валюты на англ.
add_filter('woocommerce_currency_symbol', 'custom_grivna_symbol_for_english', 10, 2);
function custom_grivna_symbol_for_english($currency_symbol, $currency) {
    if ($currency === 'UAH' && function_exists('pll_current_language') && pll_current_language() === 'en') {
        return '₴';
    }

    return $currency_symbol;
}

// Протокол вайбер
add_filter( 'kses_allowed_protocols', 'add_viber_to_allowed_protocols' );

function add_viber_to_allowed_protocols( $protocols ) {
	$protocols[] = 'viber';

	return $protocols;
}

// Key CRM подтягивать оплату
add_action('woocommerce_payment_complete', 'send_payment_to_keycrm');

function send_payment_to_keycrm($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $keycrm_order_id = $order->get_meta('_keycrm_order_id');
    if (!$keycrm_order_id) return;

    // Получение API-ключа из настроек плагина
    $settings = get_option('woocommerce_integration-keycrm_settings');
    $api_key = $settings['api_key'] ?? null;
    if (!$api_key) return;

    // Данные оплаты
    $amount         = (float) $order->get_total();
    $payment_method = $order->get_payment_method_title(); // Например, 'LiqPay'
    $payment_date   = current_time('Y-m-d H:i:s'); // В формате MySQL
    $description    = 'Оплата замовлення #' . $order->get_order_number();

    // Если знаешь ID метода оплаты в KeyCRM — подставь:
    $payment_method_id = 13; // Заменить на правильный ID из KeyCRM

    $response = wp_remote_post("https://openapi.keycrm.app/v1/order/{$keycrm_order_id}/payment", [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'payment_method_id' => $payment_method_id,
            'payment_method'    => $payment_method,
            'amount'            => $amount,
            'status'            => 'paid',
            'description'       => $description,
            'payment_date'      => $payment_date,
        ]),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('❌ KeyCRM API error: ' . $response->get_error_message());
    } else {
        error_log('✅ Payment sent to KeyCRM: ' . wp_remote_retrieve_body($response));
    }
}

// Редирект старой ссылки на фартухи
add_action('template_redirect', function() {
    $current_url = trim($_SERVER['REQUEST_URI'], '/');

    if ($current_url === '5fa542ad419c554222f774c7-5fcf8e00cdcaed0b37bc7471') {
        wp_redirect('https://i-chef.com.ua/product-category/horeca/kuhnya/fartuhy/', 301);
        exit;
    }
});

// подключить Расширения
function custom_mime_types( $mimes ) {
    $mimes['woff'] = 'font/woff';
    $mimes['woff2'] = 'font/woff2';
    return $mimes;
}
add_filter( 'upload_mimes', 'custom_mime_types' );

// Убрать notices, когда добавляю товар в корзину и открываю корзину вверху сообщение об успешном добавлении
add_action('template_redirect', function () {
    if (is_cart()) {
        ob_start(function($buffer) {
            // Заменить на регулярку, если сообщение может меняться
            $pattern = '/<div class="woocommerce-message.*has been added to your cart.*?<\/div>/is';
            $buffer = preg_replace($pattern, '', $buffer);
            // Для украинского текста:
            $pattern2 = '/<div class="woocommerce-message.*додано до вашого кошика.*?<\/div>/is';
            $buffer = preg_replace($pattern2, '', $buffer);
            return $buffer;
        });
    }
});

// Надпись доступно по предзаказу
if ( ! function_exists('ichef_t') ) {
    function ichef_t( $text ) {
        return function_exists('pll__') ? pll__( $text ) : __( $text, 'woocommerce' );
    }
}

/**
 * Страница товара: текст про предзаказ (когда реально backorder).
 */
function ich_custom_preorder_availability_text( $availability, $product ) {
    if ( ! $product instanceof WC_Product ) return $availability;

    if ( $product->backorders_require_notification() && $product->is_on_backorder( 1 ) ) {
        $availability = ichef_t('Доступно за замовленням. Термін виготовлення 15 – 25 робочих днів.');
    }
    return $availability;
}
add_filter( 'woocommerce_get_availability_text', 'ich_custom_preorder_availability_text', 10, 2 );

/**
 * Страница товара: скрыть "Наявність: …", НО оставить наш якорь для JS.
 * Работает и для вариаций (через AJAX), и для simple.
 */
add_filter( 'woocommerce_get_stock_html', function( $html, $product ) {
    if ( ! $product instanceof WC_Product ) return $html;

    $has_positive = $product->managing_stock()
        ? ( (int) $product->get_stock_quantity() > 0 )
        : $product->is_in_stock();

    if ( $has_positive ) {
        // скрываем стандарт, оставляем якорь для JS
        return '<span id="ichef-backorder-anchor"></span>';
    }
    return $html;
}, 999, 2);

// Фоллбек на старый фильтр (некоторые темы его используют)
add_filter( 'woocommerce_stock_html', function( $html, $availability, $product ) {
    if ( ! $product instanceof WC_Product ) return $html;

    $has_positive = $product->managing_stock()
        ? ( (int) $product->get_stock_quantity() > 0 )
        : $product->is_in_stock();

    if ( $has_positive ) {
        return '<span id="ichef-backorder-anchor"></span>';
    }
    return $html;
}, 999, 3);

/**
 * Кошик/міні/чекаут: нужные подсказки
 * - если stock=0 (и notify) → "Доступно за замовленням…"
 * - если stock>0, но cart_qty>stock (и notify) → "Частина заказу виготовлятиметься 15 - 25 днів."
 */
if ( ! function_exists('ichef_backorder_qty_for_cart_item') ) {
    function ichef_backorder_qty_for_cart_item( WC_Product $product, int $cart_qty ): int {
        if ( ! $product->managing_stock() ) return 0;
        if ( ! $product->backorders_allowed() ) return 0;
        $stock = max( 0, (int) $product->get_stock_quantity() );
        return max( 0, $cart_qty - $stock );
    }
}
if ( ! function_exists('ichef_backorder_message_for_cart_item') ) {
    function ichef_backorder_message_for_cart_item( WC_Product $product, int $cart_qty ): string {
        if ( ! $product || ! $product->backorders_require_notification() ) return '';

        if ( ! $product->managing_stock() ) {
            return $product->is_on_backorder( $cart_qty )
                ? ichef_t('Доступно за замовленням. Термін виготовлення 15 – 25 робочих днів.')
                : '';
        }

        $stock     = max( 0, (int) $product->get_stock_quantity() );
        $extra_qty = ichef_backorder_qty_for_cart_item( $product, $cart_qty );
        if ( $extra_qty <= 0 ) return '';

        if ( $stock === 0 ) {
            return ichef_t('Доступно за замовленням. Термін виготовлення 15 – 25 робочих днів.');
        }
        return ichef_t('Частина заказу виготовлятиметься 15 - 25 днів.');
    }
}

add_filter( 'woocommerce_cart_item_name', function( $product_name, $cart_item ) {
    if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) return $product_name;
    $product  = $cart_item['data'];
    $cart_qty = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
    $msg      = ichef_backorder_message_for_cart_item( $product, $cart_qty );
    if ( $msg !== '' ) {
        $product_name .= '<p class="ichef-backorder-note" style="color:#e67e22;">' . wp_kses_post( $msg ) . '</p>';
    }
    return $product_name;
}, 10, 2 );

add_filter( 'woocommerce_widget_cart_item_quantity', function( $quantity_html, $cart_item ) {
    if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) return $quantity_html;
    $product  = $cart_item['data'];
    $cart_qty = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
    $msg      = ichef_backorder_message_for_cart_item( $product, $cart_qty );
    if ( $msg !== '' ) {
        $quantity_html .= '<div class="ichef-backorder-note" style="color:#e67e22; margin-top:2px;">' . wp_kses_post( $msg ) . '</div>';
    }
    return $quantity_html;
}, 10, 2 );

add_filter( 'woocommerce_checkout_cart_item_quantity', function( $quantity_html, $cart_item ) {
    if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) return $quantity_html;
    $product  = $cart_item['data'];
    $cart_qty = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
    $msg      = ichef_backorder_message_for_cart_item( $product, $cart_qty );
    if ( $msg !== '' ) {
        $quantity_html .= '<div class="ichef-backorder-note" style="color:#e67e22; margin-top:2px;">' . wp_kses_post( $msg ) . '</div>';
    }
    return $quantity_html;
}, 10, 2 );

/**
 * Проставим hidden stock для simple (чтобы JS знал текущий остаток)
 */
add_action( 'woocommerce_single_product_summary', function () {
    if ( ! is_product() ) return;
    global $product;
    if ( ! $product instanceof WC_Product || ! $product->is_type('simple') ) return;

    $stock = $product->managing_stock() ? max( 0, (int) $product->get_stock_quantity() ) : 0;
    // Даже если 0 — пусть JS знает число
    echo '<input type="hidden" id="ichef-stock-qty" value="' . esc_attr( $stock ) . '">';
}, 4 );

/**
 * Подключаем JS и передаём карту остатков вариаций (и фразу), чтобы на карточке товара
 * показывать "Частина заказу…" когда qty > stock.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_product() ) return;

    $product = wc_get_product( get_queried_object_id() );
    if ( ! $product instanceof WC_Product ) return;

    $variation_map = [];
    if ( $product->is_type('variable') ) {
        foreach ( $product->get_children() as $vid ) {
            $v = wc_get_product( $vid );
            if ( ! $v ) continue;
            $variation_map[ $vid ] = $v->managing_stock() ? max( 0, (int) $v->get_stock_quantity() ) : 0;
        }
    }

    wp_enqueue_script(
        'ichef-backorder-hint',
        get_stylesheet_directory_uri() . '/js/ichef-backorder-hint.js',
        [ 'jquery', 'wc-add-to-cart-variation' ],
        '1.7.0',
        true
    );

    wp_localize_script( 'ichef-backorder-hint', 'ichefBackorder', [
        'template'        => ichef_t('Частина заказу виготовлятиметься 15 - 25 днів.'),
        'variationStocks' => $variation_map,
    ] );
});
