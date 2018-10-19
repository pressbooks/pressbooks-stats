<?php

namespace PressbooksStats\Stats;

use function Pressbooks\Utility\format_bytes;

/**
 * WP hook for our very own pressbooks_track_export action
 *
 * @param string $export_type
 */
function track_export( $export_type ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$wpdb->insert(
		\PressbooksStats\Helpers\get_stats_table(),
		[
			'user_id' => get_current_user_id(),
			'blog_id' => get_current_blog_id(),
			'time' => date( 'Y-m-d H:i:s' ),
			'export_type' => $export_type,
			'theme' => '' . wp_get_theme(), // Stringify by appending to empty string
		],
		[ '%d', '%d', '%s', '%s', '%s' ]
	);
}


/**
 * Register graphic libraries and css
 */
function init_css_js() {

	wp_register_script( 'pb-vip-stats-1', PB_STATS_PLUGIN_URL . 'symbionts/visualize/js/visualize.jQuery.js', [ 'jquery' ] );
	wp_register_style( 'pb-vip-stats-2', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/basic.css' );
	wp_register_style( 'pb-vip-stats-3', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/visualize.css' );
	wp_register_style( 'pb-vip-stats-4', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/visualize-light.css' );

	wp_register_script( 'pb-vip-stats-5', PB_STATS_PLUGIN_URL . 'assets/js/graphs.js', [ 'pb-vip-stats-1' ], '20130718' );
	wp_register_style( 'pb-vip-stats-6', PB_STATS_PLUGIN_URL . 'assets/css/stats.css', [], '20130718' );
}


/**
 * Add a top level menu item
 */
function menu() {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$user = wp_get_current_user();

	$restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers' " );
	if ( $restricted ) {
		$restricted = maybe_unserialize( $restricted[0]->meta_value );
	} else {
		$restricted = [];
	}

	if ( ! in_array( $user->ID, $restricted ) ) { // @codingStandardsIgnoreLine
		$page = add_menu_page(
			'Pressbooks Statistics',
			'PB Stats',
			'manage_network',
			'pb_stats',
			__NAMESPACE__ . '\display_stats_admin_page',
			'dashicons-chart-pie'
		);

		add_action(
			'admin_enqueue_scripts', function ( $hook ) use ( $page ) {

				if ( $hook === $page ) {
					wp_enqueue_script( 'pb-vip-stats-1' );
					wp_enqueue_style( 'pb-vip-stats-2' );
					wp_enqueue_style( 'pb-vip-stats-3' );
					wp_enqueue_style( 'pb-vip-stats-4' );
					wp_enqueue_script( 'pb-vip-stats-5' );
					wp_enqueue_style( 'pb-vip-stats-6' );
				}
			}
		);
	}

}


/**
 * @return string
 */
function get_admin_page_html_cache_key() {
	return 'pb_stats_admin_page_html';
}


/**
 * Echo stats dashboard
 */
function display_stats_admin_page() {

	$html = get_site_transient( get_admin_page_html_cache_key() );
	if ( ! empty( $html ) ) {
		echo "<!-- CACHED -->{$html}";
	} else {
		$html = generate_stats_admin_page();
		echo $html;
	}
}

/**
 * Generate the stats dashboard
 *
 * @return string HTML
 */
function generate_stats_admin_page() {

	if ( ! get_transient( 'pb_stats_generating_admin_page' ) ) {
		set_transient( 'pb_stats_generating_admin_page', 1, 5 * MINUTE_IN_SECONDS );
		// Unoptimized SQL ahead!
		$vars = [
			'totals' => query_totals(),
			'books_exported_today' => query_books_exported( '24 HOUR' ),
			'users_exported_today' => query_users_exported( '24 HOUR' ),
			'books_exported_month' => query_books_exported( '1 MONTH', true ),
			'users_exported_month' => query_users_exported( '1 MONTH', true ),
			'users_with_5_or_more_books' => users_with_x_or_more_books( 5 ),
			'sites' => query_sites_stats( 'blog_id' ),
			'users' => query_user_stats( 'ID' ),
			'export_types' => query_export_stats( 'export_type' ),
			'export_themes' => query_export_stats( 'theme' ),
			'recents' => query_last_100(),
		];

		$html = \PressbooksStats\Helpers\load_template( PB_STATS_PLUGIN_DIR . 'templates/stats.php', $vars );
		delete_transient( 'pb_stats_generating_admin_page' );
		return $html;
	}
	return __( 'Calculating stats&hellip;', 'pressbooks-stats' );
}

/**
 * Cache the stats stats dashboard
 */
function cache_stats_admin_page() {
	set_site_transient( get_admin_page_html_cache_key(), generate_stats_admin_page() );
}

// -------------------------------------------------------------------------------------------------------------------
// SQL Helpers
// -------------------------------------------------------------------------------------------------------------------

function query_totals() {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$foo = [];

	// Sites

	$tmp = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$wpdb->blogs} ", ARRAY_A );
	$foo['sites']['total'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$wpdb->blogs} WHERE spam = 1 ", ARRAY_A );
	$foo['sites']['spam'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$wpdb->blogs} WHERE ( deleted = 1 OR archived = '1') AND ( spam = 0 ) ", ARRAY_A );
	$foo['sites']['deactivated'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Users

	$tmp = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$wpdb->users} ", ARRAY_A );
	$foo['users']['total'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$wpdb->users} WHERE spam = 1 ", ARRAY_A );
	$foo['users']['spam'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function query_last_100() {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$stats = \PressbooksStats\Helpers\get_stats_table();
	$sql = "SELECT {$wpdb->blogs}.domain, {$wpdb->blogs}.path,
                   stats.blog_id, stats.time, stats.export_type, stats.user_id, stats.theme,
                   {$wpdb->users}.user_login, {$wpdb->users}.user_email
              FROM {$stats} AS stats
              JOIN {$wpdb->blogs} ON stats.blog_id = {$wpdb->blogs}.blog_id
         LEFT JOIN {$wpdb->users} ON (stats.user_id = {$wpdb->users}.ID)
         ORDER BY stats.time DESC
             LIMIT 100 ";

	$foo = $wpdb->get_results( $sql, ARRAY_A );

	foreach ( $foo as $key => $val ) {

		$sql = "SELECT option_value FROM {$wpdb->base_prefix}{$val['blog_id']}_options WHERE option_name = 'blogname' LIMIT 1 ";
		$tmp = $wpdb->get_results( $sql, ARRAY_A );

		if ( isset( $tmp[0]['option_value'] ) ) {
			$foo[ $key ]['blogname'] = $tmp[0]['option_value'];
		} else {
			$foo[ $key ]['blogname'] = '__unknown__';
		}
	}

	return $foo;
}


function query_books_exported( $interval, $just_the_count = false ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = \PressbooksStats\Helpers\get_stats_table();
	$time = 'time';
	$col = 'blog_id';

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL {$interval} )
	GROUP BY {$col} ORDER BY total DESC ";
	$foo = $wpdb->get_results( $sql, ARRAY_A );

	if ( false === (bool) $just_the_count ) {

		foreach ( $foo as $key => $val ) {

			$sql = "SELECT option_value FROM {$wpdb->base_prefix}{$val['blog_id']}_options WHERE option_name = 'blogname' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );

			if ( isset( $tmp[0]['option_value'] ) ) {
				$foo[ $key ]['blogname'] = $tmp[0]['option_value'];
			} else {
				$foo[ $key ]['blogname'] = '__unknown__';
			}

			$sql = "SELECT option_value FROM {$wpdb->base_prefix}{$val['blog_id']}_options WHERE option_name = 'blog_public' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );
			$foo[ $key ]['blog_public'] = isset( $tmp[0]['option_value'] ) ? $tmp[0]['option_value'] : null;

			$sql = "SELECT option_value FROM {$wpdb->base_prefix}{$val['blog_id']}_options WHERE option_name = 'pressbooks_upgrade_level' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );
			$foo[ $key ]['pressbooks_upgrade_level'] = isset( $tmp[0]['option_value'] ) ? $tmp[0]['option_value'] : null;

		}
	}

	return $foo;

}

function query_users_exported( $interval, $just_the_count = false ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = \PressbooksStats\Helpers\get_stats_table();
	$time = 'time';
	$col = 'user_id';

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL {$interval} )
	GROUP BY {$col} ORDER BY total DESC ";
	$foo = $wpdb->get_results( $sql, ARRAY_A );

	if ( false === (bool) $just_the_count ) {

		$is_new = [];
		$sql = "SELECT ID FROM {$wpdb->users}
        WHERE user_registered > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
		$bar = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $bar as $val ) {
			$is_new[ $val['ID'] ] = true;
		}
		unset( $bar );

		foreach ( $foo as $key => $val ) {
			$user_info = get_userdata( $val['user_id'] );
			$foo[ $key ]['username'] = $user_info->user_login;
			$foo[ $key ]['user_email'] = $user_info->user_email;
			$foo[ $key ]['is_new'] = isset( $is_new[ $val['user_id'] ] ) ? true : false;
		}
	}

	return $foo;

}


function query_export_stats( $col ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = \PressbooksStats\Helpers\get_stats_table();
	$time = 'time';

	$foo = [];
	$bar = [];

	// today, week, month, quarter

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR)
	GROUP BY {$col} ORDER BY {$col} ";
	$foo['today'] = $wpdb->get_results( $sql, ARRAY_A );

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > (DATE(NOW()) - INTERVAL 1 WEEK)
	GROUP BY {$col} ORDER BY {$col} ";
	$foo['week'] = $wpdb->get_results( $sql, ARRAY_A );

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > (DATE(NOW()) - INTERVAL 1 MONTH)
	GROUP BY {$col} ORDER BY {$col} ";
	$foo['month'] = $wpdb->get_results( $sql, ARRAY_A );

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > (DATE(NOW()) - INTERVAL 3 MONTH)
	GROUP BY {$col} ORDER BY {$col} ";
	$foo['quarter'] = $wpdb->get_results( $sql, ARRAY_A );

	foreach ( $foo as $range => $val ) {
		foreach ( $val as $val2 ) {
			$bar[ $val2[ $col ] ][ $range ] = @$bar[ $val2[ $col ] ][ $range ] + $val2['total']; // @codingStandardsIgnoreLine
		}
	}

	// Add missing zeros
	foreach ( $bar as $key => $val ) {
		if ( ! isset( $val['today'] ) ) {
			$bar[ $key ]['today'] = 0;
		}
		if ( ! isset( $val['week'] ) ) {
			$bar[ $key ]['week'] = 0;
		}
		if ( ! isset( $val['month'] ) ) {
			$bar[ $key ]['month'] = 0;
		}
		if ( ! isset( $val['quarter'] ) ) {
			$bar[ $key ]['quarter'] = 0;
		}
	}

	ksort( $bar );

	return $bar;
}


