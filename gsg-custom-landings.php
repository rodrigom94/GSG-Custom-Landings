<?php
/**
 * Plugin Name: GSG Custom Landings
 * Description: Landings y quizzes reutilizables con plantillas dinámicas, campos seguros e integraciones configurables.
 * Version: 1.1.5
 * Author: GSG
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: gsg-custom-landings
 */

if (! defined('ABSPATH')) {
    exit;
}

define('GSGCL_VERSION', '1.1.5');
define('GSGCL_FILE', __FILE__);
define('GSGCL_PATH', plugin_dir_path(__FILE__));
define('GSGCL_URL', plugin_dir_url(__FILE__));

require_once GSGCL_PATH . 'includes/class-gsgcl-settings.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-admin.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-form-handler.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-renderer.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-section-ai.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-section-library.php';
require_once GSGCL_PATH . 'includes/class-gsgcl-plugin.php';

register_activation_hook(GSGCL_FILE, array('GSGCL_Plugin', 'activate'));
register_deactivation_hook(GSGCL_FILE, array('GSGCL_Plugin', 'deactivate'));

GSGCL_Plugin::instance();