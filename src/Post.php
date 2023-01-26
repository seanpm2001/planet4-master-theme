<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Features\PostPageCategoryLinks;
use Timber\Post as TimberPost;
use Timber\Term as TimberTerm;
use WP_Block;
use WP_Error;
use WP_Query;
use WP_Term;

/**
 * Add planet4 specific functionality.
 */
class Post extends TimberPost
{
    /**
     * Issues navigation
     *
     * @var array|null $issues_nav_data
     */
    protected ?array $issues_nav_data = null;

    /**
     * Content type
     *
     */
    protected string $content_type;

    /**
     * Page types
     *
     * @var TimberTerm[] $page_types
     */
    protected array $page_types;

    /**
     * Author
     *
     */
    protected User $author;

    /**
     * Associative array with the values to be passed to GTM Data Layer.
     *
     * @var array $datalayer
     */
    protected array $data_layer;

    /**
     * Post constructor.
     *
     * @param mixed $pid The post id. If left null it will try to figure out the current post id based on being inside The_Loop.
     */
    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->set_page_types();
        $this->set_author();
    }

    /**
     * Sets the GTM Data Layer values of current P4 Post.
     */
    public function set_data_layer(): void
    {
        if (is_front_page()) {
            $this->data_layer['page_category'] = 'Homepage';
        } elseif ($this->is_act_page()) {
            $this->data_layer['page_category'] = 'Act';
        } elseif ($this->is_explore_page()) {
            $this->data_layer['page_category'] = 'Explore';
        } elseif ($this->is_take_action_page()) {
            $this->data_layer['page_category'] = 'Take Action';
        } elseif ($this->is_issue_page()) {
            $this->data_layer['page_category'] = 'Issue Page';
        } elseif ($this->is_campaign_page()) {
            $this->data_layer['page_category'] = 'Campaign Page';
        } elseif (is_tag()) {
            $this->data_layer['page_category'] = 'Tag Page';
        } else {
            $this->data_layer['page_category'] = 'Default Page';
        }
    }

    /**
     * Get the array for the GTM Data Layer.
     */
    public function get_data_layer()
    {
        return $this->data_layer;
    }

    /**
     * Checks if post is the Act page.
     *
     */
    public function is_act_page(): bool
    {
        $act_page_id = planet4_get_option('act_page');

        return absint($act_page_id) === $this->id;
    }

    /**
     * Checks if post is the Explore page.
     *
     */
    public function is_explore_page(): bool
    {
        $explore_page_id = planet4_get_option('explore_page');

        return absint($explore_page_id) === $this->id;
    }

    /**
     * Checks if post is a Take Action page (child of act page).
     *
     */
    public function is_take_action_page(): bool
    {
        $act_page_id = planet4_get_option('act_page');
        $pages = [];

        if (0 !== absint($act_page_id)) {
            $take_action_pages_args = [
                'post_type' => 'page',
                'post_parent' => $act_page_id,
                'numberposts' => - 1,
                'fields' => 'ids',
                'suppress_filters' => false,
            ];

            $pages = get_posts($take_action_pages_args);
        }

        return in_array($this->id, $pages, true);
    }

    /**
     * Checks if post is an Issue page (child of explore page).
     *
     */
    public function is_issue_page(): bool
    {
        $explore_page_id = planet4_get_option('explore_page');
        $pages = [];

        if (0 !== absint($explore_page_id)) {
            $issue_pages_args = [
                'post_type' => 'page',
                'post_parent' => $explore_page_id,
                'numberposts' => - 1,
                'fields' => 'ids',
                'suppress_filters' => false,
            ];

            $pages = get_posts($issue_pages_args);
        }

        return in_array($this->id, $pages, true);
    }

    /**
     * Checks if post is Campaign page.
     *
     */
    public function is_campaign_page(): bool
    {
        return PostCampaign::POST_TYPE === $this->post_type;
    }

    /**
     * Loads in context information on the navigation links for Issue pages relevant to current Post's categories.
     */
    public function set_issues_links(): void
    {
        // Retrieve P4 settings in order to check that we add only categories that are children of the Issues category.
        $options = get_option('planet4_options');
        $explore_page_id = $options['explore_page'] ?? '';
        $categories = get_the_category($this->ID);

        if (PostPageCategoryLinks::is_active()) {
            $this->issues_nav_data = array_map(
                fn($category) => [
                    'name' => $category->name,
                    'link' => get_term_link($category),
                ],
                array_filter($categories, fn($category) => 'uncategorised' !== $category->slug)
            );
            return;
        }

        // Handle navigation links.
        if ($categories) {
            $categories_ids = [];

            foreach ($categories as $category) {
                $categories_ids[] = $category->term_id;
            }
            // Get the Issue pages that are relevant to the Categories of the current Post.
            if ($categories_ids && $explore_page_id) {
                $args = [
                    'post_parent' => $explore_page_id,
                    'post_type' => 'page',
                    'post_status' => 'publish',
                ];

                $args['category__in'] = $categories_ids;
                $issues = ( new WP_Query($args) )->posts;

                if ($issues) {
                    foreach ($issues as $issue) {
                        if ($issue && $this->post_parent !== (int) $explore_page_id) {
                            $this->issues_nav_data[] = [
                                'name' => $issue->post_title,
                                'link' => get_permalink($issue),
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieves the accounts for each social media item found within the footer social menu.
     *
     * @param array $social_menu Array of a post objects for each menu item.
     *
     * @return array Associative array with the social media accounts.
     */
    public function get_social_accounts(array $social_menu): array
    {
        return self::filter_social_accounts($social_menu);
    }

    /**
     * Get post's planet4 custom taxonomy terms.
     *
     * @return WP_Term[]
     */
    public function get_custom_terms(): array
    {
        $terms = get_the_terms($this->id, CustomTaxonomy::TAXONOMY);
        if (false !== $terms && ! $terms instanceof WP_Error) {
            return $terms;
        }

        return [];
    }

    /**
     * Sets the page types for this Post.
     */
    public function set_page_types(): void
    {
        $taxonomies = $this->get_terms(CustomTaxonomy::TAXONOMY);

        if ($taxonomies && ! is_wp_error($taxonomies)) {
            $this->page_types = $taxonomies;
        }
    }

    /**
     * Gets the page types of this Post.
     */
    public function get_page_types()
    {
        return $this->page_types;
    }

    /**
     * Sets post/page custom planet4 type.
     * ACTION, DOCUMENT, PAGE, POST
     */
    public function set_content_type(): void
    {
        switch ($this->post_type) {
            case 'page':
                if ($this->is_take_action_page()) {
                    $this->content_type = __('ACTION', 'planet4-master-theme');
                } else {
                    $this->content_type = __('PAGE', 'planet4-master-theme');
                }
                break;
            case 'attachment':
                $this->content_type = __('DOCUMENT', 'planet4-master-theme');
                break;
            default:
                $this->content_type = __('POST', 'planet4-master-theme');
        }
    }

    /**
     * Get post/page custom planet4 type.
     * ACTION, DOCUMENT, PAGE, POST
     *
     */
    public function get_content_type(): string
    {
        return $this->content_type;
    }

    /**
     * Get value for open graph title meta.
     *
     */
    public function get_og_title(): string
    {
        return get_post_meta($this->id, 'p4_og_title', true);
    }

    /**
     * Get value for open graph description meta.
     *
     */
    public function get_og_description(): string
    {
        $og_desc = get_post_meta($this->id, 'p4_og_description', true);
        if ('' === $og_desc) {
            return $this->post_excerpt;
        }

        return wp_strip_all_tags($og_desc);
    }

    /**
     * Get image data for open graph image meta.
     *
     * @return array
     */
    public function get_og_image(): array
    {
        $meta = get_post_meta($this->id);
        $image_id = null;
        $image_metas = [ 'p4_og_image_id', '_thumbnail_id', 'background_image_id' ];
        foreach ($image_metas as $image_meta) {
            if (isset($meta[ $image_meta ][0])) {
                $image_id = $meta[ $image_meta ][0];
                break;
            }
        }

        if (null !== $image_id) {
            $image_data = wp_get_attachment_image_src($image_id, 'full');
            $og_image = [];
            if ($image_data) {
                $og_image['url'] = $image_data[0];
                $og_image['width'] = $image_data[1];
                $og_image['height'] = $image_data[2];
            }

            return $og_image;
        }

        return [];
    }

    /**
     * Get values for share buttons content.
     *
     * @return string[]
     */
    public function share_meta(): array
    {
        $og_title = get_post_meta($this->id, 'p4_og_title', true);
        $og_description = get_post_meta($this->id, 'p4_og_description', true);
        $link = get_permalink($this->id);

        if (( '' === $og_title ) && '' !== $this->post_title) {
            $og_title = $this->post_title;
        }

        return [
            'title' => $og_title,
            'description' => wp_strip_all_tags($og_description),
            'link' => $link,
        ];
    }

    /**
     * Get post's author override status.
     *
     */
    public function get_author_override(): bool
    {
        $author_override = get_post_meta($this->id, 'p4_author_override', true);
        if ($author_override) {
            return true;
        }

        return false;
    }

    /**
     * Sets the User author of this Post.
     */
    public function set_author(): void
    {
        $author_override = get_post_meta($this->id, 'p4_author_override', true);
        if ('' !== $author_override) {
            $this->author = new User(false, $author_override); // Create fake User.
        } else {
            $this->author = new User((int) $this->post_author);
        }
    }

    /**
     * Gets the User author of this Post.
     *
     */
    public function get_author(): User
    {
        return $this->author;
    }

    /**
     * Filter the accounts for each social media item found within the footer social menu.
     *
     * @param array $social_menu Array of a post objects for each menu item.
     *
     * @return array Associative array with the social media accounts.
     */
    public static function filter_social_accounts(array $social_menu): array
    {
        $social_accounts = [];
        if (isset($social_menu) && is_iterable($social_menu)) {
            $brands = [
                'facebook',
                'twitter',
                'youtube',
                'instagram',
            ];
            foreach ($social_menu as $social_menu_item) {
                $url_parts = explode('/', rtrim($social_menu_item->url, '/'));
                foreach ($brands as $brand) {
                    if (false !== strpos($social_menu_item->url, $brand)) {
                        $social_accounts[ $brand ] = count($url_parts) > 0 ? $url_parts[ count($url_parts) - 1 ] : '';
                    }
                }
            }
        }

        return $social_accounts;
    }

    /**
     * Validate password protected form.
     *
     * @return bool Return password protected form validation.
     */
    public function is_password_valid(): bool
    {

        $is_valid = true;

        // Check if page url has a unique id(custom hash), appended with it, if not add one.
        $custom_hash = filter_input(INPUT_GET, 'ch', FILTER_SANITIZE_STRING);
        if (! $custom_hash) {
            wp_safe_redirect(add_query_arg('ch', password_hash(uniqid('', true), PASSWORD_DEFAULT), get_permalink()));
            exit();
        }

        /**
         * Password protected form validation:
         * The latest entered password is stored as a secure hash in a cookie named 'wp-postpass_' . COOKIEHASH.
         * When the password form is called, that cookie has been validated already by WordPress.
         */
        if (isset($_COOKIE[ 'wp-postpass_' . COOKIEHASH ])) {
            $old_cookie = get_transient('p4-postpass_' . $custom_hash);
            $current_cookie = wp_unslash($_COOKIE[ 'wp-postpass_' . COOKIEHASH ]);
            set_transient('p4-postpass_' . $custom_hash, $current_cookie, $expiration = 60 * 5); // Transient cache expires in 5 mins.
            if (false !== $old_cookie && $current_cookie !== $old_cookie) {
                $is_valid = false;
            }
        }

        return $is_valid;
    }

    /**
     * Calculate post reading time.
     * Return 1 at minimum, to not get a "0 min read".
     *
     * @return int|null Reading time in minutes, null if option not activated on post type.
     */
    public function reading_time(): ?int
    {
        $terms_rt_option = array_map(
            fn ($t) => get_term_meta($t->term_id, CustomTaxonomy::READING_TIME_FIELD, true),
            $this->get_custom_terms()
        );
        $use_reading_time = ! empty(array_filter($terms_rt_option));
        if (! $use_reading_time) {
            return null;
        }

        $cache_key = $this->id . '~' . $this->post_modified;

        $from_cache = wp_cache_get($cache_key);
        if ($from_cache) {
            return (int) $from_cache;
        }

        $locale = $this->get_locale();
        $wpm = planet4_get_option('reading_time_wpm', Post\ReadingTimeCalculator::DEFAULT_WPM);
        $rt = new Post\ReadingTimeCalculator($locale, $wpm);
        $time = (int) max(1, round($rt->get_time($this->content) / 60));
        // Cache for 1 day. This could be infinitely long as the key checks the modified date.
        wp_cache_add($cache_key, $time, null, 3600 * 24);

        return $time;
    }

    /**
     * Server side render for the reading time block.
     *
     * @param array    $attributes Block attributes, unused.
     * @param string   $content Content which apparently no core block uses.
     * @param WP_Block $block With all block properties.
     *
     * @return string Formatted reading time.
     */
    public static function reading_time_block(array $attributes, string $content, WP_Block $block): string
    {
        $time = ( new self($block->context['postId'] ?? null) )->reading_time();
        return $time ? '<span class="article-list-item-readtime">' . $time . ' min read</span>' : '';
    }

    /**
     * Return Post locale.
     *
     * @return string The locale.
     */
    public function get_locale(): string
    {
        $site_locale = get_locale();

        $site_is_multilingual = is_plugin_active('sitepress-multilingual-cms/sitepress.php');
        if ($site_is_multilingual) {
            $lang_details = apply_filters('wpml_post_language_details', null, $this->ID);
            $post_locale = $lang_details['locale'] ?? null;
        }

        return $post_locale ?? $site_locale;
    }
}
