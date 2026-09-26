<?php
/**
 * Page : Réglages — protection, messages, apparence, avancé, journalisation.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles, $notice, $cron_next
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d’affichage (onglet actif).
$vendor   = Infinity_RCB_License::vendor_enabled();
$tabs     = array(
	'general'    => '🎛️ Général',
	'protections'=> '🛡️ Protections',
	'messages'   => '💬 Messages',
	'appearance' => '🎨 Apparence',
	'advanced'   => '⚡ Avancé & Performance',
	'logging'    => '📜 Journalisation',
);
if ( $vendor ) {
	$tabs['payment'] = '💳 Paiement (vendeur)';
}
$allowed  = array_keys( $tabs );
if ( ! in_array( $tab, $allowed, true ) ) {
	$tab = 'general';
}
?>
<div class="wrap rcb-wrap">

	<div class="rcb-hero rcb-hero-compact">
		<div class="rcb-hero-brand">
			<div class="rcb-logo"><?php printf( '%s', infinity_rcb_shield_svg() ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
			<div>
				<h1>Réglages</h1>
				<p>Configurez la protection de votre site en quelques clics</p>
			</div>
		</div>
		<div class="rcb-hero-side">
			<span class="rcb-pill rcb-pill-<?php echo ! empty( $options['master_enable'] ) ? 'ok' : 'off'; ?>">
				<span class="rcb-dot"></span><?php echo ! empty( $options['master_enable'] ) ? 'Protection ACTIVE' : 'Protection DÉSACTIVÉE'; ?>
			</span>
		</div>
	</div>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Réglages enregistrés avec succès.</div>
	<?php elseif ( 'import-ok' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Réglages importés avec succès.</div>
	<?php elseif ( 'import-invalid' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-warn">⚠️ Fichier invalide : un export JSON généré par Infinity RCB Pro est attendu.</div>
	<?php elseif ( 'reset-done' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Réglages réinitialisés aux valeurs par défaut.</div>
	<?php elseif ( 'updates-available' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">🔄 Une nouvelle version est disponible — ouvrez Extensions pour la mettre à jour en un clic.</div>
	<?php elseif ( 'updates-checked' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Vérification terminée : vous êtes à jour.</div>
	<?php elseif ( 'updates-wporg' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Aucun dépôt GitHub configuré : les mises à jour arriveront automatiquement via WordPress.org dès la publication — rien à faire.</div>
	<?php elseif ( 'updates-none' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-warn">⚠️ Dépôt GitHub injoignable (release sans fichier .zip ou erreur réseau) — WordPress.org reste actif automatiquement.</div>
	<?php endif; ?>

	<form method="post" action="" id="rcb-settings-form">
		<?php wp_nonce_field( 'infinity_rcb_settings', 'infinity_rcb_settings_nonce' ); ?>
		<input type="hidden" name="infinity_rcb_save_settings" value="1">
		<input type="hidden" name="active_tab" id="rcb-active-tab" value="<?php echo esc_attr( $tab ); ?>">

		<div class="rcb-tabs" role="tablist">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a href="#rcb-tab-<?php echo esc_attr( $key ); ?>" class="rcb-tab <?php echo $key === $tab ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>" role="tab"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<!-- ================= ONGLET : GÉNÉRAL ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-general" <?php echo 'general' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Protection globale</h2></div>
				<div class="rcb-field-row">
					<div class="rcb-field-label">
						<strong>Activer la protection</strong>
						<p>Interrupteur maître : coupe ou active toutes les protections du site d’un seul geste.</p>
					</div>
					<label class="rcb-switch">
						<input type="checkbox" name="infinity_rcb[master_enable]" value="1" <?php checked( ! empty( $options['master_enable'] ) ); ?>>
						<span class="rcb-slider"></span>
					</label>
				</div>
			</div>

			<?php
			$gh_repo  = class_exists( 'Infinity_RCB_Updater' ) ? Infinity_RCB_Updater::repo() : '';
			$gh_on    = $gh_repo && class_exists( 'Infinity_RCB_Updater' ) && Infinity_RCB_Updater::github_enabled();
			$gh_rel   = $gh_on ? Infinity_RCB_Updater::latest_release() : null;
			$gh_state = $gh_on
				? ( $gh_rel && version_compare( INFINITY_RCB_VERSION, $gh_rel['version'], '<' ) ? 'GitHub : ' . $gh_rel['version'] . ' disponible' : 'GitHub : à jour' )
				: 'WordPress.org uniquement';
			?>
			<div class="rcb-card" id="rcb-updates">
				<div class="rcb-card-head">
					<h2>🔄 Mises à jour</h2>
					<!-- Lien signé (GET) : un formulaire imbriqué fermerait le
					     grand formulaire des réglages avant l'onglet Apparence. -->
					<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=general&rcb_check_updates=1' ), 'infinity_rcb_export', 'rcb_nonce' ) ); ?>">🔄 Vérifier maintenant</a>
				</div>

				<?php if ( 'updates-available' === $notice ) : ?>
					<div class="rcb-notice rcb-notice-ok" style="margin-bottom:12px;">🔄 Une nouvelle version est disponible — <a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">mettre à jour maintenant</a> (une carte dédiée vous attend sur la page Mises à jour de WordPress).</div>
				<?php elseif ( 'updates-checked' === $notice ) : ?>
					<div class="rcb-notice rcb-notice-ok" style="margin-bottom:12px;">✅ Vérification terminée : vous êtes à jour.</div>
				<?php elseif ( 'updates-wporg' === $notice ) : ?>
					<div class="rcb-notice rcb-notice-ok" style="margin-bottom:12px;">✅ Mises à jour via WordPress.org — automatique, rien à faire.</div>
				<?php elseif ( 'updates-none' === $notice ) : ?>
					<div class="rcb-notice rcb-notice-warn" style="margin-bottom:12px;">⚠️ Dépôt GitHub injoignable (vérifiez votre connexion).</div>
				<?php endif; ?>
				<p class="rcb-muted" style="margin:0 0 12px;">Double canal, sans conflit : <strong>WordPress.org</strong> prend automatiquement le relais dès que l'extension y est publiée (ou si elle en provient) ; en attendant, <strong>GitHub</strong> livre les mises à jour des installations manuelles — le canal GitHub se désactive tout seul dès que wp.org gère l'extension.</p>
				<ul class="rcb-syslist">
					<li><span>Version installée</span><strong>v<?php echo esc_html( INFINITY_RCB_VERSION ); ?></strong></li>
					<li><span>Canal actif</span><strong><?php echo esc_html( $gh_state ); ?></strong></li>
					<?php if ( $gh_on && $gh_rel ) : ?>
						<li><span>Dernière release GitHub</span><strong>v<?php echo esc_html( $gh_rel['version'] ); ?> — <?php echo esc_html( mysql2date( 'd/m/Y', $gh_rel['date'] ) ); ?></strong></li>
					<?php endif; ?>
				</ul>
					<?php if ( $vendor ) : ?>
					<input type="hidden" name="infinity_rcb[updates][posted]" value="1">
					<div class="rcb-grid-2-col" style="margin-top:12px;">
						<div class="rcb-field">
							<label for="rcb-gh-repo">Dépôt GitHub (utilisateur/depot)</label>
							<input type="text" id="rcb-gh-repo" name="infinity_rcb[updates][github_repo]" placeholder="infinitycoder/right-click-blocker-pro" value="<?php echo esc_attr( infinity_rcb_options()['updates']['github_repo'] ); ?>">
							<p class="rcb-muted">Créez une release avec un tag <code>2.14.0</code> et un fichier <code>.zip</code> contenant le dossier du plugin. Surcharge permanente côté client : <code>define( 'INFINITY_RCB_GITHUB_REPO', 'utilisateur/depot' );</code></p>
						</div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Canal GitHub actif</strong><p>Désactivez pour ne recevoir les mises à jour que de WordPress.org.</p></div>
							<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[updates][github_enabled]" value="1" <?php checked( ! empty( $options['updates']['github_enabled'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $vendor ) : ?>
			<div class="rcb-card rcb-card-vendor">
				<div class="rcb-card-head">
					<h2>Informations du développeur <span class="rcb-pill rcb-pill-gold">SITE VENDEUR</span></h2>
				</div>
				<p class="rcb-muted" style="margin:0 0 14px;">Signature des e-mails envoyés depuis <em>ce site</em>. Ces réglages n'existent que sur votre site vendeur — les sites clients affichent les coordonnées définies dans les valeurs par défaut du plugin.</p>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-dev-name">Nom / société</label>
						<input type="text" id="rcb-dev-name" name="infinity_rcb[developer][name]" value="<?php echo esc_attr( $options['developer']['name'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-dev-site">Site web</label>
						<input type="url" id="rcb-dev-site" name="infinity_rcb[developer][website]" value="<?php echo esc_attr( $options['developer']['website'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-dev-mail">E-mail</label>
						<input type="email" id="rcb-dev-mail" name="infinity_rcb[developer][email]" value="<?php echo esc_attr( $options['developer']['email'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-dev-phone">Téléphone</label>
						<input type="text" id="rcb-dev-phone" name="infinity_rcb[developer][phone]" value="<?php echo esc_attr( $options['developer']['phone'] ); ?>">
					</div>
					<div class="rcb-field rcb-field-wide">
						<label for="rcb-dev-addr">Adresse</label>
						<textarea id="rcb-dev-addr" name="infinity_rcb[developer][address]" rows="2"><?php echo esc_textarea( $options['developer']['address'] ); ?></textarea>
					</div>
				</div>
			</div>
			<?php else : ?>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Licence &amp; support</h2></div>
				<p class="rcb-muted" style="margin:0;">La gestion de la licence (activation, achat, support) se fait entièrement depuis la page <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">Licence &amp; Achat</a>. Les mises à jour du plugin sont automatiques via WordPress et ne nécessitent aucune licence.</p>
			</div>
			<?php endif; ?>
		</section>

		<!-- ================= ONGLET : PROTECTIONS ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-protections" <?php echo 'protections' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card">
				<div class="rcb-card-head">
					<h2>Protections actives</h2>
					<div class="rcb-presets">
						<span class="rcb-muted">Préréglages :</span>
						<button type="button" class="rcb-btn rcb-btn-ghost rcb-btn-xs rcb-preset" data-preset="soft">🕊️ Doux</button>
						<button type="button" class="rcb-btn rcb-btn-ghost rcb-btn-xs rcb-preset" data-preset="balanced">⚖️ Équilibré</button>
						<button type="button" class="rcb-btn rcb-btn-ghost rcb-btn-xs rcb-preset" data-preset="max">🔒 Maximum</button>
					</div>
				</div>
				<div class="rcb-fields" id="rcb-group-prot">
					<?php
					$descriptions = array(
						'right_click' => 'Bloque le menu contextuel du clic droit sur tout le site.',
						'keyboard'    => 'Bloque F12, Ctrl+Shift+I / C et autres raccourcis d’inspection.',
						'copy'        => 'Bloque copier, couper, coller et Ctrl+A en dehors des champs de saisie.',
						'selection'   => 'Empêche la sélection de texte au niveau du CSS et des évènements.',
						'drag_drop'   => 'Empêche de faire glisser images et liens hors de la page.',
						'print'       => 'Bloque Ctrl+P et l’évènement d’impression ; remplace la page par un avertissement.',
						'screenshot'  => 'Détecte la touche PrintScreen et vide le presse-papiers.',
						'source_code' => 'Bloque Ctrl+U (affichage du code source).',
						'save_as'     => 'Bloque Ctrl+S (enregistrement de la page).',
						'devtools'    => 'Détecte l’ouverture des outils de développement (analyse des dimensions de fenêtre).',
						'console'     => 'Affiche un avertissement dans la console et bloque Ctrl+Shift+J / K.',
					);
					foreach ( $types as $type => $meta ) :
						?>
						<div class="rcb-field-row">
							<div class="rcb-field-label">
								<strong><span class="rcb-status-emoji"><?php echo esc_html( $meta['emoji'] ); ?></span> <?php echo esc_html( $meta['label'] ); ?></strong>
								<p><?php echo esc_html( $descriptions[ $type ] ); ?></p>
							</div>
							<label class="rcb-switch">
								<input type="checkbox" name="infinity_rcb[protections][<?php echo esc_attr( $type ); ?>]" value="1" <?php checked( ! empty( $options['protections'][ $type ] ) ); ?>>
								<span class="rcb-slider"></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- ================= ONGLET : MESSAGES ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-messages" <?php echo 'messages' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Messages affichés au visiteur</h2></div>
				<p class="rcb-muted" style="margin:0 0 14px;">Personnalisez le texte de chaque avertissement — notamment celui du <strong>clic droit</strong>. La mention copyright (onglet Apparence) est ajoutée automatiquement sous chaque message.</p>
				<div class="rcb-fields">
					<?php foreach ( $types as $type => $meta ) : ?>
						<div class="rcb-field">
							<label for="rcb-msg-<?php echo esc_attr( $type ); ?>"><span class="rcb-status-emoji"><?php echo esc_html( $meta['emoji'] ); ?></span> <?php echo esc_html( $meta['label'] ); ?></label>
							<input type="text" class="regular-text" id="rcb-msg-<?php echo esc_attr( $type ); ?>" name="infinity_rcb[messages][<?php echo esc_attr( $type ); ?>]" value="<?php echo esc_attr( $options['messages'][ $type ] ); ?>">
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- ================= ONGLET : APPARENCE ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-appearance" <?php echo 'appearance' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-appearance-split">
				<div class="rcb-appearance-form">
					<div class="rcb-card">
							<div class="rcb-card-head"><h2>🎨 Style &amp; couleurs</h2></div>
					<div class="rcb-style-grid">
						<?php foreach ( $styles as $key => $style ) : ?>
							<label class="rcb-style-option">
								<input type="radio" name="infinity_rcb[appearance][style]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $options['appearance']['style'], $key ); ?>>
								<span class="rcb-style-swatch" style="background:<?php echo esc_attr( $style['bg'] ); ?>;color:<?php echo esc_attr( $style['text'] ); ?>;">Message</span>
								<span class="rcb-style-name"><?php echo esc_html( $style['label'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>

						<div class="rcb-field-row" style="margin-top:16px;">
							<div class="rcb-field-label"><strong>Couleurs personnalisées</strong><p>Remplacent le thème choisi ci-dessus.</p></div>
							<label class="rcb-switch"><input type="checkbox" id="rcb-custom-on" name="infinity_rcb[appearance][custom_on]" value="1" <?php checked( ! empty( $options['appearance']['custom_bg'] ) || ! empty( $options['appearance']['custom_text'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<div class="rcb-grid-2-col">
							<div class="rcb-color-field">
								<label for="rcb-bg">Fond</label>
								<input type="color" id="rcb-bg" name="infinity_rcb[appearance][custom_bg]" value="<?php echo esc_attr( $options['appearance']['custom_bg'] ? $options['appearance']['custom_bg'] : '#1e88e5' ); ?>">
								<code id="rcb-bg-hex"><?php echo esc_html( $options['appearance']['custom_bg'] ? $options['appearance']['custom_bg'] : '#1e88e5' ); ?></code>
							</div>
							<div class="rcb-color-field">
								<label for="rcb-tx">Texte</label>
								<input type="color" id="rcb-tx" name="infinity_rcb[appearance][custom_text]" value="<?php echo esc_attr( $options['appearance']['custom_text'] ? $options['appearance']['custom_text'] : '#ffffff' ); ?>">
								<code id="rcb-tx-hex"><?php echo esc_html( $options['appearance']['custom_text'] ? $options['appearance']['custom_text'] : '#ffffff' ); ?></code>
							</div>
						</div>
						<div class="rcb-color-presets">
							<span style="font-size:12px;color:#64748B;">Palettes rapides :</span>
							<?php
							$color_presets = array(
								array( '#1E6FF0', '#ffffff', 'Bleu Infinity' ),
								array( '#0B0F1E', '#f8fafc', 'Sombre' ),
								array( '#7C3AED', '#ffffff', 'Violet' ),
								array( '#10b981', '#ffffff', 'Vert' ),
								array( '#f43f5e', '#ffffff', 'Rouge' ),
								array( '#f1c40f', '#2c3e50', 'Or' ),
								array( '#ff6b35', '#ffffff', 'Orange' ),
								array( '#06b6d4', '#0f172a', 'Cyan' ),
							);
							foreach ( $color_presets as $cp ) : ?>
								<button type="button" class="rcb-color-preset" data-bg="<?php echo esc_attr( $cp[0] ); ?>" data-tx="<?php echo esc_attr( $cp[1] ); ?>" title="<?php echo esc_attr( $cp[2] ); ?>" style="background:linear-gradient(135deg,<?php echo esc_attr( $cp[0] ); ?> 50%,<?php echo esc_attr( $cp[1] ); ?> 50%);"></button>
							<?php endforeach; ?>
						</div>
					</div>

				<div class="rcb-card">
						<div class="rcb-card-head"><h2>Comportement &amp; position</h2></div>
						<div class="rcb-grid-2-col">
							<div class="rcb-field">
								<label for="rcb-pos">Position</label>
								<select id="rcb-pos" name="infinity_rcb[appearance][position]">
									<option value="top" <?php selected( $options['appearance']['position'], 'top' ); ?>>Haut</option>
									<option value="center" <?php selected( $options['appearance']['position'], 'center' ); ?>>Centre</option>
									<option value="bottom" <?php selected( $options['appearance']['position'], 'bottom' ); ?>>Bas</option>
								</select>
							</div>
							<div class="rcb-field">
								<label for="rcb-dur">Duree (ms)</label>
								<input type="number" id="rcb-dur" name="infinity_rcb[appearance][duration]" min="500" max="10000" step="100" value="<?php echo esc_attr( $options['appearance']['duration'] ); ?>">
							</div>
						</div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Icone bouclier</strong><p>Logo du plugin dans le message.</p></div>
							<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[appearance][show_icon]" value="1" <?php checked( ! empty( $options['appearance']['show_icon'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<?php if ( class_exists( 'Infinity_RCB_Pro' ) && ! Infinity_RCB_Pro::unlocked() ) : ?>
							<?php Infinity_RCB_Pro::lock_card( 'logo' ); ?>
						<?php else : ?>
							<?php if ( class_exists( 'Infinity_RCB_Pro' ) ) { Infinity_RCB_Pro::badge(); } ?>
						<div class="rcb-field-row rcb-icon-row">
							<div class="rcb-field-label"><strong>Logo personnalise</strong><p>Remplace le bouclier par votre icone (PNG/SVG, carre 64×64+ recommande). Laissez vide pour le bouclier du plugin.</p></div>
							<div class="rcb-icon-picker">
								<span class="rcb-icon-thumb" id="rcb-icon-thumb"><?php
								if ( ! empty( $options['appearance']['custom_icon'] ) ) {
									printf( '<img src="%s" alt="">', esc_url( $options['appearance']['custom_icon'] ) );
								} else {
									printf( '%s', infinity_rcb_shield_svg( 'regular', 'plain' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin).
									;
								}
								?></span>
								<input type="hidden" id="rcb-custom-icon" name="infinity_rcb[appearance][custom_icon]" value="<?php echo esc_attr( $options['appearance']['custom_icon'] ); ?>">
								<button type="button" class="rcb-btn rcb-btn-ghost" id="rcb-icon-choose">📁 Mediatheque</button>
								<button type="button" class="rcb-btn rcb-btn-ghost" id="rcb-icon-remove" <?php echo empty( $options['appearance']['custom_icon'] ) ? 'hidden' : ''; ?>>✕ Retirer</button>
							</div>
						</div>
						<?php endif; ?>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Bip sonore</strong><p>Court signal audio.</p></div>
							<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[appearance][sound]" value="1" <?php checked( ! empty( $options['appearance']['sound'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Bouton fermer</strong><p>Le visiteur peut refermer.</p></div>
							<label class="rcb-switch"><input type="checkbox" id="rcb-close-on" name="infinity_rcb[appearance][show_close]" value="1" <?php checked( ! empty( $options['appearance']['show_close'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Barre de progression</strong><p>Temps restant visible.</p></div>
							<label class="rcb-switch"><input type="checkbox" id="rcb-bar-on" name="infinity_rcb[appearance][show_progress]" value="1" <?php checked( ! empty( $options['appearance']['show_progress'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
					</div>

					<div class="rcb-card">
						<div class="rcb-card-head"><h2>Message enrichi</h2></div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Mention copyright</strong><p>Seconde ligne sous le message.</p></div>
							<label class="rcb-switch"><input type="checkbox" id="rcb-copyright-on" name="infinity_rcb[appearance][copyright_enable]" value="1" <?php checked( ! empty( $options['appearance']['copyright_enable'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<div class="rcb-field rcb-field-wide">
							<label for="rcb-copyright">Texte</label>
							<input type="text" class="regular-text" id="rcb-copyright" name="infinity_rcb[appearance][copyright_text]" value="<?php echo esc_attr( $options['appearance']['copyright_text'] ); ?>">
							<p class="rcb-muted">Variables : <code>{annee}</code> / <code>{site}</code> / <code>{url}</code></p>
						</div>
					</div>

					<div class="rcb-card">
						<div class="rcb-card-head"><h2>Finitions</h2></div>
						<div class="rcb-grid-2-col">
							<div class="rcb-field">
								<label for="rcb-radius">Arrondi : <output id="rcb-radius-out"><?php echo (int) $options['appearance']['radius']; ?></output> px</label>
								<input type="range" id="rcb-radius" name="infinity_rcb[appearance][radius]" min="0" max="24" value="<?php echo (int) $options['appearance']['radius']; ?>" oninput="document.getElementById('rcb-radius-out').value=this.value;">
							</div>
							<div class="rcb-field">
								<label for="rcb-font">Texte : <output id="rcb-font-out"><?php echo (int) $options['appearance']['font_size']; ?></output> px</label>
								<input type="range" id="rcb-font" name="infinity_rcb[appearance][font_size]" min="11" max="22" value="<?php echo (int) $options['appearance']['font_size']; ?>" oninput="document.getElementById('rcb-font-out').value=this.value;">
							</div>
						</div>
						<div class="rcb-field-row">
							<div class="rcb-field-label"><strong>Ombre portee</strong><p>Profondeur visuelle.</p></div>
							<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[appearance][shadow]" value="1" <?php checked( ! empty( $options['appearance']['shadow'] ) ); ?>><span class="rcb-slider"></span></label>
						</div>
						<?php if ( class_exists( 'Infinity_RCB_Pro' ) && ! Infinity_RCB_Pro::unlocked() ) : ?>
							<?php Infinity_RCB_Pro::lock_card( 'css' ); ?>
						<?php else : ?>
							<div class="rcb-field rcb-field-wide">
								<label for="rcb-css">CSS personnalise<?php if ( class_exists( 'Infinity_RCB_Pro' ) ) { Infinity_RCB_Pro::badge(); } ?></label>
								<textarea id="rcb-css" name="infinity_rcb[appearance][custom_css]" rows="3" spellcheck="false"><?php echo esc_textarea( $options['appearance']['custom_css'] ); ?></textarea>
							</div>
						<?php endif; ?>
					</div>
					<p class="rcb-muted">Sélecteurs principaux : <code>#rcb-toast</code>, <code>.rcb-inner</code>, <code>.rcb-title</code>, <code>.rcb-copy</code>, <code>.rcb-close</code>.</p>
					</div>

				

				<aside class="rcb-appearance-side">
					<?php $preview_context = 'settings'; include INFINITY_RCB_DIR . 'admin/partials/preview-card.php'; ?>
				</aside>

			</div>
		</section>

		<!-- ================= ONGLET : AVANCÉ ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-advanced" <?php echo 'advanced' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Exclusions</h2></div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Exclure les administrateurs connectés</strong><p>Aucune protection pour les comptes administrateur — pratique pour éditer le site sans contrainte.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][exclude_admins]" value="1" <?php checked( ! empty( $options['advanced']['exclude_admins'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
			<div class="rcb-field rcb-field-wide">
				<label for="rcb-exclude">Pages exclues de la protection</label>
				<textarea id="rcb-exclude" name="infinity_rcb[advanced][exclude_pages]" rows="4" placeholder="contact&#10;panier&#10;42"><?php echo esc_textarea( $options['advanced']['exclude_pages'] ); ?></textarea>
				<p class="rcb-muted">Une entrée par ligne : ID de page, slug (ex. <code>contact</code>) ou URI (ex. <code>boutique/panier</code>). Les pages listées ne chargent pas du tout le script — économie maximale.</p>
			</div>
			<?php
			$editable_roles = function_exists( 'wp_roles' ) ? wp_roles()->get_names() : array(
				'editor' => 'Éditeur', 'author' => 'Auteur', 'contributor' => 'Contributeur', 'subscriber' => 'Abonné',
			);
			$excluded_roles = is_array( $options['advanced']['exclude_roles'] ?? null ) ? $options['advanced']['exclude_roles'] : array();
			?>
			<div class="rcb-field rcb-field-wide">
				<label>Rôles exclus de la protection</label>
				<div class="rcb-fields" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));display:grid;gap:6px;">
					<?php foreach ( $editable_roles as $role_key => $role_label ) :
						if ( 'administrator' === $role_key ) { continue; } // Déjà couvert par l'option dédiée. ?>
						<label style="display:flex;align-items:center;gap:8px;font-weight:400;">
							<input type="checkbox" name="infinity_rcb[advanced][exclude_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $excluded_roles, true ) ); ?>>
							<?php echo esc_html( $role_label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="rcb-muted">Utile pour les forums et sites membres : ces utilisateurs connectés ne sont pas protégés (la protection cible les visiteurs).</p>
			</div>
		</div>

			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Performance</h2></div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Collecte des statistiques &amp; journaux</strong><p>Les tentatives sont groupées et envoyées toutes les 6 secondes au maximum (une seule requête, via sendBeacon). Désactivez pour un blocage 100 % passif sans aucun appel serveur.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][tracking]" value="1" <?php checked( ! empty( $options['advanced']['tracking'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
			</div>

			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Protections supplémentaires</h2></div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Protection mobile tactile</strong><p>Bloque le menu contextuel du long-press iOS/Android et le callout d'image natif Safari.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][touch_guard]" value="1" <?php checked( ! empty( $options['advanced']['touch_guard'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Durcissement des images</strong><p>Applique <code>pointer-events: none</code> sur toutes les images (sauf celles marquées <code>data-rcb-exempt</code>) — empêche le menu « Enregistrer l'image » sur la plupart des navigateurs.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][image_pointer]" value="1" <?php checked( ! empty( $options['advanced']['image_pointer'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Bandeau sans JavaScript</strong><p>Si un visiteur désactive JavaScript, un bandeau discret l'invite à le réactiver pour profiter du site.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][noscript_warn]" value="1" <?php checked( ! empty( $options['advanced']['noscript_warn'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<?php if ( class_exists( 'Infinity_RCB_Pro' ) && ! Infinity_RCB_Pro::unlocked() ) : ?>
					<?php Infinity_RCB_Pro::lock_card( 'watermark' ); ?>
				<?php else : ?>
					<div class="rcb-field-row">
						<div class="rcb-field-label"><strong>Filigrane sur les images<?php if ( class_exists( 'Infinity_RCB_Pro' ) ) { Infinity_RCB_Pro::badge(); } ?></strong><p>Superpose votre texte sur toutes les images de plus de 80 px. Exemptez avec <code>data-rcb-exempt</code>.</p></div>
						<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][watermark]" value="1" <?php checked( ! empty( $options['advanced']['watermark'] ) ); ?>><span class="rcb-slider"></span></label>
					</div>
					<?php if ( ! empty( $options['advanced']['watermark'] ) ) : ?>
					<div class="rcb-field">
						<label for="rcb-wm-text">Texte du filigrane</label>
						<input type="text" id="rcb-wm-text" name="infinity_rcb[appearance][wm_text]" value="<?php echo esc_attr( $options['appearance']['wm_text'] ); ?>">
						<p class="rcb-muted">Variables : <code>{site}</code>, <code>{annee}</code>, <code>{url}</code>.</p>
					</div>
					<div class="rcb-grid-2-col">
						<div class="rcb-field">
							<label for="rcb-wm-opacity">Opacité : <output id="rcb-wm-opacity-out"><?php echo (int) $options['appearance']['wm_opacity']; ?></output>%</label>
							<input type="range" id="rcb-wm-opacity" name="infinity_rcb[appearance][wm_opacity]" min="10" max="100" step="5" value="<?php echo (int) $options['appearance']['wm_opacity']; ?>" oninput="document.getElementById('rcb-wm-opacity-out').value=this.value;">
							<p class="rcb-muted">10% = discret · 100% = opaque</p>
						</div>
						<div class="rcb-field">
							<label for="rcb-wm-size">Taille : <output id="rcb-wm-size-out"><?php echo (int) $options['appearance']['wm_size']; ?></output> px</label>
							<input type="range" id="rcb-wm-size" name="infinity_rcb[appearance][wm_size]" min="8" max="48" value="<?php echo (int) $options['appearance']['wm_size']; ?>" oninput="document.getElementById('rcb-wm-size-out').value=this.value;">
							<p class="rcb-muted">8 px = petit · 48 px = très grand</p>
						</div>
					</div>
					<?php endif; ?>
				<?php endif; ?>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Anti-clickjacking (X-Frame-Options)</strong><p>Interdit l'affichage de votre site dans une iframe d'un autre site — empêche le détournement de votre contenu et les attaques par clic trompeur.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][xfo]" value="1" <?php checked( ! empty( $options['advanced']['xfo'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Frame-busting JavaScript</strong><p>Complément du blocage d'iframe : si la page est malgré tout affichée dans un cadre, le visiteur est automatiquement ramené sur votre site.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][frame_bust]" value="1" <?php checked( ! empty( $options['advanced']['frame_bust'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field">
					<label for="rcb-alert">Alerte pic d'attaques (tentatives/heure et par IP)</label>
					<input type="number" id="rcb-alert" name="infinity_rcb[advanced][alert_threshold]" min="0" max="100000" step="10" value="<?php echo esc_attr( (int) $options['advanced']['alert_threshold'] ); ?>">
					<p class="rcb-muted">0 = désactivé. Au-delà du seuil (ex. 50), un e-mail d'alerte est envoyé à l'administrateur — 1 alerte maximum par IP et par heure.</p>
				</div>
			</div>

			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Détection des outils de développement</h2></div>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-interval">Intervalle de vérification (ms)</label>
						<input type="number" id="rcb-interval" name="infinity_rcb[advanced][interval]" min="200" max="5000" step="100" value="<?php echo esc_attr( $options['advanced']['interval'] ); ?>">
						<p class="rcb-muted">800 ms est un bon compromis détection / performance. La détection se met automatiquement en pause quand l'onglet est en arrière-plan.</p>
					</div>
					<div class="rcb-field">
						<label for="rcb-redirect">URL de redirection (facultatif)</label>
						<input type="url" id="rcb-redirect" name="infinity_rcb[advanced][redirect]" placeholder="https://…" value="<?php echo esc_attr( $options['advanced']['redirect'] ); ?>">
						<p class="rcb-muted">Page vers laquelle renvoyer un visiteur qui ouvre les outils de développement.</p>
					</div>
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Flouter le contenu</strong><p>Applique un flou à la page tant que les outils de développement sont ouverts.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[advanced][blur]" value="1" <?php checked( ! empty( $options['advanced']['blur'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
			</div>
		</section>

		<!-- ================= ONGLET : PAIEMENT (SITE VENDEUR UNIQUEMENT) ================= -->
		<?php if ( $vendor ) : ?>
		<section class="rcb-tab-panel" id="rcb-tab-payment" <?php echo 'payment' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card rcb-card-vendor">
				<div class="rcb-card-head">
					<h2>Coordonnées de paiement <span class="rcb-pill rcb-pill-gold">SITE VENDEUR</span></h2>
					<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">Ouvrir la page Licence &amp; Achat</a>
				</div>
				<p class="rcb-muted" style="margin:0 0 16px;">Ces coordonnées servent aux e-mails et boutons PayPal depuis <em>ce site</em>. Les installations clientes, elles, affichent les coordonnées définies dans les valeurs par défaut du plugin (fonction <code>infinity_rcb_default_options()</code>) — mettez-les à jour avant de distribuer une nouvelle version.</p>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-rip">📱 BaridiMob — RIP</label>
						<input type="text" id="rcb-rip" name="infinity_rcb[payment][baridimob_rip]" placeholder="00799999 0012345678 90" value="<?php echo esc_attr( $options['payment']['baridimob_rip'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-ccp">📮 CCP / Carte Edahabia</label>
						<input type="text" id="rcb-ccp" name="infinity_rcb[payment][ccp]" placeholder="Numéro de compte + clé" value="<?php echo esc_attr( $options['payment']['ccp'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-rib">🏦 Virement bancaire — RIB</label>
						<input type="text" id="rcb-rib" name="infinity_rcb[payment][rib]" placeholder="RIB complet" value="<?php echo esc_attr( $options['payment']['rib'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-paypal">🅿️ PayPal — e-mail marchand</label>
						<input type="email" id="rcb-paypal" name="infinity_rcb[payment][paypal_email]" placeholder="boutique@paypal.com" value="<?php echo esc_attr( $options['payment']['paypal_email'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-paypalme">🅿️ PayPal.me (prioritaire si rempli)</label>
						<input type="text" id="rcb-paypalme" name="infinity_rcb[payment][paypal_me]" placeholder="nomutilisateur" value="<?php echo esc_attr( $options['payment']['paypal_me'] ); ?>">
						<p class="rcb-muted">Le bouton « Payer en euros » pointera vers paypal.me/&lt;nom&gt;/&lt;montant&gt;EUR.</p>
					</div>
					<div class="rcb-field">
						<label for="rcb-whatsapp">💬 WhatsApp Business — numéro international</label>
						<input type="text" id="rcb-whatsapp" name="infinity_rcb[payment][whatsapp]" placeholder="+213XXXXXXXXX" value="<?php echo esc_attr( $options['payment']['whatsapp'] ); ?>">
						<p class="rcb-muted">Ajoute un bouton « Commander via WhatsApp » sur la boutique [rcb_plans]/[rcb_commande], avec message prérempli (plan + montant).</p>
					</div>
					<div class="rcb-field rcb-field-wide">
						<label for="rcb-instructions">Instructions affichées à l'acheteur</label>
						<textarea id="rcb-instructions" name="infinity_rcb[payment][instructions]" rows="3" placeholder="Après paiement, envoyez la référence de commande à …"><?php echo esc_textarea( $options['payment']['instructions'] ); ?></textarea>
					</div>
				</div>

				<div class="rcb-card-head" style="margin-top:18px;"><h2>💳 Paiement carte CIB / Edahabia — Chargily Pay <span class="rcb-pill rcb-pill-gold">LIVRAISON AUTOMATIQUE</span></h2></div>
				<p class="rcb-muted" style="margin:0 0 14px;">Avec Chargily Pay, l'acheteur paie par carte algérienne (CIB / Edahabia) et la clé de licence est générée puis envoyée <strong>automatiquement</strong> après paiement (webhook signé HMAC-SHA256). Créez un compte sur <strong>pay.chargily.com</strong> → espace développeur → clés API (Test puis Live). Tant que la clé n'est pas renseignée, rien n'est activé.</p>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-chargily-mode">Mode</label>
						<select id="rcb-chargily-mode" name="infinity_rcb[payment][chargily_mode]">
							<option value="test" <?php selected( $options['payment']['chargily_mode'], 'test' ); ?>>Test (clés test_sk_…)</option>
							<option value="live" <?php selected( $options['payment']['chargily_mode'], 'live' ); ?>>Live (clés live sk_…)</option>
						</select>
					</div>
					<div class="rcb-field">
						<label for="rcb-chargily-key">Clé secrète API</label>
						<input type="password" id="rcb-chargily-key" name="infinity_rcb[payment][chargily_key]" placeholder="test_sk_…" value="<?php echo esc_attr( $options['payment']['chargily_key'] ); ?>" autocomplete="off">
					</div>
					<div class="rcb-field rcb-field-wide">
						<label>URL du webhook (à copier dans le tableau de bord Chargily → Webhooks)</label>
						<input type="text" readonly onclick="this.select()" value="<?php echo esc_attr( Infinity_RCB_Chargily::webhook_url() ); ?>" style="font-family:monospace;">
					</div>
				</div>

				<div class="rcb-field-row" style="margin-top:10px;">
					<div class="rcb-field-label"><strong>📈 Bilan hebdo par e-mail</strong><p>Chaque lundi, un récapitulatif des ventes (licences, chiffre d'affaires, commandes en attente) est envoyé au vendeur.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[payment][digest]" value="1" <?php checked( ! empty( $options['payment']['digest'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- ===== Antispam (carte affichée dans l'onglet Avancé) ===== -->
		<?php if ( 'advanced' === $tab ) : ?>
		<div class="rcb-card" id="rcb-antispam">
			<div class="rcb-card-head">
				<h2>Antispam commentaires</h2>
				<span class="rcb-pill rcb-pill-soft">5 couches</span>
			</div>
			<div class="rcb-field-row">
				<div class="rcb-field-label"><strong>Activer l’antispam</strong><p>Bloque les commentaires de robots — marques « Indesirable » (jamais perdus).</p></div>
				<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[antispam][enabled]" value="1" <?php checked( ! empty( $options['antispam']['enabled'] ) ); ?>><span class="rcb-slider"></span></label>
			</div>
			<div class="rcb-grid-2-col" style="margin-top:12px;">
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Champ piege (honeypot)</strong><p>Invisible, rempli uniquement par les robots.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[antispam][honeypot]" value="1" <?php checked( ! empty( $options['antispam']['honeypot'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Delai minimum</strong><p>Soumission trop rapide = robot.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[antispam][timegate]" value="1" <?php checked( ! empty( $options['antispam']['timegate'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field">
					<label for="rcb-minsec">Delai minimum (s)</label>
					<input type="number" id="rcb-minsec" name="infinity_rcb[antispam][min_seconds]" min="1" max="30" value="<?php echo esc_attr( (int) $options['antispam']['min_seconds'] ); ?>">
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Limite par IP/heure</strong><p>Plafond de commentaires par adresse IP.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[antispam][ratelimit]" value="1" <?php checked( ! empty( $options['antispam']['ratelimit'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field">
					<label for="rcb-maxhour">Max/heure</label>
					<input type="number" id="rcb-maxhour" name="infinity_rcb[antispam][max_per_hour]" min="1" max="100" value="<?php echo esc_attr( (int) $options['antispam']['max_per_hour'] ); ?>">
				</div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Limiter les liens</strong><p>Trop de liens = spam.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[antispam][linkcheck]" value="1" <?php checked( ! empty( $options['antispam']['linkcheck'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-field">
					<label for="rcb-maxlinks">Max liens</label>
					<input type="number" id="rcb-maxlinks" name="infinity_rcb[antispam][max_links]" min="0" max="20" value="<?php echo esc_attr( (int) $options['antispam']['max_links'] ); ?>">
				</div>
			</div>
			<div class="rcb-field rcb-field-wide" style="margin-top:12px;">
				<label for="rcb-blacklist">Liste noire (une entree par ligne)</label>
				<textarea id="rcb-blacklist" name="infinity_rcb[antispam][blacklist]" rows="4" placeholder="192.168.1.1&#10;spam@bot.com&#10;viagra"><?php echo esc_textarea( $options['antispam']['blacklist'] ); ?></textarea>
				<p class="rcb-muted">IP, e-mail, mot-cle ou expression.</p>
			</div>
		</div>
		<?php endif; ?>

		<!-- ================= ONGLET : JOURNALISATION ================= -->
		<section class="rcb-tab-panel" id="rcb-tab-logging" <?php echo 'logging' !== $tab ? 'hidden' : ''; ?>>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Journalisation</h2></div>
				<div class="rcb-field-row">
					<div class="rcb-field-label"><strong>Activer la journalisation</strong><p>Consigne chaque tentative dans <code>wp-uploads/infinity-rcb-pro/logs/</code>.</p></div>
					<label class="rcb-switch"><input type="checkbox" name="infinity_rcb[logging][enabled]" value="1" <?php checked( ! empty( $options['logging']['enabled'] ) ); ?>><span class="rcb-slider"></span></label>
				</div>
				<div class="rcb-grid-2-col">
					<div class="rcb-field">
						<label for="rcb-level">Niveau minimal</label>
						<select id="rcb-level" name="infinity_rcb[logging][level]">
							<option value="debug" <?php selected( $options['logging']['level'], 'debug' ); ?>>Debug — tout consigner</option>
							<option value="info" <?php selected( $options['logging']['level'], 'info' ); ?>>Info</option>
							<option value="warning" <?php selected( $options['logging']['level'], 'warning' ); ?>>Warning (recommandé)</option>
							<option value="error" <?php selected( $options['logging']['level'], 'error' ); ?>>Error</option>
						</select>
					</div>
					<div class="rcb-field">
						<label for="rcb-retention">Rétention (jours)</label>
						<input type="number" id="rcb-retention" name="infinity_rcb[logging][retention_days]" min="1" max="365" value="<?php echo esc_attr( $options['logging']['retention_days'] ); ?>">
					</div>
					<div class="rcb-field">
						<label for="rcb-maxlog">Nombre maximum d’entrées par fichier</label>
						<input type="number" id="rcb-maxlog" name="infinity_rcb[logging][max_entries]" min="100" max="50000" step="100" value="<?php echo esc_attr( $options['logging']['max_entries'] ); ?>">
					</div>
				</div>
			</div>
		</section>

		<div class="rcb-save-bar">
			<button type="submit" class="rcb-btn rcb-btn-primary rcb-btn-lg">💾 Enregistrer les réglages</button>
			<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro' ) ); ?>">← Retour au tableau de bord</a>
		</div>
	</form>

	<!-- ===== Outils : sauvegarde des réglages (hors formulaire principal) ===== -->
	<div class="rcb-card" id="rcb-tools" style="margin-top:18px;">
		<div class="rcb-card-head"><h2>⚙️ Sauvegarde des réglages</h2></div>
		<p class="rcb-muted" style="margin:0 0 14px;">Exportez la configuration complète en JSON pour la répliquer sur un autre site en deux clics — idéal avec la licence Agence (20 domaines). L'import applique exactement les mêmes contrôles d'assainissement que le formulaire.</p>
		<div class="rcb-actions" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
			<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&rcb_export=settings' ), 'infinity_rcb_export', 'rcb_nonce' ) ); ?>">⬇️ Exporter (JSON)</a>

			<form method="post" action="" enctype="multipart/form-data" class="rcb-inline-form" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<?php wp_nonce_field( 'infinity_rcb_tools', 'infinity_rcb_tools_nonce' ); ?>
				<input type="file" name="rcb_settings_file" accept=".json,application/json" required style="max-width:260px;">
				<button type="submit" name="infinity_rcb_import_settings" value="1" class="rcb-btn rcb-btn-ghost">⬆️ Importer</button>
			</form>

			<form method="post" action="" class="rcb-inline-form">
				<?php wp_nonce_field( 'infinity_rcb_tools', 'infinity_rcb_tools_nonce' ); ?>
				<button type="submit" name="infinity_rcb_reset_settings" value="1" class="rcb-btn rcb-btn-danger" data-confirm="Réinitialiser TOUS les réglages aux valeurs par défaut ? Les statistiques et licences ne sont pas touchées.">↺ Réinitialiser</button>
			</form>
		</div>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — <?php if ( $options['developer']['website'] ) : ?><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php endif; ?><?php echo esc_html( $options['developer']['name'] ); ?><?php if ( $options['developer']['website'] ) : ?></a><?php endif; ?></p>
</div>
