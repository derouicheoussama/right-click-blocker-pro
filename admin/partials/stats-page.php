<?php
/**
 * Page : Statistiques — analyse détaillée sur 7/30 jours.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles, $notice, $cron_next
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$max_type = 1;
foreach ( $types as $type => $meta ) {
	$max_type = max( $max_type, (int) ( $summary['by_type'][ $type ] ?? 0 ) );
}
$export_url = wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-stats&rcb_export=stats' ), 'infinity_rcb_export', 'rcb_nonce' );
?>
<div class="wrap rcb-wrap">

	<div class="rcb-hero rcb-hero-compact">
		<div class="rcb-hero-brand">
			<div class="rcb-logo"><?php printf( '%s', infinity_rcb_shield_svg() ) ?></div>
			<div>
				<h1>Statistiques</h1>
				<p>Analyse détaillée des tentatives de contournement</p>
			</div>
		</div>
		<div class="rcb-hero-side">
			<span class="rcb-pill rcb-pill-soft">Suivi depuis le <?php echo esc_html( $summary['since'] ? date_i18n( 'd/m/Y', strtotime( $summary['since'] ) ) : '—' ); ?></span>
		</div>
	</div>

	<?php if ( 'stats-reset' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Toutes les statistiques ont été réinitialisées.</div>
	<?php endif; ?>

	<div class="rcb-kpis">
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#ef44441a;color:#ef4444;">🛡️</span>
			<div><em data-count="<?php echo (int) $summary['total']; ?>">0</em><small>Total depuis le début</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#f59e0b1a;color:#f59e0b;">📅</span>
			<div><em data-count="<?php echo (int) $summary['today']; ?>">0</em><small>Aujourd’hui</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#10b9811a;color:#10b981;">🗓️</span>
			<div><em data-count="<?php echo (int) $summary['week']; ?>">0</em><small>7 derniers jours</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#6366f11a;color:#6366f1;">📆</span>
			<div><em data-count="<?php echo (int) $summary['month']; ?>">0</em><small>30 derniers jours</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#06b6d41a;color:#06b6d4;">📈</span>
			<div><em data-count="<?php echo (float) $summary['avg']; ?>">0</em><small>Moyenne / jour (30 j)</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#8b5cf61a;color:#8b5cf6;">🏔️</span>
			<div><em data-count="<?php echo (int) $summary['peak']; ?>">0</em><small>Record quotidien<?php echo $summary['peak_day'] ? ' (' . esc_html( date_i18n( 'd/m/Y', strtotime( $summary['peak_day'] ) ) ) . ')' : ''; ?></small></div>
		</div>
	</div>

	<div class="rcb-grid-2 rcb-grid-wide-left">
		<div class="rcb-card rcb-chart-card">
			<div class="rcb-card-head"><h2>Évolution — 30 derniers jours</h2></div>
			<div class="rcb-chart-body">
				<canvas id="rcb-line-chart-30" aria-label="Évolution des tentatives sur 30 jours"></canvas>
				<div id="rcb-line-tip-30" class="rcb-tip" hidden></div>
			</div>
		</div>
		<div class="rcb-card rcb-chart-card">
			<div class="rcb-card-head"><h2>Répartition par type</h2></div>
			<div class="rcb-chart-body rcb-donut-body">
				<canvas id="rcb-donut-chart" aria-label="Répartition par type de tentative"></canvas>
				<div id="rcb-donut-legend" class="rcb-donut-legend"></div>
			</div>
		</div>
	</div>

	<div class="rcb-grid-2">
		<div class="rcb-card">
			<div class="rcb-card-head"><h2>Détail par type de protection</h2></div>
			<div class="rcb-table-wrap">
				<table class="rcb-table">
					<thead><tr><th>Type</th><th style="width:45%;">Poids</th><th>Tentatives</th><th>Part</th></tr></thead>
					<tbody>
						<?php foreach ( $types as $type => $meta ) :
							$value = (int) ( $summary['by_type'][ $type ] ?? 0 );
							$part  = $summary['total'] > 0 ? round( $value / $summary['total'] * 100, 1 ) : 0;
							?>
							<tr>
								<td><span class="rcb-badge" style="background:<?php echo esc_attr( $meta['color'] ); ?>1a;color:<?php echo esc_attr( $meta['color'] ); ?>;"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span></td>
								<td><span class="rcb-progress"><span style="width:<?php echo esc_attr( min( 100, round( $value / $max_type * 100 ) ) ); ?>%;background:<?php echo esc_attr( $meta['color'] ); ?>;"></span></span></td>
								<td><strong><?php echo number_format( $value, 0, ',', ' ' ); ?></strong></td>
								<td><?php echo esc_html( $part ); ?> %</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div>
			<div class="rcb-card">
				<div class="rcb-card-head"><h2>IP les plus actives</h2></div>
				<?php if ( empty( $top_ips ) ) : ?>
					<p class="rcb-empty">Aucune IP enregistrée pour le moment.</p>
				<?php else : ?>
					<div class="rcb-table-wrap">
						<table class="rcb-table">
							<thead><tr><th>#</th><th>Adresse IP</th><th>Tentatives</th><th>Dernière activité</th></tr></thead>
							<tbody>
								<?php foreach ( $top_ips as $i => $ip ) : ?>
									<tr>
										<td><?php echo (int) $i + 1; ?></td>
										<td><code><?php echo esc_html( $ip['ip'] ); ?></code></td>
										<td><strong><?php echo number_format( $ip['n'], 0, ',', ' ' ); ?></strong></td>
										<td><?php echo esc_html( $ip['last'] ? date_i18n( 'd/m/Y H:i', $ip['last'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : '—' ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="rcb-card">
				<div class="rcb-card-head"><h2>Dernière tentative</h2></div>
				<?php if ( empty( $summary['last']['time'] ) ) : ?>
					<p class="rcb-empty">Aucune tentative enregistrée.</p>
				<?php else : ?>
					<ul class="rcb-syslist">
						<li><span>Date &amp; heure</span><strong><?php echo esc_html( $summary['last']['time'] ); ?></strong></li>
						<li><span>Type</span><strong><?php echo isset( $types[ $summary['last']['type'] ] ) ? esc_html( $types[ $summary['last']['type'] ]['label'] ) : esc_html( $summary['last']['type'] ); ?></strong></li>
						<li><span>Adresse IP</span><strong><code><?php echo esc_html( $summary['last']['ip'] ); ?></code></strong></li>
						<li><span>Navigateur</span><strong class="rcb-ua"><?php echo esc_html( $summary['last']['ua'] ); ?></strong></li>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="rcb-card rcb-actions">
		<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( $export_url ); ?>">⬇️ Exporter en CSV</a>
		<form method="post" action="" class="rcb-inline-form" data-confirm="Réinitialiser toutes les statistiques ? Cette action est irréversible.">
			<?php wp_nonce_field( 'infinity_rcb_maintenance', 'infinity_rcb_maintenance_nonce' ); ?>
			<button type="submit" name="infinity_rcb_reset_stats" value="1" class="rcb-btn rcb-btn-danger">🗑️ Réinitialiser les statistiques</button>
		</form>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — les statistiques sont plafonnées à 60 jours d’historique pour préserver les performances.</p>
</div>
