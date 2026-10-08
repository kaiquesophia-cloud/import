<?php
/**
 * Relatórios gerenciais. Devolvem dados puros: colunas, linhas (com destino de clique e
 * destaque), indicadores de resumo e, quando faz sentido, dados de gráfico.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Reports {

	public static function reports() {
		return array(
			'locados'       => array( 'Locados agora', 'dl_operar' ),
			'devolucoes'    => array( 'Devoluções', 'dl_operar' ),
			'utilizacao'    => array( 'Utilização da frota', 'dl_financeiro' ),
			'faturamento'   => array( 'Faturamento', 'dl_financeiro' ),
			'fluxo'         => array( 'Fluxo de caixa', 'dl_financeiro' ),
			'clientes'      => array( 'Ranking de clientes', 'dl_financeiro' ),
			'inadimplencia' => array( 'Inadimplência', 'dl_financeiro' ),
			'manutencao'    => array( 'Manutenção', 'dl_operar' ),
			'estoque'       => array( 'Estoque', 'dl_operar' ),
		);
	}

	/** Uma linha do relatório. */
	private static function row( array $cells, $open = null, $tone = null ) {
		return array( 'cells' => array_map( 'strval', $cells ), 'open' => $open, 'tone' => $tone );
	}

	/** Dias de sobreposição entre dois intervalos fechados. */
	public static function overlap_days( $a_start, $a_end, $b_start, $b_end ) {
		$s = max( $a_start, $b_start );
		$e = min( $a_end, $b_end );
		return $e < $s ? 0 : dl_days_between( $s, $e ) + 1;
	}

	public static function build( $key, $from, $to ) {
		global $wpdb;
		$ti    = dl_table( 'itens' );
		$tc    = dl_table( 'contratos' );
		$tf    = dl_table( 'financeiro' );
		$today = dl_today();
		$out   = array( 'cols' => array(), 'rows' => array(), 'summary' => array(), 'period' => false, 'note' => '', 'chart' => null );

		switch ( $key ) {
			case 'locados':
				$out['cols'] = array( 'Equipamento', 'Qtd fora', 'Cliente', 'Contrato', 'Saída', 'Devolução prevista', 'Situação' );
				$rows        = $wpdb->get_results( "SELECT i.descricao, i.qtd - i.qtd_devolvida AS fora, c.id, c.numero, c.cliente_id, c.data_inicio, c.data_prev_devolucao FROM {$ti} i JOIN {$tc} c ON c.id = i.doc_id WHERE i.doc_tipo='contrato' AND i.ref_tipo='equipamento' AND c.status='ativo' AND i.qtd > i.qtd_devolvida ORDER BY c.data_prev_devolucao", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as $r ) {
					$late          = $r['data_prev_devolucao'] < $today;
					$out['rows'][] = self::row( array( $r['descricao'], dl_num( $r['fora'], 0 ), DL_DB::label( 'clientes', $r['cliente_id'] ), $r['numero'], dl_date( $r['data_inicio'] ), dl_date( $r['data_prev_devolucao'] ), $late ? 'Em atraso' : 'Em locação' ), 'contratos/' . $r['id'], $late ? 'danger' : null );
				}
				$out['summary'] = array( 'Itens fora' => count( $rows ) );
				break;

			case 'devolucoes':
				$out['cols'] = array( 'Devolução prevista', 'Contrato', 'Cliente', 'Telefone', 'Obra', 'Dias de atraso', 'Total' );
				$rows        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='ativo' AND data_prev_devolucao <= %s ORDER BY data_prev_devolucao", dl_add_days( $today, 7 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$late        = 0;
				foreach ( $rows as $r ) {
					$cli   = DL_DB::get( 'clientes', (int) $r['cliente_id'] );
					$days  = max( 0, dl_days_between( $r['data_prev_devolucao'], $today ) );
					$late += $days > 0 ? 1 : 0;
					$out['rows'][] = self::row( array( dl_date( $r['data_prev_devolucao'] ), $r['numero'], $cli ? $cli['nome'] : '', $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '', $r['local_obra'], $days ? $days : '—', dl_money( $r['total'] ) ), 'contratos/' . $r['id'], $days ? 'danger' : null );
				}
				$out['summary'] = array( 'Próximos 7 dias e atrasados' => count( $rows ), 'Atrasados' => $late );
				break;

			case 'utilizacao':
				$out['period'] = true;
				$out['cols']   = array( 'Equipamento', 'Frota', 'Dias locados', 'Dias disponíveis', 'Utilização', 'Receita no período', 'Receita acumulada', 'Valor de aquisição', 'Retorno acumulado' );
				$period_days   = dl_days_between( $from, $to ) + 1;
				$equips        = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$tot_used      = 0;
				$tot_avail     = 0;
				$tot_rev       = 0;
				$chart         = array();
				foreach ( $equips as $e ) {
					$items = $wpdb->get_results( $wpdb->prepare( "SELECT i.*, c.data_inicio, c.data_prev_devolucao, c.data_encerramento, c.status FROM {$ti} i JOIN {$tc} c ON c.id=i.doc_id WHERE i.doc_tipo='contrato' AND i.ref_tipo='equipamento' AND i.ref_id=%d AND c.status IN ('ativo','encerrado')", $e['id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					$used  = 0;
					$rev   = 0;
					$acc   = 0;
					foreach ( $items as $it ) {
						$start = $it['data_saida'] ? $it['data_saida'] : $it['data_inicio'];
						if ( $it['data_retorno'] ) {
							$end = $it['data_retorno'];
						} elseif ( 'ativo' === $it['status'] ) {
							$end = $today;
						} else {
							$end = $it['data_encerramento'] ? $it['data_encerramento'] : $it['data_prev_devolucao'];
						}
						$len   = max( 1, dl_days_between( $start, $end ) + 1 );
						$ov    = self::overlap_days( $start, $end, $from, $to );
						$used += $ov * (float) $it['qtd'];
						$rev  += (float) $it['total'] * $ov / $len;
						$acc  += (float) $it['total'];
					}
					$fleet      = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
					$avail      = $period_days * $fleet;
					$pct        = $avail ? 100 * $used / $avail : 0;
					$tot_used  += $used;
					$tot_avail += $avail;
					$tot_rev   += $rev;
					$chart[]    = array( 'label' => $e['nome'], 'value' => round( $pct, 1 ) );
					$out['rows'][] = self::row(
						array(
							trim( $e['codigo'] . ' ' . $e['nome'] ),
							$fleet,
							dl_num( $used, 0 ),
							dl_num( $avail, 0 ),
							dl_num( $pct, 1 ) . '%',
							dl_money( $rev ),
							dl_money( $acc ),
							dl_money( $e['valor_aquisicao'] ),
							(float) $e['valor_aquisicao'] > 0 ? dl_num( 100 * $acc / (float) $e['valor_aquisicao'], 0 ) . '%' : '—',
						),
						'equipamentos/' . $e['id']
					);
				}
				usort( $chart, function ( $a, $b ) { return $b['value'] <=> $a['value']; } );
				$out['chart']   = array( 'type' => 'bar', 'label' => 'Utilização (%)', 'items' => array_slice( $chart, 0, 15 ) );
				$out['summary'] = array( 'Utilização média' => dl_num( $tot_avail ? 100 * $tot_used / $tot_avail : 0, 1 ) . '%', 'Receita de locação (rateada)' => dl_money( $tot_rev ) );
				$out['note']    = 'Receita do período = valor de cada item rateado pelos dias que caem no período.';
				break;

			case 'faturamento':
				$out['period'] = true;
				$out['cols']   = array( 'Categoria', 'Tipo', 'Previsto (vencimento no período)', 'Realizado (pago no período)' );
				$cats          = DL_Modules::finance_categories();
				$prev          = $wpdb->get_results( $wpdb->prepare( "SELECT tipo, categoria, SUM(valor) v FROM {$tf} WHERE status <> 'cancelado' AND vencimento BETWEEN %s AND %s GROUP BY tipo, categoria", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$real          = $wpdb->get_results( $wpdb->prepare( "SELECT tipo, categoria, SUM(valor_pago + juros + multa - desconto) v FROM {$tf} WHERE status = 'pago' AND data_pagamento BETWEEN %s AND %s GROUP BY tipo, categoria", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$grid          = array();
				foreach ( $prev as $p ) {
					$grid[ $p['tipo'] . '|' . $p['categoria'] ]['p'] = (float) $p['v'];
				}
				foreach ( $real as $p ) {
					$grid[ $p['tipo'] . '|' . $p['categoria'] ]['r'] = (float) $p['v'];
				}
				ksort( $grid );
				$sum   = array( 'receber' => array( 0, 0 ), 'pagar' => array( 0, 0 ) );
				$chart = array();
				foreach ( $grid as $k => $v ) {
					list( $tipo, $cat ) = explode( '|', $k );
					$sum[ $tipo ][0]   += $v['p'] ?? 0;
					$sum[ $tipo ][1]   += $v['r'] ?? 0;
					$label              = $cats[ $cat ] ?? ( $cat ? $cat : 'Sem categoria' );
					$out['rows'][]      = self::row( array( $label, 'receber' === $tipo ? 'Receita' : 'Despesa', dl_money( $v['p'] ?? 0 ), dl_money( $v['r'] ?? 0 ) ), null, 'pagar' === $tipo ? 'muted' : null );
					if ( 'receber' === $tipo ) {
						$chart[] = array( 'label' => $label, 'value' => round( $v['r'] ?? 0, 2 ) );
					}
				}
				$out['chart']   = array( 'type' => 'doughnut', 'label' => 'Receitas recebidas', 'items' => $chart );
				$out['summary'] = array(
					'Receitas recebidas' => dl_money( $sum['receber'][1] ),
					'Despesas pagas'     => dl_money( $sum['pagar'][1] ),
					'Resultado de caixa' => dl_money( $sum['receber'][1] - $sum['pagar'][1] ),
					'Receitas previstas' => dl_money( $sum['receber'][0] ),
				);
				break;

			case 'fluxo':
				$out['period'] = true;
				$out['cols']   = array( 'Data', 'Entradas realizadas', 'Saídas realizadas', 'A receber (em aberto)', 'A pagar (em aberto)', 'Saldo do dia', 'Saldo acumulado' );
				$real          = $wpdb->get_results( $wpdb->prepare( "SELECT data_pagamento d, tipo, SUM(valor_pago + juros + multa - desconto) v FROM {$tf} WHERE status='pago' AND data_pagamento BETWEEN %s AND %s GROUP BY d, tipo", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$open          = $wpdb->get_results( $wpdb->prepare( "SELECT vencimento d, tipo, SUM(valor - valor_pago) v FROM {$tf} WHERE status='aberto' AND vencimento BETWEEN %s AND %s GROUP BY d, tipo", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$days          = array();
				foreach ( $real as $r ) {
					$days[ $r['d'] ][ 'r_' . $r['tipo'] ] = (float) $r['v'];
				}
				foreach ( $open as $r ) {
					$days[ $r['d'] ][ 'o_' . $r['tipo'] ] = (float) $r['v'];
				}
				ksort( $days );
				$acc   = 0;
				$chart = array();
				foreach ( $days as $d => $v ) {
					$day     = ( $v['r_receber'] ?? 0 ) - ( $v['r_pagar'] ?? 0 ) + ( $v['o_receber'] ?? 0 ) - ( $v['o_pagar'] ?? 0 );
					$acc    += $day;
					$chart[] = array( 'label' => dl_date( $d ), 'value' => round( $acc, 2 ) );
					$out['rows'][] = self::row( array( dl_date( $d ), dl_money( $v['r_receber'] ?? 0 ), dl_money( $v['r_pagar'] ?? 0 ), dl_money( $v['o_receber'] ?? 0 ), dl_money( $v['o_pagar'] ?? 0 ), dl_money( $day ), dl_money( $acc ) ), null, $acc < 0 ? 'danger' : null );
				}
				$out['chart'] = array( 'type' => 'line', 'label' => 'Saldo acumulado', 'items' => $chart );
				$out['note']  = 'Combina o que já foi pago (pela data do pagamento) com o que está em aberto (pela data de vencimento).';
				break;

			case 'clientes':
				$out['period'] = true;
				$out['cols']   = array( 'Cliente', 'Contratos', 'Valor locado', 'Recebido no período', 'Em aberto', 'Vencido' );
				$rows          = $wpdb->get_results( $wpdb->prepare( "SELECT cliente_id, COUNT(*) n, SUM(total) t FROM {$tc} WHERE status IN ('ativo','encerrado','reservado') AND data_inicio BETWEEN %s AND %s GROUP BY cliente_id ORDER BY t DESC LIMIT 100", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$chart         = array();
				foreach ( $rows as $r ) {
					$paid    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='pago' AND cliente_id=%d AND data_pagamento BETWEEN %s AND %s", $r['cliente_id'], $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$name    = DL_DB::label( 'clientes', $r['cliente_id'] );
					$overdue = DL_Finance::client_overdue( $r['cliente_id'] );
					$chart[] = array( 'label' => $name, 'value' => round( (float) $r['t'], 2 ) );
					$out['rows'][] = self::row( array( $name, (int) $r['n'], dl_money( $r['t'] ), dl_money( $paid ), dl_money( DL_Finance::client_open( $r['cliente_id'] ) ), dl_money( $overdue ) ), 'clientes/' . $r['cliente_id'], $overdue > 0 ? 'danger' : null );
				}
				$out['chart'] = array( 'type' => 'bar', 'label' => 'Valor locado', 'items' => array_slice( $chart, 0, 10 ) );
				break;

			case 'inadimplencia':
				$out['cols'] = array( 'Cliente', 'Telefone', 'Títulos vencidos', 'Mais antigo', 'Dias', 'Valor vencido' );
				$rows        = $wpdb->get_results( $wpdb->prepare( "SELECT cliente_id, COUNT(*) n, MIN(vencimento) m, SUM(valor - valor_pago) v FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento < %s GROUP BY cliente_id ORDER BY v DESC", $today ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$total       = 0;
				foreach ( $rows as $r ) {
					$cli    = DL_DB::get( 'clientes', (int) $r['cliente_id'] );
					$total += (float) $r['v'];
					$phone  = $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '';
					$row    = self::row( array( $cli ? $cli['nome'] : '(sem cliente)', $phone, (int) $r['n'], dl_date( $r['m'] ), dl_days_between( $r['m'], $today ), dl_money( $r['v'] ) ), $cli ? 'clientes/' . $cli['id'] : null, 'danger' );
					if ( $phone ) {
						$row['whatsapp'] = dl_whatsapp_link( $phone, 'Olá! Identificamos pendência de ' . dl_money( $r['v'] ) . ' com a ' . dl_opt( 'empresa_nome' ) . '. Podemos ajudar com a regularização?' );
					}
					$out['rows'][] = $row;
				}
				$out['summary'] = array( 'Total vencido' => dl_money( $total ), 'Clientes' => count( $rows ) );
				break;

			case 'manutencao':
				$out['period'] = true;
				$out['cols']   = array( 'Equipamento', 'Situação', 'Horímetro', 'Horas desde a preventiva', 'Preventiva a cada', 'OS abertas', 'Custo de OS no período' );
				$to_os         = dl_table( 'ordens_servico' );
				$equips        = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$st            = dl_statuses( 'equipamento' );
				foreach ( $equips as $e ) {
					$open  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$to_os} WHERE equipamento_id=%d AND status IN ('aberta','em_andamento','aguardando_peca')", $e['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$cost  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total),0) FROM {$to_os} WHERE equipamento_id=%d AND status='concluida' AND data_conclusao BETWEEN %s AND %s", $e['id'], $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$since = (float) $e['horimetro'] - (float) $e['ultima_manutencao_horas'];
					$due   = $e['manutencao_cada_horas'] > 0 && $since >= $e['manutencao_cada_horas'];
					if ( ! $open && ! $cost && ! $due && ! $e['manutencao_cada_horas'] ) {
						continue;
					}
					$out['rows'][] = self::row( array( trim( $e['codigo'] . ' ' . $e['nome'] ), $st[ $e['status'] ] ?? $e['status'], dl_num( $e['horimetro'], 1 ), dl_num( $since, 1 ) . ( $due ? ' — vencida' : '' ), $e['manutencao_cada_horas'] ? $e['manutencao_cada_horas'] . ' h' : '—', $open, dl_money( $cost ) ), 'equipamentos/' . $e['id'], $due ? 'danger' : null );
				}
				break;

			case 'estoque':
				$out['cols'] = array( 'Código', 'Produto', 'Tipo', 'Estoque', 'Mínimo', 'Custo', 'Valor em estoque', 'Alerta' );
				$rows        = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'produtos' ) . " WHERE ativo = 1 AND tipo <> 'servico' ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$value       = 0;
				foreach ( $rows as $p ) {
					$v      = (float) $p['estoque_atual'] * (float) $p['preco_custo'];
					$value += $v;
					$low    = (float) $p['estoque_minimo'] > 0 && (float) $p['estoque_atual'] <= (float) $p['estoque_minimo'];
					$out['rows'][] = self::row( array( $p['codigo'], $p['nome'], $p['tipo'], dl_num( $p['estoque_atual'], 2 ) . ' ' . $p['unidade'], dl_num( $p['estoque_minimo'], 2 ), dl_money( $p['preco_custo'] ), dl_money( $v ), $low ? 'repor' : '' ), 'produtos/' . $p['id'], $low ? 'danger' : null );
				}
				$out['summary'] = array( 'Valor do estoque (custo)' => dl_money( $value ) );
				break;
		}
		return $out;
	}
}
