<?php
/**
 * Self-provisioning — creates the store's required pages on any environment
 * without touching the database by hand.
 *
 * The GitHub → Hostinger deploy syncs *files only*; pages live in the database,
 * so a fresh deploy would otherwise ship a theme with no legal pages, no footer
 * menu, and (on a bare install) no homepage or WooCommerce shortcode pages.
 * This module closes that gap: it runs once per provision-version the first
 * time a privileged admin loads wp-admin, then no-ops until the version bumps.
 *
 * Everything here is idempotent and production-SAFE: existing pages are never
 * overwritten (client edits are preserved), only missing pieces are created and
 * WooCommerce cart/checkout pages are repaired only when their shortcode is
 * absent. The same routine backs `bin/provision-content.php` (CLI), so there is
 * a single source of truth.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bump this when the provisioned content changes so deployed sites re-run the
 * routine on the next admin visit. The value is compared against the
 * `fares_provision_version` option.
 */
const FARES_PROVISION_VERSION = '2026-07-09.1';

/**
 * Option key that records the last provision-version applied to this site.
 */
const FARES_PROVISION_OPTION = 'fares_provision_version';

/**
 * Run provisioning once per version, the first time an admin hits wp-admin.
 *
 * Gated so it never fires on the front end, AJAX, or cron, and only for a user
 * who could legitimately create pages. A short transient lock narrows the
 * concurrency window; the routine itself is idempotent regardless.
 */
