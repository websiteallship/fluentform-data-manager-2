<?php
if (!defined('ABSPATH')) {
    exit;
}

function update_status_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $current_user = wp_get_current_user();
    $allowed_roles = array('employee', 'manager', 'administrator');
    $user_roles = $current_user->roles;
    $has_allowed_role = false;
    foreach ($allowed_roles as $role) {
        if (in_array($role, $user_roles)) {
            $has_allowed_role = true;
            break;
        }
    }
    if (!$has_allowed_role) wp_send_json_error('Not authorized');

    if (in_array($new_status, array('new', 'accepted'))) wp_send_json_error('Invalid status');

    if (in_array('employee', $user_roles)) {
        $post = get_post($post_id);
        if ($post->post_author != $current_user->ID) wp_send_json_error('Not authorized');
    }

    update_post_meta($post_id, 'status', $new_status);
    $history = get_post_meta($post_id, 'history', true) ?: array();
    $history[] = "Status changed to " . ucwords(str_replace('_', ' ', $new_status)) . " by " . $current_user->display_name . " at " . current_time('Y-m-d H:i:s');
    update_post_meta($post_id, 'history', $history);
    wp_update_post(array('ID' => $post_id));
    wp_send_json_success();
}
add_action('wp_ajax_update_status', 'update_status_ajax');