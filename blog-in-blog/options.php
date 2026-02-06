<?php

/*  Copyright 2009  Tim Hodson  (email : tim@timhodson.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('Parsedown')){
    include_once 'plugin-meta/plugin-meta.php';
}

global $blog_in_blog_opts; 


function bib_init_opts() {
	global $blog_in_blog_opts;

	bib_set_option_default('bib_show_dots_after', 5 );
	bib_set_option_default('bib_text_delim', "," );
	bib_set_option_default('bib_text_page',  __('Page', 'blog-in-blog') );
	bib_set_option_default('bib_text_previous',  '&laquo;' );
	bib_set_option_default('bib_text_next',  '&raquo;' );
	bib_set_option_default('bib_style_selected',  'font-weight:bold;' );
	bib_set_option_default('bib_style_not_selected',  'color:grey;' );
	bib_set_option_default('bib_post_template',  'bib_post_template.tpl');
	bib_set_option_default('bib_templates',  '');
	bib_set_option_default('bib_more_link_text',  __('more', 'blog-in-blog').' &raquo;' );
	bib_set_option_default('bib_avatar_size',  96);
	bib_set_option_default('bib_hide_category_from_rss',  0);
	bib_set_option_default('bib_meta_keys',  0);
	bib_set_option_default('bib_debug',  0);
	bib_set_option_default('bib_no_collapse',  0);
	bib_set_option_default('bib_last_tab',  '');
//	bib_set_option_default('bib_single',  1);

        if( ! get_option('bib_html')
                && file_exists( BIB_WP_UPLOADS_DIR."/".get_option('bib_post_template'))
                ){
            //echo "uploads dir for template";
            bib_set_option_default('bib_html', file_get_contents(BIB_WP_UPLOADS_DIR."/".get_option('bib_post_template')) );
        }  elseif ( ! get_option('bib_html') 
                && file_exists(WP_PLUGIN_DIR."/blog-in-blog/".get_option('bib_post_template'))
                ) {
            //echo "plugin dir for template";
            bib_set_option_default('bib_html', file_get_contents(WP_PLUGIN_DIR."/blog-in-blog/".get_option('bib_post_template')) );
        } else {
            
            $html = "<!-- blog-in-blog Version: %bib_version% -->" ;
            $html .= "<div class=\"post\">" ;
            $html .= "<!-- Post Headline -->" ;
            $html .= "<div class=\"post-headline\">" ;
            $html .= "<div style=\"float:left; margin:7px\"> %post_author_avatar% </div>" ;
            $html .= "<h2>" ;
            $html .= "<a title=\"Permanent Link to %post_title%\" rel=\"bookmark\" href=\"%post_permalink%\" id=\"%post_id%\" >%post_title%</a>" ;
            $html .= "</h2>" ;
            $html .= "<small>%post_author% : %post_date% %post_time% : %post_categories%</small>" ;

            $html .= "</div>" ;
            $html .= "<!-- Post Body Copy -->" ;

            $html .= "<div class=\"post-bodycopy clearfix\">%post_content%</div>" ;
            $html .= "<div class=\"post-comments\">%post_comments%</div>	" ;

            bib_set_option_default('bib_html', $html );
            bib_set_option_default('bib_post_template', '' ); // ???
            
        }
       

	// always using the default Wordpress formats
	$blog_in_blog_opts['date_format'] = get_option('date_format');
	$blog_in_blog_opts['time_format'] = get_option('time_format');
}

/**
 * call bib_init_opts here to get default options set
 *  even if not using the plugin right away.
 * Makes sure that this is up-to-date for the admin page
 *
 */
// bib_init_opts();
add_action('init', 'bib_init_opts');


function bib_set_option_default($option_name, $default) {
	global $blog_in_blog_opts;
	
	$blog_in_blog_opts[$option_name] = get_option($option_name);
	if ($blog_in_blog_opts[$option_name] == '' ) {
		$blog_in_blog_opts[$option_name] = $default;

		update_option($option_name, $blog_in_blog_opts[$option_name]);
	}

        $debug_value = is_array($blog_in_blog_opts[$option_name]) ? print_r($blog_in_blog_opts[$option_name], true) : $blog_in_blog_opts[$option_name];
        bib_write_debug(__FUNCTION__, "OPTION DEFAULT = {$option_name} == {$debug_value}");
	
}

