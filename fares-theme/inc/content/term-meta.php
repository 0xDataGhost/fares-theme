<?php
/**
 * Product category "card artwork" term meta.
 *
 * The homepage category grid uses art-directed images distinct from the Woo
 * category thumbnail. Stored as an attachment ID in _fares_card_artwork.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

const FARES_CARD_ARTWORK_META = '_fares_card_artwork';

/**
 * Get the card artwork attachment ID for a category (0 if unset).
 *
 * @param int $term_id product_cat term ID.
 */
function fares_get_category_artwork_id( int $term_id ): int {
	return absint( get_term_meta( $term_id, FARES_CARD_ARTWORK_META, true ) );
}

/**
 * Render the field on the edit-term screen.
 *
 * @param WP_Term $term Term being edited.
 */
function fares_category_artwork_field( WP_Term $term ): void {
	$artwork_id = fares_get_category_artwork_id( $term->term_id );
	wp_nonce_field( 'fares_card_artwork', 'fares_card_artwork_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fares-card-artwork"><?php esc_html_e( 'صورة بطاقة القسم (الرئيسية)', 'fares-theme' ); ?></label></th>
		<td>
			<input type="number" min="0" id="fares-card-artwork" name="fares_card_artwork" value="<?php echo esc_attr( (string) $artwork_id ); ?>" class="small-text" />
			<p class="description"><?php esc_html_e( 'معرّف الصورة (Attachment ID) لبطاقة القسم في الصفحة الرئيسية.', 'fares-theme' ); ?></p>
			<?php if ( $artwork_id ) : ?>
				<?php echo wp_get_attachment_image( $artwork_id, 'thumbnail' ); ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'fares_category_artwork_field' );

/**
 * Save the field.
 *
 * @param int $term_id Term ID.
 */
function fares_save_category_artwork( int $term_id ): void {
	if (
		! isset( $_POST['fares_card_artwork'], $_POST['fares_card_artwork_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['fares_card_artwork_nonce'] ), 'fares_card_artwork' )
		|| ! current_user_can( 'manage_product_terms' )
	) {
		return;
	}

	update_term_meta( $term_id, FARES_CARD_ARTWORK_META, absint( $_POST['fares_card_artwork'] ) );
}
add_action( 'edited_product_cat', 'fares_save_category_artwork' );
