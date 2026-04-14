<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Section_AI
{
    public function generate_proposals($section_type, $brief, $reference_html = '')
    {
        $section_type = sanitize_key($section_type);
        $brief = sanitize_textarea_field($brief);
        $reference_context = wp_strip_all_tags($reference_html);
        $reference_excerpt = $reference_context ? wp_trim_words($reference_context, 22, '...') : '';

        $proposals = array(
            $this->build_proposal($section_type, 'editorial', $brief, $reference_excerpt),
            $this->build_proposal($section_type, 'conversion', $brief, $reference_excerpt),
            $this->build_proposal($section_type, 'compact', $brief, $reference_excerpt),
        );

        return apply_filters('gsgcl_generate_section_proposals', $proposals, $section_type, $brief, $reference_html);
    }

    private function build_proposal($section_type, $style, $brief, $reference_excerpt)
    {
        $title = ucfirst($section_type) . ' / ' . ucfirst($style);
        $summary = $brief ? wp_trim_words($brief, 18, '...') : 'Sin brief detallado';

        if ('counter' === $section_type) {
            return $this->build_counter_proposal($style, $title, $summary, $brief, $reference_excerpt);
        }

        if ('hero' === $section_type) {
            return $this->build_hero_proposal($style, $title, $summary, $brief, $reference_excerpt);
        }

        return array(
            'id' => sanitize_title($section_type . '-' . $style),
            'title' => $title,
            'summary' => $summary,
            'html' => $this->render_generic_section($section_type, $style, $brief, $reference_excerpt),
        );
    }

    private function build_counter_proposal($style, $title, $summary, $brief, $reference_excerpt)
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
            'html' => $html_by_style[$style] . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : ''),
        );
    }

    private function build_hero_proposal($style, $title, $summary, $brief, $reference_excerpt)
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
            'html' => $html . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : ''),
        );
    }

    private function render_generic_section($section_type, $style, $brief, $reference_excerpt)
    {
        return '
<section class="gsgcl-section-preview gsgcl-section-preview--' . esc_attr($section_type) . ' gsgcl-section-style-' . esc_attr($style) . '">
    <small>' . esc_html(ucfirst($section_type)) . ' / ' . esc_html(ucfirst($style)) . '</small>
    <h2>Título editable de la sección</h2>
    <p>' . esc_html($brief ?: 'Contenido base generado a partir del brief del editor.') . '</p>
    <a href="#">CTA de ejemplo</a>
</section>' . ($reference_excerpt ? "\n<!-- Referencia: " . esc_html($reference_excerpt) . " -->" : '');
    }
}