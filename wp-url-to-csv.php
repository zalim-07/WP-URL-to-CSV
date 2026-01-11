<?php
/*
Plugin Name: URL to CSV
Description: Экспорт ссылок сайта (посты и страницы) в CSV или XML.
Version: 1.0
Author: Zalim
*/

if (!defined('ABSPATH')) {
    exit;
}

// Добавляем пункт меню в админке
add_action('admin_menu', function () {
    add_management_page(
        'URL to CSV or XML',
        'URL to CSV or XML',
        'manage_options',
        'url-to-csv',
        'url_to_csv_admin_page'
    );
});

// Страница плагина
function url_to_csv_admin_page()
{
?>
    <div class="wrap">
        <h1>Экспорт ссылок</h1>

        <p class="description">
            Этот плагин позволяет экспортировать ссылки опубликованных постов и страниц сайта.<br>
            Выберите типы записей и формат (CSV или XML), затем нажмите «Экспортировать».<br>
            Файл будет скачан автоматически.
        </p>

        <form method="post">
            <?php wp_nonce_field('url_to_csv_export', 'url_to_csv_nonce'); ?>

            <h3>Типы записей</h3>
            <label>
                <input type="checkbox" name="url_types[]" value="post" checked>
                Посты
            </label><br>
            <label>
                <input type="checkbox" name="url_types[]" value="page" checked>
                Страницы
            </label>

            <h3>Формат</h3>
            <select name="url_format">
                <option value="csv">CSV</option>
                <option value="xml">XML</option>
            </select>

            <p>
                <input type="submit" name="url_to_csv_export" class="button button-primary" value="Экспортировать">
            </p>
        </form>
    </div>
    <?php
}

// Обработка экспорта
add_action('admin_init', function () {
    if (!isset($_POST['url_to_csv_export'])) {
        return;
    }

    if (
        !isset($_POST['url_to_csv_nonce']) ||
        !wp_verify_nonce($_POST['url_to_csv_nonce'], 'url_to_csv_export')
    ) {
        return;
    }

    if (empty($_POST['url_types'])) {
        return;
    }

    $format = sanitize_text_field($_POST['url_format']);
    $url_types = array_map('sanitize_text_field', $_POST['url_types']);

    $posts = get_posts([
        'post_type'   => $url_types,
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    if ($format === 'csv') {
        url_to_csv_export_csv($posts);
    } elseif ($format === 'xml') {
        url_to_csv_export_xml($posts);
    }

    exit;
});

// CSV экспорт
function url_to_csv_export_csv($posts)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=url-export.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Тип', 'Заголовок', 'URL']);

    foreach ($posts as $post) {
        fputcsv($output, [
            $post->ID,
            $post->post_type,
            $post->post_title,
            get_permalink($post),
        ]);
    }

    fclose($output);
}

// XML экспорт
function url_to_csv_export_xml($posts)
{
    header('Content-Type: text/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename=url-export.xml');

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><items></items>');

    foreach ($posts as $post) {
        $item = $xml->addChild('item');
        $item->addChild('id', $post->ID);
        $item->addChild('type', $post->post_type);
        $item->addChild('title', htmlspecialchars($post->post_title));
        $item->addChild('url', get_permalink($post));
    }

    echo $xml->asXML();
}


register_activation_hook(__FILE__, function () {
    add_option('url_to_csv_activated', true);
});

add_action('admin_notices', function () {
    if (get_option('url_to_csv_activated')) {
    ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Плагин <strong>URL to CSV</strong> активирован!<br>
                Перейдите в <strong>Инструменты → Экспорт ссылок</strong>, чтобы выгрузить ссылки сайта.
            </p>
        </div>
<?php
        delete_option('url_to_csv_activated');
    }
});
