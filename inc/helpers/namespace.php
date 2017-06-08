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
function load_template( $path, array $vars = [] ) {

	if ( ! file_exists( $path ) ) {
		throw new \Exception( "File not found: $path" );
	}

	ob_start();
	extract( $vars ); // @codingStandardsIgnoreLine
	include( $path );
	$output = ob_get_contents();
	if ( ob_get_length() ) {
		ob_end_clean();
	}

	return $output;
}

// --------------------------------------------------------------------------------------------------------------------
// Activation & Deactivation
// --------------------------------------------------------------------------------------------------------------------

/**
 * Return the current version of the stats database
 *
 * @return string
 */
function get_db_version() {
	return '1.5';
}

/**
 * Return the current name of the stats table
 *
 * @return string
 */
function get_stats_table() {
	/** @var $wpdb \wpdb */
	global $wpdb;
	return "{$wpdb->base_prefix}pressbooks_stats_exports";
}

/**
 * Create a new table for stats
 */
function install() {

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$installed_ver = get_option( 'pb_stats_db_version' );
	$db_version = get_db_version();

	if ( $installed_ver !== $db_version ) {

		rename_table();

		$table_name = get_stats_table();
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

/**
 * Rename old hard coded table
 */
function rename_table() {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$old_table_name = 'wp_pressbooks_stats_exports';

	if ( get_stats_table() === $old_table_name ) {
		// The old and new table names are the same, ignore
		return;
	}

	// @codingStandardsIgnoreStart
	$checkIfOldTableExistsSql = "SELECT 1 FROM {$old_table_name} LIMIT 1 ";
	$wpdb->get_results( $checkIfOldTableExistsSql, ARRAY_A );
	if ( ! $wpdb->last_error ) {
		// The old hard coded table exists, rename it
		$renameTableSql = "RENAME TABLE {$old_table_name} TO " . get_stats_table();
		$wpdb->query( $renameTableSql );
	}
	// @codingStandardsIgnoreEnd
}
