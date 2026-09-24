<?php
/**
 * Page : Journaux — consultation, filtrage et export des évènements.
 *
 * Variables fournies par Infinity_RCB_Admin::render_page() :
 * $options, $types, $summary, $log_stats, $log_rows, $top_ips, $styles, $notice, $cron_next
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$export_url = wp_nonce_url( admin_url( 'admin.php?page=infinity-rcb-pro-logs&rcb_export=logs' ), 'infinity_rcb_export', 'rcb_nonce' );
$level_colors = array(
	'debug'   => '#64748b',
	'info'    => '#3b82f6',
	'warning' => '#f59e0b',
	'error'   => '#ef4444',
);
?>
<div class="wrap rcb-wrap">

	<div class="rcb-hero rcb-hero-compact">
		<div class="rcb-hero-brand">
			<div class="rcb-logo"><?php printf( '%s', infinity_rcb_shield_svg() ) ?></div>
			<div>
				<h1>Journaux</h1>
				<p>Historique complet des tentatives bloquées</p>
			</div>
		</div>
		<div class="rcb-hero-side">
			<span class="rcb-pill rcb-pill-soft"><?php echo (int) $log_stats['files']; ?> fichier(s)</span>
			<span class="rcb-pill rcb-pill-<?php echo $log_stats['writable'] ? 'ok' : 'off'; ?>"><span class="rcb-dot"></span><?php echo $log_stats['writable'] ? 'Répertoire inscriptible' : 'Répertoire non inscriptible'; ?></span>
		</div>
	</div>

	<?php if ( 'logs-cleared' === $notice ) : ?>
		<div class="rcb-notice rcb-notice-ok">✅ Tous les fichiers de journal ont été supprimés.</div>
	<?php endif; ?>

	<div class="rcb-kpis">
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#6366f11a;color:#6366f1;">📜</span>
			<div><em data-count="<?php echo (int) $log_stats['lines']; ?>">0</em><small>Entrées totales</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#10b9811a;color:#10b981;">💾</span>
			<div><em><?php echo esc_html( size_format( $log_stats['size'] ) ); ?></em><small>Taille cumulée</small></div>
		</div>
		<div class="rcb-card rcb-kpi">
			<span class="rcb-kpi-ico" style="background:#f59e0b1a;color:#f59e0b;">🕒</span>
			<div><em><?php echo esc_html( $log_stats['last_mod'] ? date_i18n( 'd/m/Y H:i', $log_stats['last_mod'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : '—' ); ?></em><small>Dernière écriture</small></div>
		</div>
		<?php foreach ( $log_stats['levels'] as $level => $count ) : ?>
			<div class="rcb-card rcb-kpi">
				<span class="rcb-kpi-ico" style="background:<?php echo esc_attr( $level_colors[ $level ] ); ?>1a;color:<?php echo esc_attr( $level_colors[ $level ] ); ?>;"><?php echo 'warning' === $level ? '⚠️' : ( 'error' === $level ? '⛔' : ( 'info' === $level ? 'ℹ️' : '🔍' ) ); ?></span>
				<div><em data-count="<?php echo (int) $count; ?>">0</em><small>Niveau <?php echo esc_html( $level ); ?></small></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="rcb-card">
		<div class="rcb-card-head">
			<h2>Entrées récentes <span class="rcb-pill rcb-pill-soft" id="rcb-log-count"><?php echo count( $log_rows ); ?></span></h2>
			<div class="rcb-log-filters">
				<select id="rcb-filter-type" class="rcb-select">
					<option value="">Tous les types</option>
					<?php foreach ( $types as $type => $meta ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<select id="rcb-filter-level" class="rcb-select">
					<option value="">Tous les niveaux</option>
					<?php foreach ( array_keys( $level_colors ) as $level ) : ?>
						<option value="<?php echo esc_attr( $level ); ?>"><?php echo esc_html( ucfirst( $level ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" id="rcb-filter-search" class="rcb-search" placeholder="Rechercher (IP, navigateur…)">
			</div>
		</div>

		<?php if ( empty( $log_rows ) ) : ?>
			<p class="rcb-empty">Aucune entrée de journal.<br>Les tentatives apparaîtront ici dès que la protection interceptera une action.</p>
		<?php else : ?>
			<div class="rcb-table-wrap rcb-table-scroll">
				<table class="rcb-table rcb-log-table" id="rcb-log-table">
					<thead><tr><th>Date &amp; heure</th><th>Niveau</th><th>Type</th><th>IP</th><th>Navigateur</th></tr></thead>
					<tbody>
						<?php foreach ( $log_rows as $row ) :
							$type_meta = $types[ $row['type'] ] ?? null;
							$lc = $level_colors[ $row['level'] ] ?? '#64748b';
							?>
							<tr data-type="<?php echo esc_attr( $row['type'] ); ?>" data-level="<?php echo esc_attr( $row['level'] ); ?>" data-search="<?php echo esc_attr( function_exists( 'mb_strtolower' ) ? mb_strtolower( $row['ip'] . ' ' . $row['ua'] . ' ' . $row['time'] ) : strtolower( $row['ip'] . ' ' . $row['ua'] . ' ' . $row['time'] ) ); ?>">
								<td class="rcb-nowrap"><?php echo esc_html( $row['time'] ); ?></td>
								<td><span class="rcb-badge" style="background:<?php echo esc_attr( $lc ); ?>1a;color:<?php echo esc_attr( $lc ); ?>;"><?php echo esc_html( strtoupper( $row['level'] ) ); ?></span></td>
								<td>
									<?php if ( $type_meta ) : ?>
										<span class="rcb-badge" style="background:<?php echo esc_attr( $type_meta['color'] ); ?>1a;color:<?php echo esc_attr( $type_meta['color'] ); ?>;"><?php echo esc_html( $type_meta['emoji'] . ' ' . $type_meta['label'] ); ?></span>
									<?php else : ?>
										<span class="rcb-badge"><?php echo esc_html( $row['type'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html( $row['ip'] ); ?></code></td>
								<td class="rcb-ua"><?php echo esc_html( $row['ua'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<p class="rcb-muted">Dossier : <code><?php echo esc_html( $log_stats['dir'] ); ?></code> — fichiers mensuels <code>protection-AAAA-MM.log</code>, purge automatique après <?php echo (int) $options['logging']['retention_days']; ?> jours.</p>
	</div>

	<div class="rcb-card rcb-actions">
		<a class="rcb-btn rcb-btn-primary" href="<?php echo esc_url( $export_url ); ?>">⬇️ Exporter en CSV</a>
		<a class="rcb-btn rcb-btn-ghost" href="" onclick="location.reload();return false;">🔄 Rafraîchir</a>
		<form method="post" action="" class="rcb-inline-form" data-confirm="Supprimer tous les fichiers de journal ? Cette action est irréversible.">
			<?php wp_nonce_field( 'infinity_rcb_maintenance', 'infinity_rcb_maintenance_nonce' ); ?>
			<button type="submit" name="infinity_rcb_clear_logs" value="1" class="rcb-btn rcb-btn-danger">🗑️ Vider les journaux</button>
		</form>
	</div>

	<p class="rcb-credit">Infinity RCB Pro v<?php echo esc_html( INFINITY_RCB_VERSION ); ?> — fichiers mensuels <code>protection-AAAA-MM.log</code> avec purge automatique.</p>
</div>
