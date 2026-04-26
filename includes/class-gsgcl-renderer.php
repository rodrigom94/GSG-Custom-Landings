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
        $is_demo_banner = $this->is_demo_hero_banner($hero_image_url);
        $hero_image_class = $is_demo_banner ? ' gsgcl-hero__image--demo-banner' : '';
        $hero_title = trim(isset($config['hero_title']) ? (string) $config['hero_title'] : '');
        $hero_highlight = trim(isset($config['hero_highlight']) ? (string) $config['hero_highlight'] : '');
        ?>
        <section class="gsgcl-hero">
            <div class="gsgcl-wrap gsgcl-hero__grid">
                <div class="gsgcl-hero__content">
                    <h1 class="gsgcl-hero__title">
                        <?php echo esc_html($hero_title); ?> <span><?php echo esc_html($hero_highlight); ?></span>
                    </h1>
                    <p class="gsgcl-hero__description"><?php echo esc_html($config['hero_description']); ?></p>
                    <div class="gsgcl-hero__actions">
                        <a class="gsgcl-button gsgcl-button--accent" href="<?php echo esc_url($config['primary_cta_url']); ?>"><?php echo esc_html($config['primary_cta_label']); ?></a>
                        <a class="gsgcl-button gsgcl-button--ghost" href="<?php echo esc_url($config['secondary_cta_url']); ?>"><?php echo esc_html($config['secondary_cta_label']); ?></a>
                    </div>
                </div>
                <div class="gsgcl-hero__visual">
                    <div class="gsgcl-hero__image<?php echo esc_attr($hero_image_class); ?>"<?php if (! $is_demo_banner) : ?> style="background-image:url('<?php echo esc_url($hero_image_url); ?>');"<?php endif; ?>>
                        <?php if ($is_demo_banner) : ?>
                            <picture class="gsgcl-hero__picture">
                                <source media="(max-width: 900px)" srcset="https://gsgeducation.com/wp-content/uploads/2026/04/Banner-800x1000px-02-1.jpg" />
                                <img class="gsgcl-hero__img" src="https://gsgeducation.com/wp-content/uploads/2026/04/Banner-1800x600-px-02.jpg" alt="" loading="eager" decoding="async" />
                            </picture>
                        <?php endif; ?>
                    </div>
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
                        <?php $card_body = str_replace('20% de descuento', '<strong>20% de descuento</strong>', (string) $card['body']); ?>
                        <article class="gsgcl-card">
                            <span class="gsgcl-card__icon gsgcl-card__icon--<?php echo esc_attr($icon_classes[$idx] ?? 'person'); ?>"></span>
                            <h4><?php echo esc_html($card['title']); ?></h4>
                            <p><?php echo wp_kses($card_body, array('strong' => array())); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_steps_section($config)
    {
        $steps_image_url = 'https://gsgeducation.com/wp-content/uploads/2026/04/Banner-collage-estudiantes-celebracion1.png';
        ?>
        <section class="gsgcl-steps">
            <div class="gsgcl-wrap">
                <div class="gsgcl-steps__layout">
                    <div class="gsgcl-steps__visual" style="background-image:url('<?php echo esc_url($steps_image_url); ?>');"></div>
                    <div class="gsgcl-steps__cards">
                        <header class="gsgcl-section-header gsgcl-steps__header">
                            <h2><?php echo esc_html($config['steps_heading']); ?></h2>
                            <p><?php echo esc_html($config['steps_subheading']); ?></p>
                        </header>
                        <?php foreach ($config['steps'] as $index => $step) : ?>
                            <article class="gsgcl-step">
                                <div class="gsgcl-step__number"><?php echo esc_html((string) ($index + 1)); ?></div>
                                <div class="gsgcl-step__copy">
                                    <h3><?php echo esc_html($step['title']); ?></h3>
                                    <p><?php echo esc_html($step['body']); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    private function render_form_section($config, $landing_id, $page_id, $notice_type, $notice_message)
    {
        $countries = $this->get_form_countries();
        ?>
        <section id="gsgcl-form" class="gsgcl-form-section">
            <div class="gsgcl-wrap">
                <div class="gsgcl-form-card">
                    <header class="gsgcl-form-card__header">
                        <h2><?php echo esc_html($config['form_heading']); ?></h2>
                        <p><?php echo esc_html($config['form_description']); ?></p>
                    </header>

                    <form class="gsgcl-form" id="gsgcl-landing-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
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
                                        <select id="gsgcl_friend_destination" name="friend_destination">
                                            <?php foreach ($countries as $country) : ?>
                                                <option value="<?php echo esc_attr($country['name']); ?>" data-country-code="<?php echo esc_attr($country['code']); ?>" data-dial-code="<?php echo esc_attr($country['dial_code']); ?>"<?php selected('PE', $country['code']); ?>><?php echo esc_html($country['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label><span><?php echo esc_html__('WhatsApp', 'gsg-custom-landings'); ?></span>
                                        <span class="gsgcl-phone-field">
                                            <input type="text" id="gsgcl_friend_whatsapp_prefix" class="gsgcl-phone-field__prefix" value="+51" readonly aria-label="<?php echo esc_attr__('Prefijo de país', 'gsg-custom-landings'); ?>" />
                                            <input type="tel" id="gsgcl_friend_whatsapp_local" class="gsgcl-phone-field__number" inputmode="tel" autocomplete="tel-national" placeholder="999999999" aria-label="<?php echo esc_attr__('Número de WhatsApp', 'gsg-custom-landings'); ?>" />
                                        </span>
                                        <input type="hidden" id="gsgcl_friend_whatsapp" name="friend_whatsapp" value="+51" />
                                    </label>
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('E-mail', 'gsg-custom-landings'); ?></span><input type="email" name="friend_email" autocomplete="email" required /></label>
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('¿Qué le interesa?', 'gsg-custom-landings'); ?></span>
                                        <select name="friend_interest">
                                            <option value=""><?php echo esc_html__('Seleccione una opción', 'gsg-custom-landings'); ?></option>
                                            <option value="Curso de preparación">Curso de preparación</option>
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
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('E-mail', 'gsg-custom-landings'); ?></span><input type="email" name="student_email" autocomplete="email" required /></label>
                                    <label class="gsgcl-field-grid__full"><span><?php echo esc_html__('Comentario opcional', 'gsg-custom-landings'); ?></span><textarea name="student_comments" rows="4" placeholder="<?php echo esc_attr__('¿Algo que debamos saber?', 'gsg-custom-landings'); ?>"></textarea></label>
                                </div>
                            </div>
                        </div>

                        <button class="gsgcl-button gsgcl-button--accent gsgcl-button--submit" id="gsgcl-form-submit" type="submit"><?php echo esc_html($config['primary_cta_label']); ?></button>
                        <div
                            id="gsgcl-form-notice"
                            class="gsgcl-notice<?php echo $notice_type ? ' gsgcl-notice--' . esc_attr($notice_type) : ''; ?>"
                            <?php if (! $notice_type || ! $notice_message) : ?>hidden<?php endif; ?>
                        >
                            <?php echo esc_html($notice_message); ?>
                        </div>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var form = document.getElementById('gsgcl-landing-form');
                            var notice = document.getElementById('gsgcl-form-notice');
                            var submitButton = document.getElementById('gsgcl-form-submit');
                            var countrySelect = document.getElementById('gsgcl_friend_destination');
                            var whatsappInput = document.getElementById('gsgcl_friend_whatsapp');
                            var whatsappPrefix = document.getElementById('gsgcl_friend_whatsapp_prefix');
                            var whatsappNumber = document.getElementById('gsgcl_friend_whatsapp_local');

                            if (!form || !notice || !submitButton || !countrySelect || !whatsappInput || !whatsappPrefix || !whatsappNumber) {
                                return;
                            }

                            var defaultSubmitLabel = submitButton.textContent;

                            var renderNotice = function (type, message) {
                                notice.hidden = false;
                                notice.className = 'gsgcl-notice gsgcl-notice--' + type;
                                notice.textContent = message;
                            };

                            var setSubmittingState = function (isSubmitting) {
                                submitButton.disabled = isSubmitting;
                                submitButton.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
                                submitButton.textContent = isSubmitting ? 'Enviando...' : defaultSubmitLabel;
                            };

                            var syncWhatsappValue = function (prefix) {
                                var cleanNumber = (whatsappNumber.value || '').replace(/[^\d]/g, '');

                                whatsappNumber.value = cleanNumber;
                                whatsappInput.value = cleanNumber ? prefix + cleanNumber : prefix;
                            };

                            var syncWhatsappPrefix = function () {
                                var selectedOption = countrySelect.options[countrySelect.selectedIndex];
                                var nextPrefix = selectedOption && selectedOption.dataset.dialCode ? selectedOption.dataset.dialCode : '+51';
                                whatsappPrefix.value = nextPrefix;
                                whatsappInput.dataset.dialCode = nextPrefix;
                                syncWhatsappValue(nextPrefix);
                            };

                            var getPreferredRegionCodes = function () {
                                var localeCandidates = [];
                                var regions = [];
                                var seen = {};
                                var extractRegion = function (locale) {
                                    var match = locale && locale.match(/[-_]([A-Za-z]{2})\b/);

                                    return match ? match[1].toUpperCase() : '';
                                };
                                var pushRegion = function (region) {
                                    if (!region || seen[region]) {
                                        return;
                                    }

                                    seen[region] = true;
                                    regions.push(region);
                                };

                                if (Array.isArray(navigator.languages)) {
                                    localeCandidates = localeCandidates.concat(navigator.languages);
                                }

                                localeCandidates.push(navigator.language || '');
                                localeCandidates.push(document.documentElement.lang || '');

                                if (window.Intl && Intl.DateTimeFormat) {
                                    localeCandidates.push(Intl.DateTimeFormat().resolvedOptions().locale || '');
                                }

                                localeCandidates.forEach(function (locale) {
                                    pushRegion(extractRegion(locale));
                                });

                                return regions;
                            };

                            var preselectCountry = function () {
                                if (countrySelect.value) {
                                    return;
                                }

                                var regions = getPreferredRegionCodes();

                                if (!regions.length) {
                                    return;
                                }

                                Array.prototype.some.call(countrySelect.options, function (option, index) {
                                    if (regions.indexOf((option.dataset.countryCode || '').toUpperCase()) === -1) {
                                        return false;
                                    }

                                    countrySelect.selectedIndex = index;
                                    return true;
                                });
                            };

                            countrySelect.addEventListener('change', syncWhatsappPrefix);
                            whatsappNumber.addEventListener('input', function () {
                                syncWhatsappValue(whatsappPrefix.value || '+51');
                            });

                            form.addEventListener('submit', function (event) {
                                event.preventDefault();
                                syncWhatsappValue(whatsappPrefix.value || '+51');

                                var formData = new FormData(form);
                                formData.set('action', 'gsgcl_submit_landing_async');

                                setSubmittingState(true);

                                fetch(form.dataset.ajaxUrl, {
                                    method: 'POST',
                                    body: formData,
                                    credentials: 'same-origin'
                                })
                                    .then(function (response) {
                                        return response.json().then(function (data) {
                                            return {
                                                ok: response.ok,
                                                data: data
                                            };
                                        });
                                    })
                                    .then(function (result) {
                                        var payload = result.data && result.data.data ? result.data.data : null;
                                        if (!result.ok || !payload || payload.status !== 'success') {
                                            throw payload || { message: '<?php echo esc_js($config['error_message']); ?>' };
                                        }

                                        renderNotice('success', payload.message || '<?php echo esc_js($config['success_message']); ?>');
                                        form.reset();
                                        syncWhatsappPrefix();
                                    })
                                    .catch(function (error) {
                                        var message = error && error.message ? error.message : '<?php echo esc_js($config['error_message']); ?>';
                                        renderNotice('error', message);
                                    })
                                    .finally(function () {
                                        setSubmittingState(false);
                                    });
                            });

                            preselectCountry();
                            syncWhatsappPrefix();
                        });
                    </script>
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
                        <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url('https://conectiontool.mantra.chat/tools/u/accbc76f-7622-44d5-9f71-58963e49a0f2'); ?>" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.503 3.935 1.389 5.611L0 24l6.597-1.332A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-1.875 0-3.622-.525-5.113-1.433l-.366-.218-3.797.766.8-3.692-.24-.381A9.713 9.713 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg> <?php echo esc_html__('Escríbenos por WhatsApp - Perú y Latam', 'gsg-custom-landings'); ?></a>
                        <a class="gsgcl-button gsgcl-button--whatsapp" href="<?php echo esc_url('https://wa.link/sghvfb'); ?>" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.503 3.935 1.389 5.611L0 24l6.597-1.332A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-1.875 0-3.622-.525-5.113-1.433l-.366-.218-3.797.766.8-3.692-.24-.381A9.713 9.713 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg> <?php echo esc_html__('Escríbenos por WhatsApp - Colombia', 'gsg-custom-landings'); ?></a>
                    </div>
                </aside>
            </div>
        </section>
        <?php
    }

    private function get_landing_config($landing_id)
    {
        $success_message = $this->plugin->get_landing_meta($landing_id, 'gsgcl_success_message', 'Gracias. Tu solicitud ha sido registrada con éxito.');
        if ('' === trim((string) $success_message) || 'Gracias. Recibimos tu registro y nuestro equipo revisará el caso pronto.' === trim((string) $success_message)) {
            $success_message = 'Gracias. Tu solicitud ha sido registrada con éxito.';
        }

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
            'success_message' => $success_message,
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

    private function get_form_countries()
    {
        return array(
            array('code' => 'AF', 'name' => 'Afganistán', 'dial_code' => '+93'),
            array('code' => 'AL', 'name' => 'Albania', 'dial_code' => '+355'),
            array('code' => 'DE', 'name' => 'Alemania', 'dial_code' => '+49'),
            array('code' => 'AD', 'name' => 'Andorra', 'dial_code' => '+376'),
            array('code' => 'AO', 'name' => 'Angola', 'dial_code' => '+244'),
            array('code' => 'AG', 'name' => 'Antigua y Barbuda', 'dial_code' => '+1-268'),
            array('code' => 'SA', 'name' => 'Arabia Saudita', 'dial_code' => '+966'),
            array('code' => 'DZ', 'name' => 'Argelia', 'dial_code' => '+213'),
            array('code' => 'AR', 'name' => 'Argentina', 'dial_code' => '+54'),
            array('code' => 'AM', 'name' => 'Armenia', 'dial_code' => '+374'),
            array('code' => 'AU', 'name' => 'Australia', 'dial_code' => '+61'),
            array('code' => 'AT', 'name' => 'Austria', 'dial_code' => '+43'),
            array('code' => 'AZ', 'name' => 'Azerbaiyán', 'dial_code' => '+994'),
            array('code' => 'BS', 'name' => 'Bahamas', 'dial_code' => '+1-242'),
            array('code' => 'BH', 'name' => 'Baréin', 'dial_code' => '+973'),
            array('code' => 'BD', 'name' => 'Bangladés', 'dial_code' => '+880'),
            array('code' => 'BB', 'name' => 'Barbados', 'dial_code' => '+1-246'),
            array('code' => 'BE', 'name' => 'Bélgica', 'dial_code' => '+32'),
            array('code' => 'BZ', 'name' => 'Belice', 'dial_code' => '+501'),
            array('code' => 'BJ', 'name' => 'Benín', 'dial_code' => '+229'),
            array('code' => 'BY', 'name' => 'Bielorrusia', 'dial_code' => '+375'),
            array('code' => 'MM', 'name' => 'Birmania', 'dial_code' => '+95'),
            array('code' => 'BO', 'name' => 'Bolivia', 'dial_code' => '+591'),
            array('code' => 'BA', 'name' => 'Bosnia y Herzegovina', 'dial_code' => '+387'),
            array('code' => 'BW', 'name' => 'Botsuana', 'dial_code' => '+267'),
            array('code' => 'BR', 'name' => 'Brasil', 'dial_code' => '+55'),
            array('code' => 'BN', 'name' => 'Brunéi', 'dial_code' => '+673'),
            array('code' => 'BG', 'name' => 'Bulgaria', 'dial_code' => '+359'),
            array('code' => 'BF', 'name' => 'Burkina Faso', 'dial_code' => '+226'),
            array('code' => 'BI', 'name' => 'Burundi', 'dial_code' => '+257'),
            array('code' => 'BT', 'name' => 'Bután', 'dial_code' => '+975'),
            array('code' => 'CV', 'name' => 'Cabo Verde', 'dial_code' => '+238'),
            array('code' => 'KH', 'name' => 'Camboya', 'dial_code' => '+855'),
            array('code' => 'CM', 'name' => 'Camerún', 'dial_code' => '+237'),
            array('code' => 'CA', 'name' => 'Canadá', 'dial_code' => '+1'),
            array('code' => 'QA', 'name' => 'Catar', 'dial_code' => '+974'),
            array('code' => 'TD', 'name' => 'Chad', 'dial_code' => '+235'),
            array('code' => 'CL', 'name' => 'Chile', 'dial_code' => '+56'),
            array('code' => 'CN', 'name' => 'China', 'dial_code' => '+86'),
            array('code' => 'CY', 'name' => 'Chipre', 'dial_code' => '+357'),
            array('code' => 'CO', 'name' => 'Colombia', 'dial_code' => '+57'),
            array('code' => 'KM', 'name' => 'Comoras', 'dial_code' => '+269'),
            array('code' => 'KP', 'name' => 'Corea del Norte', 'dial_code' => '+850'),
            array('code' => 'KR', 'name' => 'Corea del Sur', 'dial_code' => '+82'),
            array('code' => 'CI', 'name' => 'Costa de Marfil', 'dial_code' => '+225'),
            array('code' => 'CR', 'name' => 'Costa Rica', 'dial_code' => '+506'),
            array('code' => 'HR', 'name' => 'Croacia', 'dial_code' => '+385'),
            array('code' => 'CU', 'name' => 'Cuba', 'dial_code' => '+53'),
            array('code' => 'DK', 'name' => 'Dinamarca', 'dial_code' => '+45'),
            array('code' => 'DM', 'name' => 'Dominica', 'dial_code' => '+1-767'),
            array('code' => 'EC', 'name' => 'Ecuador', 'dial_code' => '+593'),
            array('code' => 'EG', 'name' => 'Egipto', 'dial_code' => '+20'),
            array('code' => 'SV', 'name' => 'El Salvador', 'dial_code' => '+503'),
            array('code' => 'AE', 'name' => 'Emiratos Árabes Unidos', 'dial_code' => '+971'),
            array('code' => 'ER', 'name' => 'Eritrea', 'dial_code' => '+291'),
            array('code' => 'SK', 'name' => 'Eslovaquia', 'dial_code' => '+421'),
            array('code' => 'SI', 'name' => 'Eslovenia', 'dial_code' => '+386'),
            array('code' => 'ES', 'name' => 'España', 'dial_code' => '+34'),
            array('code' => 'US', 'name' => 'Estados Unidos', 'dial_code' => '+1'),
            array('code' => 'EE', 'name' => 'Estonia', 'dial_code' => '+372'),
            array('code' => 'SZ', 'name' => 'Esuatini', 'dial_code' => '+268'),
            array('code' => 'ET', 'name' => 'Etiopía', 'dial_code' => '+251'),
            array('code' => 'PH', 'name' => 'Filipinas', 'dial_code' => '+63'),
            array('code' => 'FI', 'name' => 'Finlandia', 'dial_code' => '+358'),
            array('code' => 'FJ', 'name' => 'Fiyi', 'dial_code' => '+679'),
            array('code' => 'FR', 'name' => 'Francia', 'dial_code' => '+33'),
            array('code' => 'GA', 'name' => 'Gabón', 'dial_code' => '+241'),
            array('code' => 'GM', 'name' => 'Gambia', 'dial_code' => '+220'),
            array('code' => 'GE', 'name' => 'Georgia', 'dial_code' => '+995'),
            array('code' => 'GH', 'name' => 'Ghana', 'dial_code' => '+233'),
            array('code' => 'GD', 'name' => 'Granada', 'dial_code' => '+1-473'),
            array('code' => 'GR', 'name' => 'Grecia', 'dial_code' => '+30'),
            array('code' => 'GT', 'name' => 'Guatemala', 'dial_code' => '+502'),
            array('code' => 'GN', 'name' => 'Guinea', 'dial_code' => '+224'),
            array('code' => 'GQ', 'name' => 'Guinea Ecuatorial', 'dial_code' => '+240'),
            array('code' => 'GW', 'name' => 'Guinea-Bisáu', 'dial_code' => '+245'),
            array('code' => 'GY', 'name' => 'Guyana', 'dial_code' => '+592'),
            array('code' => 'HT', 'name' => 'Haití', 'dial_code' => '+509'),
            array('code' => 'HN', 'name' => 'Honduras', 'dial_code' => '+504'),
            array('code' => 'HU', 'name' => 'Hungría', 'dial_code' => '+36'),
            array('code' => 'IN', 'name' => 'India', 'dial_code' => '+91'),
            array('code' => 'ID', 'name' => 'Indonesia', 'dial_code' => '+62'),
            array('code' => 'IQ', 'name' => 'Irak', 'dial_code' => '+964'),
            array('code' => 'IR', 'name' => 'Irán', 'dial_code' => '+98'),
            array('code' => 'IE', 'name' => 'Irlanda', 'dial_code' => '+353'),
            array('code' => 'IS', 'name' => 'Islandia', 'dial_code' => '+354'),
            array('code' => 'IL', 'name' => 'Israel', 'dial_code' => '+972'),
            array('code' => 'IT', 'name' => 'Italia', 'dial_code' => '+39'),
            array('code' => 'JM', 'name' => 'Jamaica', 'dial_code' => '+1-876'),
            array('code' => 'JP', 'name' => 'Japón', 'dial_code' => '+81'),
            array('code' => 'JO', 'name' => 'Jordania', 'dial_code' => '+962'),
            array('code' => 'KZ', 'name' => 'Kazajistán', 'dial_code' => '+7'),
            array('code' => 'KE', 'name' => 'Kenia', 'dial_code' => '+254'),
            array('code' => 'KG', 'name' => 'Kirguistán', 'dial_code' => '+996'),
            array('code' => 'KI', 'name' => 'Kiribati', 'dial_code' => '+686'),
            array('code' => 'XK', 'name' => 'Kosovo', 'dial_code' => '+383'),
            array('code' => 'KW', 'name' => 'Kuwait', 'dial_code' => '+965'),
            array('code' => 'LA', 'name' => 'Laos', 'dial_code' => '+856'),
            array('code' => 'LS', 'name' => 'Lesoto', 'dial_code' => '+266'),
            array('code' => 'LV', 'name' => 'Letonia', 'dial_code' => '+371'),
            array('code' => 'LB', 'name' => 'Líbano', 'dial_code' => '+961'),
            array('code' => 'LR', 'name' => 'Liberia', 'dial_code' => '+231'),
            array('code' => 'LY', 'name' => 'Libia', 'dial_code' => '+218'),
            array('code' => 'LI', 'name' => 'Liechtenstein', 'dial_code' => '+423'),
            array('code' => 'LT', 'name' => 'Lituania', 'dial_code' => '+370'),
            array('code' => 'LU', 'name' => 'Luxemburgo', 'dial_code' => '+352'),
            array('code' => 'MG', 'name' => 'Madagascar', 'dial_code' => '+261'),
            array('code' => 'MY', 'name' => 'Malasia', 'dial_code' => '+60'),
            array('code' => 'MW', 'name' => 'Malaui', 'dial_code' => '+265'),
            array('code' => 'MV', 'name' => 'Maldivas', 'dial_code' => '+960'),
            array('code' => 'ML', 'name' => 'Malí', 'dial_code' => '+223'),
            array('code' => 'MT', 'name' => 'Malta', 'dial_code' => '+356'),
            array('code' => 'MA', 'name' => 'Marruecos', 'dial_code' => '+212'),
            array('code' => 'MH', 'name' => 'Islas Marshall', 'dial_code' => '+692'),
            array('code' => 'MU', 'name' => 'Mauricio', 'dial_code' => '+230'),
            array('code' => 'MR', 'name' => 'Mauritania', 'dial_code' => '+222'),
            array('code' => 'MX', 'name' => 'México', 'dial_code' => '+52'),
            array('code' => 'FM', 'name' => 'Micronesia', 'dial_code' => '+691'),
            array('code' => 'MD', 'name' => 'Moldavia', 'dial_code' => '+373'),
            array('code' => 'MC', 'name' => 'Mónaco', 'dial_code' => '+377'),
            array('code' => 'MN', 'name' => 'Mongolia', 'dial_code' => '+976'),
            array('code' => 'ME', 'name' => 'Montenegro', 'dial_code' => '+382'),
            array('code' => 'MZ', 'name' => 'Mozambique', 'dial_code' => '+258'),
            array('code' => 'NA', 'name' => 'Namibia', 'dial_code' => '+264'),
            array('code' => 'NR', 'name' => 'Nauru', 'dial_code' => '+674'),
            array('code' => 'NP', 'name' => 'Nepal', 'dial_code' => '+977'),
            array('code' => 'NI', 'name' => 'Nicaragua', 'dial_code' => '+505'),
            array('code' => 'NE', 'name' => 'Níger', 'dial_code' => '+227'),
            array('code' => 'NG', 'name' => 'Nigeria', 'dial_code' => '+234'),
            array('code' => 'NO', 'name' => 'Noruega', 'dial_code' => '+47'),
            array('code' => 'NZ', 'name' => 'Nueva Zelanda', 'dial_code' => '+64'),
            array('code' => 'OM', 'name' => 'Omán', 'dial_code' => '+968'),
            array('code' => 'NL', 'name' => 'Países Bajos', 'dial_code' => '+31'),
            array('code' => 'PK', 'name' => 'Pakistán', 'dial_code' => '+92'),
            array('code' => 'PW', 'name' => 'Palaos', 'dial_code' => '+680'),
            array('code' => 'PS', 'name' => 'Palestina', 'dial_code' => '+970'),
            array('code' => 'PA', 'name' => 'Panamá', 'dial_code' => '+507'),
            array('code' => 'PG', 'name' => 'Papúa Nueva Guinea', 'dial_code' => '+675'),
            array('code' => 'PY', 'name' => 'Paraguay', 'dial_code' => '+595'),
            array('code' => 'PE', 'name' => 'Perú', 'dial_code' => '+51'),
            array('code' => 'PL', 'name' => 'Polonia', 'dial_code' => '+48'),
            array('code' => 'PT', 'name' => 'Portugal', 'dial_code' => '+351'),
            array('code' => 'PR', 'name' => 'Puerto Rico', 'dial_code' => '+1-787'),
            array('code' => 'GB', 'name' => 'Reino Unido', 'dial_code' => '+44'),
            array('code' => 'CF', 'name' => 'República Centroafricana', 'dial_code' => '+236'),
            array('code' => 'CZ', 'name' => 'República Checa', 'dial_code' => '+420'),
            array('code' => 'CG', 'name' => 'República del Congo', 'dial_code' => '+242'),
            array('code' => 'CD', 'name' => 'República Democrática del Congo', 'dial_code' => '+243'),
            array('code' => 'DO', 'name' => 'República Dominicana', 'dial_code' => '+1-809'),
            array('code' => 'RW', 'name' => 'Ruanda', 'dial_code' => '+250'),
            array('code' => 'RO', 'name' => 'Rumania', 'dial_code' => '+40'),
            array('code' => 'RU', 'name' => 'Rusia', 'dial_code' => '+7'),
            array('code' => 'WS', 'name' => 'Samoa', 'dial_code' => '+685'),
            array('code' => 'KN', 'name' => 'San Cristóbal y Nieves', 'dial_code' => '+1-869'),
            array('code' => 'SM', 'name' => 'San Marino', 'dial_code' => '+378'),
            array('code' => 'VC', 'name' => 'San Vicente y las Granadinas', 'dial_code' => '+1-784'),
            array('code' => 'LC', 'name' => 'Santa Lucía', 'dial_code' => '+1-758'),
            array('code' => 'ST', 'name' => 'Santo Tomé y Príncipe', 'dial_code' => '+239'),
            array('code' => 'SN', 'name' => 'Senegal', 'dial_code' => '+221'),
            array('code' => 'RS', 'name' => 'Serbia', 'dial_code' => '+381'),
            array('code' => 'SC', 'name' => 'Seychelles', 'dial_code' => '+248'),
            array('code' => 'SL', 'name' => 'Sierra Leona', 'dial_code' => '+232'),
            array('code' => 'SG', 'name' => 'Singapur', 'dial_code' => '+65'),
            array('code' => 'SY', 'name' => 'Siria', 'dial_code' => '+963'),
            array('code' => 'SO', 'name' => 'Somalia', 'dial_code' => '+252'),
            array('code' => 'LK', 'name' => 'Sri Lanka', 'dial_code' => '+94'),
            array('code' => 'ZA', 'name' => 'Sudáfrica', 'dial_code' => '+27'),
            array('code' => 'SD', 'name' => 'Sudán', 'dial_code' => '+249'),
            array('code' => 'SS', 'name' => 'Sudán del Sur', 'dial_code' => '+211'),
            array('code' => 'SE', 'name' => 'Suecia', 'dial_code' => '+46'),
            array('code' => 'CH', 'name' => 'Suiza', 'dial_code' => '+41'),
            array('code' => 'SR', 'name' => 'Surinam', 'dial_code' => '+597'),
            array('code' => 'TH', 'name' => 'Tailandia', 'dial_code' => '+66'),
            array('code' => 'TW', 'name' => 'Taiwán', 'dial_code' => '+886'),
            array('code' => 'TZ', 'name' => 'Tanzania', 'dial_code' => '+255'),
            array('code' => 'TJ', 'name' => 'Tayikistán', 'dial_code' => '+992'),
            array('code' => 'TL', 'name' => 'Timor Oriental', 'dial_code' => '+670'),
            array('code' => 'TG', 'name' => 'Togo', 'dial_code' => '+228'),
            array('code' => 'TO', 'name' => 'Tonga', 'dial_code' => '+676'),
            array('code' => 'TT', 'name' => 'Trinidad y Tobago', 'dial_code' => '+1-868'),
            array('code' => 'TN', 'name' => 'Túnez', 'dial_code' => '+216'),
            array('code' => 'TM', 'name' => 'Turkmenistán', 'dial_code' => '+993'),
            array('code' => 'TR', 'name' => 'Turquía', 'dial_code' => '+90'),
            array('code' => 'TV', 'name' => 'Tuvalu', 'dial_code' => '+688'),
            array('code' => 'UA', 'name' => 'Ucrania', 'dial_code' => '+380'),
            array('code' => 'UG', 'name' => 'Uganda', 'dial_code' => '+256'),
            array('code' => 'UY', 'name' => 'Uruguay', 'dial_code' => '+598'),
            array('code' => 'UZ', 'name' => 'Uzbekistán', 'dial_code' => '+998'),
            array('code' => 'VU', 'name' => 'Vanuatu', 'dial_code' => '+678'),
            array('code' => 'VA', 'name' => 'Vaticano', 'dial_code' => '+379'),
            array('code' => 'VE', 'name' => 'Venezuela', 'dial_code' => '+58'),
            array('code' => 'VN', 'name' => 'Vietnam', 'dial_code' => '+84'),
            array('code' => 'YE', 'name' => 'Yemen', 'dial_code' => '+967'),
            array('code' => 'DJ', 'name' => 'Yibuti', 'dial_code' => '+253'),
            array('code' => 'ZM', 'name' => 'Zambia', 'dial_code' => '+260'),
            array('code' => 'ZW', 'name' => 'Zimbabue', 'dial_code' => '+263'),
        );
    }

    private function normalize_hero_image_url($value)
    {
        $value = trim((string) $value);
        $legacy_demo_url = 'https://images.unsplash.com/photo-1505764706515-aa95265c5abc?auto=format&fit=crop&w=1200&q=80';
        $legacy_local_demo_url = GSGCL_URL . 'assets/demo-images/Banner 1800x600 px-100.jpg';
        $demo_banner_url = 'https://gsgeducation.com/wp-content/uploads/2026/04/Banner-1800x600-px-02.jpg';

        if ('' === $value || $legacy_demo_url === $value || $legacy_local_demo_url === $value) {
            return $demo_banner_url;
        }

        return $value;
    }

    private function is_demo_hero_banner($value)
    {
        $value = (string) $value;

        return false !== strpos($value, 'Banner-1800x600-px-02.jpg')
            || false !== strpos($value, 'assets/demo-images/Banner 1800x600 px-100.jpg');
    }

    private function explode_lines($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        return array_values($lines);
    }
}