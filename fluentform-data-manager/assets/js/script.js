jQuery(document).ready(function($) {
    // Take Charge
    $(document).on('click', '.take-charge', function() {
        var postId = $(this).data('post-id');
        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'take_charge',
                post_id: postId,
                nonce: my_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ' + error);
                alert('AJAX request failed');
            }
        });
    });

    // Update Status
    $(document).on('change', '.status-update', function() {
        var postId = $(this).data('post-id');
        var newStatus = $(this).val();
        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_status',
                post_id: postId,
                status: newStatus,
                nonce: my_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ' + error);
                alert('AJAX request failed');
            }
        });
    });

    // Transfer Lead
    $(document).on('click', '.transfer-lead', function() {
        var postId = $(this).data('post-id');
        $('#transfer-post-id').val(postId);
        $('.overlay, #transfer-popup').show();
    });

    $('#confirm-transfer').on('click', function() {
        var postId = $('#transfer-post-id').val();
        var newUserId = $('#transfer-user').val();
        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'transfer_lead',
                post_id: postId,
                new_user_id: newUserId,
                nonce: my_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ' + error);
                alert('AJAX request failed');
            }
        });
    });

    $('#close-popup').on('click', function() {
        $('.overlay, #transfer-popup').hide();
    });

    // Reset Lead
    $(document).on('click', '.reset-lead', function() {
        var postId = $(this).data('post-id');
        if (confirm('Are you sure you want to reset this lead?')) {
            $.ajax({
                url: my_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'reset_lead',
                    post_id: postId,
                    nonce: my_ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ' + error);
                    alert('AJAX request failed');
                }
            });
        }
    });

    // History Popup
    $(document).on('click', '.history', function() {
        var postId = $(this).data('post-id');
        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_lead_history',
                post_id: postId,
                nonce: my_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#history-content').html(response.data.join('<br>'));
                    $('.overlay, #history-popup').show();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ' + error);
                alert('AJAX request failed');
            }
        });
    });

    $('#close-history').on('click', function() {
        $('.overlay, #history-popup').hide();
    });
});