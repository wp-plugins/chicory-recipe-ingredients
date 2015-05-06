<?php
/*
Plugin Name: Chicory Recipe Ingredients
Plugin URI: http://chicoryapp.com/
Description: Chicory connects your recipes directly to leading online grocery stores. The Chicory recipe plugin displays a “get ingredients delivered’ button before or after your recipe, letting  readers cook the  recipes they discover on your site without even having to make a trip to the grocery store.
Version: 1.9
Text Domain: chicory-recipe
Author: Chicory
Author URI: http://chicoryapp.com/
*/

define('CHICORY_PLUGIN_VERSION', 		1.9);
define('CHICORY_PLUGIN_URL', 			plugin_dir_url(__FILE__));
define('CHICORY_PLUGIN_PATH',			plugin_dir_path(__FILE__));
define('CHICORY_PLUGIN_BASENAME', 		plugin_basename(__FILE__));
define('CHICORY_PLUGIN_REL_DIR', 		dirname(PLUGIN_BASENAME));
define('CHICORY_WIDGET_DOMAIN',         'www.chicoryapp.com');

define('BUTTON_LOCATION_BELOW_INGREDIENTS', 'below-ingredient');
define('BUTTON_LOCATION_BELOW_RECIPE',      'below-recipe');
define('BUTTON_LOCATION_BELOW_POST',        'below-post');

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
    if (extensions_available()) {
        $location = get_option('chicory_location_button', BUTTON_LOCATION_BELOW_INGREDIENTS);
    }
    else {
        $location = BUTTON_LOCATION_BELOW_POST;
    }
    ?>
    <div class="wrap">
        <h2>Chicory Recipe Ingredients</h2>
        <?php
        if (!extensions_available()) {
            ?>
            <div style="background-color: #FFBABA; color: #D8000C; border: 1px solid #ff0000; padding: 10px">
                <h4>The Chicory Recipe Ingredients plugins requires PHP 5.2 and above, as well as the following PHP extensions to work correctly.</h4>
                <ul style="list-style-type: circle; padding-left: 20px">
                    <li>php-libxml</li>
                    <li>php-dom</li>
                    <li>php-mbstring</li>
                </ul>
            </div>
        <?php
        }
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('chicory-settings-group') ?>
            <?php do_settings_sections('chicory-settings-group') ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Where would you like to display the Chicory button?</th>
                </tr>
                <tr>
                    <td>
                        <input type="radio" id="chicory_location_button" name="chicory_location_button"
                               value="<?php echo BUTTON_LOCATION_BELOW_INGREDIENTS ?>"
                            <?php echo ( $location == BUTTON_LOCATION_BELOW_INGREDIENTS ) ? 'checked="checked"' : '' ?>
                            <?php echo ( !extensions_available() ) ? 'disabled' : '' ?> />
                        Below Ingredient List<br/><br/>

                        <input type="radio" id="chicory_location_button" name="chicory_location_button"
                               value="<?php echo BUTTON_LOCATION_BELOW_RECIPE ?>"
                            <?php echo ( $location == BUTTON_LOCATION_BELOW_RECIPE ) ? 'checked="checked"' : '' ?>
                            <?php echo ( !extensions_available() ) ? 'disabled' : '' ?> />
                        Below Recipe<br/><br/>

                        <input type="radio" id="chicory_location_button" name="chicory_location_button"
                               value="<?php echo BUTTON_LOCATION_BELOW_POST ?>"
                            <?php echo ( $location == BUTTON_LOCATION_BELOW_POST ) ? 'checked="checked"' : '' ?>
                            <?php echo ( !extensions_available() ) ? 'disabled' : '' ?> />
                        Bottom of Post<br/><br/>
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
    $version = explode('-', phpversion());
    $version = array_shift($version);
    wp_enqueue_script('chicory-script', 'http://'. CHICORY_WIDGET_DOMAIN . '/widget_v2/?php=' . $version .'&plugin=' . CHICORY_PLUGIN_VERSION, array(), '', true);
}

function chicory_display($content) {
    // Check that necessary extensions are present
    if (!extensions_available()) {
        return $content
        . '<div class="chicory-order-ingredients-container" style="margin-top:10px !important">'
        .   '<div class="chicory-order-ingredients"></div>'
        . '</div>'
            ;
    }

    libxml_use_internal_errors(true);
    $location = get_option('chicory_location_button', BUTTON_LOCATION_BELOW_INGREDIENTS);
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

function extensions_available() {
    $php_version = phpversion();
    $major = $php_version[0];
    $minor = $php_version[2];
    $version = (float) $major . '.' . $minor;

    return ($version >= 5.2) && extension_loaded('libxml') && extension_loaded('dom') && extension_loaded('mbstring');
}

register_activation_hook(__FILE__, 'chicory_activate');
register_deactivation_hook(__FILE__,'chicory_deactivate');
register_uninstall_hook(__FILE__, 'chicory_uninstall' );
add_action('plugins_loaded','chicory_plugin_load_function');