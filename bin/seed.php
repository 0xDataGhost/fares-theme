<?php
/**
 * Idempotent dev-content seeder. Run inside wp-env:
 *   npm run seed   (wp-env run cli -- wp eval-file bin/seed.php)
 *
 * Creates: Arabic locale, categories, virtual products (simple + variable,
 * sale + out-of-stock), testimonials, footer menu, shortcode cart/checkout
 * pages, and serial numbers for the code-delivery flow.
 *
 * @package fares-theme
 */

defined( 'WP_CLI' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

WP_CLI::log( '— Fares seed —' );

/* ---------------------------------------------------------------- locale */

$locale = get_option( 'WPLANG' );
if ( 'ar' !== $locale ) {
	WP_CLI::runcommand( 'language core install ar --activate', array( 'exit_error' => false ) );
	WP_CLI::log( 'Locale switched to ar.' );
}

/* ------------------------------------------------------------ categories */

$categories = array(
	'sony-5'        => 'قسم العاب سوني 5',
	'sony-4'        => 'قسم العاب سوني 4',
	'plus-apps'     => 'قسم تطبيقات البلس',
	'plus-codes'    => 'قسم أكواد البلس',
	'steam-games'   => 'قسم بكجات العاب ستيم',
	'mobile-topup'  => 'قسم شحن العاب الجوال',
	'subscriptions' => 'قسم اشتركات بلا شوب',
	'world-cup'     => 'باقات كأس العالم',
);

$category_ids = array();
foreach ( $categories as $slug => $name ) {
	$existing = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $existing instanceof WP_Term ) {
		$category_ids[ $slug ] = (int) $existing->term_id;
		continue;
	}
	$created = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		WP_CLI::warning( "Category {$slug}: " . $created->get_error_message() );
		continue;
	}
	$category_ids[ $slug ] = (int) $created['term_id'];
	WP_CLI::log( "Category created: {$name}" );
}

/* -------------------------------------------------------------- products */

/**
 * Create (or fetch) a simple virtual product.
 */
function fares_seed_simple( string $sku, string $name, string $regular, string $sale, array $cat_ids, bool $in_stock = true, int $sales = 0 ): int {
	$existing = wc_get_product_id_by_sku( $sku );
	if ( $existing ) {
		return $existing;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_sku( $sku );
	$product->set_regular_price( $regular );
	if ( '' !== $sale ) {
		$product->set_sale_price( $sale );
	}
	$product->set_virtual( true );
	$product->set_category_ids( $cat_ids );
	$product->set_manage_stock( false );
	$product->set_stock_status( $in_stock ? 'instock' : 'outofstock' );
	$product->set_total_sales( $sales );
	$product->set_status( 'publish' );
	$product->save();

	WP_CLI::log( "Product: {$name} (#{$product->get_id()})" );
	return $product->get_id();
}

$simple_products = array(
	// sku, name, regular, sale, category, in stock, total sales.
	array( 'plus-iphone-vip', 'بلس ايفون - الباقة الماسية vip', '650.14', '586.55', 'plus-apps', true, 7917 ),
	array( 'plus-android', 'بلس اندرويد - فورى', '2463.49', '1300.34', 'plus-apps', true, 5200 ),
	array( 'plus-ipad', 'بلس ايباد - فورى', '236.87', '156.41', 'plus-apps', true, 3100 ),
	array( 'ps5-plus-deluxe', 'بلس فاخر PS5', '1300.34', '', 'sony-5', true, 900 ),
	array( 'ps5-plus-essential', 'بلس اساسى PS5', '1303.00', '', 'sony-5', true, 1500 ),
	array( 'ps5-plus-extra', 'بلس اضافى PS5', '65.17', '', 'sony-5', true, 800 ),
	array( 'insta-verify', 'توثيق حسابات انستغرام', '3910.30', '1042.75', 'subscriptions', true, 260 ),
	array( 'insta-users', 'نقل يوزرات انستقرام', '500.00', '', 'subscriptions', false, 120 ),
	array( 'wc-package-gold', 'باقة كأس العالم الذهبية', '999.00', '749.00', 'world-cup', true, 430 ),
	array( 'steam-pack-1', 'بكج ستيم ٥ العاب', '350.00', '', 'steam-games', true, 210 ),
);

$product_ids = array();
foreach ( $simple_products as [ $sku, $name, $regular, $sale, $cat, $in_stock, $sales ] ) {
	$product_ids[ $sku ] = fares_seed_simple( $sku, $name, $regular, $sale, array( $category_ids[ $cat ] ?? 0 ), $in_stock, $sales );
}

// Variable product: GTA 6 with "نوع الحساب" account-type options (the cart design's pills).
$gta_sku = 'gta6-standard';
if ( ! wc_get_product_id_by_sku( $gta_sku ) ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( 'نوع الحساب' );
	$attribute->set_options( array( 'تلعب بحسابك الاساسي', 'تلعب بحساب المتجر' ) );
	$attribute->set_visible( true );
	$attribute->set_variation( true );

	$variable = new WC_Product_Variable();
	$variable->set_name( 'قراند 6 نسخه الستاندر | gta 6' );
	$variable->set_sku( $gta_sku );
	$variable->set_virtual( true );
	$variable->set_category_ids( array( $category_ids['sony-5'] ?? 0 ) );
	$variable->set_attributes( array( $attribute ) );
	$variable->set_status( 'publish' );
	$variable->save();

	$prices = array(
		'تلعب بحسابك الاساسي' => array( '3391.30', '1042.75' ),
		'تلعب بحساب المتجر'   => array( '980.00', '742.96' ),
	);
	foreach ( $prices as $option => [ $regular, $sale ] ) {
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $variable->get_id() );
		$variation->set_attributes( array( sanitize_title( 'نوع الحساب' ) => $option ) );
		$variation->set_regular_price( $regular );
		$variation->set_sale_price( $sale );
		$variation->set_virtual( true );
		$variation->save();
	}

	WC_Product_Variable::sync( $variable->get_id() );
	$product_ids[ $gta_sku ] = $variable->get_id();
	WP_CLI::log( "Variable product: GTA 6 (#{$variable->get_id()})" );
}

