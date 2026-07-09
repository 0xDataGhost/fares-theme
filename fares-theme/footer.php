<?php
/**
 * Site footer — radius-12 card: contact / links / trust columns, payment
 * tiles, dashed divider, colophon.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="fares-footer" role="contentinfo">
	<div class="fares-container">
		<div class="fares-footer__card">
			<?php get_template_part( 'template-parts/footer/trust-badges' ); ?>
			<?php get_template_part( 'template-parts/footer/contact' ); ?>
			<?php get_template_part( 'template-parts/footer/links' ); ?>
		</div>
		<?php get_template_part( 'template-parts/footer/payment-icons' ); ?>
		<hr class="fares-footer__divider" />
		<?php get_template_part( 'template-parts/footer/colophon' ); ?>
	</div>
</footer>

<?php get_template_part( 'template-parts/header/mobile-bottom-nav' ); ?>

<?php wp_footer(); ?>
</body>
</html>
