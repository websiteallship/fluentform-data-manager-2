<?php
if (!defined('ABSPATH')) {
    exit;
}

function create_custom_roles() {
    // Vai trò employee
    add_role('employee', 'Employee', array(
        'read' => true,
        'edit_form_submissions' => true, // Chỉnh sửa submissions của mình
        'read_form_submissions' => true, // Xem submissions của mình
    ));

    // Vai trò manager
    add_role('manager', 'Manager', array(
        'read' => true,
        'edit_form_submissions' => true, // Chỉnh sửa submissions của mình
        'edit_others_form_submissions' => true, // Chỉnh sửa submissions của người khác
        'read_form_submissions' => true, // Xem submissions của mình
    ));

    // Gán quyền đầy đủ cho administrator
    $admin_role = get_role('administrator');
    $admin_role->add_cap('edit_form_submission');
    $admin_role->add_cap('edit_form_submissions');
    $admin_role->add_cap('edit_others_form_submissions'); // Xem/chỉnh sửa submissions của người khác
    $admin_role->add_cap('publish_form_submissions');
    $admin_role->add_cap('read_form_submission');
    $admin_role->add_cap('read_form_submissions');
    $admin_role->add_cap('read_private_form_submissions');
    $admin_role->add_cap('delete_form_submission');
    $admin_role->add_cap('delete_form_submissions');
}
register_activation_hook(__FILE__, 'create_custom_roles');

function assign_referral_code_to_user($user_id) {
    if (!get_user_meta($user_id, 'referral_code', true)) {
        $referral_code = 'REF' . strtoupper(wp_generate_password(8, false, false));
        update_user_meta($user_id, 'referral_code', $referral_code);
    }
}
add_action('user_register', 'assign_referral_code_to_user');

function add_referral_code_field($user) {
    ?>
    <h3>Referral Code</h3>
    <table class="form-table">
        <tr>
            <th><label for="referral_code">Referral Code</label></th>
            <td>
                <input type="text" name="referral_code" id="referral_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'referral_code', true)); ?>" class="regular-text" />
                <p class="description">Mã referral của người dùng (để lại trống để tạo tự động).</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_referral_code_field');
add_action('edit_user_profile', 'add_referral_code_field');

function save_referral_code_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['referral_code']) && !empty($_POST['referral_code'])) {
        update_user_meta($user_id, 'referral_code', sanitize_text_field($_POST['referral_code']));
    } elseif (!get_user_meta($user_id, 'referral_code', true)) {
        $referral_code = 'REF' . strtoupper(wp_generate_password(8, false, false));
        update_user_meta($user_id, 'referral_code', $referral_code);
    }
}
add_action('personal_options_update', 'save_referral_code_field');
add_action('edit_user_profile_update', 'save_referral_code_field');