<?php
/**
 * Painel inicial: indicadores do dia e listas de atenção.
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

		return array(
			'frota'        => $fleet,
			'locados'      => $out,
			'manutencao'   => $maint,
			'disponiveis'  => max( 0, $fleet - $out - $maint ),
			'utilizacao'   => $fleet ? 100 * $out / $fleet : 0,
			'ativos'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='ativo'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'atrasados'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tc} WHERE status='ativo' AND data_prev_devolucao < %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'solicitacoes' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='solicitacao'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'reservas'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='reservado'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'receber_hoje' => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento = %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'vencido'      => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento < %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'pagar_7'      => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor - valor_pago),0) FROM {$tf} WHERE tipo='pagar' AND status='aberto' AND vencimento <= %s", dl_add_days( $today, 7 ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'recebido_mes' => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(valor_pago + juros + multa - desconto),0) FROM {$tf} WHERE tipo='receber' AND status='pago' AND data_pagamento >= %s", $month1 ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'contratado_mes' => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total),0) FROM {$tc} WHERE status IN ('reservado','ativo','encerrado') AND data_inicio >= %s", $month1 ) ), // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function render() {
		dl_require_cap( 'dl_operar' );
		global $wpdb;
		$k     = self::kpis();
		$today = dl_today();
		$tc    = dl_table( 'contratos' );
		$fin   = current_user_can( 'dl_financeiro' );
		?>
		<div class="wrap dl-wrap">
			<h1><?php echo esc_html( dl_opt( 'empresa_nome' ) ); ?> — Painel</h1>
			<?php dl_render_notice(); ?>
			<p class="dl-quick">
				<a class="button button-primary" href="<?php echo esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'new' ) ) ); ?>">+ Novo orçamento de locação</a>
				<a class="button" href="<?php echo esc_url( dl_admin_url( 'dl-clientes', array( 'action' => 'new' ) ) ); ?>">+ Cliente</a>
				<a class="button" href="<?php echo esc_url( dl_admin_url( 'dl-equipamentos', array( 'action' => 'new' ) ) ); ?>">+ Equipamento</a>
				<a class="button" href="<?php echo esc_url( dl_admin_url( 'dl-os', array( 'action' => 'new' ) ) ); ?>">+ Ordem de serviço</a>
				<a class="button" href="<?php echo esc_url( dl_admin_url( 'dl-disponibilidade' ) ); ?>">Consultar disponibilidade</a>
			</p>
			<div class="dl-kpis">
				<div class="dl-kpi"><span>Frota ativa</span><strong><?php echo esc_html( dl_num( $k['frota'], 0 ) ); ?></strong></div>
				<div class="dl-kpi"><span>Locados agora</span><strong><?php echo esc_html( dl_num( $k['locados'], 0 ) ); ?></strong></div>
				<div class="dl-kpi"><span>Disponíveis</span><strong><?php echo esc_html( dl_num( $k['disponiveis'], 0 ) ); ?></strong></div>
				<div class="dl-kpi"><span>Em manutenção</span><strong><?php echo esc_html( dl_num( $k['manutencao'], 0 ) ); ?></strong></div>
				<div class="dl-kpi"><span>Utilização da frota</span><strong><?php echo esc_html( dl_num( $k['utilizacao'], 1 ) ); ?>%</strong></div>
				<div class="dl-kpi"><span>Contratos em andamento</span><strong><?php echo (int) $k['ativos']; ?></strong></div>
				<div class="dl-kpi <?php echo $k['atrasados'] ? 'dl-kpi-alert' : ''; ?>"><span>Devoluções atrasadas</span><strong><?php echo (int) $k['atrasados']; ?></strong></div>
				<div class="dl-kpi <?php echo $k['solicitacoes'] ? 'dl-kpi-warn' : ''; ?>"><span>Pedidos do site</span><strong><?php echo (int) $k['solicitacoes']; ?></strong></div>
				<?php if ( $fin ) : ?>
					<div class="dl-kpi"><span>A receber hoje</span><strong><?php echo esc_html( dl_money( $k['receber_hoje'] ) ); ?></strong></div>
					<div class="dl-kpi <?php echo $k['vencido'] > 0 ? 'dl-kpi-alert' : ''; ?>"><span>Vencido a receber</span><strong><?php echo esc_html( dl_money( $k['vencido'] ) ); ?></strong></div>
					<div class="dl-kpi"><span>A pagar em 7 dias</span><strong><?php echo esc_html( dl_money( $k['pagar_7'] ) ); ?></strong></div>
					<div class="dl-kpi"><span>Recebido no mês</span><strong><?php echo esc_html( dl_money( $k['recebido_mes'] ) ); ?></strong></div>
					<div class="dl-kpi"><span>Contratado no mês</span><strong><?php echo esc_html( dl_money( $k['contratado_mes'] ) ); ?></strong></div>
				<?php endif; ?>
			</div>

			<div class="dl-columns">
				<div class="dl-card">
					<h2>Devoluções: atrasadas e próximos 3 dias</h2>
					<?php
					$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='ativo' AND data_prev_devolucao <= %s ORDER BY data_prev_devolucao LIMIT 15", dl_add_days( $today, 3 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					self::contract_table( $rows, true );
					?>
				</div>
				<div class="dl-card">
					<h2>Saídas programadas (reservas)</h2>
					<?php
					$rows = $wpdb->get_results( "SELECT * FROM {$tc} WHERE status='reservado' ORDER BY data_inicio LIMIT 15", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					self::contract_table( $rows, false );
					?>
				</div>
				<div class="dl-card">
					<h2>Pedidos de orçamento do site</h2>
					<?php
					$rows = $wpdb->get_results( "SELECT * FROM {$tc} WHERE status='solicitacao' ORDER BY id DESC LIMIT 15", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					self::contract_table( $rows, false );
					?>
				</div>
				<div class="dl-card">
					<h2>Manutenção</h2>
					<?php
					$prev = DL_Service_Orders::preventive_due();
					$open = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'ordens_servico' ) . " WHERE status IN ('aberta','em_andamento','aguardando_peca') ORDER BY FIELD(prioridade,'urgente','alta','normal','baixa'), data_abertura LIMIT 15", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
					if ( ! $prev && ! $open ) {
						echo '<p>Nada pendente.</p>';
					}
					echo '<ul class="dl-links">';
					foreach ( $prev as $e ) {
						echo '<li>⚠ Preventiva vencida: <a href="' . esc_url( dl_admin_url( 'dl-os', array( 'action' => 'new', 'equipamento_id' => $e['id'], 'tipo' => 'preventiva' ) ) ) . '">' . esc_html( $e['nome'] ) . '</a> (' . esc_html( dl_num( $e['horimetro'] - $e['ultima_manutencao_horas'], 0 ) ) . ' h)</li>';
					}
					foreach ( $open as $os ) {
						echo '<li><a href="' . esc_url( dl_admin_url( 'dl-os', array( 'action' => 'edit', 'id' => $os['id'] ) ) ) . '">' . esc_html( $os['numero'] ) . '</a> ' . esc_html( DL_DB::label( 'equipamentos', $os['equipamento_id'] ) ) . ' ' . dl_badge( 'os', $os['status'] ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					foreach ( DL_Stock::low_stock() as $p ) {
						echo '<li>📦 Estoque baixo: <a href="' . esc_url( dl_admin_url( 'dl-produtos', array( 'action' => 'edit', 'id' => $p['id'] ) ) ) . '">' . esc_html( $p['nome'] ) . '</a> (' . esc_html( dl_num( $p['estoque_atual'], 0 ) ) . ')</li>';
					}
					echo '</ul>';
					?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function contract_table( $rows, $show_late ) {
		if ( ! $rows ) {
			echo '<p>Nada por aqui.</p>';
			return;
		}
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rows as $c ) {
			$late = $show_late && $c['data_prev_devolucao'] < dl_today();
			echo '<tr><td><a href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $c['id'] ) ) ) . '">' . esc_html( $c['numero'] ) . '</a></td><td>' . esc_html( DL_DB::label( 'clientes', $c['cliente_id'] ) ) . '</td><td>' . esc_html( dl_date( $show_late ? $c['data_prev_devolucao'] : $c['data_inicio'] ) ) . '</td><td>' . ( $late ? dl_badge( 'contrato', 'atrasado' ) : esc_html( dl_money( $c['total'] ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table>';
	}

	/** Consulta rápida de disponibilidade por período. */
	public static function render_availability() {
		dl_require_cap( 'dl_operar' );
		global $wpdb;
		$from  = isset( $_GET['de'] ) ? sanitize_text_field( wp_unslash( $_GET['de'] ) ) : dl_today(); // phpcs:ignore WordPress.Security.NonceVerification
		$to    = isset( $_GET['ate'] ) ? sanitize_text_field( wp_unslash( $_GET['ate'] ) ) : dl_add_days( dl_today(), 7 ); // phpcs:ignore WordPress.Security.NonceVerification
		$cat   = absint( $_GET['cat'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$to    = max( $from, $to );
		$where = $cat ? $wpdb->prepare( ' AND categoria_id = %d', $cat ) : '';
		$equips = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' {$where} ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$days  = DL_Contracts::rental_days( $from, $to );
		?>
		<div class="wrap dl-wrap">
			<h1>Disponibilidade da frota</h1>
			<form method="get" class="dl-filters">
				<input type="hidden" name="page" value="dl-disponibilidade">
				<label>de <input type="date" name="de" value="<?php echo esc_attr( $from ); ?>"></label>
				<label>até <input type="date" name="ate" value="<?php echo esc_attr( $to ); ?>"></label>
				<select name="cat"><option value="0">Todas as categorias</option>
				<?php foreach ( DL_DB::options( 'categorias' ) as $id => $name ) : ?>
					<option value="<?php echo (int) $id; ?>" <?php selected( $cat, $id ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
				</select>
				<button class="button">Consultar</button>
			</form>
			<table class="widefat striped dl-table">
				<thead><tr><th>Equipamento</th><th>Frota</th><th>Livre no período</th><th>Melhor preço p/ <?php echo (int) $days; ?> dia(s)</th><th>Ocupação</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $equips as $e ) : ?>
					<?php
					$free  = DL_Availability::available( $e['id'], $from, $to );
					$fleet = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
					$best  = DL_Pricing::best_price( DL_Contracts::rates( $e ), $days );
					$sched = DL_Availability::schedule( $e['id'], $from );
					?>
					<tr>
						<td><a href="<?php echo esc_url( dl_admin_url( 'dl-equipamentos', array( 'action' => 'edit', 'id' => $e['id'] ) ) ); ?>"><?php echo esc_html( trim( $e['codigo'] . ' ' . $e['nome'] ) ); ?></a> <?php echo 'manutencao' === $e['status'] ? dl_badge( 'equipamento', 'manutencao' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><?php echo (int) $fleet; ?></td>
						<td><strong style="color:<?php echo $free > 0 ? '#1a7f37' : '#b32d2e'; ?>"><?php echo esc_html( dl_num( $free, 0 ) ); ?></strong></td>
						<td><?php echo $best['total'] ? esc_html( dl_money( $best['total'] ) . ' (' . $best['descricao'] . ')' ) : '—'; ?></td>
						<td class="dl-small">
							<?php foreach ( array_slice( $sched, 0, 4 ) as $s ) : ?>
								<a href="<?php echo esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $s['id'] ) ) ); ?>"><?php echo esc_html( $s['numero'] ); ?></a> <?php echo esc_html( dl_date( $s['data_inicio'] ) . '–' . dl_date( $s['data_prev_devolucao'] ) . ' (' . ( dl_statuses( 'contrato' )[ $s['status'] ] ?? '' ) . ')' ); ?><br>
							<?php endforeach; ?>
						</td>
						<td><?php if ( $free > 0 ) : ?><a class="button button-small" href="<?php echo esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'new', 'data_inicio' => $from, 'data_prev_devolucao' => $to, 'equip' => $e['id'] ) ) ); ?>">Orçar</a><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