/**
 * =========================================================
 * Add options to admin menu
 */

add_option('bib_hide_category'); //??? why is this here?


add_action('admin_menu', 'blog_in_blog_menu', 99); // ok for 2.9

function blog_in_blog_menu() {
	add_options_page('Blog-in-Blog Options', 'Blog-in-Blog', 'manage_options', 'blog_in_blog_options_identifier', 'blog_in_blog_options');
	add_action( 'admin_init', 'register_bib_settings' );
}

function register_bib_settings() {
	//register our settings
	wp_enqueue_script('jquery');

        # ##################################
        # CATEGORIES
        # ##################################

	add_settings_section('bib_category_settings', 'Category' , 'bib_category_section_text', 'bib_category_section');

        // for capturing the last tab used on the admin page
	register_setting( 'bib-settings-group', 'bib_last_tab', 'sanitize_text_field' );
        add_settings_field('bib_last_tab', '' , 'bib_last_tab_inupt', 'bib_category_section', 'bib_category_settings');

	register_setting( 'bib-settings-group', 'bib_hide_category', 'bib_sanitize_category_array' );
	add_settings_field('bib_hide_category[]', __('Category(ies) to hide from homepage.','blog-in-blog') , 'bib_category_select', 'bib_category_section', 'bib_category_settings');

	register_setting( 'bib-settings-group', 'bib_hide_category_from_rss', 'absint' );
	add_settings_field('bib_hide_category_from_rss',__('Hide categories from feed?', 'blog-in-blog') , 'bib_category_hide_rss_input', 'bib_category_section', 'bib_category_settings');



        # ##################################
        # FORMAT
        # ##################################

	add_settings_section('bib_format', 'Pagination', 'bib_pagination_section_text', 'bib_pagination_section');

	register_setting( 'bib-settings-group', 'bib_text_previous', 'sanitize_text_field' );
	add_settings_field('bib_text_previous', __('Text to show as "previous page" link', 'blog-in-blog') , 'bib_previous_link_text_input' , 'bib_pagination_section', 'bib_format' );

	register_setting( 'bib-settings-group', 'bib_text_next', 'sanitize_text_field' );
	add_settings_field('bib_text_next',__('Text to show as "next page" link', 'blog-in-blog'), 'bib_next_link_text_input' , 'bib_pagination_section', 'bib_format' );

	register_setting( 'bib-settings-group', 'bib_text_page', 'sanitize_text_field' );
	add_settings_field('bib_text_page',__('Text to show preceeding page 1. e.g. Post (Post 1, 2, 3) or Page (Page 1, 2, 3) etc', 'blog-in-blog'), 'bib_text_page_input' , 'bib_pagination_section', 'bib_format' );

	register_setting( 'bib-settings-group', 'bib_text_delim', 'sanitize_text_field' );
	add_settings_field('bib_text_delim',__('The characters to show between page links, e.g. "," or "|"', 'blog-in-blog'), 'bib_text_delim_input' , 'bib_pagination_section', 'bib_format' );

        register_setting( 'bib-settings-group', 'bib_show_dots_after', 'absint' );
	add_settings_field('bib_show_dots_after',__('Show dots (elipsis ... ) after n pages', 'blog-in-blog') , 'bib_show_dots_input', 'bib_pagination_section', 'bib_format');

	register_setting( 'bib-settings-group', 'bib_style_selected', 'bib_sanitize_css' );
	add_settings_field('bib_style_selected', __('Style for current page e.g. font-weight:bold;', 'blog-in-blog'), 'bib_style_selected_input' , 'bib_pagination_section', 'bib_format' );

	register_setting( 'bib-settings-group', 'bib_style_not_selected', 'bib_sanitize_css' );
	add_settings_field('bib_style_not_selected',__('Style for non current page e.g. color:grey;', 'blog-in-blog') ,'bib_style_not_selected_input' , 'bib_pagination_section', 'bib_format' );
	


        # ##################################
        # TEMPLATE
        # ##################################

        add_settings_section('bib_template', 'Template', 'bib_template_section_text', 'bib_template_section');

	register_setting( 'bib-settings-group', 'bib_html','bib_htmlentities' );
	add_settings_field('bib_html', __('The html for the default post template.','blog-in-blog') , 'bib_html_textarea', 'bib_template_section', 'bib_template');

        
        register_setting( 'bib-settings-group', 'bib_templates','bib_templates_sanitize' );
        add_settings_field('bib_templates', __('User templates','blog-in-blog'), 'bib_templates_textarea', 'bib_template_section', 'bib_template');

	register_setting( 'bib-settings-group', 'bib_more_link_text', 'sanitize_text_field' );
	add_settings_field('bib_more_link_text', __('Text for the more link if you use the &lt;!--more--&gt; tag in your posts.', 'blog-in-blog'), 'bib_more_link_text_input', 'bib_template_section', 'bib_template' );

	register_setting( 'bib-settings-group', 'bib_avatar_size', 'absint' );
	add_settings_field('bib_avatar_size',__('Size of the author avatar image (pixels)', 'blog-in-blog') ,'bib_avatar_size_input' , 'bib_template_section', 'bib_template' );

	
	# custom key formatting
	add_settings_section('bib_meta', __('Custom Fields','blog-in-blog'), 'bib_meta_section_text', 'bib_meta_section');
	
	register_setting('bib-settings-group', 'bib_meta_keys', 'bib_sanitize_meta_keys');
	add_settings_field('bib_meta_keys', __('Custom fields that should be formatted as dates in the template tags (uses default wordpress date format). ', 'blog-in-blog'), 'bib_meta_keys_select', 'bib_meta_section', 'bib_meta' );
	
	
        # ##################################
        # DEBUG
        # ##################################
	
	add_settings_section('bib_debug', 'Miscellaneous', 'bib_debug_section_text', 'bib_debug_section');

        register_setting('bib-settings-group', 'bib_no_collapse', 'absint');
        add_settings_field('bib_no_collapse',__('Disable use of javascript on the admin page. This will show all settings in one go.', 'blog-in-blog') ,'bib_no_collapse_input' , 'bib_debug_section', 'bib_debug' );

        register_setting('bib-settings-group', 'bib_debug', 'absint');
	add_settings_field('bib_debug',__('Show some ugly debugging info', 'blog-in-blog') ,'bib_debug_input' , 'bib_debug_section', 'bib_debug' );

}

