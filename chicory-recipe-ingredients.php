<?php
/*
Plugin Name: Chicory Recipe Ingredients
Plugin URI: http://www.chicoryapp.com/
Description: Chicory connects your recipes directly to leading online grocery stores. The Chicory recipe plugin displays a “get ingredients delivered’ button before or after your recipe, letting  readers cook the  recipes they discover on your site without even having to make a trip to the grocery store.
Version: 1.0
Text Domain: chicory-recipe
Author: Chicory
Author URI: http://www.chicoryapp.com/
*/

define( 'CHICORY_PLUGIN_URL', 			plugin_dir_url( __FILE__ ) );
define( 'CHICORY_PLUGIN_PATH',			plugin_dir_path( __FILE__ ) );
define( 'CHICORY_PLUGIN_BASENAME', 		plugin_basename( __FILE__ ) );
define( 'CHICORY_PLUGIN_REL_DIR', 		dirname( PLUGIN_BASENAME ) );

register_activation_hook( __FILE__, 'chicory_activate' );	
register_deactivation_hook( __FILE__,'chicory_deactivate' );
register_uninstall_hook( __FILE__, 'chicory_uninstall'  );

/**
*  Activate Plugin : When activate plugin then tabel created and default value added in database.
*
* Function Name: chicory_activate
*
**/

function chicory_activate() {			
	add_option( 'chicory_location_button','top','', 'yes' );
}

/**
*  Deactivate Plugin : When deactivate plugin then default value deleted in database.
*
* Function Name: chicory_deactivate
*
**/
	 
function chicory_deactivate(){
	delete_option( 'chicory_location_button' );
}
	
/**
*  Uninstall Plugin : When uninstall plugin default value deleted in database.
*
* Function Name: chicory_uninstall
*
**/
		
function chicory_uninstall(){	
	delete_option( 'chicory_location_button' );
}

/**
* When plugin loaded then all files called.
*
* Function Name: chicory_plugin_load_function.
*
**/

add_action( 'plugins_loaded','chicory_plugin_load_function' );
function chicory_plugin_load_function(){
	if ( is_admin() ) {
		add_action( 'admin_menu', 'chicory_admin_menu' );
	} else {
		if(get_option( 'chicory_location_button' ) == 'top') {
			add_filter( 'the_content', 'chicory_top_recipe_display',1 );
		} else if(get_option( 'chicory_location_button' ) == 'bottom') {
			add_filter( 'the_content', 'chicory_bottom_recipe_display' ,1);
		} else if(get_option( 'chicory_location_button' ) == 'shortcode') {
			add_shortcode( 'chicory', 'chicory_shortcode_recipe_display' );
		}
		add_action( 'wp_enqueue_scripts', 'chicory_scripts' );
	}	
}

/**
* When plugin loaded then all script also loaded.
*
* Function Name: chicory_scripts.
*
**/
function chicory_scripts() {
	wp_enqueue_script( 'chicory-script', 'http://chicoryapp.com/widget/', array(), '', true );
}

/**
* Chicory Recipe Ingredients Settings Menu.
*
* Function Name: chicory_admin_menu.
*
**/
function chicory_admin_menu() { 
	add_menu_page('Chicory Recipe Ingredients', 'Chicory', 'administrator', __FILE__, 'chicory_settings_page',plugins_url('/icon/icon.png', __FILE__));

	add_action( 'admin_init', 'chicory_register_settings' );
}

/**
* Register Settings.
*
* Function Name: chicory_register_settings.
*
**/
function chicory_register_settings() {
  register_setting( 'chicory-settings-group', 'chicory_location_button' );
}

/**
* Chicory Recipe Ingredients Settings Page.
*
* Function Name: chicory_settings_page.
*
**/
function chicory_settings_page() { ?>
	<div class="wrap">
	<h2><?php _e( 'Chicory Recipe Ingredients', 'chicory-recipe' ) ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'chicory-settings-group' ); 
		do_settings_sections( 'chicory-settings-group' ); 
		get_option( 'chicory_location_button' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'What location would you like to display the Chicory button?', 'chicory-recipe' ) ?></th>
			<tr>
			<tr>
				<td>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="top" <?php echo ((get_option('chicory_location_button') == 'top') ? 'checked="checked"' : '') ?> /><?php _e( 'Top of recipe', 'chicory-recipe' ) ?><br/><br/>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="bottom" <?php echo ((get_option('chicory_location_button') == 'bottom') ? 'checked="checked"' : '') ?> /><?php _e( 'Bottom of recipe', 'chicory-recipe' ) ?><br/><br/>
					<input type="radio" id="chicory_location_button" name="chicory_location_button" value="shortcode" <?php echo ((get_option('chicory_location_button') == 'shortcode') ? 'checked="checked"' : '') ?> /><?php _e( 'ShortCode* [chicory]', 'chicory-recipe' ) ?>
					<p><?php _e( 'Note: * this option lets you have full control over the location of the button. However, it requires to put the short code [chicory] in every post or install it in your template. contact Chicory for the best place to install it in your template.', 'chicory-recipe' ) ?></p>
				</td>
			</tr>
			</tr>
		</table> 
		<?php submit_button(); ?>
	</form>
</div>
<?php } 

/**
* Chicory Recipe Ingredients Top Content Page Display.
*
* Function Name: chicory_top_recipe_display.
*
**/
function chicory_top_recipe_display( $content ) {
   if(is_single()) {
      $custom_content = '<div class="chicory-order-ingredients"></div>';
      $custom_content .= $content;
   }
   return $custom_content;
}

/**
* Chicory Recipe Ingredients Bottom Content Page Display.
*
* Function Name: chicory_bottom_recipe_display.
*
**/
function chicory_bottom_recipe_display( $content ) {
   if(is_single()) {
     	$content.= '<div class="chicory-order-ingredients"></div>';
   }
   return $content;
}

/**
* Chicory Recipe Ingredients Shortcode Content Page Display.
*
* Function Name: chicory_shortcode_recipe_display.
*
**/
function chicory_shortcode_recipe_display() {
   $content = '<div class="chicory-order-ingredients"></div>';
   return $content;
}
?>