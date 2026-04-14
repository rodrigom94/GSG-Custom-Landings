<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Settings
{
    private $option_name = 'gsgcl_settings';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=gsg_landing',
            __('Ajustes GSG', 'gsg-custom-landings'),
            __('Ajustes', 'gsg-custom-landings'),
            'manage_options',
            'gsgcl-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings()
    {
        register_setting(
            'gsgcl_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'gsgcl_openai_section',
            __('Integración OpenAI', 'gsg-custom-landings'),
            function () {
                echo '<p>' . esc_html__('Configuración global para futuras automatizaciones. La clave se almacena en WordPress y todavía no ejecuta llamadas externas.', 'gsg-custom-landings') . '</p>';
            },
            'gsgcl-settings'
        );

        add_settings_field(
            'openai_api_key',
            __('API Key', 'gsg-custom-landings'),
            array($this, 'render_text_field'),
            'gsgcl-settings',
            'gsgcl_openai_section',
            array(
                'key' => 'openai_api_key',
                'type' => 'password',
                'placeholder' => 'sk-...',
            )
        );

        add_settings_field(
            'openai_model',
            __('Modelo', 'gsg-custom-landings'),
            array($this, 'render_text_field'),
            'gsgcl-settings',
            'gsgcl_openai_section',
            array(
                'key' => 'openai_model',
                'type' => 'text',
                'placeholder' => 'gpt-4.1-mini',
            )
        );

        add_settings_field(
            'openai_timeout',
            __('Timeout (segundos)', 'gsg-custom-landings'),
            array($this, 'render_number_field'),
            'gsgcl-settings',
            'gsgcl_openai_section',
            array(
                'key' => 'openai_timeout',
                'min' => 5,
                'max' => 120,
            )
        );
    }

    public function sanitize_settings($settings)
    {
        $settings = is_array($settings) ? $settings : array();

        return array(
            'openai_api_key' => isset($settings['openai_api_key']) ? sanitize_text_field($settings['openai_api_key']) : '',
            'openai_model' => isset($settings['openai_model']) ? sanitize_text_field($settings['openai_model']) : 'gpt-4.1-mini',
            'openai_timeout' => isset($settings['openai_timeout']) ? max(5, min(120, absint($settings['openai_timeout']))) : 20,
        );
    }

    public function get_settings()
    {
        return wp_parse_args(
            get_option($this->option_name, array()),
            array(
                'openai_api_key' => '',
                'openai_model' => 'gpt-4.1-mini',
                'openai_timeout' => 20,
            )
        );
    }

    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('GSG Custom Landings', 'gsg-custom-landings'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gsgcl_settings_group');
                do_settings_sections('gsgcl-settings');
                submit_button(__('Guardar ajustes', 'gsg-custom-landings'));
                ?>
            </form>
        </div>
        <?php
    }

    public function render_text_field($args)
    {
        $settings = $this->get_settings();
        $key = $args['key'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        ?>
        <input
            type="<?php echo esc_attr($type); ?>"
            class="regular-text"
            name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
            value="<?php echo esc_attr($settings[$key]); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            autocomplete="off"
        />
        <?php
    }

    public function render_number_field($args)
    {
        $settings = $this->get_settings();
        $key = $args['key'];
        ?>
        <input
            type="number"
            class="small-text"
            min="<?php echo esc_attr($args['min']); ?>"
            max="<?php echo esc_attr($args['max']); ?>"
            name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
            value="<?php echo esc_attr($settings[$key]); ?>"
        />
        <?php
    }
}