# ##############################################
# Category
# ##############################################

function bib_category_section_text()
{
	echo '<p>'.__('Define which categories should be hidden from the home page and optionally exclude them from the feeds. Choose more than one category if required. Only categories with posts will appear for selection.','blog-in-blog').'</p>';
}	

function bib_last_tab_inupt(){
    echo '<input type="hidden" name="bib_last_tab" value="'.get_option('bib_last_tab').'" />';
}

function bib_category_select(){
	// categories to hide

	$select = '<select name="bib_hide_category[]" multiple="multiple" size="6" style="height:auto;">';

	$catselected = get_option('bib_hide_category');
	if (!is_array($catselected)) {
		$catselected = array($catselected);
	}
	//var_dump($catselected);

	$categories = get_categories();

	if (is_array($categories)) {
		foreach ($categories as $cat) {
			if (in_array($cat->cat_ID, $catselected)) {
				$select .= '<option value="' . esc_attr($cat->cat_ID) . '" selected="selected" >';
				$select .= esc_html($cat->cat_name) . ' (category_id=' . esc_html($cat->cat_ID);
				$select .= ', ' . esc_html($cat->category_count) . ' posts)';
				$select .= '</option>';

			} else {
				$select .= '<option value="' . esc_attr($cat->cat_ID) . '">';
				$select .= esc_html($cat->cat_name) . ' (category_id=' . esc_html($cat->cat_ID);
				$select .= ', ' . esc_html($cat->category_count) . ' posts)';
				$select .= '</option>';
			}
		}
	}
	$select .= '<option value="NONE">'.__('Show all categories', 'blog-in-blog').'</option>';
	$select .= '</select>';
	$select .= '</td></tr>';
	
	echo $select;
}

function bib_category_hide_rss_input() {
	// hide categories from RSS feed
	$checked = get_option('bib_hide_category_from_rss') ? 'checked="checked"' : '';
	echo '<input type="checkbox" name="bib_hide_category_from_rss" value="1" ' . $checked . ' />';
}

# ##############################################
# FORMAT
# ##############################################