// Rich descriptions + up-sells + reviews for the single-product surface
// (idempotent — set every run).
$vip_id = wc_get_product_id_by_sku( 'plus-iphone-vip' );
if ( $vip_id ) {
	$vip = wc_get_product( $vip_id );
	if ( $vip && '' === $vip->get_description() ) {
		$vip->set_description(
			"مميزات الاشتراك:\n\n• تحديثات دورية للتطبيقات.\n• تفعيل فوري بعد الدفع مباشرة.\n• دعم فني على مدار الساعة.\n\nشروط الاشتراك في البلس:\n\n• مدة الاشتراك سنة كاملة.\n• الاشتراك مخصص لجهاز واحد فقط ولا يمكن نقله.\n• لا يمكن إلغاء الاشتراك أو استرداد المبلغ بعد التفعيل.\n\nالضمان:\n\n• ضمان الجهاز طوال مدة الاشتراك.\n• إذا تم إغلاق شهادة قبل مرور فترة الضمان يتم تعويض مجاني لمرة واحدة فقط."
		);
	}
	$upsells = array_filter(
		array(
			wc_get_product_id_by_sku( 'gta6-standard' ),
			wc_get_product_id_by_sku( 'plus-android' ),
		)
	);
	if ( $vip ) {
		$vip->set_upsell_ids( $upsells );
		$vip->save();
	}

	// A few product reviews.
	if ( 0 === (int) get_comments( array( 'post_id' => $vip_id, 'count' => true, 'type' => 'review' ) ) ) {
		$reviews = array(
			array( 'olo alsmadi', 'منتج ممتاز والتفعيل فوري فعلاً', 5 ),
			array( 'احمد جاسم', 'تجربة شراء سلسة وأنصح فيه', 5 ),
			array( 'hailan salem', 'جيد جداً مع بعض التأخير البسيط في الرد', 4 ),
		);
		foreach ( $reviews as [ $r_author, $r_body, $r_rating ] ) {
			$cid = wp_insert_comment(
				array(
					'comment_post_ID'      => $vip_id,
					'comment_author'       => $r_author,
					'comment_author_email' => sanitize_title( $r_author ) . '@example.com',
					'comment_content'      => $r_body,
					'comment_type'         => 'review',
					'comment_approved'     => 1,
				)
			);
			if ( $cid ) {
				update_comment_meta( $cid, 'rating', $r_rating );
				update_comment_meta( $cid, 'verified', 1 );
			}
		}
		WC_Comments::clear_transients( $vip_id );
	}
}

// Ribbon badges (idempotent — set every run).
$ribbon_skus = array( 'plus-iphone-vip', 'plus-android', 'plus-ipad', 'ps5-plus-deluxe', 'ps5-plus-essential', 'ps5-plus-extra' );
foreach ( $ribbon_skus as $ribbon_sku ) {
	$rid = wc_get_product_id_by_sku( $ribbon_sku );
	if ( $rid ) {
		update_post_meta( $rid, '_fares_ribbon', 'يجب قراءة الوصف كامل' );
	}
}

/* ---------------------------------------------------------- testimonials */

