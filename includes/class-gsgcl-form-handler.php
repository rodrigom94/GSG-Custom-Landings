<?php

if (! defined('ABSPATH')) {
    exit;
}

class GSGCL_Form_Handler
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('admin_post_nopriv_gsgcl_submit_landing', array($this, 'handle_submission'));
        add_action('admin_post_gsgcl_submit_landing', array($this, 'handle_submission'));
    }

    public function handle_submission()
    {
        $nonce = isset($_POST['gsgcl_form_nonce']) ? sanitize_text_field(wp_unslash($_POST['gsgcl_form_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'gsgcl_submit_landing')) {
            $this->redirect_with_status('error', 0, 'nonce');
        }

        $landing_id = isset($_POST['landing_id']) ? absint($_POST['landing_id']) : 0;
        $page_id = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;
        $honeypot = isset($_POST['company']) ? trim((string) wp_unslash($_POST['company'])) : '';

        if ($honeypot !== '') {
            $this->redirect_with_status('error', $page_id, 'spam');
        }

        if (! $landing_id || 'gsg_landing' !== get_post_type($landing_id)) {
            $this->redirect_with_status('error', $page_id, 'landing');
        }

        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_limit_key = 'gsgcl_rate_' . md5($landing_id . '|' . $ip_address);
        if (get_transient($rate_limit_key)) {
            $this->redirect_with_status('error', $page_id, 'rate_limit');
        }

        $payload = $this->sanitize_payload($_POST);
        $errors = $this->validate_payload($payload);
        if (! empty($errors)) {
            $this->redirect_with_status('error', $page_id, implode(',', $errors));
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

        if (! is_wp_error($submission_id) && $submission_id) {
            update_post_meta($submission_id, 'gsgcl_landing_id', $landing_id);
            update_post_meta($submission_id, 'gsgcl_payload', $payload);
            update_post_meta($submission_id, 'gsgcl_openai_enabled', $this->plugin->get_landing_meta($landing_id, 'gsgcl_openai_enabled', '0'));
            update_post_meta($submission_id, 'gsgcl_openai_context', $this->plugin->get_landing_meta($landing_id, 'gsgcl_openai_context', ''));
        }

        do_action('gsgcl_submission_received', $payload, $landing_id, $submission_id);

        $configured_hook = $this->plugin->get_landing_meta($landing_id, 'gsgcl_submission_hook', '');
        if ($configured_hook) {
            do_action($configured_hook, $payload, $landing_id, $submission_id);
        }

        $redirect_override = $this->plugin->get_landing_meta($landing_id, 'gsgcl_redirect_url', '');
        $this->redirect_with_status('success', $page_id, '', $redirect_override);
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

    private function validate_payload($payload)
    {
        $errors = array();

        foreach (array('friend_name', 'friend_last_name', 'student_name') as $required_field) {
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