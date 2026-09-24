<?php
/**
 * Moteur de statistiques : compteurs globaux, par type, par jour, IP uniques,
 * derniers évènements et données pour les graphiques.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Stats {

	const MAX_DAYS   = 60;
	const MAX_IPS    = 1500;
	const KEEP_IPS   = 1000;
	const MAX_RECENT = 25;

	/**
	 * Registre des types de protection suivis.
	 */
	public static function types() {
		return array(
			'right_click' => array( 'label' => 'Clic droit',                    'emoji' => '🖱️', 'color' => '#ef4444' ),
			'keyboard'    => array( 'label' => 'Raccourcis clavier',            'emoji' => '⌨️', 'color' => '#f59e0b' ),
			'devtools'    => array( 'label' => 'Outils de développement',       'emoji' => '🛠️', 'color' => '#6366f1' ),
			'copy'        => array( 'label' => 'Copier / coller',               'emoji' => '📋', 'color' => '#10b981' ),
			'selection'   => array( 'label' => 'Sélection de texte',            'emoji' => '🔤', 'color' => '#06b6d4' ),
			'drag_drop'   => array( 'label' => 'Glisser-déposer',               'emoji' => '🖐️', 'color' => '#8b5cf6' ),
			'print'       => array( 'label' => 'Impression',                    'emoji' => '🖨️', 'color' => '#ec4899' ),
			'screenshot'  => array( 'label' => 'Capture d’écran',               'emoji' => '📸', 'color' => '#f43f5e' ),
			'source_code' => array( 'label' => 'Code source (Ctrl+U)',          'emoji' => '💻', 'color' => '#3b82f6' ),
			'save_as'     => array( 'label' => 'Enregistrer sous (Ctrl+S)',     'emoji' => '💾', 'color' => '#14b8a6' ),
			'console'     => array( 'label' => 'Console (Ctrl+Shift+J)',        'emoji' => '⚙️', 'color' => '#84cc16' ),
		);
	}

	public static function defaults() {
		$stats = array(
			'total'      => 0,
			'by_type'    => array(),
			'by_day'     => array(),
			'unique_ips' => array(),
			'recent'     => array(),
			'last'       => array( 'time' => '', 'type' => '', 'ip' => '', 'ua' => '' ),
			'since'      => current_time( 'mysql' ),
		);
		foreach ( array_keys( self::types() ) as $type ) {
			$stats['by_type'][ $type ] = 0;
		}
		return $stats;
	}

	public function get_all() {
		$saved = get_option( INFINITY_RCB_STATS_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Enregistre une tentative (raccourci de track_batch).
	 */
	public function track( $type ) {
		return $this->track_batch( array( $type => 1 ) );
	}

	/**
	 * Enregistre un lot de tentatives {type: quantité} en une seule écriture.
	 */
	public function track_batch( $counts ) {
		$types  = array_keys( self::types() );
		$clean  = array();
		$total  = 0;

		foreach ( (array) $counts as $type => $n ) {
			$type = sanitize_key( (string) $type );
			$n    = min( 1000, absint( $n ) );
			if ( in_array( $type, $types, true ) && $n > 0 ) {
				$clean[ $type ] = $n;
				$total += $n;
			}
		}
		if ( 0 === $total ) {
			return false;
		}

		$stats = $this->get_all();
		$today = current_time( 'Y-m-d' );
		$ip    = self::client_ip();
		$ip    = $ip ? $ip : 'inconnu';
		$ua    = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: '';

		$stats['total'] += $total;

		if ( ! isset( $stats['by_day'][ $today ] ) || ! is_array( $stats['by_day'][ $today ] ) ) {
			$stats['by_day'][ $today ] = array( 'total' => 0 );
			foreach ( $types as $t ) {
				$stats['by_day'][ $today ][ $t ] = 0;
			}
		}
		$stats['by_day'][ $today ]['total'] += $total;

		// Derniers évènements : un par type présent dans le lot.
		foreach ( $clean as $type => $n ) {
			$stats['by_type'][ $type ] = ( isset( $stats['by_type'][ $type ] ) ? (int) $stats['by_type'][ $type ] : 0 ) + $n;
			$stats['by_day'][ $today ][ $type ] = ( isset( $stats['by_day'][ $today ][ $type ] ) ? (int) $stats['by_day'][ $today ][ $type ] : 0 ) + $n;

			array_unshift( $stats['recent'], array(
				'time' => current_time( 'mysql' ),
				'type' => $type,
				'ip'   => $ip,
			) );
		}
		if ( count( $stats['recent'] ) > self::MAX_RECENT ) {
			$stats['recent'] = array_slice( $stats['recent'], 0, self::MAX_RECENT );
		}

		// Historique limité à MAX_DAYS jours.
		if ( count( $stats['by_day'] ) > self::MAX_DAYS ) {
			ksort( $stats['by_day'] );
			$stats['by_day'] = array_slice( $stats['by_day'], -self::MAX_DAYS, null, true );
		}

		// IP uniques plafonnées.
		if ( ! isset( $stats['unique_ips'][ $ip ] ) || ! is_array( $stats['unique_ips'][ $ip ] ) ) {
			$stats['unique_ips'][ $ip ] = array( 'n' => 0, 'last' => 0 );
		}
		$stats['unique_ips'][ $ip ]['n'] += $total;
		$stats['unique_ips'][ $ip ]['last'] = time();
		if ( count( $stats['unique_ips'] ) > self::MAX_IPS ) {
			uasort( $stats['unique_ips'], function ( $a, $b ) {
				return $b['last'] - $a['last'];
			} );
			$stats['unique_ips'] = array_slice( $stats['unique_ips'], 0, self::KEEP_IPS, true );
		}

		// Dernière tentative : type le plus fréquent du lot.
		arsort( $clean );
		$top_type = '';
		foreach ( $clean as $k => $n ) {
			$top_type = $k;
			break;
		}
		$stats['last'] = array( 'time' => current_time( 'mysql' ), 'type' => $top_type, 'ip' => $ip, 'ua' => $ua );

		update_option( INFINITY_RCB_STATS_OPTION, $stats, false );
		return true;
	}

	/**
	 * Synthèse pour le tableau de bord et l'AJAX temps réel.
	 */
	public function summary() {
		$s     = $this->get_all();
		$week  = 0;
		$month = 0;
		$peak  = 0;
		$peak_day = '';

		for ( $i = 0; $i < 30; $i++ ) {
			$d = date_i18n( 'Y-m-d', strtotime( '-' . $i . ' day', current_time( 'timestamp' ) ) );
			if ( isset( $s['by_day'][ $d ]['total'] ) ) {
				$n = (int) $s['by_day'][ $d ]['total'];
				if ( $i < 7 ) {
					$week += $n;
				}
				$month += $n;
				if ( $n > $peak ) {
					$peak     = $n;
					$peak_day = $d;
				}
			}
		}

		$today = current_time( 'Y-m-d' );

		return array(
			'total'    => (int) $s['total'],
			'today'    => isset( $s['by_day'][ $today ]['total'] ) ? (int) $s['by_day'][ $today ]['total'] : 0,
			'week'     => $week,
			'month'    => $month,
			'avg'      => round( $month / 30, 1 ),
			'peak'     => $peak,
			'peak_day' => $peak_day,
			'unique'   => count( (array) $s['unique_ips'] ),
			'by_type'  => (array) $s['by_type'],
			'recent'   => array_values( (array) $s['recent'] ),
			'last'     => (array) $s['last'],
			'since'    => $s['since'],
		);
	}

	/**
	 * Données du graphique linéaire : $ derniers jours.
	 */
	public function chart( $days = 14 ) {
		$s     = $this->get_all();
		$types = self::types();
		$days  = max( 1, min( 60, (int) $days ) );
		$out   = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$d   = date_i18n( 'Y-m-d', strtotime( '-' . $i . ' day', current_time( 'timestamp' ) ) );
			$day = isset( $s['by_day'][ $d ] ) && is_array( $s['by_day'][ $d ] ) ? $s['by_day'][ $d ] : array();

			$row = array(
				'date'  => $d,
				'label' => date_i18n( 'd/m', strtotime( $d ) ),
				'total' => isset( $day['total'] ) ? (int) $day['total'] : 0,
				'detail'=> array(),
			);
			foreach ( $types as $type => $meta ) {
				if ( ! empty( $day[ $type ] ) ) {
					$row['detail'][ $type ] = (int) $day[ $type ];
				}
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Entrées du graphique en anneau (répartition par type).
	 */
	public function donut() {
		$s     = $this->get_all();
		$types = self::types();
		$out   = array();
		foreach ( $types as $type => $meta ) {
			$value = isset( $s['by_type'][ $type ] ) ? (int) $s['by_type'][ $type ] : 0;
			if ( $value > 0 ) {
				$out[] = array(
					'label' => $meta['label'],
					'emoji' => $meta['emoji'],
					'color' => $meta['color'],
					'value' => $value,
				);
			}
		}
		usort( $out, function ( $a, $b ) {
			return $b['value'] - $a['value'];
		} );
		return $out;
	}

	/**
	 * IP les plus actives.
	 */
	public function top_ips( $limit = 10 ) {
		$s     = $this->get_all();
		$out   = array();
		$types = self::types();
		foreach ( (array) $s['unique_ips'] as $ip => $data ) {
			$out[] = array(
				'ip'   => $ip,
				'n'    => isset( $data['n'] ) ? (int) $data['n'] : 0,
				'last' => isset( $data['last'] ) ? $data['last'] : 0,
			);
		}
		usort( $out, function ( $a, $b ) {
			return $b['n'] - $a['n'];
		} );
		return array_slice( $out, 0, $limit );
	}

	public function reset() {
		update_option( INFINITY_RCB_STATS_OPTION, self::defaults(), false );
	}

	/**
	 * IP du client (REMOTE_ADDR prioritaire, en-têtes de proxy en secours).
	 */
	public static function client_ip() {
		foreach ( array( 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}
}
