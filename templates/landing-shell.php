<?php

if (! defined('ABSPATH')) {
    exit;
}

$plugin = GSGCL_Plugin::instance();
$page_id = get_queried_object_id();
$landing_id = $plugin->extract_landing_id_from_template(get_page_template_slug($page_id));
$hide_theme_chrome = $landing_id ? $plugin->should_hide_theme_chrome($landing_id) : false;

if ($hide_theme_chrome) {
    ?><!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <?php wp_head(); ?>
    </head>
    <body <?php body_class('gsgcl-standalone-template'); ?>>
    <?php
    wp_body_open();
    GSGCL_Plugin::instance()->renderer()->render_current_landing();
    wp_footer();
    ?>
    </body>
    </html>
    <?php
    return;
}

get_header();
GSGCL_Plugin::instance()->renderer()->render_current_landing();
get_footer();