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
