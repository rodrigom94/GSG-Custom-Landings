<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Renderer
{
    private $plugin;

    private $current_landing_id = 0;

    private $current_page_id = 0;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function set_current_context($landing_id, $page_id)
    {
        $this->current_landing_id = absint($landing_id);
        $this->current_page_id = absint($page_id);
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'gsg_custom_landing'
        );

        $landing_id = absint($atts['id']);
        if (! $landing_id) {
            return '';
        }

        ob_start();
        $this->render_landing($landing_id, get_queried_object_id());

        return ob_get_clean();
    }

    public function render_current_landing()
    {
        if (! $this->current_landing_id) {
            return;
        }

        $this->render_landing($this->current_landing_id, $this->current_page_id);
    }

    public function render_landing($landing_id, $page_id = 0)
    {
        $config = $this->get_landing_config($landing_id);
        $status = isset($_GET['gsgcl_status']) ? sanitize_text_field(wp_unslash($_GET['gsgcl_status'])) : '';
        $notice_type = 'success' === $status ? 'success' : ('error' === $status ? 'error' : '');
        $notice_message = 'success' === $status ? $config['success_message'] : ('error' === $status ? $config['error_message'] : '');

        if ($this->landing_uses_sections($landing_id)) {
            $this->render_landing_from_sections($landing_id, $page_id, $config, $notice_type, $notice_message);
            return;
        }

        $this->render_landing_legacy($config, $landing_id, $page_id, $notice_type, $notice_message);
    }

    private function render_landing_from_sections($landing_id, $page_id, $config, $notice_type, $notice_message)
    {
        $sections = $this->get_landing_sections($landing_id);

        if (empty($sections)) {
            $this->render_landing_legacy($config, $landing_id, $page_id, $notice_type, $notice_message);
            return;
        }
        $ds_class = empty($config['design_system']) ? ' gsgcl-ds-none' : '';
        ?>
        <div class="gsgcl-shell gsgcl-shell--sections gsgcl-variant-<?php echo esc_attr($config['layout_variant']); ?><?php echo esc_attr($ds_class); ?>">
            <?php foreach ($sections as $section) : ?>
                <?php $this->render_section($section, $config, $landing_id, $page_id, $notice_type, $notice_message); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_landing_legacy($config, $landing_id, $page_id, $notice_type, $notice_message)
    {
        $ds_class = empty($config['design_system']) ? ' gsgcl-ds-none' : '';
        ?>
        <div class="gsgcl-shell gsgcl-variant-<?php echo esc_attr($config['layout_variant']); ?><?php echo esc_attr($ds_class); ?>">
            <?php $this->render_hero_section($config); ?>
            <?php $this->render_counter_section($config); ?>
            <?php $this->render_benefits_section($config); ?>
            <?php $this->render_steps_section($config); ?>
            <?php $this->render_form_section($config, $landing_id, $page_id, $notice_type, $notice_message); ?>
            <?php $this->render_cta_section($config); ?>
        </div>
        <?php
    }

    private function landing_uses_sections($landing_id)
    {
        $schema = get_post_meta($landing_id, 'gsgcl_sections_schema', true);
        return is_array($schema) && ! empty($schema);
    }

    private function get_landing_sections($landing_id)
    {
        $schema = get_post_meta($landing_id, 'gsgcl_sections_schema', true);
        if (! is_array($schema)) {
            return array();
        }

        usort(
            $schema,
            static function ($left, $right) {
                return (int) ($left['order'] ?? 0) <=> (int) ($right['order'] ?? 0);
            }
        );

        $sections = array();
        foreach ($schema as $item) {
            $section_id = isset($item['section_id']) ? absint($item['section_id']) : 0;
            if (! $section_id) {
                continue;
            }

            $section_post = get_post($section_id);
            if (! $section_post || 'gsg_section' !== $section_post->post_type) {
                continue;
            }

            $sections[] = array(
                'id' => $section_id,
                'title' => $section_post->post_title,
                'type' => (string) get_post_meta($section_id, 'gsgcl_section_type', true),
                'variant' => (string) get_post_meta($section_id, 'gsgcl_section_variant', true),
                'version' => max(1, absint(get_post_meta($section_id, 'gsgcl_section_version', true))),
                'preview_html' => (string) get_post_meta($section_id, 'gsgcl_section_preview_html', true),
                'brief' => (string) get_post_meta($section_id, 'gsgcl_section_brief', true),
            );
        }

        return $sections;
    }

    private function render_section($section, $config, $landing_id, $page_id, $notice_type, $notice_message)
    {
        $variant = isset($section['variant']) ? $section['variant'] : '';

        if ('referral-split-v1' === $variant) {
            $this->render_hero_section($config);
            return;
        }

        if ('benefit-highlight-v1' === $variant) {
            $this->render_counter_section($config);
            return;
        }

        if ('dual-card-v1' === $variant) {
            $this->render_benefits_section($config);
            return;
        }

        if ('two-column-referral-v1' === $variant) {
            $this->render_form_section($config, $landing_id, $page_id, $notice_type, $notice_message);
            return;
        }

        if ('support-panel-v1' === $variant) {
            $this->render_cta_section($config);
            return;
        }

        if ('steps-3-v1' === $variant) {
            $this->render_steps_section($config);
            return;
        }

        $type = isset($section['type']) ? $section['type'] : 'generic';
        if ('hero' === $type) {
            $this->render_hero_section($config);
            return;
        }

        if ('counter' === $type) {
            $this->render_counter_section($config);
            return;
        }

        if ('benefits' === $type) {
            $this->render_benefits_section($config);
            return;
        }

        if ('form' === $type) {
            $this->render_form_section($config, $landing_id, $page_id, $notice_type, $notice_message);
            return;
        }

        if ('cta' === $type) {
            $this->render_cta_section($config);
            return;
        }

        $this->render_preview_section($section);
    }

    private function render_preview_section($section)
    {
        $html = isset($section['preview_html']) ? $section['preview_html'] : '';
        if (! $html) {
            return;
        }
        ?>
        <section class="gsgcl-dynamic-preview" data-section-id="<?php echo esc_attr((string) $section['id']); ?>">
            <div class="gsgcl-wrap">
                <?php echo wp_kses_post($html); ?>
            </div>
        </section>
        <?php
    }

    private function render_hero_section($config)
    {
        $hero_image_url = $this->normalize_hero_image_url(isset($config['hero_image_url']) ? $config['hero_image_url'] : '');
        $hero_image_class = $this->is_demo_hero_banner($hero_image_url) ? ' gsgcl-hero__image--demo-banner' : '';
        ?>
        <section class="gsgcl-hero">
            <div class="gsgcl-wrap gsgcl-hero__grid">
                <div class="gsgcl-hero__content">
                    <h1 class="gsgcl-hero__title">
                        <?php echo esc_html($config['hero_title']); ?>
                        <span><?php echo esc_html($config['hero_highlight']); ?></span>
                    </h1>
                    <p class="gsgcl-hero__description"><?php echo esc_html($config['hero_description']); ?></p>
                    <div class="gsgcl-hero__actions">
                        <a class="gsgcl-button gsgcl-button--accent" href="<?php echo esc_url($config['primary_cta_url']); ?>"><?php echo esc_html($config['primary_cta_label']); ?></a>
                        <a class="gsgcl-button gsgcl-button--ghost" href="<?php echo esc_url($config['secondary_cta_url']); ?>"><?php echo esc_html($config['secondary_cta_label']); ?></a>
                    </div>
                </div>
                <div class="gsgcl-hero__visual">
                    <div class="gsgcl-hero__image<?php echo esc_attr($hero_image_class); ?>" style="background-image:url('<?php echo esc_url($hero_image_url); ?>');"></div>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_counter_section($config)
    {
        ?>
        <section id="gsgcl-benefit" class="gsgcl-benefit gsgcl-benefit--counter-only">
            <div class="gsgcl-wrap">
                <header class="gsgcl-section-header">
                    <h2><?php echo esc_html($config['benefit_heading']); ?></h2>
                    <div class="gsgcl-offer"><?php echo esc_html($config['benefit_discount']); ?></div>
                    <h3><?php echo esc_html($config['benefit_subheading']); ?></h3>
                    <p><?php echo esc_html($config['benefit_description']); ?></p>
                </header>
            </div>
        </section>
        <?php
    }

    private function render_benefits_section($config)
    {
        $icon_classes = array('person', 'gift');
        ?>
        <section class="gsgcl-benefit gsgcl-benefit--cards-only">
            <div class="gsgcl-wrap">
                <div class="gsgcl-card-grid">
                    <?php foreach ($config['benefit_cards'] as $idx => $card) : ?>
                        <article class="gsgcl-card">
                            <span class="gsgcl-card__icon gsgcl-card__icon--<?php echo esc_attr($icon_classes[$idx] ?? 'person'); ?>"></span>
                            <h4><?php echo esc_html($card['title']); ?></h4>
                            <p><?php echo esc_html($card['body']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_steps_section($config)
    {
        ?>
        <section class="gsgcl-steps">
            <div class="gsgcl-wrap">
                <header class="gsgcl-section-header">
                    <h2><?php echo esc_html($config['steps_heading']); ?></h2>
                    <p><?php echo esc_html($config['steps_subheading']); ?></p>
                </header>
                <div class="gsgcl-steps__grid">
                    <?php foreach ($config['steps'] as $index => $step) : ?>
                        <article class="gsgcl-step">
                            <div class="gsgcl-step__number"><?php echo esc_html((string) ($index + 1)); ?></div>
                            <h3><?php echo esc_html($step['title']); ?></h3>
                            <p><?php echo esc_html($step['body']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_form_section($config, $landing_id, $page_id, $notice_type, $notice_message)
    {
        ?>
        <section id="gsgcl-form" class="gsgcl-form-section">
            <div class="gsgcl-wrap">
                <div class="gsgcl-form-card">
                    <header class="gsgcl-form-card__header">
                        <h2><?php echo esc_html($config['form_heading']); ?></h2>
                        <p><?php echo esc_html($config['form_description']); ?></p>
                    </header>

                    <?php if ($notice_type && $notice_message) : ?>
                        <div class="gsgcl-notice gsgcl-notice--<?php echo esc_attr($notice_type); ?>">
                            <?php echo esc_html($notice_message); ?>
                        </div>
                    <?php endif; ?>

                    <form class="gsgcl-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="gsgcl_submit_landing" />
                        <input type="hidden" name="landing_id" value="<?php echo esc_attr($landing_id); ?>" />
                        <input type="hidden" name="page_id" value="<?php echo esc_attr($page_id); ?>" />
                        <input type="hidden" name="gsgcl_form_nonce" value="<?php echo esc_attr(wp_create_nonce('gsgcl_submit_landing')); ?>" />
                        <div class="gsgcl-honeypot" aria-hidden="true">
                            <label for="gsgcl_company">Company</label>
                            <input type="text" id="gsgcl_company" name="company" tabindex="-1" autocomplete="off" />
                        </div>

                        <div class="gsgcl-form__grid">
                            <div>
                                <h3><?php echo esc_html__('Datos de tu amigo', 'gsg-custom-landings'); ?></h3>
                                <div class="gsgcl-field-grid">
                                    <label><span><?php echo esc_html__('Nombre', 'gsg-custom-landings'); ?></span><input type="text" name="friend_name" required /></label>
                                    <label><span><?php echo esc_html__('Apellido', 'gsg-custom-landings'); ?></span><input type="text" name="friend_last_name" required /></label>
                                    <label><span><?php echo esc_html__('País donde vive', 'gsg-custom-landings'); ?></span>
                                        <select name="friend_destination">
                                            <option value=""><?php echo esc_html__('Seleccione', 'gsg-custom-landings'); ?></option>
                                            <option value="Peru"><?php echo esc_html__('Perú', 'gsg-custom-landings'); ?></option>
                                            <option value="Colombia"><?php echo esc_html__('Colombia', 'gsg-custom-landings'); ?></option>
                                            <option value="Mexico"><?php echo esc_html__('México', 'gsg-custom-landings'); ?></option>
                                            <option value="Chile"><?php echo esc_html__('Chile', 'gsg-custom-landings'); ?></option>
                                            <option value="Argentina"><?php echo esc_html__('Argentina', 'gsg-custom-landings'); ?></option>
                                            <option value="Ecuador"><?php echo esc_html__('Ecuador', 'gsg-custom-landings'); ?></option>
                                            <option value="Bolivia"><?php echo esc_html__('Bolivia', 'gsg-custom-landings'); ?></option>
                                            <option value="Otro"><?php echo esc_html__('Otro', 'gsg-custom-landings'); ?></option>
                                        </select>
                                    </label>
                                    <label><span><?php echo esc_html__('WhatsApp', 'gsg-custom-landings'); ?></span><input type="tel" name="friend_whatsapp" placeholder="+51" /></label>
                                    <label><span><?php echo esc_html__('Email', 'gsg-custom-landings'); ?></span><input type="email" name="friend_email" required /></label>
                                    <label><span><?php echo esc_html__('¿Qué le interesa?', 'gsg-custom-landings'); ?></span>
                                        <select name="friend_interest">
                                            <option value=""><?php echo esc_html__('Seleccione una opción', 'gsg-custom-landings'); ?></option>
                                            <option value="Exam Prep">Exam Prep</option>
                                            <option value="Admission Advisory">Admission Advisory</option>
                                            <option value="Ambos"><?php echo esc_html__('Ambos', 'gsg-custom-landings'); ?></option>
                                            <option value="No está seguro"><?php echo esc_html__('No está seguro', 'gsg-custom-landings'); ?></option>
                                        </select>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <h3><?php echo esc_html__('Tus datos (estudiante GSG)', 'gsg-custom-landings'); ?></h3>
                                <div class="gsgcl-field-grid">
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('Tu nombre completo', 'gsg-custom-landings'); ?></span><input type="text" name="student_name" required /></label>
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('Tu email registrado en GSG', 'gsg-custom-landings'); ?></span><input type="email" name="student_email" required /></label>
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('Comentario opcional', 'gsg-custom-landings'); ?></span><textarea name="student_comments" rows="4" placeholder="<?php echo esc_attr__('¿Algo que debamos saber?', 'gsg-custom-landings'); ?>"></textarea></label>
                                </div>
                            </div>
                        </div>

                        <button class="gsgcl-button gsgcl-button--accent gsgcl-button--submit" type="submit"><?php echo esc_html($config['primary_cta_label']); ?></button>
                    </form>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_cta_section($config)
    {
        ?>
        <section class="gsgcl-reasons">
            <div class="gsgcl-wrap gsgcl-reasons__grid">
                <div class="gsgcl-reasons__panel">
                    <h2><?php echo esc_html($config['reasons_heading']); ?></h2>
                    <ul>
                        <?php foreach ($config['reasons_list'] as $reason) : ?>
                            <li><?php echo esc_html($reason); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <aside class="gsgcl-help-card">
                    <h3><?php echo esc_html($config['help_heading']); ?></h3>
                    <p><?php echo esc_html($config['help_text']); ?></p>
                    <div class="gsgcl-help-card__actions">
                        <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url($config['help_whatsapp_pe']); ?>" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.503 3.935 1.389 5.611L0 24l6.597-1.332A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-1.875 0-3.622-.525-5.113-1.433l-.366-.218-3.797.766.8-3.692-.24-.381A9.713 9.713 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg> <?php echo esc_html__('Escríbenos por WhatsApp - Perú y Latam', 'gsg-custom-landings'); ?></a>
                        <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url($config['help_whatsapp_co']); ?>" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.503 3.935 1.389 5.611L0 24l6.597-1.332A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-1.875 0-3.622-.525-5.113-1.433l-.366-.218-3.797.766.8-3.692-.24-.381A9.713 9.713 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg> <?php echo esc_html__('Escríbenos por WhatsApp - Colombia', 'gsg-custom-landings'); ?></a>
                    </div>
                </aside>
            </div>
        </section>
        <?php
    }

    private function get_landing_config($landing_id)
    {
        return array(
            'content_type' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_content_type', 'landing'),
            'layout_variant' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_layout_variant', 'referral'),
            'design_system' => '1' === $this->plugin->get_landing_meta($landing_id, 'gsgcl_design_system', '1'),
            'hero_title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_title', ''),
            'hero_highlight' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_highlight', ''),
            'hero_description' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_description', ''),
            'primary_cta_label' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_primary_cta_label', ''),
            'primary_cta_url' => $this->normalize_front_url($this->plugin->get_landing_meta($landing_id, 'gsgcl_primary_cta_url', '#gsgcl-form')),
            'secondary_cta_label' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_secondary_cta_label', ''),
            'secondary_cta_url' => $this->normalize_front_url($this->plugin->get_landing_meta($landing_id, 'gsgcl_secondary_cta_url', '#gsgcl-benefit')),
            'hero_image_url' => $this->normalize_hero_image_url($this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_image_url', '')),
            'hero_badge_primary' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_badge_primary', ''),
            'hero_badge_secondary' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_badge_secondary', ''),
            'benefit_heading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_benefit_heading', ''),
            'benefit_discount' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_benefit_discount', ''),
            'benefit_subheading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_benefit_subheading', ''),
            'benefit_description' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_benefit_description', ''),
            'benefit_cards' => array(
                array(
                    'title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_card_friend_title', ''),
                    'body' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_card_friend_body', ''),
                ),
                array(
                    'title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_card_you_title', ''),
                    'body' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_card_you_body', ''),
                ),
            ),
            'steps_heading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_steps_heading', ''),
            'steps_subheading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_steps_subheading', ''),
            'steps' => array(
                array(
                    'title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_1_title', ''),
                    'body' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_1_body', ''),
                ),
                array(
                    'title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_2_title', ''),
                    'body' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_2_body', ''),
                ),
                array(
                    'title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_3_title', ''),
                    'body' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_step_3_body', ''),
                ),
            ),
            'form_heading' => $this->normalize_form_heading($this->plugin->get_landing_meta($landing_id, 'gsgcl_form_heading', '')),
            'form_description' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_form_description', ''),
            'success_message' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_success_message', ''),
            'error_message' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_error_message', ''),
            'reasons_heading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_reasons_heading', ''),
            'reasons_list' => $this->explode_lines($this->plugin->get_landing_meta($landing_id, 'gsgcl_reasons_list', '')),
            'help_heading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_help_heading', ''),
            'help_text' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_help_text', ''),
            'help_whatsapp_pe' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_help_whatsapp_pe', ''),
            'help_whatsapp_co' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_help_whatsapp_co', ''),
        );
    }

    private function normalize_front_url($url)
    {
        if (0 === strpos($url, '#')) {
            return $url;
        }

        return $url ? $url : '#';
    }

    private function normalize_form_heading($value)
    {
        return 'Registra tu amigo' === trim((string) $value) ? 'Registra a tu amigo' : $value;
    }

    private function normalize_hero_image_url($value)
    {
        $value = trim((string) $value);
        $legacy_demo_url = 'https://images.unsplash.com/photo-1505764706515-aa95265c5abc?auto=format&fit=crop&w=1200&q=80';
        $demo_banner_url = GSGCL_URL . 'assets/demo-images/Banner 1800x600 px-100.jpg';

        if ('' === $value || $legacy_demo_url === $value) {
            return $demo_banner_url;
        }

        return $value;
    }

    private function is_demo_hero_banner($value)
    {
        return false !== strpos((string) $value, 'assets/demo-images/Banner 1800x600 px-100.jpg');
    }

    private function explode_lines($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        return array_values($lines);
    }
}