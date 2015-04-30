<?php
/*
Plugin Name: Chicory Recipe Ingredients
Plugin URI: http://chicoryapp.com/
Description: Chicory connects your recipes directly to leading online grocery stores. The Chicory recipe plugin displays a “get ingredients delivered’ button before or after your recipe, letting  readers cook the  recipes they discover on your site without even having to make a trip to the grocery store.
Version: 1.5
Text Domain: chicory-recipe
Author: Chicory
Author URI: http://chicoryapp.com/
*/

define('CHICORY_PLUGIN_URL', 			plugin_dir_url(__FILE__));
define('CHICORY_PLUGIN_PATH',			plugin_dir_path(__FILE__));
define('CHICORY_PLUGIN_BASENAME', 		plugin_basename(__FILE__));
define('CHICORY_PLUGIN_REL_DIR', 		dirname(PLUGIN_BASENAME));
define('CHICORY_WIDGET_DOMAIN',         'www.chicoryapp.com');

const BUTTON_LOCATION_BELOW_INGREDIENTS = 'below-ingredient';
const BUTTON_LOCATION_BELOW_RECIPE      = 'below-recipe';
const BUTTON_LOCATION_BELOW_POST        = 'below-post';

function chicory_activate() {			
	add_option('chicory_location_button', BUTTON_LOCATION_BELOW_INGREDIENTS, '', 'yes');
}

function chicory_deactivate() {
	delete_option('chicory_location_button');
}

function chicory_uninstall() {
	delete_option('chicory_location_button');
}

function chicory_admin_menu() { 
	add_menu_page('Chicory Recipe Ingredients', 'Chicory', 'administrator', __FILE__, 'chicory_settings_page', plugins_url('/icon/icon.png', __FILE__), 82);
	add_action('admin_init', 'chicory_register_settings');
}

function chicory_settings_page() {
    $option = get_option('chicory_location_button') ?: BUTTON_LOCATION_BELOW_INGREDIENTS;
?>
<div class="wrap">
	<h2>Chicory Recipe Ingredients</h2>
	<form method="post" action="options.php">
		<?php settings_fields('chicory-settings-group') ?>
		<?php do_settings_sections('chicory-settings-group') ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">What location would you like to display the Chicory button?</th>
			</tr>
			<tr>
				<td>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="<?php echo BUTTON_LOCATION_BELOW_INGREDIENTS ?>" <?php echo ($option == BUTTON_LOCATION_BELOW_INGREDIENTS) ? 'checked="checked"' : '' ?> />Below Ingredient List<br/><br/>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="<?php echo BUTTON_LOCATION_BELOW_RECIPE ?>" <?php echo ($option == BUTTON_LOCATION_BELOW_RECIPE) ? 'checked="checked"' : '' ?> />Below Recipe<br/><br/>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="<?php echo BUTTON_LOCATION_BELOW_POST ?>" <?php echo ($option == BUTTON_LOCATION_BELOW_POST) ? 'checked="checked"' : '' ?> />Bottom of Post<br/><br/>
				</td>
			</tr>
			</tr>
		</table>
		<?php submit_button() ?>
	</form>
</div>
<?php
}

function chicory_register_settings() {
    register_setting('chicory-settings-group', 'chicory_location_button');
}

function chicory_scripts() {
    wp_enqueue_script('chicory-script', 'http://'. CHICORY_WIDGET_DOMAIN . '/widget_v2/', array(), '', true);
}

function chicory_display($content) {
    // Check that necessary extensions are present
    if (!extension_loaded('libxml') || !extension_loaded('dom') || !extension_loaded('mbstring')) {
        return $content;
    }

    libxml_use_internal_errors(true);
    $location = get_option('chicory_location_button') ?: BUTTON_LOCATION_BELOW_INGREDIENTS;
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"));
    $xpath = new DOMXPath($doc);

    // Chicory container
    $buttonContainer = $doc->createElement('div');
    $buttonContainer->setAttribute('class', 'chicory-order-ingredients-container');
    $buttonContainer->setAttribute('style', 'margin-top:10px !important');

    // Microdata format (Easyrecipe, Recipe Card)
    if ($xpath->query('//*[contains(@itemtype, "//schema.org/Recipe") or contains(@itemtype, "//schema.org/recipe")]')->length) {

        if ($location == BUTTON_LOCATION_BELOW_INGREDIENTS) {
            $node = $xpath->query('//*[@itemprop="ingredients"]/..')->item(0);
            $node->parentNode->insertBefore($buttonContainer, $node->nextSibling);
        }

        if ($location == BUTTON_LOCATION_BELOW_RECIPE) {
            $node = $xpath->query('//*[contains(@itemtype, "//schema.org/Recipe") or contains(@itemtype, "//schema.org/recipe")]')->item(0);
            $node->appendChild($buttonContainer);
        }

        if ($location == BUTTON_LOCATION_BELOW_POST) {
            $doc->appendChild($buttonContainer);
        }
    }
    else {
        $doc->appendChild($buttonContainer);
    }

    $button = $buttonContainer->appendChild($doc->createElement('div'));
    $button->setAttribute('class', 'chicory-order-ingredients');

    return $doc->saveHTML();
}

function chicory_plugin_load_function() {
    if (is_admin()) {
        add_action('admin_menu', 'chicory_admin_menu');
    }
    else {
        add_action('wp_enqueue_scripts', 'chicory_scripts');
        add_filter('the_content', 'chicory_display', 20);
    }
}

register_activation_hook(__FILE__, 'chicory_activate');
register_deactivation_hook(__FILE__,'chicory_deactivate');
register_uninstall_hook(__FILE__, 'chicory_uninstall' );
add_action('plugins_loaded','chicory_plugin_load_function');
