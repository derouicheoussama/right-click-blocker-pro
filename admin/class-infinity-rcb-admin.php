<?php
/**
 * Interface d'administration : menus, pages, sauvegarde des réglages,
 * actions (réinitialisation, purge, exports CSV) et AJAX temps réel.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Admin {

	private $version;
	private $stats;
	private $logger;
	private $license;
	private $pages = array(
		'infinity-rcb-pro'          => 'dashboard',
		'infinity-rcb-pro-settings' => 'settings',
		'infinity-rcb-pro-license'  => 'license',
		'infinity-rcb-pro-stats'    => 'stats',
		'infinity-rcb-pro-logs'     => 'logs',
		'infinity-rcb-pro-about'    => 'about',
	);

	public function __construct( $version ) {
		$this->version = $version;
		$this->stats   = new Infinity_RCB_Stats();
		$this->logger  = new Infinity_RCB_Logger();
		$this->license = new Infinity_RCB_License();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_post_actions' ) );
		add_action( 'admin_init', array( $this, 'handle_get_exports' ) );
		add_action( 'admin_init', array( $this, 'handle_receipt' ) );
		add_action( 'admin_init', array( $this, 'handle_wizard' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_link' ), 100 );
		add_action( 'wp_ajax_infinity_rcb_live', array( $this, 'ajax_live' ) );
		add_action( 'admin_footer-plugins.php', array( $this, 'plugin_row_icon' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		add_filter( 'plugin_action_links_' . INFINITY_RCB_BASENAME, array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Menu et liens
	 * ------------------------------------------------------------------- */

	public function register_menu() {
		add_menu_page(
			'Infinity RCB Pro',
			'Infinity RCB Pro',
			'manage_options',
			'infinity-rcb-pro',
			array( $this, 'render_dashboard' ),
			infinity_rcb_menu_icon(),
			30
		);

		add_submenu_page( 'infinity-rcb-pro', 'Tableau de bord', 'Tableau de bord', 'manage_options', 'infinity-rcb-pro', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'infinity-rcb-pro', 'Réglages', 'Réglages', 'manage_options', 'infinity-rcb-pro-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'infinity-rcb-pro', 'Licence & Achat', 'Licence & Achat', 'manage_options', 'infinity-rcb-pro-license', array( $this, 'render_license' ) );
		add_submenu_page( 'infinity-rcb-pro', 'Statistiques', 'Statistiques', 'manage_options', 'infinity-rcb-pro-stats', array( $this, 'render_stats' ) );
		add_submenu_page( 'infinity-rcb-pro', 'Journaux', 'Journaux', 'manage_options', 'infinity-rcb-pro-logs', array( $this, 'render_logs' ) );
		add_submenu_page( 'infinity-rcb-pro', 'À propos', 'À propos', 'manage_options', 'infinity-rcb-pro-about', array( $this, 'render_about' ) );
	}

	public function action_links( $links ) {
		$custom = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro' ) ) . '">📊 Tableau de bord</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings' ) ) . '">⚙️ Réglages</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ) . '">🔑 Licence</a>',
		);

		// Suppression directe : flux natif WordPress (nonce + confirmation
		// navigateur). Sur une extension active, WordPress affiche son écran
		// d'avertissement — aucune suppression accidentelle possible.
		$delete_url = wp_nonce_url(
			'plugins.php?action=delete-selected&checked[]=' . rawurlencode( INFINITY_RCB_BASENAME ) . '&plugin_status=all&paged=1&s=',
			'bulk-plugins'
		);
		$custom[] = '<a href="' . esc_url( $delete_url ) . '" style="color:#b32d2e;" onclick="return confirm(\'' . esc_js( 'Supprimer Right Click Blocker PRO ? Statistiques, journaux et réglages seront perdus (les licences activées restent valides).' ) . '\');">🗑️ Supprimer</a>';

		return array_merge( $custom, $links );
	}

	public function row_meta( $meta, $file ) {
		if ( INFINITY_RCB_BASENAME !== $file ) {
			return $meta;
		}

		$options  = infinity_rcb_options();
		$lic      = $this->license->status();
		$total    = count( Infinity_RCB_Stats::types() );
		$active   = 0;
		foreach ( $options['protections'] as $on ) {
			if ( $on ) { $active++; }
		}

		// Pastilles d'état (protection, licence, mises à jour).
		$meta[] = '<span style="display:inline-block;margin-top:2px;padding:2px 9px;border-radius:999px;font-weight:600;font-size:11px;'
			. ( ! empty( $options['master_enable'] )
				? 'background:#edfaef;color:#1c7c3a;'
				: 'background:#fbeaea;color:#b32d2e;' )
			. '">'
			. ( ! empty( $options['master_enable'] )
				? '🛡️ Protection active — ' . (int) $active . '/' . $total . ' protections'
				: '⛔ Protection désactivée' )
			. '</span>';

		$meta[] = 'active' === $lic['code']
			? '<span style="display:inline-block;padding:2px 9px;border-radius:999px;font-weight:600;font-size:11px;background:#f4f0ff;color:#6d28d9;">🔑 Licence ' . esc_html( $lic['plan_label'] ) . ' — active</span>'
			: '<span style="display:inline-block;padding:2px 9px;border-radius:999px;font-weight:600;font-size:11px;background:#fff8e8;color:#92600a;">💎 Licence à vie — dès 2 900 DA (1 site) · 4 800 DA (5 sites)</span>';

		$meta[] = '🔄 Mises à jour : WordPress.org' . ( class_exists( 'Infinity_RCB_Updater' ) && '' !== Infinity_RCB_Updater::repo() ? ' + GitHub' : '' );
		$meta[] = '<a href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-about' ) ) . '">Documentation &amp; support</a>';
		return $meta;
	}

	/* ---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------- */

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'infinity-rcb-pro' ) ) {
			return;
		}

		wp_enqueue_style( 'infinity-rcb-admin', INFINITY_RCB_URL . 'admin/css/infinity-rcb-admin.css', array(), $this->version, 'all' );
		wp_enqueue_script( 'infinity-rcb-admin', INFINITY_RCB_URL . 'admin/js/infinity-rcb-admin.js', array(), $this->version, true );

		$page = isset( $_GET['page'] ) && isset( $this->pages[ sanitize_key( wp_unslash( $_GET['page'] ) ) ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d'affichage (choix des assets).
			? $this->pages[ sanitize_key( wp_unslash( $_GET['page'] ) ) ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			: '';

		wp_localize_script( 'infinity-rcb-admin', 'RCBAdmin', array(
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'infinity_rcb_admin' ),
			'page'      => $page,
			'live'      => (bool) infinity_rcb_options()['master_enable'],
			'chart'     => $this->stats->chart( 'stats' === $page ? 30 : 14 ),
			'donut'     => $this->stats->donut(),
			'types'     => Infinity_RCB_Stats::types(),
			'i18n'      => array(
				'confirmReset' => 'Réinitialiser toutes les statistiques ? Cette action est irréversible.',
				'confirmClear' => 'Supprimer tous les fichiers de journal ? Cette action est irréversible.',
			),
		) );

		// La feuille publique est chargée sur les réglages (aperçu du message)
		// et sur le tableau de bord (carte « Aperçu du message »).
		if ( in_array( $page, array( 'settings', 'dashboard' ), true ) ) {
			wp_enqueue_style( 'infinity-rcb-public', INFINITY_RCB_URL . 'assets/css/infinity-rcb-public.css', array(), $this->version, 'all' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Actions POST (réglages, réinitialisation, purge)
	 * ------------------------------------------------------------------- */

	public function handle_post_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Nettoyage : l'option vendeur en base n'est plus utilisée (constante wp-config).
		if ( false !== get_option( Infinity_RCB_License::VENDOR, false ) ) {
			delete_option( Infinity_RCB_License::VENDOR );
		}

		if ( isset( $_POST['infinity_rcb_save_settings'] ) ) {
			check_admin_referer( 'infinity_rcb_settings', 'infinity_rcb_settings_nonce' );

			$input = isset( $_POST['infinity_rcb'] ) && is_array( $_POST['infinity_rcb'] ) ? wp_unslash( $_POST['infinity_rcb'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- assaini champ par champ par sanitize_settings().
			update_option( INFINITY_RCB_OPTION, $this->sanitize_settings( $input ), false );

			$tab = isset( $_POST['active_tab'] ) ? sanitize_key( $_POST['active_tab'] ) : 'general';
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=' . $tab . '&rcb-notice=saved' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_reset_stats'] ) ) {
			check_admin_referer( 'infinity_rcb_maintenance', 'infinity_rcb_maintenance_nonce' );
			$this->stats->reset();
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-stats&rcb-notice=stats-reset' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_clear_logs'] ) ) {
			check_admin_referer( 'infinity_rcb_maintenance', 'infinity_rcb_maintenance_nonce' );
			$this->logger->clear();
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-logs&rcb-notice=logs-cleared' ) );
			exit;
		}

		/* ----- Licence & Achat ----- */

		if ( isset( $_POST['infinity_rcb_activate_license'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			// Anti force-brute : 5 tentatives par heure et par IP.
			if ( ! Infinity_RCB_License::activation_allowed() ) {
				wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=license-ratelimit' ) );
				exit;
			}

			$key   = isset( $_POST['rcb_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_key'] ) ) : '';
			$email = isset( $_POST['rcb_email'] ) ? sanitize_email( wp_unslash( $_POST['rcb_email'] ) ) : '';
			$result = $this->license->activate( $key, $email );

			if ( true !== $result ) {
				Infinity_RCB_License::register_failed_activation();
			} else {
				Infinity_RCB_License::clear_failed_activations();
			}

			$map = array(
				true      => 'license-ok',
				'invalid' => 'license-invalid',
				'quota'   => 'license-quota',
				'crypto'  => 'vendor-crypto',
				'revoked' => 'license-revoked',
			);
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( isset( $map[ $result ] ) ? $map[ $result ] : 'license-invalid' ) ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_release_request'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$domain = isset( $_POST['rcb_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_domain'] ) ) : '';
			$result = $this->license->request_domain_release( $domain );

			$map = array(
				true      => 'release-sent',
				'unknown' => 'release-unknown',
				'self'    => 'release-self',
				'mailfail'=> 'release-mailfail',
			);
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( isset( $map[ $result ] ) ? $map[ $result ] : 'release-unknown' ) . '&rcb-domain=' . rawurlencode( $domain ) ) . '#rcb-licence' );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_release_confirm'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$domain = isset( $_POST['rcb_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_domain'] ) ) : '';
			$code   = isset( $_POST['rcb_code'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_code'] ) ) : '';
			$result = $this->license->confirm_domain_release( $domain, $code );

			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( true === $result ? 'release-done' : 'release-bad' ) ) . '#rcb-licence' );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_deactivate_license'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );
			$this->license->deactivate();
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=license-off' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_create_order'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$plan  = isset( $_POST['rcb_plan'] ) ? sanitize_key( wp_unslash( $_POST['rcb_plan'] ) ) : 'single';
			$buyer = isset( $_POST['rcb_buyer'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_buyer'] ) ) : '';
			$email = isset( $_POST['rcb_order_email'] ) ? sanitize_email( wp_unslash( $_POST['rcb_order_email'] ) ) : '';
			$note  = isset( $_POST['rcb_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rcb_note'] ) ) : '';

			if ( '' === $buyer || ! is_email( $email ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=order-invalid' ) );
				exit;
			}

			if ( isset( $_POST['rcb_promo'] ) ) {
				$promo = sanitize_text_field( wp_unslash( $_POST['rcb_promo'] ) );
			} else {
				$promo = '';
			}

			$order = $this->license->create_order( $plan, $buyer, $email, $note, $promo );

			// La demande part directement chez le vendeur + confirmation à l'acheteur.
			$to_seller = $this->license->email_order_to_seller( $order );
			$to_buyer  = $this->license->email_order_to_buyer( $order );

			$emailed = ( $to_seller && $to_buyer ) ? 'both' : ( ( $to_seller || $to_buyer ) ? 'partial' : 'none' );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=order-created&emailed=' . $emailed . '&ref=' . rawurlencode( $order['ref'] ) ) . '#rcb-commande' );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_resend_order'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$ref    = isset( $_POST['rcb_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_ref'] ) ) : '';
			$order  = $ref ? $this->license->get_order( $ref ) : null;

			if ( $order ) {
				$to_seller = $this->license->email_order_to_seller( $order );
				$to_buyer  = $this->license->email_order_to_buyer( $order );
				$emailed   = ( $to_seller && $to_buyer ) ? 'both' : ( ( $to_seller || $to_buyer ) ? 'partial' : 'none' );
				wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=order-resent&emailed=' . $emailed . '&ref=' . rawurlencode( $ref ) ) . '#rcb-commande' );
				exit;
			}
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=order-invalid' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_generate_key'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			if ( ! Infinity_RCB_License::vendor_enabled() ) {
				wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=vendor-needed' ) );
				exit;
			}

			$plan = isset( $_POST['rcb_key_plan'] ) ? sanitize_key( wp_unslash( $_POST['rcb_key_plan'] ) ) : 'single';
			$tiers = infinity_rcb_default_options()['license']['tiers'];
			if ( ! isset( $tiers[ $plan ] ) ) {
				$plan = 'single';
			}
			$key = Infinity_RCB_License::generate_key( $plan );

			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=key-created&rcb-key=' . rawurlencode( $key ) . '&rcb-plan=' . rawurlencode( $plan ) ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_mail_test'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$to = is_email( $options_test = get_option( 'admin_email' ) ) ? $options_test : $this->license->seller_mail_fallback();
			$ok = $this->license->send_test_mail( $to );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( $ok ? 'mailtest-ok' : 'mailtest-fail' ) ) . '#rcb-emails' );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_clear_maillog'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );
			$this->license->clear_mail_log();
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=maillog-cleared' ) . '#rcb-emails' );
			exit;
		}

		/* ----- Codes promo (vendeur) ----- */

		if ( isset( $_POST['infinity_rcb_promo_add'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$code     = isset( $_POST['rcb_promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_promo_code'] ) ) : '';
			$pct      = isset( $_POST['rcb_promo_pct'] ) ? (int) $_POST['rcb_promo_pct'] : 0;
			$expires  = isset( $_POST['rcb_promo_expires'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_promo_expires'] ) ) : '';
			$max_uses = isset( $_POST['rcb_promo_max'] ) ? (int) $_POST['rcb_promo_max'] : 0;

			$ok = $this->license->set_promo( $code, $pct, $expires, $max_uses );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( $ok ? 'promo-saved' : 'vendor-needed' ) ) . '#rcb-promos' );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_promo_delete'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );
			$code = isset( $_POST['rcb_promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_promo_code'] ) ) : '';
			$this->license->delete_promo( $code );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=promo-deleted' ) . '#rcb-promos' );
			exit;
		}

		/* ----- Bilan hebdo immédiat (vendeur) ----- */

		if ( isset( $_POST['infinity_rcb_digest_now'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );
			$ok = $this->license->send_weekly_digest( true );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . ( $ok ? 'digest-sent' : 'digest-off' ) ) . '#rcb-promos' );
			exit;
		}

		/* ----- Sauvegarde des réglages : import / réinitialisation ----- */

		if ( isset( $_POST['infinity_rcb_import_settings'] ) ) {
			check_admin_referer( 'infinity_rcb_tools', 'infinity_rcb_tools_nonce' );

			$raw = '';
			if ( isset( $_FILES['rcb_settings_file']['tmp_name'] ) && is_uploaded_file( $_FILES['rcb_settings_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- fichier téléversé lu localement puis validé JSON.
				$raw = (string) file_get_contents( $_FILES['rcb_settings_file']['tmp_name'] );
			}
			$import = is_string( $raw ) ? json_decode( $raw, true ) : null;

			if ( ! is_array( $import ) || ! isset( $import['master_enable'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-settings&rcb-notice=import-invalid' ) );
				exit;
			}
			update_option( INFINITY_RCB_OPTION, $this->sanitize_settings( $import ), false );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-settings&rcb-notice=import-ok' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_reset_settings'] ) ) {
			check_admin_referer( 'infinity_rcb_tools', 'infinity_rcb_tools_nonce' );
			update_option( INFINITY_RCB_OPTION, infinity_rcb_default_options(), false );
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-settings&rcb-notice=reset-done' ) );
			exit;
		}

		if ( isset( $_POST['infinity_rcb_order_action'] ) ) {
			check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

			$ref = isset( $_POST['rcb_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_ref'] ) ) : '';
			$do  = isset( $_POST['rcb_do'] ) ? sanitize_key( wp_unslash( $_POST['rcb_do'] ) ) : '';

			$notice = 'order-updated';
			if ( $ref ) {
				if ( 'fulfill' === $do ) {
					$result = $this->license->fulfill_order( $ref );
					$notice = ( is_array( $result ) && ! empty( $result['key'] ) ) ? 'order-fulfilled' : 'vendor-needed';
				} elseif ( 'declare' === $do ) {
					$result = $this->license->declare_payment( $ref );
					$notice = true === $result ? 'order-declared' : 'order-declare-err';
				} elseif ( 'resend' === $do ) {
					$result = $this->license->resend_key_to_buyer( $ref );
					$notice = true === $result ? 'key-resent' : ( 'vendor' === $result ? 'vendor-needed' : 'order-nokey' );
				} elseif ( 'delete' === $do ) {
					$this->license->delete_order( $ref );
					$notice = 'order-deleted';
				}
			}
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-notice=' . $notice . ( $ref ? '&ref=' . rawurlencode( $ref ) : '' ) ) . '#rcb-commandes' );
			exit;
		}
	}

	private function sanitize_settings( $input ) {
		$defaults = infinity_rcb_default_options();
		$types    = array_keys( Infinity_RCB_Stats::types() );
		$out      = $defaults;

		$out['master_enable'] = ! empty( $input['master_enable'] );

		foreach ( $types as $type ) {
			$out['protections'][ $type ] = ! empty( $input['protections'][ $type ] );
			if ( isset( $input['messages'][ $type ] ) ) {
				$out['messages'][ $type ] = sanitize_text_field( $input['messages'][ $type ] );
			}
		}

		// Apparence.
		$styles    = array_keys( infinity_rcb_message_styles() );
		$style     = isset( $input['appearance']['style'] ) ? sanitize_key( $input['appearance']['style'] ) : 'st1';
		$position  = isset( $input['appearance']['position'] ) ? sanitize_key( $input['appearance']['position'] ) : 'top';
		$custom_on = ! empty( $input['appearance']['custom_on'] );
		$defaults  = infinity_rcb_default_options();
		$out['appearance'] = array(
			'style'            => in_array( $style, $styles, true ) ? $style : 'st1',
			'position'         => in_array( $position, array( 'top', 'center', 'bottom' ), true ) ? $position : 'top',
			'duration'         => min( 10000, max( 500, absint( $input['appearance']['duration'] ?? 2600 ) ) ),
			'custom_bg'        => $custom_on ? infinity_rcb_sanitize_hex( $input['appearance']['custom_bg'] ?? '' ) : '',
			'custom_text'      => $custom_on ? infinity_rcb_sanitize_hex( $input['appearance']['custom_text'] ?? '' ) : '',
			'show_icon'        => ! empty( $input['appearance']['show_icon'] ),
			'sound'            => ! empty( $input['appearance']['sound'] ),
			'copyright_enable' => ! empty( $input['appearance']['copyright_enable'] ),
			'copyright_text'   => sanitize_text_field( $input['appearance']['copyright_text'] ?? $defaults['appearance']['copyright_text'] ),
			'show_close'       => ! empty( $input['appearance']['show_close'] ),
			'show_progress'    => ! empty( $input['appearance']['show_progress'] ),
			'radius'           => min( 24, max( 0, absint( $input['appearance']['radius'] ?? 12 ) ) ),
			'font_size'        => min( 22, max( 11, absint( $input['appearance']['font_size'] ?? 15 ) ) ),
			'shadow'           => ! empty( $input['appearance']['shadow'] ),
			'custom_css'       => wp_strip_all_tags( (string) ( $input['appearance']['custom_css'] ?? '' ) ),
			'wm_text'          => sanitize_text_field( $input['appearance']['wm_text'] ?? $defaults['appearance']['wm_text'] ),
		);

		// Avancé.
		$exclude_roles = array();
		if ( isset( $input['advanced']['exclude_roles'] ) && is_array( $input['advanced']['exclude_roles'] ) ) {
			foreach ( $input['advanced']['exclude_roles'] as $role ) {
				$role = sanitize_key( (string) $role );
				if ( '' !== $role && 'administrator' !== $role ) {
					$exclude_roles[] = $role;
				}
			}
		}
		$chargily_mode = isset( $input['payment']['chargily_mode'] ) ? sanitize_key( $input['payment']['chargily_mode'] ) : 'test';
		$out['advanced'] = array(
			'interval'        => min( 5000, max( 200, absint( $input['advanced']['interval'] ?? 800 ) ) ),
			'blur'            => ! empty( $input['advanced']['blur'] ),
			'redirect'        => esc_url_raw( $input['advanced']['redirect'] ?? '' ),
			'exclude_pages'   => sanitize_textarea_field( $input['advanced']['exclude_pages'] ?? '' ),
			'exclude_admins'  => ! empty( $input['advanced']['exclude_admins'] ),
			'exclude_roles'   => $exclude_roles,
			'tracking'        => ! empty( $input['advanced']['tracking'] ),
			'touch_guard'     => ! empty( $input['advanced']['touch_guard'] ),
			'image_pointer'   => ! empty( $input['advanced']['image_pointer'] ),
			'noscript_warn'   => ! empty( $input['advanced']['noscript_warn'] ),
			'watermark'       => ! empty( $input['advanced']['watermark'] ),
			'xfo'             => ! empty( $input['advanced']['xfo'] ),
			'frame_bust'      => ! empty( $input['advanced']['frame_bust'] ),
			'alert_threshold' => min( 100000, max( 0, absint( $input['advanced']['alert_threshold'] ?? 0 ) ) ),
		);

		// Paiement (coordonnées du vendeur).
		$current = infinity_rcb_options();
		$out['payment'] = array(
			'baridimob_rip' => sanitize_text_field( $input['payment']['baridimob_rip'] ?? '' ),
			'ccp'           => sanitize_text_field( $input['payment']['ccp'] ?? '' ),
			'rib'           => sanitize_text_field( $input['payment']['rib'] ?? '' ),
			'paypal_email'  => sanitize_email( $input['payment']['paypal_email'] ?? '' ),
			'paypal_me'     => sanitize_text_field( $input['payment']['paypal_me'] ?? '' ),
			'instructions'  => sanitize_textarea_field( $input['payment']['instructions'] ?? '' ),
			'whatsapp'      => preg_replace( '/[^0-9+]/', '', (string) ( $input['payment']['whatsapp'] ?? '' ) ),
			'digest'        => ! empty( $input['payment']['digest'] ),
			'chargily_mode' => in_array( $chargily_mode, array( 'test', 'live' ), true ) ? $chargily_mode : 'test',
			// Clé secrète : conservée si le champ est renvoyé vide (type password).
			'chargily_key'  => '' !== trim( (string) ( $input['payment']['chargily_key'] ?? '' ) )
				? sanitize_text_field( (string) $input['payment']['chargily_key'] )
				: (string) ( $current['payment']['chargily_key'] ?? '' ),
		);

		// Journalisation.
		$level = isset( $input['logging']['level'] ) ? sanitize_key( $input['logging']['level'] ) : 'warning';
		$out['logging'] = array(
			'enabled'        => ! empty( $input['logging']['enabled'] ),
			'level'          => in_array( $level, array( 'debug', 'info', 'warning', 'error' ), true ) ? $level : 'warning',
			'retention_days' => min( 365, max( 1, absint( $input['logging']['retention_days'] ?? 30 ) ) ),
			'max_entries'    => min( 50000, max( 100, absint( $input['logging']['max_entries'] ?? 5000 ) ) ),
		);

		// Licence (prix et paliers fixés par défaut, plan et acheteur modifiables).
		// Les codes promo et révocations sont gérés séparément (page Licence,
		// site vendeur) : une sauvegarde des réglages ne doit pas les écraser.
		$plan = isset( $input['license']['plan'] ) ? sanitize_key( $input['license']['plan'] ) : 'single';
		$out['license'] = array(
			'plan'          => array_key_exists( $plan, $defaults['license']['tiers'] ) ? $plan : 'single',
			'license_key'   => sanitize_text_field( $input['license']['license_key'] ?? '' ),
			'buyer'         => sanitize_text_field( $input['license']['buyer'] ?? '' ),
			'purchase_date' => sanitize_text_field( $input['license']['purchase_date'] ?? '' ),
			'tiers'         => $defaults['license']['tiers'],
			'promo'         => is_array( $current['license']['promo'] ?? null ) ? $current['license']['promo'] : array(),
			'revoked'       => is_array( $current['license']['revoked'] ?? null ) ? $current['license']['revoked'] : array(),
		);

		// Développeur.
		$out['developer'] = array(
			'name'    => sanitize_text_field( $input['developer']['name'] ?? $defaults['developer']['name'] ),
			'website' => esc_url_raw( $input['developer']['website'] ?? $defaults['developer']['website'] ),
			'email'   => sanitize_email( $input['developer']['email'] ?? $defaults['developer']['email'] ),
			'phone'   => sanitize_text_field( $input['developer']['phone'] ?? '' ),
			'address' => sanitize_textarea_field( $input['developer']['address'] ?? '' ),
		);

		// Mises à jour (dépôt GitHub — wp.org reste prioritaire automatiquement).
		$github_repo = trim( (string) ( $input['updates']['github_repo'] ?? '' ) );
		$out['updates'] = array(
			'github_enabled' => ! empty( $input['updates']['github_enabled'] ),
			'github_repo'    => preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $github_repo ) ? $github_repo : '',
		);

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Exports GET (CSV)
	 * ------------------------------------------------------------------- */

	public function handle_get_exports() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// « Vérifier maintenant » (carte Mises à jour) : lien signé en GET —
		// un formulaire imbriqué fermerait le formulaire des réglages.
		if ( isset( $_GET['rcb_check_updates'] ) ) {
			check_admin_referer( 'infinity_rcb_export', 'rcb_nonce' );
			$notice = 'updates-wporg'; // Aucun dépôt configuré : état normal.
			if ( class_exists( 'Infinity_RCB_Updater' ) && '' !== Infinity_RCB_Updater::repo() ) {
				$release = Infinity_RCB_Updater::refresh();
				if ( null === $release ) {
					$notice = 'updates-none';
				} elseif ( version_compare( INFINITY_RCB_VERSION, $release['version'], '<' ) ) {
					$notice = 'updates-available';
				} else {
					$notice = 'updates-checked';
				}
			}
			wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=general&rcb-notice=' . $notice ) . '#rcb-updates' );
			exit;
		}

		if ( ! isset( $_GET['rcb_export'] ) ) {
			return;
		}
		check_admin_referer( 'infinity_rcb_export', 'rcb_nonce' );

		$what = sanitize_key( wp_unslash( $_GET['rcb_export'] ) );

		if ( 'logs' === $what ) {
			$this->logger->export_csv( 2000 );
			exit;
		}

		if ( 'orders' === $what ) {
			$this->license->export_orders_csv();
			exit;
		}

		if ( 'stats' === $what ) {
			$this->export_stats_csv();
			exit;
		}

		if ( 'settings' === $what ) {
			$json = wp_json_encode( array(
				'plugin'  => 'infinity-rcb-pro',
				'version' => INFINITY_RCB_VERSION,
				'exported'=> current_time( 'mysql' ),
				'options' => infinity_rcb_options(),
			), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="infinity-rcb-reglages-' . date_i18n( 'Ymd-His' ) . '.json"' );
			header( 'Content-Length: ' . strlen( $json ) );
			printf( '%s', $json );
			exit;
		}
	}

	/* ---------------------------------------------------------------------
	 * Widget du tableau de bord WordPress : mini-stats sans ouvrir le plugin
	 * ------------------------------------------------------------------- */

	public function register_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'infinity_rcb_pro_widget', '🛡️ Infinity RCB Pro', array( $this, 'render_dashboard_widget' ) );
	}

	public function render_dashboard_widget() {
		$options = infinity_rcb_options();
		$s       = $this->stats->summary();
		$lic     = $this->license->status();
		$active_protections = 0;
		foreach ( $options['protections'] as $on ) {
			if ( $on ) { $active_protections++; }
		}
		$types = Infinity_RCB_Stats::types();
		$top   = array();
		arsort( $s['by_type'] );
		foreach ( array_slice( $s['by_type'], 0, 3, true ) as $type => $n ) {
			if ( (int) $n > 0 ) {
				$top[] = esc_html( $types[ $type ]['emoji'] . ' ' . $types[ $type ]['label'] ) . ' : <strong>' . (int) $n . '</strong>';
			}
		}

		echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px;">';
		foreach ( array(
			array( (int) $s['today'], "Aujourd'hui" ),
			array( (int) $s['week'], '7 jours' ),
			array( (int) $s['total'], 'Total' ),
		) as $kpi ) {
			echo '<div style="background:' . ( $options['master_enable'] ? '#F0F6FF' : '#FEF2F2' ) . ';border-radius:10px;padding:10px;text-align:center;">'
				. '<div style="font-size:20px;font-weight:700;color:#0B0F1E;">' . (int) $kpi[0] . '</div>'
				. '<div style="font-size:11px;color:#64748B;">' . esc_html( $kpi[1] ) . '</div></div>';
		}
		echo '</div>';

		echo '<p style="margin:0 0 6px;">'
			. ( $options['master_enable']
				? '<span style="display:inline-block;background:#DCFCE7;color:#15803D;font-weight:600;font-size:11px;padding:3px 10px;border-radius:999px;">● Protection active — ' . (int) $active_protections . '/11</span>'
				: '<span style="display:inline-block;background:#FEE2E2;color:#B91C1C;font-weight:600;font-size:11px;padding:3px 10px;border-radius:999px;">● Protection désactivée</span>' )
			. ' <span style="display:inline-block;background:#EDEBFF;color:#6D28D9;font-weight:600;font-size:11px;padding:3px 10px;border-radius:999px;">Licence : ' . esc_html( $lic['label'] ) . '</span>'
			. '</p>';

		if ( $top ) {
			echo '<p style="margin:0 0 10px;font-size:12px;color:#334155;">Tentatives bloquées — ' . implode( ' · ', $top ) . '</p>';
		}

		echo '<p style="margin:0;"><a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro' ) ) . '">Tableau de bord</a> '
			. '<a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-stats' ) ) . '">Statistiques</a> '
			. '<a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings' ) ) . '">Réglages</a></p>';
	}

	/**
	 * Reçu de commande imprimable (vue autonome).
	 */
	public function handle_receipt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['rcb-recu'] ) ) {
			return;
		}
		check_admin_referer( 'infinity_rcb_export', 'rcb_nonce' );

		$ref   = sanitize_text_field( wp_unslash( $_GET['rcb-recu'] ) );
		$order = $this->license->get_order( $ref );
		if ( ! $order ) {
			wp_die( 'Commande inconnue.' );
		}

		$order = wp_parse_args( $order, array(
			'ref' => '', 'plan' => 'single', 'buyer' => '', 'email' => '',
			'amount_da' => 0, 'amount_eur' => 0.0, 'promo_code' => '', 'discount_pct' => 0, 'created' => '',
		) );
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		$label = isset( $tiers[ $order['plan'] ]['label'] ) ? $tiers[ $order['plan'] ]['label'] : $order['plan'];
		$opts  = infinity_rcb_options();
		$pay   = $opts['payment'];
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Reçu <?php echo esc_html( $order['ref'] ); ?> — Infinity RCB Pro</title>
<style>
	body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1e293b;background:#f1f5f9;margin:0;padding:24px}
	.sheet{max-width:680px;margin:0 auto;background:#fff;border-radius:16px;padding:36px 40px;box-shadow:0 10px 30px -12px rgba(15,23,42,.18)}
	.head{display:flex;align-items:center;gap:14px;border-bottom:3px solid #1E6FF0;padding-bottom:16px;margin-bottom:20px}
	.head h1{margin:0;font-size:19px;color:#0f172a}
	.head .site{color:#64748b;font-size:12.5px}
	.badge{margin-left:auto;background:#EDEBFF;color:#6D28D9;font-weight:800;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:6px 12px;border-radius:999px}
	table{width:100%;border-collapse:collapse}
	th,td{padding:9px 4px;border-bottom:1px dashed #e2e8f0;text-align:left;font-size:13.5px;vertical-align:top}
	th{color:#64748b;font-weight:600;width:38%}
	.total{font-size:20px;font-weight:800;color:#0f172a}
	.promo{color:#b45309;font-weight:700;font-size:12.5px}
	.pay{margin-top:18px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;font-size:13px}
	.pay strong{display:block;margin-bottom:6px;color:#92400e}
	.foot{margin-top:24px;color:#94a3b8;font-size:11.5px;text-align:center}
	.bar{margin:22px auto 8px;display:block;padding:11px 22px;border:0;border-radius:10px;background:linear-gradient(135deg,#1E6FF0,#7C3AED);color:#fff;font-weight:700;font-size:14px;cursor:pointer}
	@media print{body{background:#fff;padding:0}.sheet{box-shadow:none;border-radius:0}.bar{display:none}}
</style>
</head>
<body onload="window.print()">
<div class="sheet">
	<div class="head">
		<?php printf( '%s', infinity_rcb_shield_svg( 'regular' ) ) ?>
		<div>
			<h1>Reçu de commande — <?php echo esc_html( $order['ref'] ); ?></h1>
			<div class="site"><?php echo esc_html( $opts['developer']['name'] ); ?> · <?php echo esc_html( $domain ); ?> · <?php echo esc_html( $order['created'] ); ?></div>
		</div>
		<span class="badge">Licence à vie</span>
	</div>
	<table>
		<tr><th>Acheteur</th><td><strong><?php echo esc_html( $order['buyer'] ); ?></strong><br><?php echo esc_html( $order['email'] ); ?></td></tr>
		<tr><th>Produit</th><td><?php echo esc_html( $label ); ?> (<?php echo (int) $tiers[ $order['plan'] ]['domains']; ?> domaine<?php echo $tiers[ $order['plan'] ]['domains'] > 1 ? 's' : ''; ?>)</td></tr>
		<?php if ( (int) $order['discount_pct'] > 0 ) : ?>
		<tr><th>Code promo</th><td class="promo"><?php echo esc_html( $order['promo_code'] ); ?> : −<?php echo (int) $order['discount_pct']; ?> %</td></tr>
		<?php endif; ?>
		<tr><th>Montant à régler</th><td class="total"><?php echo number_format( (float) $order['amount_da'], 0, ',', ' ' ); ?> DA <span style="font-size:13px;color:#64748b;">(≈ <?php echo esc_html( number_format( (float) $order['amount_eur'], 2, ',', ' ' ) ); ?> €)</span></td></tr>
	</table>
	<?php if ( '' !== trim( (string) $pay['baridimob_rip'] ) || '' !== trim( (string) $pay['ccp'] ) || '' !== trim( (string) $pay['rib'] ) ) : ?>
	<div class="pay">
		<strong>Coordonnées de paiement</strong>
		<?php if ( '' !== trim( (string) $pay['baridimob_rip'] ) ) : ?>📱 BaridiMob (RIP) : <?php echo esc_html( $pay['baridimob_rip'] ); ?><br><?php endif; ?>
		<?php if ( '' !== trim( (string) $pay['ccp'] ) ) : ?>📮 CCP / Edahabia : <?php echo esc_html( $pay['ccp'] ); ?><br><?php endif; ?>
		<?php if ( '' !== trim( (string) $pay['rib'] ) ) : ?>🏦 Virement (RIB) : <?php echo esc_html( $pay['rib'] ); ?><?php endif; ?>
	</div>
	<?php endif; ?>
	<p style="font-size:12px;color:#64748b;margin-top:16px;">Indiquez la référence <strong><?php echo esc_html( $order['ref'] ); ?></strong> dans le libellé du paiement. La clé de licence est envoyée par e-mail après validation.</p>
	<div class="foot">Infinity RCB Pro — Reçu généré le <?php echo esc_html( date_i18n( 'd/m/Y H:i' ) ); ?> · Ce document ne constitue pas une facture fiscale.</div>
	<button class="bar" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button>
</div>
</body>
</html>
		<?php
		exit;
	}

	private function export_stats_csv() {
		$summary = $this->stats->summary();
		$types   = Infinity_RCB_Stats::types();
		$chart   = $this->stats->chart( 60 );

		$csv  = "\xEF\xBB\xBF";
		$csv .= '"Synthèse générale"' . "\r\n";
		$csv .= '"Total tentatives";"' . (int) $summary['total'] . '"' . "\r\n";
		$csv .= '"Aujourd’hui";"' . (int) $summary['today'] . '"' . "\r\n";
		$csv .= '"7 derniers jours";"' . (int) $summary['week'] . '"' . "\r\n";
		$csv .= '"30 derniers jours";"' . (int) $summary['month'] . '"' . "\r\n";
		$csv .= '"IP uniques";"' . (int) $summary['unique'] . '"' . "\r\n";
		$csv .= '"Suivi depuis";"' . $summary['since'] . '"' . "\r\n";
		$csv .= "\r\n\"Répartition par type\"\r\n";
		foreach ( $types as $type => $meta ) {
			$csv .= '"' . $meta['label'] . '";"' . (int) ( $summary['by_type'][ $type ] ?? 0 ) . '"' . "\r\n";
		}
		$csv .= "\r\n\"Activité quotidienne (60 jours)\"\r\n";
		$csv .= '"Date";"Tentatives"' . "\r\n";
		foreach ( $chart as $day ) {
			$csv .= '"' . $day['date'] . '";"' . (int) $day['total'] . '"' . "\r\n";
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="infinity-rcb-statistiques.csv"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		exit;
	}

	/* ---------------------------------------------------------------------
	 * AJAX temps réel (tableau de bord)
	 * ------------------------------------------------------------------- */

	public function ajax_live() {
		check_ajax_referer( 'infinity_rcb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permissions insuffisantes.' ), 403 );
		}

		wp_send_json_success( array(
			'summary' => $this->stats->summary(),
			'chart'   => $this->stats->chart( 14 ),
			'donut'   => $this->stats->donut(),
		) );
	}

	/**
	 * Logo du plugin dans la liste des extensions (les icônes natives ne
	 * concernent que les plugins hébergés sur WordPress.org).
	 */
	public function plugin_row_icon() {
		$icon_url = esc_url( INFINITY_RCB_URL . 'assets/icon.svg' );
		$slug = sanitize_key( dirname( INFINITY_RCB_BASENAME ) );
		?>
		<script>
		(function () {
			var want = '<?php echo esc_js( $slug ); ?>';
			var icon = '<?php printf( '%s', $icon_url ); ?>';
			var rows = document.querySelectorAll('tr[data-slug]');
			for (var i = 0; i < rows.length; i++) {
				var slug = (rows[i].getAttribute('data-slug') || '').replace(/[^a-z0-9\-]/g, '');
				if (slug.indexOf(want) === -1) { continue; }

				// Logo du plugin dans la cellule d'icône.
				var icons = rows[i].querySelectorAll('.plugin-icon');
				for (var j = 0; j < icons.length; j++) {
					icons[j].innerHTML = '<img src="' + icon + '" alt="Right Click Blocker PRO" width="64" height="64" style="width:64px;height:64px;border-radius:10px">';
				}

				// « PRO » en badge bleu dégradé dans le titre.
				var title = rows[i].querySelector('.plugin-title strong');
				if (title && title.getAttribute('data-rcb-pro') !== '1' && title.textContent.indexOf('PRO') !== -1) {
					title.setAttribute('data-rcb-pro', '1');
					title.innerHTML = title.textContent.replace('PRO', '<span style="display:inline-block;vertical-align:1px;margin:0 2px;padding:2px 8px;border-radius:6px;background:linear-gradient(135deg,#1E6FF0,#7C3AED);color:#fff;font-size:10.5px;font-weight:800;letter-spacing:1.5px;box-shadow:0 2px 6px -2px rgba(30,111,240,.55);">PRO</span>');
				}
			}
		})();
		</script>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Assistant de premier démarrage + barre d'administration
	 * --------------------------------------------------------------------- */

	public function handle_wizard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['infinity_rcb_wizard_done'] ) ) {
			return;
		}
		check_admin_referer( 'infinity_rcb_license', 'infinity_rcb_license_nonce' );

		$options = infinity_rcb_options();
		$options['wizard_done'] = true;
		update_option( INFINITY_RCB_OPTION, $options, false );
		wp_safe_redirect( admin_url( 'admin.php?page=infinity-rcb-pro&rcb-notice=wizard-done' ) );
		exit;
	}

	public function admin_bar_link( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_node( array(
			'id'    => 'infinity-rcb-pro',
			'title' => '🛡️ Infinity RCB Pro',
			'href'  => admin_url( 'admin.php?page=infinity-rcb-pro' ),
			'meta'  => array( 'title' => 'Right Click Blocker PRO — Tableau de bord' ),
		) );
		$wp_admin_bar->add_node( array(
			'id'     => 'infinity-rcb-license',
			'parent' => 'infinity-rcb-pro',
			'title'  => '🔑 Licence & Achat',
			'href'   => admin_url( 'admin.php?page=infinity-rcb-pro-license' ),
		) );
		$wp_admin_bar->add_node( array(
			'id'     => 'infinity-rcb-stats',
			'parent' => 'infinity-rcb-pro',
			'title'  => '📊 Statistiques',
			'href'   => admin_url( 'admin.php?page=infinity-rcb-pro-stats' ),
		) );
	}

	/* ---------------------------------------------------------------------
	 * Rendu des pages
	 * ------------------------------------------------------------------- */

	public function render_dashboard() {
		$this->render_page( 'dashboard-page' );
	}

	public function render_settings() {
		$this->render_page( 'settings-page' );
	}

	public function render_license() {
		$this->render_page( 'license-page' );
	}

	public function render_stats() {
		$this->render_page( 'stats-page' );
	}

	public function render_logs() {
		$this->render_page( 'logs-page' );
	}

	public function render_about() {
		$this->render_page( 'about-page' );
	}

	private function render_page( $partial ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options     = infinity_rcb_options();
		$types       = Infinity_RCB_Stats::types();
		$summary     = $this->stats->summary();
		$log_stats   = $this->logger->stats();
		$log_rows    = $this->logger->rows( 300 );
		$top_ips     = $this->stats->top_ips( 10 );
		$styles      = infinity_rcb_message_styles();
		$notice      = isset( $_GET['rcb-notice'] ) ? sanitize_key( wp_unslash( $_GET['rcb-notice'] ) ) : '';
		$cron_next   = wp_next_scheduled( INFINITY_RCB_CRON );
		$lic_status  = $this->license->status();
		$orders      = $this->license->get_orders();
		$order_ref   = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		$last_order  = $order_ref ? $this->license->get_order( $order_ref ) : null;
		$emailed     = isset( $_GET['emailed'] ) ? sanitize_key( wp_unslash( $_GET['emailed'] ) ) : '';
		$gen_key     = isset( $_GET['rcb-key'] ) ? sanitize_text_field( wp_unslash( $_GET['rcb-key'] ) ) : '';
		$gen_plan    = isset( $_GET['rcb-plan'] ) ? sanitize_key( wp_unslash( $_GET['rcb-plan'] ) ) : '';
		if ( $gen_key && ! preg_match( '/^RCB-[SF]-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{4}$/', $gen_key ) ) {
			$gen_key = '';
		}

		include INFINITY_RCB_DIR . 'admin/partials/' . $partial . '.php';
	}
}
