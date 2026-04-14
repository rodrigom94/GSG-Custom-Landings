<?php

if (! defined('ABSPATH')) {
    exit;
}

$plugin = GSGCL_Plugin::instance();
$page_id = get_queried_object_id();
$landing_id = $plugin->extract_landing_id_from_template(get_page_template_slug($page_id));
$hide_theme_chrome = $landing_id ? $plugin->should_hide_theme_chrome($landing_id) : false;

if (! $hide_theme_chrome) {
    get_header();
}

GSGCL_Plugin::instance()->renderer()->render_current_landing();

if (! $hide_theme_chrome) {
    get_footer();
}