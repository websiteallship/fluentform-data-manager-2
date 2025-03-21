<?php
if (!defined('ABSPATH')) {
    exit;
}

function reset_lead_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $current_user = wp_get_current_user();

    if (!in_array('administrator', $current_user->roles)) {
        wp_send_json_error('Not authorized');
    }

    $default_user_id = 1;
    wp_update_post(array('ID' => $post_id, 'post_author' => $default_user_id));
    update_post_meta($post_id, 'status', 'new');
    update_post_meta($post_id, 'notes', '');
    $history = get_post_meta($post_id, 'history', true) ?: array();
    $history[] = "Reset to new by " . $current_user->display_name . " at " . current_time('Y-m-d H:i:s');
    update_post_meta($post_id, 'history', $history);
    wp_send_json_success();
}
add_action('wp_ajax_reset_lead', 'reset_lead_ajax');