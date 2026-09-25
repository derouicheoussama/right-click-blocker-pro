<?php
/**
 * Plugin Name:       Right Click Blocker PRO – Right Click & Content Protection
 * Description:       Bloque le clic droit, la copie, la sélection, le glisser-déposer, l'impression, les captures d'écran et les outils de développement — avec messages personnalisés, statistiques temps réel et journaux. Tout est inclus, gratuitement.
 * Version:           2.22.1
 * Author:            Infinity Coder
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.9
 * Tested up to:      7.1
 * Requires PHP:      7.0
 *
 * COMPLIANCE NOTE (WordPress.org Guidelines 5 & 6 — no trialware):
 * every feature of this plugin is free and fully functional without any
 * license key. The optional "License & Purchase" page only handles
 * symbolic, lifetime support licenses; it never gates, restricts,
 * disables or unlocks any functionality. The optional Chargily Pay
 * gateway (vendor site only) makes NO outbound request unless the
 * vendor explicitly configures their own API key; all protections and
 * telemetry-free features work fully offline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INFINITY_RCB_VERSION', '2.22.1' );
define( 'INFINITY_RCB_FILE', __FILE__ );
define( 'INFINITY_RCB_DIR', plugin_dir_path( __FILE__ ) );
define( 'INFINITY_RCB_URL', plugin_dir_url( __FILE__ ) );
define( 'INFINITY_RCB_BASENAME', plugin_basename( __FILE__ ) );
define( 'INFINITY_RCB_OPTION', 'infinity_rcb_pro_options' );
define( 'INFINITY_RCB_STATS_OPTION', 'infinity_rcb_pro_stats' );
define( 'INFINITY_RCB_CRON', 'infinity_rcb_daily_maintenance' );

require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-stats.php';
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-logger.php';
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-license.php';
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-chargily.php';
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-antispam.php';
// Canal GitHub optionnel : le fichier de l'updater est EXCLU du build
// WordPress.org (toute routine de mise à jour personnalisée y est
// interdite) — il n'est chargé que s'il est présent (distribution GitHub).
if ( is_readable( INFINITY_RCB_DIR . 'includes/class-infinity-rcb-updater.php' ) ) {
	require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-updater.php';
}
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb-shop.php';
require_once INFINITY_RCB_DIR . 'admin/class-infinity-rcb-admin.php';
require_once INFINITY_RCB_DIR . 'includes/class-infinity-rcb.php';

/**
 * Valeurs par défaut des réglages du plugin.
 */
