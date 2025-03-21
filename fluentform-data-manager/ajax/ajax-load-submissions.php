<?php
if (!defined('ABSPATH')) {
    exit;
}

function load_submissions_ajax() {
    check_ajax_referer('my-ajax-nonce', 'nonce');
    $form_id = intval($_POST['form_id']);
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 50;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $person = isset($_POST['person']) ? intval($_POST['person']) : '';
    $referral = isset($_POST['referral']) ? sanitize_text_field($_POST['referral']) : '';
    $created = isset($_POST['created']) ? sanitize_text_field($_POST['created']) : '';
    $updated = isset($_POST['updated']) ? sanitize_text_field($_POST['updated']) : '';
    $current_user = wp_get_current_user();
    $default_user_id = 1;

    $args = array(
        'post_type' => 'form_submission',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'meta_query' => array(
            array('key' => 'form_id', 'value' => $form_id),
        ),
    );

    // Tìm kiếm toàn văn trên meta fields
    if (!empty($search)) {
        $meta_keys = array(
            'name',
            'phone',
            'email',
            'shop_name',
            'address',
            'industry',
            'production',
            'referral_code',
            'notes'
        );
        $search_meta_query = array('relation' => 'OR');
        foreach ($meta_keys as $key) {
            $search_meta_query[] = array(
                'key' => $key,
                'value' => $search,
                'compare' => 'LIKE'
            );
        }
        $args['meta_query'][] = $search_meta_query;
    }

    // Lọc theo trạng thái
    if (!empty($status)) {
        $args['meta_query'][] = array(
            'key' => 'status',
            'value' => $status,
            'compare' => '='
        );
    }

    // Lọc theo người phụ trách
    if (!empty($person)) {
        $args['author'] = $person;
    }

    // Lọc theo referral code
    if (!empty($referral)) {
        $args['meta_query'][] = array(
            'key' => 'referral_code',
            'value' => $referral,
            'compare' => '='
        );
    }

    // Lọc theo ngày tạo
    if (!empty($created)) {
        $args['date_query'] = array(
            array(
                'after' => $created,
                'inclusive' => true,
            ),
        );
    }

    // Lọc theo ngày cập nhật
    if (!empty($updated)) {
        $args['date_query']['modified'] = array(
            'after' => $updated,
            'inclusive' => true,
        );
    }

    // Quyền employee
    if (in_array('employee', $current_user->roles)) {
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array('key' => 'status', 'value' => 'new'),
            array('key' => 'status', 'value' => 'lead transferred'),
        );
        $args['author__in'] = array($default_user_id, $current_user->ID);
    }

    $query = new WP_Query($args);
    ob_start();
    foreach ($query->posts as $post) {
        setup_postdata($post);
        ?>
        <tr>
            <td><?php echo esc_html(get_post_meta($post->ID, 'name', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'phone', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'email', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'shop_name', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'address', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'industry', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'production', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'referral_code', true)); ?></td>
            <td><?php echo ($post->post_author == $default_user_id) ? 'Unassigned' : esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
            <td>
                <?php 
                $status = get_post_meta($post->ID, 'status', true);
                if ($status == 'new' && in_array('employee', $current_user->roles)) {
                    echo 'New <button class="take-charge" data-post-id="' . $post->ID . '">Take Charge</button>';
                } elseif ($status == 'lead transferred' && $post->post_author == $current_user->ID) {
                    echo 'Lead Transferred <button class="take-charge" data-post-id="' . $post->ID . '">Take Charge</button>';
                } else {
                    $statuses = array('accepted', 'lead transferred', 'consulting', 'no answer', 'spam', 'failed', 'account created');
                    echo '<select class="status-update" data-post-id="' . $post->ID . '">';
                    foreach ($statuses as $s) {
                        $selected = ($s == $status) ? 'selected' : '';
                        echo '<option value="' . $s . '" ' . $selected . '>' . ucwords(str_replace('_', ' ', $s)) . '</option>';
                    }
                    echo '</select>';
                } 
                ?>
            </td>
            <td><?php echo esc_html(get_post_meta($post->ID, 'notes', true)); ?></td>
            <td><?php echo get_the_date('Y-m-d H:i:s', $post->ID); ?></td>
            <td><?php echo get_the_modified_date('Y-m-d H:i:s', $post->ID); ?></td>
            <td>
                <button class="transfer-lead" data-post-id="<?php echo $post->ID; ?>">Transfer</button>
                <button class="history" data-post-id="<?php echo $post->ID; ?>">History</button>
                <?php if (in_array('administrator', $current_user->roles)): ?>
                    <button class="reset-lead" data-post-id="<?php echo $post->ID; ?>">Reset</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
    $html = ob_get_clean();

    $pagination = paginate_links(array(
        'total' => $query->max_num_pages,
        'current' => $paged,
        'format' => '?page=%#%',
        'prev_text' => __('« Prev'),
        'next_text' => __('Next »'),
    ));

    wp_send_json_success(array('html' => $html, 'pagination' => $pagination));
}
add_action('wp_ajax_load_submissions', 'load_submissions_ajax');