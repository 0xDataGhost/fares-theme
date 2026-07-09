<?php
/**
 * Contacts (`fares_contact`) — a private CPT that snapshots the email +
 * phone of every checkout completion so the store owner has a
 * marketing-ready mailing list without touching PII on the customer
 * table.
 *
 * De-dup key is the email address (normalised lowercase). Repeat orders
 * from the same email bump the order counter and touch the last-order
 * timestamp instead of creating a new post.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

const FARES_CONTACT_CPT     = 'fares_contact';
const FARES_CONTACT_CAP     = 'manage_woocommerce';
const FARES_EXPORT_ACTION   = 'fares_store_contacts_export';

/**
 * Register the CPT.
 *
 * `public=false` keeps it out of the front end; `show_ui=true` +
 * `show_in_menu=true` give it a top-level admin menu (icon = phone).
 */
function fares_store_register_contacts_cpt(): void {
	register_post_type(
		FARES_CONTACT_CPT,
		array(
			'labels'              => array(
				'name'          => __( 'جهات الاتصال', 'fares-store' ),
				'singular_name' => __( 'جهة اتصال', 'fares-store' ),
				'menu_name'     => __( 'جهات الاتصال', 'fares-store' ),
				'search_items'  => __( 'بحث في جهات الاتصال', 'fares-store' ),
				'not_found'     => __( 'لا توجد جهات اتصال بعد.', 'fares-store' ),
				'all_items'     => __( 'كل جهات الاتصال', 'fares-store' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-phone',
			'menu_position'       => 56,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow', // Only orders create contacts.
			),
			'supports'            => array( 'title' ),
		)
	);
}
add_action( 'init', 'fares_store_register_contacts_cpt' );

/**
 * Ingest a completed / newly-placed order into the contacts store.
 *
 * @param int $order_id Order ID.
 */
function fares_store_ingest_order_contact( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$email = sanitize_email( (string) $order->get_billing_email() );
	$phone = sanitize_text_field( (string) $order->get_billing_phone() );

	if ( '' === $email ) {
		return;
	}

	$email_key = strtolower( $email );
	$existing  = fares_store_find_contact_by_email( $email_key );
	$now       = current_time( 'mysql' );

	if ( $existing ) {
		// Bump the counter + last-seen timestamp; refresh the phone if
		// the customer used a new one.
		$count = (int) get_post_meta( $existing, '_fares_order_count', true ) + 1;
		update_post_meta( $existing, '_fares_order_count', $count );
		update_post_meta( $existing, '_fares_last_order_at', $now );
		update_post_meta( $existing, '_fares_last_order_id', $order_id );

		if ( '' !== $phone ) {
			update_post_meta( $existing, '_fares_phone', $phone );
		}
		return;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => FARES_CONTACT_CPT,
			'post_status' => 'publish',
			'post_title'  => $email,
			// Neutral author so the admin who happened to be logged in
			// (if any) does not "own" the record.
			'post_author' => 0,
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return;
	}

	update_post_meta( $post_id, '_fares_email', $email );
	update_post_meta( $post_id, '_fares_email_key', $email_key );
	update_post_meta( $post_id, '_fares_phone', $phone );
	update_post_meta( $post_id, '_fares_first_order_at', $now );
	update_post_meta( $post_id, '_fares_last_order_at', $now );
	update_post_meta( $post_id, '_fares_last_order_id', $order_id );
	update_post_meta( $post_id, '_fares_order_count', 1 );
}
add_action( 'woocommerce_checkout_order_processed', 'fares_store_ingest_order_contact', 20 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'fares_store_ingest_order_contact', 20 );

/**
 * Look up an existing contact post by its email-key meta. Returns the
 * post ID or 0.
 *
 * @param string $email_key Lowercased email.
 * @return int
 */
function fares_store_find_contact_by_email( string $email_key ): int {
	$posts = get_posts(
		array(
			'post_type'      => FARES_CONTACT_CPT,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_fares_email_key',
					'value' => $email_key,
				),
			),
		)
	);
	return $posts ? (int) $posts[0] : 0;
}

/* -------------------------------------------------------------------------
 * Admin: custom columns for the contacts list table.
 * ---------------------------------------------------------------------- */

/**
 * Column headings.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function fares_store_contacts_columns( array $columns ): array {
	$new              = array();
	$new['cb']        = $columns['cb'] ?? '';
	$new['email']     = __( 'البريد الإلكتروني', 'fares-store' );
	$new['phone']     = __( 'رقم الجوال', 'fares-store' );
	$new['orders']    = __( 'عدد الطلبات', 'fares-store' );
	$new['last_seen'] = __( 'آخر طلب', 'fares-store' );
	return $new;
}
add_filter( 'manage_' . FARES_CONTACT_CPT . '_posts_columns', 'fares_store_contacts_columns' );

/**
 * Column contents.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function fares_store_contacts_column( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'email':
			$email = (string) get_post_meta( $post_id, '_fares_email', true );
			printf(
				'<a href="mailto:%1$s">%1$s</a>',
				esc_html( $email )
			);
			break;
		case 'phone':
			$phone = (string) get_post_meta( $post_id, '_fares_phone', true );
			echo esc_html( $phone );
			break;
		case 'orders':
			echo (int) get_post_meta( $post_id, '_fares_order_count', true );
			break;
		case 'last_seen':
			$ts = (string) get_post_meta( $post_id, '_fares_last_order_at', true );
			echo esc_html( $ts );
			break;
	}
}
add_action( 'manage_' . FARES_CONTACT_CPT . '_posts_custom_column', 'fares_store_contacts_column', 10, 2 );

/**
 * Mark columns sortable.
 *
 * @param array $sortable Sortable map.
 * @return array
 */