function infinity_rcb_default_options() {
	return array(
		'master_enable' => true,
		'protections'   => array(
			'right_click' => true,
			'keyboard'    => true,
			'copy'        => true,
			'selection'   => true,
			'drag_drop'   => true,
			'print'       => true,
			'screenshot'  => true,
			'source_code' => true,
			'save_as'     => true,
			'devtools'    => true,
			'console'     => true,
		),
		'messages'      => array(
			'right_click' => 'Clic droit désactivé sur ce site !',
			'keyboard'    => 'Les raccourcis clavier sont désactivés !',
			'copy'        => 'Copier / coller est désactivé !',
			'selection'   => 'La sélection de texte est désactivée !',
			'drag_drop'   => 'Le glisser-déposer est désactivé !',
			'print'       => 'L’impression est désactivée !',
			'screenshot'  => 'Les captures d’écran sont désactivées !',
			'source_code' => 'La consultation du code source est interdite !',
			'save_as'     => 'L’enregistrement de la page est désactivé !',
			'devtools'    => 'Les outils de développement sont interdits !',
			'console'     => 'La console du navigateur est sous surveillance !',
		),
		'appearance'    => array(
			'style'            => 'st1',
			'position'         => 'top',
			'duration'         => 2600,
			'custom_bg'        => '',
			'custom_text'      => '',
			'show_icon'        => true,
			'sound'            => false,
			'copyright_enable' => true,
			'copyright_text'   => '© {annee} {site} — Tous droits réservés',
			'show_close'       => true,
			'show_progress'    => true,
			'radius'           => 12,
			'font_size'        => 15,
			'shadow'           => true,
			'custom_css'       => '',
			'wm_text'          => '© {site}',
			'wm_opacity'       => 55,
			'wm_size'          => 13,
		),
		'advanced'      => array(
			'interval'       => 800,
			'blur'           => false,
			'redirect'       => '',
			'exclude_pages'  => '',
			'exclude_admins' => false,
			'exclude_roles'  => array(),
			'tracking'       => true,
			'touch_guard'    => true,
			'image_pointer'  => true,
			'noscript_warn'  => true,
			// 2.11.0 : filigrane, anti-clickjacking, alertes pic d'attaques.
			'watermark'      => false,
			'xfo'            => true,
			'frame_bust'     => false,
			'alert_threshold'=> 0,
		),
		// ⚠️ VENDEUR (Infinity Coder) : ces coordonnées sont celles que voient
		// les SITES CLIENTS (page Licence & Achat + e-mails). Renseignez-les ici
		// avant de distribuer le plugin. Elles restent modifiables sur votre
		// propre site via Réglages → Paiement (mode vendeur uniquement).
		'payment'       => array(
			'baridimob_rip' => '',
			'ccp'           => '',
			'rib'           => '',
			'paypal_email'  => '',
			'paypal_me'     => '',
			'instructions'  => 'Après paiement, envoyez la référence de la commande et le reçu par e-mail à hi@infinitycoder.dev — la clé de licence est envoyée dès réception.',
			// 2.11.0 : WhatsApp, bilan hebdo, paiement carte CIB/Edahabia (Chargily Pay).
			'whatsapp'      => '',
			'digest'        => false,
			'chargily_mode' => 'test',
			'chargily_key'  => '',
		),
		'logging'       => array(
			'enabled'        => true,
			'level'          => 'warning',
			'retention_days' => 30,
			'max_entries'    => 5000,
		),
		'license'       => array(
			'plan'          => 'single',
			'license_key'   => '',
			'buyer'         => '',
			'purchase_date' => '',
			'tiers'         => array(
				'single' => array(
					'label'    => 'Licence Mono-Site',
					'domains'  => 1,
					'price_da' => 2900,
					'price_eur'=> 11.90,
					'features' => array( '1 nom de domaine', 'Licence à vie, sans abonnement', 'Mises à jour gratuites', 'Support e-mail 12 mois' ),
				),
				'five' => array(
					'label'    => 'Licence 5 Sites',
					'domains'  => 5,
					'price_da' => 4800,
					'price_eur'=> 22.90,
					'features' => array( '5 noms de domaine', 'Licence à vie, sans abonnement', 'Mises à jour gratuites prioritaires', 'Support e-mail prioritaire 24 mois' ),
				),
				'agency' => array(
					'label'    => 'Licence Agence',
					'domains'  => 20,
					'price_da' => 12000,
					'price_eur'=> 44.90,
					'features' => array( '20 noms de domaine', 'Licence à vie, sans abonnement', 'Mises à jour gratuites prioritaires', 'Support e-mail dédié 36 mois' ),
				),
			),
			// Codes promo (gérés par le vendeur) : 'CODE' => pourcentage (simple)
			// ou 'CODE' => array( 'pct' => 20, 'expires' => 'YYYY-MM-DD',
			// 'max_uses' => 50, 'uses' => 3 ). Expiration et quota vérifiés
			// à chaque commande (Infinity_RCB_License::validate_promo).
			'promo'         => array(
				// 'LANCEMENT20' => 20,
			),
			// Clés révoquées (remboursement, fraude) : la licence s'affiche « Révoquée ».
			'revoked'       => array(),
		),
		'developer'     => array(
			'name'    => 'Infinity Coder',
			'website' => '',
			'email'   => 'hi@infinitycoder.dev',
			'phone'   => '',
			'address' => '',
		),
		// Mises à jour : wp.org prioritaire + GitHub pour les installations
		// manuelles (dépôt officiel du plugin ; surchargeable par la
		// constante wp-config INFINITY_RCB_GITHUB_REPO).
		'antispam'       => array(
			'enabled'      => true,
			'honeypot'     => true,
			'timegate'     => true,
			'min_seconds'  => 3,
			'ratelimit'    => true,
			'max_per_hour' => 5,
			'blacklist'    => '',
			'linkcheck'    => true,
			'max_links'    => 2,
		),
		'updates'       => array(
			'github_enabled' => true,
			'github_repo'    => 'derouicheoussama/right-click-blocker-pro',
		),
	);
}