function query_sites_stats( $col ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$time = 'registered';
	$foo = [];

	// Registered

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > DATE_SUB(NOW(), INTERVAL 24 HOUR) ", $col, $time ), ARRAY_A );
	$foo['registered']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 1 WEEK ", $col, $time ), ARRAY_A );
	$foo['registered']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 1 MONTH ", $col, $time ), ARRAY_A );
	$foo['registered']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM  {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 3 MONTH ", $col, $time ), ARRAY_A );
	$foo['registered']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Active

	$time = 'last_updated';

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > DATE_SUB(NOW(), INTERVAL 24 HOUR) ", $col, $time ), ARRAY_A );
	$foo['active']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 1 WEEK ", $col, $time ), ARRAY_A );
	$foo['active']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 1 MONTH ", $col, $time ), ARRAY_A );
	$foo['active']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM  {$wpdb->blogs} WHERE %s > NOW() - INTERVAL 3 MONTH ", $col, $time ), ARRAY_A );
	$foo['active']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Spam

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE ( spam = 1 ) AND ( %s > DATE_SUB(NOW(), INTERVAL 24 HOUR)) ", $col, $time ), ARRAY_A );
	$foo['spam']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 1 WEEK ) ", $col, $time ), ARRAY_A );
	$foo['spam']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 1 MONTH ) ", $col, $time ), ARRAY_A );
	$foo['spam']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->blogs} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 3 MONTH ) ", $col, $time ), ARRAY_A );
	$foo['spam']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function query_user_stats( $col ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$time = 'user_registered';
	$foo = [];

	// Registered

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE %s > DATE_SUB(NOW(), INTERVAL 24 HOUR) ", $col, $time ), ARRAY_A );
	$foo['registered']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE %s > NOW() - INTERVAL 1 WEEK ", $col, $time ), ARRAY_A );
	$foo['registered']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE %s > NOW() - INTERVAL 1 MONTH ", $col, $time ), ARRAY_A );
	$foo['registered']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM  {$wpdb->users} WHERE %s > NOW() - INTERVAL 3 MONTH ", $col, $time ), ARRAY_A );
	$foo['registered']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Spam

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE ( spam = 1 ) AND ( %s > DATE_SUB(NOW(), INTERVAL 24 HOUR)) ", $col, $time ), ARRAY_A );
	$foo['spam']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 1 WEEK ) ", $col, $time ), ARRAY_A );
	$foo['spam']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 1 MONTH ) ", $col, $time ), ARRAY_A );
	$foo['spam']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$tmp = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(%s) AS total FROM {$wpdb->users} WHERE ( spam = 1 ) AND ( %s > NOW() - INTERVAL 3 MONTH ) ", $col, $time ), ARRAY_A );
	$foo['spam']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function users_with_x_or_more_books( $x ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$foo = [];

	$sql = "SELECT {$wpdb->usermeta}.user_id, {$wpdb->users}.user_login AS username, count({$wpdb->usermeta}.meta_key) AS total FROM {$wpdb->usermeta}
	INNER JOIN {$wpdb->users} ON {$wpdb->usermeta}.user_id = {$wpdb->users}.ID
	WHERE {$wpdb->usermeta}.meta_key LIKE 'wp_%_capabilities' AND {$wpdb->usermeta}.meta_value LIKE '%administrator%' AND {$wpdb->users}.spam = 0
	GROUP BY {$wpdb->usermeta}.user_id ";

	$tmp = $wpdb->get_results( $sql, ARRAY_A );

	foreach ( $tmp as $val ) {
		if ( $val['total'] >= $x ) {

			$sql = 'SELECT `time` FROM ' . \PressbooksStats\Helpers\get_stats_table() . ' WHERE user_id = ' . absint( $val['user_id'] ) . ' ORDER BY `time` DESC LIMIT 1 ';
			$tmp2 = $wpdb->get_results( $sql, ARRAY_A );

			$foo[] = [
				'username' => $val['username'],
				'last_export' => ( isset( $tmp2[0]['time'] ) ? date( 'Y-m-d', strtotime( $tmp2[0]['time'] ) ) : '!' ),
			];
		}
	}

	$foo = wp_list_sort( $foo, [ 'last_export' => 'DESC' ] );

	return $foo;
}