function fares_store_maybe_provision(): void {
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( get_option( FARES_PROVISION_OPTION ) === FARES_PROVISION_VERSION ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( get_transient( 'fares_provisioning_lock' ) ) {
		return;
	}
	set_transient( 'fares_provisioning_lock', 1, MINUTE_IN_SECONDS );

	fares_store_provision_content();

	update_option( FARES_PROVISION_OPTION, FARES_PROVISION_VERSION );
	delete_transient( 'fares_provisioning_lock' );
}
add_action( 'admin_init', 'fares_store_maybe_provision' );

/**
 * Provision every required piece of store content.
 *
 * @param callable|null $logger Optional log sink (e.g. `WP_CLI::log`). Defaults
 *                              to `error_log` so web runs still leave a trace.
 */
function fares_store_provision_content( ?callable $logger = null ): void {
	$log = $logger ?? static function ( string $message ): void {
		error_log( '[fares-store provision] ' . $message );
	};

	$page_ids = fares_store_provision_legal_pages( $log );
	fares_store_provision_footer_menu( $page_ids, $log );
	fares_store_provision_homepage( $log );
	fares_store_provision_wc_pages( $log );

	$log( 'Provisioning complete.' );
}

/**
 * The five policy/legal pages, keyed by slug, with Arabic content.
 *
 * @return array<int, array{slug:string,title:string,content:string}>
 */
function fares_store_legal_pages(): array {
	$store_name = get_bloginfo( 'name' );
	$channels   = function_exists( 'fares_contact_channels' )
		? fares_contact_channels()
		: array(
			'email'     => 'shopstore417@gmail.com',
			'telegram'  => 'https://t.me/Sho9_store',
			'instagram' => 'https://www.instagram.com/sho9.store',
			'x'         => 'https://x.com/sho9_store',
			'youtube'   => 'https://www.youtube.com/@sho9store',
		);
	$updated = current_time( 'Y/m/d' );
	$email   = $channels['email'];

	$terms = <<<HTML
<h2>مقدمة</h2>
<p>مرحبًا بك في متجر {$store_name}. باستخدامك هذا الموقع وإتمام أي عملية شراء فإنك توافق على الالتزام بالشروط والأحكام التالية. يُرجى قراءتها بعناية قبل استخدام خدماتنا.</p>

<h2>طبيعة المنتجات</h2>
<p>جميع المنتجات المعروضة في المتجر منتجات رقمية (أكواد تفعيل، بطاقات، اشتراكات) تُسلَّم إلكترونيًا عبر البريد الإلكتروني بعد إتمام عملية الدفع بنجاح. لا يتم شحن أي منتجات مادية.</p>

<h2>الحساب والمعلومات</h2>
<ul>
<li>تتحمل مسؤولية تقديم بيانات صحيحة ودقيقة عند الطلب، خاصة البريد الإلكتروني الذي سيُرسل إليه الكود.</li>
<li>لا يتحمل المتجر مسؤولية عدم وصول الكود بسبب إدخال بريد إلكتروني خاطئ.</li>
</ul>

<h2>الاستخدام المسموح</h2>
<p>يُمنع استخدام المتجر لأي غرض غير قانوني أو محاولة الإضرار بالخدمة أو إعادة بيع الأكواد بصورة مخالفة لشروط مزوّدي الخدمة الأصليين.</p>

<h2>الملكية الفكرية</h2>
<p>جميع العلامات التجارية وأسماء المنتجات تعود لأصحابها. استخدامها في المتجر لغرض العرض والبيع فقط.</p>

<h2>حدود المسؤولية</h2>
<p>نبذل جهدنا لضمان دقة المعلومات وصحة الأكواد المسلَّمة. في حال وجود كود غير صالح يُرجى مراجعة <a href="/refund-policy/">سياسة الاستبدال والاسترجاع</a>.</p>

<h2>القانون الواجب التطبيق</h2>
<p>تخضع هذه الشروط لأنظمة المملكة العربية السعودية، وأي نزاع ينشأ عنها يخضع لاختصاص الجهات المختصة في المملكة.</p>

<h2>التعديلات</h2>
<p>يحق للمتجر تحديث هذه الشروط في أي وقت، ويسري التحديث فور نشره على هذه الصفحة.</p>

<p><em>آخر تحديث: {$updated}</em></p>
HTML;

	$privacy = <<<HTML
<h2>مقدمة</h2>
<p>تُوضّح سياسة الخصوصية هذه كيفية جمع متجر {$store_name} لبياناتك واستخدامها وحمايتها عند استخدامك للموقع.</p>

<h2>البيانات التي نجمعها</h2>
<ul>
<li>الاسم والبريد الإلكتروني ورقم الجوال عند إتمام الطلب.</li>
<li>تفاصيل الطلب والمنتجات المشتراة.</li>
<li>بيانات تقنية أساسية مثل نوع المتصفح وملفات تعريف الارتباط (Cookies) لتحسين تجربة التصفح.</li>
</ul>

<h2>كيفية استخدام البيانات</h2>
<ul>
<li>تنفيذ الطلبات وتسليم الأكواد عبر البريد الإلكتروني.</li>
<li>التواصل معك بشأن طلبك أو الدعم الفني.</li>
<li>تحسين خدماتنا وتجربة المستخدم.</li>
</ul>

<h2>مشاركة البيانات مع أطراف ثالثة</h2>
<p>لا نبيع بياناتك. قد تتم مشاركة الحد الأدنى الضروري من البيانات مع مزوّدي خدمة الدفع ومزوّدي إرسال البريد الإلكتروني لغرض إتمام الطلب فقط، وبما يتوافق مع أنظمة حماية البيانات.</p>

<h2>ملفات تعريف الارتباط (Cookies)</h2>
<p>نستخدم ملفات تعريف الارتباط لتشغيل سلة الشراء وحفظ تفضيلاتك. يمكنك تعطيلها من إعدادات متصفحك مع العلم أن ذلك قد يؤثر على بعض وظائف الموقع.</p>

<h2>حماية البيانات</h2>
<p>نتخذ إجراءات تقنية وتنظيمية معقولة لحماية بياناتك من الوصول غير المصرّح به.</p>

<h2>حقوقك</h2>
<p>يحق لك طلب الاطلاع على بياناتك أو تصحيحها أو حذفها بمراسلتنا على <a href="mailto:{$email}">{$email}</a>.</p>

<p><em>آخر تحديث: {$updated}</em></p>
HTML;

	$refund = <<<HTML
<h2>طبيعة المنتجات الرقمية</h2>
<p>نظرًا لأن جميع منتجات متجر {$store_name} منتجات رقمية (أكواد تفعيل واشتراكات) تُسلَّم فورًا، فإنه لا يمكن استرجاع أو استبدال المنتج بعد تسليم الكود أو كشفه، وذلك لاستحالة إرجاع منتج رقمي بعد إتاحته.</p>

<h2>حالات الاستبدال</h2>
<p>نلتزم باستبدال الكود في الحالات التالية:</p>
<ul>
<li>أن يكون الكود غير صالح أو مستخدَمًا مسبقًا عند استلامه.</li>
<li>أن يكون المنتج المُرسَل مختلفًا عن المنتج المطلوب.</li>
</ul>
<p>في هذه الحالات يتم التحقق من المشكلة واستبدال الكود بآخر صالح دون أي رسوم إضافية.</p>

<h2>ما لا يشمله الاستبدال</h2>
<ul>
<li>الشراء بالخطأ أو تغيير الرأي بعد استلام الكود.</li>
<li>عدم توافق المنتج مع جهازك أو حسابك بسبب عدم قراءة وصف المنتج.</li>
<li>الأكواد التي تم تفعيلها بنجاح.</li>
</ul>

<h2>كيفية تقديم طلب</h2>
<p>لتقديم طلب استبدال، تواصل معنا خلال <strong>24 ساعة</strong> من استلام الكود عبر <a href="mailto:{$email}">{$email}</a> أو عبر <a href="{$channels['telegram']}" rel="noopener">تيليجرام</a>، مع إرفاق رقم الطلب وصورة توضّح المشكلة.</p>

<h2>مدة المعالجة</h2>
<p>تتم مراجعة الطلبات والرد عليها في أقرب وقت ممكن خلال أوقات العمل.</p>

<p><em>آخر تحديث: {$updated}</em></p>
HTML;

	$about = <<<HTML
<h2>من نحن</h2>
<p>{$store_name} متجر إلكتروني سعودي متخصص في بيع المنتجات الرقمية وأكواد التفعيل والبطاقات والاشتراكات، مع تسليم فوري وآمن عبر البريد الإلكتروني.</p>

<h2>لماذا نحن</h2>
<ul>
<li>تسليم فوري للأكواد بعد إتمام الدفع.</li>
<li>أسعار تنافسية ومنتجات موثوقة.</li>
<li>دعم فني متجاوب عبر البريد الإلكتروني ووسائل التواصل.</li>
</ul>

<h2>التوثيق والاعتماد</h2>
<p>المتجر موثّق في منصة الأعمال السعودية، ويعمل بموجب:</p>
<ul>
<li>الرقم الضريبي: <strong>312478563400003</strong></li>
<li>وثيقة العمل الحر: <strong>FL-128116989</strong></li>
</ul>

<h2>تواصل معنا</h2>
<p>يسعدنا خدمتك — تفضل بزيارة صفحة <a href="/contact/">اتصل بنا</a> للتواصل المباشر.</p>
HTML;

	$contact = <<<HTML
<h2>اتصل بنا</h2>
<p>نحن هنا لمساعدتك. تواصل معنا عبر أيٍّ من القنوات التالية وسنرد عليك في أقرب وقت ممكن.</p>

<h2>قنوات التواصل</h2>
<ul>
<li>البريد الإلكتروني: <a href="mailto:{$email}">{$email}</a></li>
<li>تيليجرام: <a href="{$channels['telegram']}" rel="noopener">قناة الدعم على تيليجرام</a></li>
<li>إنستغرام: <a href="{$channels['instagram']}" rel="noopener">@sho9.store</a></li>
<li>منصة X: <a href="{$channels['x']}" rel="noopener">@sho9_store</a></li>
<li>يوتيوب: <a href="{$channels['youtube']}" rel="noopener">قناتنا على يوتيوب</a></li>
</ul>

<h2>بيانات المتجر</h2>
<ul>
<li>الرقم الضريبي: <strong>312478563400003</strong></li>
<li>وثيقة العمل الحر: <strong>FL-128116989</strong></li>
</ul>

<p>للاستفسارات المتعلقة بطلب قائم، يُرجى ذكر رقم الطلب لتسريع المساعدة.</p>
HTML;

	return array(
		array(
			'slug'    => 'terms',
			'title'   => 'الشروط والأحكام',
			'content' => $terms,
		),
		array(
			'slug'    => 'privacy-policy',
			'title'   => 'سياسة الخصوصية',
			'content' => $privacy,
		),
		array(
			'slug'    => 'refund-policy',
			'title'   => 'سياسة الاستبدال والاسترجاع',
			'content' => $refund,
		),
		array(
			'slug'    => 'about',
			'title'   => 'من نحن',
			'content' => $about,
		),
		array(
			'slug'    => 'contact',
			'title'   => 'اتصل بنا',
			'content' => $contact,
		),
	);
}

/**
 * Create any missing legal pages; keep existing ones untouched (only publish
 * a stray draft). Returns the resulting page IDs in menu order.
 *
 * @param callable $log Logger.
 * @return int[] Page IDs.
 */
function fares_store_provision_legal_pages( callable $log ): array {
	$ids = array();

	foreach ( fares_store_legal_pages() as $page ) {
		$existing = get_page_by_path( $page['slug'] );

		if ( $existing instanceof WP_Post ) {
			if ( 'publish' !== $existing->post_status ) {
				wp_update_post(
					array(
						'ID'          => $existing->ID,
						'post_status' => 'publish',
					)
				);
			}
			$ids[] = (int) $existing->ID;
			$log( "Page exists, kept: {$page['slug']} (#{$existing->ID})" );
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_name'    => $page['slug'],
				'post_title'   => $page['title'],
				'post_content' => $page['content'],
				'post_status'  => 'publish',
				'post_author'  => 1,
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			$log( "Failed to create page {$page['slug']}: " . $page_id->get_error_message() );
			continue;
		}

		$ids[] = (int) $page_id;
		$log( "Page created: {$page['slug']} (#{$page_id})" );
	}

	// Point WordPress's official privacy-policy setting at our page.
	$privacy = get_page_by_path( 'privacy-policy' );
	if ( $privacy instanceof WP_Post ) {
		update_option( 'wp_page_for_privacy_policy', (int) $privacy->ID );
	}

	return $ids;
}

/**
 * Rebuild the footer "روابط مهمة" menu deterministically and bind it to the
 * theme's `footer-links` location so no footer link 404s.
 *
 * @param int[]    $page_ids Legal page IDs.
 * @param callable $log      Logger.
 */
function fares_store_provision_footer_menu( array $page_ids, callable $log ): void {
	if ( empty( $page_ids ) ) {
		return;
	}

	$menu_name = 'روابط مهمة';

	$locations = get_nav_menu_locations();
	$menu      = ! empty( $locations['footer-links'] ) ? wp_get_nav_menu_object( (int) $locations['footer-links'] ) : false;

	if ( ! $menu ) {
		$menu = wp_get_nav_menu_object( $menu_name );
	}

	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			$log( 'Could not create footer menu: ' . $menu_id->get_error_message() );
			return;
		}
		$log( 'Footer menu created.' );
	} else {
		$menu_id = (int) $menu->term_id;
		foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( (int) $item->ID, true );
		}
		$log( 'Footer menu items reset.' );
	}

	foreach ( $page_ids as $page_id ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-title'     => get_the_title( $page_id ),
				'menu-item-status'    => 'publish',
			)
		);
	}

	$locations                 = get_theme_mod( 'nav_menu_locations', array() );
	$locations['footer-links'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	$log( 'Footer menu wired to ' . count( $page_ids ) . ' policy pages.' );
}

