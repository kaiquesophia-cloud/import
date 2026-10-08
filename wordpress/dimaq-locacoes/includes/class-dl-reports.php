<?php
/**
 * Relatórios gerenciais com exportação CSV.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Reports {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_export' ) );
	}

	public static function reports() {
		return array(
			'locados'     => array( 'Equipamentos locados agora', 'dl_operar' ),
			'devolucoes'  => array( 'Devoluções previstas e atrasadas', 'dl_operar' ),
			'utilizacao'  => array( 'Taxa de utilização e receita por equipamento', 'dl_financeiro' ),
			'faturamento' => array( 'Faturamento por categoria', 'dl_financeiro' ),
			'fluxo'       => array( 'Fluxo de caixa', 'dl_financeiro' ),
			'clientes'    => array( 'Ranking de clientes', 'dl_financeiro' ),
			'inadimplencia' => array( 'Inadimplência', 'dl_financeiro' ),
			'manutencao'  => array( 'Manutenção da frota', 'dl_operar' ),
			'estoque'     => array( 'Estoque', 'dl_operar' ),
		);
	}

	private static function period() {
		$from = isset( $_GET['de'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', wp_unslash( $_GET['de'] ) ) ? sanitize_text_field( wp_unslash( $_GET['de'] ) ) : current_time( 'Y-m-01' ); // phpcs:ignore WordPress.Security.NonceVerification
		$to   = isset( $_GET['ate'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', wp_unslash( $_GET['ate'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ate'] ) ) : current_time( 'Y-m-t' ); // phpcs:ignore WordPress.Security.NonceVerification
		return array( $from, max( $from, $to ) );
	}

	public static function maybe_export() {
		if ( ! isset( $_GET['page'], $_GET['export'] ) || 'dl-relatorios' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		check_admin_referer( 'dl_report_export' );
		$key = sanitize_key( $_GET['r'] ?? 'locados' );
		$all = self::reports();
		if ( ! isset( $all[ $key ] ) ) {
			return;
		}
		dl_require_cap( $all[ $key ][1] );
		list( $from, $to ) = self::period();
		$data = self::build( $key, $from, $to );
		$rows = array( $data['cols'] );
		foreach ( $data['rows'] as $r ) {
			$rows[] = array_map( 'wp_strip_all_tags', $r );
		}
		DL_Crud::send_csv( 'relatorio-' . $key . '-' . $from . '.csv', $rows );
	}

	public static function render() {
		dl_require_cap( 'dl_operar' );
		$all = array_filter(
			self::reports(),
			function ( $r ) {
				return current_user_can( $r[1] );
			}
		);
		$key = sanitize_key( $_GET['r'] ?? 'locados' ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $all[ $key ] ) ) {
			$key = array_key_first( $all );
		}
		list( $from, $to ) = self::period();
		$data = self::build( $key, $from, $to );
		?>
		<div class="wrap dl-wrap">
			<h1>Relatórios</h1>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $all as $k => $r ) : ?>
					<a class="nav-tab <?php echo $k === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( dl_admin_url( 'dl-relatorios', array( 'r' => $k, 'de' => $from, 'ate' => $to ) ) ); ?>"><?php echo esc_html( $r[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form method="get" class="dl-filters">
				<input type="hidden" name="page" value="dl-relatorios"><input type="hidden" name="r" value="<?php echo esc_attr( $key ); ?>">
				<?php if ( ! empty( $data['period'] ) ) : ?>
					<label>de <input type="date" name="de" value="<?php echo esc_attr( $from ); ?>"></label>
					<label>até <input type="date" name="ate" value="<?php echo esc_attr( $to ); ?>"></label>
					<button class="button">Aplicar</button>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( dl_admin_url( 'dl-relatorios', array( 'r' => $key, 'de' => $from, 'ate' => $to, 'export' => 1 ) ), 'dl_report_export' ) ); ?>">Exportar CSV</a>
				<button type="button" class="button" onclick="window.print()">Imprimir</button>
			</form>
			<?php if ( ! empty( $data['summary'] ) ) : ?>
				<div class="dl-kpis">
					<?php foreach ( $data['summary'] as $label => $value ) : ?>
						<div class="dl-kpi"><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( $value ); ?></strong></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $data['note'] ) ) : ?>
				<p class="description"><?php echo esc_html( $data['note'] ); ?></p>
			<?php endif; ?>
			<table class="widefat striped dl-table">
				<thead><tr><?php foreach ( $data['cols'] as $c ) : ?><th><?php echo esc_html( $c ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
				<?php if ( ! $data['rows'] ) : ?>
					<tr><td colspan="<?php echo count( $data['cols'] ); ?>">Nada a mostrar.</td></tr>
				<?php endif; ?>
				<?php foreach ( $data['rows'] as $r ) : ?>
					<tr><?php foreach ( $r as $v ) : ?><td><?php echo wp_kses_post( $v ); ?></td><?php endforeach; ?></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function contract_link( $id, $number ) {
		return '<a href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $id ) ) ) . '">' . esc_html( $number ) . '</a>';
	}

	/** Dias de sobreposição entre dois intervalos fechados. */
	public static function overlap_days( $a_start, $a_end, $b_start, $b_end ) {
		$s = max( $a_start, $b_start );
		$e = min( $a_end, $b_end );
		return $e < $s ? 0 : dl_days_between( $s, $e ) + 1;
	}

	public static function build( $key, $from, $to ) {
		global $wpdb;
		$ti = dl_table( 'itens' );
		$tc = dl_table( 'contratos' );
		$tf = dl_table( 'financeiro' );
		$today = dl_today();
		$out = array( 'cols' => array(), 'rows' => array(), 'summary' => array(), 'period' => false, 'note' => '' );

		switch ( $key ) {
			case 'locados':
				$out['cols'] = array( 'Equipamento', 'Qtd fora', 'Cliente', 'Contrato', 'Saída', 'Devolução prevista', 'Situação' );
				$rows        = $wpdb->get_results( "SELECT i.descricao, i.qtd - i.qtd_devolvida AS fora, c.id, c.numero, c.cliente_id, c.data_inicio, c.data_prev_devolucao FROM {$ti} i JOIN {$tc} c ON c.id = i.doc_id WHERE i.doc_tipo='contrato' AND i.ref_tipo='equipamento' AND c.status='ativo' AND i.qtd > i.qtd_devolvida ORDER BY c.data_prev_devolucao", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as $r ) {
					$late          = $r['data_prev_devolucao'] < $today;
					$out['rows'][] = array( esc_html( $r['descricao'] ), dl_num( $r['fora'], 0 ), esc_html( DL_DB::label( 'clientes', $r['cliente_id'] ) ), self::contract_link( $r['id'], $r['numero'] ), dl_date( $r['data_inicio'] ), dl_date( $r['data_prev_devolucao'] ), $late ? dl_badge( 'contrato', 'atrasado' ) : dl_badge( 'contrato', 'ativo' ) );
				}
				$out['summary'] = array( 'Itens fora' => count( $rows ) );
				break;

			case 'devolucoes':
				$out['cols'] = array( 'Devolução prevista', 'Contrato', 'Cliente', 'Telefone', 'Obra', 'Dias de atraso', 'Total' );
				$limit       = dl_add_days( $today, 7 );
				$rows        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='ativo' AND data_prev_devolucao <= %s ORDER BY data_prev_devolucao", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$late        = 0;
				foreach ( $rows as $r ) {
					$cli  = DL_DB::get( 'clientes', (int) $r['cliente_id'] );
					$days = max( 0, dl_days_between( $r['data_prev_devolucao'], $today ) );
					$late += $days > 0 ? 1 : 0;
					$out['rows'][] = array( dl_date( $r['data_prev_devolucao'] ), self::contract_link( $r['id'], $r['numero'] ), esc_html( $cli ? $cli['nome'] : '' ), esc_html( $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '' ), esc_html( $r['local_obra'] ), $days ? '<strong style="color:#b32d2e">' . $days . '</strong>' : '—', dl_money( $r['total'] ) );
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
				foreach ( $equips as $e ) {
					$items = $wpdb->get_results( $wpdb->prepare( "SELECT i.*, c.data_inicio, c.data_prev_devolucao, c.data_encerramento, c.status FROM {$ti} i JOIN {$tc} c ON c.id=i.doc_id WHERE i.doc_tipo='contrato' AND i.ref_tipo='equipamento' AND i.ref_id=%d AND c.status IN ('ativo','encerrado')", $e['id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					$used  = 0;
					$rev   = 0;
					$acc   = 0;
					foreach ( $items as $it ) {
						$start = $it['data_saida'] ? $it['data_saida'] : $it['data_inicio'];
						$end   = $it['data_retorno'] ? $it['data_retorno'] : ( 'ativo' === $it['status'] ? max( $today, (string) $it['data_prev_devolucao'] ) : ( $it['data_encerramento'] ? $it['data_encerramento'] : $it['data_prev_devolucao'] ) );
						if ( 'ativo' === $it['status'] ) {
							$end = min( $end, $today );
						}
						$len   = max( 1, dl_days_between( $start, $end ) + 1 );
						$ov    = self::overlap_days( $start, $end, $from, $to );
						$used += $ov * (float) $it['qtd'];
						$rev  += (float) $it['total'] * $ov / $len;
						$acc  += (float) $it['total'];
					}
					$fleet     = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
					$avail     = $period_days * $fleet;
					$tot_used += $used;
					$tot_avail += $avail;
					$tot_rev  += $rev;
					$out['rows'][] = array(
						'<a href="' . esc_url( dl_admin_url( 'dl-equipamentos', array( 'action' => 'edit', 'id' => $e['id'] ) ) ) . '">' . esc_html( trim( $e['codigo'] . ' ' . $e['nome'] ) ) . '</a>',
						$fleet,
						dl_num( $used, 0 ),
						$avail,
						dl_num( $avail ? 100 * $used / $avail : 0, 1 ) . '%',
						dl_money( $rev ),
						dl_money( $acc ),
						dl_money( $e['valor_aquisicao'] ),
						(float) $e['valor_aquisicao'] > 0 ? dl_num( 100 * $acc / (float) $e['valor_aquisicao'], 0 ) . '%' : '—',
					);
				}
				$out['summary'] = array( 'Utilização média da frota' => dl_num( $tot_avail ? 100 * $tot_used / $tot_avail : 0, 1 ) . '%', 'Receita de locação no período (rateada por dia)' => dl_money( $tot_rev ) );
				$out['note']    = 'Receita do período = valor de cada item rateado pelos dias que caem no período. Receita acumulada = soma dos itens de todos os contratos em andamento ou encerrados.';
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
				$sum = array( 'receber' => array( 0, 0 ), 'pagar' => array( 0, 0 ) );
				foreach ( $grid as $k => $v ) {
					list( $tipo, $cat ) = explode( '|', $k );
					$sum[ $tipo ][0]   += $v['p'] ?? 0;
					$sum[ $tipo ][1]   += $v['r'] ?? 0;
					$out['rows'][]      = array( esc_html( $cats[ $cat ] ?? ( $cat ? $cat : 'Sem categoria' ) ), 'receber' === $tipo ? 'Receita' : 'Despesa', dl_money( $v['p'] ?? 0 ), dl_money( $v['r'] ?? 0 ) );
				}
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
				$acc = 0;
				foreach ( $days as $d => $v ) {
					$day  = ( $v['r_receber'] ?? 0 ) - ( $v['r_pagar'] ?? 0 ) + ( $v['o_receber'] ?? 0 ) - ( $v['o_pagar'] ?? 0 );
					$acc += $day;
					$out['rows'][] = array( dl_date( $d ), dl_money( $v['r_receber'] ?? 0 ), dl_money( $v['r_pagar'] ?? 0 ), dl_money( $v['o_receber'] ?? 0 ), dl_money( $v['o_pagar'] ?? 0 ), dl_money( $day ), '<strong>' . dl_money( $acc ) . '</strong>' );
				}
				$out['note'] = 'Combina o que já foi pago (pela data do pagamento) com o que está em aberto (pela data de vencimento).';
				break;

			case 'clientes':
				$out['period'] = true;
				$out['cols']   = array( 'Cliente', 'Contratos', 'Valor locado', 'Recebido no período', 'Em aberto', 'Vencido' );
				$rows          = $wpdb->get_results( $wpdb->prepare( "SELECT cliente_id, COUNT(*) n, SUM(total) t FROM {$tc} WHERE status IN ('ativo','encerrado','reservado') AND data_inicio BETWEEN %s AND %s GROUP BY cliente_id ORDER BY t DESC LIMIT 100", $from, $to ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as $r ) {
					$paid          = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='pago' AND cliente_id=%d AND data_pagamento BETWEEN %s AND %s", $r['cliente_id'], $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$out['rows'][] = array( '<a href="' . esc_url( dl_admin_url( 'dl-clientes', array( 'action' => 'edit', 'id' => $r['cliente_id'] ) ) ) . '">' . esc_html( DL_DB::label( 'clientes', $r['cliente_id'] ) ) . '</a>', (int) $r['n'], dl_money( $r['t'] ), dl_money( $paid ), dl_money( DL_Finance::client_open( $r['cliente_id'] ) ), dl_money( DL_Finance::client_overdue( $r['cliente_id'] ) ) );
				}
				break;

			case 'inadimplencia':
				$out['cols'] = array( 'Cliente', 'Telefone', 'Títulos vencidos', 'Mais antigo', 'Dias', 'Valor vencido' );
				$rows        = $wpdb->get_results( $wpdb->prepare( "SELECT cliente_id, COUNT(*) n, MIN(vencimento) m, SUM(valor - valor_pago) v FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento < %s GROUP BY cliente_id ORDER BY v DESC", $today ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				$total       = 0;
				foreach ( $rows as $r ) {
					$cli    = DL_DB::get( 'clientes', (int) $r['cliente_id'] );
					$total += (float) $r['v'];
					$link   = '<a href="' . esc_url( dl_admin_url( 'dl-financeiro', array( 'f_cliente_id' => $r['cliente_id'], 'f_status' => 'vencido' ) ) ) . '">' . esc_html( $cli ? $cli['nome'] : '(sem cliente)' ) . '</a>';
					$phone  = $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '';
					if ( $phone ) {
						$phone = '<a target="_blank" href="' . esc_url( dl_whatsapp_link( $phone, 'Olá! Identificamos pendência de ' . dl_money( $r['v'] ) . ' com a ' . dl_opt( 'empresa_nome' ) . '. Podemos ajudar com a regularização?' ) ) . '">' . esc_html( $phone ) . '</a>';
					}
					$out['rows'][] = array( $link, $phone, (int) $r['n'], dl_date( $r['m'] ), dl_days_between( $r['m'], $today ), dl_money( $r['v'] ) );
				}
				$out['summary'] = array( 'Total vencido' => dl_money( $total ), 'Clientes' => count( $rows ) );
				break;

			case 'manutencao':
				$out['period'] = true;
				$out['cols']   = array( 'Equipamento', 'Situação', 'Horímetro', 'Horas desde a preventiva', 'Preventiva a cada', 'OS abertas', 'Custo de OS no período' );
				$to_os         = dl_table( 'ordens_servico' );
				$equips        = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $equips as $e ) {
					$open  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$to_os} WHERE equipamento_id=%d AND status IN ('aberta','em_andamento','aguardando_peca')", $e['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$cost  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total),0) FROM {$to_os} WHERE equipamento_id=%d AND status='concluida' AND data_conclusao BETWEEN %s AND %s", $e['id'], $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL
					$since = (float) $e['horimetro'] - (float) $e['ultima_manutencao_horas'];
					$due   = $e['manutencao_cada_horas'] > 0 && $since >= $e['manutencao_cada_horas'];
					if ( ! $open && ! $cost && ! $due && ! $e['manutencao_cada_horas'] ) {
						continue;
					}
					$out['rows'][] = array( esc_html( trim( $e['codigo'] . ' ' . $e['nome'] ) ), dl_badge( 'equipamento', $e['status'] ), dl_num( $e['horimetro'], 1 ), $due ? '<strong style="color:#b32d2e">' . dl_num( $since, 1 ) . ' — vencida</strong>' : dl_num( $since, 1 ), $e['manutencao_cada_horas'] ? $e['manutencao_cada_horas'] . ' h' : '—', $open, dl_money( $cost ) );
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
					$out['rows'][] = array( esc_html( $p['codigo'] ), esc_html( $p['nome'] ), esc_html( $p['tipo'] ), dl_num( $p['estoque_atual'], 2 ) . ' ' . esc_html( $p['unidade'] ), dl_num( $p['estoque_minimo'], 2 ), dl_money( $p['preco_custo'] ), dl_money( $v ), $low ? '<strong style="color:#b32d2e">repor</strong>' : '' );
				}
				$out['summary'] = array( 'Valor do estoque (custo)' => dl_money( $value ) );
				break;
		}
		return $out;
	}
}
