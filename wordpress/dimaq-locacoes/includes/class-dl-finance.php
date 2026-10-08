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
		add_action( 'dl_sidebar_financeiro', array( __CLASS__, 'sidebar' ) );
		add_filter( 'dl_list_row_financeiro', array( __CLASS__, 'list_row' ) );
		add_filter( 'dl_filter_options_financeiro', array( __CLASS__, 'filter_options' ), 10, 2 );
		add_filter( 'dl_filter_where_financeiro', array( __CLASS__, 'filter_where' ), 10, 3 );
		add_filter( 'dl_row_actions_financeiro', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'dl_can_delete_financeiro', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_action( 'dl_before_delete_financeiro', array( __CLASS__, 'before_delete' ) );
		add_action( 'dl_list_footer_financeiro', array( __CLASS__, 'list_footer' ) );
		add_action( 'admin_post_dl_finance', array( __CLASS__, 'handle_action' ) );
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

	public static function filter_options( $options, $filter ) {
		if ( 'status' === $filter ) {
			$options['vencido'] = 'Vencido';
			$options['hoje']    = 'Vence hoje';
		}
		return $options;
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

	public static function row_actions( $actions, $row ) {
		if ( 'pago' === $row['status'] && 'receber' === $row['tipo'] ) {
			$actions['recibo'] = '<a target="_blank" href="' . esc_url( DL_Documents::url( 'recibo', $row['id'] ) ) . '">Recibo</a>';
		} elseif ( in_array( $row['status'], array( 'aberto', 'vencido' ), true ) ) {
			$actions['edit'] = '<a href="' . esc_url( dl_admin_url( 'dl-financeiro', array( 'action' => 'edit', 'id' => $row['id'] ) ) ) . '">Baixar</a>';
		}
		return $actions;
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

	public static function list_footer( $where ) {
		global $wpdb;
		$t = $wpdb->get_row( 'SELECT SUM(CASE WHEN tipo=\'receber\' AND status<>\'cancelado\' THEN valor ELSE 0 END) rec, SUM(CASE WHEN tipo=\'pagar\' AND status<>\'cancelado\' THEN valor ELSE 0 END) pag, SUM(CASE WHEN tipo=\'receber\' THEN valor_pago ELSE 0 END) recp, SUM(CASE WHEN tipo=\'pagar\' THEN valor_pago ELSE 0 END) pagp FROM ' . dl_table( 'financeiro' ) . " WHERE {$where}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		echo '<div class="dl-card dl-totals"><strong>Totais do filtro:</strong> A receber ' . esc_html( dl_money( $t['rec'] ) ) . ' (recebido ' . esc_html( dl_money( $t['recp'] ) ) . ') · A pagar ' . esc_html( dl_money( $t['pag'] ) ) . ' (pago ' . esc_html( dl_money( $t['pagp'] ) ) . ')</div>';
	}

	/* ------------------------------------------------------------- lateral */

	public static function sidebar( $f ) {
		$id = (int) $f['id'];
		echo '<div class="dl-card"><h3>' . ( 'receber' === $f['tipo'] ? 'Recebimento' : 'Pagamento' ) . '</h3>';
		if ( 'aberto' === $f['status'] ) {
			$late    = max( 0, dl_days_between( $f['vencimento'], dl_today() ) );
			$charges = 'receber' === $f['tipo'] ? DL_Pricing::overdue_charges( self::remaining( $f ), $late, dl_opt( 'multa_vencimento_pct', 2 ), dl_opt( 'juros_mes_pct', 1 ) ) : array( 'multa' => 0, 'juros' => 0 );
			if ( $late > 0 ) {
				echo '<p class="dl-alert">Vencido há ' . (int) $late . ' dia(s).</p>';
			}
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="dl-inline-form">';
			wp_nonce_field( 'dl_finance_' . $id );
			echo '<input type="hidden" name="action" value="dl_finance"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="op" value="baixar">';
			echo '<label>Data<br><input type="date" name="data" value="' . esc_attr( dl_today() ) . '" required></label>';
			echo '<label>Valor principal pago<br><input type="number" step="0.01" min="0.01" name="valor" value="' . esc_attr( self::remaining( $f ) ) . '" required></label>';
			echo '<label>Multa<br><input type="number" step="0.01" min="0" name="multa" value="' . esc_attr( $charges['multa'] ) . '"></label>';
			echo '<label>Juros<br><input type="number" step="0.01" min="0" name="juros" value="' . esc_attr( $charges['juros'] ) . '"></label>';
			echo '<label>Desconto<br><input type="number" step="0.01" min="0" name="desconto" value="0"></label>';
			echo '<label>Forma<br><select name="forma">';
			foreach ( dl_payment_methods() as $k => $l ) {
				echo '<option value="' . esc_attr( $k ) . '" ' . selected( $f['forma_pagamento'], $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			echo '</select></label>';
			echo '<p class="description">Se pagar menos que o principal, o saldo vira um novo lançamento com o mesmo vencimento.</p>';
			echo '<button class="button button-primary">Confirmar baixa</button></form>';
			self::small_form( $id, 'cancelar', 'Cancelar lançamento', 'Cancelar este lançamento?' );
		} elseif ( 'pago' === $f['status'] ) {
			echo '<p>Pago em ' . esc_html( dl_date( $f['data_pagamento'] ) ) . ': <strong>' . esc_html( dl_money( (float) $f['valor_pago'] + (float) $f['juros'] + (float) $f['multa'] - (float) $f['desconto'] ) ) . '</strong></p>';
			if ( 'receber' === $f['tipo'] ) {
				echo '<p><a class="button" target="_blank" href="' . esc_url( DL_Documents::url( 'recibo', $id ) ) . '">Imprimir recibo</a></p>';
			}
			self::small_form( $id, 'estornar', 'Estornar baixa', 'Estornar este pagamento?' );
		} else {
			self::small_form( $id, 'reabrir', 'Reabrir lançamento' );
		}
		if ( $f['origem'] && 'manual' !== $f['origem'] ) {
			$map = array( 'contrato' => array( 'dl-contratos', 'contratos' ), 'venda' => array( 'dl-vendas', 'vendas' ), 'os' => array( 'dl-os', 'ordens_servico' ) );
			if ( isset( $map[ $f['origem'] ] ) ) {
				echo '<p>Origem: <a href="' . esc_url( dl_admin_url( $map[ $f['origem'] ][0], array( 'action' => 'edit', 'id' => $f['origem_id'] ) ) ) . '">' . esc_html( DL_DB::label( $map[ $f['origem'] ][1], $f['origem_id'], 'numero' ) ) . '</a></p>';
			}
		}
		echo '</div>';
	}

	private static function small_form( $id, $op, $label, $confirm = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="dl-inline-form"' . ( $confirm ? ' onsubmit="return confirm(\'' . esc_js( $confirm ) . '\');"' : '' ) . '>';
		wp_nonce_field( 'dl_finance_' . $id );
		echo '<input type="hidden" name="action" value="dl_finance"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
		echo '<button class="button">' . esc_html( $label ) . '</button></form>';
	}

	public static function handle_action() {
		dl_require_cap( 'dl_financeiro' );
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'dl_finance_' . $id );
		$f = DL_DB::get( 'financeiro', $id );
		if ( ! $f ) {
			wp_die( 'Lançamento não encontrado.' );
		}
		$back = dl_admin_url( 'dl-financeiro', array( 'action' => 'edit', 'id' => $id ) );
		$op   = sanitize_key( $_POST['op'] ?? '' );

		if ( 'baixar' === $op && 'aberto' === $f['status'] ) {
			$date  = sanitize_text_field( wp_unslash( $_POST['data'] ?? dl_today() ) );
			$paid  = round( dl_decimal( wp_unslash( $_POST['valor'] ?? 0 ) ), 2 );
			$due   = self::remaining( $f );
			if ( $paid <= 0 ) {
				dl_redirect( $back, 'Informe o valor pago.', 'error' );
			}
			$paid = min( $paid, $due );
			self::pay( $f, $date, $paid, dl_decimal( wp_unslash( $_POST['juros'] ?? 0 ) ), dl_decimal( wp_unslash( $_POST['multa'] ?? 0 ) ), dl_decimal( wp_unslash( $_POST['desconto'] ?? 0 ) ), sanitize_key( $_POST['forma'] ?? '' ) );
			dl_redirect( $back, $paid < $due ? 'Baixa parcial registrada; saldo de ' . dl_money( $due - $paid ) . ' lançado em aberto.' : 'Baixa registrada.' );
		}
		if ( 'estornar' === $op && 'pago' === $f['status'] ) {
			DL_DB::update( 'financeiro', $id, array( 'status' => 'aberto', 'valor_pago' => 0, 'data_pagamento' => null, 'juros' => 0, 'multa' => 0, 'desconto' => 0 ) );
			dl_log( 'financeiro', $id, 'Baixa estornada' );
			dl_redirect( $back, 'Baixa estornada.' );
		}
		if ( 'cancelar' === $op && 'aberto' === $f['status'] ) {
			self::unbill( $f );
			DL_DB::update( 'financeiro', $id, array( 'status' => 'cancelado' ) );
			dl_log( 'financeiro', $id, 'Cancelado' );
			dl_redirect( $back, 'Lançamento cancelado.' );
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
			dl_redirect( $back, 'Lançamento reaberto.' );
		}
		dl_redirect( $back );
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

	/** Lista de lançamentos ligados a um documento (lateral do contrato, venda, OS). */
	public static function render_linked( $origin, $origin_id ) {
		if ( ! current_user_can( 'dl_financeiro' ) ) {
			return;
		}
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'financeiro' ) . ' WHERE origem = %s AND origem_id = %d ORDER BY vencimento', $origin, $origin_id ), ARRAY_A );
		if ( ! $rows ) {
			return;
		}
		echo '<div class="dl-card"><h3>Cobranças</h3><table class="widefat striped"><tbody>';
		foreach ( $rows as $r ) {
			$st = 'aberto' === $r['status'] && $r['vencimento'] < dl_today() ? 'vencido' : $r['status'];
			echo '<tr><td><a href="' . esc_url( dl_admin_url( 'dl-financeiro', array( 'action' => 'edit', 'id' => $r['id'] ) ) ) . '">' . esc_html( dl_date( $r['vencimento'] ) ) . '</a></td><td>' . esc_html( dl_money( $r['valor'] ) ) . '</td><td>' . dl_badge( 'financeiro', $st ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table></div>';
	}
}
