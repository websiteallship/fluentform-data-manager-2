<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('fluentform/submission_inserted', 'handle_form_submission', 10, 3);
function handle_form_submission($entryId, $formData, $form) {
    $default_user_id = 1;
    $referral_code = isset($formData['referral_code']) ? $formData['referral_code'] : '';
    $assigned_user_id = $default_user_id;

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

    $post_id = wp_insert_post(array(
        'post_type' => 'form_submission',
        'post_title' => 'Submission #' . $entryId,
        'post_status' => 'publish',
        'post_author' => $assigned_user_id,
    ));
    if ($post_id) {
        update_post_meta($post_id, 'form_id', $form->id);
        foreach ($formData as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'status', 'new');
        update_post_meta($post_id, 'notes', '');
        update_post_meta($post_id, 'history', array("Created by system at " . current_time('Y-m-d H:i:s')));
    }
}