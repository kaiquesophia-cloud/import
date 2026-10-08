<?php
/**
 * Disponibilidade da frota: quantas unidades de um equipamento estão livres num período.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Availability {

	/** Situações de contrato que ocupam a frota. */
	const BUSY = array( 'reservado', 'ativo' );

	/**
	 * Quantidade comprometida em contratos no intervalo [início, fim].
	 * Contrato ativo em atraso continua ocupando até ser devolvido.
	 */
	public static function committed( $equip_id, $start, $end, $exclude_contract = 0 ) {
		global $wpdb;
		$today = dl_today();
		$sql   = 'SELECT COALESCE(SUM(i.qtd - i.qtd_devolvida),0)
			FROM ' . dl_table( 'itens' ) . ' i
			JOIN ' . dl_table( 'contratos' ) . " c ON c.id = i.doc_id
			WHERE i.doc_tipo = 'contrato' AND i.ref_tipo = 'equipamento' AND i.ref_id = %d
			AND c.status IN ('reservado','ativo') AND c.id <> %d
			AND c.data_inicio <= %s
			AND ( CASE WHEN c.status = 'ativo' AND c.data_prev_devolucao < %s THEN %s ELSE c.data_prev_devolucao END ) >= %s";
		return (float) $wpdb->get_var( $wpdb->prepare( $sql, $equip_id, $exclude_contract, $end, $today, '9999-12-31', $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/** Unidades livres no período (0 se em manutenção ou inativo). */
	public static function available( $equip_id, $start, $end, $exclude_contract = 0 ) {
		$e = DL_DB::get( 'equipamentos', $equip_id );
		if ( ! $e || 'disponivel' !== $e['status'] ) {
			return 0;
		}
		$total = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
		return max( 0, $total - self::committed( $equip_id, $start, $end, $exclude_contract ) );
	}

	/** Unidades fora agora (em contrato ativo). */
	public static function out_now( $equip_id ) {
		global $wpdb;
		return (float) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(i.qtd - i.qtd_devolvida),0) FROM ' . dl_table( 'itens' ) . ' i JOIN ' . dl_table( 'contratos' ) . " c ON c.id = i.doc_id
				WHERE i.doc_tipo = 'contrato' AND i.ref_tipo = 'equipamento' AND i.ref_id = %d AND c.status = 'ativo'",
				$equip_id
			)
		);
	}

	/**
	 * Valida itens de um contrato contra a frota. Retorna lista de problemas (vazia = ok).
	 *
	 * @param array $items Linhas com ref_id e qtd.
	 */
	public static function check_items( array $items, $start, $end, $contract_id ) {
		$need = array();
		foreach ( $items as $it ) {
			if ( ( $it['ref_tipo'] ?? 'equipamento' ) !== 'equipamento' || empty( $it['ref_id'] ) ) {
				continue;
			}
			$pending                   = (float) $it['qtd'] - (float) ( $it['qtd_devolvida'] ?? 0 );
			$need[ (int) $it['ref_id'] ] = ( $need[ (int) $it['ref_id'] ] ?? 0 ) + $pending;
		}
		$problems = array();
		foreach ( $need as $equip_id => $qty ) {
			if ( $qty <= 0 ) {
				continue;
			}
			$free = self::available( $equip_id, $start, $end, $contract_id );
			if ( $qty > $free ) {
				$e          = DL_DB::get( 'equipamentos', $equip_id );
				$reason     = $e && 'disponivel' !== $e['status'] ? ' (' . ( dl_statuses( 'equipamento' )[ $e['status'] ] ?? $e['status'] ) . ')' : '';
				$problems[] = sprintf( '%s: pedido %s, livre %s no período%s', $e ? $e['nome'] : '#' . $equip_id, dl_num( $qty, 0 ), dl_num( $free, 0 ), $reason );
			}
		}
		return $problems;
	}

	/**
	 * Agenda de ocupação: contratos que usam o equipamento a partir de uma data.
	 */
	public static function schedule( $equip_id, $from = '' ) {
		global $wpdb;
		$from = $from ? $from : dl_add_days( dl_today(), -30 );
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT c.id, c.numero, c.status, c.data_inicio, c.data_prev_devolucao, c.cliente_id, i.qtd, i.qtd_devolvida
				FROM ' . dl_table( 'itens' ) . ' i JOIN ' . dl_table( 'contratos' ) . " c ON c.id = i.doc_id
				WHERE i.doc_tipo = 'contrato' AND i.ref_tipo = 'equipamento' AND i.ref_id = %d
				AND c.status IN ('orcamento','solicitacao','reservado','ativo') AND ( c.data_prev_devolucao >= %s OR c.status = 'ativo' )
				ORDER BY c.data_inicio ASC LIMIT 50",
				$equip_id,
				$from
			),
			ARRAY_A
		);
	}
}
