<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://donatestuffdev.com
 * @since             1.0.0
 * @package           Mailchimp_master
 *
 * @wordpress-plugin
 * Plugin Name:       Mailchimp Master
 * Plugin URI:        mailchimp_master
 * Description:       Customized mailchimp plugin for Donate stuff.
 * Version:           1.0.0
 * Author:            Donatestuff
 * Author URI:        https://donatestuffdev.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp_master
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MAILCHIMP_MASTER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailchimp_master-activator.php
 */
function activate_mailchimp_master() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp_master-activator.php';
	Mailchimp_master_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailchimp_master-deactivator.php
 */
function deactivate_mailchimp_master() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp_master-deactivator.php';
	Mailchimp_master_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mailchimp_master' );
register_deactivation_hook( __FILE__, 'deactivate_mailchimp_master' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp_master.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mailchimp_master() {

	$plugin = new Mailchimp_master();
	$plugin->run();

}
run_mailchimp_master();