function bib_pagination_section_text() {
	echo '<p>'.__('The pagination menu is shown by default when there are more posts than will fit on a page (as controlled by the \'num\' shortcode parameter). These settings will allow you to change the pagination menu styling. See the help for turning pagination on and off','bog-in-blog').'</p>';
}

function bib_show_dots_input() {
	// show dots after n pages
	echo '<input type="text" name="bib_show_dots_after" value="' . get_option('bib_show_dots_after') . '" />';
}

function bib_previous_link_text_input() {
	// previous link text
	echo '<input type="text" name="bib_text_previous" value="' . get_option('bib_text_previous') . '" />';
}

function bib_next_link_text_input() {
	// next link text
	echo '<input type="text" name="bib_text_next" value="' . get_option('bib_text_next') . '" />'	;
}

function bib_text_page_input() {
	// text preceeding page 1
	echo '<input type="text" name="bib_text_page" value="' . get_option('bib_text_page') . '" />';
}

function bib_text_delim_input(){
	// delimiter
	echo '<input type="text" name="bib_text_delim" value="' . get_option('bib_text_delim') . '" />';
}

function bib_style_selected_input() {
	// Style selected
	echo '<input type="text" name="bib_style_selected" value="' . get_option('bib_style_selected') . '" />';
}

function bib_style_not_selected_input() {
	// Style not selected
	echo '<input type="text" name="bib_style_not_selected" value="' . get_option('bib_style_not_selected') . '" />';
}



# ##############################################
# TEMPLATE
# ##############################################

function bib_template_section_text()
{
	echo '<p>'.__('The template is used to format each post displayed by a Blog-in-Blog shortcode. Edit the HTML below to update your current default template (this was copied from your existing default template file on upgade to this version). If you want to have more than one template in use for different instances of the shortcode, you can specify a template file using a shortcode paramater. We always look in `wp-content/uploads/` for your template file first (your template will be safe under uploads/) before looking in `wp-content/plugins/blog-in-blog` (your template will probably be lost when the plugin is upgraded). For more template tags see the help tab.','blog-in-blog').'</p>';
}

/**
 * Think this is deprecated - test...
 */
function bib_post_template_input() {
    // template file
    echo '<input type="text" name="bib_post_template" size="60" value="' . get_option('bib_post_template') . '" />';
}

function bib_html_textarea() {
    // Style not selected
    echo '<textarea rows="20" cols="60" name="bib_html" >' . html_entity_decode(get_option('bib_html')) . '</textarea>';
}

function bib_htmlentities($data){
    //var_dump($data);
    return htmlentities($data);
}

/**
 * Sanitize category array
 */
function bib_sanitize_category_array($input) {
    if (!is_array($input)) {
        return array();
    }
    return array_map('absint', $input);
}

/**
 * Sanitize CSS input - allow only safe CSS properties
 */
function bib_sanitize_css($input) {
    return sanitize_text_field($input);
}

/**
 * Sanitize meta keys array
 */
function bib_sanitize_meta_keys($input) {
    if (!is_array($input)) {
        return array();
    }
    return array_map('sanitize_key', $input);
}


function bib_templates_textarea() {
   
    // templates = array(
    //      array('template_name' => 'one' , 'template_html' => 'some HTML'),
    //      array('template_name' => 'two' , 'template_html' => 'some HTML')
    // )
    $templates = get_option('bib_templates');

    if(is_array($templates) ){
        foreach ($templates as $k => $v) {
            if(is_array($v)){
                $k = intval($k);
                echo '<hr><div class="usertemplate">';
                echo '<input type="text" size="40" name="bib_templates[' . esc_attr($k) . '][template_name]" value="' . esc_attr($v['template_name']) . '" /> template name <a href="#" class="delete_user_template" id="bib_templates[' . esc_attr($k) . ']">Delete this template</a>';
                echo '<textarea rows="20" cols="60" name="bib_templates[' . esc_attr($k) . '][template_html]" >' . esc_textarea($v['template_html']) . '</textarea>';
                echo '</div>' ;
            }
        }
    }

    echo '<a href="#" class="add_user_template" title="Add a new template. Requires javascript">Add new user template</a>';

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery("a.add_user_template").click(function(){

            var parent = jQuery("a.add_user_template").parent();

            addnewlink = jQuery("a.add_user_template").detach() ;

            existing = parent.html();

            template_count = parent.children("div.usertemplate").length ;

            html_before = '<hr><div class="usertemplate">';
            input = '<input type="text" size="40" name="bib_templates['+ template_count +'][template_name]" value="Your template name here" /> (not saved)';
            textarea = '<textarea rows="20" cols="60" name="bib_templates['+ template_count +'][template_html]" >Your html here</textarea>';
            html_after = '</div>';

            parent.html(existing + html_before + input + textarea + html_after );
            parent.append(addnewlink) ;

        });
        jQuery("a.delete_user_template").click(function(){
            id = jQuery(this).attr('id');
            if(confirm("Are you sure you want to delete this template?")){
                tn = '[name="' + id + '[template_name]"]' ;
                th = '[name="' + id + '[template_html]"]' ;

                jQuery(tn).val('');
                jQuery(th).val('');

                jQuery(tn).parent().fadeOut();

                jQuery(".button-secondary").trigger('click');
            }
        });
    });
    </script>
    <?php

}


