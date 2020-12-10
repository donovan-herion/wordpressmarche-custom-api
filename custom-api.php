<?php

/**
 * Plugin Name: Custom API
 * Description: This is a customizable api!
 * Version: 1.0
 */


function ca_posts()
{
    $args = [
        'numberposts' => 99999,
        'post_type' => 'post'
    ];

    $posts = get_posts($args);

    $data = [];
    $i = 0;

    foreach ($posts as $post) {
        $data[$i]['id'] = $post->ID;
        $data[$i]['title'] = $post->post_title;
        $data[$i]['content'] = $post->post_content;
        $data[$i]['slug'] = $post->post_name;
        $data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url($post->ID, 'thumbnail');
        $data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url($post->ID, 'medium');
        $data[$i]['featured_image']['large'] = get_the_post_thumbnail_url($post->ID, 'large');
        $i++;
    }

    // $data = "custom endpoint";
    return $data;
}


add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'posts', [
        'methods' => 'GET',
        'callback' => 'ca_posts',
    ]);
});
