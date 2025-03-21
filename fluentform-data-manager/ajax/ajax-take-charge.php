<?php
if (!defined('ABSPATH')) {
    exit;
}

function take_charge_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $current_user = wp_get_current_user();
    if (!in_array('employee', $current_user->roles)) wp_send_json_error('Not authorized');
    $status = get_post_meta($post_id, 'status', true);
    if (!in_array($status, array('new', 'lead transferred'))) wp_send_json_error('Cannot take charge');
    
    if ($status == 'lead transferred') {
        $notes = get_post_meta($post_id, 'notes', true);
        $notes .= " + " . $current_user->display_name . " đã nhận vào lúc " . current_time('Y-m-d H:i:s');
        update_post_meta($post_id, 'notes', $notes);
    }
    wp_update_post(array('ID' => $post_id, 'post_author' => $current_user->ID));
    update_post_meta($post_id, 'status', 'accepted');
    $history = get_post_meta($post_id, 'history', true) ?: array();
    $history[] = "Taken charge by " . $current_user->display_name . " at " . current_time('Y-m-d H:i:s');
    update_post_meta($post_id, 'history', $history);
    wp_send_json_success();
}
add_action('wp_ajax_take_charge', 'take_charge_ajax');