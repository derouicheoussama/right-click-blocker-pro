<?php
/**
 * Page : Licence & Achat — activation de licence, plans, commande
 * (demande envoyée par e-mail au vendeur), instructions de paiement,
 * générateur de clés (vendeur) et suivi des commandes.
 *
 * NOTE DE CONFORMITÉ (directives WordPress.org 5 & 6 — pas de trialware) :
 * cette page gère des licences de SUPPORT symboliques et optionnelles.
 * Elle n'active, ne désactive, ni ne limite aucune fonctionnalité du
 * plugin : toutes les protections sont totalement libres, avec ou sans clé.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles,
 * $notice, $cron_next, $lic_status, $orders, $last_order, $emailed, $gen_key, $gen_plan
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lic      = new Infinity_RCB_License();
$active   = 'active' === $lic_status['code'];
$vendor   = Infinity_RCB_License::vendor_enabled();
$pay      = $options['payment'];
$seller   = $options['developer'];
$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
$prefill_name  = $current_user ? $current_user->display_name : '';
$prefill_email = is_email( (string) get_option( 'admin_email' ) ) ? get_option( 'admin_email' ) : '';
$release_domain = isset( $_GET['rcb-domain'] ) ? sanitize_text_field( wp_unslash( $_GET['rcb-domain'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d'affichage (domaine à libérer).

$seller_mail = is_email( $seller['email'] ) ? $seller['email'] : '';

$email_badges = array(
	'both'    => array( 'ok', $seller_mail ? '✅ Demande envoyée à ' . $seller_mail . ' et confirmation envoyée à l’acheteur.' : '✅ Demande envoyée au vendeur et confirmation envoyée à l’acheteur.' ),
	'partial' => array( 'warn', '⚠️ Envoi partiel : vérifiez la configuration e-mail de l’hébergement. Vous pouvez renvoyer la demande ci-dessous.' ),
	'none'    => array( 'warn', $seller_mail ? '⚠️ L’envoi automatique a échoué (fonction mail désactivée ?). Utilisez le bouton « Renvoyer la demande » ou le lien direct ci-dessous.' : '⚠️ Aucun e-mail vendeur configuré (Réglages → Général → Développeur) : la demande ne peut pas partir automatiquement.' ),
);

$notices = array(
	'license-ok'        => array( 'ok', '✅ Licence activée avec succès — un e-mail de confirmation avec votre clé vient de vous être envoyé.' ),
	'license-invalid'   => array( 'warn', '⚠️ Clé de licence invalide : vérifiez le copier-coller de la clé reçue par e-mail (elle est personnelle et infalsifiable).' ),
	'license-quota'     => array( 'warn', '⚠️ Quota de domaines atteint pour cette clé. Désactivez la licence sur un autre site ou choisissez un plan supérieur.' ),
	'license-revoked'   => array( 'warn', '⛔ Cette licence a été révoquée par le vendeur. Contactez-le pour en comprendre la raison.' ),
	'license-ratelimit' => array( 'warn', '⚠️ Trop de tentatives d’activation : réessayez dans une heure ou contactez le vendeur.' ),
	'license-off'       => array( 'ok', '✅ Licence désactivée sur ce domaine — le créneau est libéré.' ),
	'order-invalid'     => array( 'warn', '⚠️ Commande incomplète : le nom et un e-mail valide sont requis.' ),
	'order-updated'     => array( 'ok', '✅ Commande mise à jour.' ),
	'order-fulfilled'   => array( 'ok', '🔑 Clé générée et envoyée automatiquement à l’acheteur — pensez à vérifier la réception du paiement.' ),
	'order-declared'    => array( 'ok', '🕵️ Paiement signalé : la demande de licence vient d’être envoyée au vendeur (accusé envoyé à l’acheteur).' ),
	'order-declare-err' => array( 'warn', '⚠️ Déclaration impossible : commande introuvable ou déjà traitée.' ),
	'order-deleted'     => array( 'ok', '🗑️ Commande supprimée.' ),
	'key-resent'        => array( 'ok', '📧 Clé de licence renvoyée à l’acheteur par e-mail.' ),
	'order-nokey'       => array( 'warn', '⚠️ Aucune clé à renvoyer pour cette commande (clé pas encore générée ?).' ),
	'vendor-crypto'     => array( 'warn', '⚠️ L’extension OpenSSL de PHP est requise pour vérifier les licences — contactez votre hébergeur.' ),
	'vendor-needed'     => array( 'warn', '⚠️ Génération réservée au site du développeur.' ),
	'release-sent'      => array( 'ok', '📧 Un code de libération à 6 chiffres vient d’être envoyé à l’adresse e-mail de la licence. Saisissez-le ci-dessous (valable 1 heure).' ),
	'release-done'      => array( 'ok', '✅ Créneau libéré : le domaine a été retiré de votre licence.' ),
	'release-bad'       => array( 'warn', '⚠️ Code incorrect ou expiré — demandez un nouveau code.' ),
	'release-unknown'   => array( 'warn', '⚠️ Domaine inconnu dans cette licence.' ),
	'release-self'      => array( 'warn', 'ℹ️ Pour ce domaine, utilisez simplement « Désactiver sur ce domaine ».' ),
	'release-mailfail'  => array( 'warn', '⚠️ Envoi du code impossible (e-mail de licence invalide ?). Contactez le vendeur.' ),
	'mailtest-ok'       => array( 'ok', '✅ E-mail de test envoyé — vérifiez la boîte de l’adresse d’administration du site (et vos spams).' ),
	'mailtest-fail'     => array( 'warn', '⛔ L’envoi a échoué : l’hébergement bloque probablement la fonction mail. Installez un plugin SMTP (ex. WP Mail SMTP) — ou utilisez les liens « ouvrir dans ma messagerie » de la confirmation de commande.' ),
	'maillog-cleared'   => array( 'ok', '✅ Journal des envois vidé.' ),
	'promo-saved'       => array( 'ok', '✅ Code promo enregistré — la remise s’applique dès la prochaine commande (expiration et quota vérifiés automatiquement).' ),
	'promo-deleted'     => array( 'ok', '✅ Code promo supprimé.' ),
	'digest-sent'       => array( 'ok', '✅ Bilan hebdo envoyé à l’adresse du vendeur — vérifiez votre boîte de réception.' ),
	'digest-off'        => array( 'warn', '⚠️ Bilan non envoyé : activez-le dans Réglages → Paiement (site vendeur) ou vérifiez l’adresse e-mail.' ),
);

$steps = array(
	array( '1', '🛒 Choisissez votre licence', 'Mono-Site, 5 Sites ou Agence — licence à vie, paiement unique, sans abonnement.' ),
	array( '2', '💳 Payez simplement', 'BaridiMob, CCP / Edahabia, carte CIB, virement ou PayPal, en indiquant la référence reçue par e-mail.' ),
	array( '3', '🕵️ Déclarez « J\'ai payé »', 'Un clic sur la page de commande : la demande de licence part aussitôt au vendeur, qui vérifie le paiement.' ),
	array( '4', '🔑 Recevez la clé et activez', 'La clé arrive automatiquement par e-mail — collez-la dans « Ma licence », active à vie.' ),
);

$status_badge = array(
	'active'   => 'rcb-pill-ok',
	'inactive' => 'rcb-pill-off',
	'quota'    => 'rcb-pill-gold',
	'invalid'  => 'rcb-pill-off',
);
?>
<div class="wrap rcb-wrap">

	<!-- ===== Hero ===== -->
	<div class="rcb-hero rcb-hero-compact">
		<div class="rcb-hero-brand">
			<div class="rcb-logo"><?php printf( '%s', infinity_rcb_shield_svg() ) ?></div>
			<div>
				<h1>Licence &amp; Achat</h1>
				<p>Activez votre licence à vie ou commandez une nouvelle clé — dès 2 900 DA (1 site) · 4 800 DA (5 sites) · 12 000 DA (Agence, 20 sites)</p>
			</div>
		</div>
		<div class="rcb-hero-side">
			<span class="rcb-pill <?php echo esc_attr( $status_badge[ $lic_status['code'] ] ); ?>">
				<span class="rcb-dot"></span>Licence : <?php echo esc_html( $lic_status['label'] ); ?>
			</span>
			<?php if ( $active ) : ?>
				<span class="rcb-pill rcb-pill-soft"><?php echo esc_html( $lic_status['plan_label'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<!-- ===== Navigation rapide ===== -->
	<div class="rcb-nav-chips">
		<a class="rcb-nav-chip" href="#rcb-licence">🔑 Ma licence</a>
		<a class="rcb-nav-chip" href="#rcb-commande">🛒 Commander</a>
		<a class="rcb-nav-chip" href="#rcb-commandes">📦 Mes commandes <span class="rcb-nav-count"><?php echo count( $orders ); ?></span></a>
		<a class="rcb-nav-chip" href="#rcb-emails">📧 E-mails &amp; diagnostic</a>
		<?php if ( $vendor ) : ?>
			<a class="rcb-nav-chip" href="#rcb-generateur">🗝️ Générateur</a>
			<a class="rcb-nav-chip" href="#rcb-promos">🏷️ Promos</a>
		<?php endif; ?>
	</div>

	<?php if ( isset( $notices[ $notice ] ) ) : ?>
		<div class="rcb-notice rcb-notice-<?php echo esc_attr( $notices[ $notice ][0] ); ?>"><?php echo esc_html( $notices[ $notice ][1] ); ?></div>
	<?php endif; ?>

	<?php if ( ( 'order-created' === $notice || 'order-resent' === $notice ) && isset( $email_badges[ $emailed ] ) ) : ?>
		<div class="rcb-notice rcb-notice-<?php echo esc_attr( $email_badges[ $emailed ][0] ); ?>"><?php echo esc_html( $email_badges[ $emailed ][1] ); ?></div>
	<?php elseif ( 'order-created' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Commande enregistrée. Suivez les instructions de paiement ci-dessous.</div>
	<?php endif; ?>

	<?php if ( $vendor ) :
		$vstats = $lic->vendor_stats();
		?>
		<!-- ===== Tableau de bord vendeur ===== -->
		<div class="rcb-card rcb-card-vendor">
			<div class="rcb-card-head">
				<h2>📈 Ventes <span class="rcb-pill rcb-pill-gold">SITE VENDEUR</span></h2>
				<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb_export=orders' ), 'infinity_rcb_export', 'rcb_nonce' ) ); ?>">⬇️ Exporter les commandes (CSV)</a>
			</div>
			<div class="rcb-kpis">
				<div class="rcb-card rcb-kpi"><span class="rcb-kpi-ico" style="background:#0ea5e91a;color:#0ea5e9;">🕵️</span><div><em data-count="<?php echo (int) $vstats['declared']; ?>">0</em><small>Payées — à vérifier</small></div></div>
				<div class="rcb-card rcb-kpi"><span class="rcb-kpi-ico" style="background:#f59e0b1a;color:#f59e0b;">⏳</span><div><em data-count="<?php echo (int) $vstats['pending']; ?>">0</em><small>En attente de paiement</small></div></div>
				<div class="rcb-card rcb-kpi"><span class="rcb-kpi-ico" style="background:#10b9811a;color:#10b981;">🔑</span><div><em data-count="<?php echo (int) $vstats['paid_month']; ?>">0</em><small>Licences livrées ce mois</small></div></div>
				<div class="rcb-card rcb-kpi"><span class="rcb-kpi-ico" style="background:#7C3AED1a;color:#7C3AED;">💰</span><div><em><?php echo number_format( (float) $vstats['revenue_da'], 0, ',', ' ' ); ?> DA</em><small>Chiffre d’affaires du mois</small></div></div>
				<div class="rcb-card rcb-kpi"><span class="rcb-kpi-ico" style="background:#1E6FF01a;color:#1E6FF0;">📦</span><div><em data-count="<?php echo (int) $vstats['total']; ?>">0</em><small>Commandes au total</small></div></div>
			</div>
			<?php if ( (int) $vstats['declared'] > 0 ) : ?>
				<div class="rcb-notice rcb-notice-warn" style="margin:0 0 10px;">🕵️ <strong><?php echo (int) $vstats['declared']; ?> commande(s) payée(s) attendent leur clé</strong> — cliquez « Valider + clé » dans le tableau ci-dessous (la clé part automatiquement par e-mail).</div>
			<?php endif; ?>
			<?php if ( '' !== $vstats['last_buyer'] ) : ?>
				<p class="rcb-muted" style="margin:0;">Dernier acheteur : <strong><?php echo esc_html( $vstats['last_buyer'] ); ?></strong> — la demande de licence arrive automatiquement après le paiement (« J'ai payé » ou carte CIB / Edahabia).</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- ===== Statut de licence ===== -->
	<div class="rcb-card" id="rcb-licence">
		<div class="rcb-card-head">
			<h2>🔑 Ma licence</h2>
			<?php if ( $active ) : ?>
				<form method="post" action="" class="rcb-inline-form" data-confirm="Désactiver la licence sur ce domaine ? Le créneau sera libéré pour un autre site.">
					<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
					<button type="submit" name="infinity_rcb_deactivate_license" value="1" class="rcb-btn rcb-btn-danger">Désactiver sur ce domaine</button>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( $active || 'quota' === $lic_status['code'] ) : ?>
			<ul class="rcb-syslist">
				<li><span>Clé de licence</span><strong><code id="rcb-key-value" class="rcb-selall"><?php echo esc_html( $lic_status['key'] ); ?></code> <button type="button" class="rcb-btn rcb-btn-ghost rcb-btn-xs" data-copy="#rcb-key-value">Copier</button></strong></li>
				<li><span>Plan</span><strong><?php echo esc_html( $lic_status['plan_label'] ); ?> (<?php echo (int) $lic_status['slots']; ?> domaine<?php echo $lic_status['slots'] > 1 ? 's' : ''; ?>)</strong></li>
				<?php if ( $lic_status['email'] ) : ?>
					<li><span>E-mail associé</span><strong><?php echo esc_html( $lic_status['email'] ); ?></strong></li>
				<?php endif; ?>
				<li><span>Domaine actuel</span><strong><code><?php echo esc_html( $lic_status['domain'] ); ?></code> <?php echo $lic_status['is_here'] ? '— enregistré ✓' : '— non enregistré'; ?></strong></li>
			</ul>

			<?php if ( ! empty( $lic_status['domains'] ) ) : ?>
				<p class="rcb-muted" style="margin-bottom:8px;">Domaines enregistrés (<?php echo count( $lic_status['domains'] ); ?>/<?php echo (int) $lic_status['slots']; ?>) — ✖ libère un créneau occupé par un site supprimé :</p>
				<div class="rcb-pay-chips">
					<?php foreach ( $lic_status['domains'] as $domain ) : ?>
						<span class="rcb-chip">
							<?php echo $lic_status['domain'] === $domain ? '📍 ' : '🌐 '; ?><?php echo esc_html( $domain ); ?>
							<?php if ( $lic_status['domain'] !== $domain ) : ?>
								<form method="post" action="" class="rcb-inline-form" style="margin:0;display:inline;">
									<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
									<input type="hidden" name="rcb_domain" value="<?php echo esc_attr( $domain ); ?>">
									<button type="submit" name="infinity_rcb_release_request" value="1" class="rcb-btn rcb-btn-danger rcb-btn-xs" title="Libérer ce créneau (code envoyé par e-mail)" data-confirm="Libérer le créneau de <?php echo esc_attr( $domain ); ?> ? Un code de confirmation sera envoyé à l’e-mail de la licence.">✖</button>
								</form>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
					<?php for ( $i = count( $lic_status['domains'] ); $i < (int) $lic_status['slots']; $i++ ) : ?>
						<span class="rcb-chip rcb-chip-empty">＋ créneau libre</span>
					<?php endfor; ?>
				</div>

				<?php if ( '' !== $release_domain && in_array( $release_domain, $lic_status['domains'], true ) && $release_domain !== $lic_status['domain'] ) : ?>
					<form method="post" action="" class="rcb-release-form">
						<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
						<input type="hidden" name="rcb_domain" value="<?php echo esc_attr( $release_domain ); ?>">
						<label for="rcb-release-code">Code reçu pour <strong><?php echo esc_html( $release_domain ); ?></strong> :</label>
						<div class="rcb-release-row">
							<input type="text" id="rcb-release-code" name="rcb_code" inputmode="numeric" maxlength="6" placeholder="000000" required>
							<button type="submit" name="infinity_rcb_release_confirm" value="1" class="rcb-btn rcb-btn-primary">Libérer ce créneau</button>
						</div>
					</form>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( 'quota' === $lic_status['code'] ) : ?>
				<p class="rcb-notice rcb-notice-warn" style="margin-top:14px;">⚠️ Ce domaine n’est pas dans la liste et le quota est atteint : libérez un créneau ci-dessus ou passez à un plan supérieur.</p>
			<?php endif; ?>

			<?php if ( ! empty( $lic_status['log'] ) ) : ?>
				<details class="rcb-license-log">
					<summary>🧾 Historique des activations (<?php echo count( $lic_status['log'] ); ?>)</summary>
					<ul>
						<?php foreach ( array_slice( (array) $lic_status['log'], 0, 10 ) as $entry ) :
							$icons = array( 'activated' => '✅ Activé', 'deactivated' => '🔓 Désactivé', 'released' => '✖ Libéré' );
							?>
							<li><?php echo esc_html( $entry['time'] ); ?> — <?php echo esc_html( $icons[ $entry['action'] ] ?? $entry['action'] ); ?> : <code><?php echo esc_html( $entry['domain'] ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>

			<?php if ( $active && 'single' === $lic_status['plan'] ) : ?>
				<div class="rcb-notice rcb-notice-warn" style="margin-top:14px;">
					⬆️ <strong>Besoin de plusieurs sites ?</strong> Commandez la <a href="#rcb-commande">licence 5 Sites</a> puis activez la nouvelle clé reçue : elle remplacera celle-ci sur ce site et ouvrira 5 créneaux de domaines.
				</div>
			<?php elseif ( $active && in_array( $lic_status['plan'], array( 'five', 'agency' ), true ) && 'agency' !== $lic_status['plan'] ) : ?>
				<div class="rcb-notice rcb-notice-ok" style="margin-top:14px;">✅ Jusqu’à <?php echo (int) $lic_status['slots']; ?> noms de domaine avec la même clé — <a href="#rcb-commande">licence Agence (20 sites)</a> disponible pour aller plus loin.</div>
			<?php elseif ( $active && 'agency' === $lic_status['plan'] ) : ?>
				<div class="rcb-notice rcb-notice-ok" style="margin-top:14px;">✅ Licence maximale active : jusqu’à 20 noms de domaine avec la même clé.</div>
			<?php endif; ?>
		<?php elseif ( 'revoked' === $lic_status['code'] ) : ?>
			<p class="rcb-notice rcb-notice-warn">⛔ Cette clé a été révoquée par le vendeur. Contactez <?php echo $seller_mail ? '<a href="mailto:' . esc_attr( $seller_mail ) . '">' . esc_html( $seller_mail ) . '</a>' : 'le vendeur'; ?> pour en comprendre la raison.</p>
		<?php else : ?>
			<p class="rcb-muted">Aucune licence active sur ce domaine. Collez la clé reçue par e-mail après votre achat.</p>
		<?php endif; ?>

		<form method="post" action="" class="rcb-license-form">
			<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
			<div class="rcb-grid-2-col">
				<div class="rcb-field">
					<label for="rcb-key">Clé de licence</label>
					<input type="text" id="rcb-key" name="rcb_key" placeholder="RCB-S-XXXXX-XXXXX-XXXX" value="<?php echo esc_attr( $active ? $lic_status['key'] : '' ); ?>" required>
				</div>
				<div class="rcb-field">
					<label for="rcb-email">E-mail de l’acheteur</label>
					<input type="email" id="rcb-email" name="rcb_email" placeholder="vous@exemple.com" value="<?php echo esc_attr( $lic_status['email'] ); ?>" required>
				</div>
			</div>
			<button type="submit" name="infinity_rcb_activate_license" value="1" class="rcb-btn rcb-btn-primary">🚀 <?php echo $active ? 'Mettre à jour / réenregistrer ce domaine' : 'Activer la licence'; ?></button>
		</form>
	</div>

	<!-- ===== Comment ça marche ===== -->
	<div class="rcb-card">
		<div class="rcb-card-head"><h2>🧭 Comment obtenir votre licence</h2></div>
		<div class="rcb-steps">
			<?php foreach ( $steps as $step ) : ?>
				<div class="rcb-step">
					<span class="rcb-step-n"><?php echo esc_html( $step[0] ); ?></span>
					<strong><?php echo esc_html( $step[1] ); ?></strong>
					<p><?php echo esc_html( $step[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ===== Plans ===== -->
	<div class="rcb-card rcb-price-card">
		<div class="rcb-card-head"><h2>💎 Licences à vie — achat symbolique</h2></div>
		<div class="rcb-plans">
			<?php foreach ( $options['license']['tiers'] as $key => $tier ) :
				$upgrade_hint = ( $active && 'single' === $lic_status['plan'] && 'five' === $key )
					|| ( $active && 'five' === $lic_status['plan'] && 'agency' === $key );
				?>
				<div class="rcb-plan <?php echo in_array( $key, array( 'five', 'agency' ), true ) ? 'rcb-plan-featured' : ''; ?>">
					<?php if ( 'five' === $key ) : ?><span class="rcb-plan-tag">Meilleure offre</span>
					<?php elseif ( 'agency' === $key ) : ?><span class="rcb-plan-tag">Professionnel</span><?php endif; ?>
					<span class="rcb-price-label"><?php echo esc_html( $tier['label'] ); ?></span>
					<div class="rcb-price-amount">
						<strong><?php echo number_format( $tier['price_da'], 0, ',', ' ' ); ?> DA</strong>
						<span>≈ <?php echo esc_html( number_format( $tier['price_eur'], 2, ',', ' ' ) ); ?> €</span>
					</div>
					<p class="rcb-plan-domains"><?php echo (int) $tier['domains']; ?> nom de domaine<?php echo $tier['domains'] > 1 ? 's' : ''; ?> — paiement unique</p>
					<ul class="rcb-price-includes">
						<?php foreach ( $tier['features'] as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $upgrade_hint ) : ?>
						<p class="rcb-plan-upgrade">⬆️ Vous êtes en <?php echo esc_html( $lic_status['plan_label'] ); ?> : commandez ce plan pour monter en gamme.</p>
					<?php endif; ?>
					<button type="button" class="rcb-btn rcb-btn-primary rcb-order-btn" data-plan="<?php echo esc_attr( $key ); ?>">🛒 Commander</button>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ===== Formulaire de commande ===== -->
	<div class="rcb-card" id="rcb-commande">
		<div class="rcb-card-head">
			<h2>🛒 Nouvelle commande</h2>
			<span class="rcb-pill rcb-pill-soft">💳 Payez · 🕵️ « J'ai payé » · 🔑 clé automatique par e-mail</span>
		</div>
		<form method="post" action="" id="rcb-order-form">
			<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
			<div class="rcb-plan-picker">
				<?php foreach ( $options['license']['tiers'] as $key => $tier ) : ?>
					<label class="rcb-plan-option <?php echo 'single' === $key ? 'is-selected' : ''; ?>">
						<input type="radio" class="rcb-order-plan" name="rcb_plan" value="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $tier['label'] ); ?>" data-price="<?php echo esc_attr( number_format( $tier['price_da'], 0, ',', ' ' ) . ' DA' ); ?>" data-domains="<?php echo esc_attr( (int) $tier['domains'] . ' nom' . ( $tier['domains'] > 1 ? 's' : '' ) . ' de domaine' . ( $tier['domains'] > 1 ? 's' : '' ) ); ?>" <?php checked( 'single', $key ); ?>>
						<strong class="rcb-plan-name"><?php echo esc_html( $tier['label'] ); ?></strong>
						<span class="rcb-plan-domains"><?php echo (int) $tier['domains']; ?> nom de domaine<?php echo $tier['domains'] > 1 ? 's' : ''; ?></span>
						<span class="rcb-plan-price"><?php echo number_format( $tier['price_da'], 0, ',', ' ' ); ?> DA <em>≈ <?php echo esc_html( number_format( $tier['price_eur'], 2, ',', ' ' ) ); ?> €</em></span>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="rcb-grid-2-col">
				<div class="rcb-field">
					<label for="rcb-buyer">Nom de l’acheteur</label>
					<input type="text" id="rcb-buyer" name="rcb_buyer" placeholder="Nom et prénom / société" value="<?php echo esc_attr( $prefill_name ); ?>" required>
				</div>
				<div class="rcb-field">
					<label for="rcb-order-email">E-mail (réception de la clé)</label>
					<input type="email" id="rcb-order-email" name="rcb_order_email" placeholder="vous@exemple.com" value="<?php echo esc_attr( $prefill_email ); ?>" required>
				</div>
				<div class="rcb-field">
					<label for="rcb-promo">Code promo (facultatif)</label>
					<input type="text" id="rcb-promo" name="rcb_promo" placeholder="Ex. LANCEMENT20" style="text-transform:uppercase;">
					<p class="rcb-muted">La remise est appliquée à la validation et confirmée par e-mail.</p>
				</div>
				<div class="rcb-field">
					<label for="rcb-note">Note (facultatif)</label>
					<textarea id="rcb-note" name="rcb_note" rows="2" placeholder="Précisions sur le paiement, référence du virement…"></textarea>
				</div>
			</div>
			<button type="submit" name="infinity_rcb_create_order" value="1" class="rcb-btn rcb-btn-primary rcb-btn-lg" id="rcb-order-submit">🛒 Commander — <span id="rcb-order-submit-label">Licence Mono-Site · 2 900 DA</span></button>
			<p class="rcb-muted" style="margin:8px 0 0;">Paiement à l'étape suivante (BaridiMob, CCP, carte CIB, PayPal) — la demande de licence part au vendeur dès votre « J'ai payé », puis la clé arrive automatiquement par e-mail.</p>
		</form>
	</div>

	<?php if ( $last_order ) :
		$paypal = $lic->paypal_url( $last_order );
		$mailto_body = 'Bonjour, je viens de payer la commande ' . $last_order['ref'] . " (" . $options['license']['tiers'][ $last_order['plan'] ]['label'] . ").\nNom : " . $last_order['buyer'] . "\nMontant : " . number_format( (int) $last_order['amount_da'], 0, ',', ' ' ) . ' DA';
		$last_status   = $last_order['status'] ?? 'pending';
		$status_labels = array(
			'pending'   => array( '⏳ En attente de paiement', '#f59e0b' ),
			'declared'  => array( '🕵️ Paiement déclaré — vérification par le vendeur', '#0ea5e9' ),
			'paid'      => array( '✅ Payée — clé générée', '#10b981' ),
			'delivered' => array( '✅ Livrée — clé activée', '#10b981' ),
		);
		$lst = $status_labels[ $last_status ] ?? array( $last_status, '#64748b' );

		// Timeline : étape courante selon le statut.
		$track = array( 'Commandée', 'Payée', 'Vérifiée', 'Clé reçue' );
		$current_step = 'pending' === $last_status ? 2 : ( 'declared' === $last_status ? 3 : 4 );
		?>
		<!-- ===== Confirmation de commande ===== -->
		<div class="rcb-card rcb-order-confirm">
			<div class="rcb-card-head">
				<h2>🧾 Commande <?php echo esc_html( $last_order['ref'] ); ?></h2>
				<span class="rcb-badge" style="background:<?php echo esc_attr( $lst[1] ); ?>1a;color:<?php echo esc_attr( $lst[1] ); ?>;"><?php echo esc_html( $lst[0] ); ?></span>
			</div>

			<div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin:6px 0 18px;">
				<?php foreach ( $track as $ti => $tlabel ) :
					$done = $ti + 1 < $current_step;
					$curr = $ti + 1 === $current_step;
					?>
					<span style="display:inline-flex;align-items:center;gap:7px;">
						<span style="width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
							<?php if ( $done ) : ?>background:#10B981;color:#fff;
							<?php elseif ( $curr ) : ?>background:linear-gradient(135deg,#1E6FF0,#7C3AED);color:#fff;
							<?php else : ?>background:#E2E8F0;color:#94A3B8;<?php endif; ?>"><?php echo $done ? '✓' : ( $ti + 1 ); ?></span>
						<span style="font-size:12.5px;font-weight:600;color:<?php echo $curr ? '#0B0F1E' : '#64748B'; ?>;"><?php echo esc_html( $tlabel ); ?></span>
					</span>
					<?php if ( $ti < count( $track ) - 1 ) : ?>
						<span style="color:#CBD5E1;margin:0 8px;font-size:13px;">→</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<div class="rcb-prose">
				<p>Bonjour <strong><?php echo esc_html( $last_order['buyer'] ); ?></strong>, votre commande est enregistrée. Réglez le montant ci-dessous en indiquant la référence <code><?php echo esc_html( $last_order['ref'] ); ?></code>, puis déclarez votre paiement.</p>
			</div>
			<ul class="rcb-syslist">
				<li><span>Référence</span><strong><code><?php echo esc_html( $last_order['ref'] ); ?></code></strong></li>
				<li><span>Plan</span><strong><?php echo esc_html( $options['license']['tiers'][ $last_order['plan'] ]['label'] ); ?></strong></li>
				<li><span>Montant</span><strong><?php echo number_format( $last_order['amount_da'], 0, ',', ' ' ); ?> DA (<?php echo esc_html( number_format( $last_order['amount_eur'], 2, ',', ' ' ) ); ?> €)</strong></li>
				<li><span>Statut</span><strong style="color:<?php echo esc_attr( $lst[1] ); ?>;"><?php echo esc_html( $lst[0] ); ?></strong></li>
			</ul>

			<?php if ( 'pending' === $last_status ) : ?>
				<form method="post" action="" style="margin:16px 0 0;">
					<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
					<input type="hidden" name="rcb_ref" value="<?php echo esc_attr( $last_order['ref'] ); ?>">
					<input type="hidden" name="rcb_do" value="declare">
					<button type="submit" name="infinity_rcb_order_action" value="1" class="rcb-btn rcb-btn-primary">✅ J'ai payé — envoyer la demande de licence au vendeur</button>
					<p class="rcb-muted" style="margin:8px 0 0;">Après votre paiement (BaridiMob, CCP, carte, PayPal) : un clic ici et le vendeur reçoit aussitôt la demande — la clé arrivera automatiquement après vérification.</p>
				</form>
			<?php elseif ( 'declared' === $last_status ) : ?>
				<div class="rcb-notice rcb-notice-ok" style="margin-top:14px;">🕵️ Paiement signalé au vendeur — votre clé de licence sera envoyée automatiquement à <strong><?php echo esc_html( $last_order['email'] ); ?></strong> dès vérification.</div>
			<?php elseif ( in_array( $last_status, array( 'paid', 'delivered' ), true ) && '' !== ( $last_order['key'] ?? '' ) ) : ?>
				<div class="rcb-notice rcb-notice-ok" style="margin-top:14px;">🔑 Clé livrée ! Collez-la dans « Ma licence » en haut de page (un e-mail de rappel vous a été envoyé).</div>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $pay['instructions'] ) ) : ?>
				<div class="rcb-notice rcb-notice-warn" style="margin-top:14px;"><?php echo nl2br( esc_html( $pay['instructions'] ) ); ?></div>
			<?php endif; ?>

			<div class="rcb-pay-detail">
				<?php if ( ! empty( $pay['baridimob_rip'] ) ) : ?>
					<div class="rcb-pay-box"><strong>📱 BaridiMob (RIP)</strong><code><?php echo esc_html( $pay['baridimob_rip'] ); ?></code></div>
				<?php endif; ?>
				<?php if ( ! empty( $pay['ccp'] ) ) : ?>
					<div class="rcb-pay-box"><strong>📮 CCP / Edahabia</strong><code><?php echo esc_html( $pay['ccp'] ); ?></code></div>
				<?php endif; ?>
				<?php if ( ! empty( $pay['rib'] ) ) : ?>
					<div class="rcb-pay-box"><strong>🏦 Virement (RIB)</strong><code><?php echo esc_html( $pay['rib'] ); ?></code></div>
				<?php endif; ?>
				<?php if ( ! empty( $last_order['chargily_url'] ) && 'pending' === $last_status ) : ?>
					<div class="rcb-pay-box rcb-pay-paypal"><strong>💳 Carte CIB / Edahabia</strong>
						<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( $last_order['chargily_url'] ); ?>" target="_blank" rel="noopener">Payer <?php echo esc_html( number_format( $last_order['amount_da'], 0, ',', ' ' ) ); ?> DA par carte</a>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $pay['paypal_email'] ) || ! empty( $pay['paypal_me'] ) ) : ?>
					<div class="rcb-pay-box rcb-pay-paypal"><strong>🅿️ PayPal</strong>
						<?php if ( $paypal ) : ?>
							<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( $paypal ); ?>" target="_blank" rel="noopener">Payer <?php echo esc_html( number_format( $last_order['amount_eur'], 2, ',', ' ' ) ); ?> € avec PayPal</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="rcb-actions" style="margin-top:16px;">
				<?php if ( $seller_mail ) : ?>
					<form method="post" action="" class="rcb-inline-form">
						<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
						<input type="hidden" name="rcb_ref" value="<?php echo esc_attr( $last_order['ref'] ); ?>">
						<button type="submit" name="infinity_rcb_resend_order" value="1" class="rcb-btn rcb-btn-ghost">📧 Renvoyer les e-mails de la commande</button>
					</form>
					<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( 'mailto:' . $seller_mail . '?subject=' . rawurlencode( 'Paiement commande ' . $last_order['ref'] . ' — Infinity RCB Pro' ) . '&body=' . rawurlencode( $mailto_body ) ); ?>">✉️ Signaler le paiement par e-mail</a>
					<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( Infinity_RCB_License::mailto_link( $last_order['email'], 'Votre commande ' . $last_order['ref'] . ' — Infinity RCB Pro', "Bonjour,\n\nVoici le récapitulatif de votre commande " . $last_order['ref'] . ".\nMontant : " . number_format( (int) $last_order['amount_da'], 0, ',', ' ' ) . " DA\n\n(Contenu complet de la confirmation automatique — utilisez ce lien si l'e-mail automatique n'arrive pas.)" ) ); ?>">📤 Ouvrir la confirmation dans ma messagerie</a>
				<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
					<span class="rcb-notice rcb-notice-warn" style="margin:0;">⚠️ Configurez l’e-mail du vendeur dans les valeurs par défaut du plugin (fonction <code>infinity_rcb_default_options()</code>) pour activer l’envoi automatique.</span>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Générateur vendeur : n'apparaît que sur le site du développeur,
	     où la constante wp-config INFINITY_RCB_VENDOR_KEY_FILE est définie. -->
	<?php if ( $vendor ) : ?>
	<div class="rcb-card" id="rcb-generateur">
		<div class="rcb-card-head">
			<h2>🗝️ Générateur de clés <span class="rcb-pill rcb-pill-gold">SITE VENDEUR</span></h2>
			<span class="rcb-pill rcb-pill-ok"><span class="rcb-dot"></span>Clé privée active</span>
		</div>

		<p class="rcb-muted" style="margin:0 0 14px;">Générez une clé signée (ECDSA) après réception d’un paiement, puis envoyez-la à l’acheteur en répondant à l’e-mail de demande. Les clés générées ici sont vérifiables par toutes les installations, mais ne peuvent être créées <strong>que sur ce site</strong> (constante <code>INFINITY_RCB_VENDOR_KEY_FILE</code> du wp-config.php).</p>
		<form method="post" action="" class="rcb-generator-form">
			<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
			<div class="rcb-grid-2-col">
				<div class="rcb-field">
					<label for="rcb-key-plan">Plan de la clé</label>
					<select id="rcb-key-plan" name="rcb_key_plan">
						<?php foreach ( $options['license']['tiers'] as $key => $tier ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $tier['label'] ); ?> (<?php echo (int) $tier['domains']; ?> domaine<?php echo $tier['domains'] > 1 ? 's' : ''; ?> — <?php echo number_format( $tier['price_da'], 0, ',', ' ' ); ?> DA)</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" name="infinity_rcb_generate_key" value="1" class="rcb-btn rcb-btn-primary">🔑 Générer une clé signée</button>
		</form>

		<?php if ( $gen_key ) :
			$tiers  = $options['license']['tiers'];
			$gplan  = isset( $tiers[ $gen_plan ]['label'] ) ? $tiers[ $gen_plan ]['label'] : $gen_plan;
			$mail_body = "Bonjour,\n\nVoici votre clé de licence Infinity RCB Pro (" . $gplan . ") :\n\n" . $gen_key . "\n\nActivation : dans votre administration WordPress → Infinity RCB Pro → Licence & Achat → collez la clé puis « Activer la licence ».\n\nMerci pour votre confiance !\n" . $options['developer']['name'];
			?>
			<div class="rcb-key-result">
				<span class="rcb-price-label">Clé générée — <?php echo esc_html( $gplan ); ?></span>
				<div class="rcb-key-line">
						<code id="rcb-gen-key" class="rcb-selall"><?php echo esc_html( $gen_key ); ?></code>
					<button type="button" class="rcb-btn rcb-btn-primary rcb-btn-xs" data-copy="#rcb-gen-key">Copier</button>
					<a class="rcb-btn rcb-btn-ghost rcb-btn-xs" href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( 'Votre clé de licence Infinity RCB Pro — ' . $gplan ) . '&body=' . rawurlencode( $mail_body ) ); ?>">✉️ Préparer l’e-mail pour l’acheteur</a>
				</div>
				<p class="rcb-muted">Clé signée numériquement — impossible à falsifier. Copiez-la maintenant, elle n'est pas archivée en clair ici.</p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Gestionnaire de codes promo + bilan hebdo : site vendeur uniquement. -->
		<div class="rcb-card" id="rcb-promos">
			<div class="rcb-card-head">
				<h2>🏷️ Codes promo <span class="rcb-pill rcb-pill-gold">SITE VENDEUR</span></h2>
				<form method="post" action="" class="rcb-inline-form">
					<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
					<button type="submit" name="infinity_rcb_digest_now" value="1" class="rcb-btn rcb-btn-ghost" title="Envoyer immédiatement le récapitulatif des ventes">📈 Envoyer le bilan maintenant</button>
				</form>
			</div>
			<p class="rcb-muted" style="margin:0 0 14px;">Chaque code peut porter une <strong>date d'expiration</strong> et un <strong>quota d'utilisations</strong> : la remise est refusée automatiquement une fois expirée ou épuisée, et le compteur s'incrémente à chaque commande valide.</p>

			<?php $promos = $lic->get_promos(); ?>
			<?php if ( empty( $promos ) ) : ?>
				<p class="rcb-empty">Aucun code promo actif.</p>
			<?php else : ?>
				<div class="rcb-table-wrap rcb-table-scroll">
					<table class="rcb-table">
						<thead><tr><th>Code</th><th>Remise</th><th>Expire le</th><th>Utilisations</th><th>Statut</th><th></th></tr></thead>
						<tbody>
							<?php foreach ( $promos as $code => $promo ) :
								$simple = ! is_array( $promo );
								$pct   = $simple ? (int) $promo : (int) ( $promo['pct'] ?? 0 );
								$exp   = ! $simple && ! empty( $promo['expires'] ) ? $promo['expires'] : '';
								$used  = ! $simple ? (int) ( $promo['uses'] ?? 0 ) : 0;
								$max   = ! $simple ? (int) ( $promo['max_uses'] ?? 0 ) : 0;

								if ( $exp && current_time( 'Y-m-d' ) > $exp ) {
									$st = array( '⛔ Expiré', '#ef4444' );
								} elseif ( $max > 0 && $used >= $max ) {
									$st = array( '✓ Épuisé', '#f59e0b' );
								} else {
									$st = array( '● Actif', '#10b981' );
								}
								?>
								<tr>
									<td><code><?php echo esc_html( $code ); ?></code></td>
									<td><strong>−<?php echo (int) $pct; ?> %</strong></td>
									<td><?php echo $exp ? esc_html( date_i18n( 'd/m/Y', strtotime( $exp ) ) ) : '—'; ?></td>
									<td><?php echo (int) $used . ( $max > 0 ? ' / ' . (int) $max : '' ); ?></td>
									<td><span class="rcb-badge" style="background:<?php echo esc_attr( $st[1] ); ?>1a;color:<?php echo esc_attr( $st[1] ); ?>;"><?php echo esc_html( trim( $st[0] ) ); ?></span></td>
									<td>
										<form method="post" action="" class="rcb-inline-form">
											<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
											<input type="hidden" name="rcb_promo_code" value="<?php echo esc_attr( $code ); ?>">
											<button type="submit" name="infinity_rcb_promo_delete" value="1" class="rcb-btn rcb-btn-danger rcb-btn-xs" data-confirm="Supprimer le code <?php echo esc_attr( $code ); ?> ?">🗑️</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<form method="post" action="" class="rcb-generator-form" style="margin-top:14px;">
				<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-promo-code">Code (majuscules)</label>
						<input type="text" id="rcb-promo-code" name="rcb_promo_code" placeholder="LANCEMENT20" style="text-transform:uppercase;" required>
					</div>
					<div class="rcb-field">
						<label for="rcb-promo-pct">Remise (%)</label>
						<input type="number" id="rcb-promo-pct" name="rcb_promo_pct" min="1" max="90" value="20" required>
					</div>
					<div class="rcb-field">
						<label for="rcb-promo-expires">Expire le (facultatif)</label>
						<input type="date" id="rcb-promo-expires" name="rcb_promo_expires">
					</div>
					<div class="rcb-field">
						<label for="rcb-promo-max">Quota d'utilisations (0 = illimité)</label>
						<input type="number" id="rcb-promo-max" name="rcb_promo_max" value="0" min="0">
					</div>
				</div>
				<button type="submit" name="infinity_rcb_promo_add" value="1" class="rcb-btn rcb-btn-primary">🏷️ Enregistrer le code</button>
			</form>
		</div>
		<?php endif; ?>

	<!-- ===== Mes commandes ===== -->
	<div class="rcb-card" id="rcb-commandes">
		<div class="rcb-card-head">
			<h2>📦 Mes commandes <span class="rcb-pill rcb-pill-soft"><?php echo count( $orders ); ?></span></h2>
		</div>
		<?php if ( empty( $orders ) ) : ?>
			<p class="rcb-empty">Aucune commande pour le moment.<br>Utilisez le formulaire ci-dessus pour commander une licence.</p>
		<?php else : ?>
			<div class="rcb-table-wrap rcb-table-scroll">
				<table class="rcb-table rcb-order-table">
					<thead><tr><th>Référence</th><th>Plan</th><th>Acheteur</th><th>Montant</th><th>Statut</th><?php if ( $vendor ) : ?><th>Clé</th><?php endif; ?><th>Date</th><th>Actions</th></tr></thead>
					<tbody>
						<?php foreach ( $orders as $order ) :
							$order = wp_parse_args( is_array( $order ) ? $order : array(), array(
								'ref' => '', 'plan' => 'single', 'buyer' => '', 'email' => '',
								'amount_da' => 0, 'amount_eur' => 0, 'status' => 'pending', 'key' => '', 'created' => '',
							) );
							$status_labels = array(
								'pending'   => array( '⏳ En attente de paiement', '#f59e0b' ),
								'declared'  => array( '🕵️ Payée déclarée — à vérifier', '#0ea5e9' ),
								'delivered' => array( '✅ Livrée — clé activée', '#10b981' ),
								'paid'      => array( '✅ Payée — clé générée', '#10b981' ),
							);
							$sl = isset( $status_labels[ $order['status'] ] ) ? $status_labels[ $order['status'] ] : array( $order['status'], '#64748b' );
							?>
							<tr>
								<td><code><?php echo esc_html( $order['ref'] ); ?></code></td>
								<td><?php echo esc_html( $options['license']['tiers'][ $order['plan'] ]['label'] ?? $order['plan'] ); ?></td>
								<td><?php echo esc_html( $order['buyer'] ); ?><br><span class="rcb-muted"><?php echo esc_html( $order['email'] ); ?></span></td>
								<td><strong><?php echo number_format( (int) $order['amount_da'], 0, ',', ' ' ); ?> DA</strong></td>
								<td><span class="rcb-badge" style="background:<?php echo esc_attr( $sl[1] ); ?>1a;color:<?php echo esc_attr( $sl[1] ); ?>;"><?php echo esc_html( $sl[0] ); ?></span></td>
									<?php if ( $vendor ) : ?>
									<td>
										<?php if ( '' !== $order['key'] ) : ?>
											<code class="rcb-order-key rcb-selall" title="<?php echo esc_attr( $order['key'] ); ?>"><?php echo esc_html( $order['key'] ); ?></code>
											<button type="button" class="rcb-btn rcb-btn-ghost rcb-btn-xs" data-copy-copy="<?php echo esc_attr( $order['key'] ); ?>" title="Copier la clé">📋</button>
											<button type="submit" name="infinity_rcb_order_action" value="1" class="rcb-btn rcb-btn-ghost rcb-btn-xs" onclick="this.form.rcb_do.value='resend';" title="Renvoyer la clé à l’acheteur par e-mail">📧</button>
										<?php else : ?>—<?php endif; ?>
									</td>
									<?php endif; ?>
								<td class="rcb-nowrap"><?php echo esc_html( $order['created'] ); ?></td>
								<td class="rcb-nowrap">
									<a class="rcb-btn rcb-btn-ghost rcb-btn-xs" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license&ref=' . rawurlencode( $order['ref'] ) ) . '#rcb-commande' ); ?>" title="Ouvrir le suivi de cette commande (timeline, paiement, clé)">👁 Ouvrir</a>
									<a class="rcb-btn rcb-btn-ghost rcb-btn-xs" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-license&rcb-recu=' . rawurlencode( $order['ref'] ) ), 'infinity_rcb_export', 'rcb_nonce' ) ); ?>" target="_blank" rel="noopener" title="Reçu imprimable / PDF">🧾 Reçu</a>
									<?php if ( 'pending' === $order['status'] && $seller_mail ) : ?>
										<a class="rcb-btn rcb-btn-ghost rcb-btn-xs" href="<?php echo esc_url( 'mailto:' . $seller_mail . '?subject=' . rawurlencode( 'Relance commande ' . $order['ref'] . ' — Infinity RCB Pro' ) . '&body=' . rawurlencode( "Bonjour,\n\nJe n'ai pas encore reçu la clé pour la commande " . $order['ref'] . " passée le " . $order['created'] . ".\n\nMerci.\n" . $order['buyer'] ) ); ?>" title="Relancer le vendeur par e-mail">✉️ Relancer</a>
									<?php endif; ?>
									<form method="post" action="" class="rcb-inline-form">
										<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
										<input type="hidden" name="rcb_ref" value="<?php echo esc_attr( $order['ref'] ); ?>">
										<?php if ( $vendor && in_array( $order['status'], array( 'pending', 'declared' ), true ) ) : ?>
											<button type="submit" name="infinity_rcb_order_action" value="1" class="rcb-btn rcb-btn-ghost rcb-btn-xs" onclick="this.form.rcb_do.value='fulfill';" title="Marquer payée et générer la clé (site vendeur uniquement)">💰 Valider + clé</button>
										<?php elseif ( 'pending' === $order['status'] ) : ?>
											<button type="submit" name="infinity_rcb_order_action" value="1" class="rcb-btn rcb-btn-ghost rcb-btn-xs" onclick="this.form.rcb_do.value='declare';" title="Signaler le paiement au vendeur">✅ J'ai payé</button>
										<?php elseif ( 'declared' === $order['status'] ) : ?>
											<span class="rcb-muted">En vérification…</span>
										<?php endif; ?>
										<button type="submit" name="infinity_rcb_order_action" value="1" class="rcb-btn rcb-btn-danger rcb-btn-xs" onclick="this.form.rcb_do.value='delete';" data-confirm="Supprimer la commande <?php echo esc_attr( $order['ref'] ); ?> ?">🗑️</button>
										<input type="hidden" name="rcb_do" value="">
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="rcb-muted" style="margin-top:12px;">💡 Suivi de vos commandes passées depuis ce site : le statut passe automatiquement à <strong>« Livrée »</strong> dès que vous activez la clé reçue par e-mail.</p>
		<?php endif; ?>
	</div>

	<!-- ===== E-mails & diagnostic ===== -->
	<div class="rcb-card" id="rcb-emails">
		<div class="rcb-card-head">
			<h2>📧 E-mails &amp; diagnostic</h2>
			<form method="post" action="" class="rcb-inline-form">
				<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
				<button type="submit" name="infinity_rcb_mail_test" value="1" class="rcb-btn rcb-btn-primary">🧪 Tester l’envoi d’e-mails</button>
			</form>
		</div>
		<p class="rcb-muted" style="margin:0 0 14px;">Toutes les notifications (confirmation de commande, demande au vendeur, clé de licence, confirmation d’activation) sont envoyées automatiquement et journalisées ci-dessous. Le test envoie un e-mail à l’adresse d’administration du site : si rien n’arrive, l’hébergement bloque la fonction mail — installez un plugin SMTP (ex. WP Mail SMTP) ou utilisez les liens « ouvrir dans ma messagerie » de la confirmation de commande.</p>

		<?php $maillog = $lic->get_mail_log(); ?>
		<?php if ( empty( $maillog ) ) : ?>
			<p class="rcb-empty">Aucun envoi pour le moment.</p>
		<?php else : ?>
			<div class="rcb-table-wrap rcb-table-scroll">
				<table class="rcb-table">
					<thead><tr><th>Date</th><th>Type</th><th>Destinataire</th><th>Objet</th><th>Statut</th></tr></thead>
					<tbody>
						<?php
						$type_labels = array(
							'seller_request' => array( '📨 Commande (info vendeur)', '#f59e0b' ),
							'license_request'=> array( '🔑 Demande de licence (après paiement)', '#ef4444' ),
							'declared_ack'   => array( '🕵️ Accusé paiement client', '#0ea5e9' ),
							'buyer_confirm'  => array( '✉️ Confirmation acheteur', '#3b82f6' ),
							'key_delivery'   => array( '🔑 Clé de licence', '#10b981' ),
							'activation'     => array( '✅ Confirmation activation', '#8b5cf6' ),
							'release_code'   => array( '🔓 Code de libération', '#ec4899' ),
							'reminder'       => array( '⏰ Relance paiement', '#f97316' ),
							'nudge'          => array( '🔔 Rappel clés à livrer', '#f43f5e' ),
							'digest'         => array( '📈 Bilan hebdo', '#0ea5e9' ),
							'alert'          => array( '🚨 Alerte attaques', '#ef4444' ),
							'test'           => array( '🧪 Test', '#64748b' ),
						);
						foreach ( $maillog as $entry ) :
							$tl = isset( $type_labels[ $entry['type'] ] ) ? $type_labels[ $entry['type'] ] : array( $entry['type'], '#64748b' );
							?>
							<tr>
								<td class="rcb-nowrap"><?php echo esc_html( $entry['time'] ); ?></td>
								<td><span class="rcb-badge" style="background:<?php echo esc_attr( $tl[1] ); ?>1a;color:<?php echo esc_attr( $tl[1] ); ?>;"><?php echo esc_html( $tl[0] ); ?></span></td>
								<td><?php echo esc_html( $entry['to'] ); ?></td>
								<td class="rcb-ua"><?php echo esc_html( $entry['subject'] ); ?></td>
								<td><?php if ( $entry['ok'] ) : ?><span class="rcb-badge" style="background:#10b9811a;color:#10b981;">✅ Envoyé</span><?php else : ?><span class="rcb-badge" style="background:#ef44441a;color:#ef4444;">⛔ Échec</span><?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<form method="post" action="" style="margin-top:10px;">
				<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
				<button type="submit" name="infinity_rcb_clear_maillog" value="1" class="rcb-btn rcb-btn-danger rcb-btn-xs" data-confirm="Vider le journal des envois ?">🗑️ Vider le journal</button>
			</form>
		<?php endif; ?>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — <?php echo esc_html( $seller['name'] ); ?> · Licences à vie : 2 900 DA ≈ 11,90 € (1 site) · 4 800 DA ≈ 22,90 € (5 sites) · 12 000 DA ≈ 44,90 € (Agence)</p>
</div>

<!-- Modale de confirmation de commande -->
<div class="rcb-modal" id="rcb-confirm-modal" hidden>
	<div class="rcb-modal-box" role="dialog" aria-modal="true" aria-labelledby="rcb-confirm-title">
		<div class="rcb-modal-head">
			<span class="rcb-modal-logo"><?php printf( '%s', infinity_rcb_shield_svg( 'regular' ) ); ?></span>
			<h3 id="rcb-confirm-title">Confirmez votre commande</h3>
		</div>
		<ul class="rcb-syslist">
			<li><span>Plan</span><strong id="rcb-confirm-plan">—</strong></li>
			<li><span>Acheteur</span><strong id="rcb-confirm-buyer">—</strong></li>
			<li><span>E-mail (réception de la clé)</span><strong id="rcb-confirm-email">—</strong></li>
			<li><span>Code promo</span><strong id="rcb-confirm-promo">—</strong></li>
		</ul>
		<div class="rcb-confirm-amount">
			<small>Montant à régler après validation</small>
			<strong id="rcb-confirm-price">—</strong>
			<em id="rcb-confirm-promo-note" hidden>Remise du code appliquée à la validation.</em>
		</div>
		<div class="rcb-confirm-next">
			<span>1 · Payez<br><small>BaridiMob · CCP · carte · PayPal</small></span>
			<span>2 · « J'ai payé »<br><small>la demande part au vendeur</small></span>
			<span>3 · Clé par e-mail<br><small>envoyée automatiquement</small></span>
		</div>
		<div class="rcb-modal-actions">
			<button type="button" class="rcb-btn rcb-btn-primary" id="rcb-confirm-ok">✅ Confirmer la commande</button>
			<button type="button" class="rcb-btn rcb-btn-ghost" id="rcb-confirm-cancel">Modifier</button>
		</div>
	</div>
</div>
