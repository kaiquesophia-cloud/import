<?php
/**
 * Ciclo da locação: orçamento → reserva → entrega (checklist de saída) → renovação →
 * devolução (checklist de retorno, atraso e avarias) → encerramento, com faturamento.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Contracts {

	public static function init() {
		add_filter( 'dl_new_row_contratos', array( __CLASS__, 'new_row' ) );
		add_filter( 'dl_before_save_contratos', array( __CLASS__, 'before_save' ), 10, 3 );
		add_action( 'dl_after_save_contratos', array( __CLASS__, 'after_save' ), 10, 3 );
		add_filter( 'dl_validate_items_contrato', array( __CLASS__, 'validate_items' ), 10, 4 );
		add_action( 'dl_items_saved_contrato', array( __CLASS__, 'recalc' ) );
		add_action( 'dl_sidebar_contratos', array( __CLASS__, 'sidebar' ) );
		add_action( 'dl_after_client_field_contratos', array( __CLASS__, 'client_warning' ) );
		add_filter( 'dl_can_delete_contratos', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_filter( 'dl_row_actions_contratos', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'dl_list_row_contratos', array( __CLASS__, 'list_row' ) );
		add_filter( 'dl_filter_options_contratos', array( __CLASS__, 'filter_options' ), 10, 2 );
		add_filter( 'dl_filter_where_contratos', array( __CLASS__, 'filter_where' ), 10, 3 );
		add_action( 'admin_post_dl_contract', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_post_dl_contract_move', array( __CLASS__, 'handle_move' ) );
		add_action( 'wp_ajax_dl_best_price', array( __CLASS__, 'ajax_best_price' ) );
	}

	/** Dias cobrados entre início e devolução (mínimo 1). */
	public static function rental_days( $start, $end ) {
		return max( 1, dl_days_between( $start, $end ), (int) dl_opt( 'locacao_minima_dias', 1 ) );
	}

	public static function is_late( $c ) {
		return 'ativo' === $c['status'] && $c['data_prev_devolucao'] && $c['data_prev_devolucao'] < dl_today();
	}

	/* ------------------------------------------------------- ganchos do CRUD */

	public static function new_row( $row ) {
		$row['data_inicio']         = $row['data_inicio'] ? $row['data_inicio'] : dl_today();
		$row['data_prev_devolucao'] = $row['data_prev_devolucao'] ? $row['data_prev_devolucao'] : dl_add_days( dl_today(), max( 1, (int) dl_opt( 'locacao_minima_dias', 1 ) ) );
		$row['validade_orcamento']  = dl_add_days( dl_today(), (int) dl_opt( 'validade_orcamento', 7 ) );
		$row['status']              = 'orcamento';
		return $row;
	}

	public static function before_save( $data, $id, $old ) {
		if ( ! empty( $data['data_inicio'] ) && ! empty( $data['data_prev_devolucao'] ) && $data['data_prev_devolucao'] < $data['data_inicio'] ) {
			return new WP_Error( 'datas', 'A devolução prevista não pode ser antes do início.' );
		}
		if ( ! $id ) {
			$data['status']     = 'orcamento';
			$data['criado_por'] = get_current_user_id();
			$data['origem']     = 'admin';
		}
		return $data;
	}

	public static function after_save( $id, $data, $old ) {
		$c = DL_DB::get( 'contratos', $id );
		if ( empty( $c['numero'] ) ) {
			DL_DB::update( 'contratos', $id, array( 'numero' => dl_doc_number( dl_opt( 'prefixo_contrato', 'LOC' ), $id, $c['criado_em'] ) ) );
		}
		self::recalc( $id );
		if ( $old && in_array( $c['status'], DL_Availability::BUSY, true ) && ( $old['data_inicio'] !== $c['data_inicio'] || $old['data_prev_devolucao'] !== $c['data_prev_devolucao'] ) ) {
			$problems = DL_Availability::check_items( DL_Items::get( 'contrato', $id ), $c['data_inicio'], $c['data_prev_devolucao'], $id );
			if ( $problems ) {
				set_transient( 'dl_notice_' . get_current_user_id(), array( 'type' => 'warning', 'message' => 'Datas salvas, mas há conflito de disponibilidade:<br>' . esc_html( implode( ' · ', $problems ) ) ), 60 );
			}
		}
	}

	public static function validate_items( $ok, $id, $rows, $existing ) {
		$c = DL_DB::get( 'contratos', $id );
		if ( ! $c || ! in_array( $c['status'], DL_Availability::BUSY, true ) ) {
			return $ok;
		}
		foreach ( $rows as &$r ) {
			if ( $r['id'] && isset( $existing[ $r['id'] ] ) ) {
				$r['qtd_devolvida'] = $existing[ $r['id'] ]['qtd_devolvida'];
			}
		}
		unset( $r );
		$problems = DL_Availability::check_items( $rows, $c['data_inicio'], $c['data_prev_devolucao'], $id );
		return $problems ? new WP_Error( 'disp', 'Itens não salvos — sem disponibilidade: ' . implode( ' · ', $problems ) ) : $ok;
	}

	/** Recalcula subtotal, adicionais e total do contrato. */
	public static function recalc( $id ) {
		$c = DL_DB::get( 'contratos', $id );
		if ( ! $c ) {
			return;
		}
		$sub   = DL_Items::sum( 'contrato', $id, 'equipamento' );
		$add   = DL_Items::sum( 'contrato', $id, 'adicional' );
		$total = max( 0, $sub + $add + (float) $c['valor_frete'] - (float) $c['desconto'] );
		DL_DB::update( 'contratos', $id, array( 'subtotal' => $sub, 'adicionais' => $add, 'total' => round( $total, 2 ) ) );
	}

	public static function client_warning( $row ) {
		$cli = DL_DB::get( 'clientes', (int) $row['cliente_id'] );
		if ( ! $cli ) {
			return;
		}
		$open = DL_Finance::client_overdue( $cli['id'] );
		if ( $cli['bloqueado'] ) {
			echo '<div class="dl-field dl-full"><div class="notice notice-error inline"><p><strong>Cliente bloqueado.</strong> ' . esc_html( $cli['motivo_bloqueio'] ) . '</p></div></div>';
		}
		if ( $open > 0 ) {
			echo '<div class="dl-field dl-full"><div class="notice notice-warning inline"><p>Cliente com ' . esc_html( dl_money( $open ) ) . ' em contas vencidas.</p></div></div>';
		}
		if ( (float) $cli['limite_credito'] > 0 ) {
			$exposure = DL_Finance::client_open( $cli['id'] );
			if ( $exposure > (float) $cli['limite_credito'] ) {
				echo '<div class="dl-field dl-full"><div class="notice notice-warning inline"><p>Em aberto (' . esc_html( dl_money( $exposure ) ) . ') acima do limite de crédito (' . esc_html( dl_money( $cli['limite_credito'] ) ) . ').</p></div></div>';
			}
		}
	}

	public static function can_delete( $can, $row ) {
		if ( ! in_array( $row['status'], array( 'solicitacao', 'orcamento', 'cancelado' ), true ) ) {
			return 'Contratos reservados, em locação ou encerrados não podem ser excluídos — cancele ou encerre.';
		}
		if ( (float) $row['valor_faturado'] > 0 ) {
			return 'Contrato com faturamento não pode ser excluído.';
		}
		return $can;
	}

	public static function list_row( $row ) {
		if ( self::is_late( $row ) ) {
			$row['status'] = 'atrasado';
		}
		return $row;
	}

	public static function row_actions( $actions, $row ) {
		$actions['print'] = '<a target="_blank" href="' . esc_url( DL_Documents::url( in_array( $row['status'], array( 'orcamento', 'solicitacao' ), true ) ? 'orcamento' : 'contrato', $row['id'] ) ) . '">Imprimir</a>';
		return $actions;
	}

	public static function filter_options( $options, $filter ) {
		if ( 'status' === $filter ) {
			$options['atrasado'] = 'Em atraso';
		}
		return $options;
	}

	public static function filter_where( $custom, $filter, $value ) {
		global $wpdb;
		if ( 'status' === $filter && 'atrasado' === $value ) {
			return $wpdb->prepare( "status = 'ativo' AND data_prev_devolucao < %s", dl_today() );
		}
		return $custom;
	}

	/* ------------------------------------------------------------- lateral */

	private static function action_form( $id, $op, $label, $class = 'button', $fields = '', $confirm = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="dl-inline-form"' . ( $confirm ? ' onsubmit="return confirm(\'' . esc_js( $confirm ) . '\');"' : '' ) . '>';
		wp_nonce_field( 'dl_contract_' . $id );
		echo '<input type="hidden" name="action" value="dl_contract"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
		echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput -- campos montados internamente.
		echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	}

	public static function sidebar( $c ) {
		$id     = (int) $c['id'];
		$status = $c['status'];
		$cli    = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		$days   = self::rental_days( $c['data_inicio'], $c['data_prev_devolucao'] );
		echo '<div class="dl-card dl-summary"><h3>Resumo</h3>';
		echo '<p>' . dl_badge( 'contrato', self::is_late( $c ) ? 'atrasado' : $status ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p>' . esc_html( dl_date( $c['data_inicio'] ) . ' → ' . dl_date( $c['data_prev_devolucao'] ) . ' (' . $days . ' dia' . ( $days > 1 ? 's' : '' ) . ')' ) . '</p>';
		if ( self::is_late( $c ) ) {
			echo '<p class="dl-alert">Atrasado há ' . (int) dl_days_between( $c['data_prev_devolucao'], dl_today() ) . ' dia(s).</p>';
		}
		echo '<p class="dl-big">' . esc_html( dl_money( $c['total'] ) ) . '</p>';
		echo '<p>Faturado: ' . esc_html( dl_money( $c['valor_faturado'] ) ) . ' · Saldo a faturar: ' . esc_html( dl_money( max( 0, $c['total'] - $c['valor_faturado'] ) ) ) . '</p>';
		echo '</div>';

		echo '<div class="dl-card dl-actions"><h3>Ações</h3>';
		if ( in_array( $status, array( 'solicitacao', 'orcamento' ), true ) ) {
			self::action_form( $id, 'reservar', 'Aprovar e reservar equipamentos', 'button button-primary' );
			echo '<a class="button" href="' . esc_url( dl_admin_url( 'dl-movimento', array( 'id' => $id, 'modo' => 'saida' ) ) ) . '">Entregar agora (iniciar locação)</a>';
		}
		if ( 'reservado' === $status ) {
			echo '<a class="button button-primary" href="' . esc_url( dl_admin_url( 'dl-movimento', array( 'id' => $id, 'modo' => 'saida' ) ) ) . '">Registrar entrega / retirada</a>';
			self::action_form( $id, 'voltar_orcamento', 'Liberar reserva (voltar a orçamento)' );
		}
		if ( 'ativo' === $status ) {
			echo '<a class="button button-primary" href="' . esc_url( dl_admin_url( 'dl-movimento', array( 'id' => $id, 'modo' => 'retorno' ) ) ) . '">Registrar devolução</a>';
			$fields = '<label>Nova data de devolução<br><input type="date" name="nova_data" required min="' . esc_attr( dl_add_days( $c['data_prev_devolucao'], 1 ) ) . '"></label><br><label><input type="checkbox" name="recalcular" value="1" checked> Recalcular valores dos itens</label><br>';
			self::action_form( $id, 'renovar', 'Renovar / prorrogar', 'button', $fields );
		}
		if ( in_array( $status, array( 'reservado', 'ativo', 'encerrado' ), true ) ) {
			$saldo  = max( 0, round( $c['total'] - $c['valor_faturado'], 2 ) );
			$fields = '<label>Valor<br><input type="number" step="0.01" min="0.01" name="valor" value="' . esc_attr( $saldo ) . '" required></label>'
				. '<label>Parcelas<br><input type="number" name="parcelas" min="1" max="36" value="1"></label>'
				. '<label>1º vencimento<br><input type="date" name="vencimento" value="' . esc_attr( dl_today() ) . '" required></label>'
				. '<label>Intervalo (dias)<br><input type="number" name="intervalo" min="1" value="30"></label>'
				. '<label>Forma<br><select name="forma">';
			foreach ( dl_payment_methods() as $k => $l ) {
				$fields .= '<option value="' . esc_attr( $k ) . '" ' . selected( $c['forma_pagamento'], $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			$fields .= '</select></label><br>';
			echo '<details><summary class="button">Faturar (gerar contas a receber)</summary>';
			self::action_form( $id, 'faturar', 'Gerar cobrança', 'button button-secondary', $fields );
			echo '</details>';
		}
		if ( in_array( $status, array( 'solicitacao', 'orcamento', 'reservado' ), true ) ) {
			self::action_form( $id, 'cancelar', 'Cancelar', 'button button-link-delete', '', 'Cancelar esta locação?' );
		}
		if ( in_array( $status, array( 'encerrado', 'cancelado' ), true ) ) {
			self::action_form( $id, 'reabrir', 'Reabrir como orçamento', 'button', '', 'Reabrir este contrato?' );
		}
		self::action_form( $id, 'duplicar', 'Duplicar como novo orçamento' );
		echo '</div>';

		echo '<div class="dl-card"><h3>Documentos</h3><ul class="dl-links">';
		$docs = array(
			'orcamento' => 'Orçamento',
			'contrato'  => 'Contrato de locação',
			'checklist' => 'Checklist de saída e retorno',
			'fatura'    => 'Fatura de locação',
		);
		foreach ( $docs as $t => $l ) {
			echo '<li><a target="_blank" href="' . esc_url( DL_Documents::url( $t, $id ) ) . '">' . esc_html( $l ) . '</a></li>';
		}
		echo '</ul>';
		if ( $cli ) {
			$type   = in_array( $status, array( 'orcamento', 'solicitacao' ), true ) ? 'orcamento' : 'contrato';
			$link   = DL_Documents::public_url( $type, $id );
			$msg    = sprintf( "Olá, %s! Segue o %s %s da %s, no valor de %s: %s", $cli['contato'] ? $cli['contato'] : $cli['nome'], 'orcamento' === $type ? 'orçamento' : 'contrato', $c['numero'], dl_opt( 'empresa_nome' ), dl_money( $c['total'] ), $link );
			$phone  = $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'];
			if ( $phone ) {
				echo '<a class="button" target="_blank" href="' . esc_url( dl_whatsapp_link( $phone, $msg ) ) . '">Enviar pelo WhatsApp</a> ';
			}
			if ( $cli['email'] ) {
				self::action_form( $id, 'email', 'Enviar por e-mail', 'button' );
			}
		}
		echo '</div>';

		DL_Finance::render_linked( 'contrato', $id );
		DL_Fiscal::render_linked( 'contrato', $id, $c );

		$adds = array_filter(
			DL_Items::get( 'contrato', $id ),
			function ( $it ) {
				return 'adicional' === $it['ref_tipo'];
			}
		);
		if ( $adds ) {
			echo '<div class="dl-card"><h3>Adicionais lançados</h3><ul class="dl-links">';
			foreach ( $adds as $a ) {
				echo '<li>' . esc_html( $a['descricao'] . ' — ' . dl_money( $a['total'] ) ) . ' ';
				if ( ! in_array( $status, array( 'encerrado', 'cancelado' ), true ) ) {
					self::action_form( $id, 'remover_adicional', '✕', 'button-link', '<input type="hidden" name="item" value="' . (int) $a['id'] . '">', 'Remover este adicional?' );
				}
				echo '</li>';
			}
			echo '</ul></div>';
		}
	}

	/* ------------------------------------------------------------- ações */

	public static function handle_action() {
		dl_require_cap( 'dl_operar' );
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'dl_contract_' . $id );
		$c = DL_DB::get( 'contratos', $id );
		if ( ! $c ) {
			wp_die( 'Contrato não encontrado.' );
		}
		$op   = sanitize_key( $_POST['op'] ?? '' );
		$back = dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $id ) );

		switch ( $op ) {
			case 'reservar':
				$msg = self::reserve( $c );
				if ( is_wp_error( $msg ) ) {
					dl_redirect( $back, $msg->get_error_message(), 'error' );
				}
				dl_redirect( $back, 'Equipamentos reservados.' );
				break;

			case 'voltar_orcamento':
				if ( 'reservado' === $c['status'] ) {
					DL_DB::update( 'contratos', $id, array( 'status' => 'orcamento' ) );
					dl_log( 'contratos', $id, 'Reserva liberada' );
				}
				dl_redirect( $back, 'Reserva liberada.' );
				break;

			case 'cancelar':
				if ( 'ativo' === $c['status'] ) {
					dl_redirect( $back, 'Contrato em locação: registre a devolução antes.', 'error' );
				}
				DL_DB::update( 'contratos', $id, array( 'status' => 'cancelado' ) );
				dl_log( 'contratos', $id, 'Cancelado' );
				dl_redirect( $back, 'Locação cancelada. Contas a receber já geradas não foram alteradas — revise no financeiro.' );
				break;

			case 'reabrir':
				DL_DB::update( 'contratos', $id, array( 'status' => 'orcamento', 'data_encerramento' => null ) );
				dl_log( 'contratos', $id, 'Reaberto como orçamento' );
				dl_redirect( $back, 'Contrato reaberto como orçamento.' );
				break;

			case 'renovar':
				$new = sanitize_text_field( wp_unslash( $_POST['nova_data'] ?? '' ) );
				$r   = self::renew( $c, $new, ! empty( $_POST['recalcular'] ) );
				if ( is_wp_error( $r ) ) {
					dl_redirect( $back, $r->get_error_message(), 'error' );
				}
				dl_redirect( $back, 'Locação prorrogada até ' . dl_date( $new ) . '.' );
				break;

			case 'faturar':
				$valor = round( dl_decimal( wp_unslash( $_POST['valor'] ?? 0 ) ), 2 );
				if ( $valor <= 0 ) {
					dl_redirect( $back, 'Informe um valor.', 'error' );
				}
				$ids = DL_Finance::create_installments(
					array(
						'tipo'            => 'receber',
						'descricao'       => 'Locação ' . $c['numero'],
						'categoria'       => 'locacao',
						'cliente_id'      => $c['cliente_id'],
						'origem'          => 'contrato',
						'origem_id'       => $id,
						'forma_pagamento' => sanitize_key( $_POST['forma'] ?? '' ),
					),
					$valor,
					absint( $_POST['parcelas'] ?? 1 ),
					sanitize_text_field( wp_unslash( $_POST['vencimento'] ?? dl_today() ) ),
					absint( $_POST['intervalo'] ?? 30 )
				);
				DL_DB::update( 'contratos', $id, array( 'valor_faturado' => round( (float) $c['valor_faturado'] + $valor, 2 ) ) );
				dl_log( 'contratos', $id, 'Faturado', dl_money( $valor ) . ' em ' . count( $ids ) . ' parcela(s)' );
				dl_redirect( $back, 'Cobrança gerada: ' . count( $ids ) . ' parcela(s) em contas a receber.' );
				break;

			case 'duplicar':
				$new_id = self::duplicate( $c );
				dl_redirect( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $new_id ) ), 'Novo orçamento criado a partir de ' . $c['numero'] . '.' );
				break;

			case 'email':
				$ok = self::send_email( $c );
				dl_redirect( $back, $ok ? 'E-mail enviado ao cliente.' : 'Falha ao enviar o e-mail (verifique o SMTP do site).', $ok ? 'success' : 'error' );
				break;

			case 'remover_adicional':
				global $wpdb;
				$wpdb->delete( dl_table( 'itens' ), array( 'id' => absint( $_POST['item'] ?? 0 ), 'doc_tipo' => 'contrato', 'doc_id' => $id, 'ref_tipo' => 'adicional' ) );
				self::recalc( $id );
				dl_log( 'contratos', $id, 'Adicional removido' );
				dl_redirect( $back, 'Adicional removido.' );
				break;
		}
		dl_redirect( $back );
	}

	public static function reserve( $c ) {
		$items = DL_Items::get( 'contrato', $c['id'] );
		if ( ! array_filter( $items, function ( $i ) { return 'equipamento' === $i['ref_tipo']; } ) ) {
			return new WP_Error( 'vazio', 'Adicione ao menos um equipamento.' );
		}
		$cli = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		if ( $cli && $cli['bloqueado'] ) {
			return new WP_Error( 'bloq', 'Cliente bloqueado: ' . $cli['motivo_bloqueio'] );
		}
		$problems = DL_Availability::check_items( $items, $c['data_inicio'], $c['data_prev_devolucao'], $c['id'] );
		if ( $problems ) {
			return new WP_Error( 'disp', 'Sem disponibilidade: ' . implode( ' · ', $problems ) );
		}
		DL_DB::update( 'contratos', $c['id'], array( 'status' => 'reservado' ) );
		dl_log( 'contratos', $c['id'], 'Reservado' );
		return true;
	}

	/** Recalcula um item de equipamento para um número de dias. */
	public static function reprice_item( $it, $days ) {
		$e = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
		if ( ! $e ) {
			return $it;
		}
		$types = dl_period_types();
		if ( 'pacote' === $it['periodo_tipo'] ) {
			$best              = DL_Pricing::best_price( self::rates( $e ), $days );
			$it['valor_unit']  = $best['total'];
			$it['periodos']    = 1;
			$it['descricao']   = $e['nome'] . ' — ' . $best['descricao'];
		} elseif ( isset( $types[ $it['periodo_tipo'] ] ) && $types[ $it['periodo_tipo'] ]['dias'] > 0 ) {
			$it['periodos'] = ceil( $days / $types[ $it['periodo_tipo'] ]['dias'] );
		}
		$it['total'] = DL_Pricing::item_total( $it['qtd'], $it['periodos'], $it['valor_unit'], $it['desconto'] );
		return $it;
	}

	public static function rates( $e ) {
		return array(
			'diaria'    => $e['valor_diaria'],
			'semanal'   => $e['valor_semanal'],
			'quinzenal' => $e['valor_quinzenal'],
			'mensal'    => $e['valor_mensal'],
		);
	}

	public static function renew( $c, $new_date, $recalc ) {
		if ( 'ativo' !== $c['status'] && 'reservado' !== $c['status'] ) {
			return new WP_Error( 'status', 'Só é possível prorrogar locação reservada ou em andamento.' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $new_date ) || $new_date <= $c['data_prev_devolucao'] ) {
			return new WP_Error( 'data', 'A nova data precisa ser posterior à devolução prevista atual.' );
		}
		$items    = DL_Items::get( 'contrato', $c['id'] );
		$problems = DL_Availability::check_items( $items, dl_add_days( $c['data_prev_devolucao'], 1 ), $new_date, $c['id'] );
		if ( $problems ) {
			return new WP_Error( 'disp', 'Não dá para prorrogar — conflito com outra reserva: ' . implode( ' · ', $problems ) );
		}
		global $wpdb;
		if ( $recalc ) {
			$days = self::rental_days( $c['data_inicio'], $new_date );
			foreach ( $items as $it ) {
				if ( 'equipamento' !== $it['ref_tipo'] || (float) $it['qtd_devolvida'] >= (float) $it['qtd'] ) {
					continue;
				}
				$n = self::reprice_item( $it, $days );
				$wpdb->update( dl_table( 'itens' ), array( 'periodos' => $n['periodos'], 'valor_unit' => $n['valor_unit'], 'total' => $n['total'], 'descricao' => $n['descricao'] ), array( 'id' => $it['id'] ) );
			}
		}
		DL_DB::update( 'contratos', $c['id'], array( 'data_prev_devolucao' => $new_date ) );
		self::recalc( $c['id'] );
		dl_log( 'contratos', $c['id'], 'Prorrogado', dl_date( $c['data_prev_devolucao'] ) . ' → ' . dl_date( $new_date ) );
		return true;
	}

	public static function duplicate( $c ) {
		$data = $c;
		unset( $data['id'], $data['numero'], $data['criado_em'], $data['atualizado_em'], $data['data_encerramento'] );
		$len                         = max( 1, dl_days_between( $c['data_inicio'], $c['data_prev_devolucao'] ) );
		$data['status']              = 'orcamento';
		$data['valor_faturado']      = 0;
		$data['adicionais']          = 0;
		$data['caucao_status']       = 'nao_cobrado';
		$data['data_inicio']         = dl_today();
		$data['data_prev_devolucao'] = dl_add_days( dl_today(), $len );
		$data['validade_orcamento']  = dl_add_days( dl_today(), (int) dl_opt( 'validade_orcamento', 7 ) );
		$data['origem']              = 'admin';
		$data['criado_por']          = get_current_user_id();
		$new_id                      = DL_DB::insert( 'contratos', $data );
		DL_DB::update( 'contratos', $new_id, array( 'numero' => dl_doc_number( dl_opt( 'prefixo_contrato', 'LOC' ), $new_id ) ) );
		global $wpdb;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			unset( $it['id'] );
			$it['doc_id']        = $new_id;
			$it['qtd_devolvida'] = 0;
			foreach ( array( 'data_saida', 'data_retorno', 'horimetro_saida', 'horimetro_retorno', 'checklist_saida', 'checklist_retorno', 'avarias' ) as $k ) {
				$it[ $k ] = null;
			}
			$it['valor_avaria'] = 0;
			$wpdb->insert( dl_table( 'itens' ), $it );
		}
		self::recalc( $new_id );
		dl_log( 'contratos', $new_id, 'Criado por duplicação', $c['numero'] );
		return $new_id;
	}

	public static function send_email( $c ) {
		$cli = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		if ( ! $cli || ! is_email( $cli['email'] ) ) {
			return false;
		}
		$type    = in_array( $c['status'], array( 'orcamento', 'solicitacao' ), true ) ? 'orcamento' : 'contrato';
		$subject = sprintf( '%s %s — %s', 'orcamento' === $type ? 'Orçamento' : 'Contrato', $c['numero'], dl_opt( 'empresa_nome' ) );
		$body    = sprintf(
			"Olá, %s!\n\nSegue o %s %s, no valor de %s, para o período de %s a %s.\n\nVisualize e imprima: %s\n\nQualquer dúvida, responda este e-mail ou chame no WhatsApp %s.\n\n%s",
			$cli['contato'] ? $cli['contato'] : $cli['nome'],
			'orcamento' === $type ? 'orçamento' : 'contrato',
			$c['numero'],
			dl_money( $c['total'] ),
			dl_date( $c['data_inicio'] ),
			dl_date( $c['data_prev_devolucao'] ),
			DL_Documents::public_url( $type, $c['id'] ),
			dl_opt( 'empresa_whatsapp' ),
			dl_opt( 'empresa_nome' )
		);
		$ok = wp_mail( $cli['email'], $subject, $body );
		if ( $ok ) {
			dl_log( 'contratos', $c['id'], 'Enviado por e-mail', $cli['email'] );
		}
		return $ok;
	}

	/* ------------------------------------------- entrega e devolução (telas) */

	public static function render_move() {
		dl_require_cap( 'dl_operar' );
		$id   = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$mode = 'retorno' === ( $_GET['modo'] ?? '' ) ? 'retorno' : 'saida'; // phpcs:ignore WordPress.Security.NonceVerification
		$c    = DL_DB::get( 'contratos', $id );
		if ( ! $c ) {
			wp_die( 'Contrato não encontrado.' );
		}
		$items = array_filter(
			DL_Items::get( 'contrato', $id ),
			function ( $it ) use ( $mode ) {
				return 'equipamento' === $it['ref_tipo'] && ( 'saida' === $mode || (float) $it['qtd_devolvida'] < (float) $it['qtd'] );
			}
		);
		$late = 'retorno' === $mode ? max( 0, dl_days_between( $c['data_prev_devolucao'], dl_today() ) ) : 0;
		?>
		<div class="wrap dl-wrap">
			<h1><?php echo esc_html( ( 'saida' === $mode ? 'Entrega / retirada' : 'Devolução' ) . ' — ' . $c['numero'] ); ?></h1>
			<p><a href="<?php echo esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $id ) ) ); ?>">← voltar ao contrato</a> · Cliente: <strong><?php echo esc_html( DL_DB::label( 'clientes', $c['cliente_id'] ) ); ?></strong> · Período: <?php echo esc_html( dl_date( $c['data_inicio'] ) . ' a ' . dl_date( $c['data_prev_devolucao'] ) ); ?></p>
			<?php dl_render_notice(); ?>
			<?php if ( ! $items ) : ?>
				<p>Nenhum item pendente.</p>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dl-card">
				<?php wp_nonce_field( 'dl_move_' . $id ); ?>
				<input type="hidden" name="action" value="dl_contract_move">
				<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
				<input type="hidden" name="modo" value="<?php echo esc_attr( $mode ); ?>">
				<p><label><?php echo 'saida' === $mode ? 'Data da entrega' : 'Data da devolução'; ?> <input type="date" name="data" value="<?php echo esc_attr( 'saida' === $mode ? max( dl_today(), (string) $c['data_inicio'] ) : dl_today() ); ?>" required></label></p>
				<?php if ( 'saida' === $mode && $c['data_inicio'] !== dl_today() ) : ?>
					<p class="description">A data de início do contrato será ajustada para a data da entrega, mantendo a duração.</p>
				<?php endif; ?>
				<table class="widefat striped">
					<thead><tr><th>Equipamento</th><th>Qtd</th><th>Horímetro</th><th>Checklist / estado</th><?php if ( 'retorno' === $mode ) : ?><th>Avarias</th><th>Valor da avaria</th><th>Abrir OS</th><?php endif; ?></tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) : ?>
						<?php $pending = (float) $it['qtd'] - (float) $it['qtd_devolvida']; ?>
						<tr>
							<td><strong><?php echo esc_html( $it['descricao'] ); ?></strong></td>
							<td>
								<?php if ( 'retorno' === $mode ) : ?>
									<input type="number" step="0.001" min="0" max="<?php echo esc_attr( $pending ); ?>" name="mv[<?php echo (int) $it['id']; ?>][qtd]" value="<?php echo esc_attr( $pending ); ?>" style="width:80px"> de <?php echo esc_html( dl_num( $pending, 0 ) ); ?>
								<?php else : ?>
									<?php echo esc_html( dl_num( $it['qtd'], 0 ) ); ?>
								<?php endif; ?>
							</td>
							<td><input type="number" step="0.1" name="mv[<?php echo (int) $it['id']; ?>][horimetro]" value="<?php echo esc_attr( 'saida' === $mode ? DL_DB::label( 'equipamentos', $it['ref_id'], 'horimetro' ) : '' ); ?>" style="width:100px"></td>
							<td><textarea name="mv[<?php echo (int) $it['id']; ?>][checklist]" rows="3" cols="30" placeholder="Ex.: limpo, completo, acessórios ok, combustível 1/2"><?php echo esc_textarea( 'saida' === $mode ? (string) $it['checklist_saida'] : '' ); ?></textarea></td>
							<?php if ( 'retorno' === $mode ) : ?>
								<td><textarea name="mv[<?php echo (int) $it['id']; ?>][avarias]" rows="3" cols="25"></textarea></td>
								<td><input type="number" step="0.01" min="0" name="mv[<?php echo (int) $it['id']; ?>][valor_avaria]" value="0" style="width:100px"></td>
								<td><label><input type="checkbox" name="mv[<?php echo (int) $it['id']; ?>][os]" value="1"> revisão</label></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( 'retorno' === $mode ) : ?>
					<p><label><input type="checkbox" name="cobrar_atraso" value="1" <?php checked( $late > 0 ); ?>> Lançar diárias excedentes se a devolução for após <?php echo esc_html( dl_date( $c['data_prev_devolucao'] ) ); ?><?php echo $late > 0 ? esc_html( ' (hoje: ' . $late . ' dia(s) de atraso)' ) : ''; ?></label></p>
					<?php if ( (float) $c['caucao'] > 0 ) : ?>
						<p><label>Caução de <?php echo esc_html( dl_money( $c['caucao'] ) ); ?>: <select name="caucao_status"><option value="">manter</option><option value="devolvido">devolvida ao cliente</option><option value="retido">retida</option></select></label></p>
					<?php endif; ?>
				<?php endif; ?>
				<p><button class="button button-primary button-large"><?php echo 'saida' === $mode ? 'Confirmar entrega e iniciar locação' : 'Confirmar devolução'; ?></button></p>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_move() {
		dl_require_cap( 'dl_operar' );
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'dl_move_' . $id );
		$c = DL_DB::get( 'contratos', $id );
		if ( ! $c ) {
			wp_die( 'Contrato não encontrado.' );
		}
		$mode = 'retorno' === ( $_POST['modo'] ?? '' ) ? 'retorno' : 'saida';
		$date = sanitize_text_field( wp_unslash( $_POST['data'] ?? '' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = dl_today();
		}
		$mv   = isset( $_POST['mv'] ) ? wp_unslash( (array) $_POST['mv'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$back = dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $id ) );
		$r    = 'saida' === $mode ? self::deliver( $c, $date, $mv ) : self::receive( $c, $date, $mv, ! empty( $_POST['cobrar_atraso'] ), sanitize_key( $_POST['caucao_status'] ?? '' ) );
		if ( is_wp_error( $r ) ) {
			dl_redirect( dl_admin_url( 'dl-movimento', array( 'id' => $id, 'modo' => $mode ) ), $r->get_error_message(), 'error' );
		}
		dl_redirect( $back, $r );
	}

	/** Entrega: confere disponibilidade, grava checklist de saída e inicia a locação. */
	public static function deliver( $c, $date, $mv ) {
		if ( ! in_array( $c['status'], array( 'solicitacao', 'orcamento', 'reservado' ), true ) ) {
			return new WP_Error( 'status', 'Esta locação já foi iniciada.' );
		}
		$cli = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		if ( $cli && $cli['bloqueado'] ) {
			return new WP_Error( 'bloq', 'Cliente bloqueado: ' . $cli['motivo_bloqueio'] );
		}
		$len   = max( 0, dl_days_between( $c['data_inicio'], $c['data_prev_devolucao'] ) );
		$start = $date;
		$end   = dl_add_days( $date, $len );
		$items = DL_Items::get( 'contrato', $c['id'] );
		if ( ! array_filter( $items, function ( $i ) { return 'equipamento' === $i['ref_tipo']; } ) ) {
			return new WP_Error( 'vazio', 'Adicione ao menos um equipamento antes de entregar.' );
		}
		$problems = DL_Availability::check_items( $items, $start, $end, $c['id'] );
		if ( $problems ) {
			return new WP_Error( 'disp', 'Sem disponibilidade: ' . implode( ' · ', $problems ) );
		}
		global $wpdb;
		foreach ( $items as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			$m = $mv[ $it['id'] ] ?? array();
			$wpdb->update(
				dl_table( 'itens' ),
				array(
					'data_saida'      => $date,
					'horimetro_saida' => '' !== ( $m['horimetro'] ?? '' ) ? dl_decimal( $m['horimetro'] ) : null,
					'checklist_saida' => sanitize_textarea_field( $m['checklist'] ?? '' ),
				),
				array( 'id' => $it['id'] )
			);
		}
		DL_DB::update( 'contratos', $c['id'], array( 'status' => 'ativo', 'data_inicio' => $start, 'data_prev_devolucao' => $end ) );
		dl_log( 'contratos', $c['id'], 'Entregue — locação iniciada', dl_date( $date ) );
		do_action( 'dl_contract_started', $c['id'] );
		return 'Entrega registrada. Locação em andamento até ' . dl_date( $end ) . '.';
	}

	/** Devolução total ou parcial, com cobrança de atraso e avarias. */
	public static function receive( $c, $date, $mv, $charge_late, $deposit_status ) {
		if ( 'ativo' !== $c['status'] ) {
			return new WP_Error( 'status', 'A locação não está em andamento.' );
		}
		global $wpdb;
		$late_days = max( 0, dl_days_between( $c['data_prev_devolucao'], $date ) );
		$pct       = (float) dl_opt( 'multa_atraso_pct', 0 );
		$returned  = 0;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] || empty( $mv[ $it['id'] ] ) ) {
				continue;
			}
			$m       = $mv[ $it['id'] ];
			$pending = (float) $it['qtd'] - (float) $it['qtd_devolvida'];
			$qty     = min( $pending, max( 0, dl_decimal( $m['qtd'] ?? $pending ) ) );
			if ( $qty <= 0 ) {
				continue;
			}
			$returned++;
			$check   = sanitize_textarea_field( $m['checklist'] ?? '' );
			$damage  = sanitize_textarea_field( $m['avarias'] ?? '' );
			$dvalue  = max( 0, round( dl_decimal( $m['valor_avaria'] ?? 0 ), 2 ) );
			$hour    = '' !== ( $m['horimetro'] ?? '' ) ? dl_decimal( $m['horimetro'] ) : null;
			$wpdb->update(
				dl_table( 'itens' ),
				array(
					'qtd_devolvida'     => (float) $it['qtd_devolvida'] + $qty,
					'data_retorno'      => $date,
					'horimetro_retorno' => $hour,
					'checklist_retorno' => trim( (string) $it['checklist_retorno'] . "\n" . dl_date( $date ) . ' (' . dl_num( $qty, 0 ) . '): ' . $check ),
					'avarias'           => trim( (string) $it['avarias'] . ( $damage ? "\n" . $damage : '' ) ),
					'valor_avaria'      => (float) $it['valor_avaria'] + $dvalue,
				),
				array( 'id' => $it['id'] )
			);
			$equip = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
			if ( $equip && null !== $hour && $hour > (float) $equip['horimetro'] ) {
				DL_DB::update( 'equipamentos', $equip['id'], array( 'horimetro' => $hour ) );
			}
			if ( $charge_late && $late_days > 0 && $equip ) {
				$fee = DL_Pricing::late_fee( $equip['valor_diaria'], $late_days, $qty, $pct );
				if ( $fee > 0 ) {
					self::add_extra( $c['id'], sprintf( 'Diárias excedentes — %s (%d dia(s) × %s)', $equip['nome'], $late_days, dl_num( $qty, 0 ) ), $fee );
				}
			}
			if ( $dvalue > 0 ) {
				self::add_extra( $c['id'], 'Avaria — ' . $it['descricao'] . ( $damage ? ': ' . wp_trim_words( $damage, 12 ) : '' ), $dvalue );
			}
			if ( ! empty( $m['os'] ) && $equip ) {
				DL_Service_Orders::open_from_return( $c, $equip, $damage ? $damage : $check );
			}
		}
		if ( ! $returned ) {
			return new WP_Error( 'nada', 'Nenhum item marcado para devolução.' );
		}
		$update = array();
		if ( in_array( $deposit_status, array( 'devolvido', 'retido' ), true ) ) {
			$update['caucao_status'] = $deposit_status;
		}
		$pending = (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(qtd - qtd_devolvida),0) FROM ' . dl_table( 'itens' ) . " WHERE doc_tipo = 'contrato' AND doc_id = %d AND ref_tipo = 'equipamento'", $c['id'] ) );
		if ( $pending <= 0 ) {
			$update['status']            = 'encerrado';
			$update['data_encerramento'] = $date;
		}
		if ( $update ) {
			DL_DB::update( 'contratos', $c['id'], $update );
		}
		self::recalc( $c['id'] );
		dl_log( 'contratos', $c['id'], $pending <= 0 ? 'Devolução total — encerrado' : 'Devolução parcial', dl_date( $date ) );
		do_action( 'dl_contract_returned', $c['id'], $pending <= 0 );
		$c2 = DL_DB::get( 'contratos', $c['id'] );
		$saldo = round( $c2['total'] - $c2['valor_faturado'], 2 );
		return ( $pending <= 0 ? 'Devolução registrada e contrato encerrado.' : 'Devolução parcial registrada.' ) . ( $saldo > 0 ? ' Saldo a faturar: ' . dl_money( $saldo ) . '.' : '' );
	}

	public static function add_extra( $contract_id, $desc, $value ) {
		global $wpdb;
		$wpdb->insert(
			dl_table( 'itens' ),
			array(
				'doc_tipo'   => 'contrato',
				'doc_id'     => $contract_id,
				'ref_tipo'   => 'adicional',
				'ref_id'     => 0,
				'descricao'  => mb_substr( $desc, 0, 250 ),
				'qtd'        => 1,
				'periodos'   => 1,
				'valor_unit' => $value,
				'total'      => $value,
				'ordem'      => 999,
			)
		);
	}

	/** AJAX: melhor tarifa para um equipamento num número de dias. */
	public static function ajax_best_price() {
		check_ajax_referer( 'dl_admin', 'nonce' );
		if ( ! current_user_can( 'dl_operar' ) ) {
			wp_send_json_error( 'sem permissão', 403 );
		}
		$e = DL_DB::get( 'equipamentos', absint( $_POST['equip'] ?? 0 ) );
		if ( ! $e ) {
			wp_send_json_error( 'equipamento não encontrado' );
		}
		$days = self::rental_days( sanitize_text_field( wp_unslash( $_POST['inicio'] ?? '' ) ), sanitize_text_field( wp_unslash( $_POST['fim'] ?? '' ) ) );
		$best = DL_Pricing::best_price( self::rates( $e ), $days );
		wp_send_json_success( $best + array( 'dias' => $days, 'nome' => $e['nome'] ) );
	}
}