/**
 * Ensure a `home` page exists and is the static front page. Respects an
 * existing valid static-front-page choice; only fixes a bare/blog install.
 *
 * @param callable $log Logger.
 */
function fares_store_provision_homepage( callable $log ): void {
	$front = get_page_by_path( 'home' );

	if ( ! $front instanceof WP_Post ) {
		$front_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_name'   => 'home',
				'post_title'  => 'الرئيسية',
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $front_id ) ) {
			$log( 'Failed to create homepage: ' . $front_id->get_error_message() );
			return;
		}
		$log( "Homepage created (#{$front_id})." );
	} else {
		$front_id = (int) $front->ID;
		$log( "Homepage exists (#{$front_id})." );
	}

	$current_front = (int) get_option( 'page_on_front' );
	$is_static     = 'page' === get_option( 'show_on_front' ) && get_post( $current_front ) instanceof WP_Post;

	if ( ! $is_static ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );
		$log( 'Static front page set to home.' );
	}
}

/**
 * Repair WooCommerce cart/checkout pages so they use the classic shortcodes the
 * theme's checkout CSS/JS targets, and give the store pages Arabic titles.
 * Only rewrites content when the shortcode is missing (block page / empty), so
 * client edits survive.
 *
 * @param callable $log Logger.
 */
