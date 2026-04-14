<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Plugin
{
    private static $instance = null;

    private $settings;

    private $admin;

    private $renderer;

    private $form_handler;

    private $section_library;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->settings = new GSGCL_Settings();
        $this->renderer = new GSGCL_Renderer($this);
        $this->admin = new GSGCL_Admin($this);
        $this->form_handler = new GSGCL_Form_Handler($this);
        $this->section_library = new GSGCL_Section_Library($this, new GSGCL_Section_AI());

        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_shortcode'));
        add_filter('theme_page_templates', array($this, 'register_dynamic_page_templates'), 20, 4);
        add_filter('template_include', array($this, 'resolve_dynamic_template'), 99);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('body_class', array($this, 'add_body_class'));
    }

    public function settings()
    {
        return $this->settings;
    }

    public function admin()
    {
        return $this->admin;
    }

    public function renderer()
    {
        return $this->renderer;
    }

    public function form_handler()
    {
        return $this->form_handler;
    }

    public function section_library()
    {
        return $this->section_library;
    }

    public function register_post_types()
    {
        register_post_type(
            'gsg_landing',
            array(
                'labels' => array(
                    'name' => __('GSG Landings', 'gsg-custom-landings'),
                    'singular_name' => __('GSG Landing', 'gsg-custom-landings'),
                    'add_new' => __('Nueva landing', 'gsg-custom-landings'),
                    'add_new_item' => __('Crear landing o quiz', 'gsg-custom-landings'),
                    'edit_item' => __('Editar landing', 'gsg-custom-landings'),
                    'new_item' => __('Nueva landing', 'gsg-custom-landings'),
                    'view_item' => __('Ver landing', 'gsg-custom-landings'),
                    'search_items' => __('Buscar landings', 'gsg-custom-landings'),
                    'not_found' => __('No hay landings registradas.', 'gsg-custom-landings'),
                    'menu_name' => __('GSG Landings', 'gsg-custom-landings'),
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'supports' => array('title'),
                'menu_icon' => 'dashicons-layout',
                'capability_type' => 'post',
                'map_meta_cap' => true,
            )
        );

        register_post_type(
            'gsg_submission',
            array(
                'labels' => array(
                    'name' => __('Leads GSG', 'gsg-custom-landings'),
                    'singular_name' => __('Lead GSG', 'gsg-custom-landings'),
                    'menu_name' => __('Leads GSG', 'gsg-custom-landings'),
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=gsg_landing',
                'supports' => array('title'),
                'capability_type' => 'post',
                'map_meta_cap' => true,
            )
        );
    }

    public function register_shortcode()
    {
        add_shortcode('gsg_custom_landing', array($this->renderer, 'render_shortcode'));
    }

    public function register_dynamic_page_templates($templates, $theme, $post, $post_type)
    {
        if ('page' !== $post_type) {
            return $templates;
        }

        foreach ($this->get_landings() as $landing) {
            $templates[$this->get_template_slug($landing->ID)] = sprintf(
                'GSG Custom | %s',
                $landing->post_title
            );
        }

        return $templates;
    }

    public function resolve_dynamic_template($template)
    {
        if (! is_singular('page')) {
            return $template;
        }

        $page_id = get_queried_object_id();
        $template_slug = get_page_template_slug($page_id);
        $landing_id = $this->extract_landing_id_from_template($template_slug);

        if (! $landing_id) {
            return $template;
        }

        $landing = get_post($landing_id);
        if (! $landing || 'gsg_landing' !== $landing->post_type) {
            return $template;
        }

        $this->renderer->set_current_context($landing_id, $page_id);

        return GSGCL_PATH . 'templates/landing-shell.php';
    }

    public function enqueue_frontend_assets()
    {
        if (! is_singular('page')) {
            return;
        }

        $page_id = get_queried_object_id();
        $template_slug = get_page_template_slug($page_id);
        $landing_id = $this->extract_landing_id_from_template($template_slug);

        if (! $landing_id) {
            return;
        }

        wp_enqueue_style(
            'gsgcl-frontend',
            GSGCL_URL . 'assets/css/gsgcl-frontend.css',
            array(),
            GSGCL_VERSION
        );
    }

    public function add_body_class($classes)
    {
        if (! is_singular('page')) {
            return $classes;
        }

        $page_id = get_queried_object_id();
        $landing_id = $this->extract_landing_id_from_template(get_page_template_slug($page_id));

        if ($landing_id) {
            $classes[] = 'gsgcl-page';
            $classes[] = 'gsgcl-page-' . $landing_id;
        }

        return $classes;
    }

    public function get_landings()
    {
        return get_posts(
            array(
                'post_type' => 'gsg_landing',
                'post_status' => array('publish', 'draft', 'private'),
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            )
        );
    }

    public function get_template_slug($landing_id)
    {
        return 'gsg-custom-landing-' . absint($landing_id);
    }

    public function extract_landing_id_from_template($template_slug)
    {
        if (! is_string($template_slug)) {
            return 0;
        }

        if (! preg_match('/^gsg-custom-landing-(\d+)$/', $template_slug, $matches)) {
            return 0;
        }

        return absint($matches[1]);
    }

    public function get_landing_meta($landing_id, $key, $default = '')
    {
        $value = get_post_meta($landing_id, $key, true);

        if ('' === $value || null === $value) {
            return $default;
        }

        return $value;
    }

    public static function activate()
    {
        $plugin = self::instance();
        $plugin->register_post_types();
        $plugin->section_library()->register_post_type();
        $plugin->create_sample_landing();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    private function create_sample_landing()
    {
        $landing_id = absint(get_option('gsgcl_sample_landing_id', 0));
        $landing = $landing_id ? get_post($landing_id) : null;

        if (! $landing || 'gsg_landing' !== $landing->post_type) {
            $landing = get_page_by_title('Invita a un amigo', OBJECT, 'gsg_landing');
            $landing_id = $landing ? $landing->ID : 0;
        }

        $created_landing = false;
        if (! $landing_id) {
            $landing_id = wp_insert_post(
                array(
                    'post_type' => 'gsg_landing',
                    'post_status' => 'publish',
                    'post_title' => 'Invita a un amigo',
                ),
                true
            );

            if (is_wp_error($landing_id) || ! $landing_id) {
                return;
            }

            $created_landing = true;
        }

        $defaults = $this->admin->get_default_meta_values();
        foreach ($defaults as $meta_key => $meta_value) {
            if ($created_landing || '' === get_post_meta($landing_id, $meta_key, true)) {
                update_post_meta($landing_id, $meta_key, $meta_value);
            }
        }

        $page_id = absint(get_option('gsgcl_sample_page_id', 0));
        $page = $page_id ? get_post($page_id) : null;

        if (! $page || 'page' !== $page->post_type) {
            $page = get_page_by_path('gsg-demo-invita-a-un-amigo', OBJECT, 'page');
            $page_id = $page ? $page->ID : 0;
        }

        if (! $page_id) {
            $page_id = wp_insert_post(
                array(
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_title' => 'Invita a un amigo',
                    'post_name' => 'gsg-demo-invita-a-un-amigo',
                    'post_content' => '',
                ),
                true
            );
        } else {
            wp_update_post(
                array(
                    'ID' => $page_id,
                    'post_status' => 'publish',
                )
            );
        }

        if (! is_wp_error($page_id) && $page_id) {
            update_post_meta($page_id, '_wp_page_template', $this->get_template_slug($landing_id));
            update_post_meta($page_id, 'gsgcl_seeded_demo_page', '1');
        }

        $section_ids = $this->section_library()->ensure_demo_sections($landing_id, $page_id);
        if (! empty($section_ids)) {
            update_post_meta($landing_id, 'gsgcl_section_ids', array_values($section_ids));
            update_post_meta($landing_id, 'gsgcl_sections_schema', $this->build_sections_schema($section_ids));
        }

        update_option('gsgcl_sample_landing_id', $landing_id, false);
        update_option('gsgcl_sample_page_id', $page_id, false);
        update_option('gsgcl_sample_seeded', 1, false);
    }

    private function build_sections_schema($section_ids)
    {
        $schema = array();

        foreach ($section_ids as $index => $section_id) {
            $schema[] = array(
                'section_id' => absint($section_id),
                'order' => $index + 1,
            );
        }

        return $schema;
    }
}