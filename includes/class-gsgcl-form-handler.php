<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Form_Handler
{
    private $plugin;

    private $log_file_name = 'gsgcl-submissions.log';

    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('admin_post_nopriv_gsgcl_submit_landing', array($this, 'handle_submission'));
        add_action('admin_post_gsgcl_submit_landing', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_gsgcl_submit_landing_async', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_gsgcl_submit_landing_async', array($this, 'handle_ajax_submission'));
    }

    public function handle_submission()
    {
        $result = $this->process_submission($_POST);

        if ('success' === $result['status']) {
            $this->redirect_with_status('success', $result['page_id'], '', $result['redirect_url']);
        }

        $this->redirect_with_status('error', $result['page_id'], $result['reason']);
    }

    public function handle_ajax_submission()
    {
        $result = $this->process_submission($_POST);

        if ('success' === $result['status']) {
            wp_send_json_success($result);
        }

        $status_code = 'rate_limit' === $result['reason'] ? 429 : 422;
        wp_send_json_error($result, $status_code);
    }

    private function sanitize_payload($input)
    {
        $input = wp_unslash($input);

        return array(
            'friend_name' => isset($input['friend_name']) ? sanitize_text_field($input['friend_name']) : '',
            'friend_last_name' => isset($input['friend_last_name']) ? sanitize_text_field($input['friend_last_name']) : '',
            'friend_destination' => isset($input['friend_destination']) ? sanitize_text_field($input['friend_destination']) : '',
            'friend_whatsapp' => isset($input['friend_whatsapp']) ? sanitize_text_field($input['friend_whatsapp']) : '',
            'friend_email' => isset($input['friend_email']) ? sanitize_email($input['friend_email']) : '',
            'friend_interest' => isset($input['friend_interest']) ? sanitize_text_field($input['friend_interest']) : '',
            'student_name' => isset($input['student_name']) ? sanitize_text_field($input['student_name']) : '',
            'student_email' => isset($input['student_email']) ? sanitize_email($input['student_email']) : '',
            'student_comments' => isset($input['student_comments']) ? sanitize_textarea_field($input['student_comments']) : '',
            'submitted_at' => current_time('mysql'),
        );
    }

    private function build_request_context($page_id)
    {
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr((string) wp_unslash($_SERVER['HTTP_USER_AGENT']), 0, 255)) : '';
        $referer = wp_get_referer();
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(home_url(wp_unslash($_SERVER['REQUEST_URI']))) : '';

        return array(
            'page_id' => absint($page_id),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referer' => $referer ? esc_url_raw($referer) : '',
            'request_uri' => $request_uri,
        );
    }

    private function validate_payload($payload)
    {
        $errors = array();

        foreach (array('friend_name', 'friend_last_name', 'student_name', 'friend_email', 'student_email') as $required_field) {
            if (empty($payload[$required_field])) {
                $errors[] = $required_field;
            }
        }

        foreach (array('friend_email', 'student_email') as $email_field) {
            if (empty($payload[$email_field]) || ! is_email($payload[$email_field])) {
                $errors[] = $email_field;
            }
        }

        return array_unique($errors);
    }

    private function process_submission($input)
    {
        $nonce = isset($input['gsgcl_form_nonce']) ? sanitize_text_field(wp_unslash($input['gsgcl_form_nonce'])) : '';
        $landing_id = isset($input['landing_id']) ? absint($input['landing_id']) : 0;
        $page_id = isset($input['page_id']) ? absint($input['page_id']) : 0;
        $honeypot = isset($input['company']) ? trim((string) wp_unslash($input['company'])) : '';

        if (! wp_verify_nonce($nonce, 'gsgcl_submit_landing')) {
            return $this->error_result('nonce', $landing_id, $page_id);
        }

        if ($honeypot !== '') {
            return $this->error_result('spam', $landing_id, $page_id);
        }

        if (! $landing_id || 'gsg_landing' !== get_post_type($landing_id)) {
            return $this->error_result('landing', $landing_id, $page_id);
        }

        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_limit_key = 'gsgcl_rate_' . md5($landing_id . '|' . $ip_address);
        if (get_transient($rate_limit_key)) {
            return $this->error_result('rate_limit', $landing_id, $page_id);
        }

        $payload = $this->sanitize_payload($input);
        $request_context = $this->build_request_context($page_id);
        $errors = $this->validate_payload($payload);
        if (! empty($errors)) {
            return $this->error_result(implode(',', $errors), $landing_id, $page_id, array('validation_fields' => $errors));
        }

        set_transient($rate_limit_key, 1, 30);

        $submission_id = wp_insert_post(
            array(
                'post_type' => 'gsg_submission',
                'post_status' => 'private',
                'post_title' => sprintf(
                    '%s - %s',
                    $payload['friend_name'],
                    current_time('mysql')
                ),
            ),
            true
        );

        if (is_wp_error($submission_id) || ! $submission_id) {
            return $this->error_result(
                'submission_insert',
                $landing_id,
                $page_id,
                array(
                    'insert_error' => is_wp_error($submission_id) ? $submission_id->get_error_message() : 'unknown_insert_failure',
                )
            );
        }

        update_post_meta($submission_id, 'gsgcl_landing_id', $landing_id);
        update_post_meta($submission_id, 'gsgcl_payload', $payload);
        update_post_meta($submission_id, 'gsgcl_request_context', $request_context);
        update_post_meta($submission_id, 'gsgcl_openai_enabled', $this->plugin->get_landing_meta($landing_id, 'gsgcl_openai_enabled', '0'));
        update_post_meta($submission_id, 'gsgcl_openai_context', $this->plugin->get_landing_meta($landing_id, 'gsgcl_openai_context', ''));

        try {
            do_action('gsgcl_submission_received', $payload, $landing_id, $submission_id);

            $configured_hook = $this->plugin->get_landing_meta($landing_id, 'gsgcl_submission_hook', '');
            if ($configured_hook) {
                $this->dispatch_submission_integration($configured_hook, $payload, $landing_id, $submission_id);
            }
        } catch (Throwable $throwable) {
            return $this->error_result(
                'hook_exception',
                $landing_id,
                $page_id,
                array(
                    'submission_id' => $submission_id,
                    'exception_message' => $throwable->getMessage(),
                )
            );
        }

        $redirect_override = $this->plugin->get_landing_meta($landing_id, 'gsgcl_redirect_url', '');
        $result = array(
            'status' => 'success',
            'reason' => '',
            'message' => $this->get_notice_message($landing_id, 'success'),
            'landing_id' => $landing_id,
            'page_id' => $page_id,
            'submission_id' => $submission_id,
            'redirect_url' => $redirect_override,
        );

        $this->write_log('info', 'submission_success', array(
            'landing_id' => $landing_id,
            'page_id' => $page_id,
            'submission_id' => $submission_id,
        ));

        return $result;
    }

    private function dispatch_submission_integration($configured_hook, $payload, $landing_id, $submission_id)
    {
        if (filter_var($configured_hook, FILTER_VALIDATE_URL)) {
            $response = wp_remote_post(
                $configured_hook,
                array(
                    'timeout' => 15,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                    'body' => wp_json_encode(
                        array(
                            'payload' => $payload,
                            'landing_id' => $landing_id,
                            'submission_id' => $submission_id,
                        )
                    ),
                )
            );

            if (is_wp_error($response)) {
                throw new RuntimeException('webhook_request_failed: ' . $response->get_error_message());
            }

            $status_code = (int) wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                throw new RuntimeException('webhook_http_' . $status_code);
            }

            $this->write_log('info', 'submission_webhook_sent', array(
                'landing_id' => $landing_id,
                'submission_id' => $submission_id,
                'webhook_url' => $configured_hook,
                'http_status' => $status_code,
            ));

            return;
        }

        do_action($configured_hook, $payload, $landing_id, $submission_id);
    }

    private function error_result($reason, $landing_id, $page_id, $context = array())
    {
        $context = array_merge(
            array(
                'landing_id' => $landing_id,
                'page_id' => $page_id,
            ),
            $context
        );

        $this->write_log('error', 'submission_error', array_merge($context, array('reason' => $reason)));

        return array(
            'status' => 'error',
            'reason' => $reason,
            'message' => $this->get_notice_message($landing_id, 'error'),
            'landing_id' => $landing_id,
            'page_id' => $page_id,
            'redirect_url' => '',
        );
    }

    private function get_notice_message($landing_id, $status)
    {
        if ('success' === $status) {
            $message = $this->plugin->get_landing_meta($landing_id, 'gsgcl_success_message', 'Gracias. Tu solicitud ha sido registrada con éxito.');

            if ('' === trim((string) $message) || 'Gracias. Recibimos tu registro y nuestro equipo revisará el caso pronto.' === trim((string) $message)) {
                return 'Gracias. Tu solicitud ha sido registrada con éxito.';
            }

            return $message;
        }

        return $this->plugin->get_landing_meta($landing_id, 'gsgcl_error_message', 'No pudimos procesar tu registro. Revisa los datos e intenta nuevamente.');
    }

    private function write_log($level, $event, $context = array())
    {
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            return;
        }

        $log_dir = trailingslashit($upload_dir['basedir']) . 'gsgcl-logs';
        if (! wp_mkdir_p($log_dir)) {
            return;
        }

        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'event' => $event,
            'context' => $context,
        );

        $encoded = wp_json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($encoded)) {
            return;
        }

        $log_file = trailingslashit($log_dir) . $this->log_file_name;
        @file_put_contents($log_file, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function redirect_with_status($status, $page_id = 0, $reason = '', $override_url = '')
    {
        $target_url = $override_url ? $override_url : ($page_id ? get_permalink($page_id) : home_url('/'));
        $target_url = add_query_arg(
            array_filter(
                array(
                    'gsgcl_status' => $status,
                    'gsgcl_reason' => $reason,
                )
            ),
            $target_url
        );

        wp_safe_redirect($target_url);
        exit;
    }
}