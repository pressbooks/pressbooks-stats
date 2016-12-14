<?php
// @codingStandardsIgnoreStart
namespace PressbooksStats\Stats;

/**
 * WP hook for our very own pressbooks_track_export action
 *
 * @param string $export_type
 */
function track_export( $export_type ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$wpdb->insert(
		'wp_pressbooks_stats_exports',
		array(
			'user_id' => get_current_user_id(),
			'blog_id' => get_current_blog_id(),
			'time' => date( 'Y-m-d H:i:s' ),
			'export_type' => $export_type,
			'theme' => '' . wp_get_theme(), // Stringify by appending to empty string

		),
	array( '%d', '%d', '%s', '%s', '%s' ) );
}


/**
 * Register graphic libraries and css
 */
function init_css_js() {

	wp_register_script( 'pb-vip-stats-1', PB_STATS_PLUGIN_URL . 'symbionts/visualize/js/visualize.jQuery.js', array( 'jquery' ) );
	wp_register_style( 'pb-vip-stats-2', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/basic.css' );
	wp_register_style( 'pb-vip-stats-3', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/visualize.css' );
	wp_register_style( 'pb-vip-stats-4', PB_STATS_PLUGIN_URL . 'symbionts/visualize/css/visualize-light.css' );

	wp_register_script( 'pb-vip-stats-5', PB_STATS_PLUGIN_URL . 'assets/js/graphs.js', array( 'pb-vip-stats-1' ), '20130718' );
	wp_register_style( 'pb-vip-stats-6', PB_STATS_PLUGIN_URL . 'assets/css/stats.css', array(), '20130718' );
}


/**
 * Add a top level menu item
 */
function menu() {

	global $wpdb;

	$user = wp_get_current_user();

	$restricted = $wpdb->get_results( 'SELECT * FROM wp_sitemeta WHERE meta_key = "pressbooks_network_managers"' );
	if ( $restricted ) {
		$restricted = maybe_unserialize( $restricted[0]->meta_value );
	} else {
	    $restricted = array();
	}

	if ( ! in_array( $user->ID, $restricted ) ) {
		$page = add_menu_page(
			'Pressbooks Statistics',
			'PB Stats',
			'manage_network',
			'pb_stats',
			__NAMESPACE__ . '\display_stats_admin_page',
		'dashicons-chart-pie' );

		add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $page ) {

			if ( $hook == $page ) {
				wp_enqueue_script( 'pb-vip-stats-1' );
				wp_enqueue_style( 'pb-vip-stats-2' );
				wp_enqueue_style( 'pb-vip-stats-3' );
				wp_enqueue_style( 'pb-vip-stats-4' );
				wp_enqueue_script( 'pb-vip-stats-5' );
				wp_enqueue_style( 'pb-vip-stats-6' );
			}
		} );
	}

}


/**
 * Echo stats dashboard
 */
function display_stats_admin_page() {

	$vars = array(
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
	);

	echo \PressbooksStats\Helpers\load_template( PB_STATS_PLUGIN_DIR . '/templates/stats.php', $vars );
}


// -------------------------------------------------------------------------------------------------------------------
// SQL Helpers
// -------------------------------------------------------------------------------------------------------------------

