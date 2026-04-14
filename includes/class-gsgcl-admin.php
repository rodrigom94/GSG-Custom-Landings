<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Admin
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_gsg_landing', array($this, 'save_landing_meta'));
    }

    public function register_meta_boxes()
    {
        add_meta_box(
            'gsgcl_landing_setup',
            __('Configuración general', 'gsg-custom-landings'),
            array($this, 'render_setup_metabox'),
            'gsg_landing',
            'normal',
            'high'
        );

        add_meta_box(
            'gsgcl_landing_content',
            __('Contenido de la landing', 'gsg-custom-landings'),
            array($this, 'render_content_metabox'),
            'gsg_landing',
            'normal',
            'default'
        );

        add_meta_box(
            'gsgcl_landing_integrations',
            __('Integraciones', 'gsg-custom-landings'),
            array($this, 'render_integrations_metabox'),
            'gsg_landing',
            'side',
            'default'
        );
    }

    public function get_default_meta_values()
    {
        return array(
            'gsgcl_content_type' => 'landing',
            'gsgcl_layout_variant' => 'referral',
            'gsgcl_hero_title' => 'Invita a un amigo',
            'gsgcl_hero_highlight' => 'y ambos ganan',
            'gsgcl_hero_description' => 'Estudiar en el extranjero es mejor cuando se comparte. Recomienda y obtén beneficios exclusivos.',
            'gsgcl_primary_cta_label' => '¡Registrar a mi amigo!',
            'gsgcl_primary_cta_url' => '#gsgcl-form',
            'gsgcl_secondary_cta_label' => 'Ver beneficio',
            'gsgcl_secondary_cta_url' => '#gsgcl-benefit',
            'gsgcl_hero_image_url' => 'https://images.unsplash.com/photo-1505764706515-aa95265c5abc?auto=format&fit=crop&w=1200&q=80',
            'gsgcl_hero_badge_primary' => 'USA',
            'gsgcl_hero_badge_secondary' => 'UK',
            'gsgcl_benefit_heading' => '¿Cuál es el beneficio?',
            'gsgcl_benefit_discount' => '20% OFF',
            'gsgcl_benefit_subheading' => 'En tu servicio de Admission',
            'gsgcl_benefit_description' => 'Aplica para ti y para tu amigo referido.',
            'gsgcl_card_friend_title' => 'Para tu amigo',
            'gsgcl_card_friend_body' => 'Obtiene un beneficio directo del 20% de descuento en su servicio de asesoría (Admission) para iniciar su proceso con el pie derecho.',
            'gsgcl_card_you_title' => 'Para ti',
            'gsgcl_card_you_body' => 'Recibes el mismo 20% de descuento por habernos recomendado. Es nuestra forma de agradecer tu confianza en la comunidad GSG.',
            'gsgcl_steps_heading' => '¿Cómo funciona?',
            'gsgcl_steps_subheading' => '3 simples pasos para activar tu beneficio.',
            'gsgcl_step_1_title' => 'Recomienda',
            'gsgcl_step_1_body' => 'Registra los datos de tu amigo en el formulario de esta página.',
            'gsgcl_step_2_title' => 'Lo contactamos',
            'gsgcl_step_2_body' => 'Nuestro equipo le brindará una orientación personalizada sobre estudios en el extranjero.',
            'gsgcl_step_3_title' => 'Recibes tu beneficio',
            'gsgcl_step_3_body' => 'Si tu amigo inicia su proceso, activamos tu 20% de descuento de inmediato.',
            'gsgcl_form_heading' => 'Registra tu amigo',
            'gsgcl_form_description' => 'Completa los datos y nosotros nos encargamos del resto.',
            'gsgcl_success_message' => 'Gracias. Recibimos tu registro y nuestro equipo revisará el caso pronto.',
            'gsgcl_error_message' => 'No pudimos procesar tu registro. Revisa los datos e intenta nuevamente.',
            'gsgcl_reasons_heading' => '¿Por qué referir a GSG Education?',
            'gsgcl_reasons_list' => "Asesoría especializada para estudiar en el extranjero.\nAcompañamiento integral en todo el proceso de Admission.\nPreparación especializada para IELTS y TOEFL.\nCientos de estudiantes ya están cumpliendo su sueño fuera del país.",
            'gsgcl_help_heading' => '¿Tienes dudas?',
            'gsgcl_help_text' => 'Si tú o tu amigo aún no lo deciden, nuestro equipo puede ayudarles a dar el siguiente paso.',
            'gsgcl_help_whatsapp_pe' => 'https://wa.me/51999999999',
            'gsgcl_help_whatsapp_co' => 'https://wa.me/573000000000',
            'gsgcl_submission_hook' => 'gsgcl_referral_submission',
            'gsgcl_redirect_url' => '',
            'gsgcl_openai_enabled' => '0',
            'gsgcl_openai_context' => 'Usar esta landing como base para futuras automatizaciones de clasificación o personalización.',
        );
    }

    public function render_setup_metabox($post)
    {
        wp_nonce_field('gsgcl_save_landing', 'gsgcl_nonce');

        $content_type = get_post_meta($post->ID, 'gsgcl_content_type', true);
        $layout_variant = get_post_meta($post->ID, 'gsgcl_layout_variant', true);
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="gsgcl_content_type"><?php echo esc_html__('Tipo', 'gsg-custom-landings'); ?></label></th>
                    <td>
                        <select name="gsgcl_content_type" id="gsgcl_content_type">
                            <option value="landing" <?php selected($content_type, 'landing'); ?>><?php echo esc_html__('Landing', 'gsg-custom-landings'); ?></option>
                            <option value="quiz" <?php selected($content_type, 'quiz'); ?>><?php echo esc_html__('Quiz', 'gsg-custom-landings'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsgcl_layout_variant"><?php echo esc_html__('Layout', 'gsg-custom-landings'); ?></label></th>
                    <td>
                        <select name="gsgcl_layout_variant" id="gsgcl_layout_variant">
                            <option value="referral" <?php selected($layout_variant, 'referral'); ?>><?php echo esc_html__('Referral', 'gsg-custom-landings'); ?></option>
                        </select>
                        <p class="description"><?php echo esc_html__('Cada landing expone un template dinámico con formato GSG Custom | {nombre}. Asigna el template a una página para publicarla.', 'gsg-custom-landings'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function render_content_metabox($post)
    {
        $defaults = $this->get_default_meta_values();
        $fields = array(
            'Hero' => array(
                array('gsgcl_hero_title', 'Título principal'),
                array('gsgcl_hero_highlight', 'Texto destacado'),
                array('gsgcl_hero_description', 'Descripción', 'textarea'),
                array('gsgcl_primary_cta_label', 'Texto CTA primario'),
                array('gsgcl_primary_cta_url', 'URL CTA primario'),
                array('gsgcl_secondary_cta_label', 'Texto CTA secundario'),
                array('gsgcl_secondary_cta_url', 'URL CTA secundario'),
                array('gsgcl_hero_image_url', 'Imagen principal (URL)'),
                array('gsgcl_hero_badge_primary', 'Badge 1'),
                array('gsgcl_hero_badge_secondary', 'Badge 2'),
            ),
            'Beneficio' => array(
                array('gsgcl_benefit_heading', 'Título sección'),
                array('gsgcl_benefit_discount', 'Descuento'),
                array('gsgcl_benefit_subheading', 'Subtítulo'),
                array('gsgcl_benefit_description', 'Descripción', 'textarea'),
                array('gsgcl_card_friend_title', 'Tarjeta amigo: título'),
                array('gsgcl_card_friend_body', 'Tarjeta amigo: contenido', 'textarea'),
                array('gsgcl_card_you_title', 'Tarjeta tú: título'),
                array('gsgcl_card_you_body', 'Tarjeta tú: contenido', 'textarea'),
            ),
            'Pasos' => array(
                array('gsgcl_steps_heading', 'Título sección'),
                array('gsgcl_steps_subheading', 'Subtítulo'),
                array('gsgcl_step_1_title', 'Paso 1: título'),
                array('gsgcl_step_1_body', 'Paso 1: contenido', 'textarea'),
                array('gsgcl_step_2_title', 'Paso 2: título'),
                array('gsgcl_step_2_body', 'Paso 2: contenido', 'textarea'),
                array('gsgcl_step_3_title', 'Paso 3: título'),
                array('gsgcl_step_3_body', 'Paso 3: contenido', 'textarea'),
            ),
            'Formulario y cierre' => array(
                array('gsgcl_form_heading', 'Título formulario'),
                array('gsgcl_form_description', 'Descripción formulario', 'textarea'),
                array('gsgcl_success_message', 'Mensaje éxito', 'textarea'),
                array('gsgcl_error_message', 'Mensaje error', 'textarea'),
                array('gsgcl_reasons_heading', 'Título beneficios finales'),
                array('gsgcl_reasons_list', 'Lista de razones (una por línea)', 'textarea'),
                array('gsgcl_help_heading', 'Título ayuda'),
                array('gsgcl_help_text', 'Texto ayuda', 'textarea'),
                array('gsgcl_help_whatsapp_pe', 'WhatsApp Perú'),
                array('gsgcl_help_whatsapp_co', 'WhatsApp Colombia'),
            ),
        );

        foreach ($fields as $section_label => $section_fields) {
            echo '<h3>' . esc_html($section_label) . '</h3>';
            echo '<table class="form-table" role="presentation"><tbody>';

            foreach ($section_fields as $field) {
                $key = $field[0];
                $label = $field[1];
                $type = isset($field[2]) ? $field[2] : 'text';
                $value = get_post_meta($post->ID, $key, true);
                if ('' === $value) {
                    $value = isset($defaults[$key]) ? $defaults[$key] : '';
                }

                echo '<tr>';
                echo '<th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
                echo '<td>';

                if ('textarea' === $type) {
                    echo '<textarea class="large-text" rows="4" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
                } else {
                    echo '<input class="regular-text" type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
                }

                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }
    }

    public function render_integrations_metabox($post)
    {
        $submission_hook = get_post_meta($post->ID, 'gsgcl_submission_hook', true);
        $redirect_url = get_post_meta($post->ID, 'gsgcl_redirect_url', true);
        $openai_enabled = get_post_meta($post->ID, 'gsgcl_openai_enabled', true);
        $openai_context = get_post_meta($post->ID, 'gsgcl_openai_context', true);
        ?>
        <p>
            <label for="gsgcl_submission_hook"><strong><?php echo esc_html__('Hook de envío', 'gsg-custom-landings'); ?></strong></label>
            <input class="widefat" type="text" id="gsgcl_submission_hook" name="gsgcl_submission_hook" value="<?php echo esc_attr($submission_hook); ?>" />
            <span class="description"><?php echo esc_html__('Se ejecuta como do_action( hook, payload, landing_id, submission_id ).', 'gsg-custom-landings'); ?></span>
        </p>
        <p>
            <label for="gsgcl_redirect_url"><strong><?php echo esc_html__('URL de redirección opcional', 'gsg-custom-landings'); ?></strong></label>
            <input class="widefat" type="url" id="gsgcl_redirect_url" name="gsgcl_redirect_url" value="<?php echo esc_attr($redirect_url); ?>" />
        </p>
        <p>
            <label>
                <input type="checkbox" name="gsgcl_openai_enabled" value="1" <?php checked($openai_enabled, '1'); ?> />
                <?php echo esc_html__('Activar integración OpenAI para esta landing', 'gsg-custom-landings'); ?>
            </label>
        </p>
        <p>
            <label for="gsgcl_openai_context"><strong><?php echo esc_html__('Contexto OpenAI', 'gsg-custom-landings'); ?></strong></label>
            <textarea class="widefat" rows="5" id="gsgcl_openai_context" name="gsgcl_openai_context"><?php echo esc_textarea($openai_context); ?></textarea>
        </p>
        <?php
    }

    public function save_landing_meta($post_id)
    {
        if (! isset($_POST['gsgcl_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gsgcl_nonce'])), 'gsgcl_save_landing')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $defaults = $this->get_default_meta_values();

        foreach (array_keys($defaults) as $meta_key) {
            if ('gsgcl_openai_enabled' === $meta_key) {
                update_post_meta($post_id, $meta_key, isset($_POST[$meta_key]) ? '1' : '0');
                continue;
            }

            if (! isset($_POST[$meta_key])) {
                continue;
            }

            $raw_value = wp_unslash($_POST[$meta_key]);
            $sanitized_value = $this->sanitize_meta_value($meta_key, $raw_value);
            update_post_meta($post_id, $meta_key, $sanitized_value);
        }
    }

    private function sanitize_meta_value($meta_key, $value)
    {
        $textarea_fields = array(
            'gsgcl_hero_description',
            'gsgcl_benefit_description',
            'gsgcl_card_friend_body',
            'gsgcl_card_you_body',
            'gsgcl_step_1_body',
            'gsgcl_step_2_body',
            'gsgcl_step_3_body',
            'gsgcl_form_description',
            'gsgcl_success_message',
            'gsgcl_error_message',
            'gsgcl_reasons_list',
            'gsgcl_help_text',
            'gsgcl_openai_context',
        );

        $url_fields = array(
            'gsgcl_primary_cta_url',
            'gsgcl_secondary_cta_url',
            'gsgcl_hero_image_url',
            'gsgcl_help_whatsapp_pe',
            'gsgcl_help_whatsapp_co',
            'gsgcl_redirect_url',
        );

        if ('gsgcl_content_type' === $meta_key) {
            return in_array($value, array('landing', 'quiz'), true) ? $value : 'landing';
        }

        if ('gsgcl_layout_variant' === $meta_key) {
            return 'referral';
        }

        if ('gsgcl_submission_hook' === $meta_key) {
            return sanitize_key($value);
        }

        if (in_array($meta_key, $url_fields, true)) {
            if (0 === strpos($value, '#')) {
                return sanitize_text_field($value);
            }

            return esc_url_raw($value);
        }

        if (in_array($meta_key, $textarea_fields, true)) {
            return sanitize_textarea_field($value);
        }

        return sanitize_text_field($value);
    }
}