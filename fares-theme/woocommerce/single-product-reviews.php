<?php
/**
 * Product reviews — custom layout per Figma 9:1556: aggregate satisfaction
 * percentage, review count, review list (avatar, stars, dates), load-more
 * pagination, then the review form.
 *
 * Overrides woocommerce/templates/single-product-reviews.php.
 *
 * @package fares-theme
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

$fares_count   = $product->get_review_count();
$fares_average = (float) $product->get_average_rating();
$fares_percent = $fares_count > 0 ? round( ( $fares_average / 5 ) * 100, 2 ) : 0;
?>
<div id="reviews" class="woocommerce-Reviews fares-reviews">
	<?php if ( $fares_count > 0 ) : ?>
		<header class="fares-reviews__summary">
			<p class="fares-reviews__stat"><span dir="ltr">%<?php echo esc_html( number_format_i18n( $fares_percent, 2 ) ); ?></span></p>
			<p class="fares-reviews__label"><?php esc_html_e( 'أوصوا بالمنتج', 'fares-theme' ); ?></p>
			<p class="fares-reviews__count">
				<?php
				/* translators: %s: number of reviews. */
				echo esc_html( sprintf( _n( '%s تعليق', '%s تعليق', $fares_count, 'fares-theme' ), number_format_i18n( $fares_count ) ) );
				?>
			</p>
		</header>
	<?php endif; ?>

	<div id="comments">
		<?php if ( have_comments() ) : ?>
			<ol class="commentlist fares-reviews__list">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
			</ol>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				?>
				<nav class="fares-reviews__pagination">
					<?php
					paginate_comments_links(
						apply_filters(
							'woocommerce_comment_pagination_args',
							array(
								'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
								'next_text' => is_rtl() ? '&larr;' : '&rarr;',
								'type'      => 'list',
							)
						)
					);
					?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<p class="woocommerce-noreviews fares-reviews__empty"><?php esc_html_e( 'لا توجد تعليقات بعد — كن أول من يقيّم هذا المنتج.', 'fares-theme' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
		<div id="review_form_wrapper" class="fares-reviews__form">
			<?php
			$fares_commenter    = wp_get_current_commenter();
			$fares_comment_form = array(
				'title_reply'         => $fares_count ? __( 'أضف تعليقك', 'fares-theme' ) : __( 'كن أول من يقيّم هذا المنتج', 'fares-theme' ),
				'title_reply_to'      => __( 'الرد على %s', 'fares-theme' ),
				'title_reply_before'  => '<span id="reply-title" class="comment-reply-title">',
				'title_reply_after'   => '</span>',
				'comment_notes_after' => '',
				'label_submit'        => __( 'إرسال التقييم', 'fares-theme' ),
				'logged_in_as'        => '',
				'comment_field'       => '',
			);

			$fares_name_email_required = (bool) get_option( 'require_name_email', 1 );
			$fares_fields              = array(
				'author' => array(
					'label'    => __( 'الاسم', 'fares-theme' ),
					'type'     => 'text',
					'value'    => $fares_commenter['comment_author'],
					'required' => $fares_name_email_required,
				),
				'email'  => array(
					'label'    => __( 'البريد الإلكتروني', 'fares-theme' ),
					'type'     => 'email',
					'value'    => $fares_commenter['comment_author_email'],
					'required' => $fares_name_email_required,
				),
			);

			$fares_comment_form['fields'] = array();

			foreach ( $fares_fields as $fares_key => $fares_field ) {
				$fares_field_html  = '<p class="comment-form-' . esc_attr( $fares_key ) . '">';
				$fares_field_html .= '<label for="' . esc_attr( $fares_key ) . '">' . esc_html( $fares_field['label'] );

				if ( $fares_field['required'] ) {
					$fares_field_html .= '&nbsp;<span class="required">*</span>';
				}

				$fares_field_html .= '</label><input id="' . esc_attr( $fares_key ) . '" name="' . esc_attr( $fares_key ) . '" type="' . esc_attr( $fares_field['type'] ) . '" value="' . esc_attr( $fares_field['value'] ) . '" size="30" ' . ( $fares_field['required'] ? 'required' : '' ) . ' /></p>';

				$fares_comment_form['fields'][ $fares_key ] = $fares_field_html;
			}

			if ( wc_review_ratings_enabled() ) {
				$fares_comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'تقييمك', 'fares-theme' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" ' . ( wc_review_ratings_required() ? 'required' : '' ) . '>
					<option value="">' . esc_html__( 'اختر&hellip;', 'fares-theme' ) . '</option>
					<option value="5">' . esc_html__( 'ممتاز', 'fares-theme' ) . '</option>
					<option value="4">' . esc_html__( 'جيد جداً', 'fares-theme' ) . '</option>
					<option value="3">' . esc_html__( 'جيد', 'fares-theme' ) . '</option>
					<option value="2">' . esc_html__( 'مقبول', 'fares-theme' ) . '</option>
					<option value="1">' . esc_html__( 'ضعيف', 'fares-theme' ) . '</option>
				</select></div>';
			}

			$fares_comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'تعليقك', 'fares-theme' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>';

			comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $fares_comment_form ) );
			?>
		</div>
	<?php else : ?>
		<p class="woocommerce-verification-required fares-reviews__gate"><?php esc_html_e( 'التقييم متاح لمن اشترى هذا المنتج فقط.', 'fares-theme' ); ?></p>
	<?php endif; ?>

	<div class="clear"></div>
</div>
