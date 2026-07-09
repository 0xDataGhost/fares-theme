<?php
/**
 * TranslatePress base configuration — Arabic (default) + English under /en/.
 *
 * TranslatePress stores its configuration AND translations in the database, so
 * this script makes the bilingual base setup reproducible across environments
 * (the equivalent of doing it in Settings → TranslatePress). Translations
 * themselves are entered in TP's editor and exported/imported via TP's tools.
 *
 * Idempotent — safe to re-run. Invoke with:
 *   wp eval-file bin/setup-languages.php     (or: npm run languages)
 *
 * @package fares-theme
 */

defined( 'WP_CLI' ) || exit;

if ( ! class_exists( 'TRP_Translate_Press' ) ) {
	WP_CLI::error( 'TranslatePress is not active — activate it first.' );
}

// 1. Languages + subdirectory URL structure (English served at /en/).
$settings                          = get_option( 'trp_settings', array() );
$settings['default-language']      = 'ar';
$settings['translation-languages'] = array( 'ar', 'en_US' );
$settings['publish-languages']     = array( 'ar', 'en_US' );
$settings['url-slugs']             = array(
	'ar'    => 'ar',
	'en_US' => 'en',
);
// The theme renders the switcher in the header, so disable TP's floating one.
$settings['trp-ls-floater'] = 'no';
update_option( 'trp_settings', $settings );

// 2. Create TP's translation tables for the language pair. TP normally does
//    this when a language is added through the admin UI; configuring via option
//    (above) bypasses that, so create them explicitly here.
$trp   = TRP_Translate_Press::get_trp_instance();
$query = $trp->get_component( 'query' );
$query->check_original_table();
$query->check_original_meta_table();
$query->check_table( 'ar', 'en_US' );

if ( class_exists( 'TRP_Gettext_Table_Creation' ) ) {
	$gettext = new TRP_Gettext_Table_Creation( $settings );
	$gettext->check_gettext_original_table();
	$gettext->check_gettext_original_meta_table();
	$gettext->check_gettext_table( 'en_US' );
}

// 3. Refresh rewrite rules so /en/ URLs resolve.
flush_rewrite_rules();

WP_CLI::success( 'Languages configured: Arabic (default) + English at /en/.' );
