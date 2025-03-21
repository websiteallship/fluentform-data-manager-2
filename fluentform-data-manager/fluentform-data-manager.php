<?php
/*
Plugin Name: FluentForm Data Manager
Description: A plugin to manage FluentForm submissions with custom post type, frontend table, and user roles.
Version: 1.8
Author: Your Name
*/

// Đảm bảo plugin không chạy trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Include các file chức năng
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhook-handler.php';
require_once plugin_dir_path(__FILE__) . 'admin/metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'frontend/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-take-charge.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-update-status.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-transfer-lead.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-reset-lead.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-history.php';
require_once plugin_dir_path(__FILE__) . 'ajax/ajax-load-submissions.php';

// Đăng ký assets
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('fluentform-data-manager-css', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.8');
    wp_enqueue_script('fluentform-data-manager-js', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.8', true);
    wp_localize_script('fluentform-data-manager-js', 'my_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my-ajax-nonce'),
    ));
});

// Đăng ký REST API endpoint
add_action('rest_api_init', 'ffdm_register_webhook_endpoint');
if (!function_exists('ffdm_register_webhook_endpoint')) {
    function ffdm_register_webhook_endpoint() {
        error_log('FFDM: Registering webhook endpoint');
        register_rest_route('ffdm/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => 'handle_webhook_submission',
            'permission_callback' => '__return_true',
        ));

        // Log tất cả route sau khi đăng ký
        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init', $wp_rest_server);
        $routes = $wp_rest_server->get_routes();
        error_log('FFDM: Registered Routes - ' . print_r($routes, true));
    }
}