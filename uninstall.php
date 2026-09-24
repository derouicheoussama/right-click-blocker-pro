<?php
/**
 * Désinstallation d'Infinity RCB Pro :
 * suppression des options, des statistiques, de la tâche planifiée
 * et du dossier de journaux situé dans wp-uploads.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'infinity_rcb_pro_options' );
delete_option( 'infinity_rcb_pro_stats' );
delete_option( 'infinity_rcb_pro_license' );
delete_option( 'infinity_rcb_pro_orders' );
delete_option( 'infinity_rcb_pro_maillog' );
delete_option( 'infinity_rcb_pro_version' );
delete_option( 'infinity_rcb_pro_spam_count' );
wp_clear_scheduled_hook( 'infinity_rcb_daily_maintenance' );

// Suppression récursive du dossier de journaux dans wp-uploads (WP_Filesystem).
$rcb_uploads = wp_upload_dir();
$rcb_target  = trailingslashit( $rcb_uploads['basedir'] ) . 'infinity-rcb-pro';

if ( is_dir( $rcb_target ) ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$rcb_fs_ok = WP_Filesystem();
	if ( $rcb_fs_ok ) {
		global $wp_filesystem;
		if ( $wp_filesystem ) {
			$wp_filesystem->delete( $rcb_target, true );
		}
	}
}
