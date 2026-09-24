<?php
/**
 * Bloqueur de spam de commentaires — 5 couches de protection :
 *
 * 1. Honeypot : champ caché que les robots remplissent (les humains non)
 * 2. Time-gate : soumission trop rapide = robot (minimum 3 s sur la page)
 * 3. Rate limit : plafond de commentaires par IP et par heure
 * 4. Blacklist : IP, e-mail et mots-clés interdits
 * 5. Compteur de liens : trop de liens = spam
 *
 * Les commentaires suspects sont marqués « spam » (pas supprimés),
 * visibles dans Commentaires → Indésirables.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Antispam {

	/**
	 * Hooks.
	 */
	public static function register() {
		$opts = infinity_rcb_options();

		if ( empty( $opts['antispam']['enabled'] ) ) {
			return;
		}

		// Champ piège injecté dans le formulaire de commentaire.
		add_action( 'comment_form_after_fields', array( __CLASS__, 'honeypot_field' ) );
		add_action( 'comment_form_logged_in_after', array( __CLASS__, 'honeypot_field' ) );

		// Horodatage caché (time-gate).
		add_action( 'comment_form', array( __CLASS__, 'timestamp_field' ) );

		// Filtre principal : intercepte avant enregistrement.
		add_filter( 'preprocess_comment', array( __CLASS__, 'filter_comment' ), 1 );

		// Compteur spam dans l'admin (bulle sur le menu Commentaires).
		add_action( 'admin_menu', array( __CLASS__, 'spam_counter_badge' ), 99 );
	}

	/**
	 * Champ honeypot : invisible pour les humains (CSS), rempli par les bots.
	 */
	public static function honeypot_field() {
		$opts = infinity_rcb_options();
		if ( empty( $opts['antispam']['honeypot'] ) ) {
			return;
		}
		$field_name = 'rcb_hp_' . wp_create_nonce( 'rcb_hp_ctx' );
		echo "\n" . '<div style="position:absolute;left:-9999px;top:-9999px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">'
			. '<label for="' . esc_attr( $field_name ) . '">Ne pas remplir ce champ</label>'
			. '<input type="text" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="" tabindex="-1" autocomplete="off">'
			. '</div>' . "\n";
	}

	/**
	 * Champ caché : horodatage JavaScript du chargement du formulaire.
	 */
	public static function timestamp_field() {
		$opts = infinity_rcb_options();
		if ( empty( $opts['antispam']['timegate'] ) ) {
			return;
		}
		echo "\n" . '<input type="hidden" name="rcb_ts" id="rcb-comment-ts" value="' . esc_attr( (string) time() ) . '">' . "\n";
		// Met à jour l'horodatage au chargement réel du DOM (pas au rendu PHP).
		echo '<script>(function(){var f=document.getElementById("rcb-comment-ts");if(f){f.value=String(Math.floor(Date.now()/1000));}})();</script>' . "\n";
	}

	/**
	 * Filtre principal : applique les 5 couches et marque en spam si nécessaire.
	 *
	 * @param array $commentdata Données du commentaire.
	 * @return array Données (status = spam si bloqué).
	 */
	public static function filter_comment( $commentdata ) {
		$opts     = infinity_rcb_options();
		$settings = $opts['antispam'];
		$ip       = Infinity_RCB_Stats::client_ip();
		$email    = isset( $commentdata['comment_author_email'] ) ? strtolower( trim( $commentdata['comment_author_email'] ) ) : '';
		$content  = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$author   = isset( $commentdata['comment_author'] ) ? (string) $commentdata['comment_author'] : '';
		$reason   = '';

		// 1. Honeypot rempli → robot.
		if ( empty( $reason ) && ! empty( $settings['honeypot'] ) ) {
			foreach ( $_POST as $key => $val ) { // phpcs:ignore WordPress.Security.NonceVerification.Mostirure -- champ public sans nonce (formulaire de commentaire WordPress standard).
				if ( 0 === strpos( (string) $key, 'rcb_hp_' ) && '' !== trim( (string) $val ) ) {
					$reason = 'honeypot';
					break;
				}
			}
		}

		// 2. Time-gate : moins de N secondes sur la page = robot.
		if ( empty( $reason ) && ! empty( $settings['timegate'] ) ) {
			$ts       = isset( $_POST['rcb_ts'] ) ? absint( $_POST['rcb_ts'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Mostirure
			$min_sec  = max( 1, (int) ( $settings['min_seconds'] ?? 3 ) );
			if ( $ts > 0 && ( time() - $ts ) < $min_sec ) {
				$reason = 'timegate';
			}
		}

		// 3. Rate limit : plafond par IP/heure.
		if ( empty( $reason ) && ! empty( $settings['ratelimit'] ) ) {
			$max_per_hour = max( 1, (int) ( $settings['max_per_hour'] ?? 5 ) );
			$key          = 'rcb_rl_c_' . md5( $ip );
			$count        = (int) get_transient( $key );
			if ( $count >= $max_per_hour ) {
				$reason = 'ratelimit';
			}
			set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		}

		// 4. Blacklist : IP, e-mail, mots-clés (une entrée par ligne).
		if ( empty( $reason ) && ! empty( $settings['blacklist'] ) ) {
			$blacklist = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $settings['blacklist'] ) ) );
			$haystack  = strtolower( $content . ' ' . $author . ' ' . $email . ' ' . $ip );
			foreach ( $blacklist as $entry ) {
				if ( '' !== $entry && false !== strpos( $haystack, strtolower( $entry ) ) ) {
					$reason = 'blacklist:' . $entry;
					break;
				}
			}
		}

		// 5. Compteur de liens : trop de liens = spam.
		if ( empty( $reason ) && ! empty( $settings['linkcheck'] ) ) {
			$max_links = max( 0, (int) ( $settings['max_links'] ?? 2 ) );
			$link_count = preg_match_all( '/https?:\/\/|www\./i', $content );
			if ( $link_count > $max_links ) {
				$reason = 'links:' . $link_count;
			}
		}

		// Résultat : marquer en spam.
		if ( '' !== $reason ) {
			$commentdata['comment_approved'] = 'spam';

			// Statistique : compteur global de spam bloqué.
			$spam_count = (int) get_option( 'infinity_rcb_pro_spam_count', 0 );
			update_option( 'infinity_rcb_pro_spam_count', $spam_count + 1, false );

			// Journal : trace de l'incident.
			if ( class_exists( 'Infinity_RCB_Logger' ) && ! empty( $opts['logging']['enabled'] ) ) {
				$logger = new Infinity_RCB_Logger();
				$logger->log( 'SPAM bloqué (' . $reason . ') IP:' . $ip . ' — ' . substr( $content, 0, 80 ), 'warning' );
			}
		}

		return $commentdata;
	}

	/**
	 * Bulle sur le menu Commentaires : nombre de spam bloqués au total.
	 */
	public static function spam_counter_badge() {
		$count = (int) get_option( 'infinity_rcb_pro_spam_count', 0 );
		if ( $count < 1 ) {
			return;
		}
		global $menu;
		foreach ( $menu as $key => $item ) {
			if ( 'edit-comments.php' === $item[2] ) {
				$menu[ $key ][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . number_format( $count ) . '</span></span>';
				break;
			}
		}
	}

	/**
	 * Réinitialise le compteur de spam.
	 */
	public static function reset_counter() {
		delete_option( 'infinity_rcb_pro_spam_count' );
	}
}
