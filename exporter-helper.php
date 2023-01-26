<?php

/**
 * Campaign Exporter Helper Functions
 *
 * @package P4MT
 */

/**
 * Parse the post content and return all attachment ids used in blocks.
 *
 * @param string $content The content to parse.
 * @return array All attachments used in the blocks.
 */
function get_attachments_used_in_content(string $content): array
{
    $blocks = parse_blocks($content);

    $attachment_ids = [];

    foreach ($blocks as $block) {
        // Fetch the attachement id/s from block fields.
        switch ($block['blockName']) {
            case 'planet4-blocks/enform':
                $attachment_ids[] = $block['attrs']['background'] ?? '';
                break;

            case 'core/media-text':
                $attachment_ids[] = $block['attrs']['mediaId'] ?? '';
                $attachment_ids[] = $block['attrs']['mediaLink'] ?? '';
                $attachment_ids[] = $block['attrs']['mediaType'] ?? '';
                break;

            case 'core/image':
            case 'planet4-blocks/happypoint':
                $attachment_ids[] = $block['attrs']['id'] ?? '';
                break;

            case 'planet4-blocks/media-video':
                $attachment_ids[] = $block['attrs']['video_poster_img'] ?? '';
                break;

            case 'planet4-blocks/gallery':
                if (isset($block['attrs']['multiple_image'])) {
                    $multiple_images = explode(',', $block['attrs']['multiple_image']);
                    $attachment_ids = array_merge($attachment_ids, $multiple_images);
                }
                break;

            case 'planet4-blocks/carousel-header':
                if (isset($block['attrs']['slides'])) {
                    foreach ($block['attrs']['slides'] as $slide) {
                        $attachment_ids[] = $slide['image'];
                    }
                }
                break;

            case 'planet4-blocks/split-two-columns':
                $attachment_ids[] = $block['attrs']['issue_image'] ?? '';
                $attachment_ids[] = $block['attrs']['tag_image'] ?? '';
                break;

            case 'planet4-blocks/columns':
                if (isset($block['attrs']['columns'])) {
                    foreach ($block['attrs']['columns'] as $column) {
                        $attachment_ids[] = $column['attachment'] ?? '';
                    }
                }
                break;

            case 'planet4-blocks/social-media-cards':
                if (isset($block['attrs']['cards'])) {
                    foreach ($block['attrs']['cards'] as $card) {
                        $attachment_ids[] = $card['image_id'];
                    }
                }
                break;

            case 'planet4-blocks/take-action-boxout':
                $attachment_ids[] = $block['attrs']['background_image'] ?? '';
                break;
        }
    }

    return $attachment_ids;
}

/**
 * Returns all attachment ids from campaign post content.
 *
 * @param array $post_ids Post IDs.
 * @return array  $post_ids Post IDs.
 */
function get_campaign_attachments($post_ids)
{

    global $wpdb;

    if (empty($post_ids)) {
        return [];
    }

    $sql = '
		SELECT id
		FROM %1$s
		WHERE post_type = \'attachment\'
		AND post_parent IN (' . generate_list_placeholders($post_ids, 2) . ')';

    $prepared_sql = $wpdb->prepare(
        $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        array_merge([ $wpdb->posts ], $post_ids)
    );
    $results = $wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $attachment_ids = array_map(
        function ($result) {
            return $result->id;
        },
        $results
    );

    /**
     * Post thumbnails
     */
    $sql = '
		SELECT meta_value
		FROM %1$s
		WHERE ( meta_key = \'_thumbnail_id\' OR meta_key = \'background_image_id\' )
		AND post_id IN(' . generate_list_placeholders($post_ids, 2) . ')';

    $prepared_sql = $wpdb->prepare(
        $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        array_merge([ $wpdb->postmeta ], $post_ids)
    );
    $results = $wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    $attachment_ids = array_merge(
        $attachment_ids,
        array_map(
            function ($result) {
                return $result->meta_value;
            },
            $results
        )
    );

    $sql = '
			SELECT post_content
			FROM %1$s
			WHERE ID IN(' . generate_list_placeholders($post_ids, 2) . ')
			AND post_content REGEXP \'((wp-image-|wp-att-)[0-9][0-9]*)|gallery_block_style|wp\:planet4\-blocks|href=|src=\'';

    $prepared_sql = $wpdb->prepare($sql, array_merge([ $wpdb->posts ], $post_ids)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $results = $wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    foreach ((array) $results as $text) {
        $text = $text->post_content;
        $attachment_ids = array_merge($attachment_ids, get_attachments_used_in_content($text));
    }

    $attachment_ids = array_unique($attachment_ids);
    sort($attachment_ids);

    // The post ids are reordered as sort all attachment ids first and then append the post id to array.(Added for simplification of import process).
    $attachment_ids = array_diff($attachment_ids, $post_ids);
    $post_ids = array_merge($attachment_ids, $post_ids);

    return $post_ids;
}

/**
 * Wrap strings in nested CDATA tags.
 *
 * @param string $str String to replace.
 */
function p4_px_single_post_cdata($str)
{
    if (seems_utf8($str) === false) {
        $str = utf8_encode($str);
    }
    $str = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $str) . ']]>';

    return $str;
}

/**
 * Get the site url.
 */
function p4_px_single_post_site_url()
{
    if (is_multisite()) {
        return network_home_url();
    } else {
        return get_bloginfo_rss('url');
    }
}

/**
 * Get the Campaign authors.
 *
 * @param array $post_ids Tag object.
 */
function p4_px_single_post_authors_list($post_ids)
{
    global $wpdb;

    $post_ids = array_map('intval', $post_ids); // santize the post_ids manually.
    $post_ids = array_filter($post_ids); // strip ones that didn't validate.

    $authors = [];

    $sql = 'SELECT DISTINCT post_author
			FROM %1$s
			WHERE ID IN(' . generate_list_placeholders($post_ids, 2) . ') AND post_status != \'auto-draft\'';

    $prepared_sql = $wpdb->prepare($sql, array_merge([ $wpdb->posts ], $post_ids)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $results = $wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    foreach ((array) $results as $result) {
        $authors[] = get_userdata($result->post_author);
    }

    $authors = array_filter($authors);

	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
    foreach ($authors as $author) {
        echo "\t<wp:author>";
        echo '<wp:author_id>' . $author->ID . '</wp:author_id>';
        echo '<wp:author_login>' . $author->user_login . '</wp:author_login>';
        echo '<wp:author_email>' . $author->user_email . '</wp:author_email>';
        echo '<wp:author_display_name>' . p4_px_single_post_cdata($author->display_name) . '</wp:author_display_name>';
        echo '<wp:author_first_name>' . p4_px_single_post_cdata($author->user_firstname) . '</wp:author_first_name>';
        echo '<wp:author_last_name>' . p4_px_single_post_cdata($author->user_lastname) . '</wp:author_last_name>';
        echo "</wp:author>\n";
    }
}