function query_totals() {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$foo = array();

	// Sites

	$sql = 'SELECT COUNT(*) AS total FROM wp_blogs ';
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['sites']['total'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = 'SELECT COUNT(*) AS total FROM wp_blogs WHERE spam = 1 ';
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['sites']['spam'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT(*) AS total FROM wp_blogs WHERE ( deleted = 1 OR archived = '1') AND ( spam = 0 ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['sites']['deactivated'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Users

	$sql = 'SELECT COUNT(*) AS total FROM wp_users ';
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['users']['total'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = 'SELECT COUNT(*) AS total FROM wp_users WHERE spam = 1 ';
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['users']['spam'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function query_last_100() {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$sql = 'SELECT wp_blogs.domain, wp_blogs.path,
                   stats.blog_id, stats.time, stats.export_type, stats.user_id, stats.theme,
                   wp_users.user_login, wp_users.user_email
              FROM wp_pressbooks_stats_exports AS stats
              JOIN wp_blogs ON stats.blog_id = wp_blogs.blog_id
         LEFT JOIN wp_users ON (stats.user_id = wp_users.ID)
         ORDER BY stats.time DESC
             LIMIT 100 ';

	$foo = $wpdb->get_results( $sql, ARRAY_A );

	foreach ( $foo as $key => $val ) {

		$sql = "SELECT option_value FROM wp_{$val['blog_id']}_options WHERE option_name = 'blogname' LIMIT 1 ";
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

	$table = 'wp_pressbooks_stats_exports';
	$time = 'time';
	$col = 'blog_id';

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL {$interval} )
	GROUP BY {$col} ORDER BY total DESC ";
	$foo = $wpdb->get_results( $sql, ARRAY_A );

	if ( false == $just_the_count ) {

		foreach ( $foo as $key => $val ) {

			$sql = "SELECT option_value FROM wp_{$val['blog_id']}_options WHERE option_name = 'blogname' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );

			if ( isset( $tmp[0]['option_value'] ) ) {
				$foo[ $key ]['blogname'] = $tmp[0]['option_value'];
			} else {
				$foo[ $key ]['blogname'] = '__unknown__';
			}

			$sql = "SELECT option_value FROM wp_{$val['blog_id']}_options WHERE option_name = 'blog_public' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );
			$foo[ $key ]['blog_public'] = $tmp[0]['option_value'];

			$sql = "SELECT option_value FROM wp_{$val['blog_id']}_options WHERE option_name = 'pressbooks_upgrade_level' ";
			$tmp = $wpdb->get_results( $sql, ARRAY_A );
			$foo[ $key ]['pressbooks_upgrade_level'] = $tmp[0]['option_value'];

		}
	}

	return $foo;

}

function query_users_exported( $interval, $just_the_count = false ) {

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = 'wp_pressbooks_stats_exports';
	$time = 'time';
	$col = 'user_id';

	$sql = "SELECT {$col}, COUNT(*) AS total FROM {$table}
	WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL {$interval} )
	GROUP BY {$col} ORDER BY total DESC ";
	$foo = $wpdb->get_results( $sql, ARRAY_A );

	if ( false == $just_the_count ) {

		$is_new = array();
		$sql = 'SELECT ID FROM wp_users
        WHERE user_registered > DATE_SUB(NOW(), INTERVAL 24 HOUR)';
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

	$table = 'wp_pressbooks_stats_exports';
	$time = 'time';

	$foo = array();
	$bar = array();

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
			$bar[ $val2[ $col ] ][ $range ] = @$bar[ $val2[ $col ] ][ $range ] + $val2['total'];
		}
	}

	// Add missing zeros
	foreach ( $bar as $key => $val ) {
		if ( ! isset( $val['today'] ) ) { $bar[ $key ]['today'] = 0;
		}
		if ( ! isset( $val['week'] ) ) { $bar[ $key ]['week'] = 0;
		}
		if ( ! isset( $val['month'] ) ) { $bar[ $key ]['month'] = 0;
		}
		if ( ! isset( $val['quarter'] ) ) { $bar[ $key ]['quarter'] = 0;
		}
	}

	ksort( $bar );

	return $bar;
}


function query_sites_stats( $col ) {

	/**
	 * wp_blogs
	 * Deactivate: Flags deleted db field, message is displayed on the front side, can undo
	 * Archive: Flags archived db field, message is displayed on the front side, can undo
	 * Spam: Flags spam db field, message is displayed on the front side, can undo
	 * Delete: Drops tables and rm dirs and files
	 * User Delete Blog: Drops tables and rm dirs and files
	 */

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = 'wp_blogs';
	$time = 'registered';

	$foo = array();

	// Registered

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 WEEK ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM  {$table} WHERE `{$time}` > NOW() - INTERVAL 3 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Active

	$time = 'last_updated';

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['active']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 WEEK ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['active']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['active']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM  {$table} WHERE `{$time}` > NOW() - INTERVAL 3 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['active']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Spam

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR)) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 1 WEEK ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 1 MONTH ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 3 MONTH ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function query_user_stats( $col ) {

	/**
	 * wp_users
	 * fields we care about: user_registered, spam, deleted
	 */

	/**
	 * wp_usermeta
	 * a user can have access to more than one blog. There are two elements in the usermeta table to look into:
	 * 'primary_blog' - As the key_name implies this is the user's primary blog. Will only contain one blog ID.
	 * A given user may have multiple 'wp_XX_capabilities' rows where 'XX' is the blog ID.
	 */

	/** @var \wpdb $wpdb */
	global $wpdb;

	$table = 'wp_users';
	$time = 'user_registered';

	$foo = array();

	// Registered

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 WEEK ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE `{$time}` > NOW() - INTERVAL 1 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM  {$table} WHERE `{$time}` > NOW() - INTERVAL 3 MONTH ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['registered']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	// Spam

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > DATE_SUB(NOW(), INTERVAL 24 HOUR)) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['today'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 1 WEEK ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['week'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 1 MONTH ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['month'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	$sql = "SELECT COUNT($col) AS total FROM {$table} WHERE ( spam = 1 ) AND ( `{$time}` > NOW() - INTERVAL 3 MONTH ) ";
	$tmp = $wpdb->get_results( $sql, ARRAY_A );
	$foo['spam']['quarter'] = isset( $tmp[0]['total'] ) ? $tmp[0]['total'] : 0;

	return $foo;
}


function users_with_x_or_more_books( $x ) {

	$transient = "users_with_{$x}_or_more_books";
	$foo = get_transient( $transient );
	if ( false !== $foo ) {
		return $foo; // Return cached copy
	}

	/** @var \wpdb $wpdb */
	global $wpdb;

	$foo = array();

	$sql = "SELECT wp_usermeta.user_id, wp_users.user_login AS username, count(wp_usermeta.meta_key) AS total FROM wp_usermeta
	INNER JOIN wp_users ON wp_usermeta.user_id = wp_users.ID
	WHERE wp_usermeta.meta_key LIKE 'wp_%_capabilities' AND wp_usermeta.meta_value LIKE '%administrator%' AND wp_users.spam = 0
	GROUP BY wp_usermeta.user_id ";

	$tmp = $wpdb->get_results( $sql, ARRAY_A );

	foreach ( $tmp as $val ) {
		if ( $val['total'] >= $x ) {

			$sql = 'SELECT `time` FROM wp_pressbooks_stats_exports WHERE user_id = ' . absint( $val['user_id'] ) . ' ORDER BY `time` DESC LIMIT 1 ';
			$tmp2 = $wpdb->get_results( $sql, ARRAY_A );

			$foo[] = array(
				'username' => $val['username'],
				'last_export' => ( isset( $tmp2[0]['time'] ) ? date( 'Y-m-d', strtotime( $tmp2[0]['time'] ) ) : '!' ),
			);
		}
	}

	$foo = wp_list_sort( $foo, array( 'last_export' => 'DESC' ) );

	set_transient( $transient, $foo, 60 * 60 * 12 );

	return $foo;
}
// @codingStandardsIgnoreEnd
