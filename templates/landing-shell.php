<?php

if (! defined('ABSPATH')) {
    exit;
}

get_header();
GSGCL_Plugin::instance()->renderer()->render_current_landing();
get_footer();