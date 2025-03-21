<?php
if (!defined('ABSPATH')) {
    exit;
}

function add_form_submission_metabox() {
    add_meta_box(
        'form_submission_data',
        'Submission Details',
        'render_form_submission_metabox',
        'form_submission',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_form_submission_metabox');

function render_form_submission_metabox($post) {
    wp_nonce_field('form_submission_save', 'form_submission_nonce');
    $fields = array(
        'name' => 'Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'shop_name' => 'Shop Name',
        'address' => 'Address',
        'industry' => 'Industry',
        'production' => 'Production',
        'referral_code' => 'Referral Code',
    );
    $status = get_post_meta($post->ID, 'status', true);
    $notes = get_post_meta($post->ID, 'notes', true);
    $person_in_charge = ($post->post_author == 1) ? 'Unassigned' : get_the_author_meta('display_name', $post->post_author);

    $current_user = wp_get_current_user();
    $can_edit = current_user_can('edit_post', $post->ID);

    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        ?>
        <p>
            <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?>:</label><br>
            <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" 
                   value="<?php echo esc_attr($value); ?>" style="width: 100%;" 
                   <?php echo $can_edit ? '' : 'readonly'; ?> />
        </p>
        <?php
    }
    ?>
    <p>
        <label for="status">Status:</label><br>
        <select id="status" name="status" <?php echo $can_edit ? '' : 'disabled'; ?>>
            <?php
            $statuses = array('new', 'accepted', 'lead transferred', 'consulting', 'no answer', 'spam', 'failed', 'account created');
            foreach ($statuses as $s) {
                $selected = ($s === $status) ? 'selected' : '';
                echo '<option value="' . esc_attr($s) . '" ' . $selected . '>' . ucwords(str_replace('_', ' ', $s)) . '</option>';
            }
            ?>
        </select>
    </p>
    <p>
        <label>Person in Charge:</label><br>
        <input type="text" value="<?php echo esc_attr($person_in_charge); ?>" readonly style="width: 100%;" />
    </p>
    <p>
        <label for="notes">Notes:</label><br>
        <textarea id="notes" name="notes" style="width: 100%;" <?php echo $can_edit ? '' : 'readonly'; ?>><?php echo esc_textarea($notes); ?></textarea>
    </p>
    <?php if (in_array('administrator', $current_user->roles)): ?>
    <p>
        <button type="button" class="reset-lead" data-post-id="<?php echo $post->ID; ?>">Reset Status</button>
    </p>
    <?php endif; ?>
    <?php
}

function save_form_submission_metabox($post_id) {
    if (!isset($_POST['form_submission_nonce']) || !wp_verify_nonce($_POST['form_submission_nonce'], 'form_submission_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array('name', 'phone', 'email', 'shop_name', 'address', 'industry', 'production', 'referral_code', 'status', 'notes');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, $field, $value);
            if ($field == 'status') {
                $history = get_post_meta($post_id, 'history', true) ?: array();
                $history[] = "Status changed to " . ucwords(str_replace('_', ' ', $value)) . " by " . wp_get_current_user()->display_name . " at " . current_time('Y-m-d H:i:s');
                update_post_meta($post_id, 'history', $history);
            }
        }
    }
}
add_action('save_post', 'save_form_submission_metabox');

// Thêm cột vào danh sách Form Submissions
add_filter('manage_form_submission_posts_columns', 'ffdm_add_submission_columns');
function ffdm_add_submission_columns($columns) {
    $columns = array(
        'cb' => $columns['cb'],
        'title' => $columns['title'],
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status',
        'person_in_charge' => 'Person in Charge',
        'date' => $columns['date'],
    );
    return $columns;
}

// Hiển thị dữ liệu trong các cột
add_action('manage_form_submission_posts_custom_column', 'ffdm_render_submission_columns', 10, 2);
function ffdm_render_submission_columns($column, $post_id) {
    switch ($column) {
        case 'name':
            echo esc_html(get_post_meta($post_id, 'name', true));
            break;
        case 'email':
            echo esc_html(get_post_meta($post_id, 'email', true));
            break;
        case 'status':
            echo esc_html(ucwords(str_replace('_', ' ', get_post_meta($post_id, 'status', true))));
            break;
        case 'person_in_charge':
            $author_id = get_post_field('post_author', $post_id);
            echo ($author_id == 1) ? 'Unassigned' : esc_html(get_the_author_meta('display_name', $author_id));
            break;
    }
}