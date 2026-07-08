<?php
/**
 * Logo / site branding.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-branding">
	<?php if ( has_custom_logo() ) : ?>
		<?php the_custom_logo(); ?>
	<?php else : ?>
		<a class="fares-branding__name" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php bloginfo( 'name' ); ?>
		</a>
	<?php endif; ?>
</div>