/**
 * Options fusionnées avec les valeurs par défaut (2 niveaux).
 */
function infinity_rcb_options() {
	$saved = get_option( INFINITY_RCB_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$def = infinity_rcb_default_options();
	$out = array_merge( $def, $saved );
	foreach ( $def as $key => $value ) {
		if ( is_array( $value ) && isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
			$out[ $key ] = array_merge( $value, $saved[ $key ] );
		}
	}
	return $out;
}

/**
 * Styles de messages prédéfinis (10 thèmes).
 */
function infinity_rcb_message_styles() {
	return array(
		'st1'  => array( 'label' => 'Classique Bleu',     'bg' => '#1e88e5', 'text' => '#ffffff', 'anim' => 'fade'   ),
		'st2'  => array( 'label' => 'Moderne Sombre',     'bg' => '#2c3e50', 'text' => '#ecf0f1', 'anim' => 'slide'  ),
		'st3'  => array( 'label' => 'Élégant Violet',     'bg' => '#9b59b6', 'text' => '#ffffff', 'anim' => 'bounce' ),
		'st4'  => array( 'label' => 'Professionnel Vert', 'bg' => '#27ae60', 'text' => '#ffffff', 'anim' => 'zoom'   ),
		'st5'  => array( 'label' => 'Premium Or',         'bg' => '#f1c40f', 'text' => '#2c3e50', 'anim' => 'flip'   ),
		'st6'  => array( 'label' => 'Verre Dépoli',       'bg' => '#0f172a', 'text' => '#f8fafc', 'anim' => 'slide'  ),
		'st7'  => array( 'label' => 'Néon Cyber',         'bg' => '#0f172a', 'text' => '#22d3ee', 'anim' => 'zoom'   ),
		'st8'  => array( 'label' => 'Dégradé Infinity',   'bg' => '#4f46e5', 'text' => '#ffffff', 'anim' => 'fade'   ),
		'st9'  => array( 'label' => 'Minimal Blanc',      'bg' => '#ffffff', 'text' => '#1e293b', 'anim' => 'slide'  ),
		'st10' => array( 'label' => 'Alerte Rouge',       'bg' => '#dc2626', 'text' => '#ffffff', 'anim' => 'bounce' ),
	);
}

/**
 * Logo SVG officiel du plugin (charte v3) : bouclier à bande dégradée
 * bleu → violet, panneau intérieur blanc, souris bleu nuit droite avec
 * molette, barre d'interdiction rouge → rose dépassant du bouclier.
 *
 * $size    : 'regular' (44px) ou 'large' (64px).
 * $variant : 'shield' (logo complet) ou 'plain' (silhouette pour fonds colorés).
 */
function infinity_rcb_shield_svg( $size = 'regular', $variant = 'shield' ) {
	$px = 'large' === $size ? '64' : '44';
	$uid = 'rcbs' . $px;

	if ( 'plain' === $variant ) {
		return '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
			. '<path d="M13 10h22v13c0 8.6-4.7 14.7-11 17.3C17.7 37.7 13 31.6 13 23Z" fill="currentColor"/>'
			. '<rect x="19.6" y="15.6" width="8.8" height="14.2" rx="4.4" fill="#0B0F1E"/>'
			. '<rect x="23.2" y="17.2" width="1.6" height="3.6" rx=".8" fill="#fff" opacity=".92"/>'
			. '<path d="M15 18.4 35.4 27.8" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>'
			. '<path d="M15 18.4 35.4 27.8" stroke="#F43F5E" stroke-width="2.8" stroke-linecap="round"/>'
			. '</svg>';
	}

	return '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
		. '<defs>'
		. '<linearGradient id="' . $uid . 'b" x1="0" y1="0" x2="1" y2="0">'
		. '<stop offset="0" stop-color="#1E6FF0"/><stop offset="1" stop-color="#7C3AED"/>'
		. '</linearGradient>'
		. '<linearGradient id="' . $uid . 's" x1="0" y1="0" x2="1" y2="1">'
		. '<stop offset="0" stop-color="#FF4D3D"/><stop offset="1" stop-color="#F4156C"/>'
		. '</linearGradient>'
		. '</defs>'
		// Bande extérieure dégradée.
		. '<path d="M13 10h22v13c0 8.6-4.7 14.7-11 17.3C17.7 37.7 13 31.6 13 23Z" fill="url(#' . $uid . 'b)"/>'
		// Panneau intérieur blanc.
		. '<path d="M16.4 13.2h15.2v9.6c0 6.7-3.5 11.3-7.6 13.6-4.1-2.3-7.6-6.9-7.6-13.6Z" fill="#ffffff"/>'
		// Souris droite + molette.
		. '<rect x="19.6" y="15.6" width="8.8" height="14.2" rx="4.4" fill="#0B0F1E"/>'
		. '<rect x="23.2" y="17.2" width="1.6" height="3.6" rx=".8" fill="#fff" opacity=".92"/>'
		// Barre d'interdiction dégradée avec détourage blanc (dépasse du bouclier).
		. '<path d="M15 18.4 35.4 27.8" stroke="#fff" stroke-width="4.6" stroke-linecap="round"/>'
		. '<path d="M15 18.4 35.4 27.8" stroke="url(#' . $uid . 's)" stroke-width="2.9" stroke-linecap="round"/>'
		. '</svg>';
}

/**
 * Wordmark officiel : INFINITY (avec Y violet) sur RIGHT CLICK (gris) BLOCKER (violet).
 * $context : 'hero' (texte clair sur fond dégradé) ou 'light' (marine sur fond clair).
 */
function infinity_rcb_wordmark( $context = 'hero' ) {
	return '<span class="rcb-wordmark rcb-wm-' . esc_attr( $context ) . '">'
		. '<span class="rcb-wm-infinity">INFINIT<i>Y</i></span>'
		. '<span class="rcb-wm-sub"><span class="rcb-wm-rc">RIGHT CLICK</span> <span class="rcb-wm-blocker">BLOCKER</span></span>'
		. '</span>';
}

/**
 * Icône du menu d'administration (fichier SVG embarqué — pas d'URI data:,
 * exigence du Plugin Check de WordPress.org).
 */
function infinity_rcb_menu_icon() {
	return INFINITY_RCB_URL . 'assets/menu-icon.svg';
}

/**
 * Assainit une couleur hexadécimale (fallback si sanitize_hex_color absent, WP < 5.4).
 */
function infinity_rcb_sanitize_hex( $color ) {
	if ( function_exists( 'sanitize_hex_color' ) ) {
		return sanitize_hex_color( $color );
	}
	if ( '' === $color ) {
		return '';
	}
	if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
		return $color;
	}
	return '';
}

