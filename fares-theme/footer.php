<?php
/**
 * Site footer — composed from template-parts/footer/* (built out in Phase 2).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="fares-footer" role="contentinfo">
	<div class="fares-container">
		<?php get_template_part( 'template-parts/footer/contact' ); ?>
		<?php get_template_part( 'template-parts/footer/links' ); ?>
		<?php get_template_part( 'template-parts/footer/trust-badges' ); ?>
		<?php get_template_part( 'template-parts/footer/payment-icons' ); ?>
		<?php get_template_part( 'template-parts/footer/colophon' ); ?>
	</div>
</footer>

<?php get_template_part( 'template-parts/header/mobile-bottom-nav' ); ?>

<?php wp_footer(); ?>
</body>
</html>
