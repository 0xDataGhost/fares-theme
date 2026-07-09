<?php
/**
 * Designed content-page layout — legal, about, and contact pages.
 *
 * Editorial dark layout: atmospheric hero (eyebrow + title + meta), an optional
 * JS-built table-of-contents rail, and a reading-measure content card. The
 * contact page swaps raw content for a designed channels grid.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_slug     = (string) get_post_field( 'post_name', get_the_ID() );
$fares_is_legal = in_array( $fares_slug, array( 'terms', 'privacy-policy', 'refund-policy' ), true );

/* Contextual eyebrow label per page. */
$fares_eyebrow = match ( $fares_slug ) {
	'terms', 'privacy-policy', 'refund-policy' => __( 'المركز القانوني', 'fares-theme' ),
	'about'   => __( 'قصّتنا', 'fares-theme' ),
	'contact' => __( 'خدمة العملاء', 'fares-theme' ),
	default   => get_bloginfo( 'name' ),
};

/* Contextual line icon per page (inline SVG, currentColor). */
$fares_icon = match ( $fares_slug ) {
	'privacy-policy' => '<path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
	'refund-policy'  => '<path d="M4 12a8 8 0 0 1 13.7-5.6L20 8"/><path d="M20 4v4h-4"/><path d="M20 12a8 8 0 0 1-13.7 5.6L4 16"/><path d="M4 20v-4h4"/>',
	'about'          => '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-6h6v6"/>',
	'contact'        => '<path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.5 8.6 8.6 0 0 1-3.7-.8L3 21l1.8-5.3A8.4 8.4 0 0 1 12.5 3 8.4 8.4 0 0 1 21 11.5z"/>',
	default          => '<path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h6"/>',
};
$fares_icon_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $fares_icon . '</svg>';
?>

<div class="fares-page__progress" aria-hidden="true"><span class="fares-page__progress-fill" data-page-progress></span></div>

<article <?php post_class( 'fares-page' ); ?>>
	<header class="fares-page__hero">
		<div class="fares-page__hero-inner fares-container">
			<nav class="fares-breadcrumb fares-page__breadcrumb" aria-label="<?php esc_attr_e( 'مسار التنقل', 'fares-theme' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'الرئيسية', 'fares-theme' ); ?></a>
				<span class="fares-breadcrumb__sep" aria-hidden="true"></span>
				<span><?php the_title(); ?></span>
			</nav>

			<p class="fares-page__eyebrow"><?php echo $fares_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG ?><span><?php echo esc_html( $fares_eyebrow ); ?></span></p>

			<h1 class="fares-page__title"><?php the_title(); ?></h1>

			<?php if ( $fares_is_legal ) : ?>
				<p class="fares-page__meta">
					<span class="fares-page__meta-dot" aria-hidden="true"></span>
					<?php
					printf(
						/* translators: %s: last modified date. */
						esc_html__( 'آخر تحديث: %s', 'fares-theme' ),
						esc_html( get_the_modified_date( 'Y/m/d' ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</header>

	<div class="fares-page__body fares-container" data-page-body>
		<aside class="fares-page__toc" data-page-toc hidden>
			<p class="fares-page__toc-title"><?php esc_html_e( 'محتويات الصفحة', 'fares-theme' ); ?></p>
			<ul class="fares-page__toc-list" data-page-toc-list></ul>
		</aside>

		<?php if ( 'contact' === $fares_slug ) : ?>
			<div class="fares-page__content fares-contact entry-content">
				<?php get_template_part( 'template-parts/page/contact-channels' ); ?>
			</div>
		<?php else : ?>
			<div class="fares-page__content entry-content" data-page-article>
				<?php the_content(); ?>
			</div>
		<?php endif; ?>
	</div>
</article>
