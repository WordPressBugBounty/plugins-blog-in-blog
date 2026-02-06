<?php
/**
 * Blog in Blog - Gutenberg Block
 *
 * Registers a Gutenberg block that provides a visual interface
 * for the blog-in-blog shortcode functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the Blog in Blog block
 */
function bib_register_block() {
    // Skip if Gutenberg is not available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register block editor script
    wp_register_script(
        'bib-block-editor',
        plugins_url('blog-in-blog-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-server-side-render', 'wp-data'),
        BIB_VERSION,
        true
    );

    // Get categories for the block
    $categories = get_categories(array('hide_empty' => false));
    $category_options = array(
        array('value' => '', 'label' => __('-- Select Category --', 'blog-in-blog'))
    );
    foreach ($categories as $cat) {
        $category_options[] = array(
            'value' => $cat->slug,
            'label' => $cat->name . ' (' . $cat->count . ' posts)'
        );
    }

    // Get custom post types
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_type_options = array(
        array('value' => '', 'label' => __('-- Default (Posts) --', 'blog-in-blog'))
    );
    foreach ($post_types as $pt) {
        if ($pt->name !== 'attachment') {
            $post_type_options[] = array(
                'value' => $pt->name,
                'label' => $pt->label
            );
        }
    }

    // Get tags
    $tags = get_tags(array('hide_empty' => false));
    $tag_options = array(
        array('value' => '', 'label' => __('-- No Tag Filter --', 'blog-in-blog'))
    );
    foreach ($tags as $tag) {
        $tag_options[] = array(
            'value' => $tag->slug,
            'label' => $tag->name . ' (' . $tag->count . ' posts)'
        );
    }

    // Get templates
    $templates = get_option('bib_templates');
    $template_options = array(
        array('value' => '', 'label' => __('Default Template', 'blog-in-blog'))
    );
    if (is_array($templates)) {
        foreach ($templates as $tpl) {
            if (is_array($tpl) && !empty($tpl['template_name'])) {
                $template_options[] = array(
                    'value' => $tpl['template_name'],
                    'label' => $tpl['template_name']
                );
            }
        }
    }

    // Pass data to JavaScript
    wp_localize_script('bib-block-editor', 'bibBlockData', array(
        'categories' => $category_options,
        'postTypes' => $post_type_options,
        'tags' => $tag_options,
        'templates' => $template_options,
    ));

    // Register block editor styles
    wp_register_style(
        'bib-block-editor-style',
        plugins_url('blog-in-blog-block.css', __FILE__),
        array('wp-edit-blocks'),
        BIB_VERSION
    );

    // Register the block
    register_block_type('blog-in-blog/posts', array(
        'editor_script' => 'bib-block-editor',
        'editor_style' => 'bib-block-editor-style',
        'render_callback' => 'bib_block_render',
        'supports' => array(
            'align' => array('wide', 'full'),
        ),
        'attributes' => array(
            'align' => array(
                'type' => 'string',
                'default' => '',
            ),
            'categorySlug' => array(
                'type' => 'string',
                'default' => '',
            ),
            'tagSlug' => array(
                'type' => 'string',
                'default' => '',
            ),
            'customPostType' => array(
                'type' => 'string',
                'default' => '',
            ),
            'num' => array(
                'type' => 'number',
                'default' => 10,
            ),
            'orderBy' => array(
                'type' => 'string',
                'default' => 'date',
            ),
            'sort' => array(
                'type' => 'string',
                'default' => 'newest',
            ),
            'pagination' => array(
                'type' => 'string',
                'default' => 'on',
            ),
            'template' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'bib_register_block');

/**
 * Server-side render callback for the block
 */
function bib_block_render($attributes) {
    // Build shortcode attributes
    $shortcode_atts = array();

    if (!empty($attributes['categorySlug'])) {
        $shortcode_atts[] = 'category_slug="' . esc_attr($attributes['categorySlug']) . '"';
    }
    if (!empty($attributes['tagSlug'])) {
        $shortcode_atts[] = 'tag_slug="' . esc_attr($attributes['tagSlug']) . '"';
    }
    if (!empty($attributes['customPostType'])) {
        $shortcode_atts[] = 'custom_post_type="' . esc_attr($attributes['customPostType']) . '"';
    }
    if (!empty($attributes['num'])) {
        $shortcode_atts[] = 'num="' . intval($attributes['num']) . '"';
    }
    if (!empty($attributes['orderBy'])) {
        $shortcode_atts[] = 'order_by="' . esc_attr($attributes['orderBy']) . '"';
    }
    if (!empty($attributes['sort'])) {
        $shortcode_atts[] = 'sort="' . esc_attr($attributes['sort']) . '"';
    }
    if (!empty($attributes['pagination'])) {
        $shortcode_atts[] = 'pagination="' . esc_attr($attributes['pagination']) . '"';
    }
    if (!empty($attributes['template'])) {
        $shortcode_atts[] = 'template="' . esc_attr($attributes['template']) . '"';
    }

    $shortcode = '[blog_in_blog ' . implode(' ', $shortcode_atts) . ']';

    // Get shortcode output
    $output = do_shortcode($shortcode);

    // Apply alignment class wrapper if set
    if (!empty($attributes['align'])) {
        $align_class = 'align' . esc_attr($attributes['align']);
        $output = '<div class="wp-block-blog-in-blog-posts ' . $align_class . '">' . $output . '</div>';
    }

    return $output;
}

/**
 * Add block category for Blog in Blog
 */
function bib_block_category($categories) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'blog-in-blog',
                'title' => __('Blog in Blog', 'blog-in-blog'),
                'icon' => 'admin-post',
            ),
        )
    );
}
add_filter('block_categories_all', 'bib_block_category', 10, 1);
