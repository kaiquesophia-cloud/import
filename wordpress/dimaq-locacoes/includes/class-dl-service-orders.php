<?php
/**
 * Ordens de serviço: manutenção preventiva e corretiva da frota e serviços para clientes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Service_Orders {

	const OPEN = array( 'aberta', 'em_andamento', 'aguardando_peca' );

	public static function init() {
		add_filter( 'dl_new_row_os', array( __CLASS__, 'new_row' ) );
		add_action( 'dl_after_save_os', array( __CLASS__, 'after_save' ), 10, 3 );
		add_action( 'dl_items_saved_os', array( __CLASS__, 'recalc' ) );
		add_action( 'dl_sidebar_os', array( __CLASS__, 'sidebar' ) );
		add_filter( 'dl_can_delete_os', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_action( 'dl_before_delete_os', array( __CLASS__, 'before_delete' ) );
		add_filter( 'dl_row_actions_os', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	public static function new_row( $row ) {
		$row['data_abertura'] = dl_today();
		$row['status']        = 'aberta';
		if ( ! empty( $row['equipamento_id'] ) ) {
			$row['horimetro'] = DL_DB::label( 'equipamentos', (int) $row['equipamento_id'], 'horimetro' );
		}
		return $row;
	}

	public static function recalc( $id ) {
		$os = DL_DB::get( 'ordens_servico', $id );
		if ( ! $os ) {
			return;
		}
		$sub = DL_Items::sum( 'os', $id );
		DL_DB::update( 'ordens_servico', $id, array( 'subtotal' => $sub, 'total' => round( $sub + (float) $os['mao_obra'], 2 ) ) );
	}

	public static function after_save( $id, $data, $old ) {
		$os = DL_DB::get( 'ordens_servico', $id );
		if ( empty( $os['numero'] ) ) {
			DL_DB::update( 'ordens_servico', $id, array( 'numero' => dl_doc_number( dl_opt( 'prefixo_os', 'OS' ), $id, $os['criado_em'] ) ) );
		}
		self::recalc( $id );
		self::apply_status( $id, $old ? $old['status'] : '' );
	}

	/** Efeitos da mudança de situação: frota, estoque e cobrança. */
	public static function apply_status( $id, $old_status ) {
		$os    = DL_DB::get( 'ordens_servico', $id );
		$equip = $os['equipamento_id'] ? DL_DB::get( 'equipamentos', (int) $os['equipamento_id'] ) : null;

		if ( in_array( $os['status'], self::OPEN, true ) ) {
			if ( $equip && 'unitario' === $equip['controle'] && 'externa' !== $os['tipo'] && 'disponivel' === $equip['status'] && DL_Availability::out_now( $equip['id'] ) <= 0 ) {
				DL_DB::update( 'equipamentos', $equip['id'], array( 'status' => 'manutencao' ) );
				dl_log( 'equipamentos', $equip['id'], 'Em manutenção', $os['numero'] );
			}
			return;
		}

		if ( 'concluida' === $os['status'] && 'concluida' !== $old_status ) {
			$update = array();
			if ( empty( $os['data_conclusao'] ) ) {
				$update['data_conclusao'] = dl_today();
			}
			if ( ! $os['estoque_baixado'] ) {
				DL_Stock::apply_document( 'os', $id, 'OS ' . $os['numero'] );
				$update['estoque_baixado'] = 1;
			}
			if ( $os['cobrar_cliente'] && $os['cliente_id'] && (float) $os['total'] > 0 && ! $os['faturado'] ) {
				DL_Finance::create_installments(
					array(
						'tipo'       => 'receber',
						'descricao'  => 'Serviço ' . $os['numero'],
						'categoria'  => 'servico',
						'cliente_id' => $os['cliente_id'],
						'origem'     => 'os',
						'origem_id'  => $id,
					),
					$os['total'],
					1,
					dl_today()
				);
				$update['faturado'] = 1;
			}
			DL_DB::update( 'ordens_servico', $id, $update );
			if ( $equip ) {
				$eq_update = array();
				if ( null !== $os['horimetro'] && (float) $os['horimetro'] > (float) $equip['horimetro'] ) {
					$eq_update['horimetro'] = $os['horimetro'];
				}
				if ( 'preventiva' === $os['tipo'] ) {
					$eq_update['ultima_manutencao_horas'] = null !== $os['horimetro'] ? $os['horimetro'] : $equip['horimetro'];
				}
				if ( $eq_update ) {
					DL_DB::update( 'equipamentos', $equip['id'], $eq_update );
				}
			}
			dl_log( 'os', $id, 'Concluída' );
		}

		if ( 'cancelada' === $os['status'] && 'cancelada' !== $old_status && $os['estoque_baixado'] ) {
			DL_Stock::apply_document( 'os', $id, 'OS ' . $os['numero'], true );
			DL_DB::update( 'ordens_servico', $id, array( 'estoque_baixado' => 0 ) );
		}

		if ( $equip && 'manutencao' === $equip['status'] && ! self::has_open( $equip['id'], $id ) ) {
			DL_DB::update( 'equipamentos', $equip['id'], array( 'status' => 'disponivel' ) );
			dl_log( 'equipamentos', $equip['id'], 'Liberado da manutenção', $os['numero'] );
		}
	}

	public static function has_open( $equip_id, $except = 0 ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'ordens_servico' ) . " WHERE equipamento_id = %d AND id <> %d AND status IN ('aberta','em_andamento','aguardando_peca')", $equip_id, $except ) );
	}

	/** OS de revisão aberta automaticamente na devolução. */
	public static function open_from_return( $contract, $equip, $text ) {
		$id = DL_DB::insert(
			'ordens_servico',
			array(
				'equipamento_id' => $equip['id'],
				'cliente_id'     => $contract['cliente_id'],
				'contrato_id'    => $contract['id'],
				'tipo'           => 'revisao',
				'status'         => 'aberta',
				'data_abertura'  => dl_today(),
				'defeito'        => 'Retorno do contrato ' . $contract['numero'] . ( $text ? ': ' . $text : '' ),
				'horimetro'      => $equip['horimetro'],
			)
		);
		DL_DB::update( 'ordens_servico', $id, array( 'numero' => dl_doc_number( dl_opt( 'prefixo_os', 'OS' ), $id ) ) );
		dl_log( 'os', $id, 'Aberta na devolução', $contract['numero'] );
		self::apply_status( $id, '' );
		return $id;
	}

	public static function can_delete( $can, $row ) {
		return $row['faturado'] ? 'OS faturada não pode ser excluída — cancele.' : $can;
	}

	public static function before_delete( $row ) {
		if ( $row['estoque_baixado'] ) {
			DL_Stock::apply_document( 'os', $row['id'], 'OS ' . $row['numero'] . ' excluída', true );
		}
	}

	public static function row_actions( $actions, $row ) {
		$actions['print'] = '<a target="_blank" href="' . esc_url( DL_Documents::url( 'os', $row['id'] ) ) . '">Imprimir</a>';
		return $actions;
	}

	public static function sidebar( $os ) {
		echo '<div class="dl-card"><h3>Resumo</h3><p>' . dl_badge( 'os', $os['status'] ) . '</p><p class="dl-big">' . esc_html( dl_money( $os['total'] ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p><a class="button" target="_blank" href="' . esc_url( DL_Documents::url( 'os', $os['id'] ) ) . '">Imprimir ordem de serviço</a></p>';
		echo '<p class="description">Ao marcar como <strong>Concluída</strong>: baixa as peças do estoque, libera o equipamento e, se marcado, gera a cobrança ao cliente.</p></div>';
		DL_Finance::render_linked( 'os', $os['id'] );
		DL_Fiscal::render_linked( 'os', $os['id'], $os );
	}

	/** Equipamentos com preventiva vencida pelo horímetro. */
	public static function preventive_due() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' AND manutencao_cada_horas > 0 AND horimetro - ultima_manutencao_horas >= manutencao_cada_horas ORDER BY nome", ARRAY_A );
	}
}
