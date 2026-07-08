<?php
/**
 * 404 template.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-container fares-404">
	<h1><?php esc_html_e( 'الصفحة غير موجودة', 'fares-theme' ); ?></h1>
	<p><?php esc_html_e( 'عذراً، الصفحة التي تبحث عنها غير متوفرة.', 'fares-theme' ); ?></p>
	<a class="fares-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة للرئيسية', 'fares-theme' ); ?></a>
</main>

<?php
get_footer();
