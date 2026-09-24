<?php
/**
 * Système de licence d'Infinity RCB Pro — v2 (clés signées).
 *
 * COMPLIANCE NOTE (WordPress.org Guidelines 5 & 6 — no trialware):
 * this optional license system ONLY records symbolic, lifetime support
 * purchases. It never gates, restricts, disables or unlocks any plugin
 * feature: every protection works fully with or without a license key.
 *
 * Technique : signature numérique asymétrique ECDSA P-256 (OpenSSL).
 * - Le plugin embarque UNIQUEMENT la CLÉ PUBLIQUE du vendeur : il peut
 *   vérifier une licence mais ne peut PAS en générer.
 * - La CLÉ PRIVÉE n'existe que chez le vendeur (hors plugin). Installée
 *   sur son propre site (« espace vendeur »), elle active le générateur
 *   de clés. Aucun client ne peut créer ni falsifier une licence.
 *
 * Format de clé : RCB-{S|F}-{XXXXX}-{XXXXX}.{signature-base64url}
 * Message signé : "RCB2|{plan}|{corps}"
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_License {

	const OPTION     = 'infinity_rcb_pro_license';
	const ORDERS     = 'infinity_rcb_pro_orders';
	const VENDOR     = 'infinity_rcb_pro_vendor';
	const MAX_ORDERS = 200;

	/**
	 * Clé publique du vendeur (Infinity Coder) — vérification uniquement.
	 */
	const PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEz9u6Bld9mEmM37sjhRBYy2hZh9Dm
