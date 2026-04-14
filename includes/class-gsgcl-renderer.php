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
        ?>
        <div class="gsgcl-shell gsgcl-variant-<?php echo esc_attr($config['layout_variant']); ?>">
            <section class="gsgcl-hero">
                <div class="gsgcl-wrap gsgcl-hero__grid">
                    <div class="gsgcl-hero__content">
                        <p class="gsgcl-kicker"><?php echo esc_html(ucfirst($config['content_type'])); ?></p>
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
                        <div class="gsgcl-hero__image" style="background-image:url('<?php echo esc_url($config['hero_image_url']); ?>');"></div>
                        <div class="gsgcl-badge gsgcl-badge--primary"><?php echo esc_html($config['hero_badge_primary']); ?></div>
                        <div class="gsgcl-badge gsgcl-badge--secondary"><?php echo esc_html($config['hero_badge_secondary']); ?></div>
                    </div>
                </div>
            </section>

            <section id="gsgcl-benefit" class="gsgcl-benefit">
                <div class="gsgcl-wrap">
                    <header class="gsgcl-section-header">
                        <h2><?php echo esc_html($config['benefit_heading']); ?></h2>
                        <div class="gsgcl-offer"><?php echo esc_html($config['benefit_discount']); ?></div>
                        <h3><?php echo esc_html($config['benefit_subheading']); ?></h3>
                        <p><?php echo esc_html($config['benefit_description']); ?></p>
                    </header>
                    <div class="gsgcl-card-grid">
                        <?php foreach ($config['benefit_cards'] as $card) : ?>
                            <article class="gsgcl-card">
                                <span class="gsgcl-card__icon"></span>
                                <h4><?php echo esc_html($card['title']); ?></h4>
                                <p><?php echo esc_html($card['body']); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

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
                                        <label><span><?php echo esc_html__('País destino', 'gsg-custom-landings'); ?></span><input type="text" name="friend_destination" /></label>
                                        <label><span><?php echo esc_html__('WhatsApp', 'gsg-custom-landings'); ?></span><input type="text" name="friend_whatsapp" /></label>
                                        <label><span><?php echo esc_html__('Email', 'gsg-custom-landings'); ?></span><input type="email" name="friend_email" required /></label>
                                        <label><span><?php echo esc_html__('¿Qué le interesa?', 'gsg-custom-landings'); ?></span><input type="text" name="friend_interest" /></label>
                                    </div>
                                </div>

                                <div>
                                    <h3><?php echo esc_html__('Tus datos', 'gsg-custom-landings'); ?></h3>
                                    <div class="gsgcl-field-grid">
                                        <label><span><?php echo esc_html__('Tu nombre completo', 'gsg-custom-landings'); ?></span><input type="text" name="student_name" required /></label>
                                        <label><span><?php echo esc_html__('Tu email', 'gsg-custom-landings'); ?></span><input type="email" name="student_email" required /></label>
                                        <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('Comentarios', 'gsg-custom-landings'); ?></span><textarea name="student_comments" rows="6" placeholder="<?php echo esc_attr__('Algo que debamos saber?', 'gsg-custom-landings'); ?>"></textarea></label>
                                    </div>
                                </div>
                            </div>

                            <button class="gsgcl-button gsgcl-button--primary" type="submit"><?php echo esc_html($config['primary_cta_label']); ?></button>
                        </form>
                    </div>
                </div>
            </section>

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
                            <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url($config['help_whatsapp_pe']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Escríbenos por WhatsApp - Perú', 'gsg-custom-landings'); ?></a>
                            <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url($config['help_whatsapp_co']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Escríbenos por WhatsApp - Colombia', 'gsg-custom-landings'); ?></a>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
        <?php
    }

    private function get_landing_config($landing_id)
    {
        return array(
            'content_type' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_content_type', 'landing'),
            'layout_variant' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_layout_variant', 'referral'),
            'hero_title' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_title', ''),
            'hero_highlight' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_highlight', ''),
            'hero_description' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_description', ''),
            'primary_cta_label' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_primary_cta_label', ''),
            'primary_cta_url' => $this->normalize_front_url($this->plugin->get_landing_meta($landing_id, 'gsgcl_primary_cta_url', '#gsgcl-form')),
            'secondary_cta_label' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_secondary_cta_label', ''),
            'secondary_cta_url' => $this->normalize_front_url($this->plugin->get_landing_meta($landing_id, 'gsgcl_secondary_cta_url', '#gsgcl-benefit')),
            'hero_image_url' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_hero_image_url', ''),
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
            'form_heading' => $this->plugin->get_landing_meta($landing_id, 'gsgcl_form_heading', ''),
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

    private function explode_lines($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        return array_values($lines);
    }
}