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
        $this->section_library = new GSGCL_Section_Library($this, new GSGCL_Section_AI($this->settings));

        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
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
                'show_ui' => false,
                'show_in_menu' => false,
                'supports' => array('title'),
                'capability_type' => 'post',
                'map_meta_cap' => true,
            )
        );
    }

    public function register_rest_routes()
    {
        register_rest_route(
            'gsgcl/v1',
            '/landings/(?P<landing_id>\d+)/leads',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'rest_export_landing_leads'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'landing_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
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

        $font_family = $this->sanitize_google_font_family($this->get_landing_meta($landing_id, 'gsgcl_google_font_family', 'Poppins'));

        wp_enqueue_style(
            'gsgcl-google-font',
            $this->build_google_fonts_url($font_family),
            array(),
            null
        );

        wp_add_inline_style(
            'gsgcl-frontend',
            '.gsgcl-shell { --gsgcl-font-family: "' . esc_attr($font_family) . '", "Segoe UI", sans-serif; }'
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

    public function get_landing_submissions($landing_id)
    {
        global $wpdb;

        $landing_id = absint($landing_id);
        if (! $landing_id) {
            return array();
        }

        $submission_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT posts.ID
                FROM {$wpdb->posts} AS posts
                INNER JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
                WHERE posts.post_type = %s
                    AND posts.post_status IN ('private', 'publish')
                    AND meta.meta_key = %s
                    AND meta.meta_value = %d
                ORDER BY posts.post_date DESC, posts.ID DESC",
                'gsg_submission',
                'gsgcl_landing_id',
                $landing_id
            )
        );

        if (empty($submission_ids)) {
            return array();
        }

        $submissions = array();

        foreach ($submission_ids as $submission_id) {
            $submission = get_post((int) $submission_id);

            if ($submission instanceof WP_Post) {
                $submissions[] = $submission;
            }
        }

        return $submissions;
    }

    public function get_submission_export_data($submission)
    {
        $submission = get_post($submission);

        if (! $submission instanceof WP_Post || 'gsg_submission' !== $submission->post_type) {
            return array();
        }

        $payload = get_post_meta($submission->ID, 'gsgcl_payload', true);
        $payload = is_array($payload) ? $payload : array();

        $request_context = get_post_meta($submission->ID, 'gsgcl_request_context', true);
        $request_context = is_array($request_context) ? $request_context : array();

        $raw_meta = $this->get_submission_raw_meta($submission->ID);

        return array(
            'submission_id' => $submission->ID,
            'submission_title' => $submission->post_title,
            'submission_status' => $submission->post_status,
            'submitted_at' => isset($payload['submitted_at']) && '' !== $payload['submitted_at'] ? $payload['submitted_at'] : $submission->post_date,
            'submitted_at_gmt' => $submission->post_date_gmt,
            'landing_id' => absint(get_post_meta($submission->ID, 'gsgcl_landing_id', true)),
            'page_id' => isset($request_context['page_id']) ? absint($request_context['page_id']) : 0,
            'payload' => $payload,
            'request_context' => $request_context,
            'meta' => array(
                'gsgcl_openai_enabled' => (string) get_post_meta($submission->ID, 'gsgcl_openai_enabled', true),
                'gsgcl_openai_context' => (string) get_post_meta($submission->ID, 'gsgcl_openai_context', true),
            ),
            'all_data' => array_merge(
                array(
                    'submission_id' => $submission->ID,
                    'submission_title' => $submission->post_title,
                    'submission_status' => $submission->post_status,
                    'submitted_at' => isset($payload['submitted_at']) && '' !== $payload['submitted_at'] ? $payload['submitted_at'] : $submission->post_date,
                    'submitted_at_gmt' => $submission->post_date_gmt,
                    'landing_id' => absint(get_post_meta($submission->ID, 'gsgcl_landing_id', true)),
                    'page_id' => isset($request_context['page_id']) ? absint($request_context['page_id']) : 0,
                ),
                $payload,
                $request_context,
                array(
                    'gsgcl_openai_enabled' => (string) get_post_meta($submission->ID, 'gsgcl_openai_enabled', true),
                    'gsgcl_openai_context' => (string) get_post_meta($submission->ID, 'gsgcl_openai_context', true),
                )
            ),
            'raw_meta' => $raw_meta,
        );
    }

    public function get_landing_leads_endpoint_url($landing_id)
    {
        return rest_url('gsgcl/v1/landings/' . absint($landing_id) . '/leads');
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

    public function rest_export_landing_leads(WP_REST_Request $request)
    {
        $landing_id = absint($request->get_param('landing_id'));
        $landing = get_post($landing_id);

        if (! $landing instanceof WP_Post || 'gsg_landing' !== $landing->post_type) {
            return new WP_Error(
                'gsgcl_rest_invalid_landing',
                __('La landing solicitada no existe.', 'gsg-custom-landings'),
                array('status' => 404)
            );
        }

        if ('1' !== $this->get_landing_meta($landing_id, 'gsgcl_leads_rest_enabled', '0')) {
            return new WP_Error(
                'gsgcl_rest_disabled',
                __('La exportación REST de leads no está habilitada para esta landing.', 'gsg-custom-landings'),
                array('status' => 403)
            );
        }

        $stored_password = (string) $this->get_landing_meta($landing_id, 'gsgcl_leads_rest_pass', '');
        if ('' === $stored_password) {
            return new WP_Error(
                'gsgcl_rest_missing_password',
                __('La landing no tiene una contraseña REST configurada.', 'gsg-custom-landings'),
                array('status' => 500)
            );
        }

        $provided_password = $this->extract_rest_password($request);
        if ('' === $provided_password || ! hash_equals($stored_password, $provided_password)) {
            return new WP_Error(
                'gsgcl_rest_invalid_password',
                __('Contraseña REST inválida.', 'gsg-custom-landings'),
                array('status' => 401)
            );
        }

        $lead_rows = array();
        foreach ($this->get_landing_submissions($landing_id) as $submission) {
            $lead_rows[] = $this->get_submission_export_data($submission);
        }

        return rest_ensure_response(
            array(
                'landing' => array(
                    'id' => $landing_id,
                    'title' => $landing->post_title,
                    'status' => $landing->post_status,
                    'endpoint' => $this->get_landing_leads_endpoint_url($landing_id),
                ),
                'generated_at' => current_time('mysql'),
                'total_leads' => count($lead_rows),
                'leads' => $lead_rows,
            )
        );
    }

    public function should_hide_theme_chrome($landing_id)
    {
        return '1' === $this->get_landing_meta($landing_id, 'gsgcl_hide_theme_chrome', '0');
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

    private function get_submission_raw_meta($submission_id)
    {
        $meta = get_post_meta($submission_id);
        $normalized_meta = array();

        foreach ($meta as $meta_key => $values) {
            if (! is_array($values)) {
                $normalized_meta[$meta_key] = maybe_unserialize($values);
                continue;
            }

            if (1 === count($values)) {
                $normalized_meta[$meta_key] = maybe_unserialize($values[0]);
                continue;
            }

            $normalized_meta[$meta_key] = array_map('maybe_unserialize', $values);
        }

        return $normalized_meta;
    }

    private function extract_rest_password(WP_REST_Request $request)
    {
        $header_password = trim((string) $request->get_header('x-gsgcl-pass'));
        if ('' !== $header_password) {
            return sanitize_text_field($header_password);
        }

        $authorization = trim((string) $request->get_header('authorization'));
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return sanitize_text_field($matches[1]);
        }

        $query_password = $request->get_param('pass');

        return is_scalar($query_password) ? sanitize_text_field((string) $query_password) : '';
    }

    private function sanitize_google_font_family($value)
    {
        $value = preg_replace('/[^A-Za-z0-9\s_-]/', '', (string) $value);
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value ? $value : 'Poppins';
    }

    private function build_google_fonts_url($font_family)
    {
        $family_query = str_replace(' ', '+', $font_family) . ':wght@400;500;600;700;800';

        return add_query_arg(
            array(
                'family' => $family_query,
                'display' => 'swap',
            ),
            'https://fonts.googleapis.com/css2'
        );
    }
}