function bib_templates_sanitize($data){
    //var_dump($data);
    if (is_array($data)){
        foreach ($data as $k => $v) {
            if($v['template_name']=='' && $v['template_html']==''){
                unset($data[$k]);
            }else{
                $v['template_name'] = strtolower(str_replace(" ", "_", $v['template_name']));
                $v['template_html'] = htmlentities($v['template_html']);
                $data[$k] = $v;
            }
        }
    }
    //print_r($data);
    return $data;
}

function bib_avatar_size_input() {
	// avatar image size
	echo '<input type="text" name="bib_avatar_size" value="' . get_option('bib_avatar_size') . '" />';
}

function bib_more_link_text_input() {
	// more_link_text
	echo '<input type="text" name="bib_more_link_text" value="' . get_option('bib_more_link_text') . '" />';
}

function bib_meta_section_text() {
	echo '<p>'.__('It is possible to display your custom fields in your post using template tags. The template tag will be the name of your custom field surrounded by a percent symbol (%). For example %my_field% . When you use date values in your custom fields, they should be entered using the format YYYY-MM-DD if you require sorting on a custom field in date order to work as expected. You can define which custom fields should be reformatted as a \'pretty\' date in your locale specific format. ','blog-in-blog').'</p>';
}


function bib_meta_keys_select(){
	// meta_keys to select

	$select = '<select name="bib_meta_keys[]" multiple="multiple" size="6" style="height:auto;">';

	$cselected = get_option('bib_meta_keys');
	if (!is_array($cselected)) {
		$cselected = array($cselected);
	}
	//var_dump($catselected);
	global $wpdb;

	$meta_keys = $wpdb->get_col("SELECT DISTINCT meta_key FROM $wpdb->postmeta");
	//var_dump($meta_keys);

	if (is_array($meta_keys)) {

		foreach ($meta_keys as $key) {

			if (in_array($key, $cselected)) {
				$select .= '<option value="' . esc_attr($key) . '" selected="selected" >';
				$select .= esc_html($key) ;
				$select .= '</option>';

			} else {
				$select .= '<option value="' . esc_attr($key) . '">';
				$select .= esc_html($key);
				$select .= '</option>';
			}

		}
	}
	$select .= '<option value="NONE">'.__('Select none', 'blog-in-blog').'</option>';
	$select .= '</select>';
	$select .= '</td></tr>';

	echo $select;
}


# ##############################################
# DEBUG
# ##############################################

function bib_debug_section_text()
{
	echo '<p>'.__('Some extra settings.','blog-in-blog').'</p>';
}



function bib_debug_input() {
	$checked = get_option('bib_debug') ? 'checked="checked"' : '';
	echo '<input type="checkbox" name="bib_debug" value="1" ' . $checked . ' />';
}

function bib_no_collapse_input() {
	$checked = get_option('bib_no_collapse') ? 'checked="checked"' : '';
	echo '<input type="checkbox" name="bib_no_collapse" value="1" ' . $checked . ' />';
}

function bib_get_help() {
    echo "<h3>Help!</h3>";
    $rt = file_get_contents( WP_PLUGIN_DIR.'/blog-in-blog/readme.txt' );
    $rc = pm_parsePluginReadme($rt, true);
    if (is_array($rc['sections'])){
        foreach ($rc['sections'] as $section){
            echo wp_kses_post($section);
        }
    }
}