/**
 * @return string
 */
function get_network_storage_cache_key() {
	return 'pb_stats_network_storage';
}


function display_network_storage() {
	$storage = get_site_transient( get_network_storage_cache_key() );
	if ( ! empty( $storage ) ) {
		$cached = '<!-- CACHED -->';
	} else {
		$cached = '';
		$storage = calculate_network_storage();
	}
	printf( '%1$s<p>%2$s: %3$s</p>', $cached, __( 'Network Storage', 'pressbooks-stats' ), $storage );
}

function calculate_network_storage() {
	if ( ! get_transient( 'pb_stats_calculating_network_storage' ) ) {
		set_transient( 'pb_stats_calculating_network_storage', 1, 5 * MINUTE_IN_SECONDS );
		$path = realpath( wp_upload_dir()['basedir'] );
		$output = exec( sprintf( 'du -b -s %s', escapeshellarg( $path ) ) );
		$storage = format_bytes( rtrim( str_replace( $path, '', $output ) ) );
		delete_transient( 'pb_stats_calculating_network_storage' );
		return $storage;
	}
	return __( 'Calculating storage&hellip;', 'pressbooks-stats' );
}

/**
 * Cache the network storage level
 */
function cache_network_storage() {
	set_site_transient( get_network_storage_cache_key(), calculate_network_storage() );
}
