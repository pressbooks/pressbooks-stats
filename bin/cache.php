<?php

if ( ! defined( 'WP_CLI' ) ) {
	$script_name = basename( $argv[0] );
	die( "Run this script with WP-CLI: `wp eval-file bin/$script_name` \n" );
}

set_time_limit( 0 );

// Stats page

$cache_key = \PressbooksStats\Stats\get_admin_page_html_cache_key();

delete_site_transient( $cache_key );

\PressbooksStats\Stats\cache_stats_admin_page();

$html = get_site_transient( \PressbooksStats\Stats\get_admin_page_html_cache_key() );

if ( empty( $html ) ) {
	echo "Failed to cache the stats dashboard... \n";
} else {
	echo "Successfully cached the stats dashboard! \n";
}

// Network storage
if ( ! defined( 'PB_DISABLE_NETWORK_STORAGE' ) || ! PB_DISABLE_NETWORK_STORAGE ) {
	$cache_key = 'pb_stats_network_storage';

	delete_site_transient( $cache_key );

	\PressbooksStats\Stats\cache_network_storage();

	$storage = get_site_transient( $cache_key );

	if ( empty( $storage ) ) {
		echo "Failed to cache network storage data... \n";
	} else {
		echo "Successfully cached network storage data! \n";
	}
}
