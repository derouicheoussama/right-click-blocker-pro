<?php
/**
 * Classe principale : charge l'admin, le front et les hooks communs.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
		add_action( 'wp_ajax_infinity_rcb_track', array( $this, 'ajax_track' ) );
		add_action( 'wp_ajax_nopriv_infinity_rcb_track', array( $this, 'ajax_track' ) );
		add_action( INFINITY_RCB_CRON, array( $this, 'daily_maintenance' ) );
		add_action( 'send_headers', array( $this, 'security_headers' ) );
		add_action( 'rest_api_init', array( 'Infinity_RCB_Chargily', 'register_routes' ) );
		add_action( 'init', array( $this, 'register_block' ), 25 );
		add_action( 'template_redirect', array( $this, 'country_guard' ), 1 );
	}

	/**
	 * Pays du visiteur (code ISO 2 lettres) lu depuis les en-têtes fournis
	 * par l'hébergeur ou le CDN (Cloudflare, GeoIP…) — aucun appel externe.
	 *
	 * @return string Code pays (ex. DZ) ou '' si inconnu.
	 */
	private function visitor_country() {
		foreach ( array( 'HTTP_CF_IPCOUNTRY', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_GEOIP_COUNTRY', 'HTTP_X_VARNISH_COUNTRY' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) && preg_match( '/^[A-Za-z]{2}$/', (string) $_SERVER[ $key ] ) ) {
				return strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
			}
		}
		return '';
	}

	/**
	 * Blocage par pays (liste noire ou liste blanche) — page 403 dédiée.
	 * Échoue « ouvert » : pays indétectable = accès autorisé (aucun visiteur
	 * légitime n'est jamais bloqué par erreur).
	 */
	public function country_guard() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}
		$options = infinity_rcb_options();
		$cb      = isset( $options['advanced']['country_block'] ) && is_array( $options['advanced']['country_block'] ) ? $options['advanced']['country_block'] : array();
		if ( empty( $options['master_enable'] ) || empty( $cb['on'] ) ) {
			return;
		}
		if ( ! empty( $options['advanced']['exclude_admins'] ) && current_user_can( 'manage_options' ) ) {
			return;
		}

		$country = $this->visitor_country();
		if ( '' === $country ) {
			return; // Indétectable : on ne bloque jamais à l'aveugle.
		}

		$codes = array_values( array_filter( array_map( 'strtoupper', array_map( 'trim', (array) preg_split( '/[,\s]+/', (string) ( $cb['codes'] ?? '' ) ) ) ) ) );
		if ( empty( $codes ) ) {
			return;
		}

		$hit   = in_array( $country, $codes, true );
		$block = ( 'allow' === ( $cb['mode'] ?? 'block' ) ) ? ! $hit : $hit;
		if ( ! $block ) {
			return;
		}

		// Journal + statistiques (même mécanisme que les protections JS).
		if ( ! empty( $options['logging']['enabled'] ) && class_exists( 'Infinity_RCB_Logger' ) ) {
			$logger = new Infinity_RCB_Logger();
			$logger->log( 'country', 'warning' );
		}
		if ( ! empty( $options['advanced']['tracking'] ) && ! empty( $options['logging']['enabled'] ) && class_exists( 'Infinity_RCB_Stats' ) ) {
			$stats = new Infinity_RCB_Stats();
			$stats->track( 'country' );
		}

		$msg   = '' !== trim( (string) ( $cb['message'] ?? '' ) ) ? $cb['message'] : 'L’accès à ce site n’est pas disponible depuis votre pays.';
		$title = get_bloginfo( 'name' );

		nocache_headers();
		status_header( 403 );
		header( 'Content-Type: text/html; charset=utf-8' );
		printf(
			'<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>403 — %1$s</title><style>body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f1f5f9;display:grid;place-items:center;min-height:100vh;color:#1e293b}.box{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:38px 42px;max-width:440px;text-align:center;box-shadow:0 12px 40px -14px rgba(15,23,42,.18)}.ico{width:62px;height:62px;border-radius:16px;background:linear-gradient(135deg,#1E6FF0,#7C3AED);display:inline-flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:16px}h1{font-size:17px;margin:0 0 8px}p{font-size:14px;line-height:1.6;color:#475569;margin:0}small{display:block;margin-top:16px;color:#94a3b8;font-size:11.5px}</style></head><body><div class="box"><span class="ico">🌍</span><h1>Accès refusé</h1><p>%2$s</p><small>%1$s — pays détecté : %3$s</small></div></body></html>',
			esc_html( $title ),
			esc_html( $msg ),
			esc_html( $country )
		);
		exit;
	}

	public function run() {
		new Infinity_RCB_Admin( INFINITY_RCB_VERSION );
		new Infinity_RCB_Shop();
	}

	/**
	 * La page courante est-elle exclue (ID, slug ou URI) ?
	 */
	private function is_page_excluded( $list ) {
		$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $list ) ) );
		if ( empty( $list ) ) {
			return false;
		}

		$id   = is_singular() ? (int) get_the_ID() : 0;
		$slug = '';
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post && isset( $post->post_name ) ) {
				$slug = $post->post_name;
			}
		}
		$uri = '';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$rcb_req_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$parsed      = wp_parse_url( $rcb_req_uri, PHP_URL_PATH );
			$uri         = trim( (string) $parsed, '/' );
		}

		foreach ( $list as $entry ) {
			$entry = strtolower( trim( $entry ) );
			if ( '' === $entry ) {
				continue;
			}
			if ( 0 !== $id && ctype_digit( $entry ) && (int) $entry === $id ) {
				return true;
			}
			if ( '' !== $slug && $entry === strtolower( $slug ) ) {
				return true;
			}
			if ( '' !== $uri && $entry === strtolower( $uri ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * L'utilisateur courant porte-t-il un rôle exclu de la protection ?
	 */
	private function is_role_excluded( $roles ) {
		if ( empty( $roles ) || ! function_exists( 'wp_get_current_user' ) ) {
			return false;
		}
		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}
		foreach ( (array) $roles as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * En-tête anti-clickjacking : interdit l'affichage du site dans une
	 * iframe tierce (X-Frame-Options: SAMEORIGIN).
	 */
	public function security_headers() {
		$options = infinity_rcb_options();
		if ( empty( $options['master_enable'] ) || empty( $options['advanced']['xfo'] ) ) {
			return;
		}
		if ( ! empty( $options['advanced']['exclude_admins'] ) && current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( $this->is_role_excluded( $options['advanced']['exclude_roles'] ) ) {
			return;
		}
		if ( $this->is_page_excluded( $options['advanced']['exclude_pages'] ) ) {
			return;
		}
		header( 'X-Frame-Options: SAMEORIGIN' );
	}

	/**
	 * Bloc Gutenberg « Démo de protection » (rendu dynamique du shortcode
	 * [rcb_demo] — aucun build, script natif wp.blocks).
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		wp_register_script(
			'infinity-rcb-block',
			INFINITY_RCB_URL . 'assets/js/infinity-rcb-block.js',
			array( 'wp-blocks', 'wp-element' ),
			INFINITY_RCB_VERSION,
			true
		);
		register_block_type( 'infinity-rcb/demo', array(
			'editor_script'   => 'infinity-rcb-block',
			'render_callback' => function () {
				return do_shortcode( '[rcb_demo]' );
			},
			'attributes'      => array(),
			'icon'            => 'shield',
			'category'        => 'widgets',
		) );
	}

	/**
	 * Assets front : chargés uniquement si la protection est active
	 * et que la page n'est pas exclue. Injecte aussi le garde noscript
	 * et le CSS durcissement des images.
	 */
	public function enqueue_public() {
		$options = infinity_rcb_options();
		if ( empty( $options['master_enable'] ) ) {
			return;
		}

		// Exclusions : administrateurs connectés, rôles choisis et pages listées.
		if ( ! empty( $options['advanced']['exclude_admins'] ) && current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( $this->is_role_excluded( $options['advanced']['exclude_roles'] ) ) {
			return;
		}
		if ( $this->is_page_excluded( $options['advanced']['exclude_pages'] ) ) {
			return;
		}

		wp_enqueue_style( 'infinity-rcb-public', INFINITY_RCB_URL . 'assets/css/infinity-rcb-public.css', array(), INFINITY_RCB_VERSION );
		wp_enqueue_script( 'infinity-rcb-public', INFINITY_RCB_URL . 'assets/js/infinity-rcb-public.js', array(), INFINITY_RCB_VERSION, true );

		// Garde sans JavaScript : bandeau d'avertissement si JS est désactivé.
		if ( ! empty( $options['advanced']['noscript_warn'] ) ) {
			add_action( 'wp_head', array( $this, 'noscript_guard' ), 99 );
		}

		// CSS durcissement des images : pointer-events none + callout bloqué.
		if ( ! empty( $options['advanced']['image_pointer'] ) ) {
			wp_add_inline_style( 'infinity-rcb-public', $this->image_css() );
		}

		// Filigrane : le texte est posé sur les images par le JS (wrapper
		// .rcb-wm + pseudo-élément), le CSS définit l'apparence.
		if ( ! empty( $options['advanced']['watermark'] ) ) {
			wp_add_inline_style( 'infinity-rcb-public', $this->watermark_css() );
		}

		// Mention copyright : variables remplacées côté serveur à chaque chargement.
		$copyright = str_replace(
			array( '{annee}', '{site}', '{url}' ),
			array( date_i18n( 'Y' ), get_bloginfo( 'name' ), home_url( '/' ) ),
			(string) $options['appearance']['copyright_text']
		);
		$wm_text = str_replace(
			array( '{annee}', '{site}', '{url}' ),
			array( date_i18n( 'Y' ), get_bloginfo( 'name' ), home_url( '/' ) ),
			(string) $options['appearance']['wm_text']
		);

		wp_localize_script( 'infinity-rcb-public', 'InfinityRCB', array(
			'master' => true,
			'prot'   => $options['protections'],
			'msg'    => $options['messages'],
			'style'  => $options['appearance']['style'],
			'pos'    => $options['appearance']['position'],
			'dur'    => (int) $options['appearance']['duration'],
			'bg'     => $options['appearance']['custom_bg'],
			'tx'     => $options['appearance']['custom_text'],
			'icon'   => ! empty( $options['appearance']['show_icon'] ),
			'icon_url' => esc_url_raw( (string) $options['appearance']['custom_icon'] ),
			'sound'  => ! empty( $options['appearance']['sound'] ),
			'copy'   => array(
				'on'   => ! empty( $options['appearance']['copyright_enable'] ) && '' !== trim( $copyright ),
				'text' => $copyright,
			),
			'close'  => ! empty( $options['appearance']['show_close'] ),
			'bar'    => ! empty( $options['appearance']['show_progress'] ),
			'look'   => array(
				'radius' => (int) $options['appearance']['radius'],
				'font'   => (int) $options['appearance']['font_size'],
				'shadow' => ! empty( $options['appearance']['shadow'] ),
				'css'    => wp_strip_all_tags( (string) $options['appearance']['custom_css'] ),
			),
			'track'  => ! empty( $options['advanced']['tracking'] ) && ( ! empty( $options['logging']['enabled'] ) ),
			'adv'    => array(
				'interval' => max( 200, (int) $options['advanced']['interval'] ),
				'blur'     => ! empty( $options['advanced']['blur'] ),
				'redirect' => esc_url_raw( $options['advanced']['redirect'] ),
				'touch'    => ! empty( $options['advanced']['touch_guard'] ),
				'cwarn'    => ! empty( $options['advanced']['console_warn'] ),
			),
			'fb'     => ! empty( $options['advanced']['frame_bust'] ),
			'wm'     => array(
				'on'   => ! empty( $options['advanced']['watermark'] ),
				'text' => wp_strip_all_tags( $wm_text ),
			),
			'ajax'   => admin_url( 'admin-ajax.php' ),
			'nonce'  => wp_create_nonce( 'infinity_rcb_public' ),
		) );
	}

	/**
	 * Bandeau noscript : avertit le visiteur si JavaScript est désactivé.
	 */
	public function noscript_guard() {
		echo '<noscript><div style="background:#1e293b;color:#fff;padding:12px 20px;text-align:center;font:600 14px/1.4 system-ui,sans-serif;position:fixed;bottom:0;left:0;right:0;z-index:2147483647;">🛡️ Activez JavaScript pour profiter pleinement de ce site — certaines protections de contenu nécessitent JavaScript.</div></noscript>';
	}

	/**
	 * CSS durcissement des images : pointer-events none + callout bloqué iOS.
	 */
	private function image_css() {
		return "body img{-webkit-touch-callout:none !important}body:not(.rcb-exempt) img:not([data-rcb-exempt]){pointer-events:none !important;-webkit-user-select:none !important;user-select:none !important}body:not(.rcb-exempt) img:not([data-rcb-exempt]) ~ *{pointer-events:auto}";
	}

	/**
	 * CSS du filigrane : le wrapper .rcb-wm est posé par le JS autour de
	 * chaque image (texte affiché par le pseudo-élément ::after).
	 */
	private function watermark_css() {
		$opts    = infinity_rcb_options();
		$opacity = isset( $opts['appearance']['wm_opacity'] ) ? max( 10, min( 100, (int) $opts['appearance']['wm_opacity'] ) ) : 55;
		$size    = isset( $opts['appearance']['wm_size'] ) ? max( 8, min( 48, (int) $opts['appearance']['wm_size'] ) ) : 13;
		$alpha   = round( $opacity / 100, 2 );

		return '.rcb-wm{position:relative;display:inline-block;max-width:100%}'
			. '.rcb-wm>img{display:block;max-width:100%}'
			. '.rcb-wm::after{content:attr(data-wm);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;'
			. 'color:rgba(255,255,255,' . $alpha . ');font:600 ' . $size . 'px/1.35 system-ui,sans-serif;text-align:center;padding:8px;'
			. 'text-shadow:0 1px 4px rgba(0,0,0,.8);pointer-events:none;user-select:none;word-break:break-word}';
	}

	/**
	 * Point d'entrée AJAX (visiteurs connectés et non connectés).
	 * Accepte soit « type » (évènement unique) soit « data » (lot {type: n}).
	 */
	public function ajax_track() {
		check_ajax_referer( 'infinity_rcb_public', 'nonce' );

		$types = array_keys( Infinity_RCB_Stats::types() );

		if ( isset( $_POST['data'] ) ) {
			$raw = json_decode( wp_unslash( $_POST['data'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON brut normalisé clé/valeur immédiatement ci-dessous.
			if ( ! is_array( $raw ) ) {
				wp_send_json_error( array( 'message' => 'Lot invalide.' ), 400 );
			}
			// Normalisation immédiate : liste blanche des types + plafonnement
			// des quantités — rien d'arbitraire n'entre dans stats/journaux.
			$counts = array();
			foreach ( $raw as $raw_type => $raw_n ) {
				$raw_type = sanitize_key( (string) $raw_type );
				if ( in_array( $raw_type, $types, true ) ) {
					$counts[ $raw_type ] = min( 1000, absint( $raw_n ) );
				}
			}
		} else {
			$type   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
			$counts = in_array( $type, $types, true ) ? array( $type => 1 ) : array();
		}

		if ( empty( $counts ) ) {
			wp_send_json_error( array( 'message' => 'Type inconnu.' ), 400 );
		}

		// Anti-flux : un lot toutes les 2 secondes maximum par visiteur.
		$throttle_key = 'rcb_tr_' . md5( Infinity_RCB_Stats::client_ip() );
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_success( array( 'throttled' => true ) );
		}
		set_transient( $throttle_key, 1, 2 );

		$stats = new Infinity_RCB_Stats();
		$stats->track_batch( $counts );

		$options = infinity_rcb_options();
		if ( ! empty( $options['logging']['enabled'] ) ) {
			$logger = new Infinity_RCB_Logger();
			$logger->log_batch( $counts, 'warning' );
		}

		// Alerte pic d'attaques : même IP au-delà du seuil horaire.
		$threshold = isset( $options['advanced']['alert_threshold'] ) ? (int) $options['advanced']['alert_threshold'] : 0;
		if ( $threshold > 0 ) {
			$ip    = Infinity_RCB_Stats::client_ip();
			$akey  = 'rcb_att_' . md5( $ip );
			$guard = 'rcb_alert_' . md5( $ip );
			$count = (int) get_transient( $akey ) + array_sum( $counts );
			set_transient( $akey, min( $count, 100000 ), HOUR_IN_SECONDS );

			if ( $count >= $threshold && ! get_transient( $guard ) ) {
				set_transient( $guard, 1, HOUR_IN_SECONDS );
				arsort( $counts );
				// Alerte e-mail : fonctionnalite de la licence a vie.
				$license = new Infinity_RCB_License();
				$lic = $license->status();
				if ( 'active' === $lic['code'] ) {
					$license->email_attack_alert( $ip, $count, implode( ', ', array_keys( array_slice( $counts, 0, 3, true ) ) ) );
				}
			}
		}

		wp_send_json_success( array( 'throttled' => false ) );
	}

	/**
	 * Maintenance quotidienne : rotation des journaux, relances des
	 * commandes impayées (J+2 / J+5), rappel vendeur des commandes payées
	 * non livrées et bilan hebdo vendeur (lundi).
	 */
	public function daily_maintenance() {
		$options = infinity_rcb_options();
		$logger  = new Infinity_RCB_Logger();
		$logger->rotate( (int) $options['logging']['retention_days'], (int) $options['logging']['max_entries'] );

		$license = new Infinity_RCB_License();
		$license->send_pending_reminders();
		$license->send_declared_nudge();
		$license->send_weekly_digest();
	}
}