function blog_in_blog_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'blog-in-blog'));
    }

    if(! get_option('bib_no_collapse')) {
?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery(".wrap").hide();
                
                jQuery("a.nav-tab").click(function(e){
                    e.preventDefault();
                });

                // apply click functions to nav tabs.
                jQuery(".nav-tab").click(function(){
                    jQuery("a.nav-tab.nav-tab-active").toggleClass("nav-tab-active") ;
                    jQuery(this).toggleClass("nav-tab-active") ;
                });

                // take clickevent off donate div!
                jQuery("div.nav-tab").unbind("click");

                // first time through hide everything but category section
                jQuery(".collapsable").hide();
                jQuery(".wrap").show();


                // toggle the display of the options.
                jQuery("#bib_category_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_category_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_category_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_category_section_tab');
                    jQuery("p.submit").show();
                });

                jQuery("#bib_pagination_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_pagination_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_pagination_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_pagination_section_tab');
                    jQuery("p.submit").show();
                });

                jQuery("#bib_template_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_template_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_template_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_template_section_tab');
                    jQuery("p.submit").show();
                });

                jQuery("#bib_debug_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_debug_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_debug_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_debug_section_tab');
                    jQuery("p.submit").show();
                });

                jQuery("#bib_help_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_help_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_help_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_help_section_tab');
                    jQuery("p.submit").hide();
                });

                jQuery("#bib_donate_section_tab").click(function(){
                    jQuery(".visible").hide().toggleClass("visible") ;
                    jQuery("#bib_donate_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_donate_section .collapsable").slideToggle() ;
                    jQuery('[name="bib_last_tab"]').val('#bib_donate_section_tab');
                    jQuery("p.submit").hide();
                });

                // get last used tab
                lt = jQuery('[name="bib_last_tab"]').val();
               
                if (lt != '' ){
                    jQuery(lt).trigger('click');
                } else {
                    jQuery("#bib_category_section .collapsable").slideToggle() ;
                    jQuery("#bib_category_section .collapsable").toggleClass("visible") ;
                    jQuery("#bib_category_section_tab").toggleClass("nav-tab-active") ;
                }

            });
        </script>
<?php }  ?>
        <style type="text/css">
            #settings_wrap {