Lhr6NHXAYrMJLTc+ofHQvJlsOZ/3geEGaHaCyqrf9Xj5AYg3drWZulMP0g==
-----END PUBLIC KEY-----';

	/* ==================================================================
	 * Prérequis et encodage
	 * ================================================================== */

	public static function crypto_available() {
		return function_exists( 'openssl_verify' ) && function_exists( 'openssl_sign' );
	}

	private static function b64url_encode( $raw ) {
		return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	private static function key_message( $plan, $body ) {
		return 'RCB2|' . $plan . '|' . $body;
	}

	/* ==================================================================
	 * Espace vendeur (activation par constante wp-config, invisible client)
	 *
	 * Sur VOTRE site uniquement, ajoutez dans wp-config.php :
	 *   define( 'INFINITY_RCB_VENDOR_PRIVATE_KEY', '-----BEGIN PRIVATE KEY-----…' );
	 * ou, plus pratique :
	 *   define( 'INFINITY_RCB_VENDOR_KEY_FILE', '/chemin/absolu/vendor-private.pem' );
	 * ================================================================== */

	private static $vendor_cache;

	/**
	 * Clé privée vendeur (constante ou fichier), ou '' sur les sites clients.
	 */
	public static function vendor_private_key() {
		if ( defined( 'INFINITY_RCB_VENDOR_PRIVATE_KEY' ) && '' !== (string) INFINITY_RCB_VENDOR_PRIVATE_KEY ) {
			return (string) INFINITY_RCB_VENDOR_PRIVATE_KEY;
		}
		if ( defined( 'INFINITY_RCB_VENDOR_KEY_FILE' ) && '' !== (string) INFINITY_RCB_VENDOR_KEY_FILE ) {
			$file = (string) INFINITY_RCB_VENDOR_KEY_FILE;
			if ( is_readable( $file ) ) {
				return (string) file_get_contents( $file );
			}
		}
		return '';
	}

	/**
	 * Le site courant est-il le site vendeur (clé privée présente et valide) ?
	 */
	public static function vendor_enabled() {
		if ( null !== self::$vendor_cache ) {
			return self::$vendor_cache;
		}
		self::$vendor_cache = false;

		if ( ! self::crypto_available() ) {
			return false;
		}
		$pem = self::vendor_private_key();
		if ( '' === $pem ) {
			return false;
		}

		$probe = wp_generate_password( 20, false, false );
		$ok = @openssl_sign( 'rcb-probe|' . $probe, $sig, $pem, OPENSSL_ALGO_SHA256 );
		if ( ! $ok || 1 !== openssl_verify( 'rcb-probe|' . $probe, $sig, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256 ) ) {
			return false;
		}

		self::$vendor_cache = true;
		return true;
	}

	/* ==================================================================
	 * Clés : génération (vendeur uniquement) et vérification publique
	 * ================================================================== */

	/**
	 * Génère une clé signée — réservé au site du vendeur (constante wp-config).
	 *
	 * @return string|null La clé, ou null si génération impossible.
	 */
	public static function generate_key( $plan ) {
		if ( ! self::vendor_enabled() ) {
			return null;
		}
		$codes = array( 'single' => 'S', 'five' => 'F', 'agency' => 'A' );
		if ( ! isset( $codes[ $plan ] ) ) {
			$plan = 'single';
		}
		$code = $codes[ $plan ];

		$body = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wp_generate_password( 10, false, false ) ) );
		$msg  = self::key_message( $plan, $body );

		if ( ! openssl_sign( $msg, $sig, self::vendor_private_key(), OPENSSL_ALGO_SHA256 ) ) {
			return null;
		}

		return 'RCB-' . $code . '-' . substr( $body, 0, 5 ) . '-' . substr( $body, 5, 5 ) . '.' . self::b64url_encode( $sig );
	}

	/**
	 * Vérifie une clé avec la clé PUBLIQUE embarquée.
	 *
	 * @return array|false array( 'plan' => 'single|five|agency' ) ou false.
	 *                     array( 'error' => 'crypto' ) si OpenSSL est absent.
	 */
	public static function validate_key( $key ) {
		if ( ! self::crypto_available() ) {
			return array( 'error' => 'crypto' );
		}

		$key = trim( (string) $key );
		if ( ! preg_match( '/^RCB-([SFA])-([A-Z0-9]{5})-([A-Z0-9]{5})\.([A-Za-z0-9_-]{80,140})$/', $key, $m ) ) {
			return false;
		}

		$plans = array( 'S' => 'single', 'F' => 'five', 'A' => 'agency' );
		$plan  = $plans[ $m[1] ];
		$body  = $m[2] . $m[3];
		$msg   = self::key_message( $plan, $body );
		$sig   = self::b64url_decode( $m[4] );

		if ( false === $sig || '' === $sig ) {
			return false;
		}
		return 1 === openssl_verify( $msg, $sig, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256 )
			? array( 'plan' => $plan )
			: false;
	}

	/**
	 * La clé figure-t-elle sur la liste de révocation du vendeur ?
	 */
	public static function is_revoked( $key ) {
		$revoked = infinity_rcb_options()['license']['revoked'];
		return is_array( $revoked ) && in_array( trim( (string) $key ), $revoked, true );
	}

	/* ==================================================================
	 * État de la licence
	 * ================================================================== */

	private function read() {
		$data = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $data ) ? $data : array(), array(
			'key'          => '',
			'email'        => '',
			'plan'         => '',
			'domains'      => array(),
			'activated_at' => '',
			'log'          => array(),
		) );
	}

	private function write( $data ) {
		update_option( self::OPTION, $data, false );
	}

	/**
	 * Journalise une action de licence (activation, désactivation, libération).
	 */
	private function log_action( $action, $domain ) {
		$data   = $this->read();
		$log    = is_array( $data['log'] ) ? $data['log'] : array();
		array_unshift( $log, array(
			'time'   => current_time( 'mysql' ),
			'action' => $action,
			'domain' => $domain,
			'ip'     => self::client_ip(),
		) );
		$data['log'] = array_slice( $log, 0, 20 );
		$this->write( $data );
	}

	public static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	public function current_domain() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return $host ? strtolower( $host ) : '';
	}

	/**
	 * Synthèse de l'état de la licence pour l'affichage.
	 *
	 * @return array code: active|inactive|quota|invalid|crypto + détails.
	 */
	public function status() {
		$data  = $this->read();
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		$base  = array(
			'plan' => '', 'plan_label' => '—', 'key' => '', 'email' => '',
			'domains' => array(), 'slots' => 0, 'domain' => $this->current_domain(), 'is_here' => false,
		);

		if ( '' === $data['key'] ) {
			return array_merge( $base, array( 'code' => 'inactive', 'label' => 'Non activée' ) );
		}

		$valid = self::validate_key( $data['key'] );
		if ( isset( $valid['error'] ) ) {
			return array_merge( $base, array( 'code' => 'crypto', 'label' => 'OpenSSL requis', 'key' => $data['key'] ) );
		}
		if ( ! $valid ) {
			return array_merge( $base, array( 'code' => 'invalid', 'label' => 'Clé invalide', 'key' => $data['key'], 'email' => $data['email'] ) );
		}
		if ( self::is_revoked( $data['key'] ) ) {
			return array_merge( $base, array( 'code' => 'revoked', 'label' => 'Licence révoquée', 'key' => $data['key'], 'email' => $data['email'], 'domains' => (array) $data['domains'], 'domain' => $this->current_domain() ) );
		}

		$plan     = $valid['plan'];
		$slots    = (int) $tiers[ $plan ]['domains'];
		$domains  = array_values( array_filter( (array) $data['domains'] ) );
		$is_here  = in_array( $this->current_domain(), $domains, true );

		$base['log'] = isset( $data['log'] ) ? (array) $data['log'] : array();
		return array_merge( $base, array(
			'code'       => $is_here ? 'active' : 'quota',
			'label'      => $is_here ? 'Active' : ( count( $domains ) >= $slots ? 'Quota de domaines atteint' : 'Domaine non enregistré' ),
			'plan'       => $plan,
			'plan_label' => $tiers[ $plan ]['label'],
			'key'        => $data['key'],
			'email'      => $data['email'],
			'domains'    => $domains,
			'slots'      => $slots,
			'is_here'    => $is_here,
			'log'        => isset( $data['log'] ) ? (array) $data['log'] : array(),
		) );
	}

	/* ==================================================================
	 * Activation / désactivation (+ journal, e-mail de confirmation,
	 * anti force-brute, libération de créneaux orphelins)
	 * ================================================================== */

	/**
	 * Anti force-brute : au plus 5 tentatives d'activation par heure et par IP.
	 */
	public static function activation_allowed() {
		$tries = (int) get_transient( 'rcb_rl_' . md5( self::client_ip() ) );
		return $tries < 5;
	}

	public static function register_failed_activation() {
		$key   = 'rcb_rl_' . md5( self::client_ip() );
		$tries = (int) get_transient( $key );
		set_transient( $key, $tries + 1, HOUR_IN_SECONDS );
	}

	public static function clear_failed_activations() {
		delete_transient( 'rcb_rl_' . md5( self::client_ip() ) );
	}

	/**
	 * @return string|true 'invalid'|'quota'|'crypto'|'revoked' ou true.
	 */
	public function activate( $key, $email = '' ) {
		$valid = self::validate_key( $key );
		if ( isset( $valid['error'] ) ) {
			return 'crypto';
		}
		if ( ! $valid ) {
			return 'invalid';
		}
		if ( self::is_revoked( $key ) ) {
			return 'revoked';
		}

		$data   = $this->read();
		$plan   = $valid['plan'];
		$slots  = (int) infinity_rcb_default_options()['license']['tiers'][ $plan ]['domains'];
		$domain = $this->current_domain();

		// Nouvelle clé : liste de domaines vierge.
		if ( $data['key'] !== $key ) {
			$data = array(
				'key'          => $key,
				'email'        => sanitize_email( $email ),
				'plan'         => $plan,
				'domains'      => array(),
				'activated_at' => '',
				'log'          => $data['log'],
			);
		}
		$data['plan'] = $plan;

		if ( ! in_array( $domain, $data['domains'], true ) ) {
			if ( count( $data['domains'] ) >= $slots ) {
				$this->write( $data );
				return 'quota';
			}
			$data['domains'][] = $domain;
		}

		if ( '' === $data['activated_at'] ) {
			$data['activated_at'] = current_time( 'mysql' );
		}
		$this->write( $data );
		$this->log_action( 'activated', $domain );

		// Suivi client : les commandes de ce plan passent en « livrées ».
		$this->mark_orders_delivered( $plan );

		// E-mail « gardez votre clé » envoyé à l'acheteur.
		$this->email_activation( $data, $plan );

		return true;
	}

	/**
	 * E-mail de confirmation d'activation (clé + mode d'emploi).
	 */
	private function email_activation( $data, $plan ) {
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		if ( ! is_email( $data['email'] ) || ! isset( $tiers[ $plan ] ) ) {
			return false;
		}
		$opts  = infinity_rcb_options();
		$slots = (int) $tiers[ $plan ]['domains'];

		$subject = 'Votre licence Infinity RCB Pro est active — gardez cette clé';

		$domain_list = '';
		foreach ( (array) $data['domains'] as $d ) {
			$domain_list .= esc_html( $d ) . ( $d === $this->current_domain() ? ' <span style="color:#16A34A;font-weight:700;">— actif</span>' : '' ) . '<br>';
		}
		$domain_rows = array(
			'Plan'               => '<strong>' . esc_html( $tiers[ $plan ]['label'] ) . '</strong>',
			'Créneaux utilisés'  => count( (array) $data['domains'] ) . ' / ' . $slots,
			'Domaines enregistrés' => $domain_list,
		);

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Votre licence est active</h1>';
		$html .= $this->email_p( 'Votre licence <strong>' . esc_html( $tiers[ $plan ]['label'] ) . '</strong> est désormais active sur <strong>' . esc_html( $this->current_domain() ) . '</strong>.' );
		$html .= $this->email_key_box( $data['key'] );
		$html .= $this->email_rows( $domain_rows );

		$html .= $this->email_h2( 'Utiliser la clé sur un autre site' );
		$html .= $this->email_steps( array(
			'Installez le plugin Infinity RCB Pro sur le nouveau site.',
			'Ouvrez <strong>Infinity RCB Pro → Licence &amp; Achat</strong>.',
			'Collez la même clé puis cliquez <strong>Activer la licence</strong> (un créneau par domaine).',
		) );
		$html .= $this->email_box( 'Pour libérer un créneau : désactivez la licence sur le site concerné, ou demandez un code de libération depuis un site encore actif.' );

		$body  = sprintf( "Bonjour,\n\nVotre licence %s est désormais active sur :\n- %s\n\n", $tiers[ $plan ]['label'], $this->current_domain() );
		$body .= "VOTRE CLÉ DE LICENCE (à conserver précieusement) :\n" . $data['key'] . "\n\n";
		$body .= "Domaines enregistrés (" . count( $data['domains'] ) . "/" . $slots . ") :\n";
		foreach ( (array) $data['domains'] as $d ) { $body .= '- ' . $d . "\n"; }
		$body .= "\nPour l'utiliser sur un autre site : installez le plugin, ouvrez Licence & Achat et collez la clé.\n";
		$body .= "Pour libérer un créneau : désactivez la licence sur le site concerné (ou demandez un code de libération depuis un site encore actif).\n\n";
		$body .= sprintf( "Vendeur : %s\n", $opts['developer']['name'] );
		if ( is_email( $opts['developer']['email'] ) ) { $body .= 'Contact : ' . $opts['developer']['email'] . "\n"; }
		$body .= "— Infinity RCB Pro";

		return $this->send_html_mail( $data['email'], $subject, 'Licence active sur ' . $this->current_domain() . ' — conservez votre clé.', $html, $body, 'activation', is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '' );
	}

	/**
	 * Marque « livrées » les commandes en attente (ou déclarées payées)
	 * correspondant au plan activé.
	 */
	private function mark_orders_delivered( $plan ) {
		$orders = $this->get_orders();
		$found  = false;
		foreach ( $orders as $i => $order ) {
			if ( in_array( $order['status'], array( 'pending', 'declared' ), true ) && $order['plan'] === $plan ) {
				$orders[ $i ]['status']      = 'delivered';
				$orders[ $i ]['delivered_at'] = current_time( 'mysql' );
				$found = true;
			}
		}
		if ( $found ) {
			$this->save_orders( $orders );
		}
	}

	/**
	 * Libère le créneau occupé par le domaine courant.
	 */
	public function deactivate() {
		$data   = $this->read();
		$domain = $this->current_domain();
		$data['domains'] = array_values( array_diff( (array) $data['domains'], array( $domain ) ) );
		if ( empty( $data['domains'] ) ) {
			$data['activated_at'] = '';
		}
		$this->write( $data );
		$this->log_action( 'deactivated', $domain );
		return true;
	}

	/**
	 * Libération d'un créneau ORPHELIN (site supprimé) : un code à 6 chiffres
	 * est envoyé à l'e-mail de la licence pour valider la libération.
	 *
	 * @return string|true 'unknown'|'self'|'mailfail' ou true.
	 */
	public function request_domain_release( $domain ) {
		$data = $this->read();
		if ( ! in_array( $domain, (array) $data['domains'], true ) ) {
			return 'unknown';
		}
		if ( $domain === $this->current_domain() ) {
			return 'self'; // Utiliser « Désactiver sur ce domaine ».
		}
		if ( ! is_email( $data['email'] ) ) {
			return 'mailfail';
		}

		$code = sprintf( '%06d', random_int( 0, 999999 ) );
		set_transient( 'rcb_rel_' . md5( $domain ), $code, HOUR_IN_SECONDS );

		$subject = 'Code de libération du domaine ' . $domain . ' — Infinity RCB Pro';

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Libération d’un créneau de domaine</h1>';
		$html .= $this->email_p( 'Une demande de libération du créneau occupé par <strong>' . esc_html( $domain ) . '</strong> vient d’être faite. Saisissez ce code sur le site d’origine pour confirmer :' );
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
<tr><td style="background-color:#0B0F1E;border-radius:8px;padding:20px;text-align:center;">
<div style="font-size:10px;letter-spacing:2px;color:#7C9BD9;font-weight:700;padding-bottom:8px;">CODE DE CONFIRMATION</div>
<div style="font-family:Consolas,\'Courier New\',monospace;font-size:28px;font-weight:700;letter-spacing:8px;color:#ffffff;">' . esc_html( $code ) . '</div>
<div style="font-size:11px;color:#94A3B8;padding-top:8px;">Valable 1 heure</div>
</td></tr></table>';
		$html .= $this->email_box( 'Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail : aucune action ne sera effectuée.' );

		$body  = "Bonjour,\n\nUne demande de libération du créneau de domaine suivant vient d'être faite :\n" . $domain . "\n\n";
		$body .= "Code de confirmation (valable 1 heure) : " . $code . "\n\n";
		$body .= "Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $data['email'], $subject, 'Code de confirmation : ' . $code . ' (valable 1 heure)', $html, $body, 'release_code' ) ? true : 'mailfail';
	}

	/**
	 * Confirme la libération d'un domaine orphelin avec le code reçu par e-mail.
	 *
	 * @return string|true 'unknown'|'bad' ou true.
	 */
	public function confirm_domain_release( $domain, $code ) {
		$key    = 'rcb_rel_' . md5( $domain );
		$expect = get_transient( $key );
		if ( false === $expect ) {
			return 'bad';
		}
		if ( ! hash_equals( (string) $expect, trim( (string) $code ) ) ) {
			return 'bad';
		}

		$data = $this->read();
		if ( ! in_array( $domain, (array) $data['domains'], true ) ) {
			delete_transient( $key );
			return 'unknown';
		}

		$data['domains'] = array_values( array_diff( (array) $data['domains'], array( $domain ) ) );
		$this->write( $data );
		$this->log_action( 'released', $domain );
		delete_transient( $key );
		return true;
	}

	/* ==================================================================
	 * Commandes (registre local)
	 * ================================================================== */

	public function get_orders() {
		$orders = get_option( self::ORDERS, array() );
		return is_array( $orders ) ? array_values( $orders ) : array();
	}

	private function save_orders( $orders ) {
		if ( count( $orders ) > self::MAX_ORDERS ) {
			$orders = array_slice( $orders, -self::MAX_ORDERS );
		}
		update_option( self::ORDERS, $orders, false );
	}

	/**
	 * Crée une commande en attente de paiement (code promo facultatif).
	 */
	public function create_order( $plan, $buyer, $email, $note = '', $promo_code = '' ) {
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		if ( ! isset( $tiers[ $plan ] ) ) {
			$plan = 'single';
		}

		// Code promo : pourcentage appliqué sur les deux montants
		// (expiration et quota vérifiés par validate_promo).
		$promo_code  = strtoupper( trim( (string) $promo_code ) );
		$discount    = $this->validate_promo( $promo_code );
		$amount_da  = $discount > 0 ? round( $tiers[ $plan ]['price_da'] * ( 100 - $discount ) / 100 ) : $tiers[ $plan ]['price_da'];
		$amount_eur = $discount > 0 ? round( $tiers[ $plan ]['price_eur'] * ( 100 - $discount ) / 100, 2 ) : $tiers[ $plan ]['price_eur'];

		$order = array(
			'ref'         => 'RCB-CMD-' . strtoupper( wp_generate_password( 6, false, false ) ),
			'plan'        => $plan,
			'buyer'       => sanitize_text_field( $buyer ),
			'email'       => sanitize_email( $email ),
			'note'        => sanitize_textarea_field( $note ),
			'amount_da'   => $amount_da,
			'amount_eur'  => $amount_eur,
			'promo_code'  => $discount > 0 ? $promo_code : '',
			'discount_pct'=> $discount,
			'status'      => 'pending',
			'key'         => '',
			'created'     => current_time( 'mysql' ),
		);

		$orders = $this->get_orders();
		array_unshift( $orders, $order );
		$this->save_orders( $orders );
		if ( $discount > 0 ) {
			$this->register_promo_use( $promo_code );
		}
		return $order;
	}

	public function get_order( $ref ) {
		foreach ( $this->get_orders() as $order ) {
			if ( $order['ref'] === $ref ) {
				return $order;
			}
		}
		return null;
	}

	private function update_order( $ref, $callback ) {
		$orders = $this->get_orders();
		foreach ( $orders as $i => $order ) {
			if ( $order['ref'] === $ref ) {
				$orders[ $i ] = call_user_func( $callback, $order );
				$this->save_orders( $orders );
				return $orders[ $i ];
			}
		}
		return null;
	}

	/**
	 * Marque une commande payée, génère sa clé et l'envoie automatiquement
	 * à l'acheteur par e-mail. Réservé au site du vendeur.
	 *
	 * @return array|null|string La commande, 'vendor' si non autorisé, null si inconnue.
	 */
	public function fulfill_order( $ref ) {
		if ( ! self::vendor_enabled() ) {
			return 'vendor';
		}
		$order = $this->update_order( $ref, function ( $order ) {
			if ( '' === $order['key'] ) {
				$order['key'] = self::generate_key( $order['plan'] );
			}
			$order['status'] = 'paid';
			return $order;
		} );
		if ( is_array( $order ) && '' !== $order['key'] ) {
			$this->email_key_to_buyer( $order );
		}
		return $order;
	}

	/**
	 * Renvoie la clé d'une commande déjà livrée à l'acheteur
	 * (site vendeur uniquement — e-mail perdu, boîte spam…).
	 *
	 * @return string|true 'vendor'|'unknown'|'nokey' ou true.
	 */
	public function resend_key_to_buyer( $ref ) {
		if ( ! self::vendor_enabled() ) {
			return 'vendor';
		}
		$order = $this->normalize_order( $this->get_order( $ref ) );
		if ( '' === $order['ref'] ) {
			return 'unknown';
		}
		if ( '' === $order['key'] ) {
			return 'nokey';
		}
		return $this->email_key_to_buyer( $order ) ? true : 'mailfail';
	}

	/**
	 * Livraison automatique : envoie la clé générée à l'acheteur.
	 */
	private function email_key_to_buyer( $order ) {
		$order = $this->normalize_order( $order );
		if ( ! is_email( $order['email'] ) || '' === $order['key'] ) {
			return false;
		}
		$tiers     = infinity_rcb_default_options()['license']['tiers'];
		$label     = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];
		$opts      = infinity_rcb_options();
		$da        = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$plan_short = array( 'single' => 'Mono-Site', 'five' => '5 Sites', 'agency' => 'Agence' );

		$subject = sprintf( 'Votre clé de licence Infinity RCB Pro (%s) — à conserver', isset( $plan_short[ $order['plan'] ] ) ? $plan_short[ $order['plan'] ] : $order['plan'] );

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Votre clé de licence est prête</h1>';
		$html .= $this->email_p( 'Bonjour <strong>' . esc_html( $order['buyer'] ) . '</strong>, merci pour votre achat ! Votre paiement est confirmé et votre licence <strong>' . esc_html( $label ) . '</strong> est validée.' );
		$html .= $this->email_key_box( $order['key'] );
		$html .= $this->email_box( 'Conservez cet e-mail : la clé est votre preuve d’achat et sert pour toutes vos futures activations.' );

		$html .= $this->email_h2( 'Activation en 3 étapes' );
		$html .= $this->email_steps( array(
			'Ouvrez l’administration WordPress de votre site.',
			'Menu <strong>Infinity RCB Pro → Licence &amp; Achat</strong>.',
			'Collez la clé dans « Ma licence » puis cliquez <strong>Activer la licence</strong>.',
		) );

		$html .= $this->email_h2( 'Récapitulatif' );
		$html .= $this->email_rows( array(
			'Commande'      => '<span style="font-family:Consolas,\'Courier New\',monospace;">' . esc_html( $order['ref'] ) . '</span>',
			'Licence'       => esc_html( $label ) . sprintf( ' — %d domaine%s', (int) $tiers[ $order['plan'] ]['domains'], $tiers[ $order['plan'] ]['domains'] > 1 ? 's' : '' ),
			'Montant réglé' => esc_html( $da ) . ' DA',
			'Validité'      => 'Licence à vie, sans abonnement',
		) );

		$text  = sprintf( "Bonjour %s,\n\n", $order['buyer'] );
		$text .= "Merci pour votre achat ! Voici votre clé de licence " . $label . " :\n\n";
		$text .= $order['key'] . "\n\n";
		$text .= "ACTIVATION (10 secondes)\n";
		$text .= "1. Ouvrez votre administration WordPress → Infinity RCB Pro → Licence & Achat.\n";
		$text .= "2. Collez la clé dans « Ma licence » et cliquez « Activer la licence ».\n\n";
		$text .= sprintf( "Commande %s — montant réglé : %s DA\n\n", $order['ref'], $da );
		$text .= sprintf( "Vendeur : %s\n", $opts['developer']['name'] );
		if ( is_email( $opts['developer']['email'] ) ) { $text .= 'Contact : ' . $opts['developer']['email'] . "\n"; }
		$text .= "— Infinity RCB Pro";

		return $this->send_html_mail( $order['email'], $subject, 'Votre clé de licence ' . $label . ' — à conserver précieusement.', $html, $text, 'key_delivery', is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '' );
	}

	/**
	 * Statistiques de ventes pour le tableau de bord vendeur.
	 */
	public function vendor_stats() {
		$pending = 0; $declared = 0; $paid_month = 0; $revenue_da = 0; $total = 0; $last_buyer = '';
		$month_start = date_i18n( 'Y-m-01 00:00:00', current_time( 'timestamp' ) );

		foreach ( $this->get_orders() as $order ) {
			$total++;
			if ( 'pending' === $order['status'] ) { $pending++; }
			if ( 'declared' === $order['status'] ) { $declared++; }
			if ( '' === $last_buyer && ! empty( $order['buyer'] ) ) { $last_buyer = $order['buyer']; }
			if ( in_array( $order['status'], array( 'paid', 'delivered' ), true ) && $order['created'] >= $month_start ) {
				$paid_month++;
				$revenue_da += (float) $order['amount_da'];
			}
		}
		return compact( 'pending', 'declared', 'paid_month', 'revenue_da', 'total', 'last_buyer' );
	}

	/* ==================================================================
	 * Relances automatiques des commandes impayées (tâche quotidienne) :
	 * 1re relance à J+2, 2e à J+5, jamais plus de deux par commande.
	 * ================================================================== */

	public function send_pending_reminders() {
		$sent = 0;
		$now  = current_time( 'timestamp' );

		foreach ( $this->get_orders() as $order ) {
			$order = $this->normalize_order( $order );
			if ( 'pending' !== $order['status'] || (int) $order['reminders'] >= 2 || ! is_email( $order['email'] ) ) {
				continue;
			}

			$created = strtotime( (string) $order['created'] );
			$last    = '' !== $order['last_reminder'] ? strtotime( (string) $order['last_reminder'] ) : $created;

			// 1re relance : 2 jours après la création ; 2e : 3 jours après la 1re.
			$due = ( 0 === (int) $order['reminders'] && $now - $created >= 2 * DAY_IN_SECONDS )
				|| ( 1 === (int) $order['reminders'] && $now - $last >= 3 * DAY_IN_SECONDS );
			if ( ! $due ) {
				continue;
			}

			if ( $this->email_payment_reminder( $order ) ) {
				$sent++;
				$this->update_order( $order['ref'], function ( $o ) {
					$o['reminders']     = (int) $o['reminders'] + 1;
					$o['last_reminder'] = current_time( 'mysql' );
					return $o;
				} );
			}
		}
		return $sent;
	}

	/**
	 * E-mail de relance de paiement (gabarit HTML de la marque).
	 */
	private function email_payment_reminder( $order ) {
		$order  = $this->normalize_order( $order );
		$opts   = infinity_rcb_options();
		$tiers  = $opts['license']['tiers'];
		$da     = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$eur    = number_format( (float) $order['amount_eur'], 2, ',', ' ' );
		$paypal = $this->paypal_url( $order );

		$subject = sprintf( 'Rappel : votre commande %1$s attend son paiement', $order['ref'] );

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Votre commande vous attend toujours</h1>';
		$html .= $this->email_p( 'Bonjour <strong>' . esc_html( $order['buyer'] ) . '</strong>, votre licence Infinity RCB Pro est prête à être livrée dès réception du paiement de la commande <strong>' . esc_html( $order['ref'] ) . '</strong>.' );
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
<tr><td style="background-color:#0B0F1E;border-radius:8px;padding:18px;text-align:center;">
<div style="font-size:10px;letter-spacing:2px;color:#7C9BD9;font-weight:700;padding-bottom:6px;">MONTANT À RÉGLER</div>
<div style="font-size:24px;font-weight:700;color:#ffffff;">' . esc_html( $da ) . ' DA <span style="font-size:15px;font-weight:400;color:#C7DBFF;">≈ ' . esc_html( $eur ) . ' €</span></div>
</td></tr></table>';

		$pay_rows = array();
		if ( '' !== trim( (string) $opts['payment']['baridimob_rip'] ) ) { $pay_rows['BaridiMob (RIP)'] = esc_html( $opts['payment']['baridimob_rip'] ); }
		if ( '' !== trim( (string) $opts['payment']['ccp'] ) )           { $pay_rows['CCP / Edahabia'] = esc_html( $opts['payment']['ccp'] ); }
		if ( '' !== trim( (string) $opts['payment']['rib'] ) )           { $pay_rows['Virement (RIB)'] = esc_html( $opts['payment']['rib'] ); }
		if ( $pay_rows ) {
			$html .= $this->email_h2( 'Comment payer' );
			$html .= $this->email_rows( $pay_rows );
		}
		if ( '' !== $paypal ) {
			$html .= $this->email_button( 'Payer ' . $eur . ' € par PayPal', $paypal );
		}
		$html .= $this->email_box( 'Indiquez la référence <strong>' . esc_html( $order['ref'] ) . '</strong> dans le libellé du paiement.' );
		$html .= $this->email_p( 'Dès réception, la clé de licence est envoyée automatiquement à cette adresse e-mail.' );

		$text  = sprintf( "Bonjour %s,\n\n", $order['buyer'] );
		$text .= sprintf( "Rappel : votre commande %s est en attente de paiement.\n", $order['ref'] );
		$text .= sprintf( "Montant : %s DA ≈ %s €\n\n", $da, $eur );
		if ( '' !== $paypal ) { $text .= sprintf( "PayPal : %s\n", $paypal ); }
		$text .= sprintf( "Indiquez la référence %s dans le libellé du paiement.\n\n", $order['ref'] );
		$text .= "— Infinity RCB Pro";

		return $this->send_html_mail( $order['email'], $subject, 'Commande ' . $order['ref'] . ' en attente — ' . $da . ' DA à régler.', $html, $text, 'reminder', is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '' );
	}

	/* ==================================================================
	 * Bilan hebdo vendeur : récapitulatif chaque lundi (option « digest »).
	 * ================================================================== */

	/**
	 * Envoie le bilan si c'est lundi (ou si $force, depuis l'admin).
	 */
	public function send_weekly_digest( $force = false ) {
		if ( ! self::vendor_enabled() || ! infinity_rcb_options()['payment']['digest'] ) {
			return false;
		}
		if ( ! $force && '1' !== date_i18n( 'N', current_time( 'timestamp' ) ) ) {
			return false; // Lundi uniquement.
		}
		$to = infinity_rcb_options()['developer']['email'];
		if ( ! is_email( $to ) ) {
			return false;
		}

		$week_start = date_i18n( 'Y-m-d 00:00:00', strtotime( '-6 days', current_time( 'timestamp' ) ) );
		$paid = 0; $revenue = 0; $pending = 0; $by_plan = array();

		foreach ( $this->get_orders() as $raw ) {
			$o = $this->normalize_order( $raw );
			if ( 'pending' === $o['status'] ) { $pending++; }
			if ( in_array( $o['status'], array( 'paid', 'delivered' ), true ) && $o['created'] >= $week_start ) {
				$paid++;
				$revenue += (float) $o['amount_da'];
				$by_plan[ $o['plan'] ] = ( $by_plan[ $o['plan'] ] ?? 0 ) + 1;
			}
		}
		arsort( $by_plan );
		$tiers    = infinity_rcb_default_options()['license']['tiers'];
		$top_plan = '';
		foreach ( $by_plan as $plan => $n ) {
			$top_plan = ( $tiers[ $plan ]['label'] ?? $plan ) . ' (' . (int) $n . ')';
			break;
		}

		$subject = 'Bilan hebdo Infinity RCB Pro — ' . date_i18n( 'd/m/Y', current_time( 'timestamp' ) );

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Bilan de la semaine</h1>';
		$html .= $this->email_p( 'Récapitulatif automatique des ventes des 7 derniers jours sur <strong>' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . '</strong>.' );
		$html .= $this->email_rows( array(
			'Licences vendues (7 jours)' => '<strong>' . (int) $paid . '</strong>',
			'Chiffre d’affaires (7 jours)' => '<strong>' . esc_html( number_format( $revenue, 0, ',', ' ' ) ) . ' DA</strong>',
			'Plan le plus vendu'         => '' !== $top_plan ? esc_html( $top_plan ) : '—',
			'Commandes en attente'       => (int) $pending . ( $pending > 0 ? ' — pensez à « Valider + clé » après paiement' : ' ✓' ),
		) );
		$html .= $this->email_box( 'Livraison manuelle : Infinity RCB Pro → Licence &amp; Achat → « Valider + clé ». Les paiements Chargily (CIB/Edahabia) livrent la clé automatiquement.' );

		$text  = "Bilan hebdo Infinity RCB Pro\n\n";
		$text .= sprintf( "Licences vendues (7 jours) : %d\n", $paid );
		$text .= sprintf( "Chiffre d'affaires : %s DA\n", number_format( $revenue, 0, ',', ' ' ) );
		$text .= sprintf( "Commandes en attente : %d\n\n", $pending );
		$text .= "— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, 'Bilan des ventes : ' . (int) $paid . ' licence(s), ' . number_format( $revenue, 0, ',', ' ' ) . ' DA cette semaine.', $html, $text, 'digest' );
	}

	/* ==================================================================
	 * Alerte pic d'attaques : même IP au-delà du seuil horaire.
	 * ================================================================== */

	public function email_attack_alert( $ip, $count, $top_types ) {
		$to = is_email( (string) get_option( 'admin_email' ) ) ? get_option( 'admin_email' ) : infinity_rcb_options()['developer']['email'];
		if ( ! is_email( $to ) ) {
			return false;
		}

		$subject = 'Pic d’activité détecté sur votre site — Infinity RCB Pro';

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Pic de tentatives détecté</h1>';
		$html .= $this->email_p( 'Une adresse IP cumule <strong>' . (int) $count . ' tentatives bloquées en moins d’une heure</strong> sur <strong>' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . '</strong>. Le contenu reste protégé — ce message est purement informatif.' );
		$html .= $this->email_rows( array(
			'Adresse IP'     => '<code>' . esc_html( $ip ) . '</code>',
			'Tentatives (1 h)' => '<strong>' . (int) $count . '</strong>',
			'Types dominants'=> esc_html( $top_types ),
		) );
		$html .= $this->email_box( 'Vous pouvez bannir cette IP depuis votre hébergeur ou un plugin de sécurité. Les détails complets sont dans Infinity RCB Pro → Journaux.' );

		$text  = "Pic d'activité détecté\n\n";
		$text .= sprintf( "IP : %s — %d tentatives bloquées en moins d'une heure.\n", $ip, (int) $count );
		$text .= sprintf( "Types dominants : %s\n\n", $top_types );
		$text .= "Détails : Infinity RCB Pro → Journaux.\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, (int) $count . ' tentatives bloquées depuis ' . $ip . ' en moins d’une heure.', $html, $text, 'alert' );
	}


	/**
	 * Export CSV des commandes.
	 */
	public function export_orders_csv() {
		$labels = array( 'single' => 'Mono-Site', 'five' => '5 Sites', 'agency' => 'Agence' );
		$csv  = "\xEF\xBB\xBF";
		$csv .= '"Date";"Référence";"Plan";"Acheteur";"E-mail";"Montant DA";"Montant EUR";"Remise";"Statut"' . "\r\n";
		foreach ( $this->get_orders() as $o ) {
			$csv .= sprintf(
				'"%s";"%s";"%s";"%s";"%s";"%s";"%s";"%s";"%s"' . "\r\n",
				$o['created'], $o['ref'],
				isset( $labels[ $o['plan'] ] ) ? $labels[ $o['plan'] ] : $o['plan'],
				$o['buyer'], $o['email'],
				number_format( (float) $o['amount_da'], 0, ',', ' ' ),
				number_format( (float) $o['amount_eur'], 2, ',', ' ' ),
				(int) $o['discount_pct'] > 0 ? $o['promo_code'] . ' (-' . (int) $o['discount_pct'] . '%)' : '—',
				$o['status']
			);
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="infinity-rcb-commandes.csv"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		printf( '%s', $csv );
		exit;
	}

	public function delete_order( $ref ) {
		$orders = $this->get_orders();
		foreach ( $orders as $i => $order ) {
			if ( $order['ref'] === $ref ) {
				unset( $orders[ $i ] );
			}
		}
		$this->save_orders( array_values( $orders ) );
		return true;
	}

	/* ==================================================================
	 * Liens de paiement et notifications e-mail (+ journal d'envoi)
	 * ================================================================== */

	const MAILLOG = 'infinity_rcb_pro_maillog';

	/**
	 * Enveloppe d'envoi : wp_mail + journalisation du résultat.
	 */
	private function send_mail( $to, $subject, $body, $type, $headers = array() ) {
		$ok = wp_mail( $to, $subject, $body, $headers );
		$this->log_mail( $to, $subject, $type, $ok );
		return $ok;
	}

	private function log_mail( $to, $subject, $type, $ok ) {
		$log = get_option( self::MAILLOG, array() );
		if ( ! is_array( $log ) ) { $log = array(); }
		array_unshift( $log, array(
			'time'    => current_time( 'mysql' ),
			'to'      => is_array( $to ) ? implode( ', ', $to ) : (string) $to,
			'subject' => substr( (string) $subject, 0, 120 ),
			'type'    => $type,
			'ok'      => (bool) $ok,
		) );
		update_option( self::MAILLOG, array_slice( $log, 0, 50 ), false );
	}

	public function get_mail_log() {
		$log = get_option( self::MAILLOG, array() );
		return is_array( $log ) ? $log : array();
	}

	public function clear_mail_log() {
		delete_option( self::MAILLOG );
	}

	/* ==================================================================
	 * Gabarits d'e-mails HTML (charte Infinity RCB Pro)
	 *
	 * Délivrabilité : multipart/alternative (HTML + texte brut via
	 * PHPMailer->AltBody), From aligné sur le domaine du site (SPF/DKIM
	 * de l'hébergement), Reply-To pour les réponses humaines. Si un
	 * plugin SMTP impose déjà wp_mail_from, on ne l'écrase pas.
	 * ================================================================== */

	/**
	 * Version texte brut du dernier e-mail HTML envoyé.
	 */
	private static $alt_body = '';

	/**
	 * Rattache la version texte à PHPMailer (multipart/alternative).
	 */
	public static function apply_alt_body( $phpmailer ) {
		if ( '' !== self::$alt_body ) {
			$phpmailer->AltBody = self::$alt_body;
		}
		self::$alt_body = '';
	}

	/**
	 * En-têtes d'envoi : Content-Type HTML + From du site + Reply-To.
	 */
	private function mail_headers( $reply_to = '' ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		if ( ! has_filter( 'wp_mail_from' ) ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			$host = $host ? preg_replace( '/^www\./', '', strtolower( $host ) ) : '';
			if ( $host ) {
				$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
				$headers[] = 'From: ' . $name . ' <wordpress@' . $host . '>';
			}
		}
		if ( $reply_to && is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}
		return $headers;
	}

	/**
	 * Enveloppe d'envoi d'un e-mail HTML habillé (HTML + alternative texte).
	 */
	private function send_html_mail( $to, $subject, $preheader, $html, $text, $type, $reply_to = '' ) {
		self::$alt_body = wp_strip_all_tags( $text );
		if ( ! has_action( 'phpmailer_init', array( __CLASS__, 'apply_alt_body' ) ) ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'apply_alt_body' ) );
		}

		$ok = $this->send_mail( $to, $subject, $this->email_shell( $preheader, $html ), $type, $this->mail_headers( $reply_to ) );

		self::$alt_body = ''; // Réinitialisé si phpmailer_init n'a pas déclenché.
		return $ok;
	}

	/**
	 * Squelette commun : entête dégradé de marque, corps, pied de page.
	 * Table imbriquées + styles inline (rendu fiable Gmail/Outlook/mobile).
	 */
	private function email_shell( $preheader, $inner ) {
		$opts    = infinity_rcb_options();
		$vendor  = $opts['developer']['name'];
		$contact = is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '';
		$year    = gmdate( 'Y' );

		$footer_contact = $vendor;
		if ( $contact ) {
			$footer_contact .= ' · <a href="mailto:' . esc_attr( $contact ) . '" style="color:#A5B4FC;text-decoration:underline;">' . esc_html( $contact ) . '</a>';
		}

		return '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>Infinity RCB Pro</title>
</head>
<body style="margin:0;padding:0;background-color:#EEF1F8;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;">' . esc_html( $preheader ) . '</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#EEF1F8;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">

<tr><td style="background-color:#1E6FF0;padding:26px 32px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="padding-right:14px;vertical-align:middle;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="background-color:#ffffff;border-radius:10px;padding:8px 12px;font-size:17px;font-weight:700;color:#1E6FF0;letter-spacing:1px;">RCB</td>
</tr></table>
</td>
<td style="vertical-align:middle;color:#ffffff;">
<div style="font-size:18px;font-weight:700;letter-spacing:3px;line-height:1.2;">INFINITY</div>
<div style="font-size:10px;letter-spacing:2px;color:#C7DBFF;padding-top:3px;">RIGHT CLICK BLOCKER · PRO</div>
</td>
</tr></table>
</td></tr>
<tr><td style="background-color:#7C3AED;font-size:0;line-height:0;height:4px;">&nbsp;</td></tr>

<tr><td style="padding:32px;">
' . $inner . '
</td></tr>

<tr><td style="background-color:#0B0F1E;padding:22px 32px;text-align:center;">
<div style="font-size:12px;line-height:1.8;color:#94A3B8;">' . $footer_contact . '</div>
<div style="font-size:11px;line-height:1.7;color:#64748B;padding-top:4px;">Licence à vie · Mises à jour gratuites · &copy; ' . $year . ' Infinity Coder — Infinity RCB Pro</div>
</td></tr>

</table>
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
<tr><td align="center" style="padding:14px 8px 0;font-size:11px;line-height:1.6;color:#8B93A7;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
E-mail automatique envoyé par le site ' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . ' — aucune réponse n\'est nécessaire.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
	}

	/**
	 * Titre de section.
	 */
	private function email_h2( $text ) {
		return '<h2 style="margin:26px 0 12px;font-size:15px;font-weight:700;color:#0B0F1E;border-left:3px solid #1E6FF0;padding-left:10px;">' . $text . '</h2>';
	}

	/**
	 * Paragraphe.
	 */
	private function email_p( $html ) {
		return '<p style="margin:0 0 14px;font-size:14px;line-height:1.65;color:#334155;">' . $html . '</p>';
	}

	/**
	 * Tableau libellé → valeur.
	 */
	private function email_rows( $pairs ) {
		$rows = '';
		foreach ( $pairs as $label => $value ) {
			$rows .= '<tr>'
				. '<td style="padding:9px 0;border-bottom:1px solid #EEF2F7;font-size:13px;color:#64748B;width:38%;">' . esc_html( $label ) . '</td>'
				. '<td style="padding:9px 0 9px 14px;border-bottom:1px solid #EEF2F7;font-size:13px;font-weight:600;color:#0B0F1E;word-break:break-word;">' . $value . '</td>'
				. '</tr>';
		}
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">' . $rows . '</table>';
	}

	/**
	 * Encadré bleu de mise en avant.
	 */
	private function email_box( $html ) {
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
<tr><td style="background-color:#EEF4FF;border-left:4px solid #1E6FF0;border-radius:8px;padding:14px 16px;font-size:14px;line-height:1.6;color:#0B0F1E;">' . $html . '</td></tr></table>';
	}

	/**
	 * Encadré sombre pour une clé de licence.
	 */
	private function email_key_box( $key, $caption = 'CLÉ DE LICENCE — À CONSERVER' ) {
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
<tr><td style="background-color:#0B0F1E;border-radius:8px;padding:16px;text-align:center;">
<div style="font-size:10px;letter-spacing:2px;color:#7C9BD9;font-weight:700;padding-bottom:8px;">' . esc_html( $caption ) . '</div>
<div style="font-family:Consolas,\'Courier New\',monospace;font-size:13px;line-height:1.6;color:#ffffff;word-break:break-all;">' . esc_html( $key ) . '</div>
</td></tr></table>';
	}

	/**
	 * Bouton d'action centré.
	 */
	private function email_button( $label, $url ) {
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px auto 6px;"><tr>
<td style="background-color:#1E6FF0;border-radius:8px;">
<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:13px 30px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">' . esc_html( $label ) . '</a>
</td></tr></table>';
	}

	/**
	 * Étapes numérotées.
	 */
	private function email_steps( $steps ) {
		$rows = '';
		foreach ( array_values( $steps ) as $i => $step ) {
			$rows .= '<tr>'
				. '<td width="24" valign="top" style="padding:6px 8px 6px 0;font-size:13px;font-weight:700;color:#1E6FF0;">' . ( $i + 1 ) . '.</td>'
				. '<td valign="top" style="padding:6px 0;font-size:13px;line-height:1.6;color:#334155;">' . $step . '</td>'
				. '</tr>';
		}
		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 10px;">' . $rows . '</table>';
	}

	/**
	 * E-mail de diagnostic : vérifie que l'hébergement envoie bien les mails.
	 */
	public function send_test_mail( $to ) {
		$subject = 'Test d’envoi réussi — Infinity RCB Pro';

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Test d’envoi réussi</h1>';
		$html .= $this->email_p( 'Si vous lisez cet e-mail, l’envoi automatique fonctionne sur votre site. Les confirmations de commande, la demande au vendeur, la livraison de la clé et les confirmations d’activation partiront correctement.' );
		$html .= $this->email_h2( 'Détails de l’envoi' );
		$html .= $this->email_rows( array(
			'Site'         => esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			'Adresse'      => esc_html( home_url( '/' ) ),
			'Date d’envoi' => esc_html( date_i18n( 'd/m/Y à H:i', current_time( 'timestamp' ) ) ),
			'Version'      => 'Infinity RCB Pro ' . esc_html( INFINITY_RCB_VERSION ),
			'Format'       => 'HTML + texte brut (multipart)',
		) );
		$html .= $this->email_box( 'Astuce : si cet e-mail arrive dans les indésirables, installez un plugin SMTP (WP Mail SMTP, FluentSMTP…) avec l’e-mail officiel de votre domaine.' );

		$text  = "Test d'envoi réussi — Infinity RCB Pro\n\n";
		$text .= "Si vous recevez cet e-mail, l'envoi automatique fonctionne sur votre site.\n\n";
		$text .= 'Site : ' . home_url( '/' ) . "\n";
		$text .= 'Version : Infinity RCB Pro ' . INFINITY_RCB_VERSION . "\n";
		$text .= "Astuce : en cas d'indésirables, configurez un plugin SMTP.\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, 'Test d’envoi : l’e-mail automatique fonctionne sur votre site.', $html, $text, 'test' );
	}

	/**
	 * Adresse du vendeur (défauts), pour les diagnostics.
	 */
	public function seller_mail_fallback() {
		$email = infinity_rcb_options()['developer']['email'];
		return is_email( $email ) ? $email : '';
	}

	/**
	 * Lien « ouvrir dans ma messagerie » (secours sans wp_mail).
	 */
	public static function mailto_link( $to, $subject, $body ) {
		return 'mailto:' . rawurlencode( $to )
			. '?subject=' . rawurlencode( $subject )
			. '&body=' . rawurlencode( $body );
	}

	/**
	 * URL PayPal réelle (paiement standard) pour une commande.
	 */
	public function paypal_url( $order ) {
		$opts   = infinity_rcb_options();
		$labels = array( 'single' => 'Mono-Site', 'five' => '5 Sites' );

		if ( ! empty( $opts['payment']['paypal_me'] ) ) {
			return 'https://paypal.me/' . rawurlencode( trim( $opts['payment']['paypal_me'], '/' ) ) . '/' . number_format( (float) $order['amount_eur'], 2, '.', '' ) . 'EUR';
		}

		if ( ! empty( $opts['payment']['paypal_email'] ) ) {
			$args = array(
				'cmd'           => '_xclick',
				'business'      => $opts['payment']['paypal_email'],
				'item_name'     => 'Infinity RCB Pro — Licence ' . ( isset( $labels[ $order['plan'] ] ) ? $labels[ $order['plan'] ] : $order['plan'] ),
				'amount'        => number_format( (float) $order['amount_eur'], 2, '.', '' ),
				'currency_code' => 'EUR',
				'no_shipping'   => 1,
			);
			return 'https://www.paypal.com/cgi-bin/webscr?' . build_query( $args );
		}

		return '';
	}

	/**
	 * Prépare une commande normalisée.
	 */
	private function normalize_order( $order ) {
		return wp_parse_args( is_array( $order ) ? $order : array(), array(
			'ref' => '', 'plan' => 'single', 'buyer' => '', 'email' => '', 'note' => '',
			'amount_da' => 0, 'amount_eur' => 0.0, 'promo_code' => '', 'discount_pct' => 0,
			'status' => 'pending', 'key' => '', 'created' => '',
			'reminders' => 0, 'last_reminder' => '', 'chargily_id' => '', 'chargily_url' => '',
			'declared_at' => '',
		) );
	}

	/* ==================================================================
	 * Déclaration de paiement (« J'ai payé ») : le client confirme avoir
	 * réglé ; le vendeur reçoit ALORS la demande de licence actionnable.
	 * Statuts : pending → declared → paid → delivered.
	 * ================================================================== */

	/**
	 * Le client déclare avoir payé sa commande.
	 *
	 * @param  string $ref   Référence de commande.
	 * @param  string $email E-mail de contrôle (parcours public : doit
	 *                       correspondre à celui de la commande ; vide en
	 *                       contexte admin).
	 * @return string|true 'unknown'|'status'|'email'|'ratelimit' ou true.
	 */
	public function declare_payment( $ref, $email = '' ) {
		$order = $this->normalize_order( $this->get_order( $ref ) );
		if ( '' === $order['ref'] ) {
			return 'unknown';
		}
		if ( 'pending' !== $order['status'] ) {
			return 'status';
		}

		// Parcours public : l'e-mail doit correspondre à la commande.
		if ( '' !== $email ) {
			if ( ! hash_equals( strtolower( (string) $order['email'] ), strtolower( sanitize_email( $email ) ) ) ) {
				return 'email';
			}
			// Anti-abus : 5 déclarations par heure et par IP.
			$key = 'rcb_decl_' . md5( self::client_ip() );
			if ( (int) get_transient( $key ) >= 5 ) {
				return 'ratelimit';
			}
			set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
		}

		$this->update_order( $ref, function ( $o ) {
			$o['status']      = 'declared';
			$o['declared_at'] = current_time( 'mysql' );
			return $o;
		} );

		// Le vendeur reçoit la demande de licence (actionnable)…
		$this->email_license_request_to_seller( $order );
		// …et l'acheteur un accusé de réception.
		if ( is_email( $order['email'] ) ) {
			$this->email_payment_declared_ack( $order );
		}
		return true;
	}

	/**
	 * Demande de licence envoyée au VENDEUR après le paiement déclaré
	 * (ou automatiquement via Chargily) : c'est LE message à traiter.
	 */
	public function email_license_request_to_seller( $order ) {
		$order = $this->normalize_order( $order );
		$opts  = infinity_rcb_options();
		$tiers = $opts['license']['tiers'];
		$to    = $opts['developer']['email'];

		if ( ! is_email( $to ) ) {
			return false;
		}

		$plan_label = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];
		$da         = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$eur        = number_format( (float) $order['amount_eur'], 2, ',', ' ' );
		$subject    = sprintf( 'Demande de licence — %1$s payée (%2$s DA) : à livrer', $order['ref'], $da );

		$rows = array(
			'Référence'      => '<span style="font-family:Consolas,\'Courier New\',monospace;">' . esc_html( $order['ref'] ) . '</span>',
			'Licence'        => '<strong>' . esc_html( $plan_label ) . '</strong>',
			'Montant réglé'  => '<strong>' . esc_html( $da ) . ' DA ≈ ' . esc_html( $eur ) . ' €</strong>',
			'Acheteur'       => esc_html( $order['buyer'] ),
			'E-mail'         => '<a href="mailto:' . esc_attr( $order['email'] ) . '" style="color:#1E6FF0;text-decoration:none;">' . esc_html( $order['email'] ) . '</a>',
			'Paiement déclaré le' => esc_html( $order['declared_at'] ?: current_time( 'mysql' ) ),
		);
		if ( (int) $order['discount_pct'] > 0 ) {
			$rows['Remise'] = esc_html( $order['promo_code'] ) . ' (−' . (int) $order['discount_pct'] . '%)';
		}
		if ( '' !== trim( (string) $order['note'] ) ) {
			$rows['Note'] = esc_html( $order['note'] );
		}

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Demande de licence à livrer</h1>';
		$html .= $this->email_p( 'Le client déclare avoir réglé la commande <strong>' . esc_html( $order['ref'] ) . '</strong>. Vérifiez la réception du paiement puis livrez la clé — 10 secondes suffisent.' );
		$html .= $this->email_rows( $rows );
		$html .= $this->email_h2( 'Livrer la clé (10 secondes)' );
		$html .= $this->email_steps( array(
			'Vérifiez la réception du montant (' . esc_html( $da ) . ' DA) : BaridiMob / CCP / virement / PayPal.',
			'Sur votre site vendeur : <strong>Infinity RCB Pro → Licence &amp; Achat → commandes</strong>.',
			'Cliquez <strong>« Valider + clé »</strong> sur ' . esc_html( $order['ref'] ) . ' : la clé signée est générée et <strong>envoyée automatiquement à l’acheteur</strong>.',
		) );
		$html .= $this->email_box( 'Paiement introuvable ? Le bouton « Répondre » écrit directement à l’acheteur (' . esc_html( $order['email'] ) . ') pour demander le justificatif.' );

		$text  = "DEMANDE DE LICENCE A LIVRER\n\n";
		$text .= sprintf( "Le client declare avoir paye la commande %s.\n", $order['ref'] );
		$text .= sprintf( "Licence : %s — %s DA (≈ %s EUR)\n", $plan_label, $da, $eur );
		$text .= sprintf( "Acheteur : %s <%s>\n", $order['buyer'], $order['email'] );
		$text .= sprintf( "Declare le : %s\n\n", $order['declared_at'] ?: current_time( 'mysql' ) );
		$text .= "LIVRER : Infinity RCB Pro → Licence & Achat → « Valider + cle » sur la commande.\n";
		$text .= "Repondre a cet e-mail ecrit a l'acheteur.\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, 'Commande ' . $order['ref'] . ' payée — vérifiez puis livrez la clé (« Valider + clé »).', $html, $text, 'license_request', $order['email'] );
	}

	/**
	 * Accusé de réception au client : paiement enregistré, clé en approche.
	 */
	private function email_payment_declared_ack( $order ) {
		$order = $this->normalize_order( $order );
		$opts  = infinity_rcb_options();
		$da    = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		$label = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];

		$subject = 'Paiement enregistré — votre clé Infinity RCB Pro arrive';

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Paiement bien enregistré</h1>';
		$html .= $this->email_p( 'Bonjour <strong>' . esc_html( $order['buyer'] ) . '</strong>, votre paiement pour la commande <strong>' . esc_html( $order['ref'] ) . '</strong> (' . esc_html( $label ) . ', ' . esc_html( $da ) . ' DA) a été signalé au vendeur.' );
		$html .= $this->email_rows( array(
			'Référence' => '<span style="font-family:Consolas,\'Courier New\',monospace;">' . esc_html( $order['ref'] ) . '</span>',
			'Étape'      => '<strong>Vérification du paiement par le vendeur</strong>',
			'Prochaine étape' => 'Réception de votre clé de licence par e-mail',
		) );
		$html .= $this->email_box( 'Dès la vérification, votre clé de licence est envoyée automatiquement à cette adresse — aucune action de votre part.' );

		$text  = sprintf( "Bonjour %s,\n\n", $order['buyer'] );
		$text .= sprintf( "Votre paiement pour la commande %s (%s DA) a ete signale au vendeur.\n", $order['ref'], $da );
		$text .= "Des verification, votre cle de licence vous sera envoyee automatiquement.\n\n";
		if ( is_email( $opts['developer']['email'] ) ) { $text .= 'Contact vendeur : ' . $opts['developer']['email'] . "\n"; }
		$text .= "— Infinity RCB Pro";

		return $this->send_html_mail( $order['email'], $subject, 'Commande ' . $order['ref'] . ' : paiement enregistré, clé à venir.', $html, $text, 'declared_ack', is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '' );
	}

	/**
	 * Rappel quotidien vendeur : commandes déclarées (payées) non livrées
	 * depuis plus de 24 h. 1 e-mail maximum par jour, uniquement si besoin.
	 */
	public function send_declared_nudge() {
		if ( ! self::vendor_enabled() ) {
			return false;
		}
		$to = infinity_rcb_options()['developer']['email'];
		if ( ! is_email( $to ) ) {
			return false;
		}

		$waiting = array();
		$cut     = date( 'Y-m-d H:i:s', strtotime( '-24 hours', current_time( 'timestamp' ) ) );
		foreach ( $this->get_orders() as $raw ) {
			$o = $this->normalize_order( $raw );
			if ( 'declared' === $o['status'] && '' !== $o['declared_at'] && $o['declared_at'] < $cut ) {
				$waiting[] = $o;
			}
		}
		if ( empty( $waiting ) ) {
			return false;
		}

		$rows = '';
		foreach ( array_slice( $waiting, 0, 10 ) as $o ) {
			$rows .= '<tr>'
				. '<td style="padding:8px 0;border-bottom:1px solid #EEF2F7;font-size:13px;font-family:Consolas,\'Courier New\',monospace;color:#0B0F1E;">' . esc_html( $o['ref'] ) . '</td>'
				. '<td style="padding:8px 0 8px 14px;border-bottom:1px solid #EEF2F7;font-size:13px;font-weight:600;color:#0B0F1E;">' . esc_html( $o['buyer'] ) . ' — ' . number_format( (float) $o['amount_da'], 0, ',', ' ' ) . ' DA</td>'
				. '</tr>';
		}

		$n = count( $waiting );
		$subject = $n . ' commande' . ( $n > 1 ? 's payées attendent leur clé' : ' payée attend sa clé' );

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Des clés à livrer</h1>';
		$html .= $this->email_p( 'Des clients ont déclaré leur paiement il y a plus de 24 heures et attendent leur clé de licence :' );
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">' . $rows . '</table>';
		$html .= $this->email_box( 'Infinity RCB Pro → Licence &amp; Achat → « Valider + clé » sur chaque commande : la clé part automatiquement par e-mail.' );

		$text = $subject . "\n\n";
		foreach ( array_slice( $waiting, 0, 10 ) as $o ) {
			$text .= sprintf( "- %s — %s (%s DA)\n", $o['ref'], $o['buyer'], number_format( (float) $o['amount_da'], 0, ',', ' ' ) );
		}
		$text .= "\nLivraison : Licence & Achat → « Valider + cle ».\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, count( $waiting ) . ' commande(s) payée(s) à livrer — « Valider + clé ».', $html, $text, 'nudge' );
	}

	/**
	 * Attache un checkout Chargily (id + URL de paiement) à une commande.
	 */
	public function set_order_chargily( $ref, $checkout_id, $checkout_url ) {
		return $this->update_order( $ref, function ( $order ) use ( $checkout_id, $checkout_url ) {
			$order['chargily_id']  = sanitize_text_field( (string) $checkout_id );
			$order['chargily_url'] = esc_url_raw( (string) $checkout_url );
			return $order;
		} );
	}

	/* ==================================================================
	 * Codes promo avancés : pourcentage + expiration + quota d'utilisations
	 * Format simple (hérité) : 'CODE' => 20
	 * Format avancé : 'CODE' => array( 'pct' => 20, 'expires' => 'YYYY-MM-DD',
	 *                                  'max_uses' => 50, 'uses' => 3 )
	 * ================================================================== */

	public function get_promos() {
		$promos = infinity_rcb_options()['license']['promo'];
		return is_array( $promos ) ? $promos : array();
	}

	/**
	 * Remise (pourcentage) d'un code valide, ou 0. Un code est invalide
	 * s'il est inconnu, expiré ou épuisé (quota d'utilisations atteint).
	 */
	public function validate_promo( $code ) {
		$code = strtoupper( trim( (string) $code ) );
		if ( '' === $code ) {
			return 0;
		}
		$promos = $this->get_promos();
		if ( ! isset( $promos[ $code ] ) ) {
			return 0;
		}

		$promo = $promos[ $code ];
		if ( is_int( $promo ) || is_string( $promo ) && '' !== $promo ) {
			// Format hérité : simple pourcentage, sans limite.
			return min( 90, max( 1, (int) $promo ) );
		}
		if ( ! is_array( $promo ) ) {
			return 0;
		}

		$pct = min( 90, max( 1, (int) ( $promo['pct'] ?? 0 ) ) );
		if ( $pct < 1 ) {
			return 0;
		}
		// Expiration (incluse) : « expires » au format YYYY-MM-DD.
		if ( ! empty( $promo['expires'] ) && current_time( 'Y-m-d' ) > (string) $promo['expires'] ) {
			return 0;
		}
		// Quota : max_uses vide ou 0 = illimité.
		$max_uses = (int) ( $promo['max_uses'] ?? 0 );
		if ( $max_uses > 0 && (int) ( $promo['uses'] ?? 0 ) >= $max_uses ) {
			return 0;
		}
		return $pct;
	}

	/**
	 * Incrémente le compteur d'utilisations d'un code (format avancé).
	 */
	private function register_promo_use( $code ) {
		$code = strtoupper( trim( (string) $code ) );
		if ( '' === $code ) {
			return;
		}
		$options = infinity_rcb_options();
		$promos  = $options['license']['promo'];
		if ( ! is_array( $promos ) || ! isset( $promos[ $code ] ) || ! is_array( $promos[ $code ] ) ) {
			return;
		}
		$promos[ $code ]['uses'] = (int) ( $promos[ $code ]['uses'] ?? 0 ) + 1;
		$options['license']['promo'] = $promos;
		update_option( INFINITY_RCB_OPTION, $options, false );
	}

	/**
	 * Crée (ou met à jour) un code promo — site vendeur uniquement.
	 */
	public function set_promo( $code, $pct, $expires = '', $max_uses = 0 ) {
		if ( ! self::vendor_enabled() ) {
			return false;
		}
		$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
		$pct  = min( 90, max( 1, (int) $pct ) );
		if ( '' === $code || $pct < 1 ) {
			return false;
		}

		$options = infinity_rcb_options();
		$promos  = is_array( $options['license']['promo'] ) ? $options['license']['promo'] : array();
		$expires = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $expires ) ? $expires : '';

		// Conserve le compteur d'utilisations si le code existe déjà.
		$uses = isset( $promos[ $code ] ) && is_array( $promos[ $code ] ) ? (int) ( $promos[ $code ]['uses'] ?? 0 ) : 0;
		$promos[ $code ] = array(
			'pct'      => $pct,
			'expires'  => $expires,
			'max_uses' => max( 0, (int) $max_uses ),
			'uses'     => $uses,
		);
		$options['license']['promo'] = $promos;
		return (bool) update_option( INFINITY_RCB_OPTION, $options, false );
	}

	public function delete_promo( $code ) {
		if ( ! self::vendor_enabled() ) {
			return false;
		}
		$code   = strtoupper( trim( (string) $code ) );
		$options = infinity_rcb_options();
		$promos  = is_array( $options['license']['promo'] ) ? $options['license']['promo'] : array();
		if ( ! isset( $promos[ $code ] ) ) {
			return false;
		}
		unset( $promos[ $code ] );
		$options['license']['promo'] = $promos;
		return (bool) update_option( INFINITY_RCB_OPTION, $options, false );
	}

	/**
	 * Envoie la demande de commande au vendeur (e-mail du développeur).
	 */
	public function email_order_to_seller( $order ) {
		$order  = $this->normalize_order( $order );
		$opts   = infinity_rcb_options();
		$tiers  = $opts['license']['tiers'];
		$to     = $opts['developer']['email'];

		if ( ! is_email( $to ) ) {
			return false;
		}

		$plan_label = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];
		$da         = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$eur        = number_format( (float) $order['amount_eur'], 2, ',', ' ' );
		$subject    = sprintf( 'Nouvelle commande %1$s — %2$s (%3$s DA) : en attente de paiement', $order['ref'], $plan_label, $da );

		$rows = array(
			'Référence'  => '<span style="font-family:Consolas,\'Courier New\',monospace;">' . esc_html( $order['ref'] ) . '</span>',
			'Plan'       => esc_html( $plan_label ) . sprintf( ' (%d domaine%s)', (int) $tiers[ $order['plan'] ]['domains'], $tiers[ $order['plan'] ]['domains'] > 1 ? 's' : '' ),
			'Montant'    => '<strong>' . esc_html( $da ) . ' DA ≈ ' . esc_html( $eur ) . ' €</strong>',
			'Acheteur'   => esc_html( $order['buyer'] ),
			'E-mail'     => '<a href="mailto:' . esc_attr( $order['email'] ) . '" style="color:#1E6FF0;text-decoration:none;">' . esc_html( $order['email'] ) . '</a>',
			'Créée le'   => esc_html( $order['created'] ),
		);
		if ( (int) $order['discount_pct'] > 0 ) {
			$rows['Remise'] = esc_html( $order['promo_code'] ) . ' (−' . (int) $order['discount_pct'] . '%)';
		}
		$rows['Note acheteur'] = '' !== trim( (string) $order['note'] ) ? esc_html( $order['note'] ) : '—';

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Nouvelle commande (information)</h1>';
		$html .= $this->email_p( 'Une commande de licence vient d’être créée sur <strong>' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . '</strong> (' . esc_html( home_url( '/' ) ) . ').' );
		$html .= $this->email_rows( $rows );
		$html .= $this->email_box( '<strong>Aucune action pour l’instant :</strong> la <strong>demande de licence</strong> vous parviendra automatiquement après le paiement — bouton « J’ai payé » du client ou paiement carte CIB / Edahabia.' );

		$text  = sprintf( "Nouvelle commande (INFORMATION — aucune action avant paiement)\nDepuis le site %s (%s)\n\n", get_bloginfo( 'name' ), home_url( '/' ) );
		$text .= sprintf( "Référence : %s\n", $order['ref'] );
		$text .= sprintf( "Plan : %s\n", $plan_label );
		$text .= sprintf( "Montant : %s DA ≈ %s €\n", $da, $eur );
		if ( (int) $order['discount_pct'] > 0 ) {
			$text .= sprintf( "Remise : %s (-%d%%)\n", $order['promo_code'], (int) $order['discount_pct'] );
		}
		$text .= sprintf( "Acheteur : %s <%s>\n", $order['buyer'], $order['email'] );
		$text .= sprintf( "Créée le : %s\n\n", $order['created'] );
		$text .= "La demande de licence arrivera apres le paiement du client (« J'ai payé » ou carte).\n\n— Infinity RCB Pro";

		return $this->send_html_mail( $to, $subject, 'Commande ' . $order['ref'] . ' créée — en attente de paiement (aucune action).', $html, $text, 'seller_request', $order['email'] );
	}

	/**
	 * Envoie la confirmation de commande à l'acheteur.
	 */
	public function email_order_to_buyer( $order ) {
		$order  = $this->normalize_order( $order );
		$opts   = infinity_rcb_options();
		$pay    = $opts['payment'];
		$tiers  = $opts['license']['tiers'];

		if ( ! is_email( $order['email'] ) ) {
			return false;
		}

		$plan_label = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];
		$paypal     = $this->paypal_url( $order );
		$da         = number_format( (float) $order['amount_da'], 0, ',', ' ' );
		$eur        = number_format( (float) $order['amount_eur'], 2, ',', ' ' );
		$subject    = sprintf( 'Votre commande %1$s — licence Infinity RCB Pro (%2$s)', $order['ref'], $plan_label );

		$rows = array(
			'Référence' => '<span style="font-family:Consolas,\'Courier New\',monospace;">' . esc_html( $order['ref'] ) . '</span>',
			'Plan'      => esc_html( $plan_label ) . sprintf( ' — %d domaine%s, licence à vie', (int) $tiers[ $order['plan'] ]['domains'], $tiers[ $order['plan'] ]['domains'] > 1 ? 's' : '' ),
		);
		if ( (int) $order['discount_pct'] > 0 ) {
			$rows['Remise'] = esc_html( $order['promo_code'] ) . ' (−' . (int) $order['discount_pct'] . '%)';
		}

		$html  = '<h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0B0F1E;">Merci pour votre commande</h1>';
		$html .= $this->email_p( 'Bonjour <strong>' . esc_html( $order['buyer'] ) . '</strong>, votre commande est bien enregistrée. Conservez la référence ci-dessous : elle identifie votre paiement.' );
		$html .= $this->email_rows( $rows );
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
<tr><td style="background-color:#0B0F1E;border-radius:8px;padding:18px;text-align:center;">
<div style="font-size:10px;letter-spacing:2px;color:#7C9BD9;font-weight:700;padding-bottom:6px;">MONTANT À RÉGLER</div>
<div style="font-size:24px;font-weight:700;color:#ffffff;">' . esc_html( $da ) . ' DA <span style="font-size:15px;font-weight:400;color:#C7DBFF;">≈ ' . esc_html( $eur ) . ' €</span></div>
</td></tr></table>';

		$pay_rows = array();
		if ( '' !== trim( (string) $pay['baridimob_rip'] ) ) { $pay_rows['BaridiMob (RIP)'] = esc_html( $pay['baridimob_rip'] ); }
		if ( '' !== trim( (string) $pay['ccp'] ) )           { $pay_rows['CCP / Edahabia'] = esc_html( $pay['ccp'] ); }
		if ( '' !== trim( (string) $pay['rib'] ) )           { $pay_rows['Virement (RIB)'] = esc_html( $pay['rib'] ); }
		if ( $pay_rows ) {
			$html .= $this->email_h2( 'Comment payer' );
			$html .= $this->email_rows( $pay_rows );
		}
		if ( '' !== $paypal ) {
			$html .= $this->email_button( 'Payer ' . $eur . ' € par PayPal', $paypal );
		}
		$html .= $this->email_box( '<strong>Important :</strong> indiquez la référence <strong>' . esc_html( $order['ref'] ) . '</strong> dans le libellé du paiement pour un traitement immédiat.' );
		if ( '' !== trim( (string) $pay['instructions'] ) ) {
			$html .= $this->email_p( nl2br( esc_html( $pay['instructions'] ) ) );
		}

		$html .= $this->email_h2( 'Après votre paiement' );
		$html .= $this->email_steps( array(
			'Revenez sur la page de commande et cliquez <strong>« J’ai payé »</strong> — la demande de licence part aussitôt au vendeur.',
			'Le vendeur vérifie le paiement puis clique <strong>« Valider + clé »</strong>.',
			'Votre clé de licence vous est <strong>envoyée automatiquement</strong> à cette adresse e-mail — activez-la dans Infinity RCB Pro → Licence &amp; Achat.',
		) );

		$text  = sprintf( "Bonjour %s,\n\n", $order['buyer'] );
		$text .= "Merci pour votre commande d'une licence Infinity RCB Pro.\n\n";
		$text .= sprintf( "Référence : %s\n", $order['ref'] );
		$text .= sprintf( "Plan : %s — %d domaine%s, licence à vie\n", $plan_label, (int) $tiers[ $order['plan'] ]['domains'], $tiers[ $order['plan'] ]['domains'] > 1 ? 's' : '' );
		$text .= sprintf( "Montant à régler : %s DA ≈ %s €\n\n", $da, $eur );
		$text .= "POUR PAYER\n";
		if ( '' !== trim( (string) $pay['baridimob_rip'] ) ) { $text .= sprintf( "- BaridiMob (RIP) : %s\n", $pay['baridimob_rip'] ); }
		if ( '' !== trim( (string) $pay['ccp'] ) )           { $text .= sprintf( "- CCP / Edahabia : %s\n", $pay['ccp'] ); }
		if ( '' !== trim( (string) $pay['rib'] ) )           { $text .= sprintf( "- Virement (RIB) : %s\n", $pay['rib'] ); }
		if ( '' !== $paypal )                                { $text .= sprintf( "- PayPal : %s\n", $paypal ); }
		$text .= sprintf( "- Important : indiquez la référence %s dans le libellé du paiement.\n\n", $order['ref'] );
		if ( '' !== trim( (string) $pay['instructions'] ) ) {
			$text .= $pay['instructions'] . "\n\n";
		}
		$text .= "APRÈS PAIEMENT\n";
		$text .= "1. Revenez sur la page de commande et cliquez « J'ai payé ».\n";
		$text .= "2. Le vendeur vérifie puis valide : la clé vous est envoyée automatiquement.\n";
		$text .= "3. Activez-la dans Infinity RCB Pro → Licence & Achat.\n\n";
		$text .= sprintf( "Vendeur : %s\n", $opts['developer']['name'] );
		if ( is_email( $opts['developer']['email'] ) ) {
			$text .= sprintf( "Contact : %s\n", $opts['developer']['email'] );
		}
		$text .= "— Infinity RCB Pro";

		return $this->send_html_mail( $order['email'], $subject, 'Commande ' . $order['ref'] . ' enregistrée — montant à régler : ' . $da . ' DA.', $html, $text, 'buyer_confirm', is_email( $opts['developer']['email'] ) ? $opts['developer']['email'] : '' );
	}
}
