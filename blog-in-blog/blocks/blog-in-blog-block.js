(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;
    const { __ } = wp.i18n;

    // Block icon
    const blockIcon = el('svg', {
        width: 24,
        height: 24,
        viewBox: '0 0 24 24'
    },
        el('path', {
            d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z'
        })
    );

    registerBlockType('blog-in-blog/posts', {
        title: __('Blog in Blog', 'blog-in-blog'),
        description: __('Display posts from a category, tag, or custom post type.', 'blog-in-blog'),
        icon: blockIcon,
        category: 'blog-in-blog',
        keywords: [
            __('posts', 'blog-in-blog'),
            __('category', 'blog-in-blog'),
            __('blog', 'blog-in-blog'),
            __('list', 'blog-in-blog'),
        ],
        supports: {
            html: false,
            align: ['wide', 'full'],
        },
        attributes: {
            categorySlug: {
                type: 'string',
                default: '',
            },
            tagSlug: {
                type: 'string',
                default: '',
            },
            customPostType: {
                type: 'string',
                default: '',
            },
            num: {
                type: 'number',
                default: 10,
            },
            orderBy: {
                type: 'string',
                default: 'date',
            },
            sort: {
                type: 'string',
                default: 'newest',
            },
            pagination: {
                type: 'string',
                default: 'on',
            },
            template: {
                type: 'string',
                default: '',
            },
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { categorySlug, tagSlug, customPostType, num, orderBy, sort, pagination, template } = attributes;

            // Get data passed from PHP
            const categories = window.bibBlockData ? window.bibBlockData.categories : [];
            const postTypes = window.bibBlockData ? window.bibBlockData.postTypes : [];
            const tags = window.bibBlockData ? window.bibBlockData.tags : [];
            const templates = window.bibBlockData ? window.bibBlockData.templates : [];

            return el(Fragment, {},
                // Inspector Controls (sidebar)
                el(InspectorControls, {},
                    // Content Source Panel
                    el(PanelBody, {
                        title: __('Content Source', 'blog-in-blog'),
                        initialOpen: true
                    },
                        el(SelectControl, {
                            label: __('Category', 'blog-in-blog'),
                            value: categorySlug,
                            options: categories,
                            onChange: function(value) {
                                setAttributes({ categorySlug: value });
                            },
                            help: __('Select a category to display posts from.', 'blog-in-blog')
                        }),
                        el(SelectControl, {
                            label: __('Tag', 'blog-in-blog'),
                            value: tagSlug,
                            options: tags,
                            onChange: function(value) {
                                setAttributes({ tagSlug: value });
                            },
                            help: __('Optionally filter by tag.', 'blog-in-blog')
                        }),
                        el(SelectControl, {
                            label: __('Post Type', 'blog-in-blog'),
                            value: customPostType,
                            options: postTypes,
                            onChange: function(value) {
                                setAttributes({ customPostType: value });
                            },
                            help: __('Select a custom post type if needed.', 'blog-in-blog')
                        })
                    ),

                    // Display Settings Panel
                    el(PanelBody, {
                        title: __('Display Settings', 'blog-in-blog'),
                        initialOpen: true
                    },
                        el(RangeControl, {
                            label: __('Number of Posts', 'blog-in-blog'),
                            value: num,
                            onChange: function(value) {
                                setAttributes({ num: value });
                            },
                            min: 1,
                            max: 50,
                        }),
                        el(SelectControl, {
                            label: __('Order By', 'blog-in-blog'),
                            value: orderBy,
                            options: [
                                { value: 'date', label: __('Date', 'blog-in-blog') },
                                { value: 'title', label: __('Title', 'blog-in-blog') },
                                { value: 'modified', label: __('Last Modified', 'blog-in-blog') },
                                { value: 'rand', label: __('Random', 'blog-in-blog') },
                                { value: 'comment_count', label: __('Comment Count', 'blog-in-blog') },
                            ],
                            onChange: function(value) {
                                setAttributes({ orderBy: value });
                            },
                        }),
                        el(SelectControl, {
                            label: __('Sort Order', 'blog-in-blog'),
                            value: sort,
                            options: [
                                { value: 'newest', label: __('Newest First', 'blog-in-blog') },
                                { value: 'oldest', label: __('Oldest First', 'blog-in-blog') },
                            ],
                            onChange: function(value) {
                                setAttributes({ sort: value });
                            },
                        }),
                        el(ToggleControl, {
                            label: __('Show Pagination', 'blog-in-blog'),
                            checked: pagination === 'on',
                            onChange: function(value) {
                                setAttributes({ pagination: value ? 'on' : 'off' });
                            },
                        })
                    ),

                    // Template Panel
                    el(PanelBody, {
                        title: __('Template', 'blog-in-blog'),
                        initialOpen: false
                    },
                        el(SelectControl, {
                            label: __('Post Template', 'blog-in-blog'),
                            value: template,
                            options: templates,
                            onChange: function(value) {
                                setAttributes({ template: value });
                            },
                            help: __('Select a custom template or use the default.', 'blog-in-blog')
                        })
                    )
                ),

                // Block Preview
                el('div', { className: props.className },
                    el(ServerSideRender, {
                        block: 'blog-in-blog/posts',
                        attributes: attributes,
                        EmptyResponsePlaceholder: function() {
                            return el('div', {
                                className: 'bib-block-placeholder'
                            },
                                el('span', {
                                    className: 'dashicons dashicons-admin-post',
                                    style: { fontSize: '36px', width: '36px', height: '36px' }
                                }),
                                el('p', {}, __('Blog in Blog', 'blog-in-blog')),
                                el('p', { className: 'bib-block-help' },
                                    __('Select a category, tag, or post type from the block settings to display posts.', 'blog-in-blog')
                                )
                            );
                        },
                    })
                )
            );
        },

        save: function() {
            // Server-side rendering - return null
            return null;
        },
    });
})(window.wp);
