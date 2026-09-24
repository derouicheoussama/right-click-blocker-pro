<?php
/**
 * Carte « 👀 Aperçu du message » — rendu exact du message visiteur dans un
 * cadre navigateur, avec test en direct (styles, positions, messages).
 *
 * $preview_context : 'dashboard' (réglages sauvegardés, lecture seule)
 *                 ou 'settings' (synchronisée en direct avec le formulaire).
 *
 * Variables requises : $options, $types, $styles (fournies par render_page()).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$preview_context = isset( $preview_context ) ? $preview_context : 'dashboard';
$ap              = $options['appearance'];
$style_meta      = isset( $styles[ $ap['style'] ] ) ? $styles[ $ap['style'] ] : $styles['st1'];

// Texte de copyright calculé comme côté front ({annee}, {site}, {url}).
$pv_copy = str_replace(
	array( '{annee}', '{site}', '{url}' ),
	array( date_i18n( 'Y' ), get_bloginfo( 'name' ), home_url( '/' ) ),
	(string) $ap['copyright_text']
);

// Position du toast dans le cadre d'aperçu (le CSS public impose !important).
$pv_toast_style = 'position:absolute !important;left:50% !important;max-width:min(88%,520px) !important;';
if ( 'center' === $ap['position'] ) {
	$pv_toast_style .= 'top:50% !important;bottom:auto !important;transform:translate(-50%,-50%) !important;';
} elseif ( 'bottom' === $ap['position'] ) {
	$pv_toast_style .= 'bottom:16px !important;top:auto !important;transform:translateX(-50%) !important;';
} else {
	$pv_toast_style .= 'top:16px !important;bottom:auto !important;transform:translateX(-50%) !important;';
}

// Finitions (couleurs personnalisées, rayon, taille, ombre) comme le front.
$pv_inner_style = '';
if ( ! empty( $ap['custom_bg'] ) )   { $pv_inner_style .= 'background:' . esc_attr( $ap['custom_bg'] ) . ';'; }
if ( ! empty( $ap['custom_text'] ) ) { $pv_inner_style .= 'color:' . esc_attr( $ap['custom_text'] ) . ';'; }
$pv_inner_style .= 'border-radius:' . (int) $ap['radius'] . 'px;';
$pv_inner_style .= 'font-size:' . (int) $ap['font_size'] . 'px;';
if ( empty( $ap['shadow'] ) )        { $pv_inner_style .= 'box-shadow:none;'; }
?>
<div class="rcb-card" id="rcb-dash-preview" data-context="<?php echo esc_attr( $preview_context ); ?>">
	<div class="rcb-card-head">
		<h2>👀 Aperçu du message
			<?php if ( 'settings' === $preview_context ) : ?>
				<span class="rcb-pill rcb-pill-ok"><span class="rcb-dot"></span>suit vos réglages en direct</span>
			<?php else : ?>
				<span class="rcb-pill rcb-pill-soft">rendu exact pour vos visiteurs</span>
			<?php endif; ?>
		</h2>
		<?php if ( 'dashboard' === $preview_context ) : ?>
			<a class="rcb-btn rcb-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=appearance' ) ); ?>">🎨 Personnaliser</a>
		<?php endif; ?>
	</div>
	<p class="rcb-muted" style="margin:0 0 12px;">
		<?php if ( 'settings' === $preview_context ) : ?>
			Comportement <strong>temps réel</strong> : le message apparaît, reste le temps configuré puis disparaît — la croix le ferme, le bip sonore joue s'il est activé. Chaque réglage du formulaire s'y reflète instantanément.
		<?php else : ?>
			Voici exactement ce que voit un visiteur : apparition, durée configurée puis disparition — croix fonctionnelle et bip sonore inclus. Testez styles, positions et messages en direct.
		<?php endif; ?>
	</p>

	<div class="rcb-preview-stage" data-pos="<?php echo esc_attr( $ap['position'] ); ?>" data-site="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" data-url="<?php echo esc_attr( home_url( '/' ) ); ?>" data-dur="<?php echo (int) $ap['duration']; ?>" data-sound="<?php echo ! empty( $ap['sound'] ) ? '1' : '0'; ?>">
		<div class="rcb-preview-browser">
			<span class="rcb-preview-dot"></span><span class="rcb-preview-dot"></span><span class="rcb-preview-dot"></span>
			<span class="rcb-preview-url">🔒 <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
		</div>
		<div class="rcb-preview-page">
			<span class="rcb-preview-line" style="width:42%;height:14px;"></span>
			<span class="rcb-preview-line" style="width:88%;"></span>
			<span class="rcb-preview-line" style="width:76%;"></span>
			<span class="rcb-preview-line" style="width:81%;"></span>
			<span class="rcb-preview-img"></span>
		</div>

		<button type="button" class="rcb-preview-replay-hint" id="rcb-dash-replay-hint">▶ Rejouer l’aperçu</button>

		<div class="rcb-toast rcb-show rcb-pos-<?php echo esc_attr( $ap['position'] ); ?>" id="rcb-dash-toast" role="presentation" style="<?php echo esc_attr( $pv_toast_style ); ?>">
			<div class="rcb-inner rcb-st-<?php echo esc_attr( $ap['style'] ); ?> rcb-a-<?php echo esc_attr( $style_meta['anim'] ); ?>" style="<?php echo esc_attr( $pv_inner_style ); ?>">
				<span class="rcb-ico" <?php echo empty( $ap['show_icon'] ) ? 'style="display:none;"' : ''; ?>><?php printf( '%s', infinity_rcb_shield_svg( 'regular', 'plain' ) ) ?></span>
				<span class="rcb-body">
					<span class="rcb-title"><?php echo esc_html( $options['messages']['right_click'] ); ?></span>
					<span class="rcb-copy" <?php echo ( empty( $ap['copyright_enable'] ) || '' === trim( $pv_copy ) ) ? 'style="display:none;"' : ''; ?>><?php echo esc_html( $pv_copy ); ?></span>
				</span>
				<button type="button" class="rcb-close" tabindex="-1" <?php echo empty( $ap['show_close'] ) ? 'style="display:none;"' : ''; ?>>&times;</button>
				<span class="rcb-bar" <?php echo empty( $ap['show_progress'] ) ? 'style="display:none;"' : ''; ?>><i style="animation-duration:<?php echo (int) $ap['duration']; ?>ms;"></i></span>
			</div>
		</div>
	</div>

	<div class="rcb-preview-controls">
		<button type="button" class="rcb-btn rcb-btn-primary" id="rcb-dash-replay">▶ Rejouer l'aperçu</button>

		<div class="rcb-preview-group" role="group" aria-label="Position du message">
			<button type="button" class="rcb-dash-chip rcb-dash-pos <?php echo 'top' === $ap['position'] ? 'is-active' : ''; ?>" data-pos="top">⬆ Haut</button>
			<button type="button" class="rcb-dash-chip rcb-dash-pos <?php echo 'center' === $ap['position'] ? 'is-active' : ''; ?>" data-pos="center">⬌ Centre</button>
			<button type="button" class="rcb-dash-chip rcb-dash-pos <?php echo 'bottom' === $ap['position'] ? 'is-active' : ''; ?>" data-pos="bottom">⬇ Bas</button>
		</div>

		<div class="rcb-preview-group" role="group" aria-label="Type de message">
			<select id="rcb-dash-type" aria-label="Message à afficher">
				<?php foreach ( $types as $type => $meta ) : ?>
					<option value="<?php echo esc_attr( (string) $options['messages'][ $type ] ); ?>" <?php selected( $type, 'right_click' ); ?>><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="rcb-preview-styles" role="group" aria-label="Styles de message">
		<?php foreach ( $styles as $key => $meta ) : ?>
			<button type="button" class="rcb-dash-chip rcb-dash-style <?php echo $key === $ap['style'] ? 'is-active' : ''; ?>"
				data-st="<?php echo esc_attr( $key ); ?>" data-anim="<?php echo esc_attr( $meta['anim'] ); ?>">
				<span class="rcb-dash-swatch" style="background:<?php echo esc_attr( $meta['bg'] ); ?>;"></span><?php echo esc_html( $meta['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<p class="rcb-muted" style="margin:10px 0 0;">
		<?php if ( 'settings' === $preview_context ) : ?>
			Les pastilles Styles et Position mettent le formulaire à jour — pensez à <strong>💾 Enregistrer les réglages</strong> pour les appliquer au site.
		<?php else : ?>
			Style actif : <strong><?php echo esc_html( $style_meta['label'] ); ?></strong> · affichage <?php echo esc_html( round( (int) $ap['duration'] / 1000, 1 ) ); ?> s · position <?php echo esc_html( 'top' === $ap['position'] ? 'haut' : ( 'center' === $ap['position'] ? 'centre' : 'bas' ) ); ?> — personnalisez dans <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-settings&tab=appearance' ) ); ?>">Réglages → Apparence</a>.
		<?php endif; ?>
	</p>
</div>
