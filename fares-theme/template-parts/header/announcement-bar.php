<?php
/**
 * Top announcement bar.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! get_theme_mod( FARES_ANNOUNCEMENT_ENABLED_MOD, true ) ) {
	return;
}

$fares_announcement_text = trim( (string) get_theme_mod( FARES_ANNOUNCEMENT_TEXT_MOD, fares_announcement_default_text() ) );
if ( '' === $fares_announcement_text ) {
	return;
}

$fares_announcement_link = get_theme_mod( FARES_ANNOUNCEMENT_LINK_MOD, '' );
?>
<div class="fares-announcement">
	<div class="fares-container fares-announcement__inner">
		<p class="fares-announcement__text">
			<?php if ( $fares_announcement_link ) : ?>
				<a class="fares-announcement__link" href="<?php echo esc_url( $fares_announcement_link ); ?>">
					<?php echo esc_html( $fares_announcement_text ); ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $fares_announcement_text ); ?>
			<?php endif; ?>
		</p>
	</div>
</div>
