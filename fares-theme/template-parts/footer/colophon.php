<?php
/**
 * Footer colophon (copyright row).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-footer__colophon">
	<p>
		<?php
		/* translators: 1: year, 2: site name. */
		printf( esc_html__( 'الحقوق محفوظة | %1$s %2$s', 'fares-theme' ), esc_html( gmdate( 'Y' ) ), esc_html( get_bloginfo( 'name' ) ) );
		?>
	</p>
</div>
