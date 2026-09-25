<?php
/**
 * Page : Tableau de bord — vue d'ensemble temps réel.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles, $notice, $cron_next
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$master_on  = ! empty( $options['master_enable'] );
$active_pro = 0;
foreach ( $types as $type => $meta ) {
	if ( ! empty( $options['protections'][ $type ] ) ) {
		$active_pro++;
	}
}
?>
<div class="wrap rcb-wrap">

	<!-- ===== En-tête ===== -->
	<div class="rcb-hero">
		<div class="rcb-hero-brand">
			<div class="rcb-logo"><?php printf( '%s', infinity_rcb_shield_svg( 'large' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?></div>
			<div>
				<h1 class="rcb-hero-title">
					<?php printf( '%s', infinity_rcb_wordmark( 'hero' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML statique de confiance (helper du plugin). ?>
					<span class="rcb-pill rcb-pill-soft">v<?php echo esc_html( INFINITY_RCB_VERSION ); ?></span>
				</h1>
				<p>Protection de contenu professionnelle pour WordPress — Simple · Puissant · Léger</p>
			</div>
		</div>
		<div class="rcb-hero-side">
			<span class="rcb-pill rcb-pill-<?php echo $master_on ? 'ok' : 'off'; ?>">
				<span class="rcb-dot"></span><?php echo $master_on ? 'Protection ACTIVE' : 'Protection DÉSACTIVÉE'; ?>
			</span>
			<a class="rcb-pill rcb-pill-gold" style="text-decoration:none;" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">
				<?php if ( 'active' === $lic_status['code'] ) : ?>
					🔑 Licence <?php echo esc_html( $lic_status['plan_label'] ); ?> — active
				<?php else : ?>
					💎 Licence à vie — dès 2 900 DA (1 site)
				<?php endif; ?>
			</a>
		</div>
	</div>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Réglages enregistrés avec succès.</div>
	<?php elseif ( 'stats-reset' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Statistiques réinitialisées.</div>
	<?php elseif ( 'logs-cleared' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Journaux supprimés.</div>
	<?php endif; ?>

	<?php if ( 'wizard-done' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Bienvenue ! Votre protection est configurée et active. Explorez le tableau de bord ci-dessous.</div>
	<?php endif; ?>

	<?php
	// Bandeau non-licencié : informatif, dismissible 7 jours, ne bloque rien.
	$rcb_show_nag = 'active' !== $lic_status['code'] && 'revoked' !== $lic_status['code'];
	$rcb_nag_dismissed = (int) get_user_meta( get_current_user_id(), 'infinity_rcb_pro_nag_dismissed', true );
	$rcb_nag_expired = $rcb_nag_dismissed && ( time() - $rcb_nag_dismissed ) > 7 * DAY_IN_SECONDS;
	if ( $rcb_show_nag && ( ! $rcb_nag_dismissed || $rcb_nag_expired ) ) :
	?>
	<div class="rcb-card" id="rcb-license-nag" style="display:flex;align-items:center;gap:16px;padding:14px 20px;border-left:4px solid #f59e0b;margin-bottom:20px;">
		<span style="font-size:24px;">💡</span>
		<div style="flex:1;">
			<strong style="font-size:14px;color:#92400e;">Installation non-licenciée — support non inclus</strong>
			<p style="margin:2px 0 0;font-size:13px;color:#64748B;">Toutes les protections fonctionnent librement. Une licence à vie (dès 2 900 DA) ajoute le support prioritaire et les alertes e-mail. <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>#rcb-commande">Obtenir une licence</a></p>
		</div>
		<form method="post" action="">
			<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
			<button type="submit" name="infinity_rcb_dismiss_nag" value="1" class="btn btn-sm" style="background:none;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;cursor:pointer;font-size:12px;color:#64748B;">Plus tard</button>
		</form>
	</div>
	<?php endif; ?>

	<?php if ( empty( $options['wizard_done'] ) ) : ?>
	<!-- ===== Assistant de premier démarrage ===== -->
	<div class="rcb-card rcb-wizard">
		<div class="rcb-card-head">
			<h2>👋 Bienvenue dans Infinity RCB Pro <span class="rcb-pill rcb-pill-gold">Premier démarrage</span></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
				<button type="submit" name="infinity_rcb_wizard_done" value="1" class="rcb-btn rcb-btn-ghost">✕ Ignorer</button>
			</form>
		</div>
		<div class="rcb-steps">
			<div class="rcb-step">
				<span class="rcb-step-n">1</span>
				<strong>Votre site est déjà protégé</strong>
				<p>L'installation a activé les 11 protections recommandées : clic droit, raccourcis, copie, DevTools, impression… Testez votre site public pour voir le message d'avertissement.</p>
			</div>
			<div class="rcb-step">
				<span class="rcb-step-n">2</span>
				<strong>Personnalisez le message</strong>
				<p>10 styles, copyright automatique, couleurs personnalisées. <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=appearance' ) ); ?>">Ouvrir Apparence →</a></p>
			</div>
			<div class="rcb-step">
				<span class="rcb-step-n">3</span>
				<strong>Surveillez en temps réel</strong>
				<p>Chaque tentative bloquée apparaît ci-dessous avec graphiques, IP et journaux exportables. <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">Licence &amp; support →</a></p>
			</div>
		</div>
		<form method="post" action="" style="margin-top:16px;">
			<?php wp_nonce_field( 'infinity_rcb_license', 'infinity_rcb_license_nonce' ); ?>
			<button type="submit" name="infinity_rcb_wizard_done" value="1" class="rcb-btn rcb-btn-primary rcb-btn-lg">✅ Parfait, c'est parti !</button>
		</form>
	</div>
	<?php endif; ?>

	<?php if ( ! $master_on ) : ?>
		<div class="rcb-notice rcb-notice-warn">
			⚠️ La protection globale est <strong>désactivée</strong> : aucun blocage n'est appliqué sur le site.
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings' ) ); ?>">Activer la protection</a>
		</div>
	<?php endif; ?>

	<!-- ===== Indicateurs clés ===== -->
	<div class="rcb-kpis">
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#ef44441a;color:#ef4444;">🛡️</span>
			<div><em data-live="total" data-count="<?php echo (int) $summary['total']; ?>">0</em><small>Total tentatives bloquées</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#f59e0b1a;color:#f59e0b;">📅</span>
			<div><em data-live="today" data-count="<?php echo (int) $summary['today']; ?>">0</em><small>Aujourd’hui</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#6366f11a;color:#6366f1;">🖱️</span>
			<div><em data-live="right_click" data-count="<?php echo (int) ( $summary['by_type']['right_click'] ?? 0 ); ?>">0</em><small>Clics droits bloqués</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#10b9811a;color:#10b981;">⌨️</span>
			<div><em data-live="keyboard" data-count="<?php echo (int) ( $summary['by_type']['keyboard'] ?? 0 ); ?>">0</em><small>Raccourcis clavier</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#8b5cf61a;color:#8b5cf6;">🛠️</span>
			<div><em data-live="devtools" data-count="<?php echo (int) ( $summary['by_type']['devtools'] ?? 0 ); ?>">0</em><small>Outils de développement</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#06b6d41a;color:#06b6d4;">🌐</span>
			<div><em data-live="unique" data-count="<?php echo (int) $summary['unique']; ?>">0</em><small>IP uniques</small></div>
		</div>
	</div>

	<?php
	$rcb_spam_count = (int) get_option( 'infinity_rcb_pro_spam_count', 0 );
	if ( $rcb_spam_count > 0 ) :
		?>
	<!-- ===== Compteur antispam ===== -->
	<div class="rcb-card" style="display:flex;align-items:center;gap:16px;padding:14px 20px;">
		<span style="font-size:28px;">🚫</span>
		<div style="flex:1;">
			<strong style="font-size:16px;color:#DC2626;"><?php echo number_format( $rcb_spam_count ); ?> commentaires spam bloqués</strong>
			<p style="margin:2px 0 0;font-size:13px;color:#64748B;">Antispam actif — honeypot, délai minimum, limite IP/heure, liste noire et limite de liens. Détails dans <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=advanced' ) ); ?>#rcb-antispam">Réglages → Avancé → Antispam</a>.</p>
		</div>
	</div>
	<?php endif; ?>

	<!-- ===== Aperçu du message d'avertissement ===== -->
	<?php $preview_context = 'dashboard'; include INFINITY_RCB_DIR . 'admin/partials/preview-card.php'; ?>

	<!-- ===== Graphiques ===== -->
	<div class="rcb-grid-2 rcb-grid-wide-left">
		<div class="rcb-card rcb-chart-card">
			<div class="rcb-card-head">
				<h2>Activité — 14 derniers jours</h2>
				<span class="rcb-pill rcb-pill-soft"><span class="rcb-dot rcb-live-dot"></span>Temps réel</span>
			</div>
			<div class="rcb-chart-body">
				<canvas id="rcb-line-chart" aria-label="Graphique d’activité quotidienne"></canvas>
				<div id="rcb-line-tip" class="rcb-tip" hidden></div>
			</div>
		</div>
		<div class="rcb-card rcb-chart-card">
			<div class="rcb-card-head">
				<h2>Répartition par type</h2>
			</div>
			<div class="rcb-chart-body rcb-donut-body">
				<canvas id="rcb-donut-chart" aria-label="Répartition des tentatives par type"></canvas>
				<div id="rcb-donut-legend" class="rcb-donut-legend"></div>
			</div>
		</div>
	</div>

	<!-- ===== État des protections ===== -->
	<div class="rcb-card">
		<div class="rcb-card-head">
			<h2>État des protections <span class="rcb-pill rcb-pill-soft"><?php echo (int) $active_pro; ?>/<?php echo count( $types ); ?> actives</span></h2>
			<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=protections' ) ); ?>">Configurer</a>
		</div>
		<div class="rcb-status-grid">
			<?php foreach ( $types as $type => $meta ) : ?>
				<div class="rcb-status-chip <?php echo ! empty( $options['protections'][ $type ] ) ? 'is-on' : 'is-off'; ?>">
					<span class="rcb-dot"></span>
					<span class="rcb-status-emoji"><?php echo esc_html( $meta['emoji'] ); ?></span>
					<?php echo esc_html( $meta['label'] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ===== Activité récente + Système ===== -->
	<div class="rcb-grid-2 rcb-grid-wide-left">
		<div class="rcb-card">
			<div class="rcb-card-head">
				<h2>Dernières tentatives</h2>
				<span class="rcb-pill rcb-pill-soft" id="rcb-recent-count"><?php echo count( (array) $summary['recent'] ); ?></span>
			</div>
			<?php if ( empty( $summary['recent'] ) ) : ?>
				<p class="rcb-empty" id="rcb-recent-empty">Aucune tentative enregistrée pour le moment.<br>Les évènements apparaîtront ici en temps réel dès qu’un visiteur tentera une action interdite.</p>
			<?php endif; ?>
			<div class="rcb-table-wrap">
				<table class="rcb-table" id="rcb-recent-table" <?php echo empty( $summary['recent'] ) ? 'hidden' : ''; ?>>
					<thead><tr><th>Date &amp; heure</th><th>Type</th><th>IP</th></tr></thead>
					<tbody id="rcb-recent-body">
						<?php foreach ( (array) $summary['recent'] as $event ) : ?>
							<tr>
								<td><?php echo esc_html( $event['time'] ); ?></td>
								<td>
									<?php if ( isset( $types[ $event['type'] ] ) ) : ?>
										<span class="rcb-badge" style="background:<?php echo esc_attr( $types[ $event['type'] ]['color'] ); ?>1a;color:<?php echo esc_attr( $types[ $event['type'] ]['color'] ); ?>;">
											<?php echo esc_html( $types[ $event['type'] ]['emoji'] . ' ' . $types[ $event['type'] ]['label'] ); ?>
										</span>
									<?php else : ?>
										<span class="rcb-badge"><?php echo esc_html( $event['type'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html( $event['ip'] ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ( ! empty( $summary['last']['ua'] ) ) : ?>
				<p class="rcb-muted" id="rcb-last-ua">Dernier user-agent : <code><?php echo esc_html( $summary['last']['ua'] ); ?></code></p>
			<?php endif; ?>
		</div>

		<div class="rcb-card">
			<div class="rcb-card-head"><h2>Informations système</h2></div>
			<ul class="rcb-syslist">
				<li><span>Version du plugin</span><strong>v<?php echo esc_html( INFINITY_RCB_VERSION ); ?></strong></li>
				<li><span>WordPress</span><strong>v<?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong></li>
				<li><span>PHP</span><strong>v<?php echo esc_html( PHP_VERSION ); ?></strong></li>
				<li><span>Suivi des statistiques depuis</span><strong><?php echo esc_html( $summary['since'] ? $summary['since'] : '—' ); ?></strong></li>
				<li><span>Journalisation</span>
					<strong><?php echo ! empty( $options['logging']['enabled'] ) ? 'Activée' : 'Désactivée'; ?></strong></li>
				<li><span>Répertoire des journaux</span>
					<strong class="<?php echo $log_stats['writable'] ? 'rcb-ok-text' : 'rcb-err-text'; ?>"><?php echo $log_stats['writable'] ? 'Inscriptible ✓' : 'Non inscriptible ✗'; ?></strong></li>
				<li><span>Entrées de journal</span><strong><?php echo number_format( (int) $log_stats['lines'], 0, ',', ' ' ); ?></strong></li>
				<li><span>Maintenance automatique</span><strong><?php echo $cron_next ? esc_html( date_i18n( 'd/m/Y H:i', $cron_next + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) : 'Non planifiée'; ?></strong></li>
				<li><span>Licence</span><strong>
					<?php if ( 'active' === $lic_status['code'] ) : ?>
						<?php echo esc_html( $lic_status['plan_label'] ); ?> — <span class="rcb-ok-text">active ✓</span> (<?php echo count( $lic_status['domains'] ); ?>/<?php echo (int) $lic_status['slots']; ?> domaines)
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">Activer une licence</a> — 2 900 DA (1 site) · 4 800 DA (5 sites)
					<?php endif; ?>
				</strong></li>
			</ul>
		</div>
	</div>

	<!-- ===== Actions rapides ===== -->
	<div class="rcb-card rcb-actions">
		<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings' ) ); ?>">⚙️ Réglages</a>
		<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-stats' ) ); ?>">📊 Statistiques détaillées</a>
		<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-logs' ) ); ?>">📜 Journaux</a>
		<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro&rcb_export=stats' ), 'infinity_rcb_export', 'rcb_nonce' ) ); ?>">⬇️ Exporter les statistiques (CSV)</a>
		<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-about' ) ); ?>">ℹ️ À propos &amp; licence</a>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — développé avec ❤️ par <?php if ( $options['developer']['website'] ) : ?><a href="<?php echo esc_url( $options['developer']['website'] ); ?>" target="_blank" rel="noopener"><?php endif; ?><?php echo esc_html( $options['developer']['name'] ); ?><?php if ( $options['developer']['website'] ) : ?></a><?php endif; ?></p>
</div>
