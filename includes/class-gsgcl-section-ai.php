<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Section_AI
{
    private $settings;

    public function __construct($settings = null)
    {
        $this->settings = $settings;
    }

    public function generate_proposals($section_type, $brief, $reference_html = '', $reference_image_url = '')
    {
        $section_type = sanitize_key($section_type);
        $brief = sanitize_textarea_field($brief);
        $reference_image_url = esc_url_raw($reference_image_url);
        $reference_context = wp_strip_all_tags($reference_html);
        $reference_excerpt = $reference_context ? wp_trim_words($reference_context, 22, '...') : '';

        $remote_proposals = $this->generate_openai_proposals($section_type, $brief, $reference_excerpt, $reference_image_url);
        if (! empty($remote_proposals)) {
            return $remote_proposals;
        }

        $proposals = array(
            $this->build_proposal($section_type, 'editorial', $brief, $reference_excerpt, $reference_image_url),
            $this->build_proposal($section_type, 'conversion', $brief, $reference_excerpt, $reference_image_url),
            $this->build_proposal($section_type, 'compact', $brief, $reference_excerpt, $reference_image_url),
        );

        return apply_filters('gsgcl_generate_section_proposals', $proposals, $section_type, $brief, $reference_html, $reference_image_url);
    }

    private function build_proposal($section_type, $style, $brief, $reference_excerpt, $reference_image_url)
    {
        $title = ucfirst($section_type) . ' / ' . ucfirst($style);
        $summary = $brief ? wp_trim_words($brief, 18, '...') : 'Sin brief detallado';

        if ($reference_image_url) {
            $summary .= ' | Referencia visual disponible';
        }

        if ('counter' === $section_type) {
            return $this->build_counter_proposal($style, $title, $summary, $brief, $reference_excerpt, $reference_image_url);
        }

        if ('hero' === $section_type) {
            return $this->build_hero_proposal($style, $title, $summary, $brief, $reference_excerpt, $reference_image_url);
        }

        return array(
            'id' => sanitize_title($section_type . '-' . $style),
            'title' => $title,
            'summary' => $summary,
            'html' => $this->render_generic_section($section_type, $style, $brief, $reference_excerpt, $reference_image_url),
        );
    }

    private function build_counter_proposal($style, $title, $summary, $brief, $reference_excerpt, $reference_image_url)
    {
        $descriptions = array(
            'editorial' => 'Enfoque visual amplio con cifra protagonista y soporte descriptivo.',
            'conversion' => 'Prioriza lectura rápida, métricas y CTA secundaria.',
            'compact' => 'Bloque más corto para mezclarse con otras secciones en la landing.',
        );

        $html_by_style = array(
            'editorial' => '
<section class="gsgcl-section-preview gsgcl-section-preview--counter gsgcl-counter-style-editorial">
    <div class="gsgcl-preview-kicker">Counter section</div>
    <div class="gsgcl-preview-stat">20% OFF</div>
    <h2>Beneficio principal que domina la sección</h2>
    <p>' . esc_html($brief ?: 'Resumen del beneficio principal que se quiere comunicar.') . '</p>
    <div class="gsgcl-preview-metrics">
        <div><strong>1</strong><span>beneficio para ti</span></div>
        <div><strong>1</strong><span>beneficio para tu referido</span></div>
        <div><strong>3</strong><span>pasos para activarlo</span></div>
    </div>
</section>',
            'conversion' => '
<section class="gsgcl-section-preview gsgcl-section-preview--counter gsgcl-counter-style-conversion">
    <header>
        <h2>20% OFF para activar hoy</h2>
        <p>' . esc_html($brief ?: 'Mensaje orientado a acelerar decisión y claridad del incentivo.') . '</p>
    </header>
    <div class="gsgcl-preview-pill-row">
        <span class="gsgcl-preview-pill">Descuento inmediato</span>
        <span class="gsgcl-preview-pill">Aplica al referral</span>
        <span class="gsgcl-preview-pill">Sin pasos complejos</span>
    </div>
    <a href="#gsgcl-form">Quiero activar este beneficio</a>
</section>',
            'compact' => '
<section class="gsgcl-section-preview gsgcl-section-preview--counter gsgcl-counter-style-compact">
    <div class="gsgcl-preview-stack">
        <small>Counter compact</small>
        <strong>20% OFF</strong>
        <p>' . esc_html($brief ?: 'Bloque breve para reforzar la propuesta de valor.') . '</p>
    </div>
</section>',
        );

        return array(
            'id' => sanitize_title('counter-' . $style),
            'title' => $title,
            'summary' => $descriptions[$style],
            'html' => $html_by_style[$style]
                . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : '')
                . ($reference_image_url ? "\n<!-- Imagen: " . esc_url($reference_image_url) . " -->" : ''),
        );
    }

    private function build_hero_proposal($style, $title, $summary, $brief, $reference_excerpt, $reference_image_url)
    {
        $html = '
<section class="gsgcl-section-preview gsgcl-section-preview--hero gsgcl-hero-style-' . esc_attr($style) . '">
    <div class="gsgcl-preview-copy">
        <small>Hero section</small>
        <h1>Título principal editable</h1>
        <p>' . esc_html($brief ?: 'Descripción base para orientar la propuesta del hero.') . '</p>
        <a href="#gsgcl-form">CTA principal</a>
    </div>
    <div class="gsgcl-preview-media">Media preview</div>
</section>';

        return array(
            'id' => sanitize_title('hero-' . $style),
            'title' => $title,
            'summary' => $summary,
            'html' => $html
                . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : '')
                . ($reference_image_url ? "\n<!-- Imagen: " . esc_url($reference_image_url) . " -->" : ''),
        );
    }

    private function render_generic_section($section_type, $style, $brief, $reference_excerpt, $reference_image_url)
    {
        return '
<section class="gsgcl-section-preview gsgcl-section-preview--' . esc_attr($section_type) . ' gsgcl-section-style-' . esc_attr($style) . '">
    <small>' . esc_html(ucfirst($section_type)) . ' / ' . esc_html(ucfirst($style)) . '</small>
    <h2>Título editable de la sección</h2>
    <p>' . esc_html($brief ?: 'Contenido base generado a partir del brief del editor.') . '</p>
    <a href="#">CTA de ejemplo</a>
</section>'
        . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : '')
        . ($reference_image_url ? "\n<!-- Imagen: " . esc_url($reference_image_url) . " -->" : '');
    }

    private function generate_openai_proposals($section_type, $brief, $reference_excerpt, $reference_image_url)
    {
        $settings = $this->get_openai_settings();
        if (empty($settings['openai_api_key'])) {
            return array();
        }

        $payload = array(
            'model' => $settings['openai_model'],
            'input' => $this->build_openai_input($section_type, $brief, $reference_excerpt, $reference_image_url),
        );

        $response = wp_remote_post(
            'https://api.openai.com/v1/responses',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $settings['openai_api_key'],
                    'Content-Type' => 'application/json',
                ),
                'timeout' => $settings['openai_timeout'],
                'body' => wp_json_encode($payload),
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return array();
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            return array();
        }

        $text = $this->extract_response_text($data);
        if (! $text) {
            return array();
        }

        return $this->normalize_openai_proposals($text, $section_type, $brief, $reference_excerpt, $reference_image_url);
    }

    private function get_openai_settings()
    {
        if ($this->settings && method_exists($this->settings, 'get_settings')) {
            return wp_parse_args(
                $this->settings->get_settings(),
                array(
                    'openai_api_key' => '',
                    'openai_model' => 'gpt-5.4',
                    'openai_timeout' => 20,
                )
            );
        }

        return array(
            'openai_api_key' => '',
            'openai_model' => 'gpt-5.4',
            'openai_timeout' => 20,
        );
    }

    private function build_openai_input($section_type, $brief, $reference_excerpt, $reference_image_url)
    {
        $system_prompt = implode("\n", array(
            'Eres un director creativo senior para landings de marketing.',
            'Debes responder solo JSON válido.',
            'Devuelve exactamente 3 propuestas.',
            'Cada propuesta debe tener: id, title, summary, html.',
            'El html debe ser seguro para WordPress, semántico, responsive y sin scripts.',
            'No incluyas markdown, ni texto fuera del JSON.',
        ));

        $brief_text = $brief ? $brief : 'Sin brief específico.';
        $reference_text = $reference_excerpt ? $reference_excerpt : 'Sin referencia HTML adicional.';

        $user_prompt = implode("\n", array(
            'Genera 3 variaciones de una sección reutilizable para una landing.',
            'Tipo de sección: ' . $section_type,
            'Brief: ' . $brief_text,
            'Referencia HTML resumida: ' . $reference_text,
            'Estilos requeridos: editorial, conversion y compact.',
            'Usa ids slug únicos relacionados al tipo y al estilo.',
            'El summary debe describir el enfoque de cada variante en una frase.',
            'El html debe venir listo para usarse como preview editable.',
        ));

        $content = array(
            array(
                'type' => 'input_text',
                'text' => $user_prompt,
            ),
        );

        if ($reference_image_url) {
            $content[] = array(
                'type' => 'input_image',
                'image_url' => $reference_image_url,
            );
        }

        return array(
            array(
                'role' => 'system',
                'content' => array(
                    array(
                        'type' => 'input_text',
                        'text' => $system_prompt,
                    ),
                ),
            ),
            array(
                'role' => 'user',
                'content' => $content,
            ),
        );
    }

    private function extract_response_text($data)
    {
        if (! empty($data['output_text']) && is_string($data['output_text'])) {
            return trim($data['output_text']);
        }

        if (! empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $output_item) {
                if (empty($output_item['content']) || ! is_array($output_item['content'])) {
                    continue;
                }

                foreach ($output_item['content'] as $content_item) {
                    if (! empty($content_item['text']) && is_string($content_item['text'])) {
                        return trim($content_item['text']);
                    }
                }
            }
        }

        if (! empty($data['choices'][0]['message']['content']) && is_string($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }

        return '';
    }

    private function normalize_openai_proposals($raw_text, $section_type, $brief, $reference_excerpt, $reference_image_url)
    {
        $decoded = json_decode(trim($raw_text), true);

        if (! is_array($decoded)) {
            if (preg_match('/\[.*\]/s', $raw_text, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            return array();
        }

        $items = isset($decoded['proposals']) && is_array($decoded['proposals']) ? $decoded['proposals'] : $decoded;
        if (! is_array($items) || empty($items)) {
            return array();
        }

        $normalized = array();
        foreach (array_slice($items, 0, 3) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $style = isset($item['style']) ? sanitize_title($item['style']) : 'variant-' . ($index + 1);
            $html = isset($item['html']) ? wp_kses_post($item['html']) : '';
            if (! $html) {
                continue;
            }

            if ($reference_excerpt) {
                $html .= "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->";
            }

            if ($reference_image_url) {
                $html .= "\n<!-- Imagen: " . esc_url($reference_image_url) . " -->";
            }

            $normalized[] = array(
                'id' => ! empty($item['id']) ? sanitize_title($item['id']) : sanitize_title($section_type . '-' . $style),
                'title' => ! empty($item['title']) ? sanitize_text_field($item['title']) : ucfirst($section_type) . ' / ' . ucfirst($style),
                'summary' => ! empty($item['summary']) ? sanitize_text_field($item['summary']) : wp_trim_words($brief ?: 'Propuesta generada por OpenAI.', 18, '...'),
                'html' => $html,
            );
        }

        return count($normalized) === 3 ? $normalized : array();
    }
}