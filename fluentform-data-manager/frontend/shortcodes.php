<?php
if (!defined('ABSPATH')) {
    exit;
}

function form_submissions_shortcode($atts) {
    $atts = shortcode_atts(array('form_id' => 0), $atts);
    $form_id = intval($atts['form_id']);
    if (!$form_id) {
        return 'Please provide a valid form ID.';
    }
    $current_user = wp_get_current_user();
    $default_user_id = 1;

    ob_start();
    ?>
    <div class="submission-table-wrapper">
        <div class="filter-search-options">
            <div class="search-box">
                <label for="search-input">Tìm kiếm: </label>
                <input type="text" id="search-input" placeholder="Nhập từ khóa...">
            </div>
            <div class="filter-options">
                <label for="filter-status">Trạng thái: </label>
                <select id="filter-status">
                    <option value="">Tất cả</option>
                    <option value="new">Mới</option>
                    <option value="accepted">Đã tiếp nhận</option>
                    <option value="lead transferred">Lead chuyển giao</option>
                    <option value="consulting">Đang tư vấn</option>
                    <option value="no answer">Không nghe máy</option>
                    <option value="spam">Spam</option>
                    <option value="failed">Thất bại</option>
                    <option value="account created">Đã tạo tài khoản</option>
                </select>

                <label for="filter-person">Người phụ trách: </label>
                <select id="filter-person">
                    <option value="">Tất cả</option>
                    <option value="<?php echo $default_user_id; ?>">Unassigned</option>
                    <?php 
                    $all_users = get_users(array('role__in' => array('employee', 'manager', 'administrator')));
                    foreach ($all_users as $user) {
                        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                    }
                    ?>
                </select>

                <label for="filter-referral">Referral Code: </label>
                <input type="text" id="filter-referral" placeholder="Nhập mã referral...">

                <label for="filter-created">Ngày tạo từ: </label>
                <input type="date" id="filter-created">

                <label for="filter-updated">Ngày cập nhật từ: </label>
                <input type="date" id="filter-updated">
            </div>
            <div class="per-page-options">
                <label for="per-page">Số bản ghi mỗi trang: </label>
                <select id="per-page">
                    <option value="30">30</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <table border="1" style="width:100%; border-collapse: collapse;" id="submission-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Shop Name</th>
                    <th>Address</th>
                    <th>Industry</th>
                    <th>Production</th>
                    <th>Referral Code</th>
                    <th>Person in Charge</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Created Date</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="pagination" id="pagination"></div>
    </div>
    <div class="overlay"></div>
    <div class="popup" id="transfer-popup">
        <h3>Transfer Lead</h3>
        <select id="transfer-user">
            <?php 
            foreach ($all_users as $user) {
                if ($user->ID != $current_user->ID) {
                    $referral_code = get_user_meta($user->ID, 'referral_code', true);
                    echo '<option value="' . $user->ID . '">' . esc_html($referral_code) . '_' . esc_html($user->display_name) . '</option>';
                }
            }
            ?>
        </select>
        <button id="confirm-transfer">Confirm</button>
        <button id="close-popup">Close</button>
        <input type="hidden" id="transfer-post-id" value="">
    </div>
    <div class="popup" id="history-popup">
        <h3>Lead History</h3>
        <div id="history-content"></div>
        <button id="close-history">Close</button>
    </div>
    <script>
    jQuery(document).ready(function($) {
        var formId = <?php echo $form_id; ?>;
        var defaultPerPage = 50;

        function loadSubmissions(page, perPage, filters) {
            $.ajax({
                url: my_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_submissions',
                    form_id: formId,
                    page: page,
                    per_page: perPage,
                    search: filters.search,
                    status: filters.status,
                    person: filters.person,
                    referral: filters.referral,
                    created: filters.created,
                    updated: filters.updated,
                    nonce: my_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#submission-table tbody').html(response.data.html);
                        $('#pagination').html(response.data.pagination);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ' + error);
                }
            });
        }

        // Tải trang đầu tiên
        loadSubmissions(1, defaultPerPage, {
            search: '',
            status: '',
            person: '',
            referral: '',
            created: '',
            updated: ''
        });

        // Thay đổi số bản ghi mỗi trang
        $('#per-page').on('change', function() {
            var perPage = $(this).val();
            var filters = getFilters();
            loadSubmissions(1, perPage, filters);
        });

        // Xử lý phân trang
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            var perPage = $('#per-page').val();
            var filters = getFilters();
            loadSubmissions(page, perPage, filters);
        });

        // Xử lý tìm kiếm và lọc
        $('#search-input, #filter-status, #filter-person, #filter-referral, #filter-created, #filter-updated').on('input change', function() {
            var perPage = $('#per-page').val();
            var filters = getFilters();
            loadSubmissions(1, perPage, filters);
        });

        function getFilters() {
            return {
                search: $('#search-input').val(),
                status: $('#filter-status').val(),
                person: $('#filter-person').val(),
                referral: $('#filter-referral').val(),
                created: $('#filter-created').val(),
                updated: $('#filter-updated').val()
            };
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('form_submissions', 'form_submissions_shortcode');