<?php
/**
 * Designed contact-channels layout (contact page body).
 *
 * Replaces the raw editor content on the contact page with a channels grid
 * built from `fares_contact_channels()` (single source of truth) plus a quiet
 * store-credentials panel. Icons reuse the theme SVG sprite via fares_icon().
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_channels = fares_contact_channels();

/* Channel cards: sprite icon id, label, display value, href, LTR value flag. */
$fares_cards = array(
	array(
		'icon'  => 'email',
		'label' => __( 'البريد الإلكتروني', 'fares-theme' ),
		'value' => $fares_channels['email'],
		'href'  => 'mailto:' . $fares_channels['email'],
		'ltr'   => true,
	),
	array(
		'icon'  => 'telegram',
		'label' => __( 'تيليجرام', 'fares-theme' ),
		'value' => __( 'قناة الدعم', 'fares-theme' ),
		'href'  => $fares_channels['telegram'],
		'ltr'   => false,
	),
	array(
		'icon'  => 'instagram',
		'label' => __( 'إنستغرام', 'fares-theme' ),
		'value' => '@sho9.store',
		'href'  => $fares_channels['instagram'],
		'ltr'   => true,
	),
	array(
		'icon'  => 'x',
		'label' => __( 'منصّة X', 'fares-theme' ),
		'value' => '@sho9_store',
		'href'  => $fares_channels['x'],
		'ltr'   => true,
	),
	array(
		'icon'  => 'youtube',
		'label' => __( 'يوتيوب', 'fares-theme' ),
		'value' => __( 'قناتنا الرسمية', 'fares-theme' ),
		'href'  => $fares_channels['youtube'],
		'ltr'   => false,
	),
);

/* Store credentials shown as a quiet info panel. */
$fares_credentials = array(
	array(
		'key' => __( 'الرقم الضريبي', 'fares-theme' ),
		'val' => '312478563400003',
	),
	array(
		'key' => __( 'وثيقة العمل الحر', 'fares-theme' ),
		'val' => 'FL-128116989',
	),
);

$fares_is_external = static fn( string $icon ): bool => in_array( $icon, array( 'instagram', 'x', 'youtube' ), true );
?>

<p class="fares-contact__intro">
	<?php esc_html_e( 'نحن هنا لمساعدتك. اختر القناة الأنسب لك وسنردّ عليك في أقرب وقت ممكن. للاستفسارات المتعلقة بطلب قائم، يُرجى ذكر رقم الطلب لتسريع المساعدة.', 'fares-theme' ); ?>
</p>

<ul class="fares-contact__grid">
	<?php foreach ( $fares_cards as $card ) : ?>
		<li>
			<a
				class="fares-contact__card"
				href="<?php echo esc_url( $card['href'] ); ?>"
				<?php if ( $fares_is_external( $card['icon'] ) ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
			>
				<span class="fares-contact__icon"><?php fares_icon( $card['icon'] ); ?></span>
				<span>
					<span class="fares-contact__label"><?php echo esc_html( $card['label'] ); ?></span>
					<span class="fares-contact__value"<?php echo $card['ltr'] ? ' dir="ltr"' : ''; ?>><?php echo esc_html( $card['value'] ); ?></span>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>

<div class="fares-contact__info">
	<?php foreach ( $fares_credentials as $item ) : ?>
		<div class="fares-contact__info-item">
			<span class="fares-contact__info-key"><?php echo esc_html( $item['key'] ); ?></span>
			<span class="fares-contact__info-val" dir="ltr"><?php echo esc_html( $item['val'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