$testimonials = array(
	array( 'محمد الانصارى', 'ثقة وتواصل ممتاز الله يسعدهم', 5 ),
	array( 'ريان محمد', 'اجزم بانه افضل متجر من جميع النواحي واكثر ناحيه عجبتني سرعة الرد في خدمة العملاء ياساتر ماقد شفت متجر يرد عليك ويعاونك وباسلوب لبق زي كذا صراحتاً اشهد لهم بذا الشيء شكراً لهم والله الافضل', 5 ),
	array( 'احمد جاسم', 'تعامل راقي وسرعة في التسليم', 5 ),
	array( 'هيلان سالم', 'خدمة ممتازة وأسعار منافسة', 4 ),
);

foreach ( $testimonials as [ $author, $body, $rating ] ) {
	$found = get_posts(
		array(
			'post_type'      => 'fares_testimonial',
			'title'          => $author,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $found ) {
		continue;
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'fares_testimonial',
			'post_title'   => $author,
			'post_content' => $body,
			'post_status'  => 'publish',
		)
	);
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_fares_rating', $rating );
		WP_CLI::log( "Testimonial: {$author}" );
	}
}

/* ----------------------------------------------------------------- pages */

// Cart/checkout pages must use classic shortcodes (new WC installs create blocks).
$shortcode_pages = array(
	'cart'     => array( 'woocommerce_cart_page_id', '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->', 'سلة المشتريات' ),
	'checkout' => array( 'woocommerce_checkout_page_id', '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->', 'اتمام الطلب' ),
);

foreach ( $shortcode_pages as $which => [ $option, $content, $title ] ) {
	$page_id = (int) get_option( $option );
	if ( ! $page_id ) {
		WP_CLI::warning( "No {$which} page configured." );
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_title'   => $title,
			'post_content' => $content,
		)
	);
	WP_CLI::log( "Page #{$page_id} → [{$which}] shortcode." );
}

// Homepage: static front page rendered by front-page.php.
$front = get_page_by_path( 'home' );
if ( ! $front ) {
	$front_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_name'   => 'home',
			'post_title'  => 'الرئيسية',
			'post_status' => 'publish',
		)
	);
} else {
	$front_id = $front->ID;
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $front_id );

/* ------------------------------------------------------------------ menu */

$menu_name = 'روابط مهمة';
$menu      = wp_get_nav_menu_object( $menu_name );
if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );
	foreach ( array( 'من نحن', 'طريقة تفعيل بلا شوب', 'تحديثات شوب بلس', 'سياسة الاستبدال والاسترجاع', 'سياسة الاستخدام والخصوصية', 'التسويق بالعمولة' ) as $label ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $label,
				'menu-item-url'    => home_url( '/' . sanitize_title( $label ) . '/' ),
				'menu-item-status' => 'publish',
			)
		);
	}
	$locations                 = get_theme_mod( 'nav_menu_locations', array() );
	$locations['footer-links'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
	WP_CLI::log( 'Footer menu created.' );
}

/* --------------------------------------------------------------- serials */

// Enable serial delivery on the VIP product and stock a few dev keys.
$vip_serial_id = wc_get_product_id_by_sku( 'plus-iphone-vip' );
if ( function_exists( 'wcsn_insert_key' ) && $vip_serial_id ) {
	update_post_meta( $vip_serial_id, '_is_serial_number', 'yes' );

	$existing_keys = function_exists( 'wcsn_get_keys' )
		? (int) wcsn_get_keys( array( 'product_id' => $vip_serial_id ), true )
		: 0;

	if ( 0 === $existing_keys ) {
		for ( $i = 1; $i <= 5; $i++ ) {
			$inserted = wcsn_insert_key(
				array(
					'serial_key' => sprintf( 'FARES-DEV-%04d-%04d', $vip_serial_id, $i ),
					'product_id' => $vip_serial_id,
					'status'     => 'available',
				)
			);
			if ( is_wp_error( $inserted ) ) {
				WP_CLI::warning( 'Serial insert: ' . $inserted->get_error_message() );
				break;
			}
		}
		WP_CLI::log( 'Serial keys seeded.' );
	}
}

// Payment gateway for dev checkout flows (COD — no external processor).
$cod = get_option( 'woocommerce_cod_settings', array() );
if ( ! is_array( $cod ) || ( $cod['enabled'] ?? 'no' ) !== 'yes' ) {
	$cod = array_merge(
		is_array( $cod ) ? $cod : array(),
		array(
			'enabled'      => 'yes',
			'title'        => 'الدفع عند الاستلام (تجريبي)',
			'instructions' => 'بوابة تجريبية لبيئة التطوير.',
		)
	);
	update_option( 'woocommerce_cod_settings', $cod );
	WP_CLI::log( 'COD gateway enabled.' );
}

// Woo housekeeping so prices/currency render like the design.
update_option( 'woocommerce_currency', 'EGP' );
update_option( 'woocommerce_price_thousand_sep', ',' );
update_option( 'woocommerce_price_decimal_sep', '.' );

WP_CLI::success( 'Seed complete.' );
