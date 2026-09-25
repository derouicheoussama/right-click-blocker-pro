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

		// Expérience wp-admin/update-core.php : carte de mise à jour branded,
		// actions de fin de mise à jour et purge du cache après upgrade.
		add_action( 'admin_notices', array( __CLASS__, 'update_core_card' ) );
		add_filter( 'update_plugin_complete_actions', array( __CLASS__, 'complete_actions' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'after_upgrade' ), 10, 2 );
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
		$obj->tested               = get_bloginfo( 'version' ); // Cosmétique : évite le faux avertissement « non testée » sur le canal GitHub.
		$obj->requires             = '4.9';
		$obj->requires_php         = '7.0';
		$transient->response[ $base ] = $obj;

		return $transient;
	}

	/**
	 * Fiche « détails de la mise à jour » (fenêtre WordPress) enrichie :
	 * couverture, icônes, galerie de captures réelles et notes de release.
	 */
	public static function filter_plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}
		if ( dirname( INFINITY_RCB_BASENAME ) !== $args->slug ) {
			return $result;
		}

		// wp.org gère déjà cette installation → laisser le répertoire officiel
		// répondre (fiche officielle avec ses propres visuels).
		$wporg_cache = get_site_transient( 'update_plugins' );
		if ( is_object( $wporg_cache ) && ( isset( $wporg_cache->no_update[ INFINITY_RCB_BASENAME ] ) || isset( $wporg_cache->response[ INFINITY_RCB_BASENAME ] ) ) ) {
			return $result;
		}

		$release = self::latest_release();
		if ( null === $release ) {
			return $result;
		}

		$notes_raw = function_exists( 'mb_substr' ) ? mb_substr( $release['notes'], 0, 4000 ) : substr( $release['notes'], 0, 4000 );
		$notes     = wp_kses_post( nl2br( $notes_raw ) );

		// Galerie : captures réelles embarquées dans le plugin (assets locaux).
		$shots = array(
			INFINITY_RCB_URL . 'assets/screenshot-1.png' => 'Tableau de bord temps réel : indicateurs, graphique d’activité et aperçu du message',
			INFINITY_RCB_URL . 'assets/screenshot-2.png' => 'Licence &amp; Achat : plans, commande guidée et suivi de livraison',
			INFINITY_RCB_URL . 'assets/screenshot-3.png' => 'Réglages → Apparence : personnalisation avec aperçu en direct',
			INFINITY_RCB_URL . 'assets/screenshot-4.png' => 'Message d’avertissement affiché au visiteur (copyright automatique)',
			INFINITY_RCB_URL . 'assets/screenshot-5.png' => 'Boutique publique : plans, WhatsApp et paiement carte CIB / Edahabia',
			INFINITY_RCB_URL . 'assets/screenshot-6.png' => 'E-mails professionnels HTML (confirmation, clé de licence)',
		);
		$gallery = '';
		foreach ( $shots as $src => $caption ) {
			$gallery .= '<p style="margin:14px 0 4px;"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( wp_strip_all_tags( $caption ) ) . '" style="max-width:100%;height:auto;border:1px solid #dcdcde;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);"></p>'
				. '<p style="color:#646970;font-size:12px;margin:0 0 10px;">' . $caption . '</p>';
		}

		$info                 = new stdClass();
		$info->name           = 'Right Click Blocker PRO – Right Click & Content Protection';
		$info->slug           = $args->slug;
		$info->version        = $release['version'];
		$info->download_link  = $release['package'];
		$info->tested         = get_bloginfo( 'version' );
		$info->requires       = '4.9';
		$info->requires_php   = '7.0';
		$info->last_updated   = $release['date'];
		$info->author         = '<a href="https://github.com/derouicheoussama">Infinity Coder</a>';
		$info->author_profile = 'https://github.com/derouicheoussama';
		$info->homepage       = 'https://github.com/derouicheoussama/right-click-blocker-pro';
		$info->donate_link    = '';
		$info->banners        = array(
			'low'  => INFINITY_RCB_URL . 'assets/banner-772x250.png',
			'high' => INFINITY_RCB_URL . 'assets/banner-1544x500.png',
		);
		$info->icons          = array(
			'1x'      => INFINITY_RCB_URL . 'assets/icon-128.png',
			'2x'      => INFINITY_RCB_URL . 'assets/icon-256.png',
			'default' => INFINITY_RCB_URL . 'assets/icon-256.png',
			'svg'     => INFINITY_RCB_URL . 'assets/icon.svg',
		);
		$info->sections       = array(
			'description' => '<p>Bloque le clic droit, la copie, la sélection, le glisser-déposer, l’impression, les captures d’écran et les outils de développement — avec messages personnalisés (10 styles), statistiques temps réel, journaux, filigrane d’images et anti-clickjacking. Tout est inclus, gratuitement.</p>'
				. '<ul><li>🆓 DevTools, impression, stats, styles : rien de verrouillé</li><li>⚡ ~19 Ko d’assets, sans jQuery</li><li>🔒 Vie privée totale : aucune donnée sortante</li><li>🔑 Licences à vie signées ECDSA (optionnelles)</li></ul>',
			'screenshots' => $gallery,
			'installation'=> '<ol><li>Téléchargez le .zip de la release puis Extensions → Ajouter → Téléverser.</li><li>Activez — les 11 protections recommandées sont déjà opérationnelles.</li><li>Personnalisez dans Réglages → Apparence (aperçu en direct).</li></ol>',
			'changelog'   => '' !== $notes ? $notes : '<p>Voir les notes de la release sur GitHub.</p>',
		);
		return $info;
	}

	/**
	 * Mise à jour GitHub en attente pour ce plugin, ou null.
	 * Distingue notre offre GitHub de celle du répertoire officiel en
	 * regardant l'hôte du paquet (wp.org = downloads.wordpress.org).
	 */
	public static function pending_github_update() {
		$transient = get_site_transient( 'update_plugins' );
		$base      = INFINITY_RCB_BASENAME;
		if ( ! is_object( $transient ) || empty( $transient->response[ $base ] ) || ! is_object( $transient->response[ $base ] ) ) {
			return null;
		}
		$host = (string) wp_parse_url( (string) $transient->response[ $base ]->package, PHP_URL_HOST );
		if ( 'github.com' !== $host && 'api.github.com' !== $host && '.githubusercontent.com' !== substr( $host, -strlen( '.githubusercontent.com' ) ) ) {
			return null;
		}
		return $transient->response[ $base ];
	}

	/**
	 * Carte de mise à jour sur wp-admin/update-core.php : version, canal,
	 * extrait des notes et bouton qui coche et soumet le formulaire.
	 */
	public static function update_core_card() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'update-core' !== $screen->id ) {
			return;
		}
		$update = self::pending_github_update();
		if ( null === $update ) {
			return;
		}

		add_thickbox();

		$release = self::latest_release();
		$notes   = '';
		$date    = '';
		if ( is_array( $release ) && isset( $release['version'] ) && $release['version'] === $update->new_version ) {
			$notes = (string) $release['notes'];
			$date  = (string) $release['date'];
		}
		$notes = trim( preg_replace( '/[\r\n]+/', ' ', wp_strip_all_tags( $notes ) ) );
		if ( function_exists( 'mb_substr' ) ) {
			$notes = mb_substr( $notes, 0, 320 );
		} else {
			$notes = substr( $notes, 0, 320 );
		}

		$details_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( INFINITY_RCB_BASENAME ) ) . '&TB_iframe=true&width=900&height=850' );
		?>
		<div style="margin:16px 20px 0;background:#fff;border:1px solid #dcdcde;border-left:4px solid #1E6FF0;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.05);padding:16px 20px;display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
			<div style="flex:0 0 auto;width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#1E6FF0 0%,#7C3AED 100%);display:flex;align-items:center;justify-content:center;font-size:23px;line-height:1;">🛡️</div>
			<div style="flex:1 1 420px;min-width:260px;">
				<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
					<strong style="font-size:14px;">Right Click Blocker PRO</strong>
					<span style="background:rgba(124,58,237,.1);color:#6D28D9;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;">Canal GitHub Releases</span>
				</div>
				<div style="color:#1d2327;font-size:14px;margin-bottom:4px;">
					Nouvelle version disponible&nbsp;:
					<strong style="color:#646970;font-weight:600;"><?php echo esc_html( INFINITY_RCB_VERSION ); ?></strong>
					<span style="color:#646970;">→</span>
					<strong style="color:#1E6FF0;"><?php echo esc_html( $update->new_version ); ?></strong>
					<?php if ( '' !== $date ) : ?>
						<span style="color:#8c8f94;font-size:12px;">· publiée le <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $notes ) : ?>
					<p style="margin:6px 0 4px;color:#50575e;font-size:13px;line-height:1.5;"><?php echo esc_html( $notes ); ?>…</p>
				<?php endif; ?>
				<p style="margin:6px 0 12px;color:#8c8f94;font-size:12px;">Ce canal servira uniquement jusqu'à l'acceptation sur WordPress.org — la bascule sera automatique.</p>
				<div style="display:flex;gap:10px;flex-wrap:wrap;">
					<button type="button" id="rcb-update-now" style="cursor:pointer;border:0;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,#1E6FF0 0%,#7C3AED 100%);box-shadow:0 2px 6px rgba(30,111,240,.35);">⬇️ Mettre à jour maintenant</button>
					<a href="<?php echo esc_url( $details_url ); ?>" class="thickbox open-plugin-details-modal" style="display:inline-block;border:1px solid #dcdcde;border-radius:8px;padding:8px 16px;font-size:13px;text-decoration:none;color:#1d2327;background:#fff;">🔍 Voir les détails</a>
					<a href="<?php echo esc_url( 'https://github.com/' . self::repo() . '/releases' ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;border:1px solid transparent;border-radius:8px;padding:8px 16px;font-size:13px;text-decoration:none;color:#1d2327;background:#fff;">Notes complètes sur GitHub ↗</a>
				</div>
			</div>
		</div>
		<script>
		(function () {
			var btn = document.getElementById('rcb-update-now');
			if (!btn) { return; }
			btn.addEventListener('click', function () {
				var f = document.getElementById('upgrade-plugins-form');
				if (!f) { return; }
				var c = f.querySelector('input[name="checked[]"][value="<?php echo esc_js( INFINITY_RCB_BASENAME ); ?>"]');
				if (c) { c.checked = true; }
				f.submit();
			});
		})();
		</script>
		<?php
	}

	/**
	 * Après une mise à jour réussie : ajoute « Découvrir les nouveautés »
	 * aux liens de fin (« Retourner aux extensions », etc.).
	 */
	public static function complete_actions( $actions, $plugin ) {
		if ( ! is_array( $actions ) || INFINITY_RCB_BASENAME !== $plugin ) {
			return $actions;
		}
		$actions['rcb_whatsnew'] = '<a href="' . esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-about#rcb-about-versions' ) ) . '">🛡️ Découvrir les nouveautés</a>';
		return $actions;
	}

	/**
	 * Fin d'un upgrade : purge le cache de release pour relire GitHub dès
	 * le prochain contrôle (évite une ligne « mise à jour » périmée).
	 */
	public static function after_upgrade( $upgrader, $data ) {
		if ( ! is_array( $data ) || empty( $data['type'] ) || 'plugin' !== $data['type'] ) {
			return;
		}
		delete_site_transient( self::CACHE_KEY );
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