/*                width:700px ;*/
                width: 90% ;
            }
            .clickable {
                margin-bottom: -1px;
                padding:0;
            }
            .form-table th {
                width:400px;
            }
            #bib_template_section .form-table th{
                width: 200px;
            }
            #bib_help_section ul {
                list-style-type: square !important;
                padding: 0 0 0 20px;
            }
            #bib_category_section .form-table th{
                width: 200px;
            }
            .submit.secondary {
                float:right;
                margin: 5px 100px 5px 20px  ;
                position: relative;
                top:-10px;
            }
            #bib_menu{
                padding: 0 0 0 5px !important;
            }
            h2 .nav-tab {
               font-size: 0.7em !important;
               margin-right: 2px !important;
               padding: 2px 10px 6px !important;
            }
            .donate {
                background-color: #ffeebb;   
            }
            h3{
                border-width: 1px 1px 0;
                border-style: solid solid solid;
                -moz-border-radius-topleft: 6px;
                -moz-border-radius-topright: 6px;
                -webkit-border-top-right-radius: 6px;
                -webkit-border-top-left-radius: 6px;
                -khtml-border-top-right-radius: 6px;
                -khtml-border-top-left-radius: 6px;
                border-top-right-radius: 6px;
                border-top-left-radius: 6px;
                background-color: #F1F1F1 !important ;
                border-color: #E3E3E3;
                padding: 7px 4px 25px 7px !important;
               border-bottom: 1px solid #E3E3E3;
            }
            
        </style>
	<div class="wrap">
	<h2>Blog-in-Blog</h2>
        
        <h2 id="bib_menu" style="border-bottom: 1px solid #ccc; padding-bottom: 0;">
            <a href="#category" id="bib_category_section_tab" class="clickable nav-tab"><?php _e('Category','blog-in-blog') ; ?> </a>
            <a href="#pagination" id="bib_pagination_section_tab" class="clickable nav-tab"><?php _e('Pagination','blog-in-blog') ; ?> </a>
            <a href="#template" id="bib_template_section_tab" class="clickable nav-tab"><?php _e('Template','blog-in-blog') ; ?> </a>
            <a href="#misc" id="bib_debug_section_tab" class="clickable nav-tab"><?php _e('Misc','blog-in-blog') ; ?> </a>
            <a href="#help" id="bib_help_section_tab" class="clickable nav-tab"><?php _e('Help','blog-in-blog') ; ?> </a>
            <a href="#donate" id="bib_donate_section_tab" class="clickable nav-tab donate"><?php _e('Donate','blog-in-blog') ; ?> ☕</a>
        </h2>
        <div id="settings_wrap">
        <form method="post" action="options.php">

	<?php settings_fields( 'bib-settings-group' ); ?>
            
            <div id="bib_category_section">
                <div class="collapsable"><a name="category" ></a>
                    <p class="submit secondary">
                    <input type="submit" class="button-secondary" value="<?php _e('Save Changes (All Tabs)'); ?>" />
                    </p>
                    
                    <?php do_settings_sections('bib_category_section'); ?>
                    
                </div>
            </div>
            <div id="bib_pagination_section">
                <div class="collapsable"><a name="pagination" ></a>
                    <p class="submit secondary">
                    <input type="submit" class="button-secondary" value="<?php _e('Save Changes (All Tabs)'); ?>" />
                    </p>
                    <?php do_settings_sections('bib_pagination_section'); ?>
                </div>
            </div>
            <div id="bib_template_section">
                <div class="collapsable"><a name="template" ></a>
                    <p class="submit secondary">
                    <input type="submit" class="button-secondary" value="<?php _e('Save Changes (All Tabs)'); ?>" />
                    </p>
                    <?php do_settings_sections('bib_template_section'); ?>
                    <?php do_settings_sections('bib_meta_section'); ?>
                </div>
            </div>
            <div id="bib_debug_section">
                <div class="collapsable"><a name="misc" ></a>
                    <p class="submit secondary">
                    <input type="submit" class="button-secondary" value="<?php _e('Save Changes (All Tabs)'); ?>" />
                    </p>
                    <?php do_settings_sections('bib_debug_section'); ?>
                </div>
            </div>
            <div id="bib_help_section">
                <div class="collapsable"><a name="help" ></a>
                   <?php  bib_get_help(); ?>
                </div>
            </div>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes (All Tabs)'); ?>" />
	</p>
        </form>

            <div id="bib_donate_section">
                <div class="collapsable"><a name="donate" ></a>
                    <h3><?php _e('Support Blog-in-Blog Development', 'blog-in-blog'); ?></h3>
                    <div style="max-width: 600px; line-height: 1.6;">
                        <p><?php _e('Hi! I\'m Tim, the developer of Blog-in-Blog. I created this plugin and maintain it in my free time, alongside my day job and family life.', 'blog-in-blog'); ?></p>
                        <p><?php _e('If you find this plugin useful for your website, please consider buying me a coffee! Your support helps me dedicate time to:', 'blog-in-blog'); ?></p>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li><?php _e('Keeping the plugin updated and compatible with the latest WordPress versions', 'blog-in-blog'); ?></li>
                            <li><?php _e('Fixing bugs and improving performance', 'blog-in-blog'); ?></li>
                            <li><?php _e('Adding new features based on user feedback', 'blog-in-blog'); ?></li>
                            <li><?php _e('Providing support to users', 'blog-in-blog'); ?></li>
                        </ul>
                        <p><?php _e('Every donation, no matter how small, is greatly appreciated and motivates me to keep improving this plugin. Thank you! 🙏', 'blog-in-blog'); ?></p>
                        <div style="margin-top: 20px;">
                            <form action="https://www.paypal.com/donate" method="post" target="_top">
                                <input type="hidden" name="hosted_button_id" value="P52WVZF99UG9L" />
                                <input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
                                <img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>

	<?php echo __('See full notes at', 'blog-in-blog') ; ?>
        <a href="http://wordpress.org/extend/plugins/blog-in-blog/">http://wordpress.org/extend/plugins/blog-in-blog/</a>

	</div>
	
	<?php
}

?>