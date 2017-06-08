<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( "Run this script with WP-CLI: `wp eval-file bin/cache.php` \n" );
}

set_time_limit( 0 );

$cache_key = \PressbooksStats\Stats\get_admin_page_html_cache_key();

delete_site_transient( $cache_key );

\PressbooksStats\Stats\cache_stats_admin_page();

$html = get_site_transient( \PressbooksStats\Stats\get_admin_page_html_cache_key() );

if ( empty( $html ) ) {
	echo "Failed to cache the stats dashboard... \n";
} else {
	echo "Successfully cached the stats dashboard! \n";
}
