<?php

namespace PressbooksStats\Helpers;

/**
 * Simple template system.
 *
 * @param string $path
 * @param array $vars (optional)
 *
 * @return string
 * @throws \Exception
 */
function load_template( $path, array $vars = array() ) {

	if ( ! file_exists( $path ) ) {
		throw new \Exception( "File not found: $path" );
	}

	ob_start();
	extract( $vars ); // @codingStandardsIgnoreLine
	include( $path );
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

// --------------------------------------------------------------------------------------------------------------------
// Activation & Deactivation
// --------------------------------------------------------------------------------------------------------------------

function install() {

	$db_version = '1.4';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$table_name = 'wp_pressbooks_stats_exports';
	$installed_ver = get_option( 'pb_stats_db_version' );

	if ( $installed_ver != $db_version ) {
		$sql = "CREATE TABLE {$table_name} (
               id mediumint(9) NOT NULL AUTO_INCREMENT,
               blog_id mediumint(9) NOT NULL,
               time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
               export_type VARCHAR(16) NOT NULL,
               user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
               theme VARCHAR(32) NOT NULL,
               PRIMARY KEY  id (id),
               KEY `export_theme` (`export_type`,`theme`),
               KEY `time` (`time`)
               ); ";

		dbDelta( $sql );
		update_option( 'pb_stats_db_version', $db_version );
	}

}
