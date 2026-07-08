<?php
/**
 * Site header — announcement bar + main header chrome.
 *
 * Composed from template-parts/header/* (built out in Phase 2).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'تخطي إلى المحتوى', 'fares-theme' ); ?></a>

<?php get_template_part( 'template-parts/header/announcement-bar' ); ?>

<header class="fares-header" role="banner">
	<div class="fares-container fares-header__inner">
		<div class="fares-header__lead">
			<?php get_template_part( 'template-parts/header/menu-toggle' ); ?>
			<?php get_template_part( 'template-parts/header/account-link' ); ?>
		</div>
		<?php get_template_part( 'template-parts/header/site-branding' ); ?>
		<?php get_template_part( 'template-parts/header/header-actions' ); ?>
	</div>
</header>

<?php get_template_part( 'template-parts/global/category-drawer' ); ?>
