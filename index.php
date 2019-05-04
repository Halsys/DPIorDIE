<?php
/**
 * DPIorDie
 * Saves and discards uplouds based on DPI.
 * @package	 DPIorDie
 * @author		Cory Null(Noll) Crimmins - Golden <cory190@live.com>
 * @license	 GPL-3.0+
 * @link			http://wordpress.org/plugins
 * @copyright 2014 Cory Null(Noll) Crimmins - Golden
 * @wordpress-plugin
 * Plugin Name:				DPIorDie
 * Plugin URI:				http://wordpress.org/plugins
 * Description:				Saves and discards uplouds based on DPI.
 * Version:						1.0.0
 * Author:						Cory Null(Noll) Crimmins - Golden
 * Author URI:				http://mountainvalley.today/
 * Text Domain:				dpi-or-die
 * License:						GPL-3.0+
 * License URI:				http://www.gnu.org/licenses/gpl.txt
 * Domain Path:				/languages
 * GitHub Plugin URI:
 */

// If this file is called directly, abort.
if ( ! defined( "WPINC" ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once(
	plugin_dir_path( __FILE__ ) .
	"public/class-dpi-or-die.php"
);

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook(
	__FILE__,
	array("DPIorDie", "activate")
);
register_deactivation_hook(
	__FILE__,
	array("DPIorDie", "deactivate")
);

add_action(
	"plugins_loaded",
	array("DPIorDie", "get_instance")
);

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *	 ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( "DOING_AJAX" ) || ! DOING_AJAX ) ) {
	require_once(
		plugin_dir_path( __FILE__ ) .
		"admin/class-dpi-or-die-admin.php"
	);
	add_action(
		"plugins_loaded",
		array("DPIorDie_Admin", "get_instance")
	);
}
