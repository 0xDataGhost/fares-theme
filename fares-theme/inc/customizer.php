<?php
/**
 * Theme Customizer — client-editable content.
 *
 * Currently: the homepage hero banner (image + optional link). Values are
 * theme_mods; the hero image is stored as an attachment ID (same convention as
 * category card artwork) so we get srcset + the media library's alt text.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

const FARES_HERO_IMAGE_MOD = 'fares_hero_image';
const FARES_HERO_LINK_MOD  = 'fares_hero_link';

const FARES_ANNOUNCEMENT_ENABLED_MOD = 'fares_announcement_enabled';
const FARES_ANNOUNCEMENT_TEXT_MOD    = 'fares_announcement_text';
const FARES_ANNOUNCEMENT_LINK_MOD    = 'fares_announcement_link';

/**
 * Default announcement-bar text (shown until the client sets their own).
 */
function fares_announcement_default_text(): string {
	return __( 'يوجد لدينا جميع طرق الدفع لاي دولة بالعالم للتواصل اضغط هنا', 'fares-theme' );
}

/**
 * Register the "Home banner" section and its controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function fares_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'fares_hero',
		array(
			'title'       => __( 'البانر الرئيسي', 'fares-theme' ),
			'description' => __( 'الصورة الظاهرة أعلى الصفحة الرئيسية. الحجم المثالي 1100×900 بكسل تقريبًا.', 'fares-theme' ),
			'priority'    => 30,
		)
	);

	// Hero image (attachment ID).
	$wp_customize->add_setting(
		FARES_HERO_IMAGE_MOD,
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			FARES_HERO_IMAGE_MOD,
			array(
				'label'       => __( 'صورة البانر', 'fares-theme' ),
				'description' => __( 'اترك الحقل فارغًا لاستخدام الصورة الافتراضية للقالب.', 'fares-theme' ),
				'section'     => 'fares_hero',
				'mime_type'   => 'image',
			)
		)
	);

	// Optional click-through link for the banner.
	$wp_customize->add_setting(
		FARES_HERO_LINK_MOD,
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		FARES_HERO_LINK_MOD,
		array(
			'label'       => __( 'رابط البانر (اختياري)', 'fares-theme' ),
			'description' => __( 'عند إضافته يصبح البانر قابلًا للنقر ويوجّه إلى هذا الرابط.', 'fares-theme' ),
			'section'     => 'fares_hero',
			'type'        => 'url',
		)
	);

	/* ---------------------------------------------------- announcement bar */

	$wp_customize->add_section(
		'fares_announcement',
		array(
			'title'       => __( 'شريط الإعلان', 'fares-theme' ),
			'description' => __( 'الشريط العلوي الظاهر أعلى كل صفحة.', 'fares-theme' ),
			'priority'    => 31,
		)
	);

	// Show / hide the bar.
	$wp_customize->add_setting(
		FARES_ANNOUNCEMENT_ENABLED_MOD,
		array(
			'default'           => true,
			'sanitize_callback' => 'wp_validate_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		FARES_ANNOUNCEMENT_ENABLED_MOD,
		array(
			'label'   => __( 'إظهار شريط الإعلان', 'fares-theme' ),
			'section' => 'fares_announcement',
			'type'    => 'checkbox',
		)
	);

	// Bar text.
	$wp_customize->add_setting(
		FARES_ANNOUNCEMENT_TEXT_MOD,
		array(
			'default'           => fares_announcement_default_text(),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		FARES_ANNOUNCEMENT_TEXT_MOD,
		array(
			'label'   => __( 'نص الإعلان', 'fares-theme' ),
			'section' => 'fares_announcement',
			'type'    => 'text',
		)
	);

	// Optional click-through link.
	$wp_customize->add_setting(
		FARES_ANNOUNCEMENT_LINK_MOD,
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		FARES_ANNOUNCEMENT_LINK_MOD,
		array(
			'label'       => __( 'رابط الإعلان (اختياري)', 'fares-theme' ),
			'description' => __( 'عند إضافته يصبح نص الإعلان قابلًا للنقر ويوجّه إلى هذا الرابط.', 'fares-theme' ),
			'section'     => 'fares_announcement',
			'type'        => 'url',
		)
	);
}
add_action( 'customize_register', 'fares_customize_register' );
