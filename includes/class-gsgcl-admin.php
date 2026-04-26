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
            'gsgcl_landing_leads',
            __('Leads y API REST', 'gsg-custom-landings'),
            array($this, 'render_leads_metabox'),
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

        wp_register_style('gsgcl-admin-ui', false, array(), GSGCL_VERSION);
        wp_enqueue_style('gsgcl-admin-ui');
        wp_add_inline_style('gsgcl-admin-ui', $this->get_inline_admin_css());

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
            'gsgcl_design_system' => '1',
            'gsgcl_google_font_family' => 'Poppins',
            'gsgcl_hero_title' => 'Invita a un amigo',
            'gsgcl_hero_highlight' => 'y ambos ganan',
            'gsgcl_hero_description' => 'Estudiar en el extranjero es mejor cuando se comparte. Recomienda y obtén beneficios exclusivos.',
            'gsgcl_primary_cta_label' => '¡Registrar a mi amigo!',
            'gsgcl_primary_cta_url' => '#gsgcl-form',
            'gsgcl_secondary_cta_label' => 'Ver beneficio',
            'gsgcl_secondary_cta_url' => '#gsgcl-benefit',
            'gsgcl_hero_image_url' => 'https://gsgeducation.com/wp-content/uploads/2026/04/Banner-1800x600-px-02.jpg',
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
            'gsgcl_form_heading' => 'Registra a tu amigo',
            'gsgcl_form_description' => 'Completa los datos y nosotros nos encargamos del resto.',
            'gsgcl_success_message' => 'Gracias. Tu solicitud ha sido registrada con éxito.',
            'gsgcl_error_message' => 'No pudimos procesar tu registro. Revisa los datos e intenta nuevamente.',
            'gsgcl_reasons_heading' => '¿Por qué referir a GSG Education?',
            'gsgcl_reasons_list' => "+12 años de experiencia en América Latina.\n+4000 alumnos aceptados en universidades top a nivel mundial.\nConvenios con +400 universidades a nivel internacional.\nMiembros de organizaciones como NAFSA, International ACAC y UCAS.",
            'gsgcl_help_heading' => '¿Tienes dudas?',
            'gsgcl_help_text' => 'Si tú o tu amigo aún no lo deciden, nuestro equipo puede ayudarles a dar el siguiente paso.',
            'gsgcl_help_whatsapp_pe' => 'https://conectiontool.mantra.chat/tools/u/accbc76f-7622-44d5-9f71-58963e49a0f2',
            'gsgcl_help_whatsapp_co' => 'https://wa.link/sghvfb',
            'gsgcl_submission_hook' => 'gsgcl_referral_submission',
            'gsgcl_redirect_url' => '',
            'gsgcl_openai_enabled' => '0',
            'gsgcl_openai_context' => 'Usar esta landing como base para futuras automatizaciones de clasificación o personalización.',
            'gsgcl_leads_rest_enabled' => '0',
            'gsgcl_leads_rest_pass' => '',
        );
    }

    public function render_leads_metabox($post)
    {
        $lead_rows = array();
        foreach ($this->plugin->get_landing_submissions($post->ID) as $submission) {
            $lead_rows[] = $this->plugin->get_submission_export_data($submission);
        }

        $rest_enabled = get_post_meta($post->ID, 'gsgcl_leads_rest_enabled', true);
        $rest_pass = (string) get_post_meta($post->ID, 'gsgcl_leads_rest_pass', true);
        $endpoint_url = $this->plugin->get_landing_leads_endpoint_url($post->ID);
        $last_submission = ! empty($lead_rows) ? (string) $lead_rows[0]['submitted_at'] : '';
        $rest_status_label = __('Inactiva', 'gsg-custom-landings');
        $rest_status_class = 'inactive';

        if ('1' === $rest_enabled && '' !== $rest_pass) {
            $rest_status_label = __('Activa', 'gsg-custom-landings');
            $rest_status_class = 'active';
        } elseif ('1' === $rest_enabled) {
            $rest_status_label = __('Incompleta', 'gsg-custom-landings');
            $rest_status_class = 'warning';
        }

        $tab_prefix = 'gsgcl-leads-tabs-' . $post->ID;
        $curl_example = "curl --request GET \\\n+  --url '" . $endpoint_url . "' \\\n+  --header 'X-GSGCL-Pass: TU_PASS_CONFIGURADO'";
        $fetch_example = "fetch('" . $endpoint_url . "', {\n  headers: {\n    'X-GSGCL-Pass': 'TU_PASS_CONFIGURADO'\n  }\n})\n  .then((response) => response.json())\n  .then((data) => console.log(data));";
        $response_example = wp_json_encode(
            array(
                'landing' => array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'status' => $post->post_status,
                    'endpoint' => $endpoint_url,
                ),
                'generated_at' => current_time('mysql'),
                'total_leads' => 1,
                'leads' => array(
                    array(
                        'submission_id' => 123,
                        'submitted_at' => current_time('mysql'),
                        'payload' => array(
                            'friend_name' => 'Ana',
                            'friend_last_name' => 'Perez',
                            'friend_destination' => 'Peru',
                            'friend_whatsapp' => '+51999999999',
                            'friend_email' => 'ana@example.com',
                            'friend_interest' => 'Admission Advisory',
                            'student_name' => 'Luis Gomez',
                            'student_email' => 'luis@example.com',
                            'student_comments' => 'Seguimiento prioritario',
                        ),
                        'request_context' => array(
                            'page_id' => 456,
                            'ip_address' => '127.0.0.1',
                            'referer' => home_url('/landing-demo/'),
                        ),
                        'meta' => array(
                            'gsgcl_openai_enabled' => '0',
                            'gsgcl_openai_context' => '',
                        ),
                        'all_data' => array(
                            'submission_id' => 123,
                            'friend_name' => 'Ana',
                            'friend_email' => 'ana@example.com',
                            'student_name' => 'Luis Gomez',
                            'page_id' => 456,
                        ),
                    ),
                ),
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        ?>
        <div class="gsgcl-admin-card-stack gsgcl-tabbed-panel" data-default-tab="<?php echo esc_attr($tab_prefix . '-table'); ?>">
            <div class="gsgcl-admin-summary-grid">
                <div class="gsgcl-admin-stat-card">
                    <span class="gsgcl-admin-stat-card__label"><?php echo esc_html__('Total de leads', 'gsg-custom-landings'); ?></span>
                    <strong class="gsgcl-admin-stat-card__value"><?php echo esc_html((string) count($lead_rows)); ?></strong>
                    <span class="gsgcl-admin-stat-card__meta"><?php echo esc_html__('Registrados para esta landing', 'gsg-custom-landings'); ?></span>
                </div>
                <div class="gsgcl-admin-stat-card">
                    <span class="gsgcl-admin-stat-card__label"><?php echo esc_html__('Último lead', 'gsg-custom-landings'); ?></span>
                    <strong class="gsgcl-admin-stat-card__value"><?php echo esc_html($last_submission ? $last_submission : __('Sin envíos todavía', 'gsg-custom-landings')); ?></strong>
                    <span class="gsgcl-admin-stat-card__meta"><?php echo esc_html__('La tabla muestra todo el histórico actual', 'gsg-custom-landings'); ?></span>
                </div>
                <div class="gsgcl-admin-stat-card">
                    <span class="gsgcl-admin-stat-card__label"><?php echo esc_html__('Estado API REST', 'gsg-custom-landings'); ?></span>
                    <strong class="gsgcl-admin-stat-card__value">
                        <span class="gsgcl-admin-badge gsgcl-admin-badge--<?php echo esc_attr($rest_status_class); ?>"><?php echo esc_html($rest_status_label); ?></span>
                    </strong>
                    <span class="gsgcl-admin-stat-card__meta"><?php echo esc_html__('Ruta por landing con pass configurable', 'gsg-custom-landings'); ?></span>
                </div>
            </div>

            <div class="gsgcl-admin-tablist" role="tablist" aria-label="<?php echo esc_attr__('Tabs de leads', 'gsg-custom-landings'); ?>">
                <button type="button" class="gsgcl-admin-tab is-active" data-tab-target="<?php echo esc_attr($tab_prefix . '-table'); ?>" role="tab" aria-selected="true" aria-controls="<?php echo esc_attr($tab_prefix . '-table'); ?>"><?php echo esc_html__('Leads', 'gsg-custom-landings'); ?></button>
                <button type="button" class="gsgcl-admin-tab" data-tab-target="<?php echo esc_attr($tab_prefix . '-rest'); ?>" role="tab" aria-selected="false" aria-controls="<?php echo esc_attr($tab_prefix . '-rest'); ?>"><?php echo esc_html__('Configuración REST', 'gsg-custom-landings'); ?></button>
            </div>

            <div id="<?php echo esc_attr($tab_prefix . '-table'); ?>" class="gsgcl-admin-tabpanel is-active" role="tabpanel">
                <div class="gsgcl-leads-table-wrap">
                    <table class="widefat striped gsgcl-leads-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Fecha', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Nombre amigo', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Apellido amigo', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('País', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('WhatsApp', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('E-mail amigo', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Interés', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Estudiante', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('E-mail estudiante', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Comentarios', 'gsg-custom-landings'); ?></th>
                                <th><?php echo esc_html__('Contexto técnico', 'gsg-custom-landings'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lead_rows)) : ?>
                                <tr>
                                    <td colspan="11">
                                        <div class="gsgcl-empty-state">
                                            <strong><?php echo esc_html__('Todavía no hay leads para esta landing.', 'gsg-custom-landings'); ?></strong>
                                            <p><?php echo esc_html__('Cuando lleguen formularios, aparecerán aquí con toda la data capturada y el contexto técnico guardado.', 'gsg-custom-landings'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($lead_rows as $lead_row) : ?>
                                    <?php
                                    $payload = isset($lead_row['payload']) && is_array($lead_row['payload']) ? $lead_row['payload'] : array();
                                    $request_context = isset($lead_row['request_context']) && is_array($lead_row['request_context']) ? $lead_row['request_context'] : array();
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html((string) $lead_row['submitted_at']); ?></strong>
                                            <div class="gsgcl-cell-meta">#<?php echo esc_html((string) $lead_row['submission_id']); ?></div>
                                        </td>
                                        <td><?php echo esc_html(isset($payload['friend_name']) ? (string) $payload['friend_name'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['friend_last_name']) ? (string) $payload['friend_last_name'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['friend_destination']) ? (string) $payload['friend_destination'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['friend_whatsapp']) ? (string) $payload['friend_whatsapp'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['friend_email']) ? (string) $payload['friend_email'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['friend_interest']) ? (string) $payload['friend_interest'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['student_name']) ? (string) $payload['student_name'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($payload['student_email']) ? (string) $payload['student_email'] : ''); ?></td>
                                        <td class="gsgcl-cell-comments"><?php echo esc_html(isset($payload['student_comments']) ? (string) $payload['student_comments'] : ''); ?></td>
                                        <td>
                                            <div class="gsgcl-leads-context">
                                                <span><strong><?php echo esc_html__('Page ID:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(isset($request_context['page_id']) && $request_context['page_id'] ? (string) $request_context['page_id'] : '0'); ?></span>
                                                <span><strong><?php echo esc_html__('IP:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(isset($request_context['ip_address']) ? (string) $request_context['ip_address'] : ''); ?></span>
                                                <span><strong><?php echo esc_html__('Referer:', 'gsg-custom-landings'); ?></strong> <?php echo ! empty($request_context['referer']) ? '<a href="' . esc_url((string) $request_context['referer']) . '" target="_blank" rel="noopener noreferrer">' . esc_html((string) $request_context['referer']) . '</a>' : esc_html__('N/D', 'gsg-custom-landings'); ?></span>
                                                <span><strong><?php echo esc_html__('URI:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(isset($request_context['request_uri']) ? (string) $request_context['request_uri'] : ''); ?></span>
                                                <span><strong><?php echo esc_html__('User agent:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(isset($request_context['user_agent']) ? (string) $request_context['user_agent'] : ''); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="<?php echo esc_attr($tab_prefix . '-rest'); ?>" class="gsgcl-admin-tabpanel" role="tabpanel" hidden>
                <div class="gsgcl-admin-two-column">
                    <div class="gsgcl-admin-panel-card">
                        <h3><?php echo esc_html__('Configuración de la ruta REST', 'gsg-custom-landings'); ?></h3>
                        <p class="description"><?php echo esc_html__('Activa un endpoint por landing para que un sistema externo consuma todos los leads guardados con payload, contexto técnico, meta auxiliar y una versión consolidada en all_data.', 'gsg-custom-landings'); ?></p>

                        <p>
                            <label>
                                <input type="checkbox" name="gsgcl_leads_rest_enabled" value="1" <?php checked($rest_enabled, '1'); ?> />
                                <?php echo esc_html__('Habilitar exportación REST de leads para esta landing', 'gsg-custom-landings'); ?>
                            </label>
                        </p>

                        <p>
                            <label for="gsgcl_leads_rest_pass"><strong><?php echo esc_html__('Password de consumo', 'gsg-custom-landings'); ?></strong></label>
                            <input class="widefat code" type="text" id="gsgcl_leads_rest_pass" name="gsgcl_leads_rest_pass" value="<?php echo esc_attr($rest_pass); ?>" autocomplete="off" placeholder="lead-sync-2026" />
                        </p>

                        <p>
                            <label for="gsgcl_leads_rest_endpoint"><strong><?php echo esc_html__('Endpoint', 'gsg-custom-landings'); ?></strong></label>
                            <input class="widefat code" type="text" id="gsgcl_leads_rest_endpoint" value="<?php echo esc_attr($endpoint_url); ?>" readonly />
                        </p>

                        <div class="gsgcl-admin-callout">
                            <strong><?php echo esc_html__('Métodos de autenticación soportados', 'gsg-custom-landings'); ?></strong>
                            <p><?php echo esc_html__('Preferido: header X-GSGCL-Pass. También se acepta Authorization: Bearer <pass> y como fallback pass por query string.', 'gsg-custom-landings'); ?></p>
                        </div>
                    </div>

                    <div class="gsgcl-admin-panel-card">
                        <h3><?php echo esc_html__('Instrucciones de consumo', 'gsg-custom-landings'); ?></h3>
                        <ol class="gsgcl-admin-instructions">
                            <li><?php echo esc_html__('Guarda la landing con la opción REST activada y un password configurado.', 'gsg-custom-landings'); ?></li>
                            <li><?php echo esc_html__('Haz un GET al endpoint de esta landing enviando el password en el header X-GSGCL-Pass.', 'gsg-custom-landings'); ?></li>
                            <li><?php echo esc_html__('Procesa el array leads. Cada lead incluye payload, request_context, meta, all_data y raw_meta.', 'gsg-custom-landings'); ?></li>
                        </ol>

                        <strong><?php echo esc_html__('Ejemplo cURL', 'gsg-custom-landings'); ?></strong>
                        <pre class="gsgcl-admin-inline-code"><?php echo esc_html($curl_example); ?></pre>

                        <strong><?php echo esc_html__('Ejemplo fetch', 'gsg-custom-landings'); ?></strong>
                        <pre class="gsgcl-admin-inline-code"><?php echo esc_html($fetch_example); ?></pre>

                        <strong><?php echo esc_html__('Estructura de respuesta', 'gsg-custom-landings'); ?></strong>
                        <pre class="gsgcl-admin-inline-code"><?php echo esc_html((string) $response_example); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
                <tr>
                    <th scope="row"><?php echo esc_html__('Sistema de diseño', 'gsg-custom-landings'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="gsgcl_design_system" value="1" <?php checked(get_post_meta($post->ID, 'gsgcl_design_system', true), '1'); ?> />
                            <?php echo esc_html__('Aplicar tipografía global (h1-h4, p, li) del sistema de diseño. Desmarcar para usar estilos propios de cada sección.', 'gsg-custom-landings'); ?>
                        </label>
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
            <span class="description"><?php echo esc_html__('Acepta un hook interno de WordPress o una URL webhook HTTPS; los webhooks se envían por POST con payload, landing_id y submission_id.', 'gsg-custom-landings'); ?></span>
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
            if (in_array($meta_key, array('gsgcl_openai_enabled', 'gsgcl_hide_theme_chrome', 'gsgcl_design_system', 'gsgcl_leads_rest_enabled'), true)) {
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
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return esc_url_raw($value);
            }

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

    private function get_inline_admin_css()
    {
        return <<<'CSS'
body.folded #adminmenuback, body.folded #adminmenuwrap, body.folded #adminmenu { width: 30px; }
body.folded #wpcontent, body.folded #wpfooter { margin-left: 30px; }
body.folded #adminmenu .wp-submenu { left: 30px; }
.gsgcl-admin-card-stack { display: grid; gap: 18px; }
.gsgcl-admin-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.gsgcl-admin-stat-card { padding: 18px; border: 1px solid #d7e3f4; border-radius: 18px; background: linear-gradient(180deg, #ffffff 0%, #f6faff 100%); box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05); }
.gsgcl-admin-stat-card__label { display: block; margin-bottom: 8px; color: #48617d; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
.gsgcl-admin-stat-card__value { display: block; color: #10233d; font-size: 24px; line-height: 1.2; }
.gsgcl-admin-stat-card__meta { display: block; margin-top: 8px; color: #5c7088; }
.gsgcl-admin-tablist { display: flex; flex-wrap: wrap; gap: 8px; }
.gsgcl-admin-tab { appearance: none; padding: 10px 16px; border: 1px solid #c6d4e6; border-radius: 999px; background: #ffffff; color: #24415f; cursor: pointer; font-weight: 600; }
.gsgcl-admin-tab.is-active { border-color: #0f5bb8; background: linear-gradient(135deg, #0f5bb8 0%, #256ed0 100%); color: #ffffff; box-shadow: 0 10px 20px rgba(15, 91, 184, 0.2); }
.gsgcl-admin-tabpanel { display: none; }
.gsgcl-admin-tabpanel.is-active { display: block; }
.gsgcl-admin-two-column { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
.gsgcl-admin-panel-card { padding: 20px; border: 1px solid #d7e3f4; border-radius: 20px; background: #ffffff; box-shadow: 0 12px 26px rgba(15, 23, 42, 0.04); }
.gsgcl-admin-panel-card h3 { margin-top: 0; }
.gsgcl-admin-callout { margin-top: 16px; padding: 14px 16px; border-left: 4px solid #0f5bb8; border-radius: 12px; background: #eff6ff; }
.gsgcl-admin-instructions { margin: 10px 0 18px; padding-left: 18px; }
.gsgcl-admin-inline-code { margin: 8px 0 18px; padding: 14px 16px; border-radius: 16px; background: #0f172a; color: #e5eefb; overflow: auto; white-space: pre-wrap; word-break: break-word; }
.gsgcl-admin-badge { display: inline-flex; align-items: center; justify-content: center; min-height: 32px; padding: 0 12px; border-radius: 999px; font-size: 13px; font-weight: 700; }
.gsgcl-admin-badge--active { background: #dcfce7; color: #166534; }
.gsgcl-admin-badge--inactive { background: #e2e8f0; color: #334155; }
.gsgcl-admin-badge--warning { background: #fef3c7; color: #92400e; }
.gsgcl-leads-table-wrap { overflow: auto; border: 1px solid #d7e3f4; border-radius: 20px; }
.gsgcl-leads-table { min-width: 1280px; border-collapse: separate; border-spacing: 0; }
.gsgcl-leads-table thead th { position: sticky; top: 0; background: #eff6ff; color: #24415f; z-index: 1; }
.gsgcl-leads-table tbody td { vertical-align: top; }
.gsgcl-cell-meta { margin-top: 6px; color: #64748b; font-size: 12px; }
.gsgcl-cell-comments { min-width: 220px; }
.gsgcl-leads-context { display: grid; gap: 6px; min-width: 280px; color: #334155; }
.gsgcl-leads-context a { word-break: break-all; }
.gsgcl-empty-state { padding: 28px; text-align: center; }
.gsgcl-empty-state strong { display: block; margin-bottom: 8px; color: #10233d; }
@media (max-width: 782px) {
    .gsgcl-admin-summary-grid,
    .gsgcl-admin-two-column { grid-template-columns: 1fr; }
}
CSS;
    }

    private function get_google_font_presets()
    {
        return $this->google_font_presets;
    }
}