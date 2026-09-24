<?php
/**
 * Boutique publique : shortcodes [rcb_plans] et [rcb_commande] à utiliser
 * sur le site du VENDEUR pour vendre les licences sans que l'acheteur ait
 * besoin d'installer le plugin d'abord. Les commandes déclenchent les
 * mêmes e-mails (vendeur + confirmation acheteur) que le parcours admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Shop {

	public function __construct() {
		add_shortcode( 'rcb_plans', array( $this, 'shortcode_plans' ) );
		add_shortcode( 'rcb_commande', array( $this, 'shortcode_order' ) );
		add_shortcode( 'rcb_demo', array( $this, 'shortcode_demo' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'init', array( $this, 'handle_order' ), 20 );
	}

	public function enqueue() {
		wp_enqueue_style( 'infinity-rcb-shop', INFINITY_RCB_URL . 'assets/css/infinity-rcb-shop.css', array(), INFINITY_RCB_VERSION );
		wp_enqueue_style( 'infinity-rcb-public', INFINITY_RCB_URL . 'assets/css/infinity-rcb-public.css', array(), INFINITY_RCB_VERSION );
		wp_enqueue_script( 'infinity-rcb-demo', INFINITY_RCB_URL . 'assets/js/infinity-rcb-demo.js', array(), INFINITY_RCB_VERSION, true );
	}

	/**
	 * Lien « Commander via WhatsApp » (si le vendeur a renseigné son
	 * numéro) : numéro international sans « + » ni espaces + message
	 * prérempli. Retourne '' si le WhatsApp n'est pas configuré.
	 */
	public function whatsapp_url( $message ) {
		$number = preg_replace( '/[^0-9]/', '', (string) infinity_rcb_options()['payment']['whatsapp'] );
		if ( '' === $number || '' === trim( (string) $message ) ) {
			return '';
		}
		return 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
	}

	/* ------------------------------------------------------------------
	 * Shortcode [rcb_plans]
	 * ------------------------------------------------------------------ */

	public function shortcode_plans() {
		$tiers = infinity_rcb_default_options()['license']['tiers'];
		$out   = '<div class="rcb-shop">';

		foreach ( $tiers as $key => $tier ) {
			$wa_plan = $this->whatsapp_url( sprintf( 'Bonjour, je souhaite commander la licence %s (%s DA) — Infinity RCB Pro.', $tier['label'], number_format( $tier['price_da'], 0, ',', ' ' ) ) );
			$out .= '<div class="rcb-shop-plan' . ( 'agency' === $key ? ' rcb-shop-plan-featured' : '' ) . '">';
			if ( 'agency' === $key ) {
				$out .= '<span class="rcb-shop-tag">Professionnel</span>';
			}
			$out .= '<span class="rcb-shop-label">' . esc_html( $tier['label'] ) . '</span>';
			$out .= '<div class="rcb-shop-price"><strong>' . number_format( $tier['price_da'], 0, ',', ' ' ) . ' DA</strong>'
				. '<span>≈ ' . esc_html( number_format( $tier['price_eur'], 2, ',', ' ' ) ) . ' €</span></div>';
			$out .= '<p class="rcb-shop-domains">' . (int) $tier['domains'] . ' nom' . ( $tier['domains'] > 1 ? 's' : '' ) . ' de domaine — paiement unique</p>';
			$out .= '<ul class="rcb-shop-features">';
			foreach ( $tier['features'] as $feature ) {
				$out .= '<li>' . esc_html( $feature ) . '</li>';
			}
			$out .= '</ul>';
			$out .= '<a class="rcb-shop-btn" href="#rcb-commande">🛒 Commander</a>';
			if ( '' !== $wa_plan ) {
				$out .= '<a class="rcb-shop-btn rcb-shop-btn-wa" href="' . esc_url( $wa_plan ) . '" target="_blank" rel="noopener">💬 Commander via WhatsApp</a>';
			}
			$out .= '</div>';
		}

		$out .= '</div>';
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Shortcode [rcb_commande]
	 * ------------------------------------------------------------------ */

	public function shortcode_order() {
		$tiers  = infinity_rcb_default_options()['license']['tiers'];
		$status = isset( $_GET['rcb-shop'] ) ? sanitize_key( wp_unslash( $_GET['rcb-shop'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d'affichage uniquement.
		$ref    = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d'affichage uniquement.
		$lic    = new Infinity_RCB_License();
		$order  = $ref ? $lic->get_order( $ref ) : null;
		$opts   = infinity_rcb_options();
		$out    = '<div class="rcb-shop">';

		// Message d'état après envoi.
		if ( 'ok' === $status && $order ) {
			$paypal = $lic->paypal_url( $order );
			$out .= '<div class="rcb-shop-success">';
			$out .= '<h3>✅ Commande ' . esc_html( $order['ref'] ) . ' enregistrée</h3>';
			$out .= '<p>Merci <strong>' . esc_html( $order['buyer'] ) . '</strong> ! Votre demande a été transmise au vendeur'
				. ( is_email( $opts['developer']['email'] ) ? ' (<strong>' . esc_html( $opts['developer']['email'] ) . '</strong>)' : '' )
				. ' et une confirmation vient de vous être envoyée à <strong>' . esc_html( $order['email'] ) . '</strong>.</p>';
			$out .= '<p>Montant à régler : <strong>' . number_format( (float) $order['amount_da'], 0, ',', ' ' ) . ' DA</strong>'
				. ( (int) $order['discount_pct'] > 0 ? ' <small>(code ' . esc_html( $order['promo_code'] ) . ' : -' . (int) $order['discount_pct'] . '%)</small>' : '' )
				. ' — référence à indiquer : <code>' . esc_html( $order['ref'] ) . '</code></p>';
			$pay = $opts['payment'];
			$out .= '<ul class="rcb-shop-pay">';
			if ( '' !== trim( (string) $pay['baridimob_rip'] ) ) { $out .= '<li><strong>📱 BaridiMob (RIP)</strong> : ' . esc_html( $pay['baridimob_rip'] ) . '</li>'; }
			if ( '' !== trim( (string) $pay['ccp'] ) )           { $out .= '<li><strong>📮 CCP / Edahabia</strong> : ' . esc_html( $pay['ccp'] ) . '</li>'; }
			if ( '' !== trim( (string) $pay['rib'] ) )           { $out .= '<li><strong>🏦 Virement (RIB)</strong> : ' . esc_html( $pay['rib'] ) . '</li>'; }
			if ( '' !== $paypal )                                { $out .= '<li><strong>🅿️ PayPal</strong> : <a href="' . esc_url( $paypal ) . '" target="_blank" rel="noopener">Payer ' . esc_html( number_format( (float) $order['amount_eur'], 2, ',', ' ' ) ) . ' €</a></li>'; }
			$out .= '</ul>';
			// Paiement carte CIB / Edahabia (Chargily) si activé par le vendeur.
			if ( 'pending' === $order['status'] && ! empty( $order['chargily_url'] ) ) {
				$out .= '<p style="text-align:center;"><a class="rcb-shop-btn rcb-shop-btn-card" href="' . esc_url( $order['chargily_url'] ) . '" target="_blank" rel="noopener">💳 Payer par carte CIB / Edahabia</a></p>';
			}

			// Étape « J'ai payé » : déclenche la demande de licence au vendeur.
			if ( 'pending' === $order['status'] ) {
				$out .= '<div class="rcb-shop-declare"><h4>Vous avez payé ?</h4>';
				$out .= '<p>Cliquez pour signaler votre paiement : la demande de licence part aussitôt au vendeur, puis votre clé arrive automatiquement par e-mail après vérification.</p>';
				$out .= '<form method="post" action="' . esc_url( remove_query_arg( array( 'rcb-shop', 'ref' ) ) ) . '#rcb-commande">';
				$out .= wp_nonce_field( 'infinity_rcb_shop', 'rcb_shop_nonce', true, false );
				$out .= '<input type="hidden" name="rcb_paid_ref" value="' . esc_attr( $order['ref'] ) . '">';
				$out .= '<button type="submit" name="rcb_shop_paid" value="1" class="rcb-shop-btn">✅ J\'ai payé — envoyer la demande de licence</button></form></div>';
			} elseif ( 'declared' === $order['status'] ) {
				$out .= '<div class="rcb-shop-success" style="margin-top:14px;"><h3>🕵️ Paiement signalé au vendeur</h3><p>Votre commande est en cours de vérification — la clé de licence vous sera envoyée automatiquement dès validation.</p></div>';
			}

			// Commande directe par WhatsApp.
			$wa = $this->whatsapp_url( sprintf( 'Bonjour, je souhaite payer la commande %1$s (%2$s DA) — Infinity RCB Pro.', $order['ref'], number_format( (float) $order['amount_da'], 0, ',', ' ' ) ) );
			if ( '' !== $wa ) {
				$out .= '<p style="text-align:center;"><a class="rcb-shop-btn rcb-shop-btn-wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">💬 Payer / commander via WhatsApp</a></p>';
			}
			if ( '' !== trim( (string) $pay['instructions'] ) ) {
				$out .= '<p class="rcb-shop-note">' . nl2br( esc_html( $pay['instructions'] ) ) . '</p>';
			}
			$out .= '</div>';
		} elseif ( 'paid-ok' === $status ) {
			$out .= '<div class="rcb-shop-success"><h3>🕵️ Paiement signalé au vendeur</h3><p>Merci ! La demande de licence vient de partir chez le vendeur. Dès vérification du paiement, votre clé de licence sera envoyée automatiquement à votre adresse e-mail.</p></div>';
		} elseif ( 'paid-err' === $status ) {
			$out .= '<div class="rcb-shop-error">⚠️ Déclaration impossible : référence inconnue ou e-mail ne correspondant pas à la commande.</div>';
		} elseif ( 'paid-status' === $status ) {
			$out .= '<div class="rcb-shop-error">ℹ️ Cette commande a déjà été signalée (ou livrée) — vérifiez votre boîte e-mail.</div>';
		} elseif ( 'paid-limit' === $status ) {
			$out .= '<div class="rcb-shop-error">⚠️ Trop de tentatives : réessayez dans une heure.</div>';
		} elseif ( 'err' === $status ) {
			$out .= '<div class="rcb-shop-error">⚠️ Commande incomplète : le nom et un e-mail valide sont requis.</div>';
		}

		// Formulaire.
		$out .= '<form method="post" action="' . esc_url( remove_query_arg( array( 'rcb-shop', 'ref' ) ) ) . '#rcb-commande" class="rcb-shop-form" id="rcb-commande">';
		$out .= wp_nonce_field( 'infinity_rcb_shop', 'rcb_shop_nonce', true, false );

		foreach ( $tiers as $key => $tier ) {
			$out .= '<label class="rcb-shop-radio"><input type="radio" name="rcb_plan" value="' . esc_attr( $key ) . '"' . checked( 'single', $key, false ) . ' data-label="' . esc_attr( $tier['label'] ) . '" data-price="' . esc_attr( number_format( $tier['price_da'], 0, ',', ' ' ) ) . ' DA">'
				. '<span><strong>' . esc_html( $tier['label'] ) . '</strong><br>' . number_format( $tier['price_da'], 0, ',', ' ' ) . ' DA — ' . (int) $tier['domains'] . ' domaine' . ( $tier['domains'] > 1 ? 's' : '' ) . '</span></label>';
		}

		$out .= '<label>Nom et prénom / société<input type="text" name="rcb_buyer" required placeholder="Votre nom"></label>';
		$out .= '<label>E-mail (réception de la clé)<input type="email" name="rcb_email" required placeholder="vous@exemple.com"></label>';
		$out .= '<label>Code promo (facultatif)<input type="text" name="rcb_promo" placeholder="Ex. LANCEMENT20"></label>';
		$out .= '<label>Note (facultatif)<textarea name="rcb_note" rows="2" placeholder="Précisions…"></textarea></label>';
		$out .= '<button type="submit" name="rcb_shop_order" value="1" class="rcb-shop-btn" id="rcb-shop-submit">🛒 Commander</button>';
		$out .= '<p style="text-align:center;font-size:12.5px;color:#64748B;margin:10px 0 0;">Paiement à l\'étape suivante (BaridiMob, CCP, carte CIB, PayPal) — la clé arrive automatiquement par e-mail après votre « J\'ai payé ».</p>';
		$out .= '</form>';

		// Modale de confirmation compacte (avant envoi définitif).
		$out .= '<div class="rcb-shop-modal" id="rcb-shop-modal" hidden><div class="rcb-shop-modal-box" role="dialog" aria-modal="true">
			<div class="rcb-shop-modal-head"><h4>Confirmez votre commande</h4></div>
			<ul class="rcb-shop-modal-list">
				<li><span>Plan</span><strong id="rcb-shop-confirm-plan">—</strong></li>
				<li><span>Votre nom</span><strong id="rcb-shop-confirm-buyer">—</strong></li>
				<li><span>E-mail (clé)</span><strong id="rcb-shop-confirm-email">—</strong></li>
				<li><span>Montant</span><strong id="rcb-shop-confirm-price">—</strong></li>
			</ul>
			<p style="font-size:12px;color:#64748B;margin:0 0 12px;">Après confirmation : coordonnées de paiement, bouton « J\'ai payé » puis clé automatique.</p>
			<div class="rcb-shop-modal-actions">
				<button type="button" class="rcb-shop-btn" id="rcb-shop-confirm-ok" style="width:auto;">✅ Confirmer</button>
				<button type="button" class="rcb-shop-btn" id="rcb-shop-confirm-cancel" style="width:auto;background:#fff;color:#334155;border:1px solid #cbd5e1;">Modifier</button>
			</div>
		</div></div>';

		// Déclaration tardive : « j'ai déjà commandé et je viens de payer ».
		$out .= '<div class="rcb-shop-declare"><h4>Déjà payé une commande ?</h4>';
		$out .= '<p>Indiquez votre référence et l’e-mail de la commande : le vendeur recevra aussitôt votre demande de licence.</p>';
		$out .= '<form method="post" action="' . esc_url( remove_query_arg( array( 'rcb-shop', 'ref' ) ) ) . '#rcb-commande" class="rcb-shop-paid-form">';
		$out .= wp_nonce_field( 'infinity_rcb_shop', 'rcb_shop_nonce', true, false );
		$out .= '<input type="text" name="rcb_paid_ref" placeholder="Référence (RCB-CMD-…)" required>';
		$out .= '<input type="email" name="rcb_paid_email" placeholder="E-mail de la commande" required>';
		$out .= '<button type="submit" name="rcb_shop_paid" value="1" class="rcb-shop-btn">🕵️ Signaler mon paiement</button>';
		$out .= '</form></div>';

		$out .= '</div>';

		return $out;
	}

	/* ------------------------------------------------------------------
	 * Shortcode [rcb_demo] — démo publique interactive (meilleure que la
	 * concurrence : 11 tests réels, journal en direct, 10 styles, tests
	 * clavier et DevTools armés, compteur). Autonome, zéro requête serveur.
	 * ------------------------------------------------------------------ */

	public function shortcode_demo() {
		$tiers = infinity_rcb_default_options()['license']['tiers'];

		$tests = array(
			array( 'right_click', '🖱️', 'Clic droit', 'Faites un clic droit sur cette carte', true ),
			array( 'selection',   '🔤', 'Sélection de texte', 'Essayez de surligner ce paragraphe. Lorem ipsum dolor sit amet, consectetur adipiscing elit — impossible de le sélectionner.', true ),
			array( 'copy',        '📋', 'Copier / couper', 'Sélectionnez puis Ctrl+C sur ce texte protégé. La copie est neutralisée.', true ),
			array( 'drag_image',  '🖼️', 'Glisser une image', 'Essayez de faire glisser ce logo hors de la page', true ),
			array( 'drag_link',   '🔗', 'Glisser un lien', 'Essayez de faire glisser ce lien : lien protégé', true ),
			array( 'key_u',       '💻', 'Code source', 'Ctrl+U pour afficher la source', false ),
			array( 'key_s',       '💾', 'Enregistrer la page', 'Ctrl+S pour télécharger la page', false ),
			array( 'key_p',       '🖨️', 'Impression', 'Ctrl+P pour imprimer', false ),
			array( 'key_f12',     '🛠️', 'Raccourcis DevTools', 'F12, Ctrl+Shift+I / J / C / K', false ),
			array( 'screenshot',  '📸', 'Capture d’écran', 'Touche PrintScreen (ImpÉcr)', false ),
			array( 'devtools',    '🕵️', 'Détection DevTools', 'Ouvrez les outils de développement — détection en temps réel', false ),
		);

		$out  = '<div class="rcb-demo" id="rcb-demo">';
		$out .= '<div class="rcb-demo-hero">';
		$out .= '<div class="rcb-demo-count"><span id="rcb-demo-count">0</span><small>tentatives bloquées<br>pendant votre visite</small></div>';
		$out .= '<div class="rcb-demo-lead"><h3>🛡️ Essayez de voler ce contenu. Nous attendons.</h3>';
		$out .= '<p>Chaque test ci-dessous est <strong>réellement protégé</strong> par Infinity RCB Pro — exactement comme le sera votre site. Tout est journalisé en direct.</p></div>';
		$out .= '</div>';

		// Grille de tests.
		$out .= '<div class="rcb-demo-grid">';
		foreach ( $tests as $t ) {
			$armed = $t[4] ? '' : ' rcb-demo-armed';
			$out .= '<div class="rcb-demo-test' . $armed . '" data-test="' . esc_attr( $t[0] ) . '">';
			$out .= '<span class="rcb-demo-emoji">' . $t[1] . '</span>';
			$out .= '<strong>' . esc_html( $t[2] ) . '</strong>';
			if ( 'drag_image' === $t[0] ) {
				$out .= '<p class="rcb-demo-sample"><img src="' . esc_url( INFINITY_RCB_URL . 'assets/icon.svg' ) . '" alt="Logo protégé" draggable="true" width="64" height="64"></p>';
			} elseif ( 'drag_link' === $t[0] ) {
				$out .= '<p class="rcb-demo-sample"><a href="#rcb-demo" draggable="true">🔗 lien protégé</a></p>';
			} elseif ( $t[4] ) {
				$out .= '<p class="rcb-demo-sample">' . esc_html( $t[3] ) . '</p>';
			} else {
				$out .= '<p>' . esc_html( $t[3] ) . '</p>';
			}
			$out .= $t[4] ? '<span class="rcb-demo-try">ESSAYEZ 👆</span>' : '<span class="rcb-demo-arm">▶ Activer le test</span>';
			$out .= '</div>';
		}
		$out .= '</div>';

		// Journal + galerie de styles.
		$out .= '<div class="rcb-demo-bottom">';
		$out .= '<div class="rcb-demo-log"><h4>📜 Journal en direct</h4><ul id="rcb-demo-log"></ul><p class="rcb-demo-empty" id="rcb-demo-empty">En attente de votre premier essai… tentez n’importe quel test ci-dessus !</p></div>';
		$out .= '<div class="rcb-demo-styles"><h4>🎨 10 styles de messages (cliquez pour tester)</h4><div class="rcb-demo-gallery">';
		$labels = infinity_rcb_message_styles();
		foreach ( array_keys( $labels ) as $st ) {
			$out .= '<button type="button" class="rcb-demo-style-btn" data-style="' . esc_attr( $st ) . '" style="--sw:' . esc_attr( $labels[ $st ]['bg'] ) . ';">' . esc_html( $labels[ $st ]['label'] ) . '</button>';
		}
		$out .= '</div><p class="rcb-muted">Plus : copyright automatique, bouton de fermeture, barre de progression, bip sonore et couleurs personnalisées.</p></div>';
		$out .= '</div>';

		// CTA.
		$out .= '<div class="rcb-demo-cta">';
		foreach ( $tiers as $tier ) {
			$out .= '<div class="rcb-demo-price"><strong>' . number_format( $tier['price_da'], 0, ',', ' ' ) . ' DA</strong><span>' . esc_html( $tier['label'] ) . '</span></div>';
		}
		$out .= '<a class="rcb-shop-btn" href="#rcb-commande">🛒 Commander votre licence à vie</a>';
		$out .= '</div>';

		$out .= '</div>';
		return $out;
	}

	/**
	 * Traitement de la commande publique et de la déclaration de paiement.
	 */
	public function handle_order() {
		if ( isset( $_POST['rcb_shop_paid'] ) ) {
			if ( ! isset( $_POST['rcb_shop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcb_shop_nonce'] ) ), 'infinity_rcb_shop' ) ) {
				return;
			}

			$ref   = isset( $_POST['rcb_paid_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_paid_ref'] ) ) : '';
			$email = isset( $_POST['rcb_paid_email'] ) ? sanitize_email( wp_unslash( $_POST['rcb_paid_email'] ) ) : '';

			$lic    = new Infinity_RCB_License();
			$result = $lic->declare_payment( $ref, $email );

			$map = array(
				true         => 'paid-ok',
				'unknown'    => 'paid-err',
				'email'      => 'paid-err',
				'status'     => 'paid-status',
				'ratelimit'  => 'paid-limit',
			);
			wp_safe_redirect( add_query_arg( 'rcb-shop', $map[ $result ] ?? 'paid-err' ) . '#rcb-commande' );
			exit;
		}

		if ( ! isset( $_POST['rcb_shop_order'] ) ) {
			return;
		}
		if ( ! isset( $_POST['rcb_shop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcb_shop_nonce'] ) ), 'infinity_rcb_shop' ) ) {
			return;
		}

		$plan  = isset( $_POST['rcb_plan'] ) ? sanitize_key( wp_unslash( $_POST['rcb_plan'] ) ) : 'single';
		$buyer = isset( $_POST['rcb_buyer'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_buyer'] ) ) : '';
		$email = isset( $_POST['rcb_email'] ) ? sanitize_email( wp_unslash( $_POST['rcb_email'] ) ) : '';
		$promo = isset( $_POST['rcb_promo'] ) ? sanitize_text_field( wp_unslash( $_POST['rcb_promo'] ) ) : '';
		$note  = isset( $_POST['rcb_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rcb_note'] ) ) : '';

		if ( '' === $buyer || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'rcb-shop', 'err' ) . '#rcb-commande' );
			exit;
		}

		$lic    = new Infinity_RCB_License();
		$order  = $lic->create_order( $plan, $buyer, $email, $note, $promo );
		$lic->email_order_to_seller( $order );
		$lic->email_order_to_buyer( $order );

		// Paiement carte CIB / Edahabia : création du checkout Chargily et
		// redirection vers la page de paiement (livraison auto par webhook).
		if ( Infinity_RCB_Chargily::enabled() ) {
			$back     = add_query_arg( array( 'rcb-shop' => 'ok', 'ref' => rawurlencode( $order['ref'] ) ) ) . '#rcb-commande';
			$checkout = Infinity_RCB_Chargily::create_checkout( $order, $back, $back );
			if ( ! empty( $checkout['url'] ) ) {
				$lic->set_order_chargily( $order['ref'], $checkout['id'], $checkout['url'] );
				wp_redirect( $checkout['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- domaine de paiement Chargily défini par le vendeur. // Domaine externe autorisé : pay.chargily.net.
				exit;
			}
			// Échec de création : on continue vers la page classique.
		}

		wp_safe_redirect( add_query_arg( array( 'rcb-shop' => 'ok', 'ref' => rawurlencode( $order['ref'] ) ) ) . '#rcb-commande' );
		exit;
	}
}
