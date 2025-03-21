<?php
if (!defined('ABSPATH')) {
    exit;
}

function handle_webhook_submission($request) {
    $api_key = $request->get_header('X-API-Key');
    $expected_key = 'my-secret-key-12345'; // Thay bằng key bạn đặt trên allship.vn
    if ($api_key !== $expected_key) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Invalid API Key'), 403);
    }

    $data = $request->get_params();
    $default_user_id = 1;
    $referral_code = isset($data['referral_code']) ? sanitize_text_field($data['referral_code']) : '';
    $assigned_user_id = $default_user_id;

    // Kiểm tra referral_code để gán user
    if ($referral_code) {
        $users = get_users(array(
            'meta_key' => 'referral_code',
            'meta_value' => $referral_code,
            'number' => 1,
        ));
        if (!empty($users)) {
            $assigned_user_id = $users[0]->ID;
        }
    }

    // Tạo post mới
    $post_id = wp_insert_post(array(
        'post_type' => 'form_submission',
        'post_title' => 'Submission #' . time(),
        'post_status' => 'publish',
        'post_author' => $assigned_user_id,
    ));

    if ($post_id) {
        $fields = array('name', 'phone', 'email', 'shop_name', 'address', 'industry', 'production', 'referral_code');
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($data[$field]));
            }
        }
        // Lấy form_id từ __submission nếu có, nếu không mặc định là 0
        $form_id = isset($data['__submission']['form_id']) ? sanitize_text_field($data['__submission']['form_id']) : 0;
        update_post_meta($post_id, 'form_id', $form_id);
        update_post_meta($post_id, 'status', 'new');
        update_post_meta($post_id, 'notes', '');
        update_post_meta($post_id, 'history', array("Created by webhook at " . current_time('Y-m-d H:i:s')));
        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
    }

    return new WP_REST_Response(array('success' => false, 'message' => 'Failed to create submission'), 500);
}