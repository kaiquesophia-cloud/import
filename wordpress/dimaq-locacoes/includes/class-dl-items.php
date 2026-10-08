<?php
/**
 * Itens de documentos: equipamentos do contrato, peças da OS e produtos da venda.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Items {

	public static function get( $doc_tipo, $doc_id ) {
		global $wpdb;
		if ( ! $doc_id ) {
			return array();
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'itens' ) . ' WHERE doc_tipo = %s AND doc_id = %d ORDER BY ordem ASC, id ASC', $doc_tipo, $doc_id ), ARRAY_A );
	}

	/**
	 * Grava os itens enviados. Itens que sumiram do formulário são removidos,
	 * exceto os que já tiveram devolução registrada.
	 */
	public static function save_items( $def, $doc_id, array $posted ) {
		global $wpdb;
		$doc_tipo = $def['doc_tipo'];
		$kind     = $def['items'];
		$existing = array();
		foreach ( self::get( $doc_tipo, $doc_id ) as $it ) {
			$existing[ (int) $it['id'] ] = $it;
		}

		$row = DL_DB::get( $def['table'], $doc_id );
		$locked = ( 'contrato' === $doc_tipo && in_array( $row['status'], array( 'encerrado', 'cancelado' ), true ) )
			|| ( 'venda' === $doc_tipo && in_array( $row['status'], array( 'cancelada' ), true ) )
			|| ( 'os' === $doc_tipo && ! empty( $row['estoque_baixado'] ) );
		if ( $locked || ( 'venda' === $doc_tipo && ! empty( $row['estoque_baixado'] ) ) ) {
			do_action( 'dl_items_saved_' . $doc_tipo, $doc_id );
			return true;
		}

		$rows  = array();
		$order = 0;
		foreach ( $posted as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$ref  = absint( $p['ref_id'] ?? 0 );
			$desc = sanitize_text_field( $p['descricao'] ?? '' );
			if ( ! $ref && '' === $desc ) {
				continue;
			}
			$qty     = max( 0, dl_decimal( $p['qtd'] ?? 1 ) );
			$unit    = max( 0, dl_decimal( $p['valor_unit'] ?? 0 ) );
			$disc    = max( 0, dl_decimal( $p['desconto'] ?? 0 ) );
			$ptype   = 'equipamento' === $kind ? sanitize_key( $p['periodo_tipo'] ?? 'diaria' ) : '';
			$periods = 'equipamento' === $kind ? max( 0, dl_decimal( $p['periodos'] ?? 1 ) ) : 1;
			if ( 'equipamento' === $kind && ! isset( dl_period_types()[ $ptype ] ) ) {
				$ptype = 'diaria';
			}
			if ( '' === $desc && $ref ) {
				$desc = DL_DB::label( 'equipamento' === $kind ? 'equipamentos' : 'produtos', $ref );
			}
			$rows[] = array(
				'id'           => absint( $p['id'] ?? 0 ),
				'doc_tipo'     => $doc_tipo,
				'doc_id'       => $doc_id,
				'ref_tipo'     => 'equipamento' === $kind ? 'equipamento' : 'produto',
				'ref_id'       => $ref,
				'descricao'    => $desc,
				'qtd'          => $qty,
				'periodo_tipo' => $ptype,
				'periodos'     => $periods,
				'valor_unit'   => $unit,
				'desconto'     => $disc,
				'total'        => DL_Pricing::item_total( $qty, $periods, $unit, $disc ),
				'ordem'        => $order++,
			);
		}

		$valid = apply_filters( 'dl_validate_items_' . $doc_tipo, true, $doc_id, $rows, $existing );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$kept = array();
		foreach ( $rows as $r ) {
			$item_id = $r['id'];
			unset( $r['id'] );
			if ( $item_id && isset( $existing[ $item_id ] ) ) {
				if ( (float) $existing[ $item_id ]['qtd_devolvida'] > $r['qtd'] ) {
					$r['qtd'] = (float) $existing[ $item_id ]['qtd_devolvida'];
					$r['total'] = DL_Pricing::item_total( $r['qtd'], $r['periodos'], $r['valor_unit'], $r['desconto'] );
				}
				$wpdb->update( dl_table( 'itens' ), $r, array( 'id' => $item_id ) );
				$kept[] = $item_id;
			} else {
				$wpdb->insert( dl_table( 'itens' ), $r );
				$kept[] = (int) $wpdb->insert_id;
			}
		}
		foreach ( $existing as $item_id => $it ) {
			if ( ! in_array( $item_id, $kept, true ) && (float) $it['qtd_devolvida'] <= 0 && 'adicional' !== $it['ref_tipo'] ) {
				$wpdb->delete( dl_table( 'itens' ), array( 'id' => $item_id ) );
			}
		}
		do_action( 'dl_items_saved_' . $doc_tipo, $doc_id );
		return true;
	}

	/** Soma dos itens de um documento. */
	public static function sum( $doc_tipo, $doc_id, $ref_tipo = null ) {
		global $wpdb;
		$sql = 'SELECT COALESCE(SUM(total),0) FROM ' . dl_table( 'itens' ) . ' WHERE doc_tipo = %s AND doc_id = %d';
		$arg = array( $doc_tipo, $doc_id );
		if ( $ref_tipo ) {
			$sql  .= ' AND ref_tipo = %s';
			$arg[] = $ref_tipo;
		}
		return (float) $wpdb->get_var( $wpdb->prepare( $sql, $arg ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
