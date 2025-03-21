<?php
if (!defined('ABSPATH')) {
    exit;
}

function transfer_lead_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $new_user_id = intval($_POST['new_user_id']);
    $current_user = wp_get_current_user();

    // Danh sách vai trò được phép chuyển giao
    $allowed_roles = array('employee', 'manager', 'administrator');
    $user_roles = $current_user->roles;
    $has_allowed_role = false;
    foreach ($allowed_roles as $role) {
        if (in_array($role, $user_roles)) {
            $has_allowed_role = true;
            break;
        }
    }
    if (!$has_allowed_role) {
        wp_send_json_error('Not authorized');
    }

    // Chỉ employee cần kiểm tra post_author, manager và admin có thể chuyển bất kỳ lead nào
    if (in_array('employee', $user_roles) && !in_array('manager', $user_roles) && !in_array('administrator', $user_roles)) {
        $post = get_post($post_id);
        if ($post->post_author != $current_user->ID) {
            wp_send_json_error('Not authorized');
        }
    }

    $new_user = get_userdata($new_user_id);
    $notes = $current_user->display_name . " đã chuyển giao cho " . $new_user->display_name;
    wp_update_post(array('ID' => $post_id, 'post_author' => $new_user_id));
    update_post_meta($post_id, 'status', 'lead transferred');
    update_post_meta($post_id, 'notes', $notes);
    $history = get_post_meta($post_id, 'history', true) ?: array();
    $history[] = "Transferred to " . $new_user->display_name . " by " . $current_user->display_name . " at " . current_time('Y-m-d H:i:s');
    update_post_meta($post_id, 'history', $history);
    wp_send_json_success();
}
add_action('wp_ajax_transfer_lead', 'transfer_lead_ajax');