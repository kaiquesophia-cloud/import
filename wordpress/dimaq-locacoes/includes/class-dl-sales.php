<?php
/**
 * Vendas de produtos e peças: confirma → baixa estoque e gera contas a receber.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Sales {

	public static function init() {
		add_filter( 'dl_new_row_vendas', array( __CLASS__, 'new_row' ) );
		add_filter( 'dl_before_save_vendas', array( __CLASS__, 'before_save' ), 10, 3 );
		add_action( 'dl_after_save_vendas', array( __CLASS__, 'after_save' ), 10, 3 );
		add_action( 'dl_items_saved_venda', array( __CLASS__, 'recalc' ) );
		add_filter( 'dl_can_delete_vendas', array( __CLASS__, 'can_delete' ), 10, 2 );
	}

	public static function new_row( $row ) {
		$row['data_venda']          = dl_today();
		$row['primeiro_vencimento'] = dl_today();
		$row['status']              = 'orcamento';
		return $row;
	}

	public static function before_save( $data, $id, $old ) {
		if ( $old && 'confirmada' === $old['status'] && isset( $data['status'] ) && 'orcamento' === $data['status'] ) {
			return new WP_Error( 'status', 'Venda confirmada não volta a orçamento — cancele e crie outra.' );
		}
		if ( $old && 'cancelada' === $old['status'] && isset( $data['status'] ) && 'cancelada' !== $data['status'] ) {
			return new WP_Error( 'status', 'Venda cancelada não pode ser reativada.' );
		}
		return $data;
	}

	public static function recalc( $id ) {
		$v = DL_DB::get( 'vendas', $id );
		if ( ! $v || $v['estoque_baixado'] ) {
			return;
		}
		$sub = DL_Items::sum( 'venda', $id );
		DL_DB::update( 'vendas', $id, array( 'subtotal' => $sub, 'total' => max( 0, round( $sub + (float) $v['valor_frete'] - (float) $v['desconto'], 2 ) ) ) );
	}

	public static function after_save( $id, $data, $old ) {
		$v = DL_DB::get( 'vendas', $id );
		if ( empty( $v['numero'] ) ) {
			DL_DB::update( 'vendas', $id, array( 'numero' => dl_doc_number( dl_opt( 'prefixo_venda', 'VD' ), $id, $v['criado_em'] ) ) );
		}
		self::recalc( $id );
		$v = DL_DB::get( 'vendas', $id );
		if ( 'confirmada' === $v['status'] && ! $v['estoque_baixado'] ) {
			self::confirm( $v );
		}
		if ( 'cancelada' === $v['status'] && $old && 'cancelada' !== $old['status'] ) {
			self::cancel( $v );
		}
	}

	public static function confirm( $v ) {
		if ( ! DL_Items::get( 'venda', $v['id'] ) ) {
			DL_DB::update( 'vendas', $v['id'], array( 'status' => 'orcamento' ) );
			dl_notice( 'Adicione itens antes de confirmar a venda.', 'error' );
			return;
		}
		DL_Stock::apply_document( 'venda', $v['id'], 'Venda ' . $v['numero'] );
		if ( (float) $v['total'] > 0 && ! $v['faturado'] ) {
			DL_Finance::create_installments(
				array(
					'tipo'            => 'receber',
					'descricao'       => 'Venda ' . $v['numero'],
					'categoria'       => 'venda',
					'cliente_id'      => $v['cliente_id'],
					'origem'          => 'venda',
					'origem_id'       => $v['id'],
					'forma_pagamento' => $v['forma_pagamento'],
				),
				$v['total'],
				max( 1, (int) $v['parcelas'] ),
				$v['primeiro_vencimento'] ? $v['primeiro_vencimento'] : dl_today()
			);
		}
		DL_DB::update( 'vendas', $v['id'], array( 'estoque_baixado' => 1, 'faturado' => 1 ) );
		dl_log( 'vendas', $v['id'], 'Confirmada — estoque baixado e contas geradas' );
	}

	public static function cancel( $v ) {
		global $wpdb;
		if ( $v['estoque_baixado'] ) {
			DL_Stock::apply_document( 'venda', $v['id'], 'Venda ' . $v['numero'], true );
		}
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . dl_table( 'financeiro' ) . " SET status = 'cancelado' WHERE origem = 'venda' AND origem_id = %d AND status = 'aberto'", $v['id'] ) );
		DL_DB::update( 'vendas', $v['id'], array( 'estoque_baixado' => 0 ) );
		dl_log( 'vendas', $v['id'], 'Cancelada — estoque estornado e parcelas em aberto canceladas' );
	}

	public static function can_delete( $can, $row ) {
		return 'orcamento' === $row['status'] ? $can : 'Só orçamentos podem ser excluídos — cancele a venda.';
	}
}
