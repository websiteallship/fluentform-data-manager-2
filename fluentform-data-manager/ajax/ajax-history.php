<?php
if (!defined('ABSPATH')) {
    exit;
}

function get_lead_history_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $history = get_post_meta($post_id, 'history', true) ?: array();
    wp_send_json_success($history);
}
add_action('wp_ajax_get_lead_history', 'get_lead_history_ajax');