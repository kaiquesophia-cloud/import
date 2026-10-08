<?php
/**
 * Financeiro: contas a receber e a pagar, parcelamento, baixa, estorno e recibo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Finance {

	public static function init() {
		add_filter( 'dl_new_row_financeiro', array( __CLASS__, 'new_row' ) );
		add_filter( 'dl_before_save_financeiro', array( __CLASS__, 'before_save' ), 10, 3 );
		add_filter( 'dl_list_row_financeiro', array( __CLASS__, 'list_row' ) );
		add_filter( 'dl_filter_where_financeiro', array( __CLASS__, 'filter_where' ), 10, 3 );
		add_filter( 'dl_can_delete_financeiro', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_action( 'dl_before_delete_financeiro', array( __CLASS__, 'before_delete' ) );
	}

	/**
	 * Cria lançamentos parcelados. $base traz tipo, descrição, cliente, origem...
	 *
	 * @return int[] ids criados.
	 */
	public static function create_installments( array $base, $total, $count, $first_due, $interval_days = 30 ) {
		$count = max( 1, min( 120, (int) $count ) );
		$parts = DL_Pricing::installments( $total, $count );
		$ids   = array();
		foreach ( $parts as $i => $value ) {
			$due   = 0 === $i ? $first_due : ( 30 === (int) $interval_days ? date( 'Y-m-d', strtotime( $first_due . ' +' . $i . ' months' ) ) : dl_add_days( $first_due, $i * max( 1, (int) $interval_days ) ) );
			$row   = array_merge(
				array(
					'tipo'    => 'receber',
					'emissao' => dl_today(),
					'status'  => 'aberto',
				),
				$base,
				array(
					'descricao'      => $base['descricao'] . ( $count > 1 ? ' (' . ( $i + 1 ) . '/' . $count . ')' : '' ),
					'parcela'        => $i + 1,
					'total_parcelas' => $count,
					'vencimento'     => $due,
					'valor'          => $value,
				)
			);
			$ids[] = DL_DB::insert( 'financeiro', $row );
		}
		return $ids;
	}

	/** Valor ainda devido de um lançamento (sem encargos). */
	public static function remaining( $f ) {
		return max( 0, round( (float) $f['valor'] - (float) $f['valor_pago'], 2 ) );
	}

	public static function client_overdue( $client_id ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(valor - valor_pago),0) FROM ' . dl_table( 'financeiro' ) . " WHERE tipo = 'receber' AND status = 'aberto' AND cliente_id = %d AND vencimento < %s", $client_id, dl_today() ) );
	}

	public static function client_open( $client_id ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(valor - valor_pago),0) FROM ' . dl_table( 'financeiro' ) . " WHERE tipo = 'receber' AND status = 'aberto' AND cliente_id = %d", $client_id ) );
	}

	/* ------------------------------------------------------- ganchos do CRUD */

	public static function new_row( $row ) {
		$row['emissao']    = dl_today();
		$row['vencimento'] = $row['vencimento'] ? $row['vencimento'] : dl_today();
		$row['tipo']       = in_array( $row['tipo'], array( 'receber', 'pagar' ), true ) ? $row['tipo'] : 'receber';
		$row['status']     = 'aberto';
		return $row;
	}

	public static function before_save( $data, $id, $old ) {
		if ( $old && 'pago' === $old['status'] ) {
			// Lançamento pago: só observações, categoria e conta podem mudar.
			return array_intersect_key( $data, array_flip( array( 'obs', 'categoria', 'conta', 'descricao' ) ) );
		}
		if ( isset( $data['valor'] ) && $data['valor'] <= 0 ) {
			return new WP_Error( 'valor', 'O valor precisa ser maior que zero.' );
		}
		if ( ! $id ) {
			$data['status'] = 'aberto';
			$data['origem'] = 'manual';
		}
		return $data;
	}

	public static function list_row( $row ) {
		if ( 'aberto' === $row['status'] && $row['vencimento'] < dl_today() ) {
			$row['status'] = 'vencido';
		}
		if ( 'pagar' === $row['tipo'] && ! $row['cliente_id'] && $row['fornecedor_id'] ) {
			$row['descricao'] .= ' — ' . DL_DB::label( 'fornecedores', $row['fornecedor_id'] );
		}
		return $row;
	}

	public static function filter_where( $custom, $filter, $value ) {
		global $wpdb;
		if ( 'status' === $filter && 'vencido' === $value ) {
			return $wpdb->prepare( "status = 'aberto' AND vencimento < %s", dl_today() );
		}
		if ( 'status' === $filter && 'hoje' === $value ) {
			return $wpdb->prepare( "status = 'aberto' AND vencimento = %s", dl_today() );
		}
		return $custom;
	}

	public static function can_delete( $can, $row ) {
		return 'pago' === $row['status'] ? 'Lançamento pago: estorne a baixa antes de excluir.' : $can;
	}

	public static function before_delete( $row ) {
		self::unbill( $row );
	}

	/** Ao cancelar/excluir cobrança de contrato, devolve o valor ao saldo a faturar. */
	private static function unbill( $row ) {
		if ( 'contrato' === $row['origem'] && 'receber' === $row['tipo'] && 'cancelado' !== $row['status'] ) {
			$c = DL_DB::get( 'contratos', (int) $row['origem_id'] );
			if ( $c ) {
				DL_DB::update( 'contratos', $c['id'], array( 'valor_faturado' => max( 0, round( (float) $c['valor_faturado'] - (float) $row['valor'], 2 ) ) ) );
			}
		}
	}

	/** Totais de um filtro da lista. */
	public static function totals( $where ) {
		global $wpdb;
		$t = $wpdb->get_row( "SELECT SUM(CASE WHEN tipo='receber' AND status<>'cancelado' THEN valor ELSE 0 END) rec, SUM(CASE WHEN tipo='pagar' AND status<>'cancelado' THEN valor ELSE 0 END) pag, SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE 0 END) recp, SUM(CASE WHEN tipo='pagar' THEN valor_pago ELSE 0 END) pagp FROM " . dl_table( 'financeiro' ) . " WHERE {$where}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array_map( 'floatval', (array) $t );
	}

	/** Situação considerando vencimento. */
	public static function status_of( $f ) {
		return 'aberto' === $f['status'] && $f['vencimento'] < dl_today() ? 'vencido' : $f['status'];
	}

	/** Multa e juros sugeridos para baixar hoje. */
	public static function suggested_charges( $f ) {
		$late = max( 0, dl_days_between( $f['vencimento'], dl_today() ) );
		if ( 'receber' !== $f['tipo'] || 'aberto' !== $f['status'] ) {
			return array( 'multa' => 0, 'juros' => 0, 'dias' => $late );
		}
		return DL_Pricing::overdue_charges( self::remaining( $f ), $late, dl_opt( 'multa_vencimento_pct', 2 ), dl_opt( 'juros_mes_pct', 1 ) ) + array( 'dias' => $late );
	}

	/**
	 * Baixa, estorno, cancelamento ou reabertura.
	 *
	 * @return array|WP_Error
	 */
	public static function perform( $f, $op, array $p ) {
		$id = (int) $f['id'];
		if ( 'baixar' === $op ) {
			if ( 'aberto' !== $f['status'] ) {
				return new WP_Error( 'status', 'Lançamento não está em aberto.' );
			}
			$date = sanitize_text_field( $p['data'] ?? '' );
			$date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : dl_today();
			$paid = round( dl_decimal( $p['valor'] ?? 0 ), 2 );
			$due  = self::remaining( $f );
			if ( $paid <= 0 ) {
				return new WP_Error( 'valor', 'Informe o valor pago.' );
			}
			$paid = min( $paid, $due );
			self::pay( $f, $date, $paid, dl_decimal( $p['juros'] ?? 0 ), dl_decimal( $p['multa'] ?? 0 ), dl_decimal( $p['desconto'] ?? 0 ), sanitize_key( $p['forma'] ?? '' ) );
			return array( 'message' => $paid < $due ? 'Baixa parcial registrada; saldo de ' . dl_money( $due - $paid ) . ' lançado em aberto.' : 'Baixa registrada.' );
		}
		if ( 'estornar' === $op && 'pago' === $f['status'] ) {
			DL_DB::update( 'financeiro', $id, array( 'status' => 'aberto', 'valor_pago' => 0, 'data_pagamento' => null, 'juros' => 0, 'multa' => 0, 'desconto' => 0 ) );
			dl_log( 'financeiro', $id, 'Baixa estornada' );
			return array( 'message' => 'Baixa estornada.' );
		}
		if ( 'cancelar' === $op && 'aberto' === $f['status'] ) {
			self::unbill( $f );
			DL_DB::update( 'financeiro', $id, array( 'status' => 'cancelado' ) );
			dl_log( 'financeiro', $id, 'Cancelado' );
			return array( 'message' => 'Lançamento cancelado.' );
		}
		if ( 'reabrir' === $op && 'cancelado' === $f['status'] ) {
			if ( 'contrato' === $f['origem'] && 'receber' === $f['tipo'] ) {
				$c = DL_DB::get( 'contratos', (int) $f['origem_id'] );
				if ( $c ) {
					DL_DB::update( 'contratos', $c['id'], array( 'valor_faturado' => round( (float) $c['valor_faturado'] + (float) $f['valor'], 2 ) ) );
				}
			}
			DL_DB::update( 'financeiro', $id, array( 'status' => 'aberto' ) );
			dl_log( 'financeiro', $id, 'Reaberto' );
			return array( 'message' => 'Lançamento reaberto.' );
		}
		return new WP_Error( 'acao', 'Ação não permitida nesta situação.' );
	}

	/** Registra pagamento; pagamento parcial gera lançamento com o saldo. */
	public static function pay( $f, $date, $paid, $interest = 0, $fine = 0, $discount = 0, $method = '' ) {
		$due = self::remaining( $f );
		if ( $paid < $due ) {
			$rest              = $f;
			unset( $rest['id'], $rest['criado_em'] );
			$rest['valor']      = round( $due - $paid, 2 );
			$rest['valor_pago'] = 0;
			$rest['juros']      = 0;
			$rest['multa']      = 0;
			$rest['desconto']   = 0;
			$rest['descricao']  = $f['descricao'] . ' (saldo)';
			$rest['status']     = 'aberto';
			DL_DB::insert( 'financeiro', $rest );
		}
		DL_DB::update(
			'financeiro',
			$f['id'],
			array(
				'status'          => 'pago',
				'valor'           => round( (float) $f['valor_pago'] + $paid, 2 ),
				'valor_pago'      => round( (float) $f['valor_pago'] + $paid, 2 ),
				'data_pagamento'  => $date,
				'juros'           => round( (float) $interest, 2 ),
				'multa'           => round( (float) $fine, 2 ),
				'desconto'        => round( (float) $discount, 2 ),
				'forma_pagamento' => $method ? $method : $f['forma_pagamento'],
			)
		);
		dl_log( 'financeiro', $f['id'], 'Baixado', dl_money( $paid ) );
		do_action( 'dl_finance_paid', $f['id'] );
	}

	/** Lançamentos ligados a um documento (contrato, venda, OS). */
	public static function linked( $origin, $origin_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'financeiro' ) . ' WHERE origem = %s AND origem_id = %d ORDER BY vencimento', $origin, $origin_id ), ARRAY_A );
		foreach ( $rows as &$r ) {
			$r['situacao'] = self::status_of( $r );
		}
		return $rows;
	}
}
