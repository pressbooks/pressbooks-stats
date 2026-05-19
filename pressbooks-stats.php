<?php
/**
 * Plugin Name: Pressbooks Stats
 * Description: Pressbooks plugin which provides some basic activity statistics for a Pressbooks network.
 * Requires at least: WordPress 6.5
 * Requires Plugins: pressbooks
 * x-release-please-start-version
 * Version: 1.10.1
 * x-release-please-end
 * Pressbooks tested up to: 6.18.0
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.org
 * Text Domain: pressbooks-stats
 * Domain Path: /languages
 * Network: True
 * License: GPL v3 or later
 *
 * @package Pressbooks_Stats
 * @author Pressbooks (Book Oven Inc.)
 * @license GPL-3.0-or-later
 */

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'PB_STATS_PLUGIN_DIR' ) ) {
	define( 'PB_STATS_PLUGIN_DIR', __DIR__ . '/' ); // Must have trailing slash!
}

if ( ! defined( 'PB_STATS_PLUGIN_URL' ) ) {
	define( 'PB_STATS_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Must have trailing slash!
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksStats', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( PB_STATS_PLUGIN_DIR . 'inc/helpers/namespace.php' );
require( PB_STATS_PLUGIN_DIR . 'inc/stats/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

// Activate
register_activation_hook( __FILE__, '\PressbooksStats\Helpers\install' );

// Stats
add_action( 'pressbooks_track_export', '\PressbooksStats\Stats\track_export' );
add_action( 'admin_init', '\PressbooksStats\Stats\init_css_js' );
add_action( 'network_admin_menu', '\PressbooksStats\Stats\menu' );
if ( ! defined( 'PB_DISABLE_NETWORK_STORAGE' ) || ! PB_DISABLE_NETWORK_STORAGE ) {
	add_action( 'mu_rightnow_end', '\PressbooksStats\Stats\display_network_storage' );
}

add_action('init', function () {
	load_plugin_textdomain( 'pressbooks-stats', false, 'pressbooks-stats/languages' );
});
