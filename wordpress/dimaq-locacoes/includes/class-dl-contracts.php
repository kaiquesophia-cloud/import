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
		add_filter( 'dl_can_delete_contratos', array( __CLASS__, 'can_delete' ), 10, 2 );
		add_filter( 'dl_list_row_contratos', array( __CLASS__, 'list_row' ) );
		add_filter( 'dl_filter_where_contratos', array( __CLASS__, 'filter_where' ), 10, 3 );
	}

	/** Dias cobrados entre início e devolução (mínimo 1). */
	public static function rental_days( $start, $end ) {
		$days = dl_days_between( $start, $end ) + ( self::inclusive() ? 1 : 0 );
		return max( 1, $days, (int) dl_opt( 'locacao_minima_dias', 1 ) );
	}

	/** A Dimaq conta o dia da retirada e o da devolução (08/10 a 06/11 = 30 dias). */
	public static function inclusive() {
		return (bool) (int) dl_opt( 'contagem_inclusiva', 1 );
	}

	/** Data de devolução para uma duração em dias, conforme a forma de contar. */
	public static function end_for( $start, $days ) {
		return dl_add_days( $start, max( 1, (int) $days ) - ( self::inclusive() ? 1 : 0 ) );
	}

	/** Gera o número do contrato: sequencial ("6.576 / 1") ou por ano ("LOC2026-00001"). */
	public static function new_number( $id, $date = '' ) {
		if ( 'ano' === dl_opt( 'numeracao_contrato', 'sequencial' ) ) {
			return dl_doc_number( dl_opt( 'prefixo_contrato', 'LOC' ), $id, $date );
		}
		$next = max( (int) get_option( 'dl_contract_last', 0 ) + 1, (int) dl_opt( 'proximo_contrato', 1 ) );
		update_option( 'dl_contract_last', $next, false );
		return number_format( $next, 0, ',', '.' ) . ' / 1';
	}

	public static function is_late( $c ) {
		return 'ativo' === $c['status'] && $c['data_prev_devolucao'] && $c['data_prev_devolucao'] < dl_today();
	}

	/* ------------------------------------------------------- ganchos do CRUD */

	public static function new_row( $row ) {
		$row['data_inicio']         = $row['data_inicio'] ? $row['data_inicio'] : dl_today();
		$row['data_prev_devolucao'] = $row['data_prev_devolucao'] ? $row['data_prev_devolucao'] : self::end_for( $row['data_inicio'], max( 1, (int) dl_opt( 'locacao_minima_dias', 1 ) ) );
		$row['validade_orcamento']  = dl_add_days( dl_today(), (int) dl_opt( 'validade_orcamento', 7 ) );
		$row['status']              = 'orcamento';
		// Forma de cobrança: a da configuração, salvo se a tela pediu outra.
		if ( ! isset( $_GET['cobranca'] ) || ! in_array( $row['cobranca'], array( 'periodo', 'medicao' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$row['cobranca'] = 'medicao' === dl_opt( 'cobranca_padrao', 'periodo' ) ? 'medicao' : 'periodo';
		}
		$row['medicao_ciclo'] = $row['medicao_ciclo'] ? $row['medicao_ciclo'] : 30;
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
			DL_DB::update( 'contratos', $id, array( 'numero' => self::new_number( $id, $c['criado_em'] ) ) );
		}
		self::recalc( $id );
		if ( $old && in_array( $c['status'], DL_Availability::BUSY, true ) && ( $old['data_inicio'] !== $c['data_inicio'] || $old['data_prev_devolucao'] !== $c['data_prev_devolucao'] ) ) {
			$problems = DL_Availability::check_items( DL_Items::get( 'contrato', $id ), $c['data_inicio'], $c['data_prev_devolucao'], $id );
			if ( $problems ) {
				dl_notice( 'Datas salvas, mas há conflito de disponibilidade: ' . implode( ' · ', $problems ) );
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

	/** Alertas sobre o cliente (bloqueio, atraso, limite de crédito). */
	public static function client_alerts( $client_id ) {
		$cli = DL_DB::get( 'clientes', (int) $client_id );
		$out = array();
		if ( ! $cli ) {
			return $out;
		}
		if ( $cli['bloqueado'] ) {
			$out[] = array( 'type' => 'error', 'message' => 'Cliente bloqueado. ' . $cli['motivo_bloqueio'] );
		}
		$overdue = DL_Finance::client_overdue( $cli['id'] );
		if ( $overdue > 0 ) {
			$out[] = array( 'type' => 'warning', 'message' => 'Cliente com ' . dl_money( $overdue ) . ' em contas vencidas.' );
		}
		if ( (float) $cli['limite_credito'] > 0 ) {
			$exposure = DL_Finance::client_open( $cli['id'] );
			if ( $exposure > (float) $cli['limite_credito'] ) {
				$out[] = array( 'type' => 'warning', 'message' => 'Em aberto (' . dl_money( $exposure ) . ') acima do limite de crédito (' . dl_money( $cli['limite_credito'] ) . ').' );
			}
		}
		return $out;
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

	public static function filter_where( $custom, $filter, $value ) {
		global $wpdb;
		if ( 'status' === $filter && 'atrasado' === $value ) {
			return $wpdb->prepare( "status = 'ativo' AND data_prev_devolucao < %s", dl_today() );
		}
		return $custom;
	}

	/* ------------------------------------------------------------- ações */

	/** Ações que a tela pode oferecer conforme a situação do contrato. */
	public static function available_actions( $c ) {
		$st = $c['status'];
		$a  = array();
		if ( in_array( $st, array( 'solicitacao', 'orcamento' ), true ) ) {
			$a[] = 'reservar';
			$a[] = 'entregar';
		}
		if ( 'reservado' === $st ) {
			$a[] = 'entregar';
			$a[] = 'voltar_orcamento';
		}
		if ( 'ativo' === $st ) {
			$a[] = 'devolver';
			$a[] = 'renovar';
			$a[] = 'agendar_coleta';
		}
		if ( DL_Measurement::is_measured( $c ) ) {
			if ( in_array( $st, array( 'ativo', 'encerrado' ), true ) ) {
				$a[] = 'medir';
			}
		} elseif ( in_array( $st, array( 'reservado', 'ativo', 'encerrado' ), true ) ) {
			$a[] = 'faturar';
		}
		if ( in_array( $st, array( 'solicitacao', 'orcamento', 'reservado' ), true ) ) {
			$a[] = 'cancelar';
		}
		if ( in_array( $st, array( 'encerrado', 'cancelado' ), true ) ) {
			$a[] = 'reabrir';
		}
		$a[] = 'duplicar';
		return $a;
	}

	/**
	 * Executa uma ação sobre o contrato.
	 *
	 * @return array|WP_Error ['message'=>string, 'id'=>int (contrato resultante)]
	 */
	public static function perform( $c, $op, array $p ) {
		$id = (int) $c['id'];
		switch ( $op ) {
			case 'reservar':
				$r = self::reserve( $c );
				return is_wp_error( $r ) ? $r : array( 'message' => 'Equipamentos reservados.' );

			case 'voltar_orcamento':
				if ( 'reservado' !== $c['status'] ) {
					return new WP_Error( 'status', 'Só reservas podem ser liberadas.' );
				}
				DL_DB::update( 'contratos', $id, array( 'status' => 'orcamento' ) );
				dl_log( 'contratos', $id, 'Reserva liberada' );
				return array( 'message' => 'Reserva liberada.' );

			case 'cancelar':
				if ( ! in_array( $c['status'], array( 'solicitacao', 'orcamento', 'reservado' ), true ) ) {
					return new WP_Error( 'status', 'Contrato em locação: registre a devolução antes.' );
				}
				DL_DB::update( 'contratos', $id, array( 'status' => 'cancelado' ) );
				dl_log( 'contratos', $id, 'Cancelado' );
				return array( 'message' => 'Locação cancelada. Cobranças já geradas não foram alteradas — revise no financeiro.' );

			case 'reabrir':
				if ( ! in_array( $c['status'], array( 'encerrado', 'cancelado' ), true ) ) {
					return new WP_Error( 'status', 'Só contratos encerrados ou cancelados podem ser reabertos.' );
				}
				DL_DB::update( 'contratos', $id, array( 'status' => 'orcamento', 'data_encerramento' => null ) );
				dl_log( 'contratos', $id, 'Reaberto como orçamento' );
				return array( 'message' => 'Contrato reaberto como orçamento.' );

			case 'renovar':
				$new = sanitize_text_field( $p['nova_data'] ?? '' );
				$r   = self::renew( $c, $new, ! empty( $p['recalcular'] ) );
				return is_wp_error( $r ) ? $r : array( 'message' => 'Locação prorrogada até ' . dl_date( $new ) . '.' );

			case 'faturar':
				if ( ! in_array( $c['status'], array( 'reservado', 'ativo', 'encerrado' ), true ) ) {
					return new WP_Error( 'status', 'Fature depois de reservar ou entregar.' );
				}
				$valor = round( dl_decimal( $p['valor'] ?? 0 ), 2 );
				if ( $valor <= 0 ) {
					return new WP_Error( 'valor', 'Informe um valor.' );
				}
				$due = sanitize_text_field( $p['vencimento'] ?? '' );
				$ids = DL_Finance::create_installments(
					array(
						'tipo'            => 'receber',
						'descricao'       => 'Locação ' . $c['numero'],
						'categoria'       => 'locacao',
						'cliente_id'      => $c['cliente_id'],
						'origem'          => 'contrato',
						'origem_id'       => $id,
						'forma_pagamento' => sanitize_key( $p['forma'] ?? '' ),
					),
					$valor,
					absint( $p['parcelas'] ?? 1 ),
					preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due ) ? $due : dl_today(),
					absint( $p['intervalo'] ?? 30 )
				);
				DL_DB::update( 'contratos', $id, array( 'valor_faturado' => round( (float) $c['valor_faturado'] + $valor, 2 ) ) );
				dl_log( 'contratos', $id, 'Faturado', dl_money( $valor ) . ' em ' . count( $ids ) . ' parcela(s)' );
				return array( 'message' => 'Cobrança gerada: ' . count( $ids ) . ' parcela(s) em contas a receber.' );

			case 'duplicar':
				$new_id = self::duplicate( $c );
				return array( 'message' => 'Novo orçamento criado a partir de ' . $c['numero'] . '.', 'id' => $new_id );

			case 'agendar_coleta':
				if ( 'ativo' !== $c['status'] ) {
					return new WP_Error( 'status', 'Só dá para agendar coleta de locação em andamento.' );
				}
				$date   = sanitize_text_field( $p['data'] ?? '' );
				$driver = sanitize_text_field( $p['motorista'] ?? $c['motorista'] );
				if ( ! empty( $p['remover'] ) ) {
					DL_DB::update( 'contratos', $id, array( 'coleta_em' => null ) );
					dl_log( 'contratos', $id, 'Coleta desmarcada' );
					return array( 'message' => 'Coleta desmarcada.' );
				}
				if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					return new WP_Error( 'data', 'Informe a data da coleta.' );
				}
				DL_DB::update( 'contratos', $id, array( 'coleta_em' => $date, 'motorista' => $driver ) );
				dl_log( 'contratos', $id, 'Coleta agendada', dl_date( $date ) . ( $driver ? ' — ' . $driver : '' ) );
				return array( 'message' => 'Coleta agendada para ' . dl_date( $date ) . '. Ela aparece na rota do dia.' );

			case 'email':
				return self::send_email( $c ) ? array( 'message' => 'E-mail enviado ao cliente.' ) : new WP_Error( 'email', 'Falha ao enviar o e-mail (verifique o SMTP do site e o e-mail do cliente).' );

			case 'entregar':
				$date = sanitize_text_field( $p['data'] ?? '' );
				$r    = self::deliver( $c, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : dl_today(), (array) ( $p['itens'] ?? array() ) );
				return is_wp_error( $r ) ? $r : array( 'message' => $r );

			case 'devolver':
				$date = sanitize_text_field( $p['data'] ?? '' );
				$r    = self::receive( $c, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : dl_today(), (array) ( $p['itens'] ?? array() ), ! empty( $p['cobrar_atraso'] ), sanitize_key( $p['caucao_status'] ?? '' ) );
				return is_wp_error( $r ) ? $r : array( 'message' => $r );

			case 'medir':
				return DL_Measurement::generate( $c, $p );

			case 'cancelar_medicao':
				$m = DL_DB::get( 'medicoes', absint( $p['medicao'] ?? 0 ) );
				if ( ! $m || (int) $m['contrato_id'] !== $id ) {
					return new WP_Error( 'medicao', 'Medição não encontrada.' );
				}
				return DL_Measurement::cancel( $m );

			case 'remover_adicional':
				global $wpdb;
				if ( in_array( $c['status'], array( 'encerrado', 'cancelado' ), true ) ) {
					return new WP_Error( 'status', 'Contrato fechado.' );
				}
				$wpdb->delete( dl_table( 'itens' ), array( 'id' => absint( $p['item'] ?? 0 ), 'doc_tipo' => 'contrato', 'doc_id' => $id, 'ref_tipo' => 'adicional' ) );
				self::recalc( $id );
				dl_log( 'contratos', $id, 'Adicional removido' );
				return array( 'message' => 'Adicional removido.' );

			case 'adicional':
				$desc  = sanitize_text_field( $p['descricao'] ?? '' );
				$value = round( dl_decimal( $p['valor'] ?? 0 ), 2 );
				if ( ! $desc || $value <= 0 ) {
					return new WP_Error( 'dados', 'Informe descrição e valor.' );
				}
				self::add_extra( $id, $desc, $value );
				self::recalc( $id );
				dl_log( 'contratos', $id, 'Adicional lançado', $desc . ' — ' . dl_money( $value ) );
				return array( 'message' => 'Adicional lançado.' );
		}
		return new WP_Error( 'acao', 'Ação desconhecida.' );
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
		DL_DB::update( 'contratos', $new_id, array( 'numero' => self::new_number( $new_id ) ) );
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

	/* ------------------------------------------------- entrega e devolução */

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
			DL_Measurement::log( $it['id'], $c['id'], 'saida', $it['qtd'], $date );
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
			DL_Measurement::log( $it['id'], $c['id'], 'retorno', $qty, $date );
			$equip = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
			if ( $equip && null !== $hour && $hour > (float) $equip['horimetro'] ) {
				DL_DB::update( 'equipamentos', $equip['id'], array( 'horimetro' => $hour ) );
			}
			// Na cobrança por medição o tempo a mais já entra na medição: sem diária de atraso.
			if ( $charge_late && $late_days > 0 && $equip && ! DL_Measurement::is_measured( $c ) ) {
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
		if ( DL_Measurement::is_measured( $c2 ) ) {
			return ( $pending <= 0 ? 'Devolução registrada e contrato encerrado. Gere a medição final.' : 'Devolução parcial registrada; a próxima medição já considera a nova quantidade.' );
		}
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
}
