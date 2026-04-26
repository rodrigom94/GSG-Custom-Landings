(function ($) {
    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function parseSelectedSections($list) {
        var items = [];
        $list.find('.gsgcl-selected-item').each(function (index) {
            var $item = $(this);
            items.push({
                section_id: Number($item.data('sectionId')),
                order: index + 1,
            });
        });
        return items;
    }

    function updateSchemaField($root) {
        var targetId = $root.data('target-input');
        var $field = $('#' + targetId);
        var value = parseSelectedSections($root.find('.gsgcl-selected-sections'));
        $field.val(JSON.stringify(value));
    }

    function buildSelectedSectionItem(data) {
        return $(
            '<li class="gsgcl-selected-item" data-section-id="' + data.id + '">' +
                '<span class="gsgcl-drag-handle">::</span>' +
                '<div><strong>' + data.title + '</strong><span>' + data.type + ' / ' + data.variant + '</span></div>' +
                '<button type="button" class="button-link-delete gsgcl-remove-section-button">Quitar</button>' +
            '</li>'
        );
    }

    function setStatus($root, message, isError) {
        var $status = $root.find('.gsgcl-live-status').first();
        $status.text(message || '');
        $status.css('color', isError ? '#b32d2e' : '#2271b1');
    }

    function collectSectionPayload($root) {
        var localBrief = $root.find('.gsgcl-section-brief, #gsgcl_section_brief').first().val();
        var localImage = $root.find('.gsgcl-section-reference-image, #gsgcl_section_reference_image_url_preview').first().val();

        return {
            section_id: Number($root.data('sectionId') || $('#post_ID').val()),
            section_type: $root.find('.gsgcl-section-type, #gsgcl_section_type').first().val(),
            variant: $root.find('.gsgcl-section-variant, #gsgcl_section_variant').first().val(),
            version: $root.find('.gsgcl-section-version, #gsgcl_section_version').first().val(),
            brief: localBrief || $('#gsgcl_landing_reference_brief').val(),
            preview_html: $root.find('.gsgcl-section-preview-html, #gsgcl_section_preview_html').first().val(),
            reference_image_url: localImage || $('#gsgcl_landing_reference_image_url').val(),
        };
    }

    function updateAnalysis($root, analysis) {
        if (!analysis) {
            return;
        }

        var html = '' +
            '<ul>' +
                '<li>Headings: ' + analysis.heading_count + '</li>' +
                '<li>Párrafos: ' + analysis.paragraph_count + '</li>' +
                '<li>Inputs: ' + analysis.input_count + '</li>' +
                '<li>CTAs: ' + analysis.cta_count + '</li>' +
            '</ul>';

        if (analysis.headings && analysis.headings.length) {
            html += '<p><strong>Headings detectados:</strong> ' + analysis.headings.join(' | ') + '</p>';
        }

        $root.find('.gsgcl-analysis-box').first().html('<h4>Análisis actual del HTML</h4>' + html);
    }

    function replaceSidePanels($root, response) {
        var $proposalsContainer = $root.find('.gsgcl-inline-proposals-panel, #gsgcl_section_proposals .inside > .gsgcl-admin-stack > div').first();
        var $revisionsContainer = $root.find('.gsgcl-inline-revisions-panel, #gsgcl_section_proposals .inside > .gsgcl-admin-stack > div').eq($root.find('.gsgcl-inline-revisions-panel').length ? 0 : 1);

        if (response.proposals_html) {
            $proposalsContainer.html('<h4>Variaciones propuestas</h4>' + response.proposals_html);
        }
        if (response.revisions_html) {
            $revisionsContainer.html('<h4>Revisiones</h4>' + response.revisions_html);
        }
    }

    function getEditorRoot($trigger) {
        var $root = $trigger.closest('.gsgcl-section-editor');
        if ($root.length) {
            return $root;
        }

        return $(document.body);
    }

    function activateAdminTab($button) {
        var $root = $button.closest('.gsgcl-tabbed-panel');
        var targetId = $button.data('tabTarget');

        if (!$root.length || !targetId) {
            return;
        }

        $root.find('.gsgcl-admin-tab').removeClass('is-active').attr('aria-selected', 'false').attr('tabindex', '-1');
        $button.addClass('is-active').attr('aria-selected', 'true').attr('tabindex', '0');

        $root.find('.gsgcl-admin-tabpanel').removeClass('is-active').attr('hidden', 'hidden');
        $root.find('#' + targetId).addClass('is-active').removeAttr('hidden');
    }

    function buildInlineEditorCard(payload) {
        return $(payload.editor_html || '');
    }

    $(function () {
        var $tabbedPanels = $('.gsgcl-tabbed-panel');
        if ($tabbedPanels.length) {
            $tabbedPanels.each(function () {
                var $root = $(this);
                var defaultTab = $root.data('defaultTab');
                var $defaultButton = defaultTab
                    ? $root.find('.gsgcl-admin-tab[data-tab-target="' + defaultTab + '"]').first()
                    : $root.find('.gsgcl-admin-tab').first();

                if ($defaultButton.length) {
                    activateAdminTab($defaultButton);
                }
            });

            $(document).on('click', '.gsgcl-admin-tab', function () {
                activateAdminTab($(this));
            });
        }

        var $landingSections = $('.gsgcl-landing-sections');
        if ($landingSections.length) {
            $landingSections.each(function () {
                var $root = $(this);
                var $selected = $root.find('.gsgcl-selected-sections');

                $selected.sortable({
                    handle: '.gsgcl-drag-handle',
                    update: function () {
                        updateSchemaField($root);
                    },
                });

                $root.on('click', '.gsgcl-add-section-button', function () {
                    var $item = $(this).closest('.gsgcl-library-item');
                    var sectionId = Number($item.data('sectionId'));
                    if ($selected.find('[data-section-id="' + sectionId + '"]').length) {
                        return;
                    }

                    var payload = {};
                    var rawJson = $item.find('.gsgcl-library-item-json').text();
                    if (rawJson) {
                        payload = JSON.parse(rawJson);
                    }

                    $selected.append(buildSelectedSectionItem({
                        id: sectionId,
                        title: $item.data('sectionTitle'),
                        type: $item.data('sectionType'),
                        variant: $item.data('sectionVariant'),
                    }));

                    if (payload.editor_html && !$('#gsgcl-inline-editors-wrap').find('[data-section-id="' + sectionId + '"]').length) {
                        $('#gsgcl-inline-editors-wrap').append(buildInlineEditorCard(payload));
                    }

                    updateSchemaField($root);
                });

                $root.on('click', '.gsgcl-remove-section-button', function () {
                    var $selectedItem = $(this).closest('.gsgcl-selected-item');
                    var sectionId = Number($selectedItem.data('sectionId'));
                    $selectedItem.remove();
                    $('#gsgcl-inline-editors-wrap').find('[data-section-id="' + sectionId + '"]').remove();
                    updateSchemaField($root);
                });
            });
        }

        if ($('#gsgcl-live-preview-frame').length || $('.gsgcl-section-editor').length) {
            $(document).on('click', '.gsgcl-preview-live-button', function () {
                var $root = getEditorRoot($(this));
                var payload = collectSectionPayload($root);
                payload.action = 'gsgcl_preview_section';
                payload.nonce = gsgclAdmin.previewNonce;

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        $root.find('.gsgcl-live-preview-frame').first().html(response.data.preview_html);
                        updateAnalysis($root, response.data.analysis);
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.previewUpdated, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });

            $(document).on('click', '.gsgcl-save-live-button', function () {
                var $root = getEditorRoot($(this));
                var payload = collectSectionPayload($root);
                payload.action = 'gsgcl_save_section';
                payload.nonce = gsgclAdmin.saveNonce;

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        $root.find('.gsgcl-section-preview-html, #gsgcl_section_preview_html').first().val(response.data.preview_html);
                        $root.find('.gsgcl-live-preview-frame').first().html(response.data.preview_html);
                        updateAnalysis($root, response.data.analysis);
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.sectionSaved, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });

            $(document).on('click', '.gsgcl-restore-revision-button', function () {
                var $root = getEditorRoot($(this));
                var payload = {
                    action: 'gsgcl_restore_section',
                    section_id: Number($root.data('sectionId') || $('#post_ID').val()),
                    revision_id: $(this).data('revisionId'),
                    nonce: gsgclAdmin.restoreNonce,
                };

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        $root.find('.gsgcl-section-preview-html, #gsgcl_section_preview_html').first().val(response.data.preview_html);
                        $root.find('.gsgcl-live-preview-frame').first().html(response.data.preview_html);
                        updateAnalysis($root, response.data.analysis);
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.revisionRestored, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });

            $(document).on('click', '.gsgcl-apply-proposal-button', function () {
                var $root = getEditorRoot($(this));
                var payload = {
                    action: 'gsgcl_apply_proposal',
                    section_id: Number($root.data('sectionId') || $('#post_ID').val()),
                    proposal_index: Number($(this).data('proposalIndex')),
                    nonce: gsgclAdmin.applyNonce,
                };

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        $root.find('.gsgcl-section-preview-html, #gsgcl_section_preview_html').first().val(response.data.preview_html);
                        $root.find('.gsgcl-live-preview-frame').first().html(response.data.preview_html);
                        updateAnalysis($root, response.data.analysis);
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.proposalApplied, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });

            $(document).on('click', '.gsgcl-inline-generate-button', function (event) {
                event.preventDefault();
                var $root = getEditorRoot($(this));
                var payload = collectSectionPayload($root);
                payload.action = 'gsgcl_generate_proposals';
                payload.nonce = gsgclAdmin.generateNonce;

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.proposalsGenerated, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });

            $(document).on('submit', '.gsgcl-inline-form', function (event) {
                event.preventDefault();
                var $root = getEditorRoot($(this));
                var payload = collectSectionPayload($root);
                payload.action = 'gsgcl_generate_proposals';
                payload.nonce = gsgclAdmin.generateNonce;

                $.post(gsgclAdmin.ajaxUrl, payload)
                    .done(function (response) {
                        if (!response.success) {
                            setStatus($root, gsgclAdmin.messages.error, true);
                            return;
                        }
                        replaceSidePanels($root, response.data);
                        setStatus($root, gsgclAdmin.messages.proposalsGenerated, false);
                    })
                    .fail(function () {
                        setStatus($root, gsgclAdmin.messages.error, true);
                    });
            });
        }
    });
})(jQuery);