<?php
/**
 * Module Pro — options premium déverrouillées par une licence à vie.
 *
 * ARCHITECTURE DUALE (conformité WordPress.org) :
 * ce fichier est distribué UNIQUEMENT dans le build vendeur (GitHub). Il est
 * EXCLU du build WordPress.org — la version du répertoire officiel reste
 * 100 % gratuite et complète, sans la moindre restriction (guides 5-6 :
 * pas de trialware). Sur le canal vendeur, ces options cosmétiques avancées
 * enrichissent la licence à vie (support prioritaire + alertes e-mail +
 * options Pro) sans jamais toucher aux 16 protections, qui restent libres.
 *
 * Options concernées : logo personnalisé du message, CSS personnalisé,
 * filigrane d'images (texte, opacité, taille).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Pro {

	/**
	 * Options couvertes par le module Pro (canal vendeur uniquement).
	 */
	public static function features() {
		return array(
			'logo'      => array( 'Logo personnalisé du message', 'Votre logo PNG/SVG à la place du bouclier.' ),
			'css'       => array( 'CSS personnalisé', 'Stylez finement le message (rayon, couleurs, animation…).' ),
			'watermark' => array( 'Filigrane d’images', 'Texte, opacité et taille superposés à vos images.' ),
		);
	}

	/**
	 * Licence active ? (vérification cryptographique ECDSA via la classe
	 * Licence ; filtre idiomatique pour les intégrations vendeur).
	 */
	private static $unlocked = null;

	public static function unlocked() {
		if ( null === self::$unlocked ) {
			$license = new Infinity_RCB_License();
			$status  = apply_filters( 'infinity_rcb_pro_license_status', $license->status() );
			self::$unlocked = ( is_array( $status ) && isset( $status['code'] ) && 'active' === $status['code'] );
		}
		return self::$unlocked;
	}

	/**
	 * Carte de verrouillage affichée à la place d'une option Pro non débloquée.
	 */
	public static function lock_card( $feature = '' ) {
		$f     = self::features();
		$meta  = isset( $f[ $feature ] ) ? $f[ $feature ] : array( 'Option Pro', '' );
		$label = isset( $f[ $feature ] ) ? ' — ' . $meta[0] : '';
		?>
		<div class="rcb-pro-lock">
			<span class="rcb-pro-pill">PRO</span>
			<div>
				<strong><?php echo esc_html( $meta[0] ); ?></strong>
				<p><?php echo esc_html( $meta[1] ); ?> Option de l'édition Pro, débloquée par une <strong>licence à vie</strong> (dès 2 900 DA ≈ 11,90 €) avec support prioritaire.</p>
			</div>
			<a class="rcb-btn rcb-btn-primary rcb-btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-rcb-pro-license' ) ); ?>">🔓 Débloquer<?php echo esc_html( $label ); ?></a>
		</div>
		<?php
	}

	/**
	 * Pastille PRO accolée au libellé d'une option débloquée.
	 */
	public static function badge() {
		echo ' <span class="rcb-pro-pill rcb-pro-pill-ok">PRO</span>';
	}
}
