<?php
/**
 * Production content provisioning — legal pages + footer menu.
 *
 * Idempotent, production-SAFE (no demo products, no fixtures). Creates the
 * store's required policy pages as real, editable WordPress pages and wires the
 * `footer-links` menu to them so no footer link ever 404s.
 *
 * Run once per environment:
 *   wp eval-file bin/provision-content.php          (or: npm run provision)
 *
 * Re-running is safe: existing pages are preserved (client edits are never
 * overwritten); only missing pages are created. The footer menu is rebuilt
 * deterministically from these pages on every run.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Emit a CLI log line.
 *
 * @param string $message Message.
 */
function fares_provision_log( string $message ): void {
	WP_CLI::log( $message );
}

$fares_store_name = get_bloginfo( 'name' );
$fares_channels   = function_exists( 'fares_contact_channels' )
	? fares_contact_channels()
	: array(
		'email'     => 'shopstore417@gmail.com',
		'telegram'  => 'https://t.me/Sho9_store',
		'instagram' => 'https://www.instagram.com/sho9.store',
		'x'         => 'https://x.com/sho9_store',
		'youtube'   => 'https://www.youtube.com/@sho9store',
	);
$fares_updated    = current_time( 'Y/m/d' );
$fares_email      = $fares_channels['email'];

/* ---------------------------------------------------------------- content */

$fares_terms = <<<HTML
<h2>مقدمة</h2>
<p>مرحبًا بك في متجر {$fares_store_name}. باستخدامك هذا الموقع وإتمام أي عملية شراء فإنك توافق على الالتزام بالشروط والأحكام التالية. يُرجى قراءتها بعناية قبل استخدام خدماتنا.</p>

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

<p><em>آخر تحديث: {$fares_updated}</em></p>
HTML;

$fares_privacy = <<<HTML
<h2>مقدمة</h2>
<p>تُوضّح سياسة الخصوصية هذه كيفية جمع متجر {$fares_store_name} لبياناتك واستخدامها وحمايتها عند استخدامك للموقع.</p>

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
<p>يحق لك طلب الاطلاع على بياناتك أو تصحيحها أو حذفها بمراسلتنا على <a href="mailto:{$fares_email}">{$fares_email}</a>.</p>

<p><em>آخر تحديث: {$fares_updated}</em></p>
HTML;

$fares_refund = <<<HTML
<h2>طبيعة المنتجات الرقمية</h2>
<p>نظرًا لأن جميع منتجات متجر {$fares_store_name} منتجات رقمية (أكواد تفعيل واشتراكات) تُسلَّم فورًا، فإنه لا يمكن استرجاع أو استبدال المنتج بعد تسليم الكود أو كشفه، وذلك لاستحالة إرجاع منتج رقمي بعد إتاحته.</p>

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
<p>لتقديم طلب استبدال، تواصل معنا خلال <strong>24 ساعة</strong> من استلام الكود عبر <a href="mailto:{$fares_email}">{$fares_email}</a> أو عبر <a href="{$fares_channels['telegram']}" rel="noopener">تيليجرام</a>، مع إرفاق رقم الطلب وصورة توضّح المشكلة.</p>

<h2>مدة المعالجة</h2>
<p>تتم مراجعة الطلبات والرد عليها في أقرب وقت ممكن خلال أوقات العمل.</p>

<p><em>آخر تحديث: {$fares_updated}</em></p>
HTML;

$fares_about = <<<HTML
<h2>من نحن</h2>
<p>{$fares_store_name} متجر إلكتروني سعودي متخصص في بيع المنتجات الرقمية وأكواد التفعيل والبطاقات والاشتراكات، مع تسليم فوري وآمن عبر البريد الإلكتروني.</p>

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

$fares_contact = <<<HTML
<h2>اتصل بنا</h2>
<p>نحن هنا لمساعدتك. تواصل معنا عبر أيٍّ من القنوات التالية وسنرد عليك في أقرب وقت ممكن.</p>

<h2>قنوات التواصل</h2>
<ul>
<li>البريد الإلكتروني: <a href="mailto:{$fares_email}">{$fares_email}</a></li>
<li>تيليجرام: <a href="{$fares_channels['telegram']}" rel="noopener">قناة الدعم على تيليجرام</a></li>
<li>إنستغرام: <a href="{$fares_channels['instagram']}" rel="noopener">@sho9.store</a></li>
<li>منصة X: <a href="{$fares_channels['x']}" rel="noopener">@sho9_store</a></li>
<li>يوتيوب: <a href="{$fares_channels['youtube']}" rel="noopener">قناتنا على يوتيوب</a></li>
</ul>

<h2>بيانات المتجر</h2>
<ul>
<li>الرقم الضريبي: <strong>312478563400003</strong></li>
<li>وثيقة العمل الحر: <strong>FL-128116989</strong></li>
</ul>

<p>للاستفسارات المتعلقة بطلب قائم، يُرجى ذكر رقم الطلب لتسريع المساعدة.</p>
HTML;

/* ------------------------------------------------------------------ pages */

$fares_pages = array(
	array(
		'slug'    => 'terms',
		'title'   => 'الشروط والأحكام',
		'content' => $fares_terms,
	),
	array(
		'slug'    => 'privacy-policy',
		'title'   => 'سياسة الخصوصية',
		'content' => $fares_privacy,
	),
	array(
		'slug'    => 'refund-policy',
		'title'   => 'سياسة الاستبدال والاسترجاع',
		'content' => $fares_refund,
	),
	array(
		'slug'    => 'about',
		'title'   => 'من نحن',
		'content' => $fares_about,
	),
	array(
		'slug'    => 'contact',
		'title'   => 'اتصل بنا',
		'content' => $fares_contact,
	),
);

$fares_page_ids = array();

foreach ( $fares_pages as $page ) {
	$existing = get_page_by_path( $page['slug'] );

	if ( $existing instanceof WP_Post ) {
		// Preserve any edits the client has made; just make sure it's published.
		if ( 'publish' !== $existing->post_status ) {
			wp_update_post(
				array(
					'ID'          => $existing->ID,
					'post_status' => 'publish',
				)
			);
		}
		$fares_page_ids[] = (int) $existing->ID;
		fares_provision_log( "Page exists, kept: {$page['slug']} (#{$existing->ID})" );
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
		WP_CLI::warning( "Failed to create page {$page['slug']}: " . $page_id->get_error_message() );
		continue;
	}

	$fares_page_ids[] = (int) $page_id;
	fares_provision_log( "Page created: {$page['slug']} (#{$page_id})" );
}

/* ------------------------------------------------------------- footer menu */

$menu_name = 'روابط مهمة';

// Prefer the menu already bound to the footer-links location; fall back to name.
$locations = get_nav_menu_locations();
$menu      = ! empty( $locations['footer-links'] ) ? wp_get_nav_menu_object( (int) $locations['footer-links'] ) : false;

if ( ! $menu ) {
	$menu = wp_get_nav_menu_object( $menu_name );
}

if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) {
		WP_CLI::error( 'Could not create footer menu: ' . $menu_id->get_error_message() );
	}
	fares_provision_log( 'Footer menu created.' );
} else {
	$menu_id = (int) $menu->term_id;
	// Deterministic rebuild: clear existing items so stale 404 custom links
	// (from the dev seeder) are removed and pages aren't duplicated on re-run.
	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( (int) $item->ID, true );
	}
	fares_provision_log( 'Footer menu items reset.' );
}

foreach ( $fares_page_ids as $page_id ) {
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

fares_provision_log( 'Footer menu wired to ' . count( $fares_page_ids ) . ' policy pages. Done.' );
