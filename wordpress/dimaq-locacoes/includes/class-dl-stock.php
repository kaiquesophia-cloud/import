<?php
/**
 * Estoque de produtos e peças: movimentações e saldo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Stock {

	public static function init() {
		add_filter( 'dl_can_delete_produtos', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_filter( 'dl_list_row_produtos', array( __CLASS__, 'list_row' ) );
	}

	/**
	 * Registra uma movimentação e atualiza o saldo.
	 *
	 * @param string $type entrada | saida | ajuste (ajuste define o saldo final).
	 */
	public static function move( $product_id, $type, $qty, $origin = 'manual', $origin_id = 0, $note = '', $cost = 0 ) {
		global $wpdb;
		$p = DL_DB::get( 'produtos', $product_id );
		if ( ! $p || 'servico' === $p['tipo'] ) {
			return false;
		}
		$qty = (float) $qty;
		if ( 'ajuste' === $type ) {
			$delta = $qty - (float) $p['estoque_atual'];
			$new   = $qty;
			$qty   = $delta;
		} else {
			$delta = 'entrada' === $type ? abs( $qty ) : -abs( $qty );
			$new   = (float) $p['estoque_atual'] + $delta;
		}
		$wpdb->insert(
			dl_table( 'estoque_mov' ),
			array(
				'produto_id' => $product_id,
				'tipo'       => $type,
				'qtd'        => $qty,
				'custo_unit' => (float) $cost,
				'origem'     => $origin,
				'origem_id'  => (int) $origin_id,
				'obs'        => mb_substr( (string) $note, 0, 250 ),
				'usuario_id' => get_current_user_id(),
				'criado_em'  => dl_now(),
			)
		);
		$update = array( 'estoque_atual' => $new );
		if ( 'entrada' === $type && $cost > 0 ) {
			$update['preco_custo'] = $cost;
		}
		DL_DB::update( 'produtos', $product_id, $update );
		return true;
	}

	/** Baixa (ou estorna, com $reverse) os produtos de um documento. */
	public static function apply_document( $doc_tipo, $doc_id, $label, $reverse = false ) {
		foreach ( DL_Items::get( $doc_tipo, $doc_id ) as $it ) {
			if ( 'produto' !== $it['ref_tipo'] || ! $it['ref_id'] ) {
				continue;
			}
			self::move( $it['ref_id'], $reverse ? 'entrada' : 'saida', $it['qtd'], $doc_tipo, $doc_id, ( $reverse ? 'Estorno ' : '' ) . $label );
		}
	}

	public static function list_row( $row ) {
		if ( 'servico' !== $row['tipo'] && (float) $row['estoque_minimo'] > 0 && (float) $row['estoque_atual'] <= (float) $row['estoque_minimo'] ) {
			$row['estoque_atual'] = $row['estoque_atual'] . ' ⚠';
		}
		return $row;
	}

	public static function can_delete( $can, $row ) {
		global $wpdb;
		$used = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'itens' ) . " WHERE ref_tipo = 'produto' AND ref_id = %d", $row['id'] ) );
		return $used ? 'Produto já usado em vendas ou OS — desative em vez de excluir.' : $can;
	}

	/** Últimas movimentações de um produto. */
	public static function history( $product_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'estoque_mov' ) . ' WHERE produto_id = %d ORDER BY id DESC LIMIT 30', $product_id ), ARRAY_A );
	}

	/** Produtos no estoque mínimo ou abaixo. */
	public static function low_stock() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'produtos' ) . " WHERE ativo = 1 AND tipo <> 'servico' AND estoque_minimo > 0 AND estoque_atual <= estoque_minimo ORDER BY nome", ARRAY_A );
	}
}
