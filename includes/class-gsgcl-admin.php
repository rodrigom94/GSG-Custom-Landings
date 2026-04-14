<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Admin
{
    private $plugin;

    private $google_font_presets = array(
        'Poppins',
        'Montserrat',
        'Inter',
        'DM Sans',
        'Outfit',
        'Nunito Sans',
        'Playfair Display',
        'Merriweather',
        'Lora',
        'Space Grotesk',
    );

    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_gsg_landing', array($this, 'save_landing_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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

        add_meta_box(
            'gsgcl_landing_sections',
            __('Secciones de la landing', 'gsg-custom-landings'),
            array($this, 'render_sections_metabox'),
            'gsg_landing',
            'normal',
            'default'
        );
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        if (! in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->post_type, array('gsg_landing', 'gsg_section'), true)) {
            return;
        }

        wp_enqueue_script(
            'gsgcl-admin',
            GSGCL_URL . 'assets/js/gsgcl-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            GSGCL_VERSION,
            true
        );

        wp_localize_script(
            'gsgcl-admin',
            'gsgclAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'previewNonce' => wp_create_nonce('gsgcl_ajax_preview_section'),
                'saveNonce' => wp_create_nonce('gsgcl_ajax_save_section'),
                'restoreNonce' => wp_create_nonce('gsgcl_ajax_restore_section'),
                'generateNonce' => wp_create_nonce('gsgcl_ajax_generate_proposals'),
                'applyNonce' => wp_create_nonce('gsgcl_ajax_apply_proposal'),
                'messages' => array(
                    'previewUpdated' => __('Preview actualizado.', 'gsg-custom-landings'),
                    'sectionSaved' => __('Sección guardada con revisión.', 'gsg-custom-landings'),
                    'proposalApplied' => __('La propuesta fue aplicada.', 'gsg-custom-landings'),
                    'revisionRestored' => __('La revisión fue restaurada.', 'gsg-custom-landings'),
                    'proposalsGenerated' => __('Se generaron 3 propuestas.', 'gsg-custom-landings'),
                    'error' => __('No se pudo completar la acción.', 'gsg-custom-landings'),
                ),
            )
        );
    }

    public function get_default_meta_values()
    {
        return array(
            'gsgcl_content_type' => 'landing',
            'gsgcl_layout_variant' => 'referral',
            'gsgcl_hide_theme_chrome' => '0',
            'gsgcl_google_font_family' => 'Poppins',
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
        $hide_theme_chrome = get_post_meta($post->ID, 'gsgcl_hide_theme_chrome', true);
        $google_font_family = get_post_meta($post->ID, 'gsgcl_google_font_family', true);
        if ('' === $google_font_family) {
            $google_font_family = 'Poppins';
        }
        $font_presets = $this->get_google_font_presets();
        $selected_font_preset = in_array($google_font_family, $font_presets, true) ? $google_font_family : '__custom__';
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
                <tr>
                    <th scope="row"><?php echo esc_html__('Header/Footer del theme', 'gsg-custom-landings'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="gsgcl_hide_theme_chrome" value="1" <?php checked($hide_theme_chrome, '1'); ?> />
                            <?php echo esc_html__('Ocultar header y footer cuando esta landing use su template dinámico', 'gsg-custom-landings'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsgcl_google_font_family_preset"><?php echo esc_html__('Google Font', 'gsg-custom-landings'); ?></label></th>
                    <td>
                        <select id="gsgcl_google_font_family_preset" name="gsgcl_google_font_family_preset">
                            <?php foreach ($font_presets as $font_name) : ?>
                                <option value="<?php echo esc_attr($font_name); ?>" <?php selected($selected_font_preset, $font_name); ?>><?php echo esc_html($font_name); ?></option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?php selected($selected_font_preset, '__custom__'); ?>><?php echo esc_html__('Custom', 'gsg-custom-landings'); ?></option>
                        </select>
                        <p>
                            <input class="regular-text" type="text" id="gsgcl_google_font_family" name="gsgcl_google_font_family" value="<?php echo esc_attr($selected_font_preset === '__custom__' ? $google_font_family : ''); ?>" placeholder="Poppins" />
                        </p>
                        <p class="description"><?php echo esc_html__('Elige una fuente sugerida o escribe una fuente de Google Fonts personalizada. Por defecto: Poppins.', 'gsg-custom-landings'); ?></p>
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

    public function render_sections_metabox($post)
    {
        $schema = get_post_meta($post->ID, 'gsgcl_sections_schema', true);
        $schema = is_array($schema) ? $schema : array();
        $selected_ids = array();

        foreach ($schema as $item) {
            if (! empty($item['section_id'])) {
                $selected_ids[] = absint($item['section_id']);
            }
        }

        $sections = get_posts(
            array(
                'post_type' => 'gsg_section',
                'post_status' => array('publish', 'draft', 'private'),
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            )
        );
        ?>
        <div class="gsgcl-landing-sections" data-target-input="gsgcl_sections_schema_json">
            <p>
                <label for="gsgcl_landing_reference_brief"><strong><?php echo esc_html__('Brief global de la landing', 'gsg-custom-landings'); ?></strong></label>
                <textarea class="large-text" rows="3" id="gsgcl_landing_reference_brief" name="gsgcl_landing_reference_brief"><?php echo esc_textarea((string) get_post_meta($post->ID, 'gsgcl_landing_reference_brief', true)); ?></textarea>
            </p>
            <p>
                <label for="gsgcl_landing_reference_image_url"><strong><?php echo esc_html__('Imagen global de referencia', 'gsg-custom-landings'); ?></strong></label>
                <input class="large-text" type="url" id="gsgcl_landing_reference_image_url" name="gsgcl_landing_reference_image_url" value="<?php echo esc_attr((string) get_post_meta($post->ID, 'gsgcl_landing_reference_image_url', true)); ?>" placeholder="https://..." />
            </p>
            <p class="description"><?php echo esc_html__('Arma la landing como un rompecabezas: agrega secciones desde la biblioteca, ordénalas y guárdalas directamente en esta landing.', 'gsg-custom-landings'); ?></p>

            <div class="gsgcl-sections-grid">
                <div class="gsgcl-sections-panel">
                    <h4><?php echo esc_html__('Biblioteca disponible', 'gsg-custom-landings'); ?></h4>
                    <div class="gsgcl-section-library-list">
                        <?php foreach ($sections as $section) : ?>
                            <?php
                            $section_id = $section->ID;
                            $type = (string) get_post_meta($section_id, 'gsgcl_section_type', true);
                            $variant = (string) get_post_meta($section_id, 'gsgcl_section_variant', true);
                            $payload = $this->plugin->section_library()->get_section_editor_payload($section_id);
                            ?>
                            <div class="gsgcl-library-item" data-section-id="<?php echo esc_attr((string) $section_id); ?>" data-section-type="<?php echo esc_attr($type); ?>" data-section-variant="<?php echo esc_attr($variant); ?>" data-section-title="<?php echo esc_attr($section->post_title); ?>">
                                <strong><?php echo esc_html($section->post_title); ?></strong>
                                <span><?php echo esc_html($type . ' / ' . $variant); ?></span>
                                <button type="button" class="button button-small gsgcl-add-section-button"><?php echo esc_html__('Agregar', 'gsg-custom-landings'); ?></button>
                                <script type="application/json" class="gsgcl-library-item-json"><?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="gsgcl-sections-panel">
                    <h4><?php echo esc_html__('Orden actual de la landing', 'gsg-custom-landings'); ?></h4>
                    <ul class="gsgcl-selected-sections" id="gsgcl-selected-sections">
                        <?php foreach ($schema as $item) : ?>
                            <?php
                            $section_id = isset($item['section_id']) ? absint($item['section_id']) : 0;
                            $section = $section_id ? get_post($section_id) : null;
                            if (! $section || 'gsg_section' !== $section->post_type) {
                                continue;
                            }
                            $type = (string) get_post_meta($section_id, 'gsgcl_section_type', true);
                            $variant = (string) get_post_meta($section_id, 'gsgcl_section_variant', true);
                            ?>
                            <li class="gsgcl-selected-item" data-section-id="<?php echo esc_attr((string) $section_id); ?>">
                                <span class="gsgcl-drag-handle">::</span>
                                <div>
                                    <strong><?php echo esc_html($section->post_title); ?></strong>
                                    <span><?php echo esc_html($type . ' / ' . $variant); ?></span>
                                </div>
                                <button type="button" class="button-link-delete gsgcl-remove-section-button"><?php echo esc_html__('Quitar', 'gsg-custom-landings'); ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="gsgcl_sections_schema_json" id="gsgcl_sections_schema_json" value="<?php echo esc_attr(wp_json_encode($schema)); ?>" />
                </div>
            </div>

            <div class="gsgcl-inline-editors-wrap" id="gsgcl-inline-editors-wrap">
                <?php foreach ($selected_ids as $section_id) : ?>
                    <?php echo $this->plugin->section_library()->render_inline_editor($section_id); ?>
                <?php endforeach; ?>
            </div>
        </div>
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
            if (in_array($meta_key, array('gsgcl_openai_enabled', 'gsgcl_hide_theme_chrome'), true)) {
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

        if (isset($_POST['gsgcl_sections_schema_json'])) {
            $schema = $this->sanitize_sections_schema(wp_unslash($_POST['gsgcl_sections_schema_json']));
            update_post_meta($post_id, 'gsgcl_sections_schema', $schema);
            update_post_meta($post_id, 'gsgcl_section_ids', wp_list_pluck($schema, 'section_id'));
        }

        if (isset($_POST['gsgcl_landing_reference_brief'])) {
            update_post_meta($post_id, 'gsgcl_landing_reference_brief', sanitize_textarea_field(wp_unslash($_POST['gsgcl_landing_reference_brief'])));
        }

        if (isset($_POST['gsgcl_landing_reference_image_url'])) {
            update_post_meta($post_id, 'gsgcl_landing_reference_image_url', esc_url_raw(wp_unslash($_POST['gsgcl_landing_reference_image_url'])));
        }
    }

    private function sanitize_sections_schema($raw_json)
    {
        $decoded = json_decode((string) $raw_json, true);
        if (! is_array($decoded)) {
            return array();
        }

        $sanitized = array();
        $order = 1;

        foreach ($decoded as $item) {
            $section_id = isset($item['section_id']) ? absint($item['section_id']) : 0;
            if (! $section_id || 'gsg_section' !== get_post_type($section_id)) {
                continue;
            }

            $sanitized[] = array(
                'section_id' => $section_id,
                'order' => $order,
            );
            $order++;
        }

        return $sanitized;
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

        if ('gsgcl_google_font_family' === $meta_key) {
            $value = preg_replace('/[^A-Za-z0-9\s_-]/', '', (string) $value);
            $value = trim(preg_replace('/\s+/', ' ', (string) $value));

            if (! $value && isset($_POST['gsgcl_google_font_family_preset'])) {
                $preset = sanitize_text_field(wp_unslash($_POST['gsgcl_google_font_family_preset']));
                if ('__custom__' !== $preset) {
                    $value = $preset;
                }
            }

            return $value ? $value : 'Poppins';
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

    private function get_google_font_presets()
    {
        return $this->google_font_presets;
    }
}