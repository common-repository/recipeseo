<?php
/*
Plugin Name: RecipeSEO
Plugin URI: http://sushiday.com/recipe-seo-plugin/
Description: A plugin that adds all the necessary microdata to your recipes, so it will show up in Google's Recipe Search
Version: 1.3.2
Author: Allison Day
Author URI: http://codeswan.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hey!  This is just a plugin, not much it can do when called directly.";
	exit;
}

if (!defined('AMD_RECIPESEO_VERSION_KEY'))
    define('AMD_RECIPESEO_VERSION_KEY', 'amd_recipeseo_version');

if (!defined('AMD_RECIPESEO_VERSION_NUM'))
    define('AMD_RECIPESEO_VERSION_NUM', '1.3.2');
    
if (!defined('AMD_RECIPESEO_PLUGIN_DIRECTORY'))
    define('AMD_RECIPESEO_PLUGIN_DIRECTORY', get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/');

add_option(AMD_RECIPESEO_VERSION_KEY, AMD_RECIPESEO_VERSION_NUM);

add_option('recipeseo_ingredient_label', 'Ingredients');
add_option('recipeseo_ingredient_label_hide', '');
add_option('recipeseo_ingredient_list_type', 'ul');
add_option('recipeseo_instruction_label', 'Cooking Directions');
add_option('recipeseo_instruction_label_hide', '');
add_option('recipeseo_instruction_list_type', 'ol');
add_option('recipeseo_prep_time_label', 'Prep Time:');
add_option('recipeseo_prep_time_label_hide', '');
add_option('recipeseo_cook_time_label', 'Cook Time:');
add_option('recipeseo_cook_time_label_hide', '');
add_option('recipeseo_total_time_label', 'Total Time:');
add_option('recipeseo_total_time_label_hide', '');
add_option('recipeseo_yield_label', 'Yield:');
add_option('recipeseo_yield_label_hide', '');
add_option('recipeseo_serving_size_label', 'Serving Size:');
add_option('recipeseo_serving_size_label_hide', '');
add_option('recipeseo_calories_label', 'Calories per serving:');
add_option('recipeseo_calories_label_hide', '');
add_option('recipeseo_fat_label', 'Fat per serving:');
add_option('recipeseo_fat_label_hide', '');

register_activation_hook(__FILE__, 'amd_recipeseo_install');

add_action('media_buttons', 'amd_recipeseo_add_recipe_button', 30);
add_action('init', 'amd_recipeseo_enhance_mce');

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=amd_recipeseo') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	amd_recipeseo_iframe_content($_POST, $_REQUEST);
	exit;
}

// Creates RecipeSEO tables in the db if they don't exist already.
function amd_recipeseo_install() {
    global $wpdb;

    $recipes_table = $wpdb->prefix . "amd_recipeseo_recipes";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$recipes_table'") != $recipes_table) {
        $sql = "CREATE TABLE " . $recipes_table . " (
            recipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            recipe_title TEXT,
            summary TEXT,
            rating TEXT,
            prep_time TEXT,
            cook_time TEXT,
            total_time TEXT,
            yield TEXT,
            serving_size VARCHAR(50),
            calories VARCHAR(50),
            fat VARCHAR(50),
            instructions TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    $ingredients_table = $wpdb->prefix . "amd_recipeseo_ingredients";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$ingredients_table'") != $ingredients_table) {
        $sql_2 = "CREATE TABLE " . $ingredients_table . " (
            ingredient_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(200) NOT NULL,
            amount VARCHAR(200),
            created_at TIMESTAMP DEFAULT NOW()
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_2);
    }
    
    add_option("amd_recipeseo_db_version", "1.0");
}

add_action('admin_menu', 'amd_recipeseo_menu_pages');

// Adds module to left sidebar in wp-admin for RecipeSEO
function amd_recipeseo_menu_pages() {
    // Add the top-level admin menu
    $page_title = 'RecipeSEO Settings';
    $menu_title = 'RecipeSEO';
    $capability = 'manage_options';
    $menu_slug = 'recipeseo-settings';
    $function = 'amd_recipeseo_settings';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    // Add submenu page with same slug as parent to ensure no duplicates
    $settings_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $settings_title, $capability, $menu_slug, $function);
}

// Adds 'Settings' page to the RecipeSEO module
function amd_recipeseo_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    $recipeseo_icon = AMD_RECIPESEO_PLUGIN_DIRECTORY . "recipeseo.gif";
    
    if ($_POST['ingredient-list-type']) {
        $ingredient_label = $_POST['ingredient-label'];
        $ingredient_label_hide = $_POST['ingredient-label-hide'];
        $ingredient_list_type = $_POST['ingredient-list-type'];
        $instruction_label = $_POST['instruction-label'];
        $instruction_label_hide = $_POST['instruction-label-hide'];
        $instruction_list_type = $_POST['instruction-list-type'];
        $prep_time_label = $_POST['prep-time-label'];
        $prep_time_label_hide = $_POST['prep-time-label-hide'];
        $cook_time_label = $_POST['cook-time-label'];
        $cook_time_label_hide = $_POST['cook-time-label-hide'];
        $total_time_label = $_POST['total-time-label'];
        $total_time_label_hide = $_POST['total-time-label-hide'];
        $yield_label = $_POST['yield-label'];
        $yield_label_hide = $_POST['yield-label-hide'];
        $serving_size_label = $_POST['serving-size-label'];
        $serving_size_label_hide = $_POST['serving-size-label-hide'];
        $calories_label = $_POST['calories-label'];
        $calories_label_hide = $_POST['calories-label-hide'];
        $fat_label = $_POST['fat-label'];
        $fat_label_hide = $_POST['fat-label-hide'];
        
        update_option('recipeseo_ingredient_label', $ingredient_label);
        update_option('recipeseo_ingredient_label_hide', $ingredient_label_hide);
        update_option('recipeseo_ingredient_list_type', $ingredient_list_type);
        update_option('recipeseo_instruction_label', $instruction_label);
        update_option('recipeseo_instruction_label_hide', $instruction_label_hide);
        update_option('recipeseo_instruction_list_type', $instruction_list_type);
        update_option('recipeseo_prep_time_label', $prep_time_label);
        update_option('recipeseo_prep_time_label_hide', $prep_time_label_hide);
        update_option('recipeseo_cook_time_label', $cook_time_label);
        update_option('recipeseo_cook_time_label_hide', $cook_time_label_hide);
        update_option('recipeseo_total_time_label', $total_time_label);
        update_option('recipeseo_total_time_label_hide', $total_time_label_hide);
        update_option('recipeseo_yield_label', $yield_label);
        update_option('recipeseo_yield_label_hide', $yield_label_hide);
        update_option('recipeseo_serving_size_label', $serving_size_label);
        update_option('recipeseo_serving_size_label_hide', $serving_size_label_hide);
        update_option('recipeseo_calories_label', $calories_label);
        update_option('recipeseo_calories_label_hide', $calories_label_hide);
        update_option('recipeseo_fat_label', $fat_label);
        update_option('recipeseo_fat_label_hide', $fat_label_hide);
    } else {
        $ingredient_label = get_option('recipeseo_ingredient_label');
        $ingredient_label_hide = get_option('recipeseo_ingredient_label_hide');
        $ingredient_list_type = get_option('recipeseo_ingredient_list_type');
        $instruction_label = get_option('recipeseo_instruction_label');
        $instruction_label_hide = get_option('recipeseo_instruction_label_hide');
        $instruction_list_type = get_option('recipeseo_instruction_list_type');
        $prep_time_label = get_option('recipeseo_prep_time_label');
        $prep_time_label_hide = get_option('recipeseo_prep_time_label_hide');
        $cook_time_label = get_option('recipeseo_cook_time_label');
        $cook_time_label_hide = get_option('recipeseo_cook_time_label_hide');
        $total_time_label = get_option('recipeseo_total_time_label');
        $total_time_label_hide = get_option('recipeseo_total_time_label_hide');
        $yield_label = get_option('recipeseo_yield_label');
        $yield_label_hide = get_option('recipeseo_yield_label_hide');
        $serving_size_label = get_option('recipeseo_serving_size_label');
        $serving_size_label_hide = get_option('recipeseo_serving_size_label_hide');
        $calories_label = get_option('recipeseo_calories_label');
        $calories_label_hide = get_option('recipeseo_calories_label_hide');
        $fat_label = get_option('recipeseo_fat_label');
        $fat_label_hide = get_option('recipeseo_fat_label_hide');
    }
    
    $ingredient_label_hide = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ing_ul = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ing_ol = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ing_p = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ing_div = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
    
    $instruction_label_hide = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ins_ul = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ins_ol = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ins_p = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ins_div = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');
    
    $prep_time_label_hide = (strcmp($prep_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $cook_time_label_hide = (strcmp($cook_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $total_time_label_hide = (strcmp($total_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $yield_label_hide = (strcmp($yield_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $serving_size_label_hide = (strcmp($serving_size_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $calories_label_hide = (strcmp($calories_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $fat_label_hide = (strcmp($fat_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $other_options = '';
    $other_options_array = array('Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 'Calories', 'Fat');
    
    foreach ($other_options_array as $option) {
        $name = strtolower(str_replace(' ', '-', $option));
        $value = strtolower(str_replace(' ', '_', $option)) . '_label';
        $value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
        $other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . ${$value_hide} . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
    }

    echo '<style>
        .form-table label { line-height: 2.5; }
        hr { border: 1px solid #DDD; border-left: none; border-right: none; border-bottom: none; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form enctype="multipart/form-data" method="post" action="" name="recipeseo_settings_form">
            <h2><img src="' . $recipeseo_icon . '" /> RecipeSEO Settings</h2>
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td><input type="radio" name="ingredient-list-type" value="ul" ' . $ing_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="ingredient-list-type" value="ol" ' . $ing_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="ingredient-list-type" value="p" ' . $ing_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="ingredient-list-type" value="div" ' . $ing_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td><input type="radio" name="instruction-list-type" value="ol" ' . $ins_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="instruction-list-type" value="ul" ' . $ins_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="instruction-list-type" value="p" ' . $ins_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="instruction-list-type" value="div" ' . $ins_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>
            
            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
        </form>
    </div>';
}

function amd_recipeseo_enhance_mce() {
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
        return;
    if ( get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'amd_recipeseo_tinymce_plugin');
    }
}

function amd_recipeseo_tinymce_plugin($plugin_array) {
    
   $plugin_array['amdrecipeseo'] =  get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/recipeseo_editor_plugin.js';
   
   return $plugin_array;
}

// Adds  the recipe button to the editor in the media row
function amd_recipeseo_add_recipe_button() {
    global $post_ID, $temp_ID;
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);

	$media_upload_iframe_src = get_option('siteurl').'/wp-admin/media-upload.php?post_id='.$uploading_iframe_ID;

	$media_amd_recipeseo_iframe_src = apply_filters('media_amd_recipeseo_iframe_src', "$media_upload_iframe_src&amp;type=amd_recipeseo&amp;tab=amd_recipeseo");
	$media_amd_recipeseo_title = __('Add a Recipe', 'wp-media-amd_recipeseo');

	echo "<a class=\"thickbox\" href=\"{$media_amd_recipeseo_iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640\" title=\"$media_amd_recipeseo_title\"><img src='" . get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)) . "/recipeseo.gif?ver=1.0' alt='RecipeSEO Icon' /></a>";
}

// Content for the popup iframe when creating or editing a recipe
function amd_recipeseo_iframe_content($post_info = null, $get_info = null) {
    $recipe_id = 0;
    $iframe_title = "Add a Recipe";
    $submit = "Add Recipe";
    if ($post_info || $get_info) {
        if ($get_info["post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe = amd_recipeseo_select_recipe_db($recipe_id);
            $ingredients_list = amd_recipeseo_select_ingredients_db($recipe_id);
            
            $recipe_title = $recipe->recipe_title;
            $summary = $recipe->summary;
            $rating = $recipe->rating;
            
            $prep_time_input = '';
            $cook_time_input = '';
            $total_time_input = '';
            if (class_exists('DateInterval')) {
                try {
                    $prep_time = new DateInterval($recipe->prep_time);
                    $prep_time_seconds = $prep_time->s;
                    $prep_time_minutes = $prep_time->i;
                    $prep_time_hours = $prep_time->h;
                    $prep_time_days = $prep_time->d;
                    $prep_time_months = $prep_time->m;
                    $prep_time_years = $prep_time->y;
                } catch (Exception $e) {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                try {
                    $cook_time = new DateInterval($recipe->cook_time);
                    $cook_time_seconds = $cook_time->s;
                    $cook_time_minutes = $cook_time->i;
                    $cook_time_hours = $cook_time->h;
                    $cook_time_days = $cook_time->d;
                    $cook_time_months = $cook_time->m;
                    $cook_time_years = $cook_time->y;
                } catch (Exception $e) {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
            
                try {
                    $total_time = new DateInterval($recipe->total_time);
                    $total_time_seconds = $total_time->s;
                    $total_time_minutes = $total_time->i;
                    $total_time_hours = $total_time->h;
                    $total_time_days = $total_time->d;
                    $total_time_months = $total_time->m;
                    $total_time_years = $total_time->y;
                } catch (Exception $e) {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            } else {
                if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
                    preg_match('(\d*S)', $recipe->prep_time, $pts);
                    $prep_time_seconds = str_replace('S', '', $pts[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
                    $prep_time_minutes = str_replace('M', '', $ptm[0][0]);
                    preg_match('(\d*H)', $recipe->prep_time, $pth);
                    $prep_time_hours = str_replace('H', '', $pth[0]);
                    preg_match('(\d*D)', $recipe->prep_time, $ptd);
                    $prep_time_days = str_replace('D', '', $ptd[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptmm);
                    $prep_time_months = str_replace('M', '', $ptmm[0]);
                    preg_match('(\d*Y)', $recipe->prep_time, $pty);
                    $prep_time_years = str_replace('Y', '', $pty[0]);
                } else {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
                    preg_match('(\d*S)', $recipe->cook_time, $cts);
                    $cook_time_seconds = str_replace('S', '', $cts[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
                    $cook_time_minutes = str_replace('M', '', $ctm[0][0]);
                    preg_match('(\d*H)', $recipe->cook_time, $cth);
                    $cook_time_hours = str_replace('H', '', $cth[0]);
                    preg_match('(\d*D)', $recipe->cook_time, $ctd);
                    $cook_time_days = str_replace('D', '', $ctd[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctmm);
                    $cook_time_months = str_replace('M', '', $ctmm[0]);
                    preg_match('(\d*Y)', $recipe->cook_time, $cty);
                    $cook_time_years = str_replace('Y', '', $cty[0]);
                } else {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
                    preg_match('(\d*S)', $recipe->total_time, $tts);
                    $total_time_seconds = str_replace('S', '', $tts[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
                    $total_time_minutes = str_replace('M', '', $ttm[0][0]);
                    preg_match('(\d*H)', $recipe->total_time, $tth);
                    $total_time_hours = str_replace('H', '', $tth[0]);
                    preg_match('(\d*D)', $recipe->total_time, $ttd);
                    $total_time_days = str_replace('D', '', $ttd[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttmm);
                    $total_time_months = str_replace('M', '', $ttmm[0]);
                    preg_match('(\d*Y)', $recipe->total_time, $tty);
                    $total_time_years = str_replace('Y', '', $tty[0]);
                } else {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            }
            
            $yield = $recipe->yield;
            $serving_size = $recipe->serving_size;
            $calories = $recipe->calories;
            $fat = $recipe->fat;
            $ingredients = array();
            $i = 0;
            foreach ($ingredients_list as $ingredient) {
                $ingredients[$i]["name"] = $ingredient->name;
                $ingredients[$i]["amount"] = $ingredient->amount;
                $i++;
            }
            $instructions = $recipe->instructions;
            $iframe_title = "Update Your Recipe";
            $submit = "Update Recipe";
        } else {
            $recipe_id = htmlentities($post_info["recipe_id"], ENT_QUOTES);
            $recipe_title = htmlentities($post_info["recipe_title"], ENT_QUOTES);
            $summary = htmlentities($post_info["summary"], ENT_QUOTES);
            $rating = htmlentities($post_info["rating"], ENT_QUOTES);
            $prep_time_seconds = htmlentities($post_info["prep_time_seconds"], ENT_QUOTES);
            $prep_time_minutes = htmlentities($post_info["prep_time_minutes"], ENT_QUOTES);
            $prep_time_hours = htmlentities($post_info["prep_time_hours"], ENT_QUOTES);
            $prep_time_days = htmlentities($post_info["prep_time_days"], ENT_QUOTES);
            $prep_time_weeks = htmlentities($post_info["prep_time_weeks"], ENT_QUOTES);
            $prep_time_months = htmlentities($post_info["prep_time_months"], ENT_QUOTES);
            $prep_time_years = htmlentities($post_info["prep_time_years"], ENT_QUOTES);
            $cook_time_seconds = htmlentities($post_info["cook_time_seconds"], ENT_QUOTES);
            $cook_time_minutes = htmlentities($post_info["cook_time_minutes"], ENT_QUOTES);
            $cook_time_hours = htmlentities($post_info["cook_time_hours"], ENT_QUOTES);
            $cook_time_days = htmlentities($post_info["cook_time_days"], ENT_QUOTES);
            $cook_time_weeks = htmlentities($post_info["cook_time_weeks"], ENT_QUOTES);
            $cook_time_months = htmlentities($post_info["cook_time_months"], ENT_QUOTES);
            $cook_time_years = htmlentities($post_info["cook_time_years"], ENT_QUOTES);
            $total_time_seconds = htmlentities($post_info["total_time_seconds"], ENT_QUOTES);
            $total_time_minutes = htmlentities($post_info["total_time_minutes"], ENT_QUOTES);
            $total_time_hours = htmlentities($post_info["total_time_hours"], ENT_QUOTES);
            $total_time_days = htmlentities($post_info["total_time_days"], ENT_QUOTES);
            $total_time_weeks = htmlentities($post_info["total_time_weeks"], ENT_QUOTES);
            $total_time_months = htmlentities($post_info["total_time_months"], ENT_QUOTES);
            $total_time_years = htmlentities($post_info["total_time_years"], ENT_QUOTES);
            $yield = htmlentities($post_info["yield"], ENT_QUOTES);
            $serving_size = htmlentities($post_info["serving_size"], ENT_QUOTES);
            $calories = htmlentities($post_info["calories"], ENT_QUOTES);
            $fat = htmlentities($post_info["fat"], ENT_QUOTES);
            $ingredients = array();
            for ($i = 0; $i < count($post_info["ingredients"]); $i++) {
                $ingredients[$i]["name"] = htmlentities($post_info["ingredients"][$i]["name"], ENT_QUOTES);
                $ingredients[$i]["amount"] = htmlentities($post_info["ingredients"][$i]["amount"], ENT_QUOTES);
            }
            $instructions = htmlentities($post_info["instructions"], ENT_QUOTES);
            if ($recipe_title != null && $recipe_title != '' && $ingredients[0]['name'] != null && $ingredients[0]['name'] != '') {
                $recipe_id = amd_recipeseo_insert_db($post_info);
            }
        }
    }
    
    $id = (int) $_REQUEST["post_id"];
    $url = get_option('siteurl');
    $dirname = dirname(plugin_basename(__FILE__));
    $submitform = '';
    if ($post_info != null) {
        $submitform .= "<script>window.onload = amdRecipeseoSubmitForm;</script>";
    }
    $addingredients = '';
    if (!empty($ingredients) && count($ingredients) > 5) {
        $num_ingredients = count($ingredients);
    } else {
        $num_ingredients = 5;
    }
    for ($i=1; $i<$num_ingredients; $i++) {
        $addingredients .= "<script type='text/javascript'>amdRecipeseoAddIngredient('" . $i . "', '" . $ingredients[$i]['amount'] . "', '" . $ingredients[$i]['name'] . "');</script>";
    }
    
    echo <<< HTML

<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="$url/wp-content/plugins/$dirname/recipeseo.css" type="text/css" media="all" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[
                                        
        var globalCount = 0;
        
        function amdRecipeseoAddIngredient(count, amount, name) {
            five = true;
            amount1 = '';
            name1 = '';
            if (count!=undefined) {
                globalCount=count;
                five = false;
            }
            if (amount!=undefined) {
                amount1=amount;
            }
            if (name!=undefined) {
                name1=name;
            }
            if (five) {
                for (i=0;i<5;i++) {
                    var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="amount" type="text" name="ingredients[' + globalCount + '][amount]" value="' + amount1 + '" /><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>';
                    $('#amd_recipeseo_ingredients').append(newIngredient);
                    globalCount++;
                }
            } else {
                var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="amount" type="text" name="ingredients[' + globalCount + '][amount]" value="' + amount1 + '" /><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>';
                $('#amd_recipeseo_ingredients').append(newIngredient);
                globalCount++;
            }
            
            return false;
        }
        
        function amdRecipeseoSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;
            var ingredient0 = $('#ingredient-0 input.name').val();
            if (title==null || title=='') {
                $('#recipe-title input').addClass('input-error');
                $('#recipe-title').append('<p class="error-message">You must enter a title for your recipe.</p>');
                
                return false;
            }
            if (ingredient0==null || ingredient0=='' || ingredient0==undefined) {
                $('#ingredient-0 input').addClass('input-error');
                $('#amd_recipeseo_ingredients').append('<p class="error-message">You must enter at least one ingredient.</p>');
                
                return false;
            }
            window.parent.amdRecipeseoInsertIntoPostEditor('$recipe_id','$url','$dirname');
        }
        
        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                
                return false;
            });
            $('#add-another-ingredient a').click(function() {
                amdRecipeseoAddIngredient();
                
                return false;
            });
        });
        
    //]]>
    </script>
    $submitform
</head>
<body id="amd-recipeseo-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
        <h3 class='amd-recipeseo-title'>$iframe_title</h3>
        <div id='amd-recipeseo-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <span id='amd_recipeseo_ingredients' class='cls'><p><label class='cls'>Ingredients <span class='required'>*</span></label></p>
                <div class='ingredient cls'><label class='amount'>Amount</label><label class='name'>Name</label></div>
                <div id='ingredient-0' class='ingredient cls'><input class='amount' type='text' name='ingredients[0][amount]' value='{$ingredients[0]['amount']}' /><input class='name' type='text' name='ingredients[0][name]' value='{$ingredients[0]['name']}' /></div>
                $addingredients
            </span>
            <p id='add-another-ingredient'><a href='#' class='cls'>+ Add more ingredients</a></p>
            <p id='amd-recipeseo-instructions' class='cls'><label>Instructions <small>(Put each instruction on a separate line. There is no need to number your instructions.)</small></label><textarea name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></label></p>
                <p><label>Rating</label> <input type='text' name='rating' value='$rating' /></p>
                <p class="cls"><label>Prep Time</label> 
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="60" name='prep_time_seconds' value='$prep_time_seconds' /><label>seconds</label></span>
                        <span><input type='number' min="0" max="60" name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                        <span><input type='number' min="0" max="24" name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span class="last"><input type='number' min="0" max="31" name='prep_time_days' value='$prep_time_days' /><label>days</label></span>
                        <span><input type='number' min="0" max="12" name='prep_time_months' value='$prep_time_months' /><label>months</label></span>
                        <span><input type='number' min="0" name='prep_time_years' value='$prep_time_years' /><label>years</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="60" name='cook_time_seconds' value='$cook_time_seconds' /><label>seconds</label></span>
                        <span><input type='number' min="0" max="60" name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                        <span><input type='number' min="0" max="24" name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span class="last"><input type='number' min="0" max="31" name='cook_time_days' value='$cook_time_days' /><label>days</label></span>
                        <span><input type='number' min="0" max="12" name='cook_time_months' value='$cook_time_months' /><label>months</label></span>
                        <span><input type='number' min="0" name='cook_time_years' value='$cook_time_years' /><label>years</label></span>
                    </span>
                </p>
                <p class="cls"><label>Total Time</label>
                    $total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="60" name='total_time_seconds' value='$total_time_seconds' /><label>seconds</label></span> 
                        <span><input type='number' min="0" max="60" name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                        <span><input type='number' min="0" max="24" name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span class="last"><input type='number' min="0" max="31" name='total_time_days' value='$total_time_days' /><label>days</label></span>
                        <span><input type='number' min="0" max="12" name='total_time_months' value='$total_time_months' /><label>months</label></span>
                        <span><input type='number' min="0" name='total_time_years' value='$total_time_years' /><label>years</label></span>
                    </span>
                </p>
                <p><label>Yield</label> <input type='text' name='yield' value='$yield' /></p>
                <p><label>Serving Size</label> <input type='text' name='serving_size' value='$serving_size' /></p>
                <p><label>Calories</label> <input type='text' name='calories' value='$calories' /></p>
                <p><label>Fat</label> <input type='text' name='fat' value='$fat' /></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
    </form>
</body>
HTML;
}

// Inserts the recipe into the database
function amd_recipeseo_insert_db($post_info) {
    global $wpdb;
    
    $recipe_id = $post_info["recipe_id"];
    
    if ($post_info["prep_time_years"] || $post_info["prep_time_months"] || $post_info["prep_time_days"] || $post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
        $prep_time = 'P';
        if ($post_info["prep_time_years"]) {
            $prep_time .= $post_info["prep_time_years"] . 'Y';
        }
        if ($post_info["prep_time_months"]) {
            $prep_time .= $post_info["prep_time_months"] . 'M';
        }
        if ($post_info["prep_time_days"]) {
            $prep_time .= $post_info["prep_time_days"] . 'D';
        }
        if ($post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
            $prep_time .= 'T';
        }
        if ($post_info["prep_time_hours"]) {
            $prep_time .= $post_info["prep_time_hours"] . 'H';
        }
        if ($post_info["prep_time_minutes"]) {
            $prep_time .= $post_info["prep_time_minutes"] . 'M';
        }
        if ($post_info["prep_time_seconds"]) {
            $prep_time .= $post_info["prep_time_seconds"] . 'S';
        }
    } else {
        $prep_time = $post_info["prep_time"];
    }
    
    if ($post_info["cook_time_years"] || $post_info["cook_time_months"] || $post_info["cook_time_days"] || $post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
        $cook_time = 'P';
        if ($post_info["cook_time_years"]) {
            $cook_time .= $post_info["cook_time_years"] . 'Y';
        }
        if ($post_info["cook_time_months"]) {
            $cook_time .= $post_info["cook_time_months"] . 'M';
        }
        if ($post_info["cook_time_days"]) {
            $cook_time .= $post_info["cook_time_days"] . 'D';
        }
        if ($post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
            $cook_time .= 'T';
        }
        if ($post_info["cook_time_hours"]) {
            $cook_time .= $post_info["cook_time_hours"] . 'H';
        }
        if ($post_info["cook_time_minutes"]) {
            $cook_time .= $post_info["cook_time_minutes"] . 'M';
        }
        if ($post_info["cook_time_seconds"]) {
            $cook_time .= $post_info["cook_time_seconds"] . 'S';
        }
    } else {
        $cook_time = $post_info["cook_time"];
    }
    
    if ($post_info["total_time_years"] || $post_info["total_time_months"] || $post_info["total_time_days"] || $post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
        $total_time = 'P';
        if ($post_info["total_time_years"]) {
            $total_time .= $post_info["total_time_years"] . 'Y';
        }
        if ($post_info["total_time_months"]) {
            $total_time .= $post_info["total_time_months"] . 'M';
        }
        if ($post_info["total_time_days"]) {
            $total_time .= $post_info["total_time_days"] . 'D';
        }
        if ($post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
            $total_time .= 'T';
        }
        if ($post_info["total_time_hours"]) {
            $total_time .= $post_info["total_time_hours"] . 'H';
        }
        if ($post_info["total_time_minutes"]) {
            $total_time .= $post_info["total_time_minutes"] . 'M';
        }
        if ($post_info["total_time_seconds"]) {
            $total_time .= $post_info["total_time_seconds"] . 'S';
        }
    } else {
        $total_time = $post_info["total_time"];
    }
        
    $recipe = array (
        "post_id" => $post_info["post_id"],
        "recipe_title" => $post_info["recipe_title"],
        "summary" => $post_info["summary"],
        "rating" => $post_info["rating"],
        "prep_time" => $prep_time,
        "cook_time" => $cook_time,
        "total_time" => $total_time,
        "yield" => $post_info["yield"],
        "serving_size" => $post_info["serving_size"],
        "calories" => $post_info["calories"],
        "fat" => $post_info["fat"],
        "instructions" => $post_info["instructions"],
    );
    
    if (amd_recipeseo_select_recipe_db($recipe_id) == null) {
        $wpdb->insert( $wpdb->prefix . "amd_recipeseo_recipes", $recipe );
        $recipe_id = $wpdb->insert_id;
    } else {
        $wpdb->update( $wpdb->prefix . "amd_recipeseo_recipes", $recipe, array( 'recipe_id' => $recipe_id ));
        $wpdb->query("DELETE FROM " . $wpdb->prefix . "amd_recipeseo_ingredients WHERE recipe_id = '" . $recipe_id . "'");
    }
    
    for ($i = 0; $i < count($post_info["ingredients"]); $i++) {
        if ($post_info["ingredients"][$i]["amount"] != null || $post_info["ingredients"][$i]["name"] != null) {
            $ingredient = array(
                "recipe_id" => $recipe_id,
                "amount" => $post_info["ingredients"][$i]["amount"],
                "name" => $post_info["ingredients"][$i]["name"],
            );
        
            $wpdb->insert( $wpdb->prefix . "amd_recipeseo_ingredients", $ingredient );
        }
    }
        
    return $recipe_id;
}

// Inserts the recipe into the post editor
function amd_recipeseo_plugin_footer() {
    $url = get_option('siteurl');
    $dirname = dirname(plugin_basename(__FILE__));
    
    echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';
    var dirname = '$dirname';
        function amdRecipeseoInsertIntoPostEditor(rid,getoption,dirname) {
            tb_remove();
            
            var ed;
            
            var output = '<img id="amd-recipeseo-recipe-';
            output += rid;
            output += '" class="amd-recipeseo-recipe" src="' + getoption + '/wp-content/plugins/' + dirname + '/recipeseo-placeholder.png" alt="" />';
            
        	
        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if ( typeof edInsertContent == 'function' ) {
        		edInsertContent(edCanvas, output);
        	} else {
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'amd_recipeseo_plugin_footer');

// Converts the image to a recipe for output
function amd_recipeseo_convert_to_recipe($post_text) {
    $output = $post_text;
    $needle_old = 'id="amd-recipeseo-recipe-';
    $preg_needle_old = '/(id)=("(amd-recipeseo-recipe-)[0-9^"]*")/i';
    $needle = '[amd-recipeseo-recipe:';
    $preg_needle = '/\[amd-recipeseo-recipe:([0-9]+)\]/i';
    
    if (strpos($post_text, $needle_old) !== false) {
        // This is for backwards compatability. Please do not delete or alter.
        preg_match_all($preg_needle_old, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('id="amd-recipeseo-recipe-', '', $match);
            $recipe_id = str_replace('"', '', $recipe_id);
            
            $recipe = amd_recipeseo_select_recipe_db($recipe_id);
            $ingredients = amd_recipeseo_select_ingredients_db($recipe_id);
                        
            $formatted_recipe = amd_recipeseo_format_recipe($recipe, $ingredients);

            $output = str_replace('<img class="amd-recipeseo-recipe" id="amd-recipeseo-recipe-' . $recipe_id . '" alt="" src="' . get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/recipeseo-placeholder.png" />', $formatted_recipe, $output);
        }
    }
    
    if (strpos($post_text, $needle) !== false) {
        preg_match_all($preg_needle, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('[amd-recipeseo-recipe:', '', $match);
            $recipe_id = str_replace(']', '', $recipe_id);

            $recipe = amd_recipeseo_select_recipe_db($recipe_id);
            $ingredients = amd_recipeseo_select_ingredients_db($recipe_id);

            $formatted_recipe = amd_recipeseo_format_recipe($recipe, $ingredients);

            $output = str_replace('[amd-recipeseo-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
        }
    }
    
    return $output;
}

add_filter('the_content', 'amd_recipeseo_convert_to_recipe');

// Pulls a recipe from the db
function amd_recipeseo_select_recipe_db($recipe_id) {
    global $wpdb;
    
    $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "amd_recipeseo_recipes WHERE recipe_id=" . $recipe_id);

    return $recipe;
}

// Pulls ingredients from the db
function amd_recipeseo_select_ingredients_db($recipe_id) {
    global $wpdb;
    
    $ingredients = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "amd_recipeseo_ingredients WHERE recipe_id=" . $recipe_id . " ORDER BY ingredient_id");

    return $ingredients;
}

// Formats the recipe for output
function amd_recipeseo_format_recipe($recipe, $ingredients) {
    $output = "";
    $duration = array('y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    
    $output .= '
    <div class="hrecipe">
       <span class="item">
          <p id="recipeseo-title" class="fn">' . $recipe->recipe_title . '</p>
       </span>';
    if ($recipe->summary != null) {
        $output .= '<p id="recipeseo-summary" class="summary">' . $recipe->summary . '</p>';
    }
    if ($recipe->rating != 0) {
        $output .= '<p id="recipeseo-rating" class="review hreview-aggregate">
          <span class="rating">' . $recipe->rating . '</span>
       </p>';
    }
    if ($recipe->prep_time != null) {
        if (class_exists('DateInterval')) {
            try {
                $prep_time_object = new DateInterval($recipe->prep_time);
            
                $prep_time = '';
                foreach ($duration as $abbr => $name) {
                    if ($prep_time_object->$abbr > 0) {
                        $prep_time .= $prep_time_object->$abbr . ' ' . $name;
                        if ($prep_time_object->$abbr > 1) {
                            $prep_time .= 's';
                        }
                        $prep_time .= ', ';
                    }
                }

                $prep_time = trim($prep_time, ' \t,');
            } catch (Exception $e) {
                $prep_time = $recipe->prep_time;
            }
        } else {
            $arr = explode('T', $recipe->prep_time); 
            $arr[0] = str_replace('M', 'X', $arr[0]); 
            $prep_time = implode('T', $arr);

            $prep_time = str_replace('P', '', $prep_time);
            $prep_time = str_replace('Y', ' years, ', $prep_time);
            $prep_time = str_replace('X', ' months, ', $prep_time);
            $prep_time = str_replace('D', ' days, ', $prep_time);
            $prep_time = str_replace('T', '', $prep_time);
            $prep_time = str_replace('H', ' hours, ', $prep_time);
            $prep_time = str_replace('M', ' minutes, ', $prep_time);
            $prep_time = str_replace('S', ' seconds', $prep_time);
            
            $prep_time = trim($prep_time, ' \t,');
        }
        
        $output .= '<p id="recipeseo-prep-time">';
        if (strcmp(get_option('recipeseo_prep_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('recipeseo_prep_time_label') . ' ';
        }
        $output .= '<span class="preptime">' . $prep_time . '<span class="value-title" title="' . $recipe->prep_time . '"><!-- --></span></span></p>';
    }
    if ($recipe->cook_time != null) {
        if (class_exists('DateInterval')) {
            try {
                $cook_time_object = new DateInterval($recipe->cook_time);
            
                $cook_time = '';
                foreach ($duration as $abbr => $name) {
                    if ($cook_time_object->$abbr > 0) {
                        $cook_time .= $cook_time_object->$abbr . ' ' . $name;
                        if ($cook_time_object->$abbr > 1) {
                            $cook_time .= 's';
                        }
                        $cook_time .= ', ';
                    }
                }

                $cook_time = trim($cook_time, ' \t,');
            } catch (Exception $e) {
                $cook_time = $recipe->cook_time;
            }
        } else {
            $arr = explode('T', $recipe->cook_time); 
            $arr[0] = str_replace('M', 'X', $arr[0]); 
            $cook_time = implode('T', $arr);

            $cook_time = str_replace('P', '', $cook_time);
            $cook_time = str_replace('Y', ' years, ', $cook_time);
            $cook_time = str_replace('X', ' months, ', $cook_time);
            $cook_time = str_replace('D', ' days, ', $cook_time);
            $cook_time = str_replace('T', '', $cook_time);
            $cook_time = str_replace('H', ' hours, ', $cook_time);
            $cook_time = str_replace('M', ' minutes, ', $cook_time);
            $cook_time = str_replace('S', ' seconds', $cook_time);
            
            $cook_time = trim($cook_time, ' \t,');
        }
        
        $output .= '<p id="recipeseo-cook-time">';
        if (strcmp(get_option('recipeseo_cook_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('recipeseo_cook_time_label') . ' ';
        }
        $output .= '<span class="cooktime">' . $cook_time . '<span class="value-title" title="' . $recipe->cook_time . '"><!-- --></span></span></p>';
    }
    if ($recipe->total_time != null) {
        if (class_exists('DateInterval')) {
            try {
                $total_time_object = new DateInterval($recipe->total_time);
            
                $total_time = '';
                foreach ($duration as $abbr => $name) {
                    if ($total_time_object->$abbr > 0) {
                        $total_time .= $total_time_object->$abbr . ' ' . $name;
                        if ($total_time_object->$abbr > 1) {
                            $total_time .= 's';
                        }
                        $total_time .= ', ';
                    }
                }

                $total_time = trim($total_time, ' \t,');
            } catch (Exception $e) {
                $total_time = $recipe->total_time;
            }
        } else {
            $arr = explode('T', $recipe->total_time); 
            $arr[0] = str_replace('M', 'X', $arr[0]); 
            $total_time = implode('T', $arr);

            $total_time = str_replace('P', '', $total_time);
            $total_time = str_replace('Y', ' years, ', $total_time);
            $total_time = str_replace('X', ' months, ', $total_time);
            $total_time = str_replace('D', ' days, ', $total_time);
            $total_time = str_replace('T', '', $total_time);
            $total_time = str_replace('H', ' hours, ', $total_time);
            $total_time = str_replace('M', ' minutes, ', $total_time);
            $total_time = str_replace('S', ' seconds', $total_time);
            
            $total_time = trim($total_time, ' \t,');
        }
        
        $output .= '<p id="recipeseo-total-time">';
        if (strcmp(get_option('recipeseo_total_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('recipeseo_total_time_label') . ' ';
        }
        $output .= '<span class="duration">' . $total_time . '<span class="value-title" title="' . $recipe->total_time . '"><!-- --></span></span></p>';
    }
    if ($recipe->yield != null) {
        $output .= '<p id="recipeseo-yield">';
        if (strcmp(get_option('recipeseo_yield_label_hide'), 'Hide') != 0) {
            $output .= get_option('recipeseo_yield_label') . ' ';
        }
        $output .= '<span class="yield">' . $recipe->yield . '</span></p>';
    }
    if ($recipe->serving_size != null || $recipe->calories != null || $recipe->fat != null) {
        $output .= '<div id="recipeseo-nutrition" class="nutrition">';
        if ($recipe->serving_size != null) {
            $output .= '<p id="recipeseo-serving-size">';
            if (strcmp(get_option('recipeseo_serving_size_label_hide'), 'Hide') != 0) {
                $output .= get_option('recipeseo_serving_size_label') . ' ';
            }
            $output .= '<span class="servingsize">' . $recipe->serving_size . '</span></p>';
        }
        if ($recipe->calories != null) {
            $output .= '<p id="recipeseo-calories">';
            if (strcmp(get_option('recipeseo_calories_label_hide'), 'Hide') != 0) {
                $output .= get_option('recipeseo_calories_label') . ' ';
            }
            $output .= '<span class="calories">' . $recipe->calories . '</span></p>';
        }
        if ($recipe->fat != null) {
            $output .= '<p id="recipeseo-fat">';
            if (strcmp(get_option('recipeseo_fat_label_hide'), 'Hide') != 0) {
                $output .= get_option('recipeseo_fat_label') . ' ';
            }
            $output .= '<span class="fat">' . $recipe->fat . '</span></p>';
        }
        $output .= '</div>';
    }
    
    $ingredient_type= '';
    $ingredient_tag = '';
    $ingredient_list_type_option = get_option('recipeseo_ingredient_list_type');
    if (strcmp($ingredient_list_type_option, 'ul') == 0 || strcmp($ingredient_list_type_option, 'ol') == 0) {
        $ingredient_type = $ingredient_list_type_option;
        $ingredient_tag = 'li';
    } else if (strcmp($ingredient_list_type_option, 'p') == 0 || strcmp($ingredient_list_type_option, 'div') == 0) {
        $ingredient_type = 'span';
        $ingredient_tag = $ingredient_list_type_option;
    }
    
    if (strcmp(get_option('recipeseo_ingredient_label_hide'), 'Hide') != 0) {
        $output .= '<p id="recipeseo-ingredients">' . get_option('recipeseo_ingredient_label') . '</p>';
    }
    $output .= '<' . $ingredient_type . ' id="recipeseo-ingredients-list">';
    $i = 0;
    foreach ($ingredients as $ingredient) {
        $output .= '<' . $ingredient_tag . ' id="recipeseo-ingredient-' . $i . '" class="ingredient">';
                $output .= '<span id="recipeseo-ingredient-' . $i . '-amount" class="amount">' . $ingredient->amount . '</span> ';
                $output .= '<span id="recipeseo-ingredient-' . $i . '-name" class="name">' . $ingredient->name . '</span>';
        $output .= '</' . $ingredient_tag . '>';
        $i++;
    }
    $output .= '</' . $ingredient_type . '>';

    if ($recipe->instructions != null) {
        
        $instruction_type= '';
        $instruction_tag = '';
        $instruction_list_type_option = get_option('recipeseo_instruction_list_type');
        if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
            $instruction_type = $instruction_list_type_option;
            $instruction_tag = 'li';
        } else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
            $instruction_type = 'span';
            $instruction_tag = $instruction_list_type_option;
        }
        
        $instructions = explode("\n", $recipe->instructions);
        if (strcmp(get_option('recipeseo_instruction_label_hide'), 'Hide') != 0) {
            $output .= '<p id="recipeseo-instructions">' . get_option('recipeseo_instruction_label') . '</p>';
        }
        $output .= '<' . $instruction_type . ' id="recipeseo-instructions-list" class="instructions">';
        $j = 0;
        foreach ($instructions as $instruction) {
            if (strlen($instruction) > 1) {            
                $output .= '<' . $instruction_tag . ' id="recipeseo-instruction-' . $j . '" class="instruction">';
                    $output .= $instruction;
                $output .= '</' . $instruction_tag . '>';
                $j++;
            }
        }
        $output .= '</' . $instruction_type . '>';
    }

    $output .= '</div>';
    
    return $output;
}