/**
 * Activation : options par défaut, dossier de journaux, cron quotidien.
 */
function infinity_rcb_activate() {
	if ( false === get_option( INFINITY_RCB_OPTION, false ) ) {
		add_option( INFINITY_RCB_OPTION, infinity_rcb_default_options(), '', false );
	}
	if ( false === get_option( INFINITY_RCB_STATS_OPTION, false ) ) {
		add_option( INFINITY_RCB_STATS_OPTION, Infinity_RCB_Stats::defaults(), '', false );
	}

	$logger = new Infinity_RCB_Logger();
	$logger->ensure_dir();

	if ( ! wp_next_scheduled( INFINITY_RCB_CRON ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', INFINITY_RCB_CRON );
	}
}
register_activation_hook( __FILE__, 'infinity_rcb_activate' );

/**
 * Désactivation : on retire simplement la tâche planifiée.
 */
function infinity_rcb_deactivate() {
	wp_clear_scheduled_hook( INFINITY_RCB_CRON );
}
register_deactivation_hook( __FILE__, 'infinity_rcb_deactivate' );

/**
 * Lancement du plugin.
 */
function infinity_rcb_boot() {
	$core = new Infinity_RCB();
	$core->run();
}
add_action( 'plugins_loaded', 'infinity_rcb_boot', 20 );

/**
 * Mises à jour : WordPress.org est la seule source du build wp.org ; le
 * canal GitHub (optionnel) n'existe que si l'updater est distribué
 * (build GitHub) — il se désactive dès que wp.org gère l'extension.
 */
if ( class_exists( 'Infinity_RCB_Updater' ) ) {
	add_action( 'plugins_loaded', array( 'Infinity_RCB_Updater', 'register' ), 30 );
}

/**
 * Bloqueur de spam de commentaires (5 couches : honeypot, time-gate,
 * rate limit, blacklist, compteur de liens).
 */
add_action( 'plugins_loaded', array( 'Infinity_RCB_Antispam', 'register' ), 25 );

/**
 * Migration à la montée de version : remplace les anciennes coordonnées
 * vendeur par l'adresse officielle, uniquement si elles n'ont pas été
 * personnalisées autrement.
 */
function infinity_rcb_upgrade() {
	$stored = get_option( 'infinity_rcb_pro_version', '' );
	if ( INFINITY_RCB_VERSION === $stored ) {
		return;
	}

	$legacy_emails = array( 'contact@infinitycll.com', 'derouicheoussama.pro@gmail.com' );
	$opts         = get_option( INFINITY_RCB_OPTION, array() );
	$changed      = false;

	// 2.15.1 : dépôt GitHub officiel intégré par défaut (remplissage unique
	// pour les installations antérieures qui n'avaient aucun dépôt).
	if ( version_compare( $stored, '2.15.1', '<' ) && is_array( $opts ) ) {
		$rcb_repo = is_array( $opts['updates'] ?? null ) ? (string) ( $opts['updates']['github_repo'] ?? '' ) : '';
		if ( '' === trim( $rcb_repo ) ) {
			$opts['updates']['github_repo'] = 'derouicheoussama/right-click-blocker-pro';
			$changed = true;
		}
	}
	if ( is_array( $opts ) ) {
		if ( isset( $opts['developer']['email'] ) && in_array( $opts['developer']['email'], $legacy_emails, true ) ) {
			$opts['developer']['email'] = 'hi@infinitycoder.dev';
			$changed = true;
		}
		if ( isset( $opts['payment']['instructions'] ) ) {
			$migrated = str_replace( $legacy_emails, 'hi@infinitycoder.dev', (string) $opts['payment']['instructions'] );
			if ( $migrated !== $opts['payment']['instructions'] ) {
				$opts['payment']['instructions'] = $migrated;
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( INFINITY_RCB_OPTION, $opts, false );
		}
	}

	update_option( 'infinity_rcb_pro_version', INFINITY_RCB_VERSION, false );
}
add_action( 'plugins_loaded', 'infinity_rcb_upgrade', 5 );
