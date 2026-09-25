<?php
/**
 * Page : À propos — présentation détaillée, fonctionnement technique,
 * compatibilité, licences (2 900 DA / 1 site et 4 800 DA / 5 sites),
 * feuille de route, journal des versions, FAQ et contact.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles, $notice, $cron_next
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tiers    = $options['license']['tiers'];
$features = array(
	array( '🖱️', 'Blocage du clic droit', 'Le menu contextuel est désactivé sur tout le site, avec message personnalisé et mention copyright.' ),
	array( '⌨️', 'Raccourcis clavier', 'F12, Ctrl+Shift+I / C / J, Ctrl+U, Ctrl+S et bien d’autres sont neutralisés avant tout autre script du thème.' ),
	array( '🛠️', 'Détection DevTools', 'L’ouverture des outils de développement est détectée en temps réel : flou du contenu ou redirection automatique.' ),
	array( '📋', 'Anti copier-coller', 'Copie, coupe, collage et tout-sélection bloqués en dehors des champs de saisie (formulaires préservés).' ),
	array( '🔤', 'Anti sélection', 'La sélection de texte est rendue impossible au niveau CSS et JavaScript, images comprises.' ),
	array( '🖐️', 'Anti glisser-déposer', 'Les images et liens ne peuvent plus être glissés hors de la page vers le bureau ou un autre onglet.' ),
	array( '🖨️', 'Anti impression', 'Ctrl+P bloqué et impression remplacée par un avertissement clair au lieu de votre contenu.' ),
	array( '📸', 'Anti capture d’écran', 'La touche PrintScreen est détectée et le presse-papiers vidé immédiatement.' ),
	array( '🎨', '10 styles de messages', 'Bleu, sombre, violet, vert, or, verre dépoli, néon, dégradé, minimal blanc, alerte rouge — plus couleurs personnalisées.' ),
	array( '©️', 'Copyright automatique', 'Mention de droits personnalisable sous chaque message : variables {annee}, {site} et {url} mises à jour automatiquement.' ),
	array( '📊', 'Tableau de bord avancé', 'Indicateurs temps réel, graphique d’activité 14/30 jours, répartition par type et état des protections.' ),
	array( '📜', 'Journaux détaillés', 'Chaque tentative consignée avec date, type, IP et navigateur — filtrable, recherchable, exportable CSV.' ),
	array( '🌐', '100 % responsive', 'Interface et messages parfaitement adaptés mobile, tablette et desktop, avec respect des préférences d’accessibilité.' ),
	array( '⚡', 'Ultra léger', 'Aucune dépendance externe : 1 fichier CSS (~7 Ko) et 1 fichier JS (~12 Ko), chargés seulement si la protection est active.' ),
	array( '🔒', 'Données 100 % locales', 'Aucune donnée envoyée à des services tiers : statistiques et journaux restent sur votre serveur.' ),
	array( '🌍', 'Prêt pour l’international', 'Text domain complet, fonctionnement vérifié sur WordPress 4.9 à 7.1 et PHP 7.0 à 8.3.' ),
);

$mechanisms = array(
	array( '🖱️ Clic droit', 'Évènement <code>contextmenu</code> intercepté en phase de capture', 'Menu bloqué + message + statistique' ),
	array( '⌨️ F12 / Ctrl+Shift+I / C', 'Évènement <code>keydown</code> intercepté avant le thème', 'Ouverture des outils dev bloquée' ),
	array( '🛠️ Outils de développement', 'Analyse périodique des dimensions <code>outerWidth/innerWidth</code>', 'Flou du contenu, redirection et alerte' ),
	array( '💻 Code source (Ctrl+U)', 'Évènement <code>keydown</code>', 'Affichage de la source bloqué' ),
	array( '💾 Enregistrer (Ctrl+S)', 'Évènement <code>keydown</code>', 'Enregistrement de page bloqué' ),
	array( '📋 Copier / couper / coller', 'Évènements <code>copy</code>, <code>cut</code>, <code>paste</code> + Ctrl+A', 'Presse-papiers neutralisé hors formulaires' ),
	array( '🔤 Sélection de texte', '<code>selectstart</code> + CSS <code>user-select: none</code>', 'Sélection impossible, champs de saisie préservés' ),
	array( '🖐️ Glisser-déposer', 'Évènement <code>dragstart</code>', 'Images et liens non déplaçables' ),
	array( '🖨️ Impression (Ctrl+P)', '<code>keydown</code> + <code>beforeprint</code> + feuille <code>@media print</code>', 'Page remplacée par un avertissement' ),
	array( '📸 Capture d’écran', 'Évènement <code>keyup</code> (PrintScreen)', 'Presse-papiers vidé + alerte' ),
	array( '⚙️ Console (Ctrl+Shift+J)', 'Avertissement <code>console</code> stylisé + raccourcis bloqués', 'Message dissuasif affiché dans la console' ),
);

$browsers = array(
	'Chrome / Edge' => '✔ Complet',
	'Firefox'       => '✔ Complet',
	'Safari'        => '✔ Complet',
	'Opera'         => '✔ Complet',
	'Samsung Internet' => '✔ Complet',
	'Mobile (iOS / Android)' => '✔ Complet',
);

$compat = array(
	'WordPress'    => '4.9 → 7.1',
	'PHP'          => '7.0 → 8.3',
	'Thèmes blocks (Gutenberg, FSE)' => '✔',
	'Thèmes classiques' => '✔',
	'Elementor, Divi, WPBakery, Beaver Builder' => '✔',
	'WooCommerce'  => '✔',
	'Multisite'    => '✔ (activation par site)',
	'WPML / Polylang' => '✔ (i18n prêt)',
	'Caching (LiteSpeed, WP Rocket, W3TC)' => '✔',
	'RTL'          => '✔ (mise en page neutre)',
);
?>
<div class="wrap rcb-wrap">

	<!-- ===== Hero ===== -->
	<div class="rcb-hero rcb-hero-about">
		<div class="rcb-hero-brand">
			<div class="rcb-logo rcb-logo-lg"><?php printf( '%s', infinity_rcb_shield_svg( 'large' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
			<div>
				<h1 class="rcb-hero-title">
					<?php printf( '%s', infinity_rcb_wordmark( 'hero' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?>
					<span class="rcb-pill rcb-pill-soft">v<?php echo esc_html( INFINITY_RCB_VERSION ); ?></span>
				</h1>
				<p>« Infinity RCB Pro » — Protect Your Content · Simple · Powerful · Lightweight</p>
				<div class="rcb-hero-badges">
					<span class="rcb-pill rcb-pill-soft">WordPress 4.9+</span>
					<span class="rcb-pill rcb-pill-soft">PHP 7.0+</span>
					<span class="rcb-pill rcb-pill-soft">GPL v2+</span>
					<span class="rcb-pill rcb-pill-gold">Licence à vie — dès 2 900 DA</span>
				</div>
			</div>
		</div>
	</div>


	<!-- ===== Navigation rapide ===== -->
	<div class="rcb-nav-chips">
		<a class="rcb-nav-chip" href="#rcb-about-presentation">📌 À propos</a>
		<a class="rcb-nav-chip" href="#rcb-about-mecanismes">⚙️ Mécanismes</a>
		<a class="rcb-nav-chip" href="#rcb-about-licences">💎 Licences</a>
		<a class="rcb-nav-chip" href="#rcb-about-fonctions">✨ Fonctionnalités</a>
		<a class="rcb-nav-chip" href="#rcb-about-technique">🔧 Technique</a>
		<a class="rcb-nav-chip" href="#rcb-integrity">🔒 Intégrité</a>
		<a class="rcb-nav-chip" href="#rcb-about-versions">🗂️ Versions</a>
	</div>

	<!-- ===== Identité de marque (repliable) ===== -->
	<details class="rcb-about-details">
		<summary>🎨 Identité de marque &amp; charte graphique <small>(cliquer pour déplier)</small></summary>
		<div class="rcb-card" style="margin-top:14px;">
		<div class="rcb-brand-grid">
			<div class="rcb-brand-demo rcb-brand-demo-dark">
				<span class="rcb-brand-label">Lockup sur fond sombre</span>
				<div class="rcb-brand-row"><?php printf( '%s', infinity_rcb_shield_svg( 'regular' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?><?php printf( '%s', infinity_rcb_wordmark( 'hero' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
				<span class="rcb-brand-tag">Protect Your Content</span>
			</div>
			<div class="rcb-brand-demo">
				<span class="rcb-brand-label">Lockup sur fond clair</span>
				<div class="rcb-brand-row"><?php printf( '%s', infinity_rcb_shield_svg( 'regular' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?><?php printf( '%s', infinity_rcb_wordmark( 'light' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
				<span class="rcb-brand-tag rcb-brand-tag-light">Simple · Powerful · Lightweight</span>
			</div>
		</div>
		<div class="rcb-palette">
			<?php
			$palette = array(
				array( '#2E7BF6', 'Bleu Infinity' ),
				array( '#7C3AED', 'Violet Blocker' ),
				array( '#0D1230', 'Bleu nuit' ),
				array( '#F43F5E', 'Rouge Interdiction' ),
				array( '#EDEBFF', 'Lavande claire' ),
				array( '#8A8F9E', 'Gris Droits' ),
			);
			foreach ( $palette as $sw ) : ?>
				<div class="rcb-swatch">
					<span class="rcb-swatch-color" style="background:<?php echo esc_attr( $sw[0] ); ?>;"></span>
					<strong><?php echo esc_html( $sw[0] ); ?></strong>
					<small><?php echo esc_html( $sw[1] ); ?></small>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="rcb-muted" style="margin:12px 0 0;">Logo, icône de menu, icônes des messages et bannières de distribution suivent cette charte. Fichiers : <code>assets/icon.svg</code>, <code>icon-128.png</code>, <code>icon-256.png</code>, <code>banner-1544x500.png</code>, <code>banner-772x250.png</code>.</p>
	</div>

	</details>

	<!-- ===== Présentation ===== -->
	<div class="rcb-card" id="rcb-about-presentation">
		<div class="rcb-card-head"><h2>📌 À propos du plugin</h2></div>
		<div class="rcb-prose">
			<p><strong>Infinity RCB Pro</strong> est une solution complète de protection de contenu développée par <?php if ( $options['developer']['website'] ) : ?><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php endif; ?><?php echo esc_html( $options['developer']['name'] ); ?><?php if ( $options['developer']['website'] ) : ?></a><?php endif; ?>, conçue pour les créateurs, photographes, rédacteurs, e-commerçants et agences qui publient du contenu de valeur et veulent limiter son vol : textes, images, prix, fiches produits ou articles de blog.</p>
			<p>Le principe est simple : <strong>toutes les méthodes courantes d’extraction de contenu sont neutralisées</strong> — clic droit, raccourcis d’inspection, copier-coller, sélection, glisser-déposer, impression et captures d’écran — avec un message d’avertissement professionnel, personnalisable dans sa forme comme dans son texte, assorti d’une mention copyright automatique (© année, nom du site).</p>
			<p>Chaque tentative de contournement est <strong>enregistrée et analysée</strong> : statistiques temps réel, graphiques d’activité, répartition par type d’attaque, adresses IP les plus actives et journaux complets exportables en CSV. Vous savez ainsi qui tente de copier votre contenu, quand et comment.</p>
			<p>Le plugin est pensé pour durer : code sans dépendance (aucun framework externe), compatibilité large (WordPress 4.9 → 7.1, PHP 7.0 → 8.3, tous les grands thèmes et page builders), et confidentialité totale — rien n’est envoyé vers l’extérieur, tout reste sur votre serveur.</p>
		</div>
	</div>

	<!-- ===== Mécanismes ===== -->
	<div class="rcb-card" id="rcb-about-mecanismes">
		<div class="rcb-card-head"><h2>⚙️ Mécanismes de protection</h2></div>
		<p class="rcb-muted" style="margin:0 0 14px;">Chaque mécanisme s’appuie sur les évènements standard du navigateur, interceptés <strong>en phase de capture</strong> pour passer avant les scripts des thèmes et page builders — c’est ce qui garantit le blocage quel que soit le thème utilisé.</p>
		<div class="rcb-table-wrap">
			<table class="rcb-table">
				<thead><tr><th>Menace</th><th>Mécanisme navigateur</th><th>Résultat pour le visiteur</th></tr></thead>
				<tbody>
					<?php foreach ( $mechanisms as $m ) : ?>
						<tr>
							<td><strong><?php echo wp_kses_post( $m[0] ); ?></strong></td>
							<td><?php echo wp_kses( $m[1], array( 'code' => array() ) ); ?></td>
							<td><?php echo esc_html( $m[2] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- ===== Compatibilité ===== -->
	<div class="rcb-grid-2">
		<div class="rcb-card">
			<div class="rcb-card-head"><h2>🧩 Compatibilité</h2></div>
			<div class="rcb-table-wrap">
				<table class="rcb-table">
					<thead><tr><th>Environnement</th><th>Support</th></tr></thead>
					<tbody>
						<?php foreach ( $compat as $env => $support ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $env ); ?></strong></td>
								<td class="rcb-ok-text"><?php echo esc_html( $support ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="rcb-muted" style="margin:12px 0 0;">Installé ici : WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?> · PHP <?php echo esc_html( PHP_VERSION ); ?></p>
		</div>
		<div class="rcb-card">
			<div class="rcb-card-head"><h2>🌐 Navigateurs testés</h2></div>
			<ul class="rcb-syslist">
				<?php foreach ( $browsers as $browser => $support ) : ?>
					<li><span><?php echo esc_html( $browser ); ?></span><strong class="rcb-ok-text"><?php echo esc_html( $support ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
			<p class="rcb-muted" style="margin-top:12px;">Le message d’avertissement respecte <code>prefers-reduced-motion</code> : les animations sont réduites pour les visiteurs sensibles au mouvement.</p>
		</div>
	</div>

	<!-- ===== Licences ===== -->
	<div class="rcb-card rcb-price-card" id="rcb-about-licences">
		<div class="rcb-card-head"><h2>💎 Licences à vie — achat symbolique</h2></div>
		<div class="rcb-plans">
			<?php foreach ( $tiers as $key => $tier ) :
				$is_current = $options['license']['plan'] === $key;
				?>
				<div class="rcb-plan <?php echo 'five' === $key ? 'rcb-plan-featured' : ''; ?>">
					<?php if ( 'five' === $key ) : ?><span class="rcb-plan-tag">Meilleure offre</span><?php endif; ?>
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
					<?php if ( $is_current ) : ?>
						<span class="rcb-pill rcb-pill-ok">✔ Plan actif</span>
					<?php else : ?>
						<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=general' ) ); ?>">Choisir ce plan</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="rcb-price-side">
			<p>Ces prix symboliques soutiennent le développement et la maintenance du plugin. <strong>Commande, paiement et activation se gèrent directement depuis la page <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">Licence &amp; Achat</a></strong> : vous créez la commande, vous payez par BaridiMob, CCP, Edahabia, virement ou PayPal, puis vous activez la clé reçue par e-mail.</p>
			<div class="rcb-pay-chips">
				<span class="rcb-chip">📱 BaridiMob</span>
				<span class="rcb-chip">💳 Carte Edahabia</span>
				<span class="rcb-chip">📮 CCP</span>
				<span class="rcb-chip">🏦 Virement bancaire</span>
				<span class="rcb-chip">🅿️ PayPal</span>
			</div>
			<?php if ( ! empty( $options['license']['buyer'] ) || ! empty( $options['license']['purchase_date'] ) || ! empty( $options['license']['license_key'] ) ) : ?>
				<ul class="rcb-syslist">
					<?php if ( ! empty( $options['license']['buyer'] ) ) : ?>
						<li><span>Licencié à</span><strong><?php echo esc_html( $options['license']['buyer'] ); ?></strong></li>
					<?php endif; ?>
					<?php if ( ! empty( $options['license']['purchase_date'] ) ) : ?>
						<li><span>Date d’achat</span><strong><?php echo esc_html( $options['license']['purchase_date'] ); ?></strong></li>
					<?php endif; ?>
					<?php if ( ! empty( $options['license']['license_key'] ) ) : ?>
						<li><span>Clé de licence</span><strong><code><?php echo esc_html( $options['license']['license_key'] ); ?></code></strong></li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $options['developer']['email'] ) : ?>
				<a class="rcb-btn rcb-btn-primary" href="mailto:<?php echo esc_attr( $options['developer']['email'] ); ?>?subject=Achat%20licence%20Infinity%20RCB%20Pro">📩 Acheter / contacter pour l’achat</a>
			<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
				<span class="rcb-muted">E-mail du vendeur non configuré — Réglages → Général → Développeur.</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- ===== Fonctionnalités ===== -->
	<div class="rcb-card" id="rcb-about-fonctions">
		<div class="rcb-card-head"><h2>✨ Fonctionnalités</h2></div>
		<div class="rcb-features-grid">
			<?php foreach ( $features as $feature ) : ?>
				<div class="rcb-feature">
					<span class="rcb-feature-ico"><?php echo esc_html( $feature[0] ); ?></span>
					<strong><?php echo esc_html( $feature[1] ); ?></strong>
					<p><?php echo esc_html( $feature[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ===== Technique & Développeur ===== -->
	<div class="rcb-grid-2" id="rcb-about-technique">
		<div class="rcb-card">
			<div class="rcb-card-head"><h2>🔧 Informations techniques</h2></div>
			<ul class="rcb-syslist">
				<li><span>Nom complet</span><strong>Right Click Blocker PRO (Infinity RCB Pro)</strong></li>
				<li><span>Version installée</span><strong><?php echo esc_html( INFINITY_RCB_VERSION ); ?></strong></li>
				<li><span>Auteur</span><strong><?php echo esc_html( $options['developer']['name'] ); ?></strong></li>
				<?php if ( $options['developer']['website'] ) : ?>
				<li><span>Site officiel</span><strong><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $options['developer']['website'] ); ?></a></strong></li>
				<?php endif; ?>
				<li><span>Licence</span><strong>GPL v2 ou ultérieure</strong></li>
				<li><span>WordPress compatible</span><strong>4.9 → 6.8</strong> <em class="rcb-muted">(installé : <?php echo esc_html( get_bloginfo( 'version' ) ); ?>)</em></li>
				<li><span>PHP compatible</span><strong>7.0 → 8.3</strong> <em class="rcb-muted">(installé : <?php echo esc_html( PHP_VERSION ); ?>)</em></li>
				<li><span>Text domain</span><strong>infinity-rcb-pro</strong></li>
				<li><span>Dossier des journaux</span><strong class="rcb-ua"><?php echo esc_html( $log_stats['dir'] ); ?></strong></li>
				<li><span>Statistiques stockées</span><strong><?php echo number_format( (int) $summary['total'], 0, ',', ' ' ); ?> tentatives</strong></li>
				<li><span>Poids côté visiteur</span><strong>1 CSS (~7 Ko) + 1 JS (~12 Ko)</strong></li>
				<li><span>Dépendances externes</span><strong>Aucune</strong></li>
			</ul>
		</div>

		<div class="rcb-card">
			<div class="rcb-card-head"><h2>👤 Développeur &amp; contact</h2></div>
			<div class="rcb-dev-card">
				<div class="rcb-dev-avatar"><?php printf( '%s', infinity_rcb_shield_svg( 'regular', 'plain' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
				<div>
					<h3><?php echo esc_html( $options['developer']['name'] ); ?></h3>
					<p class="rcb-muted">Développement web &amp; plugins WordPress sur mesure</p>
				</div>
			</div>
			<ul class="rcb-syslist">
				<?php if ( $options['developer']['website'] ) : ?>
				<li><span>Site web</span><strong><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $options['developer']['website'] ); ?></a></strong></li>
				<?php endif; ?>
				<?php if ( $options['developer']['email'] ) : ?>
				<li><span>E-mail</span><strong><a href="mailto:<?php echo esc_attr( $options['developer']['email'] ); ?>"><?php echo esc_html( $options['developer']['email'] ); ?></a></strong></li>
				<?php endif; ?>
				<?php if ( ! empty( $options['developer']['phone'] ) ) : ?>
					<li><span>Téléphone</span><strong><?php echo esc_html( $options['developer']['phone'] ); ?></strong></li>
				<?php endif; ?>
				<?php if ( ! empty( $options['developer']['address'] ) ) : ?>
					<li><span>Adresse</span><strong><?php echo esc_html( $options['developer']['address'] ); ?></strong></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<!-- ===== Feuille de route (repliable) ===== -->
	<details class="rcb-about-details">
		<summary>🗺️ Feuille de route <small>(cliquer pour déplier)</small></summary>
		<div class="rcb-card" style="margin-top:14px;">
		<div class="rcb-roadmap">
			<div class="rcb-roadmap-step"><span class="rcb-pill rcb-pill-soft">T4 2026</span><div><strong>Exports PDF &amp; alertes e-mail</strong><p>Rapports statistiques en PDF et notification automatique au-delà d’un seuil de tentatives.</p></div></div>
			<div class="rcb-roadmap-step"><span class="rcb-pill rcb-pill-soft">T1 2027</span><div><strong>Règles par rôle et par page</strong><p>Protection différenciée selon le rôle (abonné, client…) et exclusion de pages spécifiques.</p></div></div>
			<div class="rcb-roadmap-step"><span class="rcb-pill rcb-pill-soft">T2 2027</span><div><strong>Mode verrouillage renforcé &amp; géo-restriction</strong><p>Blocage total après X tentatives et restriction par pays.</p></div></div>
			<div class="rcb-roadmap-step"><span class="rcb-pill rcb-pill-soft">Idées</span><div><strong>API REST des statistiques &amp; watermarks sur images</strong><p>Intégrations externes et filigranes automatiques.</p></div></div>
		</div>
	</div>

	</details>

	<!-- ===== Intégrité des fichiers ===== -->
	<?php
	$integrity_status = array( 'status' => 'no-manifest' );
	if ( isset( $rcb_admin ) && is_object( $rcb_admin ) && method_exists( $rcb_admin, 'integrity_check' ) ) {
		$integrity_status = $rcb_admin->integrity_check();
	}
	?>
	<div class="rcb-card" id="rcb-integrity">
		<div class="rcb-card-head">
			<h2>🔒 Intégrité des fichiers <span class="rcb-pill rcb-pill-soft">anti-altération</span></h2>
		</div>
		<?php if ( 'ok' === $integrity_status['status'] ) : ?>
			<div class="rcb-notice rcb-notice-ok">✅ Installation intègre : <?php printf( '%d/%d fichiers conformes au manifeste de la release.', (int) $integrity_status['ok'], (int) $integrity_status['total'] ); ?></div>
			<p class="rcb-muted" style="margin:10px 0 0;">Chaque fichier est comparé au manifeste SHA-256 généré à la publication officielle (GitHub Actions). Une copie ou un zip modifié avant redistribution serait signalé ici.</p>
		<?php elseif ( 'tampered' === $integrity_status['status'] ) : ?>
			<div class="rcb-notice rcb-notice-warn">⚠️ Fichiers différents de la release officielle — cette copie a été modifiée. Fichiers concernés :</div>
			<ul class="rcb-syslist" style="margin-top:10px;">
				<?php foreach ( array_slice( (array) $integrity_status['modified'], 0, 8 ) as $int_file ) : ?>
					<li><span>Modifié</span><strong><code><?php printf( '%s', esc_html( $int_file ) ); ?></code></strong></li>
				<?php endforeach; ?>
				<?php foreach ( array_slice( (array) $integrity_status['missing'], 0, 5 ) as $int_file ) : ?>
					<li><span>Manquant</span><strong><code><?php printf( '%s', esc_html( $int_file ) ); ?></code></strong></li>
				<?php endforeach; ?>
			</ul>
			<p class="rcb-muted" style="margin:10px 0 0;">Téléchargez à nouveau le zip officiel depuis la <a href="https://github.com/derouicheoussama/right-click-blocker-pro/releases" target="_blank" rel="noopener">page des releases</a> (vérifiez SHA256SUMS.txt) ou depuis WordPress.org.</p>
		<?php else : ?>
			<div class="rcb-notice rcb-notice-warn">ℹ️ Manifeste d'intégrité absent (installation développeur ou installation manuelle hors release). Les installations officielles GitHub / WordPress.org embarquent automatiquement le manifeste SHA-256.</div>
		<?php endif; ?>
	</div>

	<!-- ===== Journal des versions (repliable) ===== -->
	<details class="rcb-about-details" id="rcb-about-versions">
		<summary>🗂️ Journal des versions <small>(cliquer pour déplier l'historique complet)</small></summary>
		<div class="rcb-card rcb-changelog-card" style="margin-top:14px;">
		<div class="rcb-changelog">
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.22.1</span>
				<div>
					<strong>Optimisation générale + licence nag</strong>
					<ul>
						<li>Bandeau non-licencié dismissible (7 jours) + alertes e-mail réservées aux licenciés.</li>
						<li>Page À propos : FAQ harmonisée, compatibilité WP 7.1, 16 protections.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.22.0</span>
				<div>
					<strong>Licence nag conforme wp.org</strong>
					<ul>
						<li>Bandeau « non-licencié » dismissible + footer + alertes e-mail gated.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.21.0</span>
				<div>
					<strong>Filigrane réglable + audit sécurité</strong>
					<ul>
						<li>Opacité (10-100%) et taille (8-48px) du filigrane réglables par curseurs. Audit : 0 erreur, 0 faille.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.17.0</span>
			</div>
			<div>
				<strong>Commande guidée + notifications temps réel</strong>
				<ul>
					<li>Popup automatique après commande (réf, montant, étapes, copier) et après « J&#8217;ai payé ».</li>
					<li>Surveillance 25 s : popup « votre clé est arrivée » avec copie + activation en 1 clic.</li>
				</ul>
			</div>
		</div>
		<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.15.0</span>
			</div>
			<div>
				<strong>Modale de commande repensée</strong>
				<ul>
					<li>En-tête de marque, montant en avant, étapes suivantes (payer → « J’ai payé » → clé), focus clavier et état d’envoi.</li>
					<li>Bouton de commande dynamique (plan + prix) et wording corrigé (page Licence et boutique publique).</li>
				</ul>
			</div>
		</div>
		<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.5</span>
			</div>
			<div>
				<strong>Vérification de mise à jour plus claire</strong>
				<ul>
					<li>Sans dépôt GitHub configuré : confirmation neutre « WordPress.org automatique » (plus de faux avertissement).</li>
					<li>L’avertissement n’apparaît que si un dépôt configuré est injoignable.</li>
				</ul>
			</div>
		</div>
		<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.4</span>
			</div>
			<div>
				<strong>Conformité Plugin Check</strong>
				<ul>
					<li>Updater GitHub exclu du build wp.org (chargé seulement si présent), désinstallation via WP_Filesystem.</li>
					<li>Fonctions fichiers, dates et échappements conformes ; readme en anglais, Tested up to 7.1.</li>
				</ul>
			</div>
		</div>
		<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.3</span>
				<div>
					<strong>Aperçu en comportement réel</strong>
					<ul>
						<li>Le message apparaît, reste la durée configurée puis disparaît — comme pour vos visiteurs.</li>
						<li>Croix fonctionnelle, bip sonore au rejouer, bouton « Rejouer » dans le cadre quand le message est masqué.</li>
						<li>Chaque réglage rejoue la séquence en temps réel.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.2</span>
				<div>
					<strong>Fiche extension enrichie (page Extensions)</strong>
					<ul>
						<li>Actions directes : Tableau de bord, Réglages, Licence et Supprimer (confirmation + flux natif).</li>
						<li>Notes d'état : pastilles protection X/11 et licence, canal de mise à jour, lien documentation.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.1</span>
				<div>
					<strong>Aperçu élargi</strong>
					<ul>
						<li>Colonne d'aperçu adaptative (jusqu'à 600 px) et cadre navigateur plus spacieux dans l'onglet Apparence.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.14.0</span>
				<div>
					<strong>Double canal de mise à jour : wp.org + GitHub</strong>
					<ul>
						<li>WordPress.org automatique et prioritaire dès la publication.</li>
						<li>GitHub Releases pour les installations manuelles — désactivation automatique dès que wp.org gère l'extension.</li>
						<li>Carte « Mises à jour » (statut, vérification immédiate, dépôt configurable).</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.13.2</span>
				<div>
					<strong>Apparence : personnalisation et aperçu sur un seul écran</strong>
					<ul>
						<li>Deux colonnes : formulaire à gauche, aperçu collant à droite — plus besoin de faire défiler pour voir les modifications en temps réel.</li>
						<li>Sur mobile, l'aperçu se place au-dessus du formulaire.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.13.1</span>
				<div>
					<strong>Aperçu du message dans l'onglet Apparence</strong>
					<ul>
						<li>La carte d'aperçu s'installe en tête de Réglages → Apparence et suit le formulaire <strong>en direct</strong> (styles, couleurs, durée, copyright, position…).</li>
						<li>Les pastilles Styles/Position mettent le formulaire à jour — deux sens de synchronisation.</li>
						<li>La carte reste aussi disponible sur le tableau de bord.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.13.0</span>
				<div>
					<strong>Page Licence &amp; Achat fluidifiée</strong>
					<ul>
						<li>Correctif : le plan choisi est désormais surligné dans le formulaire de commande.</li>
						<li>Clés : sélection complète en un clic, troncature propre dans le tableau avec info-bulle.</li>
						<li>Boutons « Ouvrir » (suivi timeline d'une commande) et « Renvoyer la clé » à l'acheteur.</li>
						<li>Navigation rapide par ancres + confirmations précises après chaque action.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.12.2</span>
				<div>
					<strong>Aperçu du message sur le tableau de bord</strong>
					<ul>
						<li>Carte « 👀 Aperçu du message » : rendu exact du message visiteur (style, couleurs, copyright, progression) dans un cadre navigateur.</li>
						<li>Test en direct : 10 styles, 3 positions, 11 messages et bouton « Rejouer » — sans toucher aux réglages.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.12.1</span>
				<div>
					<strong>Renommage « Right Click Blocker PRO »</strong>
					<ul>
						<li>Le plugin s'affiche désormais sous le nom « Right Click Blocker PRO – Right Click &amp; Content Protection ».</li>
						<li>Badge <code>PRO</code> en dégradé bleu dans le titre de la liste des extensions.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.12.0</span>
				<div>
					<strong>Parcours de commande automatisé : « J'ai payé »</strong>
					<ul>
						<li>Nouvelle étape « J'ai payé » : le vendeur reçoit la demande de licence uniquement après le paiement déclaré (bouton boutique, page Licence ou déclaration tardive par référence + e-mail).</li>
						<li>Timeline de suivi Commandée → Payée → Vérifiée → Clé reçue, statut « à vérifier » et KPI vendeur dédié.</li>
						<li>Accusé de paiement au client + rappel quotidien des clés à livrer (plus de 24 h).</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.11.0</span>
				<div>
					<strong>Croissance commerciale et durcissement de la protection</strong>
					<ul>
						<li>Ventes : WhatsApp « Commander », codes promo avancés (expiration + quota), relances J+2/J+5 et bilan hebdo vendeur.</li>
						<li>Paiement carte CIB / Edahabia via Chargily Pay (site vendeur) : livraison de la clé 100 % automatique par webhook signé.</li>
						<li>Protection : filigrane d'images, anti-clickjacking (X-Frame-Options + frame-busting), alerte e-mail sur pic d'attaques, exclusions par rôle.</li>
						<li>Expérience : widget tableau de bord WordPress, import/export JSON des réglages, bloc Gutenberg « Démo de protection ».</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.10.1</span>
				<div>
					<strong>E-mails professionnels HTML et délivrabilité</strong>
					<ul>
						<li>Les 6 e-mails du plugin (confirmation, demande vendeur, clé, activation, libération, test) adoptent un gabarit HTML structuré aux couleurs de la marque.</li>
						<li>Alternative texte brut (multipart/alternative) + en-têtes optimisés (From aligné sur le domaine, Reply-To) pour atterrir en boîte de réception.</li>
						<li>Contact vendeur par défaut : <code>hi@infinitycoder.dev</code> (migration automatique des anciennes adresses).</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.10.0</span>
				<div>
					<strong>Assistant de démarrage, protection mobile et durcissement images</strong>
					<ul>
						<li>Carte de bienvenue à 3 étapes au premier démarrage (dismissible).</li>
						<li>Protection mobile : long-press iOS/Android bloqué, callout d'image Safari neutralisé.</li>
						<li>Durcissement des images : <code>pointer-events: none</code> avec exemption <code>data-rcb-exempt</code>.</li>
						<li>Bandeau noscript + lien rapide dans la barre d'administration WordPress.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.9.2</span>
				<div>
					<strong>Conformité WordPress.org</strong>
					<ul>
						<li>Suppression de toutes les URI data: (icônes servies depuis des fichiers SVG embarqués).</li>
						<li>Notes de conformité explicites : la licence optionnelle ne limite ni n’active aucune fonctionnalité (toutes les protections sont libres).</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.9.1</span>
				<div>
					<strong>Démo publique interactive [rcb_demo]</strong>
					<ul>
						<li>11 tests réels à essayer (clic droit, sélection, copie, glisser image et lien, Ctrl+U/S/P, F12, PrintScreen, détection DevTools armée au clic), journal en direct et compteur de tentatives.</li>
						<li>Galerie cliquable des 10 styles de messages avec aperçu instantané.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.9.0</span>
				<div>
					<strong>Achat &amp; licence : boutique publique, Agence, promos et robustesse</strong>
					<ul>
						<li>Shortcodes [rcb_plans] et [rcb_commande] : vendre les licences depuis le site du vendeur, sans installation préalable chez l’acheteur.</li>
						<li>Nouveau palier Licence Agence : 20 noms de domaine (12 000 DA ≈ 44,90 €), support dédié 36 mois.</li>
						<li>Codes promo, préremplissage du formulaire + fenêtre de confirmation, relance par e-mail et reçu imprimable par commande.</li>
						<li>Robustesse : e-mail « gardez votre clé » à l’activation, journal des activations, libération des créneaux orphelins par code e-mail, anti force-brute (5 essais/heure), clés révocables, livraison e-mail automatique après « Valider + clé », export CSV des commandes et tableau de bord des ventes côté vendeur.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.8.1</span>
				<div>
					<strong>Suivi client des commandes</strong>
					<ul>
						<li>Le statut d’une commande passe automatiquement à « Livrée — clé activée » dès que la clé reçue par e-mail est activée.</li>
						<li>Colonne « Clé » du tableau réservée au site vendeur ; statuts et textes clarifiés côté client.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.8.0</span>
				<div>
					<strong>Parcours licence 100 % client</strong>
					<ul>
						<li>4 étapes simples : choisir, payer, recevoir la clé par e-mail, activer — plus aucune mention de mécanique vendeur côté client.</li>
						<li>Montée en gamme guidée : encart « Passer à la licence 5 Sites » et bouton dédié pour remplacer une licence Mono-Site.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.7.1</span>
				<div>
					<strong>Préparation publication WordPress.org</strong>
					<ul>
						<li>Nom du plugin optimisé pour la recherche (Right Click &amp; Content Protection).</li>
						<li>Fiche readme complète : atouts, tableau « inclus gratuitement », FAQ étendue, 4 captures.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.7.0</span>
				<div>
					<strong>Réglages 100 % client + nouveau logo</strong>
					<ul>
						<li>Onglet Paiement, carte Licence et carte Développeur retirés des installations clientes — réservés au site du développeur (mode vendeur).</li>
						<li>Les coordonnées de paiement affichées aux acheteurs proviennent des valeurs par défaut du plugin, en lecture seule.</li>
						<li>Nouveau logo officiel : bouclier à bande dégradée bleu → violet, panneau blanc, souris droite avec molette, barre d’interdiction dégradée rouge → rose ; icônes, bannières et captures régénérées.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.6.0</span>
				<div>
					<strong>Aucune interface vendeur côté client</strong>
					<ul>
						<li>Le générateur de clés n’existe plus dans l’interface des sites clients : il n’apparaît que sur le site du développeur, activé par une constante wp-config.</li>
						<li>Parcours client recentré : activer une licence, demander une licence au développeur (e-mail automatique), mises à jour automatiques du plugin via WordPress.org.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.5.0</span>
				<div>
					<strong>Licences signées numériquement — génération réservée au développeur</strong>
					<ul>
						<li>Clés protégées par signature ECDSA P-256 : le plugin ne contient que la clé <strong>publique</strong> ; aucune génération possible côté client, falsification et élévation de plan rejetées.</li>
						<li>Espace vendeur verrouillé : la clé privée s’installe uniquement sur le site du développeur et active le générateur de clés signées.</li>
						<li>Logo officiel affiché dans la liste des extensions WordPress.</li>
						<li>Captures d’écran de la fiche (tableau de bord, licence &amp; achat, réglages).</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.4.0</span>
				<div>
					<strong>Flux de commande par e-mail, générateur de clés vendeur</strong>
					<ul>
						<li>Chaque commande envoie automatiquement la demande au vendeur (Reply-To = acheteur) et une confirmation avec instructions de paiement à l’acheteur — plus un bouton de renvoi et un secours par lien e-mail.</li>
						<li>Nouveau générateur de clés (VENDEUR) : plan au choix, clé sécurisée affichée avec copie et e-mail d’envoi pré-rempli.</li>
						<li>Parcours CLIENT / VENDEUR expliqué étape par étape sur la page Licence &amp; Achat.</li>
						<li>Auteur : Infinity Coder ; nom de plugin conforme aux règles WordPress.org.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.3.1</span>
				<div>
					<strong>Charte de marque officielle complète</strong>
					<ul>
						<li>Logo affiné selon la charte : bouclier blanc plein, souris bleu nuit inclinée, barre d’interdiction rouge épaisse à bouts arrondis.</li>
						<li>Wordmark officiel INFINITY / RIGHT CLICK BLOCKER (Y violet, RIGHT CLICK gris, BLOCKER violet) dans les en-têtes du tableau de bord et de la page À propos.</li>
						<li>Nouvelle section « Identité de marque » : lockups clair/sombre, palette officielle et taglines.</li>
						<li>Bannières WordPress.org générées : banner-1544×500 et banner-772×250 (SVG + PNG), icônes régénérées.</li>
						<li>Teinte lavande #EDEBFF intégrée aux pastilles et survols de l’interface.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.3.0</span>
				<div>
					<strong>Identité visuelle officielle du plugin</strong>
					<ul>
						<li>Nouveau logo : tuile arrondie dégradé bleu → violet, bouclier à contour blanc, souris bleu nuit barrée d’une diagonale rouge.</li>
						<li>Icône de menu WordPress personnalisée (SVG haute résolution), icônes des messages refaites à l’identité.</li>
						<li>Palette harmonisée sur toutes les pages : dégradés bleu → violet, graphique en bleu, accents et anneaux de focalisation recalibrés.</li>
						<li>Fichiers d’icône de distribution : <code>assets/icon.svg</code>, <code>icon-128.png</code>, <code>icon-256.png</code>.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.2.1</span>
				<div>
					<strong>Interface élargie et harmonisée</strong>
					<ul>
						<li>Pages étendues jusqu’à 1680 px : tableaux, graphiques et grilles exploitent toute la largeur disponible.</li>
						<li>Journaux et commandes : en-tête de tableau collant au défilement, colonnes calibrées, colonne navigateur enfin lisible.</li>
						<li>Harmonisation générale : héros, cartes KPI, boutons, zébrage des lignes et crédits de pied de page sur toutes les pages.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.2.0</span>
				<div>
					<strong>Système de licence, paiement et optimisation vitesse</strong>
					<ul>
						<li>Système de licence complet : clés sécurisées (somme de contrôle), activation par nom de domaine avec quota 1 ou 5 sites, désactivation libérant un créneau.</li>
						<li>Nouvelle page « Licence &amp; Achat » : statut en direct, plans, formulaire de commande, instructions de paiement, bouton PayPal réel et suivi des commandes.</li>
						<li>Génération de clés par le vendeur (« Valider + clé ») — boucle paiement → clé → activation 100 % intégrée.</li>
						<li>Réglages enrichis : préréglages Doux / Équilibré / Maximum, exclusion de pages et des administrateurs, arrondi, taille de texte, ombre et CSS personnalisé.</li>
						<li>Performance : statistiques groupées en une seule écriture, envoi des tentatives par lots via sendBeacon (1 requête / 6 s max), pause de la détection en arrière-plan, collecte désactivable.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.1.0</span>
				<div>
					<strong>Messages enrichis, licences à deux paliers et compatibilité élargie</strong>
					<ul>
						<li>Message d’avertissement repensé : titre + mention copyright automatique (<code>{annee}</code>, <code>{site}</code>, <code>{url}</code>), bouton de fermeture et barre de progression.</li>
						<li>10 styles de messages (ajout : Verre Dépoli, Néon Cyber, Dégradé Infinity, Minimal Blanc, Alerte Rouge).</li>
						<li>Licences à trois paliers : 2 900 DA ≈ 11,90 € (1 site), 4 800 DA ≈ 22,90 € (5 sites) et 12 000 DA ≈ 44,90 € (Agence, 20 sites), avec clé de licence.</li>
						<li>Compatibilité élargie : WordPress 4.9 → 7.1, PHP 7.0 → 8.3, écouteurs en phase de capture pour passer avant tous les thèmes et page builders.</li>
						<li>Accessibilité : respect de <code>prefers-reduced-motion</code>, chargement des traductions.</li>
						<li>Page « À propos » enrichie : fonctionnement détaillé, compatibilité, feuille de route, comparatif de licences.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">2.0.0</span>
				<div>
					<strong>Réécriture complète « Pro »</strong>
					<ul>
						<li>Nouveau tableau de bord avancé responsive : indicateurs temps réel, graphique d’activité, répartition par type, état des protections.</li>
						<li>Statistiques enrichies : par jour, par type, IP uniques, record quotidien, moyennes, export CSV.</li>
						<li>Journaux revus : fichiers mensuels dans wp-uploads, filtres par type/niveau, recherche, rotation et rétention automatiques.</li>
						<li>Réglages modernisés : 6 onglets, interrupteurs, aperçu du message en direct.</li>
						<li>Protection front réécrite : détection DevTools, flou du contenu, redirection, bip sonore, anti PrintScreen.</li>
					</ul>
				</div>
			</div>
			<div class="rcb-changelog-entry">
				<span class="rcb-pill rcb-pill-soft">1.0.0</span>
				<div>
					<strong>Version initiale</strong>
					<ul>
						<li>Blocage du clic droit, des raccourcis clavier et des outils de développement avec messages personnalisés.</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<!-- ===== FAQ ===== -->
	<div class="rcb-card">
		<div class="rcb-card-head"><h2>❓ Questions fréquentes</h2></div>
		<div class="rcb-faq">
			<details>
				<summary>Quelle différence entre les licences 2 900 DA, 4 800 DA et 12 000 DA ?</summary>
				<p>La licence à <strong>2 900 DA ≈ 11,90 €</strong> couvre <strong>1 nom de domaine</strong> ; celle à <strong>4 800 DA ≈ 22,90 €</strong> jusqu’à <strong>5 noms de domaine</strong> avec support prioritaire 24 mois ; la <strong>Licence Agence à 12 000 DA ≈ 44,90 €</strong> couvre jusqu’à <strong>20 noms de domaine</strong> avec support dédié 36 mois — idéale pour les agences et webmasters. Toutes sont à vie, sans abonnement, mises à jour incluses. Pour changer de palier, commandez simplement le plan supérieur et activez la nouvelle clé reçue.</p>
			</details>
			<details>
				<summary>Le plugin est-il compatible avec mon thème et mes page builders ?</summary>
				<p>Oui. Les protections sont posées en <strong>phase de capture</strong> (elles s’exécutent avant les scripts du thème) et les styles du message sont blindés contre les feuilles de style agressives. Le plugin est testé avec les thèmes blocks Gutenberg/FSE, les thèmes classiques, Elementor, Divi, WPBakery, Beaver Builder et WooCommerce.</p>
			</details>
			<details>
				<summary>Le plugin bloque-t-il à 100 % le vol de contenu ?</summary>
				<p>Aucune solution web ne peut bloquer à 100 % un visiteur déterminé (désactivation de JavaScript, outils système…). Infinity RCB Pro place la barre très haut : il neutralise toutes les méthodes courantes, détecte les outils de développement et dissuade la grande majorité des tentatives — tout en laissant votre site parfaitement utilisable.</p>
			</details>
			<details>
				<summary>Mes données sont-elles envoyées à l’extérieur ?</summary>
				<p>Non. Statistiques et journaux sont stockés uniquement sur votre serveur (base WordPress et dossier wp-uploads). Aucune donnée n’est transmise à un service tiers, aucun appel externe n’est effectué.</p>
			</details>
			<details>
				<summary>Les formulaires de mon site continuent-ils de fonctionner ?</summary>
				<p>Oui. La protection copier-coller et la sélection sont désactivées à l’intérieur des champs de saisie (input, textarea, select, contenteditable) afin de ne jamais gêner vos visiteurs — connexion, commentaires, checkout WooCommerce, etc.</p>
			</details>
			<details>
				<summary>Le plugin ralentit-il mon site ?</summary>
				<p>Non. Un seul fichier CSS (~7 Ko) et un seul fichier JavaScript (~12 Ko), sans jQuery ni framework, chargés uniquement lorsque la protection est active. La détection DevTools utilise un intervalle configurable (800 ms par défaut) à l’impact négligeable.</p>
			</details>
			<details>
				<summary>Comment personnaliser le texte du clic droit et la mention copyright ?</summary>
				<p>Réglages → onglet <strong>Messages</strong> pour le texte de chaque avertissement (dont le clic droit), et onglet <strong>Apparence</strong> pour la mention copyright avec les variables <code>{annee}</code>, <code>{site}</code> et <code>{url}</code>. L’aperçu en direct permet de tester le rendu avant d’enregistrer.</p>
			</details>
			<details>
				<summary>Comment mettre à jour le plugin ?</summary>
				<p>Une fois le plugin publié sur WordPress.org, les <strong>mises à jour sont automatiques</strong> depuis le menu Extensions de WordPress, comme n’importe quel plugin officiel — la licence n’est jamais nécessaire pour les mises à jour. La clé de licence sert uniquement au support et à l’usage commercial défini par le vendeur.</p>
			</details>
			<details>
				<summary>Comment puis-je obtenir de l’aide ?</summary>
				<p><?php if ( $options['developer']['email'] ) : ?>Écrivez à <a href="mailto:<?php echo esc_attr( $options['developer']['email'] ); ?>"><?php echo esc_html( $options['developer']['email'] ); ?></a>.<?php else : ?>Contactez le développeur via la page « Licence &amp; Achat ».<?php endif; ?> Le support est inclus avec la licence (12 mois en mono-site, 24 mois prioritaire en 5 sites).</p>
			</details>
		</div>
	</div>

	<!-- ===== Confidentialité ===== -->
	<div class="rcb-card">
		<div class="rcb-card-head"><h2>🔒 Confidentialité</h2></div>
		<div class="rcb-prose">
			<p>Lorsqu’une tentative est bloquée, le plugin enregistre la date, le type d’action, l’adresse IP et le user-agent du navigateur, uniquement à des fins de sécurité et de statistiques internes. Ces données restent sur votre serveur et peuvent être supprimées à tout moment (pages Statistiques et Journaux). Elles ne sont jamais partagées ni vendues, et aucun cookie n’est déposé par le plugin.</p>
		</div>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — développé avec ❤️ par <?php if ( $options['developer']['website'] ) : ?><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php endif; ?><?php echo esc_html( $options['developer']['name'] ); ?><?php if ( $options['developer']['website'] ) : ?></a><?php endif; ?> — Licences à vie : 2 900 DA ≈ 11,90 € (1 site) · 4 800 DA ≈ 22,90 € (5 sites)</p>
</div>
