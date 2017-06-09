<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">

	<div id="icon-plugins" class="icon32"><br /></div>
	<h2>Pressbooks Statistics</h2>


	<p>&nbsp;</p>
	<?php
	foreach ( $totals as $key => $val ) {
		$real_total = @$val['total'] - @$val['spam'] - @$val['deactivated'];
		echo '<h1> &#9733; ' . ( ! empty( $key ) ? ucfirst( $key ) : '...' ) . ': ';
		echo "{$val['total']} ";
		echo "<span style='color:#ccc;font-size:small;font-style:italic;'> &middot; Spam: {$val['spam']}";
		if ( isset( $val['deactivated'] ) ) { echo ", Deactivated: {$val['deactivated']} ";
		}
		echo ", Real Total: $real_total";
		echo '</span></h1>';
	}
	?>


	<div class="executive-summary">
	<p><strong><?php echo count( $books_exported_today ); ?></strong> books had at least one export done in the last 24 hours, <strong><?php echo count( $books_exported_month ); ?></strong> in the last month.</p>

	<p><strong><?php echo count( $users_exported_today ); ?></strong> users exported at least one book in the last 24 hours, <strong><?php echo count( $users_exported_month ); ?></strong> in the last month.</p>

	<p><strong><?php echo $sites['active']['today'] ?></strong> books were active in the last 24 hours.</p>
	<?php
	$old_users = 0;
	foreach ( $users_exported_today as $val ) {
		if ( false == $val['is_new'] ) { ++$old_users;
		}
	}
	?>
	<p><strong><?php echo $old_users; ?></strong> users did NOT register in the last 24 hours, did an EXPORT in the last 24 hours.</p>
	</div>


	<table>
		<caption>Last ~100 Exports</caption>
		<thead>
		<tr>
			<th style="text-align:center;">Time</th>
			<th style="text-align:center;">Book</th>
			<th style="text-align:center;">User</th>
			<th style="text-align:center;">Theme</th>
			<th style="text-align:center;">Type</th>
			<th style="text-align:center;">Total</th>
		</tr>
		</thead>
		<tbody>
		<?php

		$total = 0;
		$prev = $next = false;
		$types = $themes = array();
		$j = 1;

		foreach ( $recents as $i => $recent ) {

			$siteurl = $recent['domain'] . $recent['path'];

			if ( false !== $prev && $siteurl != $prev ) {
				$next = true;
			}

			if ( $next ) {

				ksort( $themes );
				ksort( $types );

				?>
			<tr <?php if ( $j % 2 ) { echo 'class="alternate"';} ?> >
				<td><?php echo $recents[ $i - 1 ]['time']; ?></td>
				<td>
					<a href="http://<?php echo $recents[ $i - 1 ]['domain'] . $recents[ $i - 1 ]['path']; ?>"><?php echo $recents[ $i - 1 ]['blogname']; ?></a>
				</td>
				<td>
					<?php if ( 0 != $recents[ $i - 1 ]['user_id'] ) { ?>
					<a href="<?php echo site_url(); ?>/wp-admin/network/users.php?orderby=id&order=DESC&s=<?php echo $recents[ $i - 1 ]['user_login']; ?>"><?php echo $recents[ $i - 1 ]['user_login']; ?></a> |
					<a href="mailto:<?php echo $recents[ $i - 1 ]['user_email']; ?>"><?php echo $recents[ $i - 1 ]['user_email']; ?></a>
					<?php } ?>
				<td><?php echo implode( ', ', array_keys( $themes ) ); ?></td>
				<td><?php echo implode( ', ', array_keys( $types ) ); ?></td>
				<td><?php echo array_sum( $types ); ?></td>
			</tr>
				<?php

				$total += array_sum( $types );
				++$j;
				$types = $themes = array();
			}

			$themes[ $recent['theme'] ] = @++$themes[ $recent['theme'] ];
			$types[ $recent['export_type'] ] = @++$types[ $recent['export_type'] ];

			$prev = $siteurl;
			$next = false;
		}
		?>
		<tr>
			<td colspan="5"></td>
			<td><strong><?php echo $total; ?></strong></td>
		</tr>
		</tbody>
	</table>


	<table>
		<caption>Books exported in the last 24h</caption>
		<thead>
		<tr>
			<th style="text-align:center;">Book</th>
			<th style="text-align:center;">Public</th>
			<?php if ( is_plugin_active( 'pressbooks-vip/pressbooks-vip.php' ) ) { ?>
			<th style="text-align:center;">Upgrade Level</th>
			<?php } ?>
			<th style="text-align:center;">Total</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$i = 1;
		foreach ( $books_exported_today as $key => $val ) {
			?><tr <?php if ( $i % 2 ) { echo 'class="alternate"';
			} ?> ><?php
			echo "<td><a href='" . get_site_url( $val['blog_id'] ) . "'>{$val['blogname']}</a></td>";
			echo '<td>' . ( $val['blog_public'] ? '<strong>yes</strong>' : '<span style="color:#ccc;">no</span>' ) . '</td>';

if ( is_plugin_active( 'pressbooks-vip/pressbooks-vip.php' ) ) {
	$upgrade_level = strtoupper( $val['pressbooks_upgrade_level'] );
	echo '<td>' . ( $val['pressbooks_upgrade_level'] ? "<strong>$upgrade_level</strong> ($" . number_format( ( PressbooksVIP\Upgrade\actual_package_value( $val['pressbooks_upgrade_level'] ) / 100 ), 2 ) . ')' : "<span style='color:#ccc;'>n/a</span>" ) . '</td>';
}

			echo "<td>{$val['total']}</td>";
			echo '</tr>';
			++$i;
		}
		?>
		</tbody>
	</table>

	<table>
		<caption>Users who exported in the last 24h</caption>
		<thead>
		<tr>
			<th style="text-align:center;">User</th>
			<th style="text-align:center;"># of books</th>
			<th style="text-align:center;">Registered in last 24h?</th>
			<th style="text-align:center;">Total</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$i = 1;
		foreach ( $users_exported_today as $key => $val ) {
		?><tr <?php if ( $i % 2 ) { echo 'class="alternate"';
		} ?> ><?php
			echo "<td><a href='" . site_url() . '/wp-admin/network/users.php?orderby=id&order=DESC&s=' . $val['username'] . "'>{$val['username']}</a> | <a href='mailto:{$val['user_email']}'>{$val['user_email']}</a></td>";
			echo '<td>' . count( get_blogs_of_user( $val['user_id'], true ) ) . '</td>';
			echo '<td>' . ( $val['is_new'] ? '<strong>yes</strong>' : '<span style="color:#ccc;">no</span>' ) . '</td>';
			echo "<td>{$val['total']}</td>";
			echo '</tr>';
			++$i;
		}
			?>
		</tbody>
	</table>


	<table class="bar-chart">
		<caption>Book Activity</caption>
		<thead>
		<tr>
			<td></td>
			<th>24 Hours</th>
			<th>Week</th>
			<th>Month</th>
			<th>Quarter</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $sites as $key => $val ) {
			echo '<tr>';
			echo '<th>' . ( ! empty( $key ) ? ucfirst( $key ) : '...' ) . '</th>';
			echo "<td>{$val['today']}</td>";
			echo "<td>{$val['week']}</td>";
			echo "<td>{$val['month']}</td>";
			echo "<td>{$val['quarter']}</td>";
			echo '</tr>';
		}
		?>
		</tbody>
	</table>


	<table class="bar-chart">
		<caption>User Activity</caption>
		<thead>
		<tr>
			<td></td>
			<th>24 Hours</th>
			<th>Week</th>
			<th>Month</th>
			<th>Quarter</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $users as $key => $val ) {
			echo '<tr>';
			echo '<th>' . ( ! empty( $key ) ? ucfirst( $key ) : '...' ) . '</th>';
			echo "<td>{$val['today']}</td>";
			echo "<td>{$val['week']}</td>";
			echo "<td>{$val['month']}</td>";
			echo "<td>{$val['quarter']}</td>";
			echo '</tr>';
		}
		?>
		</tbody>
	</table>


	<table class="pie-chart">
		<caption>Exported Formats</caption>
		<thead>
		<tr>
			<td></td>
			<th>24 Hours</th>
			<th>Week</th>
			<th>Month</th>
			<th>Quarter</th>
		</tr>
		</thead>
		<tbody>
		<?php

		uasort( $export_types, function ( $a, $b ) {
			return $a['quarter'] < $b['quarter'];
		} );

		foreach ( $export_types as $key => $val ) {
			echo '<tr>';
			echo '<th>' . ( ! empty( $key ) ? ucfirst( $key ) : '...' ) . '</th>';
			echo "<td>{$val['today']}</td>";
			echo "<td>{$val['week']}</td>";
			echo "<td>{$val['month']}</td>";
			echo "<td>{$val['quarter']}</td>";
			echo '</tr>';

		}
		?>
		</tbody>
	</table>


	<table class="pie-chart">
		<caption>Exported Themes</caption>
		<thead>
		<tr>
			<td></td>
			<th>24 Hours</th>
			<th>Week</th>
			<th>Month</th>
			<th>Quarter</th>
		</tr>
		</thead>
		<tbody>
		<?php

		uasort( $export_themes, function ( $a, $b ) {
			return $a['quarter'] < $b['quarter'];
		} );

		foreach ( $export_themes as $key => $val ) {
			echo '<tr>';
			echo '<th>' . ( ! empty( $key ) ? ucfirst( $key ) : '...' ) . '</th>';
			echo "<td>{$val['today']}</td>";
			echo "<td>{$val['week']}</td>";
			echo "<td>{$val['month']}</td>";
			echo "<td>{$val['quarter']}</td>";
			echo '</tr>';

		}
		?>
		</tbody>
	</table>


	<table>
		<caption>Users With 5 Or More Books</caption>
		<tbody>
		<tr>
			<td style="text-align:left;">
				Total: <?php echo count( $users_with_5_or_more_books ); ?><br />
				<?php
				foreach ( $users_with_5_or_more_books as $val ) {
					echo "<a href='" . site_url() . '/wp-admin/network/users.php?orderby=id&order=DESC&s=' . $val['username'] . "'>{$val['username']}</a> <span style='color:#ccc;'>({$val['last_export']}),</span> ";
				}
				?>
			</td>
		</tr>
		</tbody>
	</table>

</div>
