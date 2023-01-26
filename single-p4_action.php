<?php

/**
 * The Template for displaying all action pages
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * (Note: This file is a copy of page.php to use the same template for action pages.)
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\Context;
use P4\MasterTheme\Post;
use Timber\Timber;

$context = Timber::get_context();
$post = new Post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$page_meta_data = get_post_meta($post->ID);
$page_meta_data = array_map('reset', $page_meta_data);

// Set GTM Data Layer values.
$post->set_data_layer();
$data_layer = $post->get_data_layer();

Context::set_header($context, $page_meta_data, $post->title);
Context::set_background_image($context);
Context::set_og_meta_fields($context, $post);
Context::set_campaign_datalayer($context, $page_meta_data);
Context::set_utm_params($context, $post);
Context::set_custom_styles($context, $page_meta_data);

$context['post'] = $post;
$context['social_accounts'] = $post->get_social_accounts($context['footer_social_menu'] ?: []);
$context['page_category'] = $data_layer['page_category'];
$context['post_tags'] = implode(', ', $post->tags());
$context['custom_body_classes'] = 'brown-bg ';

if (post_password_required($post->ID)) {
    // Password protected form validation.
    $context['is_password_valid'] = $post->is_password_valid();

    // Hide the page title from links to the extra feeds.
    remove_action('wp_head', 'feed_links_extra', 3);

    $context['login_url'] = wp_login_url();

    Timber::render('single-page.twig', $context);
} else {
    Timber::render([ 'page-' . $post->post_name . '.twig', 'page.twig' ], $context);
}
