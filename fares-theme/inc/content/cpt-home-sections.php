<?php
/**
 * "Home Section" custom post type — dashboard-managed homepage bands.
 *
 * Lets the client add homepage sections without code: either a decorative
 * banner, or a banner + product carousel bound to a product category. Each
 * section is a post with meta; the homepage renders published sections ordered
 * by the "Order" attribute (menu_order) through the existing section renderers.
 *
 * Presentation-domain content structure, same home as the testimonial CPT.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

const FARES_SECTION_TYPE_META        = '_fares_section_type';
const FARES_SECTION_CATEGORY_META    = '_fares_section_category';
const FARES_SECTION_COUNT_META       = '_fares_section_count';
const FARES_SECTION_BANNER_META      = '_fares_section_banner';
const FARES_SECTION_BANNER_LINK_META = '_fares_section_banner_link';
const FARES_SECTION_VIEW_ALL_META    = '_fares_section_view_all';

const FARES_SECTION_DEFAULT_COUNT = 8;
const FARES_SECTION_MAX_COUNT     = 20;

/**
 * Register the home-section CPT.
 */
function fares_register_home_section_cpt(): void {
	register_post_type(
		'fares_home_section',
		array(
			'labels'          => array(
				'name'          => __( 'أقسام الصفحة الرئيسية', 'fares-theme' ),
				'singular_name' => __( 'قسم رئيسي', 'fares-theme' ),
				'add_new_item'  => __( 'إضافة قسم جديد', 'fares-theme' ),
				'edit_item'     => __( 'تعديل القسم', 'fares-theme' ),
				'menu_name'     => __( 'أقسام الرئيسية', 'fares-theme' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-images-alt2',
			'supports'        => array( 'title', 'page-attributes' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'fares_register_home_section_cpt' );

/**
 * Register the section's settings meta box.
 */
function fares_home_section_meta_box_register(): void {
	add_meta_box(
		'fares_home_section_settings',
		__( 'إعدادات القسم', 'fares-theme' ),
		'fares_home_section_meta_box',
		'fares_home_section',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'fares_home_section_meta_box_register' );

/**
 * Render the section settings meta box.
 *
 * @param WP_Post $post Section post.
 */
function fares_home_section_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'fares_home_section', 'fares_home_section_nonce' );

	$type        = get_post_meta( $post->ID, FARES_SECTION_TYPE_META, true ) ?: 'products';
	$category    = absint( get_post_meta( $post->ID, FARES_SECTION_CATEGORY_META, true ) );
	$count       = absint( get_post_meta( $post->ID, FARES_SECTION_COUNT_META, true ) ) ?: FARES_SECTION_DEFAULT_COUNT;
	$banner      = absint( get_post_meta( $post->ID, FARES_SECTION_BANNER_META, true ) );
	$banner_link = (string) get_post_meta( $post->ID, FARES_SECTION_BANNER_LINK_META, true );
	$view_all    = (string) get_post_meta( $post->ID, FARES_SECTION_VIEW_ALL_META, true );
	?>
	<p>
		<label for="fares-section-type"><strong><?php esc_html_e( 'نوع القسم', 'fares-theme' ); ?></strong></label><br />
		<select id="fares-section-type" name="fares_section_type">
			<option value="products" <?php selected( $type, 'products' ); ?>><?php esc_html_e( 'قسم منتجات (بانر + شريط منتجات)', 'fares-theme' ); ?></option>
			<option value="banner" <?php selected( $type, 'banner' ); ?>><?php esc_html_e( 'بانر فقط', 'fares-theme' ); ?></option>
		</select>
		<span class="description"><?php esc_html_e( 'عنوان القسم أعلاه يظهر كترويسة لقسم المنتجات.', 'fares-theme' ); ?></span>
	</p>

	<p>
		<label for="fares-section-category"><strong><?php esc_html_e( 'التصنيف (لقسم المنتجات)', 'fares-theme' ); ?></strong></label><br />
		<?php
		wp_dropdown_categories(
			array(
				'taxonomy'         => 'product_cat',
				'name'             => 'fares_section_category',
				'id'               => 'fares-section-category',
				'selected'         => $category,
				'show_option_none' => __( '— اختر تصنيفًا —', 'fares-theme' ),
				'option_none_value' => '0',
				'hide_empty'       => false,
				'orderby'          => 'name',
			)
		);
		?>
	</p>

	<p>
		<label for="fares-section-count"><strong><?php esc_html_e( 'عدد المنتجات المعروضة', 'fares-theme' ); ?></strong></label><br />
		<input type="number" id="fares-section-count" name="fares_section_count" min="1" max="<?php echo esc_attr( (string) FARES_SECTION_MAX_COUNT ); ?>" value="<?php echo esc_attr( (string) $count ); ?>" class="small-text" />
	</p>

	<p>
		<label for="fares-section-banner"><strong><?php esc_html_e( 'صورة البانر (Attachment ID)', 'fares-theme' ); ?></strong></label><br />
		<input type="number" id="fares-section-banner" name="fares_section_banner" min="0" value="<?php echo esc_attr( (string) $banner ); ?>" class="small-text" />
		<br />
		<span class="description"><?php esc_html_e( 'ارفع الصورة من مكتبة الوسائط، ثم انسخ رقم الـ ID والصقه هنا. اتركه فارغًا لإخفاء البانر.', 'fares-theme' ); ?></span>
		<?php if ( $banner ) : ?>
			<br /><?php echo wp_get_attachment_image( $banner, 'medium' ); ?>
		<?php endif; ?>
	</p>

	<p>
		<label for="fares-section-banner-link"><strong><?php esc_html_e( 'رابط البانر (اختياري)', 'fares-theme' ); ?></strong></label><br />
		<input type="url" id="fares-section-banner-link" name="fares_section_banner_link" value="<?php echo esc_attr( $banner_link ); ?>" class="regular-text" placeholder="https://" />
	</p>

	<p>
		<label for="fares-section-view-all"><strong><?php esc_html_e( 'رابط "عرض الكل" (اختياري)', 'fares-theme' ); ?></strong></label><br />
		<input type="url" id="fares-section-view-all" name="fares_section_view_all" value="<?php echo esc_attr( $view_all ); ?>" class="regular-text" placeholder="https://" />
		<br />
		<span class="description"><?php esc_html_e( 'يُستخدم رابط التصنيف تلقائيًا إذا تُرك فارغًا.', 'fares-theme' ); ?></span>
	</p>
	<?php
}

/**
 * Persist the section settings.
 *
 * @param int $post_id Section post ID.
 */
function fares_save_home_section( int $post_id ): void {
	if (
		! isset( $_POST['fares_home_section_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['fares_home_section_nonce'] ), 'fares_home_section' )
	) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$type = isset( $_POST['fares_section_type'] ) && 'banner' === $_POST['fares_section_type'] ? 'banner' : 'products';
	update_post_meta( $post_id, FARES_SECTION_TYPE_META, $type );

	update_post_meta( $post_id, FARES_SECTION_CATEGORY_META, absint( $_POST['fares_section_category'] ?? 0 ) );

	$count = absint( $_POST['fares_section_count'] ?? FARES_SECTION_DEFAULT_COUNT );
	$count = max( 1, min( FARES_SECTION_MAX_COUNT, $count ) );
	update_post_meta( $post_id, FARES_SECTION_COUNT_META, $count );

	update_post_meta( $post_id, FARES_SECTION_BANNER_META, absint( $_POST['fares_section_banner'] ?? 0 ) );
	update_post_meta( $post_id, FARES_SECTION_BANNER_LINK_META, esc_url_raw( wp_unslash( $_POST['fares_section_banner_link'] ?? '' ) ) );
	update_post_meta( $post_id, FARES_SECTION_VIEW_ALL_META, esc_url_raw( wp_unslash( $_POST['fares_section_view_all'] ?? '' ) ) );
}
add_action( 'save_post_fares_home_section', 'fares_save_home_section' );

/**
 * Add a "type" column to the admin list.
 *
 * @param array $columns Columns.
 * @return array
 */
function fares_home_section_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['fares_section_type'] = __( 'النوع', 'fares-theme' );
		}
	}
	return $new;
}
add_filter( 'manage_fares_home_section_posts_columns', 'fares_home_section_admin_columns' );

/**
 * Render the type column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function fares_home_section_admin_column_content( string $column, int $post_id ): void {
	if ( 'fares_section_type' !== $column ) {
		return;
	}

	$type = get_post_meta( $post_id, FARES_SECTION_TYPE_META, true ) ?: 'products';
	echo esc_html( 'banner' === $type ? __( 'بانر', 'fares-theme' ) : __( 'منتجات', 'fares-theme' ) );
}
add_action( 'manage_fares_home_section_posts_custom_column', 'fares_home_section_admin_column_content', 10, 2 );