function fares_store_contacts_sortable( array $sortable ): array {
	$sortable['email']     = 'email';
	$sortable['phone']     = 'phone';
	$sortable['orders']    = 'orders';
	$sortable['last_seen'] = 'last_seen';
	return $sortable;
}
add_filter( 'manage_edit-' . FARES_CONTACT_CPT . '_sortable_columns', 'fares_store_contacts_sortable' );

/**
 * Translate the sortable column into a meta-key orderby.
 *
 * @param WP_Query $query Main list-table query.
 */
function fares_store_contacts_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( FARES_CONTACT_CPT !== $query->get( 'post_type' ) ) {
		return;
	}

	$map = array(
		'email'     => array( 'meta_key' => '_fares_email_key',    'orderby' => 'meta_value' ),
		'phone'     => array( 'meta_key' => '_fares_phone',        'orderby' => 'meta_value' ),
		'orders'    => array( 'meta_key' => '_fares_order_count',  'orderby' => 'meta_value_num' ),
		'last_seen' => array( 'meta_key' => '_fares_last_order_at','orderby' => 'meta_value' ),
	);

	$orderby = (string) $query->get( 'orderby' );
	if ( isset( $map[ $orderby ] ) ) {
		$query->set( 'meta_key', $map[ $orderby ]['meta_key'] );
		$query->set( 'orderby', $map[ $orderby ]['orderby'] );
	}
}
add_action( 'pre_get_posts', 'fares_store_contacts_orderby' );

/**
 * Search by email or phone from the list-table search box.
 *
 * @param string   $search   Existing SQL WHERE fragment.
 * @param WP_Query $query    The query.
 * @return string
 */
function fares_store_contacts_search( string $search, WP_Query $query ): string {
	global $wpdb;

	if ( ! is_admin() || ! $query->is_main_query() ) {
		return $search;
	}
	if ( FARES_CONTACT_CPT !== $query->get( 'post_type' ) ) {
		return $search;
	}

	$term = (string) $query->get( 's' );
	if ( '' === $term ) {
		return $search;
	}

	$like = '%' . $wpdb->esc_like( $term ) . '%';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$where = $wpdb->prepare(
		" AND ({$wpdb->posts}.ID IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key IN ('_fares_email','_fares_phone')
			AND meta_value LIKE %s
		))",
		$like
	);
	return $where;
}
add_filter( 'posts_search', 'fares_store_contacts_search', 10, 2 );

/* -------------------------------------------------------------------------
 * Export button + CSV stream.
 * ---------------------------------------------------------------------- */

/**
 * Render the "Export CSV" link above the list table.
 *
 * @param string $which Position (top/bottom) — only top gets the button.
 */
function fares_store_contacts_export_button( string $which ): void {
	if ( 'top' !== $which ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit-' . FARES_CONTACT_CPT !== $screen->id ) {
		return;
	}

	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=' . FARES_EXPORT_ACTION ),
		FARES_EXPORT_ACTION
	);

	printf(
		'<a href="%1$s" class="button button-primary" style="margin-inline-start:8px;">%2$s</a>',
		esc_url( $url ),
		esc_html__( 'تصدير CSV', 'fares-store' )
	);
}
add_action( 'manage_posts_extra_tablenav', 'fares_store_contacts_export_button' );

/**
 * Stream the contacts table as CSV. Batches through IDs so a large
 * dataset never sits in memory as a full result set.
 */
function fares_store_export_contacts_csv(): void {
	if ( ! current_user_can( FARES_CONTACT_CAP ) ) {
		wp_die( esc_html__( 'ليست لديك صلاحية.', 'fares-store' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( FARES_EXPORT_ACTION );

	nocache_headers();
	$filename = 'contacts-' . gmdate( 'Y-m-d' ) . '.csv';
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	// End any output buffering PHP or WP started so we can stream cleanly.
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	$fh = fopen( 'php://output', 'w' );
	if ( ! $fh ) {
		exit;
	}

	// BOM so Excel opens UTF-8 without asking.
	fwrite( $fh, "\xEF\xBB\xBF" );

	fputcsv(
		$fh,
		array(
			__( 'البريد الإلكتروني', 'fares-store' ),
			__( 'رقم الجوال', 'fares-store' ),
			__( 'تاريخ التسجيل', 'fares-store' ),
			__( 'آخر طلب', 'fares-store' ),
			__( 'عدد الطلبات', 'fares-store' ),
		)
	);

	$batch    = 500;
	$paged    = 1;
	$per_flush = 100;
	$counter  = 0;

	do {
		$ids = get_posts(
			array(
				'post_type'      => FARES_CONTACT_CPT,
				'post_status'    => 'any',
				'posts_per_page' => $batch,
				'paged'          => $paged,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		foreach ( $ids as $id ) {
			fputcsv(
				$fh,
				array(
					(string) get_post_meta( $id, '_fares_email', true ),
					(string) get_post_meta( $id, '_fares_phone', true ),
					(string) get_post_meta( $id, '_fares_first_order_at', true ),
					(string) get_post_meta( $id, '_fares_last_order_at', true ),
					(string) get_post_meta( $id, '_fares_order_count', true ),
				)
			);
			$counter++;
			if ( 0 === $counter % $per_flush ) {
				flush();
			}
		}

		$paged++;
	} while ( count( $ids ) === $batch );

	fclose( $fh );
	exit;
}
add_action( 'admin_post_' . FARES_EXPORT_ACTION, 'fares_store_export_contacts_csv' );
