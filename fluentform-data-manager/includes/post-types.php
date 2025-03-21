<?php
if (!defined('ABSPATH')) {
    exit;
}

function register_form_submission_post_type() {
    $args = array(
        'public' => false,
        'show_ui' => true,
        'labels' => array(
            'name' => 'Form Submissions',
            'singular_name' => 'Form Submission',
        ),
        'supports' => array('title'),
        'capability_type' => 'form_submission',
        'map_meta_cap' => true,
    );
    register_post_type('form_submission', $args);
}
add_action('init', 'register_form_submission_post_type');