function fares_store_provision_wc_pages( callable $log ): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		$log( 'WooCommerce inactive — skipped WC page provisioning.' );
		return;
	}

	$shortcode_pages = array(
		'cart'     => array(
			'option'    => 'woocommerce_cart_page_id',
			'shortcode' => 'woocommerce_cart',
			'title'     => 'سلة المشتريات',
		),
		'checkout' => array(
			'option'    => 'woocommerce_checkout_page_id',
			'shortcode' => 'woocommerce_checkout',
			'title'     => 'اتمام الطلب',
		),
	);

	foreach ( $shortcode_pages as $which => $spec ) {
		$page_id = (int) get_option( $spec['option'] );
		$content = '<!-- wp:shortcode -->[' . $spec['shortcode'] . ']<!-- /wp:shortcode -->';

		if ( ! $page_id || ! get_post( $page_id ) instanceof WP_Post ) {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_name'    => $which,
					'post_title'   => $spec['title'],
					'post_content' => $content,
					'post_status'  => 'publish',
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				$log( "Failed to create {$which} page: " . $page_id->get_error_message() );
				continue;
			}
			update_option( $spec['option'], (int) $page_id );
			$log( "WC {$which} page created (#{$page_id})." );
			continue;
		}

		$post = get_post( $page_id );
		if ( false === strpos( (string) $post->post_content, '[' . $spec['shortcode'] . ']' ) ) {
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_title'   => $spec['title'],
					'post_content' => $content,
				)
			);
			$log( "WC {$which} page repaired → [{$spec['shortcode']}]." );
		}
	}

	// Arabicise the shop/account titles only when still on the English default.
	$title_defaults = array(
		'woocommerce_shop_page_id'      => array( 'Shop', 'المتجر' ),
		'woocommerce_myaccount_page_id' => array( 'My account', 'حسابي' ),
	);

	foreach ( $title_defaults as $option => list( $english, $arabic ) ) {
		$page_id = (int) get_option( $option );
		if ( ! $page_id ) {
			continue;
		}
		$post = get_post( $page_id );
		if ( $post instanceof WP_Post && $english === $post->post_title ) {
			wp_update_post(
				array(
					'ID'         => $page_id,
					'post_title' => $arabic,
				)
			);
			$log( "Renamed {$english} → {$arabic} (#{$page_id})." );
		}
	}

	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules( false );
	}
}
