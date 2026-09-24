<?php
/**
 * Mise à jour double canal : WordPress.org (automatique, prioritaire) et
 * GitHub Releases (installations manuelles / pré-publication).
 *
 * CONFORMITÉ WORDPRESS.ORG : si l'extension est gérée par le répertoire
 * officiel (présente dans « response » ou « no_update » du transiente des
 * mises à jour), le canal GitHub se désactive automatiquement — wp.org
 * reste la SEULE source de mise à jour, conformément aux directives.
 *
 * Configuration du dépôt GitHub (ordre de priorité) :
 *   1. define( 'INFINITY_RCB_GITHUB_REPO', 'utilisateur/depot' );
 *   2. Réglages → Général → Mises à jour (site vendeur) ;
 *   3. Valeur par défaut du code (mise par le vendeur avant distribution).
 *
 * Convention de release : tag « 2.14.0 » (ou « v2.14.0 ») avec UN asset
 * .zip contenant le dossier du plugin (ex. right-click-blocker-pro/).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Updater {

	const CACHE_KEY = 'rcb_gh_latest';
	const CACHE_TTL = 43200; // 12 heures.

	/**
	 * Dépôt GitHub « utilisateur/depot » (vide = wp.org uniquement).
	 */
	public static function repo() {
		$repo = trim( (string) ( infinity_rcb_options()['updates']['github_repo'] ?? '' ) );
		if ( defined( 'INFINITY_RCB_GITHUB_REPO' ) && '' !== (string) INFINITY_RCB_GITHUB_REPO ) {
			$repo = (string) INFINITY_RCB_GITHUB_REPO;
		}
		return preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ? $repo : '';
	}

	public static function github_enabled() {
		return ! empty( infinity_rcb_options()['updates']['github_enabled'] );
	}

	/**
	 * Hooks (appelé à plugins_loaded).
	 */
	public static function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'filter_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'filter_plugins_api' ), 10, 3 );
	}

	/**
	 * Dernière release GitHub : array( version, package, notes, date ) ou null.
	 */
	public static function latest_release( $force = false ) {
		$repo = self::repo();
		if ( '' === $repo || ! self::github_enabled() ) {
			return null;
		}

		if ( ! $force && false !== ( $cached = get_site_transient( self::CACHE_KEY ) ) ) {
			return is_array( $cached ) ? $cached : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . $repo . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; InfinityRCBPro',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS ); // Échec : nouvelle tentative dans 1 h.
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['tag_name'] ) ) {
			set_site_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS );
			return null;
		}

		// Premier asset .zip de la release (l'archive du plugin).
		$package = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( isset( $asset['browser_download_url'] ) && '.zip' === substr( $asset['name'], -4 ) ) {
					$package = (string) $asset['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $package ) {
			set_site_transient( self::CACHE_KEY, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$release = array(
			'version' => ltrim( (string) $data['tag_name'], 'vV' ),
			'package' => $package,
			'notes'   => isset( $data['body'] ) ? (string) $data['body'] : '',
			'date'    => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
		);
		set_site_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
		return $release;
	}

	/**
	 * Offre la mise à jour GitHub uniquement si wp.org ne gère pas déjà
	 * l'extension (conformité répertoire officiel) et si la release est
	 * plus récente que la version installée.
	 */
	public static function filter_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$base = INFINITY_RCB_BASENAME;
		// wp.org propose déjà une mise à jour OU connaît l'extension
		// (installée depuis le répertoire) → wp.org reste maître.
		if ( isset( $transient->response[ $base ] ) || isset( $transient->no_update[ $base ] ) ) {
			return $transient;
		}

		$release = self::latest_release();
		if ( null === $release || version_compare( INFINITY_RCB_VERSION, $release['version'], '>=' ) ) {
			return $transient;
		}

		$obj                       = new stdClass();
		$obj->slug                 = dirname( $base );
		$obj->plugin               = $base;
		$obj->new_version          = $release['version'];
		$obj->url                  = 'https://github.com/' . self::repo() . '/releases';
		$obj->package              = $release['package'];
		$obj->icons                = array( 'default' => INFINITY_RCB_URL . 'assets/icon-256.png' );
		$transient->response[ $base ] = $obj;

		return $transient;
	}

	/**
	 * Fiche « détails de la mise à jour » (fenêtre WordPress) alimentée
	 * par les notes de la release GitHub.
	 */
	public static function filter_plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}
		if ( dirname( INFINITY_RCB_BASENAME ) !== $args->slug ) {
			return $result;
		}

		$release = self::latest_release();
		if ( null === $release ) {
			return $result;
		}

		$notes_raw = function_exists( 'mb_substr' ) ? mb_substr( $release['notes'], 0, 4000 ) : substr( $release['notes'], 0, 4000 );
		$notes     = wp_kses_post( nl2br( $notes_raw ) );
		$info                 = new stdClass();
		$info->name           = 'Right Click Blocker PRO – Right Click & Content Protection';
		$info->slug           = $args->slug;
		$info->version        = $release['version'];
		$info->download_link  = $release['package'];
		$info->tested         = '6.8';
		$info->requires       = '4.9';
		$info->requires_php   = '7.0';
		$info->last_updated   = $release['date'];
		$info->author         = 'Infinity Coder';
		$info->homepage       = 'https://github.com/' . self::repo() . '/releases';
		$info->sections       = array(
			'description' => 'Blocage du clic droit, copie, sélection, impression, DevTools — statistiques, journaux, messages personnalisés. Tout est inclus, gratuitement.',
			'changelog'   => '' !== $notes ? $notes : '<p>Voir les notes de la release sur GitHub.</p>',
		);
		$info->icons          = array( 'default' => INFINITY_RCB_URL . 'assets/icon-256.png' );

		return $info;
	}

	/**
	 * « Vérifier maintenant » : purge les caches puis relit la release.
	 *
	 * @return array|WP_Error|null Résultat pour l'avis admin.
	 */
	public static function refresh() {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		return self::latest_release( true );
	}
}
