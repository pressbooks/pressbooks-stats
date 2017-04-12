<?php
/*
Plugin Name: Pressbooks Stats
Description: Pressbooks plugin which provides some basic activity statistics for a Pressbooks network.
Version: 1.2.1
Author: BookOven Inc.
*/

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks' ) . '</p></div>';
	} );
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

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

function _pressbooks_stats_autoload( $class_name ) {

	$prefix = 'PressbooksStats\\';
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
		// Ignore classes not in our namespace
		return;
	}

	$parts = explode( '\\', strtolower( $class_name ) );
	array_shift( $parts );
	$class_file = 'class-pb-' . str_replace( '_', '-', array_pop( $parts ) ) . '.php';
	$path = count( $parts ) ? implode( '/', $parts ) . '/' : '';
	require( PB_STATS_PLUGIN_DIR . 'includes/' . $path . $class_file );
}

spl_autoload_register( '_pressbooks_stats_autoload' );

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( PB_STATS_PLUGIN_DIR . 'includes/pb-helpers.php' );
require( PB_STATS_PLUGIN_DIR . 'includes/pb-stats.php' );

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

// Activate
register_activation_hook( __FILE__, '\PressbooksStats\Helpers\install' );

// Stats
add_action( 'pressbooks_track_export', '\PressbooksStats\Stats\track_export' );
add_action( 'admin_init', '\PressbooksStats\Stats\init_css_js' );
add_action( 'network_admin_menu', '\PressbooksStats\Stats\menu' );
