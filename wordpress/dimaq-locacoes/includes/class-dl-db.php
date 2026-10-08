<?php
/**
 * Acesso simples às tabelas do plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_DB {

	public static function get( $table, $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dl_table( $table ) . ' WHERE id = %d', $id ), ARRAY_A );
	}

	public static function insert( $table, array $data ) {
		global $wpdb;
		$columns = self::columns( $table );
		if ( in_array( 'criado_em', $columns, true ) && empty( $data['criado_em'] ) ) {
			$data['criado_em'] = dl_now();
		}
		$data = array_intersect_key( $data, array_flip( $columns ) );
		unset( $data['id'] );
		$wpdb->insert( dl_table( $table ), $data );
		return (int) $wpdb->insert_id;
	}

	public static function update( $table, $id, array $data ) {
		global $wpdb;
		$columns = self::columns( $table );
		if ( in_array( 'atualizado_em', $columns, true ) ) {
			$data['atualizado_em'] = dl_now();
		}
		$data = array_intersect_key( $data, array_flip( $columns ) );
		unset( $data['id'] );
		if ( ! $data ) {
			return false;
		}
		return false !== $wpdb->update( dl_table( $table ), $data, array( 'id' => (int) $id ) );
	}

	public static function delete( $table, $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( dl_table( $table ), array( 'id' => (int) $id ) );
	}

	/** Lista de colunas reais da tabela (com cache por requisição). */
	public static function columns( $table ) {
		static $cache = array();
		if ( ! isset( $cache[ $table ] ) ) {
			global $wpdb;
			$cache[ $table ] = $wpdb->get_col( 'DESCRIBE ' . dl_table( $table ), 0 );
		}
		return $cache[ $table ];
	}

	/** Pares id => rótulo para preencher selects. */
	public static function options( $table, $label = 'nome', $where = '1=1' ) {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT id, ' . esc_sql( $label ) . ' AS label FROM ' . dl_table( $table ) . " WHERE {$where} ORDER BY label ASC LIMIT 2000", ARRAY_A );
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r['id'] ] = $r['label'];
		}
		return $out;
	}

	/** Rótulo de um registro relacionado (cliente, equipamento...). */
	public static function label( $table, $id, $field = 'nome' ) {
		static $cache = array();
		$key = $table . ':' . $id . ':' . $field;
		if ( ! isset( $cache[ $key ] ) ) {
			global $wpdb;
			$cache[ $key ] = $id ? (string) $wpdb->get_var( $wpdb->prepare( 'SELECT ' . esc_sql( $field ) . ' FROM ' . dl_table( $table ) . ' WHERE id = %d', $id ) ) : '';
		}
		return $cache[ $key ];
	}
}
