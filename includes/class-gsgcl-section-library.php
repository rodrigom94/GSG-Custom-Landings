<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Section_Library
{
    private $plugin;

    private $ai;

    public function __construct($plugin, $ai)
    {
        $this->plugin = $plugin;
        $this->ai = $ai;

        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_gsg_section', array($this, 'save_section_meta'));
        add_action('admin_post_gsgcl_generate_section_proposals', array($this, 'handle_generate_proposals'));
        add_action('admin_post_gsgcl_apply_section_proposal', array($this, 'handle_apply_proposal'));
        add_action('admin_post_gsgcl_restore_section_revision', array($this, 'handle_restore_revision'));
        add_action('admin_post_gsgcl_duplicate_section', array($this, 'handle_duplicate_section'));
        add_action('wp_ajax_gsgcl_preview_section', array($this, 'ajax_preview_section'));
        add_action('wp_ajax_gsgcl_save_section', array($this, 'ajax_save_section'));
        add_action('wp_ajax_gsgcl_restore_section', array($this, 'ajax_restore_section'));
        add_action('wp_ajax_gsgcl_generate_proposals', array($this, 'ajax_generate_proposals'));
        add_action('wp_ajax_gsgcl_apply_proposal', array($this, 'ajax_apply_proposal'));
        add_action('admin_notices', array($this, 'render_admin_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('post_row_actions', array($this, 'register_row_actions'), 10, 2);
        add_filter('manage_gsg_section_posts_columns', array($this, 'register_columns'));
        add_action('manage_gsg_section_posts_custom_column', array($this, 'render_column'), 10, 2);
    }

    public function register_post_type()
    {
        register_post_type(
            'gsg_section',
            array(
                'labels' => array(
                    'name' => __('GSG Sections', 'gsg-custom-landings'),
                    'singular_name' => __('GSG Section', 'gsg-custom-landings'),
                    'add_new_item' => __('Crear sección', 'gsg-custom-landings'),
                    'edit_item' => __('Editar sección', 'gsg-custom-landings'),
                    'menu_name' => __('Sections Library', 'gsg-custom-landings'),
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=gsg_landing',
                'supports' => array('title'),
                'menu_icon' => 'dashicons-screenoptions',
                'capability_type' => 'post',
                'map_meta_cap' => true,
            )
        );
    }

    public function register_meta_boxes()
    {
        add_meta_box(
            'gsgcl_section_setup',
            __('Configuración de sección', 'gsg-custom-landings'),
            array($this, 'render_setup_metabox'),
            'gsg_section',
            'normal',
            'high'
        );

        add_meta_box(
            'gsgcl_section_preview',
            __('Preview HTML y brief', 'gsg-custom-landings'),
            array($this, 'render_preview_metabox'),
            'gsg_section',
            'normal',
            'default'
        );

        add_meta_box(
            'gsgcl_section_proposals',
            __('Propuestas, revisiones y rollback', 'gsg-custom-landings'),
            array($this, 'render_proposals_metabox'),
            'gsg_section',
            'side',
            'default'
        );

        add_meta_box(
            'gsgcl_section_live_preview',
            __('Preview live', 'gsg-custom-landings'),
            array($this, 'render_live_preview_metabox'),
            'gsg_section',
            'normal',
            'low'
        );
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        if (! in_array($hook_suffix, array('post.php', 'post-new.php', 'edit.php'), true)) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen || 'gsg_section' !== $screen->post_type) {
            return;
        }

        wp_register_style('gsgcl-section-admin', false, array(), GSGCL_VERSION);
        wp_enqueue_style('gsgcl-section-admin');
        wp_add_inline_style('gsgcl-section-admin', $this->get_inline_admin_css());

        wp_register_style('gsgcl-section-admin-ui', false, array(), GSGCL_VERSION);
        wp_enqueue_style('gsgcl-section-admin-ui');
        wp_add_inline_style(
            'gsgcl-section-admin-ui',
            'body.folded #adminmenuback, body.folded #adminmenuwrap, body.folded #adminmenu { width: 30px; } body.folded #wpcontent, body.folded #wpfooter { margin-left: 30px; } body.folded #adminmenu .wp-submenu { left: 30px; }'
        );
    }

    public function render_setup_metabox($post)
    {
        wp_nonce_field('gsgcl_save_section', 'gsgcl_section_nonce');

        $section_type = $this->get_section_meta($post->ID, 'gsgcl_section_type', 'counter');
        $variant = $this->get_section_meta($post->ID, 'gsgcl_section_variant', 'default-v1');
        $version = $this->get_section_meta($post->ID, 'gsgcl_section_version', '1');
        $reference_id = absint($this->get_section_meta($post->ID, 'gsgcl_section_reference_id', '0'));
        $root_id = absint($this->get_section_meta($post->ID, 'gsgcl_section_root_id', '0'));
        $reference_image_url = $this->get_section_meta($post->ID, 'gsgcl_section_reference_image_url', '');
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="gsgcl_section_type"><?php echo esc_html__('Tipo de sección', 'gsg-custom-landings'); ?></label></th>
                    <td>
                        <select name="gsgcl_section_type" id="gsgcl_section_type">
                            <?php foreach ($this->get_section_types() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($section_type, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsgcl_section_variant"><?php echo esc_html__('Variant key', 'gsg-custom-landings'); ?></label></th>
                    <td><input class="regular-text" type="text" name="gsgcl_section_variant" id="gsgcl_section_variant" value="<?php echo esc_attr($variant); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsgcl_section_version"><?php echo esc_html__('Versión', 'gsg-custom-landings'); ?></label></th>
                    <td><input class="small-text" type="number" min="1" name="gsgcl_section_version" id="gsgcl_section_version" value="<?php echo esc_attr($version); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsgcl_section_reference_image_url"><?php echo esc_html__('Imagen de referencia', 'gsg-custom-landings'); ?></label></th>
                    <td>
                        <input class="regular-text" type="url" name="gsgcl_section_reference_image_url" id="gsgcl_section_reference_image_url" value="<?php echo esc_attr($reference_image_url); ?>" placeholder="https://..." />
                        <p class="description"><?php echo esc_html__('Pega una imagen de Figma o mockup para usarla como referencia visual de la sección.', 'gsg-custom-landings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Referencia', 'gsg-custom-landings'); ?></th>
                    <td>
                        <p><?php echo $reference_id ? esc_html(sprintf(__('Section #%d', 'gsg-custom-landings'), $reference_id)) : esc_html__('Sin referencia', 'gsg-custom-landings'); ?></p>
                        <p><?php echo $root_id ? esc_html(sprintf(__('Raíz de linaje #%d', 'gsg-custom-landings'), $root_id)) : esc_html__('Sin raíz registrada', 'gsg-custom-landings'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function render_preview_metabox($post)
    {
        $brief = $this->get_section_meta($post->ID, 'gsgcl_section_brief', '');
        $preview_html = $this->get_section_meta($post->ID, 'gsgcl_section_preview_html', '');
        $reference_image_url = $this->get_section_meta($post->ID, 'gsgcl_section_reference_image_url', '');
        $analysis = $this->get_analysis($post->ID);
        ?>
        <div class="gsgcl-admin-stack">
            <p>
                <label for="gsgcl_section_brief"><strong><?php echo esc_html__('Brief del editor', 'gsg-custom-landings'); ?></strong></label>
                <textarea class="large-text code" rows="5" name="gsgcl_section_brief" id="gsgcl_section_brief"><?php echo esc_textarea($brief); ?></textarea>
            </p>
            <p>
                <label for="gsgcl_section_preview_html"><strong><?php echo esc_html__('HTML preview editable', 'gsg-custom-landings'); ?></strong></label>
                <textarea class="large-text code gsgcl-code-area" rows="18" name="gsgcl_section_preview_html" id="gsgcl_section_preview_html"><?php echo esc_textarea($preview_html); ?></textarea>
            </p>
            <p>
                <label for="gsgcl_section_reference_image_url_preview"><strong><?php echo esc_html__('Referencia visual', 'gsg-custom-landings'); ?></strong></label>
                <input class="large-text gsgcl-section-reference-image" type="url" name="gsgcl_section_reference_image_url_preview" id="gsgcl_section_reference_image_url_preview" value="<?php echo esc_attr($reference_image_url); ?>" placeholder="https://..." />
            </p>
            <div class="gsgcl-live-actions" data-section-id="<?php echo esc_attr((string) $post->ID); ?>">
                <button type="button" class="button button-secondary gsgcl-preview-live-button"><?php echo esc_html__('Actualizar preview live', 'gsg-custom-landings'); ?></button>
                <button type="button" class="button button-primary gsgcl-save-live-button"><?php echo esc_html__('Guardar revisión sin recarga', 'gsg-custom-landings'); ?></button>
                <span class="gsgcl-live-status" aria-live="polite"></span>
            </div>
            <?php if ($post->ID) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gsgcl-inline-form">
                    <input type="hidden" name="action" value="gsgcl_generate_section_proposals" />
                    <input type="hidden" name="section_id" value="<?php echo esc_attr($post->ID); ?>" />
                    <?php wp_nonce_field('gsgcl_generate_section_proposals_' . $post->ID, 'gsgcl_action_nonce'); ?>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Generar 3 propuestas', 'gsg-custom-landings'); ?></button>
                    <span class="description"><?php echo esc_html__('Usa el brief actual y el preview como referencia para proponer variaciones de la sección.', 'gsg-custom-landings'); ?></span>
                </form>
            <?php endif; ?>
            <div class="gsgcl-analysis-box">
                <h4><?php echo esc_html__('Análisis actual del HTML', 'gsg-custom-landings'); ?></h4>
                <ul>
                    <li><?php echo esc_html(sprintf(__('Headings: %d', 'gsg-custom-landings'), $analysis['heading_count'])); ?></li>
                    <li><?php echo esc_html(sprintf(__('Párrafos: %d', 'gsg-custom-landings'), $analysis['paragraph_count'])); ?></li>
                    <li><?php echo esc_html(sprintf(__('Inputs: %d', 'gsg-custom-landings'), $analysis['input_count'])); ?></li>
                    <li><?php echo esc_html(sprintf(__('CTAs: %d', 'gsg-custom-landings'), $analysis['cta_count'])); ?></li>
                </ul>
                <?php if (! empty($analysis['headings'])) : ?>
                    <p><strong><?php echo esc_html__('Headings detectados:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(implode(' | ', $analysis['headings'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_live_preview_metabox($post)
    {
        $preview_html = $this->get_section_meta($post->ID, 'gsgcl_section_preview_html', '');
        ?>
        <div class="gsgcl-live-preview-wrap" data-live-preview-root="1">
            <div class="gsgcl-live-preview-frame" id="gsgcl-live-preview-frame">
                <?php echo wp_kses_post($preview_html); ?>
            </div>
        </div>
        <?php
    }

    public function render_inline_editor($section_id)
    {
        $section_id = absint($section_id);
        $post = get_post($section_id);
        if (! $post || 'gsg_section' !== $post->post_type) {
            return '';
        }

        $type = $this->get_section_meta($section_id, 'gsgcl_section_type', 'generic');
        $variant = $this->get_section_meta($section_id, 'gsgcl_section_variant', 'default-v1');
        $version = $this->get_section_meta($section_id, 'gsgcl_section_version', '1');
        $brief = $this->get_section_meta($section_id, 'gsgcl_section_brief', '');
        $preview_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        $reference_image_url = $this->get_section_meta($section_id, 'gsgcl_section_reference_image_url', '');
        $analysis = $this->get_analysis($section_id);

        ob_start();
        ?>
        <div class="gsgcl-section-editor" data-section-id="<?php echo esc_attr((string) $section_id); ?>">
            <div class="gsgcl-section-editor__header">
                <div>
                    <h4><?php echo esc_html($post->post_title); ?></h4>
                    <p><?php echo esc_html($type . ' / ' . $variant . ' / v' . $version); ?></p>
                </div>
                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($section_id, '')); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Abrir sección', 'gsg-custom-landings'); ?></a>
            </div>
            <div class="gsgcl-section-editor__grid">
                <div class="gsgcl-section-editor__panel">
                    <p>
                        <label><strong><?php echo esc_html__('Brief', 'gsg-custom-landings'); ?></strong></label>
                        <textarea class="large-text code gsgcl-section-brief" rows="4"><?php echo esc_textarea($brief); ?></textarea>
                    </p>
                    <p>
                        <label><strong><?php echo esc_html__('Imagen de referencia', 'gsg-custom-landings'); ?></strong></label>
                        <input class="large-text gsgcl-section-reference-image" type="url" value="<?php echo esc_attr($reference_image_url); ?>" placeholder="https://..." />
                    </p>
                    <p>
                        <label><strong><?php echo esc_html__('HTML preview', 'gsg-custom-landings'); ?></strong></label>
                        <textarea class="large-text code gsgcl-section-preview-html" rows="12"><?php echo esc_textarea($preview_html); ?></textarea>
                    </p>
                    <div class="gsgcl-live-actions">
                        <button type="button" class="button button-secondary gsgcl-preview-live-button"><?php echo esc_html__('Preview live', 'gsg-custom-landings'); ?></button>
                        <button type="button" class="button button-primary gsgcl-save-live-button"><?php echo esc_html__('Guardar revisión', 'gsg-custom-landings'); ?></button>
                        <button type="button" class="button gsgcl-inline-generate-button"><?php echo esc_html__('Generar 3 propuestas', 'gsg-custom-landings'); ?></button>
                        <span class="gsgcl-live-status" aria-live="polite"></span>
                    </div>
                    <div class="gsgcl-analysis-box">
                        <h4><?php echo esc_html__('Análisis actual del HTML', 'gsg-custom-landings'); ?></h4>
                        <ul>
                            <li><?php echo esc_html(sprintf(__('Headings: %d', 'gsg-custom-landings'), $analysis['heading_count'])); ?></li>
                            <li><?php echo esc_html(sprintf(__('Párrafos: %d', 'gsg-custom-landings'), $analysis['paragraph_count'])); ?></li>
                            <li><?php echo esc_html(sprintf(__('Inputs: %d', 'gsg-custom-landings'), $analysis['input_count'])); ?></li>
                            <li><?php echo esc_html(sprintf(__('CTAs: %d', 'gsg-custom-landings'), $analysis['cta_count'])); ?></li>
                        </ul>
                        <?php if (! empty($analysis['headings'])) : ?>
                            <p><strong><?php echo esc_html__('Headings detectados:', 'gsg-custom-landings'); ?></strong> <?php echo esc_html(implode(' | ', $analysis['headings'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gsgcl-section-editor__panel">
                    <div class="gsgcl-live-preview-wrap">
                        <div class="gsgcl-live-preview-frame gsgcl-live-preview-frame--inline">
                            <?php echo wp_kses_post($preview_html); ?>
                        </div>
                    </div>
                    <div class="gsgcl-inline-sidepanels">
                        <div class="gsgcl-inline-proposals-panel">
                            <h4><?php echo esc_html__('Variaciones propuestas', 'gsg-custom-landings'); ?></h4>
                            <?php echo $this->render_proposals_markup($section_id); ?>
                        </div>
                        <div class="gsgcl-inline-revisions-panel">
                            <h4><?php echo esc_html__('Revisiones', 'gsg-custom-landings'); ?></h4>
                            <?php echo $this->render_revisions_markup($section_id); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public function get_section_editor_payload($section_id)
    {
        $section_id = absint($section_id);
        return array(
            'id' => $section_id,
            'title' => get_the_title($section_id),
            'type' => $this->get_section_meta($section_id, 'gsgcl_section_type', 'generic'),
            'variant' => $this->get_section_meta($section_id, 'gsgcl_section_variant', 'default-v1'),
            'version' => max(1, absint($this->get_section_meta($section_id, 'gsgcl_section_version', '1'))),
            'brief' => $this->get_section_meta($section_id, 'gsgcl_section_brief', ''),
            'preview_html' => $this->get_section_meta($section_id, 'gsgcl_section_preview_html', ''),
            'reference_image_url' => $this->get_section_meta($section_id, 'gsgcl_section_reference_image_url', ''),
            'editor_html' => $this->render_inline_editor($section_id),
        );
    }

    public function render_proposals_metabox($post)
    {
        $proposals = $this->get_section_meta($post->ID, 'gsgcl_section_proposals', array());
        $revisions = $this->get_revisions($post->ID);
        ?>
        <div class="gsgcl-admin-stack">
            <div>
                <h4><?php echo esc_html__('Variaciones propuestas', 'gsg-custom-landings'); ?></h4>
                <?php if (empty($proposals)) : ?>
                    <p><?php echo esc_html__('Aún no hay propuestas generadas para esta sección.', 'gsg-custom-landings'); ?></p>
                <?php else : ?>
                    <?php foreach ($proposals as $index => $proposal) : ?>
                        <div class="gsgcl-card-box">
                            <strong><?php echo esc_html($proposal['title']); ?></strong>
                            <p><?php echo esc_html($proposal['summary']); ?></p>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="gsgcl_apply_section_proposal" />
                                <input type="hidden" name="section_id" value="<?php echo esc_attr($post->ID); ?>" />
                                <input type="hidden" name="proposal_index" value="<?php echo esc_attr((string) $index); ?>" />
                                <?php wp_nonce_field('gsgcl_apply_section_proposal_' . $post->ID, 'gsgcl_action_nonce'); ?>
                                <button type="submit" class="button"><?php echo esc_html__('Usar propuesta', 'gsg-custom-landings'); ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <h4><?php echo esc_html__('Revisiones', 'gsg-custom-landings'); ?></h4>
                <?php if (empty($revisions)) : ?>
                    <p><?php echo esc_html__('Todavía no hay revisiones guardadas.', 'gsg-custom-landings'); ?></p>
                <?php else : ?>
                    <?php foreach (array_slice($revisions, 0, 8) as $revision) : ?>
                        <div class="gsgcl-card-box">
                            <strong><?php echo esc_html($revision['label']); ?></strong>
                            <p><?php echo esc_html($revision['created_at']); ?></p>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="gsgcl_restore_section_revision" />
                                <input type="hidden" name="section_id" value="<?php echo esc_attr($post->ID); ?>" />
                                <input type="hidden" name="revision_id" value="<?php echo esc_attr($revision['id']); ?>" />
                                <?php wp_nonce_field('gsgcl_restore_section_revision_' . $post->ID, 'gsgcl_action_nonce'); ?>
                                <button type="submit" class="button"><?php echo esc_html__('Restaurar', 'gsg-custom-landings'); ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($post->ID && 'auto-draft' !== $post->post_status) : ?>
                <div>
                    <h4><?php echo esc_html__('Fork y versionado', 'gsg-custom-landings'); ?></h4>
                    <p><?php echo esc_html__('Puedes crear un fork administrable o una nueva versión tomando esta sección como referencia.', 'gsg-custom-landings'); ?></p>
                    <p>
                        <a class="button" href="<?php echo esc_url($this->get_duplicate_url($post->ID, 'fork')); ?>"><?php echo esc_html__('Crear fork', 'gsg-custom-landings'); ?></a>
                        <a class="button" href="<?php echo esc_url($this->get_duplicate_url($post->ID, 'version')); ?>"><?php echo esc_html__('Crear versión siguiente', 'gsg-custom-landings'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_section_meta($post_id)
    {
        if (! isset($_POST['gsgcl_section_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gsgcl_section_nonce'])), 'gsgcl_save_section')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $old_preview_html = (string) get_post_meta($post_id, 'gsgcl_section_preview_html', true);
        $new_preview_html = isset($_POST['gsgcl_section_preview_html']) ? $this->sanitize_html_preview(wp_unslash($_POST['gsgcl_section_preview_html'])) : '';

        update_post_meta($post_id, 'gsgcl_section_type', isset($_POST['gsgcl_section_type']) ? sanitize_key(wp_unslash($_POST['gsgcl_section_type'])) : 'generic');
        update_post_meta($post_id, 'gsgcl_section_variant', isset($_POST['gsgcl_section_variant']) ? sanitize_title(wp_unslash($_POST['gsgcl_section_variant'])) : 'default-v1');
        update_post_meta($post_id, 'gsgcl_section_version', isset($_POST['gsgcl_section_version']) ? max(1, absint(wp_unslash($_POST['gsgcl_section_version']))) : 1);
        update_post_meta($post_id, 'gsgcl_section_brief', isset($_POST['gsgcl_section_brief']) ? sanitize_textarea_field(wp_unslash($_POST['gsgcl_section_brief'])) : '');
        update_post_meta($post_id, 'gsgcl_section_reference_image_url', isset($_POST['gsgcl_section_reference_image_url']) ? esc_url_raw(wp_unslash($_POST['gsgcl_section_reference_image_url'])) : '');
        update_post_meta($post_id, 'gsgcl_section_preview_html', $new_preview_html);

        if (! get_post_meta($post_id, 'gsgcl_section_root_id', true)) {
            update_post_meta($post_id, 'gsgcl_section_root_id', $post_id);
        }

        if ('' !== $old_preview_html && $old_preview_html !== $new_preview_html) {
            $this->store_revision($post_id, $old_preview_html, __('Edición manual', 'gsg-custom-landings'));
        }

        update_post_meta($post_id, 'gsgcl_section_analysis', $this->analyze_html($new_preview_html));
    }

    public function handle_generate_proposals()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_section_action($section_id, 'gsgcl_generate_section_proposals_' . $section_id);

        $section_type = $this->get_section_meta($section_id, 'gsgcl_section_type', 'generic');
        $brief = $this->get_section_meta($section_id, 'gsgcl_section_brief', '');
        $preview_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        $reference_image_url = $this->get_section_meta($section_id, 'gsgcl_section_reference_image_url', '');

        update_post_meta($section_id, 'gsgcl_section_proposals', $this->ai->generate_proposals($section_type, $brief, $preview_html, $reference_image_url));
        $this->redirect_back_to_section($section_id, 'proposals_generated');
    }

    public function handle_apply_proposal()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_section_action($section_id, 'gsgcl_apply_section_proposal_' . $section_id);

        $proposal_index = isset($_POST['proposal_index']) ? absint($_POST['proposal_index']) : -1;
        $proposals = $this->get_section_meta($section_id, 'gsgcl_section_proposals', array());
        if (! isset($proposals[$proposal_index])) {
            $this->redirect_back_to_section($section_id, 'missing_proposal');
        }

        $current_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        if ($current_html) {
            $this->store_revision($section_id, $current_html, __('Antes de aplicar propuesta', 'gsg-custom-landings'));
        }

        $next_html = $this->sanitize_html_preview($proposals[$proposal_index]['html']);
        update_post_meta($section_id, 'gsgcl_section_preview_html', $next_html);
        update_post_meta($section_id, 'gsgcl_section_analysis', $this->analyze_html($next_html));

        $this->redirect_back_to_section($section_id, 'proposal_applied');
    }

    public function handle_restore_revision()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_section_action($section_id, 'gsgcl_restore_section_revision_' . $section_id);

        $revision_id = isset($_POST['revision_id']) ? sanitize_text_field(wp_unslash($_POST['revision_id'])) : '';
        $revisions = $this->get_revisions($section_id);
        $matched_revision = null;

        foreach ($revisions as $revision) {
            if ($revision['id'] === $revision_id) {
                $matched_revision = $revision;
                break;
            }
        }

        if (! $matched_revision) {
            $this->redirect_back_to_section($section_id, 'missing_revision');
        }

        $current_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        if ($current_html) {
            $this->store_revision($section_id, $current_html, __('Antes de rollback', 'gsg-custom-landings'));
        }

        update_post_meta($section_id, 'gsgcl_section_preview_html', $this->sanitize_html_preview($matched_revision['preview_html']));
        update_post_meta($section_id, 'gsgcl_section_analysis', $this->analyze_html($matched_revision['preview_html']));

        $this->redirect_back_to_section($section_id, 'revision_restored');
    }

    public function ajax_preview_section()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_ajax_request($section_id, 'gsgcl_ajax_preview_section');

        $preview_html = isset($_POST['preview_html']) ? $this->sanitize_html_preview(wp_unslash($_POST['preview_html'])) : '';
        $analysis = $this->analyze_html($preview_html);

        wp_send_json_success(
            array(
                'preview_html' => $preview_html,
                'analysis' => $analysis,
                'revisions_html' => $this->render_revisions_markup($section_id),
                'proposals_html' => $this->render_proposals_markup($section_id),
            )
        );
    }

    public function ajax_save_section()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_ajax_request($section_id, 'gsgcl_ajax_save_section');

        $old_preview_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        $new_preview_html = isset($_POST['preview_html']) ? $this->sanitize_html_preview(wp_unslash($_POST['preview_html'])) : '';
        $brief = isset($_POST['brief']) ? sanitize_textarea_field(wp_unslash($_POST['brief'])) : '';
        $reference_image_url = isset($_POST['reference_image_url']) ? esc_url_raw(wp_unslash($_POST['reference_image_url'])) : '';
        $section_type = isset($_POST['section_type']) ? sanitize_key(wp_unslash($_POST['section_type'])) : $this->get_section_meta($section_id, 'gsgcl_section_type', 'generic');
        $variant = isset($_POST['variant']) ? sanitize_title(wp_unslash($_POST['variant'])) : $this->get_section_meta($section_id, 'gsgcl_section_variant', 'default-v1');
        $version = isset($_POST['version']) ? max(1, absint(wp_unslash($_POST['version']))) : max(1, absint($this->get_section_meta($section_id, 'gsgcl_section_version', '1')));

        if ($old_preview_html && $old_preview_html !== $new_preview_html) {
            $this->store_revision($section_id, $old_preview_html, __('Edición live', 'gsg-custom-landings'));
        }

        update_post_meta($section_id, 'gsgcl_section_type', $section_type);
        update_post_meta($section_id, 'gsgcl_section_variant', $variant);
        update_post_meta($section_id, 'gsgcl_section_version', $version);
        update_post_meta($section_id, 'gsgcl_section_brief', $brief);
        update_post_meta($section_id, 'gsgcl_section_reference_image_url', $reference_image_url);
        update_post_meta($section_id, 'gsgcl_section_preview_html', $new_preview_html);
        update_post_meta($section_id, 'gsgcl_section_analysis', $this->analyze_html($new_preview_html));

        wp_send_json_success(
            array(
                'preview_html' => $new_preview_html,
                'analysis' => $this->get_analysis($section_id),
                'revisions_html' => $this->render_revisions_markup($section_id),
            )
        );
    }

    public function ajax_restore_section()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_ajax_request($section_id, 'gsgcl_ajax_restore_section');

        $revision_id = isset($_POST['revision_id']) ? sanitize_text_field(wp_unslash($_POST['revision_id'])) : '';
        $revisions = $this->get_revisions($section_id);
        $matched_revision = null;

        foreach ($revisions as $revision) {
            if ($revision['id'] === $revision_id) {
                $matched_revision = $revision;
                break;
            }
        }

        if (! $matched_revision) {
            wp_send_json_error(array('message' => __('Revisión no encontrada.', 'gsg-custom-landings')), 404);
        }

        $current_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        if ($current_html) {
            $this->store_revision($section_id, $current_html, __('Antes de rollback', 'gsg-custom-landings'));
        }

        update_post_meta($section_id, 'gsgcl_section_preview_html', $this->sanitize_html_preview($matched_revision['preview_html']));
        update_post_meta($section_id, 'gsgcl_section_analysis', $this->analyze_html($matched_revision['preview_html']));

        wp_send_json_success(
            array(
                'preview_html' => $this->get_section_meta($section_id, 'gsgcl_section_preview_html', ''),
                'analysis' => $this->get_analysis($section_id),
                'revisions_html' => $this->render_revisions_markup($section_id),
            )
        );
    }

    public function ajax_generate_proposals()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_ajax_request($section_id, 'gsgcl_ajax_generate_proposals');

        $section_type = isset($_POST['section_type']) ? sanitize_key(wp_unslash($_POST['section_type'])) : $this->get_section_meta($section_id, 'gsgcl_section_type', 'generic');
        $brief = isset($_POST['brief']) ? sanitize_textarea_field(wp_unslash($_POST['brief'])) : $this->get_section_meta($section_id, 'gsgcl_section_brief', '');
        $preview_html = isset($_POST['preview_html']) ? $this->sanitize_html_preview(wp_unslash($_POST['preview_html'])) : $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        $reference_image_url = isset($_POST['reference_image_url']) ? esc_url_raw(wp_unslash($_POST['reference_image_url'])) : $this->get_section_meta($section_id, 'gsgcl_section_reference_image_url', '');

        update_post_meta($section_id, 'gsgcl_section_proposals', $this->ai->generate_proposals($section_type, $brief, $preview_html, $reference_image_url));

        wp_send_json_success(
            array(
                'proposals_html' => $this->render_proposals_markup($section_id),
            )
        );
    }

    public function ajax_apply_proposal()
    {
        $section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
        $this->guard_ajax_request($section_id, 'gsgcl_ajax_apply_proposal');

        $proposal_index = isset($_POST['proposal_index']) ? absint($_POST['proposal_index']) : -1;
        $proposals = $this->get_section_meta($section_id, 'gsgcl_section_proposals', array());
        if (! isset($proposals[$proposal_index])) {
            wp_send_json_error(array('message' => __('Propuesta no encontrada.', 'gsg-custom-landings')), 404);
        }

        $current_html = $this->get_section_meta($section_id, 'gsgcl_section_preview_html', '');
        if ($current_html) {
            $this->store_revision($section_id, $current_html, __('Antes de aplicar propuesta', 'gsg-custom-landings'));
        }

        $next_html = $this->sanitize_html_preview($proposals[$proposal_index]['html']);
        update_post_meta($section_id, 'gsgcl_section_preview_html', $next_html);
        update_post_meta($section_id, 'gsgcl_section_analysis', $this->analyze_html($next_html));

        wp_send_json_success(
            array(
                'preview_html' => $next_html,
                'analysis' => $this->get_analysis($section_id),
                'revisions_html' => $this->render_revisions_markup($section_id),
                'proposals_html' => $this->render_proposals_markup($section_id),
            )
        );
    }

    public function handle_duplicate_section()
    {
        $source_id = isset($_GET['source_id']) ? absint($_GET['source_id']) : 0;
        $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : 'fork';

        if (! $source_id || ! wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'gsgcl_duplicate_section_' . $source_id . '_' . $mode)) {
            wp_die(esc_html__('No autorizado.', 'gsg-custom-landings'));
        }

        if (! current_user_can('edit_post', $source_id)) {
            wp_die(esc_html__('No tienes permisos.', 'gsg-custom-landings'));
        }

        $source_post = get_post($source_id);
        if (! $source_post || 'gsg_section' !== $source_post->post_type) {
            wp_die(esc_html__('Sección no encontrada.', 'gsg-custom-landings'));
        }

        $source_variant = $this->get_section_meta($source_id, 'gsgcl_section_variant', 'default-v1');
        $source_version = max(1, absint($this->get_section_meta($source_id, 'gsgcl_section_version', '1')));
        $root_id = absint($this->get_section_meta($source_id, 'gsgcl_section_root_id', '0'));
        $root_id = $root_id ? $root_id : $source_id;

        $target_variant = 'version' === $mode ? $source_variant : sanitize_title($source_variant . '-fork');
        $target_version = 'version' === $mode ? $source_version + 1 : 1;
        $target_title = 'version' === $mode
            ? sprintf('%s v%d', $source_post->post_title, $target_version)
            : sprintf('%s Fork', $source_post->post_title);

        $new_post_id = wp_insert_post(
            array(
                'post_type' => 'gsg_section',
                'post_status' => 'draft',
                'post_title' => $target_title,
            ),
            true
        );

        if (is_wp_error($new_post_id) || ! $new_post_id) {
            wp_die(esc_html__('No se pudo duplicar la sección.', 'gsg-custom-landings'));
        }

        foreach ($this->get_section_meta_keys() as $meta_key) {
            $value = get_post_meta($source_id, $meta_key, true);
            update_post_meta($new_post_id, $meta_key, $value);
        }

        update_post_meta($new_post_id, 'gsgcl_section_reference_id', $source_id);
        update_post_meta($new_post_id, 'gsgcl_section_root_id', $root_id);
        update_post_meta($new_post_id, 'gsgcl_section_variant', $target_variant);
        update_post_meta($new_post_id, 'gsgcl_section_version', $target_version);
        update_post_meta($new_post_id, 'gsgcl_section_analysis', $this->analyze_html($this->get_section_meta($new_post_id, 'gsgcl_section_preview_html', '')));

        wp_safe_redirect(admin_url('post.php?post=' . $new_post_id . '&action=edit&gsgcl_notice=duplicated'));
        exit;
    }

    public function render_admin_notice()
    {
        if (! isset($_GET['gsgcl_notice'])) {
            return;
        }

        $notice = sanitize_key(wp_unslash($_GET['gsgcl_notice']));
        $messages = array(
            'proposals_generated' => __('Se generaron 3 propuestas para la sección.', 'gsg-custom-landings'),
            'proposal_applied' => __('La propuesta fue aplicada al preview HTML.', 'gsg-custom-landings'),
            'revision_restored' => __('La revisión fue restaurada correctamente.', 'gsg-custom-landings'),
            'duplicated' => __('Se creó una nueva sección a partir de la referencia.', 'gsg-custom-landings'),
            'missing_proposal' => __('La propuesta seleccionada ya no existe.', 'gsg-custom-landings'),
            'missing_revision' => __('La revisión solicitada no existe.', 'gsg-custom-landings'),
        );

        if (! isset($messages[$notice])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$notice]) . '</p></div>';
    }

    public function register_row_actions($actions, $post)
    {
        if ('gsg_section' !== $post->post_type) {
            return $actions;
        }

        $actions['gsgcl_fork'] = '<a href="' . esc_url($this->get_duplicate_url($post->ID, 'fork')) . '">' . esc_html__('Crear fork', 'gsg-custom-landings') . '</a>';
        $actions['gsgcl_version'] = '<a href="' . esc_url($this->get_duplicate_url($post->ID, 'version')) . '">' . esc_html__('Crear versión siguiente', 'gsg-custom-landings') . '</a>';

        return $actions;
    }

    public function register_columns($columns)
    {
        $columns['gsgcl_section_type'] = __('Tipo', 'gsg-custom-landings');
        $columns['gsgcl_section_variant'] = __('Variant', 'gsg-custom-landings');
        $columns['gsgcl_section_version'] = __('Versión', 'gsg-custom-landings');

        return $columns;
    }

    public function render_column($column, $post_id)
    {
        if ('gsgcl_section_type' === $column) {
            echo esc_html($this->get_section_meta($post_id, 'gsgcl_section_type', 'generic'));
        }

        if ('gsgcl_section_variant' === $column) {
            echo esc_html($this->get_section_meta($post_id, 'gsgcl_section_variant', 'default-v1'));
        }

        if ('gsgcl_section_version' === $column) {
            echo esc_html((string) max(1, absint($this->get_section_meta($post_id, 'gsgcl_section_version', '1'))));
        }
    }

    public function ensure_demo_sections($landing_id = 0, $page_id = 0)
    {
        $existing_ids = get_option('gsgcl_sample_section_ids', array());
        $existing_ids = is_array($existing_ids) ? array_filter(array_map('absint', $existing_ids)) : array();
        $valid_ids = array();

        foreach ($existing_ids as $existing_id) {
            $post = get_post($existing_id);
            if ($post && 'gsg_section' === $post->post_type) {
                $valid_ids[] = $existing_id;
            }
        }

        if (! empty($valid_ids)) {
            return $valid_ids;
        }

        $created_ids = array();
        foreach ($this->get_demo_section_definitions($landing_id, $page_id) as $definition) {
            $post_id = wp_insert_post(
                array(
                    'post_type' => 'gsg_section',
                    'post_status' => 'publish',
                    'post_title' => $definition['post_title'],
                ),
                true
            );

            if (is_wp_error($post_id) || ! $post_id) {
                continue;
            }

            update_post_meta($post_id, 'gsgcl_section_type', $definition['section_type']);
            update_post_meta($post_id, 'gsgcl_section_variant', $definition['variant']);
            update_post_meta($post_id, 'gsgcl_section_version', 1);
            update_post_meta($post_id, 'gsgcl_section_brief', $definition['brief']);
            update_post_meta($post_id, 'gsgcl_section_preview_html', $this->sanitize_html_preview($definition['preview_html']));
            update_post_meta($post_id, 'gsgcl_section_root_id', $post_id);
            update_post_meta($post_id, 'gsgcl_section_reference_id', 0);
            update_post_meta($post_id, 'gsgcl_section_analysis', $this->analyze_html($definition['preview_html']));
            $created_ids[] = $post_id;
        }

        if (! empty($created_ids)) {
            update_option('gsgcl_sample_section_ids', $created_ids, false);
        }

        return $created_ids;
    }

    private function guard_section_action($section_id, $nonce_action)
    {
        if (! $section_id || ! isset($_POST['gsgcl_action_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gsgcl_action_nonce'])), $nonce_action)) {
            wp_die(esc_html__('No autorizado.', 'gsg-custom-landings'));
        }

        if (! current_user_can('edit_post', $section_id)) {
            wp_die(esc_html__('No tienes permisos.', 'gsg-custom-landings'));
        }
    }

    private function guard_ajax_request($section_id, $nonce_action)
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! $section_id || ! wp_verify_nonce($nonce, $nonce_action) || ! current_user_can('edit_post', $section_id)) {
            wp_send_json_error(array('message' => __('No autorizado.', 'gsg-custom-landings')), 403);
        }
    }

    private function get_section_meta($post_id, $key, $default = '')
    {
        $value = get_post_meta($post_id, $key, true);
        return '' === $value || null === $value ? $default : $value;
    }

    private function get_section_types()
    {
        return array(
            'hero' => __('Hero', 'gsg-custom-landings'),
            'counter' => __('Counter', 'gsg-custom-landings'),
            'benefits' => __('Benefits', 'gsg-custom-landings'),
            'form' => __('Form', 'gsg-custom-landings'),
            'testimonials' => __('Testimonials', 'gsg-custom-landings'),
            'faq' => __('FAQ', 'gsg-custom-landings'),
            'cta' => __('CTA', 'gsg-custom-landings'),
            'generic' => __('Generic', 'gsg-custom-landings'),
        );
    }

    private function get_demo_section_definitions($landing_id, $page_id)
    {
        $form_anchor = $page_id ? get_permalink($page_id) . '#gsgcl-form' : '#gsgcl-form';

        return array(
            array(
                'post_title' => 'Demo Hero Referral',
                'section_type' => 'hero',
                'variant' => 'referral-split-v1',
                'brief' => 'Hero principal de la landing Invita a un amigo con mensaje de valor, CTA primario y visual de destinos.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-hero">
    <div class="gsgcl-demo-hero__copy">
        <h1>Invita a un amigo <span>y ambos ganan</span></h1>
        <p>Estudiar en el extranjero es mejor cuando se comparte. Recomienda y obtén beneficios exclusivos.</p>
        <div class="gsgcl-demo-hero__actions">
            <a href="' . esc_url($form_anchor) . '">¡Registrar a mi amigo!</a>
            <a href="#gsgcl-benefit">Ver beneficio</a>
        </div>
    </div>
    <div class="gsgcl-demo-hero__visual" style="background-image:url(' . esc_url('https://gsgeducation.com/wp-content/uploads/2026/04/Banner-1800x600-px-02.jpg') . ');"></div>
</section>',
            ),
            array(
                'post_title' => 'Demo Counter Benefit',
                'section_type' => 'counter',
                'variant' => 'benefit-highlight-v1',
                'brief' => 'Counter visual que resume el 20% OFF y abre la lectura del beneficio principal.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-counter">
    <h2>¿Cuál es el beneficio?</h2>
    <div class="gsgcl-demo-counter__value">20% OFF</div>
    <h3>En tu servicio de Admission</h3>
    <p>Aplica para ti y para tu amigo referido.</p>
</section>',
            ),
            array(
                'post_title' => 'Demo Benefits Cards',
                'section_type' => 'benefits',
                'variant' => 'dual-card-v1',
                'brief' => 'Dos tarjetas para explicar el beneficio del amigo referido y del estudiante que recomienda.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-benefits">
    <article>
        <h3>Para tu amigo</h3>
        <p>Obtiene un beneficio directo del 20% de descuento en su servicio de asesoría.</p>
    </article>
    <article>
        <h3>Para ti</h3>
        <p>Recibes el mismo 20% de descuento por habernos recomendado.</p>
    </article>
</section>',
            ),
            array(
                'post_title' => 'Demo Steps Referral',
                'section_type' => 'generic',
                'variant' => 'steps-3-v1',
                'brief' => 'Sección de tres pasos que explica el flujo del referral.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-steps">
    <h2>¿Cómo funciona?</h2>
    <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start;">
        <div style="min-height:360px;border-radius:24px;background:url(&quot;https://gsgeducation.com/wp-content/uploads/2026/04/Banner-collage-estudiantes-celebracion1.png&quot;) center/cover no-repeat;"></div>
        <div style="display:grid;gap:14px;">
            <article><strong>1</strong><h3>Recomienda</h3><p>Registra los datos de tu amigo.</p></article>
            <article><strong>2</strong><h3>Lo contactamos</h3><p>Nuestro equipo le brinda orientación personalizada.</p></article>
            <article><strong>3</strong><h3>Recibes tu beneficio</h3><p>Si inicia su proceso, activamos tu descuento.</p></article>
        </div>
    </div>
</section>',
            ),
            array(
                'post_title' => 'Demo Referral Form',
                'section_type' => 'form',
                'variant' => 'two-column-referral-v1',
                'brief' => 'Formulario principal de registro con datos del amigo y del estudiante que recomienda.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-form">
    <h2>Registra a tu amigo</h2>
    <form>
        <input type="text" name="friend_name" placeholder="Nombre" />
        <input type="text" name="friend_last_name" placeholder="Apellido" />
        <input type="email" name="friend_email" placeholder="Email" />
        <input type="text" name="student_name" placeholder="Tu nombre completo" />
        <input type="email" name="student_email" placeholder="Tu email" />
        <textarea name="student_comments" rows="4" placeholder="Comentarios"></textarea>
        <button type="submit">¡Registrar a mi amigo!</button>
    </form>
</section>',
            ),
            array(
                'post_title' => 'Demo Footer Help',
                'section_type' => 'cta',
                'variant' => 'support-panel-v1',
                'brief' => 'Bloque final con razones para referir a GSG y accesos a WhatsApp.',
                'preview_html' => '
<section class="gsgcl-section-preview gsgcl-demo-footer-help">
    <div>
        <h2>¿Por qué referir a GSG Education?</h2>
        <ul>
            <li>+12 años de experiencia en América Latina.</li>
            <li>+4000 alumnos aceptados en universidades top a nivel mundial.</li>
            <li>Convenios con +400 universidades a nivel internacional.</li>
            <li>Miembros de organizaciones como NAFSA, International ACAC y UCAS.</li>
        </ul>
    </div>
    <div>
        <h3>¿Tienes dudas?</h3>
        <a href="https://conectiontool.mantra.chat/tools/u/accbc76f-7622-44d5-9f71-58963e49a0f2">Escríbenos por WhatsApp - Perú y Latam</a>
        <a href="https://wa.link/sghvfb">Escríbenos por WhatsApp - Colombia</a>
    </div>
</section>',
            ),
        );
    }

    private function sanitize_html_preview($html)
    {
        $allowed = wp_kses_allowed_html('post');
        $allowed['section'] = array('class' => true, 'id' => true, 'data-*' => true);
        $allowed['header'] = array('class' => true, 'id' => true);
        $allowed['small'] = array('class' => true);
        $allowed['div']['data-*'] = true;
        $allowed['a']['data-*'] = true;
        $allowed['button'] = array('class' => true, 'type' => true);
        $allowed['input'] = array('type' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'class' => true);
        $allowed['textarea'] = array('name' => true, 'rows' => true, 'placeholder' => true, 'class' => true);
        $allowed['form'] = array('action' => true, 'method' => true, 'class' => true);

        return wp_kses($html, $allowed);
    }

    private function analyze_html($html)
    {
        $result = array(
            'heading_count' => 0,
            'paragraph_count' => 0,
            'input_count' => 0,
            'cta_count' => 0,
            'headings' => array(),
            'inputs' => array(),
            'ctas' => array(),
        );

        if (! is_string($html) || '' === trim($html)) {
            return $result;
        }

        $internal_errors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>');
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $text = trim($heading->textContent);
            if ($text) {
                $result['headings'][] = $text;
            }
        }

        foreach ($xpath->query('//input|//textarea|//select') as $input) {
            $result['inputs'][] = array(
                'name' => $input->attributes->getNamedItem('name') ? $input->attributes->getNamedItem('name')->nodeValue : '',
                'type' => $input->attributes->getNamedItem('type') ? $input->attributes->getNamedItem('type')->nodeValue : $input->nodeName,
            );
        }

        foreach ($xpath->query('//a|//button') as $cta) {
            $label = trim($cta->textContent);
            if ($label) {
                $result['ctas'][] = array(
                    'label' => $label,
                    'target' => $cta->attributes->getNamedItem('href') ? $cta->attributes->getNamedItem('href')->nodeValue : '',
                );
            }
        }

        $result['heading_count'] = count($result['headings']);
        $result['paragraph_count'] = $xpath->query('//p')->length;
        $result['input_count'] = count($result['inputs']);
        $result['cta_count'] = count($result['ctas']);

        libxml_clear_errors();
        libxml_use_internal_errors($internal_errors);

        return $result;
    }

    private function get_analysis($post_id)
    {
        $analysis = get_post_meta($post_id, 'gsgcl_section_analysis', true);
        return is_array($analysis) ? $analysis : $this->analyze_html($this->get_section_meta($post_id, 'gsgcl_section_preview_html', ''));
    }

    private function store_revision($post_id, $preview_html, $label)
    {
        $revisions = $this->get_revisions($post_id);
        array_unshift(
            $revisions,
            array(
                'id' => wp_generate_uuid4(),
                'label' => sanitize_text_field($label),
                'created_at' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'preview_html' => $preview_html,
            )
        );

        update_post_meta($post_id, 'gsgcl_section_revisions', array_slice($revisions, 0, 25));
    }

    private function get_revisions($post_id)
    {
        $revisions = get_post_meta($post_id, 'gsgcl_section_revisions', true);
        return is_array($revisions) ? $revisions : array();
    }

    private function redirect_back_to_section($section_id, $notice)
    {
        wp_safe_redirect(admin_url('post.php?post=' . $section_id . '&action=edit&gsgcl_notice=' . $notice));
        exit;
    }

    private function get_duplicate_url($section_id, $mode)
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=gsgcl_duplicate_section&source_id=' . $section_id . '&mode=' . $mode),
            'gsgcl_duplicate_section_' . $section_id . '_' . $mode
        );
    }

    private function get_section_meta_keys()
    {
        return array(
            'gsgcl_section_type',
            'gsgcl_section_variant',
            'gsgcl_section_version',
            'gsgcl_section_reference_id',
            'gsgcl_section_root_id',
            'gsgcl_section_brief',
            'gsgcl_section_reference_image_url',
            'gsgcl_section_preview_html',
            'gsgcl_section_analysis',
            'gsgcl_section_proposals',
            'gsgcl_section_revisions',
        );
    }

    private function get_inline_admin_css()
    {
        return '
        .gsgcl-admin-stack { display:grid; gap:16px; }
        .gsgcl-sections-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
        .gsgcl-sections-panel { padding:12px; background:#fff; border:1px solid #dcdcde; border-radius:8px; }
        .gsgcl-section-library-list, .gsgcl-selected-sections { display:grid; gap:10px; }
        .gsgcl-library-item, .gsgcl-selected-item { display:flex; gap:12px; justify-content:space-between; align-items:center; padding:10px; border:1px solid #dcdcde; border-radius:8px; background:#f8f9fa; }
        .gsgcl-library-item > div, .gsgcl-selected-item > div { display:grid; gap:4px; }
        .gsgcl-selected-sections { margin:0; padding:0; list-style:none; }
        .gsgcl-drag-handle { cursor:move; color:#646970; font-weight:700; }
        .gsgcl-inline-editors-wrap { display:grid; gap:16px; margin-top:16px; }
        .gsgcl-section-editor { padding:16px; background:#fff; border:1px solid #dcdcde; border-radius:10px; }
        .gsgcl-section-editor__header { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:12px; }
        .gsgcl-section-editor__header h4 { margin:0 0 4px; }
        .gsgcl-section-editor__header p { margin:0; color:#646970; }
        .gsgcl-section-editor__grid { display:grid; grid-template-columns:1.05fr 0.95fr; gap:16px; }
        .gsgcl-section-editor__panel { display:grid; gap:12px; }
        .gsgcl-inline-sidepanels { display:grid; gap:12px; }
        .gsgcl-code-area { min-height: 320px; font-family: Consolas, monospace; }
        .gsgcl-live-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .gsgcl-live-status { color:#2271b1; font-weight:600; }
        .gsgcl-live-preview-wrap { background:#f6f7f7; padding:12px; border-radius:8px; }
        .gsgcl-live-preview-frame { padding:18px; background:#fff; border:1px solid #dcdcde; border-radius:8px; min-height:200px; }
        .gsgcl-live-preview-frame--inline { min-height:260px; overflow:auto; }
        .gsgcl-analysis-box, .gsgcl-card-box { padding: 12px; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; }
        .gsgcl-card-box p { margin: 8px 0; }
        .gsgcl-inline-form { display:grid; gap:8px; margin-top: 12px; }
        .gsgcl-demo-hero { display:grid; grid-template-columns:minmax(0, 1.05fr) minmax(240px, 0.95fr); gap:20px; align-items:center; }
        .gsgcl-demo-hero__copy { display:grid; gap:12px; }
        .gsgcl-demo-hero__copy h1, .gsgcl-demo-hero__copy h2, .gsgcl-demo-hero__copy p { margin:0; }
        .gsgcl-demo-hero__actions { display:flex; flex-wrap:wrap; gap:10px; }
        .gsgcl-demo-hero__actions a { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:0 16px; border-radius:12px; }
        .gsgcl-demo-hero__visual { min-height:340px; border-radius:20px; background-position:center; background-size:cover; background-repeat:no-repeat; box-shadow:0 14px 30px rgba(16, 61, 146, 0.08); }
        .gsgcl-demo-counter { display:grid; gap:12px; text-align:center; }
        .gsgcl-demo-counter__value { display:inline-flex; align-items:center; justify-content:center; margin:0 auto; padding:14px 24px; border-radius:18px; background:linear-gradient(90deg, #0d3e9f, #397dc9); color:#fff; font-weight:800; font-size:clamp(2rem, 6vw, 4rem); }
        .gsgcl-demo-benefits { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .gsgcl-demo-benefits article, .gsgcl-demo-footer-help > div { padding:18px; border:1px solid #dcdcde; border-radius:18px; background:#fff; }
        .gsgcl-demo-steps { display:grid; gap:16px; }
        .gsgcl-demo-steps > div { display:grid; grid-template-columns:minmax(240px, 0.95fr) 1.05fr; gap:24px; align-items:start; }
        .gsgcl-demo-steps > div > div:first-child { min-height:360px; border-radius:24px; background-position:center; background-size:cover; background-repeat:no-repeat; }
        .gsgcl-demo-steps > div > div:last-child { display:grid; gap:14px; }
        .gsgcl-demo-steps article { display:grid; grid-template-columns:auto 1fr; gap:12px; align-items:start; padding:16px 18px; border:1px solid #dcdcde; border-radius:18px; background:#fff; }
        .gsgcl-demo-steps article strong { display:grid; place-items:center; width:44px; height:44px; border-radius:50%; background:#ffc600; color:#22282b; }
        .gsgcl-demo-form form { display:grid; gap:12px; }
        .gsgcl-demo-footer-help { display:grid; grid-template-columns:1.1fr 0.9fr; gap:16px; }
        .gsgcl-demo-footer-help a { display:flex; width:100%; }
        @media (max-width: 782px) { .gsgcl-demo-hero, .gsgcl-demo-benefits, .gsgcl-demo-footer-help { grid-template-columns:1fr; } .gsgcl-demo-hero__visual, .gsgcl-demo-steps > div > div:first-child { min-height:240px; background-size:cover; } .gsgcl-demo-steps > div { grid-template-columns:1fr !important; } .gsgcl-demo-hero__actions a, .gsgcl-demo-footer-help a { width:100%; } }
        @media (max-width: 1100px) { .gsgcl-section-editor__grid, .gsgcl-sections-grid { grid-template-columns:1fr; } }
        ';
    }

    private function render_revisions_markup($section_id)
    {
        $revisions = array_slice($this->get_revisions($section_id), 0, 8);
        ob_start();

        if (empty($revisions)) {
            echo '<p>' . esc_html__('Todavía no hay revisiones guardadas.', 'gsg-custom-landings') . '</p>';
        } else {
            foreach ($revisions as $revision) {
                echo '<div class="gsgcl-card-box">';
                echo '<strong>' . esc_html($revision['label']) . '</strong>';
                echo '<p>' . esc_html($revision['created_at']) . '</p>';
                echo '<button type="button" class="button gsgcl-restore-revision-button" data-revision-id="' . esc_attr($revision['id']) . '">' . esc_html__('Restaurar', 'gsg-custom-landings') . '</button>';
                echo '</div>';
            }
        }

        return ob_get_clean();
    }

    private function render_proposals_markup($section_id)
    {
        $proposals = $this->get_section_meta($section_id, 'gsgcl_section_proposals', array());
        ob_start();

        if (empty($proposals)) {
            echo '<p>' . esc_html__('Aún no hay propuestas generadas para esta sección.', 'gsg-custom-landings') . '</p>';
        } else {
            foreach ($proposals as $index => $proposal) {
                echo '<div class="gsgcl-card-box">';
                echo '<strong>' . esc_html($proposal['title']) . '</strong>';
                echo '<p>' . esc_html($proposal['summary']) . '</p>';
                echo '<button type="button" class="button gsgcl-apply-proposal-button" data-proposal-index="' . esc_attr((string) $index) . '">' . esc_html__('Usar propuesta', 'gsg-custom-landings') . '</button>';
                echo '</div>';
            }
        }

        return ob_get_clean();
    }
}