<?php
/**
 * Dados do painel inicial: indicadores, locações em andamento, funil, agenda,
 * linha do tempo da frota, gráficos e alertas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Dashboard {

	public static function kpis() {
		global $wpdb;
		$today = dl_today();
		$te    = dl_table( 'equipamentos' );
		$tc    = dl_table( 'contratos' );
		$tf    = dl_table( 'financeiro' );
		$ti    = dl_table( 'itens' );

		$fleet  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(CASE WHEN controle='quantidade' THEN qtd_total ELSE 1 END),0) FROM {$te} WHERE status <> 'inativo'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$maint  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(CASE WHEN controle='quantidade' THEN qtd_total ELSE 1 END),0) FROM {$te} WHERE status = 'manutencao'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out    = (float) $wpdb->get_var( "SELECT COALESCE(SUM(i.qtd - i.qtd_devolvida),0) FROM {$ti} i JOIN {$tc} c ON c.id=i.doc_id WHERE i.doc_tipo='contrato' AND i.ref_tipo='equipamento' AND c.status='ativo'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$month1 = current_time( 'Y-m-01' );
		$fin    = current_user_can( 'dl_financeiro' );

		$k = array(
			'frota'        => $fleet,
			'locados'      => $out,
			'manutencao'   => $maint,
			'disponiveis'  => max( 0, $fleet - $out - $maint ),
			'utilizacao'   => $fleet ? round( 100 * $out / $fleet, 1 ) : 0,
			'ativos'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='ativo'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'atrasados'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tc} WHERE status='ativo' AND data_prev_devolucao < %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'vencem_hoje'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tc} WHERE status='ativo' AND data_prev_devolucao = %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'solicitacoes' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='solicitacao'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'reservas'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='reservado'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'em_locacao_valor' => (float) $wpdb->get_var( "SELECT COALESCE(SUM(total),0) FROM {$tc} WHERE status='ativo'" ), // phpcs:ignore WordPress.DB.PreparedSQL
		);
		if ( $fin ) {
			$k += array(
				'receber_hoje'   => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento = %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
				'vencido'        => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento < %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
				'pagar_7'        => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='pagar' AND status='aberto' AND vencimento <= %s", dl_add_days( $today, 7 ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL
				'recebido_mes'   => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor_pago + juros + multa - desconto),0) FROM {$tf} WHERE tipo='receber' AND status='pago' AND data_pagamento >= %s", $month1 ) ), // phpcs:ignore WordPress.DB.PreparedSQL
				'contratado_mes' => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total),0) FROM {$tc} WHERE status IN ('reservado','ativo','encerrado') AND data_inicio >= %s", $month1 ) ), // phpcs:ignore WordPress.DB.PreparedSQL
				'a_faturar'      => (float) $wpdb->get_var( "SELECT COALESCE(SUM(CASE WHEN total > valor_faturado THEN total - valor_faturado ELSE 0 END),0) FROM {$tc} WHERE status IN ('ativo','encerrado','reservado')" ), // phpcs:ignore WordPress.DB.PreparedSQL
			);
		}
		return $k;
	}

	/** Abreviações em português, independentes do idioma do WordPress. */
	public static function month_name( $n ) {
		$m = array( 1 => 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' );
		return $m[ $n ];
	}

	public static function weekday( $w ) {
		$d = array( 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb' );
		return $d[ $w ];
	}

	/** Cartão de um contrato com tudo que a tela precisa. */
	public static function card( $c ) {
		$today   = dl_today();
		$cli     = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		$total_d = DL_Contracts::rental_days( $c['data_inicio'], $c['data_prev_devolucao'] );
		$elapsed = dl_days_between( $c['data_inicio'], $today ) + ( DL_Contracts::inclusive() ? 1 : 0 );
		$late    = DL_Contracts::is_late( $c ) ? dl_days_between( $c['data_prev_devolucao'], $today ) : 0;
		$items   = array();
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			$items[] = array(
				'id'        => (int) $it['id'],
				'ref_id'    => (int) $it['ref_id'],
				'descricao' => DL_DB::label( 'equipamentos', $it['ref_id'] ) ?: $it['descricao'],
				'qtd'       => (float) $it['qtd'],
				'pendente'  => (float) $it['qtd'] - (float) $it['qtd_devolvida'],
			);
		}
		$phone = $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '';
		$name  = $cli ? ( $cli['contato'] ? $cli['contato'] : $cli['nome'] ) : '';
		if ( 'ativo' === $c['status'] ) {
			$msg = $late
				? sprintf( 'Olá, %s! A devolução da locação %s estava prevista para %s. Podemos combinar a coleta ou a renovação?', $name, $c['numero'], dl_date( $c['data_prev_devolucao'] ) )
				: sprintf( 'Olá, %s! Passando para lembrar que a locação %s vence em %s. Quer renovar?', $name, $c['numero'], dl_date( $c['data_prev_devolucao'] ) );
		} else {
			$msg = sprintf( 'Olá, %s! Segue o orçamento %s da %s, no valor de %s: %s', $name, $c['numero'], dl_opt( 'empresa_nome' ), dl_money( $c['total'] ), DL_Documents::public_url( 'orcamento', $c['id'] ) );
		}
		return array(
			'id'            => (int) $c['id'],
			'numero'        => $c['numero'],
			'status'        => DL_Contracts::is_late( $c ) ? 'atrasado' : $c['status'],
			'status_raw'    => $c['status'],
			'cliente'       => $cli ? array( 'id' => (int) $cli['id'], 'nome' => $cli['nome'], 'telefone' => $phone, 'bloqueado' => (bool) $cli['bloqueado'] ) : null,
			'local_obra'    => trim( $c['local_obra'] . ( $c['endereco_entrega'] ? ' · ' . $c['endereco_entrega'] : '' ), ' ·' ),
			'entrega'       => $c['entrega'],
			'inicio'        => $c['data_inicio'],
			'fim'           => $c['data_prev_devolucao'],
			'dias_total'    => $total_d,
			'dias_passados' => max( 0, min( $total_d, $elapsed ) ),
			'dias_restantes' => dl_days_between( $today, $c['data_prev_devolucao'] ),
			'dias_atraso'   => $late,
			'progresso'     => max( 0, min( 100, round( 100 * $elapsed / $total_d ) ) ),
			'total'         => (float) $c['total'],
			'a_faturar'     => max( 0, round( (float) $c['total'] - (float) $c['valor_faturado'], 2 ) ),
			'origem'        => $c['origem'],
			'itens'         => $items,
			'whatsapp'      => $phone ? dl_whatsapp_link( $phone, $msg ) : '',
			'acoes'         => DL_Contracts::available_actions( $c ),
			'medicao'       => DL_Measurement::is_measured( $c ),
		);
	}

	/** Locações em andamento (mais urgentes primeiro). */
	public static function active() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'contratos' ) . " WHERE status = 'ativo' ORDER BY data_prev_devolucao ASC, id ASC LIMIT 300", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array_map( array( __CLASS__, 'card' ), $rows );
	}

	/** Funil comercial: pedidos do site, orçamentos e reservas. */
	public static function pipeline() {
		global $wpdb;
		$out = array();
		foreach ( array( 'solicitacao', 'orcamento', 'reservado' ) as $st ) {
			$order    = 'reservado' === $st ? 'data_inicio ASC' : 'id DESC';
			$rows     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'contratos' ) . " WHERE status = %s ORDER BY {$order} LIMIT 60", $st ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
			$out[ $st ] = array_map( array( __CLASS__, 'card' ), $rows );
		}
		return $out;
	}

	/** Agenda de hoje e amanhã: saídas e devoluções. */
	public static function agenda() {
		global $wpdb;
		$tc       = dl_table( 'contratos' );
		$today    = dl_today();
		$tomorrow = dl_add_days( $today, 1 );
		$out      = array();
		foreach ( array( $today => 'Hoje', $tomorrow => 'Amanhã' ) as $day => $label ) {
			$saidas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status = 'reservado' AND data_inicio " . ( $day === $today ? '<=' : '=' ) . ' %s ORDER BY data_inicio', $day ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
			$voltas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status = 'ativo' AND data_prev_devolucao = %s", $day ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
			$out[]  = array(
				'dia'       => $day,
				'label'     => $label,
				'saidas'    => array_map( array( __CLASS__, 'card' ), $saidas ),
				'devolucoes' => array_map( array( __CLASS__, 'card' ), $voltas ),
			);
		}
		return $out;
	}

	/** Recebido e contratado por mês (últimos N meses). */
	public static function revenue( $months = 6 ) {
		global $wpdb;
		$out = array();
		for ( $i = $months - 1; $i >= 0; $i-- ) {
			$start = date( 'Y-m-01', strtotime( current_time( 'Y-m-01' ) . " -{$i} months" ) );
			$end   = date( 'Y-m-t', strtotime( $start ) );
			$out[] = array(
				'mes'        => self::month_name( (int) date( 'n', strtotime( $start ) ) ) . '/' . date( 'y', strtotime( $start ) ),
				'recebido'   => current_user_can( 'dl_financeiro' ) ? (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(valor_pago + juros + multa - desconto),0) FROM ' . dl_table( 'financeiro' ) . " WHERE tipo='receber' AND status='pago' AND data_pagamento BETWEEN %s AND %s", $start, $end ) ) : null,
				'contratado' => (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(total),0) FROM ' . dl_table( 'contratos' ) . " WHERE status IN ('reservado','ativo','encerrado') AND data_inicio BETWEEN %s AND %s", $start, $end ) ),
			);
		}
		return $out;
	}

	/** Ocupação por categoria agora. */
	public static function by_category() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$cats = array();
		foreach ( $rows as $e ) {
			$name = DL_DB::label( 'categorias', (int) $e['categoria_id'] ) ?: 'Sem categoria';
			$f    = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
			$cats[ $name ]['frota']  = ( $cats[ $name ]['frota'] ?? 0 ) + $f;
			$cats[ $name ]['locado'] = ( $cats[ $name ]['locado'] ?? 0 ) + DL_Availability::out_now( $e['id'] );
		}
		$out = array();
		foreach ( $cats as $name => $v ) {
			$out[] = array( 'categoria' => $name, 'frota' => $v['frota'], 'locado' => $v['locado'], 'pct' => $v['frota'] ? round( 100 * $v['locado'] / $v['frota'] ) : 0 );
		}
		usort( $out, function ( $a, $b ) { return $b['pct'] <=> $a['pct']; } );
		return $out;
	}

	/** Alertas que pedem ação. */
	public static function alerts() {
		$out = array();
		foreach ( DL_Measurement::pending() as $p ) {
			$s     = $p['sugestao'];
			$out[] = array( 'tipo' => 'medicao', 'texto' => ( $s['final'] ? 'Medição final pendente: ' : 'Medição ' . $s['numero'] . ' pendente: ' ) . $p['contrato']['numero'] . ' — ' . DL_DB::label( 'clientes', $p['contrato']['cliente_id'] ) . ' (' . dl_date( $s['inicio'] ) . ' a ' . dl_date( $s['fim'] ) . ')', 'abrir' => 'contratos/' . $p['contrato']['id'] );
		}
		foreach ( DL_Service_Orders::preventive_due() as $e ) {
			$out[] = array( 'tipo' => 'manutencao', 'texto' => 'Preventiva vencida: ' . $e['nome'] . ' (' . dl_num( $e['horimetro'] - $e['ultima_manutencao_horas'], 0 ) . ' h)', 'abrir' => 'equipamentos/' . $e['id'] );
		}
		foreach ( DL_Stock::low_stock() as $p ) {
			$out[] = array( 'tipo' => 'estoque', 'texto' => 'Estoque baixo: ' . $p['nome'] . ' (' . dl_num( $p['estoque_atual'], 0 ) . ' ' . $p['unidade'] . ')', 'abrir' => 'produtos/' . $p['id'] );
		}
		global $wpdb;
		$os = $wpdb->get_results( 'SELECT id, numero, equipamento_id, prioridade FROM ' . dl_table( 'ordens_servico' ) . " WHERE status IN ('aberta','em_andamento','aguardando_peca') AND prioridade IN ('alta','urgente') ORDER BY id DESC LIMIT 10", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $os as $o ) {
			$out[] = array( 'tipo' => 'os', 'texto' => 'OS ' . $o['numero'] . ' (' . $o['prioridade'] . '): ' . DL_DB::label( 'equipamentos', $o['equipamento_id'] ), 'abrir' => 'os/' . $o['id'] );
		}
		return $out;
	}

	/**
	 * Linha do tempo: cada equipamento com as faixas ocupadas por contratos no intervalo.
	 */
	public static function timeline( $from, $days, $category = 0, $q = '' ) {
		global $wpdb;
		$days  = max( 7, min( 90, (int) $days ) );
		$to    = dl_add_days( $from, $days - 1 );
		$where = "status <> 'inativo'";
		if ( $category ) {
			$where .= $wpdb->prepare( ' AND categoria_id = %d', $category );
		}
		if ( $q ) {
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= $wpdb->prepare( ' AND (nome LIKE %s OR codigo LIKE %s)', $like, $like );
		}
		$equips = $wpdb->get_results( 'SELECT id, codigo, nome, controle, qtd_total, status, categoria_id FROM ' . dl_table( 'equipamentos' ) . " WHERE {$where} ORDER BY nome LIMIT 200", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$today  = dl_today();
		$rows   = array();
		foreach ( $equips as $e ) {
			$segs = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT c.id, c.numero, c.status, c.cliente_id, c.data_inicio, c.data_prev_devolucao, SUM(i.qtd - i.qtd_devolvida) AS qtd
					FROM ' . dl_table( 'itens' ) . ' i JOIN ' . dl_table( 'contratos' ) . " c ON c.id = i.doc_id
					WHERE i.doc_tipo = 'contrato' AND i.ref_tipo = 'equipamento' AND i.ref_id = %d
					AND c.status IN ('orcamento','solicitacao','reservado','ativo') AND i.qtd > i.qtd_devolvida
					AND c.data_inicio <= %s AND ( c.data_prev_devolucao >= %s OR c.status = 'ativo' )
					GROUP BY c.id ORDER BY c.data_inicio",
					$e['id'],
					$to,
					$from
				),
				ARRAY_A
			);
			$bars = array();
			foreach ( $segs as $s ) {
				$late  = 'ativo' === $s['status'] && $s['data_prev_devolucao'] < $today;
				$end   = $late ? max( $today, $s['data_prev_devolucao'] ) : $s['data_prev_devolucao'];
				$start = max( $from, $s['data_inicio'] );
				$end   = min( $to, $end );
				if ( $end < $start ) {
					continue;
				}
				$bars[] = array(
					'contrato' => (int) $s['id'],
					'numero'   => $s['numero'],
					'status'   => $late ? 'atrasado' : $s['status'],
					'cliente'  => DL_DB::label( 'clientes', $s['cliente_id'] ),
					'qtd'      => (float) $s['qtd'],
					'inicio'   => $s['data_inicio'],
					'fim'      => $s['data_prev_devolucao'],
					'col'      => dl_days_between( $from, $start ),
					'span'     => dl_days_between( $start, $end ) + 1,
				);
			}
			$rows[] = array(
				'id'     => (int) $e['id'],
				'nome'   => trim( $e['codigo'] . ' ' . $e['nome'] ),
				'frota'  => 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1,
				'status' => $e['status'],
				'bars'   => $bars,
			);
		}
		$header = array();
		for ( $i = 0; $i < $days; $i++ ) {
			$d        = dl_add_days( $from, $i );
			$header[] = array( 'data' => $d, 'dia' => date( 'd', strtotime( $d ) ), 'semana' => self::weekday( (int) date( 'w', strtotime( $d ) ) ), 'fds' => in_array( (int) date( 'w', strtotime( $d ) ), array( 0, 6 ), true ), 'hoje' => $d === $today );
		}
		return array( 'de' => $from, 'ate' => $to, 'dias' => $header, 'linhas' => $rows );
	}
}
