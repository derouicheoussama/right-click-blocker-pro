<?php
/**
 * Passerelle Chargily Pay — paiement par carte EDAHABIA / CIB (Algérie).
 *
 * Réservé au SITE VENDEUR (comme tout le volet commercial) : la clé API
 * n'est configurée que sur le site du développeur. Aucun appel sortant
 * n'est effectif tant que la clé n'est pas renseignée.
 *
 * Flux : [rcb_commande] → création de checkout (montant en centimes) →
 * redirection vers pay.chargily.net → paiement → webhook signé HMAC-SHA256
 * (en-tête « signature ») → vérification → fulfill_order() génère ET
 * envoie la clé à l'acheteur automatiquement. Livraison 100 % automatique.
 *
 * API : https://pay.chargily.net/api/v2/checkouts (live) ou
 *       https://pay.chargily.net/test/api/v2/checkouts (test).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Chargily {

	const LIVE_BASE = 'https://pay.chargily.net/api/v2/checkouts';
	const TEST_BASE = 'https://pay.chargily.net/test/api/v2/checkouts';

	/**
	 * Passerelle configurée ? (site vendeur + clé API renseignée)
	 */
	public static function enabled() {
		$pay = infinity_rcb_options()['payment'];
		return Infinity_RCB_License::vendor_enabled()
			&& '' !== trim( (string) ( $pay['chargily_key'] ?? '' ) );
	}

	public static function secret_key() {
		return trim( (string) ( infinity_rcb_options()['payment']['chargily_key'] ?? '' ) );
	}

	public static function api_url() {
		$mode = infinity_rcb_options()['payment']['chargily_mode'] ?? 'test';
		return 'live' === $mode ? self::LIVE_BASE : self::TEST_BASE;
	}

	/**
	 * Crée un checkout pour une commande et renvoie
	 * array( 'id' => …, 'url' => … ) ou false en cas d'échec.
	 */
	public static function create_checkout( $order, $success_url, $failure_url ) {
		$key = self::secret_key();
		if ( '' === $key || empty( $order['ref'] ) ) {
			return false;
		}

		$body = array(
			// Chargily exprime les montants en centimes (DA × 100).
			'amount'          => (int) round( (float) $order['amount_da'] * 100 ),
			'currency'        => 'dzd',
			'success_url'     => $success_url,
			'failure_url'     => $failure_url,
			'description'     => 'Commande ' . $order['ref'] . ' — Infinity RCB Pro',
			'locale'          => 'fr',
			'webhook_endpoint'=> self::webhook_url(),
			'metadata'        => array( 'ref' => $order['ref'] ),
		);

		$response = wp_remote_post( self::api_url(), array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['checkout_url'] ) ) {
			return false;
		}

		return array(
			'id'  => isset( $data['id'] ) ? (string) $data['id'] : '',
			'url' => (string) $data['checkout_url'],
		);
	}

	/**
	 * URL du webhook (à renseigner aussi dans le tableau de bord Chargily).
	 */
	public static function webhook_url() {
		return function_exists( 'rest_url' ) ? rest_url( 'infinity-rcb/v1/chargily' ) : '';
	}

	/**
	 * Vérifie la signature HMAC-SHA256 (hex) de la charge brute reçue.
	 */
	public static function verify_signature( $raw_body, $signature ) {
		$key = self::secret_key();
		if ( '' === $key || ! is_string( $signature ) || '' === $signature ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', (string) $raw_body, $key ), $signature );
	}

	/**
	 * Enregistrement de la route REST du webhook.
	 */
	public static function register_routes() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}
		register_rest_route( 'infinity-rcb/v1', '/chargily', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_webhook' ),
			'permission_callback' => '__return_true', // Authentification par signature HMAC.
		) );
	}

	/**
	 * Réception du webhook : signature → événement checkout.paid → livraison.
	 */
	public static function handle_webhook( $request ) {
		$raw  = $request->get_body();
		$sig  = $request->get_header( 'signature' );

		if ( ! self::enabled() || ! self::verify_signature( $raw, $sig ) ) {
			return new WP_Error( 'rcb_chargily_signature', 'Signature invalide.', array( 'status' => 403 ) );
		}

		$event = json_decode( $raw, true );
		$type  = isset( $event['type'] ) ? (string) $event['type'] : '';
		$data  = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : array();

		if ( 'checkout.paid' === $type && 'paid' === ( $data['status'] ?? '' ) ) {
			$ref  = '';
			if ( isset( $data['metadata']['ref'] ) ) {
				$ref = sanitize_text_field( (string) $data['metadata']['ref'] );
			} elseif ( isset( $data['description'] ) && preg_match( '/RCB-CMD-[A-Z0-9]{6}/', (string) $data['description'], $m ) ) {
				$ref = $m[0]; // Anciennes charges sans métadonnées.
			}

			if ( '' !== $ref ) {
				$lic   = new Infinity_RCB_License();
				$order = $lic->get_order( $ref );
				// Idempotent : seule une commande non encore payée déclenche
				// la livraison (pending = jamais payée, declared = « J'ai payé »
				// déclaré manuellement et confirmé par le paiement carte).
				if ( $order && in_array( $order['status'], array( 'pending', 'declared' ), true ) ) {
					$lic->fulfill_order( $ref );
				}
			}
		}

		return array( 'received' => true );
	}
}
