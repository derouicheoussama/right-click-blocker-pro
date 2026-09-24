<?php
/**
 * Journalisation : fichiers mensuels dans wp-uploads/infinity-rcb-pro/logs/,
 * lecture des dernières lignes, rotation et export CSV.
 *
 * Tous les chemins manipulés sont construits à partir du répertoire de base
 * et validés (normalisation + contrôle de préfixe) avant tout accès disque.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infinity_RCB_Logger {

	private $dir;
	private $levels = array( 'debug', 'info', 'warning', 'error' );

	public function __construct() {
		$uploads   = wp_upload_dir();
		$this->dir = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . 'infinity-rcb-pro/logs' );
	}

	public function dir() {
		return $this->dir;
	}

	/**
	 * Un chemin n'est accepté que s'il reste strictement à l'intérieur
	 * du répertoire de base des journaux (aucune remontée possible).
	 */
	private function is_safe_path( $path ) {
		$base = rtrim( $this->dir, '/' );
		$norm = wp_normalize_path( $path );
		if ( false !== strpos( $norm, '..' . '/' ) ) {
			return false;
		}
		return 0 === strpos( $norm, $base . '/' );
	}

	public function ensure_dir() {
		if ( ! is_dir( $this->dir ) ) {
			wp_mkdir_p( $this->dir );
		}
		return is_dir( $this->dir ) && wp_is_writable( $this->dir );
	}

	private function current_file() {
		$file = $this->dir . '/protection-' . current_time( 'Y-m' ) . '.log';
		return $this->is_safe_path( $file ) ? $file : '';
	}

	/**
	 * Écrit une entrée : [date] [NIVEAU] TYPE - IP: x - UA: y
	 */
	public function log( $type, $level = 'warning' ) {
		return $this->log_batch( array( $type => 1 ), $level );
	}

	/**
	 * Écrit un lot de tentatives {type: quantité} (une ligne par type).
	 */
	public function log_batch( $counts, $level = 'warning' ) {
		if ( ! in_array( $level, $this->levels, true ) ) {
			$level = 'warning';
		}
		$file = $this->current_file();
		if ( '' === $file || ! $this->ensure_dir() ) {
			return false;
		}

		$types = array_keys( Infinity_RCB_Stats::types() );
		$ip    = Infinity_RCB_Stats::client_ip();
		$ua    = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';
		$when  = current_time( 'Y-m-d H:i:s' );
		$lines = '';

		foreach ( (array) $counts as $type => $n ) {
			$type = sanitize_key( (string) $type );
			$n    = min( 1000, absint( $n ) );
			if ( ! in_array( $type, $types, true ) || $n < 1 ) {
				continue;
			}
			$lines .= sprintf(
				"[%s] [%s] %s%s - IP: %s - UA: %s\n",
				$when,
				strtoupper( $level ),
				strtoupper( $type ),
				$n > 1 ? ' x' . $n : '',
				$ip ? $ip : 'inconnu',
				$ua
			);
		}

		if ( '' === $lines ) {
			return false;
		}
		return file_put_contents( $file, $lines, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Liste des fichiers de journal valides, du plus récent au plus ancien.
	 */
	private function files() {
		if ( ! is_dir( $this->dir ) ) {
			return array();
		}
		$files = array();
		$found = (array) glob( $this->dir . '/*.log' );
		foreach ( $found as $file ) {
			if ( ! preg_match( '/^protection-\d{4}-\d{2}\.log$/', basename( $file ) ) ) {
				continue;
			}
			if ( ! $this->is_safe_path( $file ) ) {
				continue;
			}
			$files[] = wp_normalize_path( $file );
		}
		rsort( $files );
		return $files;
	}

	/**
	 * Lignes analysées, les plus récentes en premier.
	 */
	public function rows( $limit = 300 ) {
		$rows = array();
		foreach ( $this->files() as $file ) {
			$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if ( ! is_array( $lines ) ) {
				continue;
			}
			$lines = array_reverse( $lines );
			foreach ( $lines as $line ) {
				$rows[] = $this->parse( $line );
				if ( count( $rows ) >= $limit ) {
					return $rows;
				}
			}
		}
		return $rows;
	}

	private function parse( $line ) {
		$row = array(
			'time'  => '',
			'level' => '',
			'type'  => '',
			'count' => 1,
			'ip'    => '',
			'ua'    => '',
			'raw'   => $line,
		);
		if ( preg_match( '/^\[([0-9\- :]+)\] \[([A-Z]+)\] ([A-Z_]+)(?:\s+[xX](\d+))? - IP: (.*?) - UA: (.*)$/', $line, $m ) ) {
			$row['time']  = $m[1];
			$row['level'] = strtolower( $m[2] );
			$row['type']  = strtolower( $m[3] );
			$row['count'] = isset( $m[4] ) ? max( 1, (int) $m[4] ) : 1;
			$row['ip']    = $m[5];
			$row['ua']    = $m[6];
		}
		return $row;
	}

	public function clear() {
		$deleted = 0;
		foreach ( $this->files() as $file ) {
			if ( wp_is_writable( $file ) && wp_delete_file( $file ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}

	/**
	 * Rotation quotidienne : purge par rétention et plafonnement par nombre d'entrées.
	 */
	public function rotate( $retention_days = 30, $max_entries = 5000 ) {
		$retention_days = max( 0, (int) $retention_days );
		$max_entries    = max( 100, (int) $max_entries );

		foreach ( $this->files() as $file ) {
			if ( $retention_days > 0 && ( time() - (int) filemtime( $file ) ) > $retention_days * DAY_IN_SECONDS ) {
				wp_delete_file( $file );
				continue;
			}
			$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if ( is_array( $lines ) && count( $lines ) > $max_entries ) {
				$keep = implode( "\n", array_slice( $lines, -$max_entries ) ) . "\n";
				file_put_contents( $file, $keep, LOCK_EX );
			}
		}
	}

	/**
	 * Statistiques des journaux pour la page dédiée.
	 */
	public function stats() {
		$total_lines = 0;
		$total_size  = 0;
		$last_mod    = 0;
		$levels      = array( 'debug' => 0, 'info' => 0, 'warning' => 0, 'error' => 0 );
		$file_count  = 0;

		foreach ( $this->files() as $file ) {
			$file_count++;
			$total_size += (int) filesize( $file );
			$last_mod    = max( $last_mod, (int) filemtime( $file ) );

			$contents = file_get_contents( $file );
			if ( false !== $contents && '' !== $contents ) {
				foreach ( explode( "\n", $contents ) as $line ) {
					if ( '' === trim( $line ) ) { continue; }
					$total_lines++;
					foreach ( $levels as $level => $n ) {
						if ( false !== stripos( $line, '[' . strtoupper( $level ) . ']' ) ) {
							$levels[ $level ]++;
						}
					}
				}
			}
		}

		return array(
			'files'     => $file_count,
			'lines'     => $total_lines,
			'size'      => $total_size,
			'last_mod'  => $last_mod,
			'levels'    => $levels,
			'writable'  => $this->ensure_dir(),
			'dir'       => $this->dir,
		);
	}

	/**
	 * Ligne CSV échappée (délimiteur « ; », guillemets doublés).
	 */
	private function csv_line( $fields ) {
		$out = '';
		foreach ( $fields as $field ) {
			if ( '' !== $out ) {
				$out .= ';';
			}
			$out .= '"' . str_replace( '"', '""', (string) $field ) . '"';
		}
		return $out;
	}

	/**
	 * Export CSV des $limit dernières entrées, envoyé directement au navigateur.
	 */
	public function export_csv( $limit = 2000 ) {
		$csv  = "\xEF\xBB\xBF"; // BOM UTF-8 pour une ouverture correcte dans Excel.
		$csv .= $this->csv_line( array( 'Date', 'Niveau', 'Type', 'IP', 'User-Agent' ) ) . "\r\n";

		foreach ( $this->rows( $limit ) as $row ) {
			$csv .= $this->csv_line( array( $row['time'], $row['level'], $row['type'], $row['ip'], $row['ua'] ) ) . "\r\n";
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="infinity-rcb-journaux.csv"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		exit;
	}
}
