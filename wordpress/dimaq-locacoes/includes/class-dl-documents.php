<?php
/**
 * Documentos para imprimir ou salvar em PDF: orçamento, contrato, checklist, fatura de locação,
 * ordem de serviço, pedido de venda e recibo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Documents {

	const TYPES = array(
		'orcamento' => 'contratos',
		'contrato'  => 'contratos',
		'checklist' => 'contratos',
		'fatura'    => 'contratos',
		'os'        => 'ordens_servico',
		'venda'     => 'vendas',
		'recibo'    => 'financeiro',
	);

	public static function init() {
		add_action( 'admin_post_dl_doc', array( __CLASS__, 'admin_view' ) );
		add_action( 'template_redirect', array( __CLASS__, 'public_view' ) );
	}

	/** Link interno (usuário logado com permissão). */
	public static function url( $type, $id ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'dl_doc', 'tipo' => $type, 'id' => (int) $id ), admin_url( 'admin-post.php' ) ), 'dl_doc_' . $type . '_' . $id );
	}

	/** Link público assinado, para mandar ao cliente. */
	public static function public_url( $type, $id ) {
		return add_query_arg( array( 'dl_doc' => $type, 'id' => (int) $id, 'k' => self::key( $type, $id ) ), home_url( '/' ) );
	}

	public static function key( $type, $id ) {
		return substr( hash_hmac( 'sha256', $type . '|' . (int) $id, wp_salt( 'auth' ) . 'dl_doc' ), 0, 24 );
	}

	public static function admin_view() {
		$type = sanitize_key( $_GET['tipo'] ?? '' );
		$id   = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'dl_doc_' . $type . '_' . $id );
		dl_require_cap( 'recibo' === $type ? 'dl_financeiro' : 'dl_operar' );
		self::output( $type, $id );
	}

	public static function public_view() {
		if ( empty( $_GET['dl_doc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$type = sanitize_key( $_GET['dl_doc'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$id   = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$k    = sanitize_text_field( wp_unslash( $_GET['k'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! hash_equals( self::key( $type, $id ), $k ) ) {
			wp_die( 'Link inválido ou expirado.', '', 403 );
		}
		self::output( $type, $id );
	}

	public static function output( $type, $id ) {
		if ( ! isset( self::TYPES[ $type ] ) ) {
			wp_die( 'Documento inválido.' );
		}
		$row = DL_DB::get( self::TYPES[ $type ], $id );
		if ( ! $row ) {
			wp_die( 'Documento não encontrado.' );
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		$titles = array(
			'orcamento' => 'Orçamento de locação',
			'contrato'  => 'Contrato de locação de equipamentos',
			'checklist' => 'Checklist de saída e retorno',
			'fatura'    => 'Fatura de locação',
			'os'        => 'Ordem de serviço',
			'venda'     => 'Pedido de venda',
			'recibo'    => 'Recibo',
		);
		$number = $row['numero'] ?? ( 'Nº ' . $row['id'] );
		?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $titles[ $type ] . ' ' . $number ); ?></title>
<style>
	:root { --c: <?php echo esc_html( dl_opt( 'cor_primaria', '#f2a900' ) ); ?>; }
	* { box-sizing: border-box; }
	body { font: 13px/1.45 Arial, Helvetica, sans-serif; color: #222; background: #eee; margin: 0; }
	.page { background: #fff; max-width: 820px; margin: 20px auto; padding: 32px 36px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
	header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid var(--c); padding-bottom: 12px; margin-bottom: 16px; gap: 16px; }
	header img { max-height: 64px; max-width: 220px; }
	header .co { font-size: 12px; color: #555; }
	header .co strong { font-size: 15px; color: #111; }
	h1 { font-size: 18px; margin: 0 0 4px; text-transform: uppercase; }
	h2 { font-size: 12.5px; text-transform: uppercase; background: #f4f4f4; padding: 4px 8px; margin: 14px 0 6px; border-left: 4px solid var(--c); }
	table { width: 100%; border-collapse: collapse; }
	th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; vertical-align: top; }
	th { background: #fafafa; font-size: 12px; }
	td.n, th.n { text-align: right; white-space: nowrap; }
	.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; }
	.tot td { font-weight: bold; }
	.big { font-size: 16px; }
	.clauses { white-space: pre-wrap; font-size: 12px; text-align: justify; }
	.sign { display: flex; gap: 40px; margin-top: 44px; page-break-inside: avoid; }
	.sign div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 4px; font-size: 12px; }
	.muted { color: #777; font-size: 11px; }
	.box { border: 1px solid #ddd; min-height: 34px; padding: 6px 8px; white-space: pre-wrap; }
	.bar { text-align: center; margin: 14px; }
	.bar button { background: var(--c); border: 0; padding: 10px 22px; font-size: 14px; cursor: pointer; border-radius: 4px; }
	@page { size: A4; margin: 12mm; }
	@media print {
		body { background: #fff; font-size: 11.5px; line-height: 1.35; }
		.page { box-shadow: none; margin: 0; max-width: none; padding: 0; }
		.bar { display: none; }
		header { padding-bottom: 8px; margin-bottom: 10px; }
		header img { max-height: 52px; }
		h2 { margin: 10px 0 5px; }
		th, td { padding: 3px 6px; }
		.box { min-height: 0; }
		.sign { margin-top: 36px; }
		table, .box, .grid { page-break-inside: avoid; }
	}
</style></head><body>
<div class="bar"><button onclick="window.print()">Imprimir / salvar em PDF</button></div>
<div class="page">
	<header>
		<div>
			<img src="<?php echo esc_url( dl_opt( 'logo_url' ) ? dl_opt( 'logo_url' ) : DL_URL . 'assets/app/logo-dimaq-original.png' ); ?>" alt="<?php echo esc_attr( dl_opt( 'empresa_nome' ) ); ?>"><br>
			<div class="co"><strong><?php echo esc_html( dl_opt( 'empresa_nome' ) ); ?></strong><br>
			<?php echo esc_html( trim( ( dl_opt( 'empresa_cnpj' ) ? 'CNPJ ' . dl_opt( 'empresa_cnpj' ) : '' ) . ( dl_opt( 'empresa_ie' ) ? ' · IE ' . dl_opt( 'empresa_ie' ) : '' ) ) ); ?><br>
			<?php echo esc_html( trim( dl_opt( 'empresa_endereco' ) . ' — ' . dl_opt( 'empresa_cidade' ) . '/' . dl_opt( 'empresa_uf' ), ' —/' ) ); ?><br>
			<?php echo esc_html( trim( dl_opt( 'empresa_telefone' ) . ' · ' . dl_opt( 'empresa_whatsapp' ) . ' · ' . dl_opt( 'empresa_email' ), ' ·' ) ); ?></div>
		</div>
		<div style="text-align:right"><h1><?php echo esc_html( $titles[ $type ] ); ?></h1><div class="big"><?php echo esc_html( $number ); ?></div><div class="muted">Emitido em <?php echo esc_html( current_time( 'd/m/Y H:i' ) ); ?></div></div>
	</header>
		<?php
		$method = 'render_' . $type;
		self::$method( $row );
		?>
</div></body></html>
		<?php
		exit;
	}

	private static function client_block( $client_id, $title = 'Cliente' ) {
		$c = DL_DB::get( 'clientes', (int) $client_id );
		if ( ! $c ) {
			return;
		}
		echo '<h2>' . esc_html( $title ) . '</h2><div class="grid">';
		echo '<div><strong>' . esc_html( $c['nome'] ) . '</strong>' . ( $c['fantasia'] ? ' (' . esc_html( $c['fantasia'] ) . ')' : '' ) . '</div>';
		echo '<div>' . esc_html( ( 'PF' === $c['tipo'] ? 'CPF ' : 'CNPJ ' ) . dl_format_document( $c['documento'] ) . ( $c['ie_rg'] ? ' · ' . ( 'PF' === $c['tipo'] ? 'RG ' : 'IE ' ) . $c['ie_rg'] : '' ) ) . '</div>';
		$street = trim( implode( ', ', array_filter( array( $c['logradouro'], $c['numero'], $c['complemento'] ) ) ) . ( $c['bairro'] ? ' — ' . $c['bairro'] : '' ), ' —' );
		$city   = trim( $c['cidade'] . ( $c['uf'] ? '/' . $c['uf'] : '' ) . ( $c['cep'] ? ' · CEP ' . $c['cep'] : '' ) );
		$phones = array_unique( array_filter( array( dl_format_phone( $c['telefone'] ), dl_format_phone( $c['whatsapp'] ) ) ) );
		$lines  = array_filter( array( $street, $city, implode( ' · ', $phones ), $c['email'] ) );
		foreach ( $lines as $line ) {
			echo '<div>' . esc_html( $line ) . '</div>';
		}
		if ( $c['contato'] ) {
			echo '<div>Contato: ' . esc_html( $c['contato'] ) . '</div>';
		}
		echo '</div>';
	}

	private static function contract_header( $c ) {
		self::client_block( $c['cliente_id'], 'Locatário' );
		$entregas = array( 'retirada' => 'Cliente retira', 'entrega' => 'Locadora entrega', 'entrega_coleta' => 'Locadora entrega e coleta' );
		echo '<h2>Locação</h2><div class="grid">';
		echo '<div>Período: <strong>' . esc_html( dl_date( $c['data_inicio'] ) . ' a ' . dl_date( $c['data_prev_devolucao'] ) ) . '</strong> (' . (int) DL_Contracts::rental_days( $c['data_inicio'], $c['data_prev_devolucao'] ) . ' dias)</div>';
		echo '<div>Entrega: ' . esc_html( $entregas[ $c['entrega'] ] ?? $c['entrega'] ) . '</div>';
		if ( $c['local_obra'] || $c['endereco_entrega'] ) {
			echo '<div>Obra: ' . esc_html( $c['local_obra'] ) . '</div><div>' . esc_html( $c['endereco_entrega'] ) . '</div>';
		}
		if ( $c['responsavel_obra'] ) {
			echo '<div>Responsável: ' . esc_html( $c['responsavel_obra'] . ' ' . $c['telefone_obra'] ) . '</div>';
		}
		echo '</div>';
	}

	private static function contract_items( $c, $show_replacement = false ) {
		$types = dl_period_types();
		echo '<h2>Equipamentos</h2><table><thead><tr><th>Equipamento</th><th class="n">Qtd</th><th>Cobrança</th><th class="n">Valor unit.</th><th class="n">Total</th>' . ( $show_replacement ? '<th class="n">Valor de reposição</th>' : '' ) . '</tr></thead><tbody>';
		$extras = array();
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'adicional' === $it['ref_tipo'] ) {
				$extras[] = $it;
				continue;
			}
			$e    = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
			$desc = $it['descricao'] . ( $e && $e['codigo'] ? ' [' . $e['codigo'] . ']' : '' ) . ( $e && $e['numero_serie'] ? ' — série ' . $e['numero_serie'] : '' );
			$per  = 'pacote' === $it['periodo_tipo'] ? 'Pacote do período' : dl_num( $it['periodos'], 0 ) . ' × ' . ( $types[ $it['periodo_tipo'] ]['label'] ?? '' );
			echo '<tr><td>' . esc_html( $desc ) . '</td><td class="n">' . esc_html( dl_num( $it['qtd'], 0 ) ) . '</td><td>' . esc_html( $per ) . '</td><td class="n">' . esc_html( dl_money( $it['valor_unit'] ) ) . '</td><td class="n">' . esc_html( dl_money( $it['total'] ) ) . '</td>';
			if ( $show_replacement ) {
				echo '<td class="n">' . esc_html( $e ? dl_money( $e['valor_reposicao'] ) : '' ) . '</td>';
			}
			echo '</tr>';
		}
		foreach ( $extras as $it ) {
			echo '<tr><td colspan="4">' . esc_html( $it['descricao'] ) . '</td><td class="n">' . esc_html( dl_money( $it['total'] ) ) . '</td>' . ( $show_replacement ? '<td></td>' : '' ) . '</tr>';
		}
		$cols = $show_replacement ? 5 : 4;
		echo '</tbody><tfoot>';
		if ( (float) $c['valor_frete'] > 0 ) {
			echo '<tr><td colspan="' . (int) ( $cols - 1 ) . '" class="n">Frete</td><td class="n">' . esc_html( dl_money( $c['valor_frete'] ) ) . '</td>' . ( $show_replacement ? '<td></td>' : '' ) . '</tr>';
		}
		if ( (float) $c['desconto'] > 0 ) {
			echo '<tr><td colspan="' . (int) ( $cols - 1 ) . '" class="n">Desconto</td><td class="n">- ' . esc_html( dl_money( $c['desconto'] ) ) . '</td>' . ( $show_replacement ? '<td></td>' : '' ) . '</tr>';
		}
		echo '<tr class="tot"><td colspan="' . (int) ( $cols - 1 ) . '" class="n">Total</td><td class="n big">' . esc_html( dl_money( $c['total'] ) ) . '</td>' . ( $show_replacement ? '<td></td>' : '' ) . '</tr>';
		echo '</tfoot></table>';
		if ( (float) $c['caucao'] > 0 ) {
			echo '<p>Caução: <strong>' . esc_html( dl_money( $c['caucao'] ) ) . '</strong> (devolvida após conferência do retorno).</p>';
		}
		$pay = dl_payment_methods();
		if ( $c['forma_pagamento'] || $c['condicao_pagamento'] ) {
			echo '<p>Pagamento: ' . esc_html( trim( ( $pay[ $c['forma_pagamento'] ] ?? '' ) . ' — ' . $c['condicao_pagamento'], ' —' ) ) . '</p>';
		}
		if ( $c['obs'] ) {
			echo '<h2>Observações</h2><div class="box">' . esc_html( $c['obs'] ) . '</div>';
		}
	}

	private static function render_orcamento( $c ) {
		self::contract_header( $c );
		self::contract_items( $c );
		echo '<p>Validade do orçamento: <strong>' . esc_html( dl_date( $c['validade_orcamento'] ) ?: dl_opt( 'validade_orcamento' ) . ' dias' ) . '</strong>. Disponibilidade confirmada somente após a aprovação.</p>';
		if ( dl_opt( 'dados_bancarios' ) ) {
			echo '<h2>Dados para pagamento</h2><div class="box">' . esc_html( dl_opt( 'dados_bancarios' ) ) . '</div>';
		}
	}

	private static function render_contrato( $c ) {
		self::contract_header( $c );
		self::contract_items( $c, true );
		$cli  = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		$vars = array(
			'{cliente}'            => $cli ? $cli['nome'] : '',
			'{documento_cliente}'  => $cli ? dl_format_document( $cli['documento'] ) : '',
			'{numero}'             => $c['numero'],
			'{data_inicio}'        => dl_date( $c['data_inicio'] ),
			'{data_devolucao}'     => dl_date( $c['data_prev_devolucao'] ),
			'{total}'              => dl_money( $c['total'] ),
			'{condicao_pagamento}' => $c['condicao_pagamento'] ? $c['condicao_pagamento'] : ( dl_payment_methods()[ $c['forma_pagamento'] ] ?? 'a combinar' ),
			'{local_obra}'         => trim( $c['local_obra'] . ' ' . $c['endereco_entrega'] ) ?: 'informado pelo locatário',
			'{multa_vencimento}'   => dl_num( dl_opt( 'multa_vencimento_pct' ), 2 ),
			'{juros_mes}'          => dl_num( dl_opt( 'juros_mes_pct' ), 2 ),
			'{empresa}'            => dl_opt( 'empresa_nome' ),
			'{cidade_empresa}'     => dl_opt( 'empresa_cidade' ) ?: '________',
		);
		echo '<h2>Condições gerais</h2><div class="clauses">' . esc_html( strtr( dl_opt( 'clausulas_contrato' ), $vars ) ) . '</div>';
		echo '<p>' . esc_html( ( dl_opt( 'empresa_cidade' ) ?: '________' ) . ', ' . date_i18n( 'j \d\e F \d\e Y', strtotime( $c['data_inicio'] ? $c['data_inicio'] : dl_today() ) ) ) . '.</p>';
		echo '<div class="sign"><div>' . esc_html( dl_opt( 'empresa_nome' ) ) . '<br>LOCADORA</div><div>' . esc_html( $cli ? $cli['nome'] : '' ) . '<br>LOCATÁRIO</div></div>';
		echo '<div class="sign"><div>Testemunha 1 — CPF</div><div>Testemunha 2 — CPF</div></div>';
	}

	private static function render_checklist( $c ) {
		self::contract_header( $c );
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			echo '<h2>' . esc_html( $it['descricao'] . ' — qtd ' . dl_num( $it['qtd'], 0 ) ) . '</h2><table><tr><th style="width:50%">Saída ' . esc_html( dl_date( $it['data_saida'] ) ) . ( null !== $it['horimetro_saida'] ? ' · horímetro ' . esc_html( dl_num( $it['horimetro_saida'], 1 ) ) : '' ) . '</th><th>Retorno ' . esc_html( dl_date( $it['data_retorno'] ) ) . ( null !== $it['horimetro_retorno'] ? ' · horímetro ' . esc_html( dl_num( $it['horimetro_retorno'], 1 ) ) : '' ) . '</th></tr>';
			echo '<tr><td><div class="box">' . esc_html( $it['checklist_saida'] ) . '</div></td><td><div class="box">' . esc_html( $it['checklist_retorno'] ) . ( $it['avarias'] ? "\nAvarias: " . esc_html( $it['avarias'] ) : '' ) . '</div></td></tr></table>';
		}
		echo '<p class="muted">Itens a conferir: estado geral, limpeza, acessórios, cabos, mangueiras, discos/brocas, nível de combustível/óleo, funcionamento.</p>';
		echo '<div class="sign"><div>Conferente (locadora)</div><div>Cliente — entrega</div><div>Cliente — devolução</div></div>';
	}

	private static function render_fatura( $c ) {
		self::client_block( $c['cliente_id'], 'Locatário' );
		echo '<p>Referente ao contrato <strong>' . esc_html( $c['numero'] ) . '</strong>, período ' . esc_html( dl_date( $c['data_inicio'] ) . ' a ' . dl_date( $c['data_encerramento'] ? $c['data_encerramento'] : $c['data_prev_devolucao'] ) ) . '.</p>';
		self::contract_items( $c );
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'financeiro' ) . " WHERE origem = 'contrato' AND origem_id = %d AND status <> 'cancelado' ORDER BY vencimento", $c['id'] ), ARRAY_A );
		if ( $rows ) {
			echo '<h2>Vencimentos</h2><table><tr><th>Parcela</th><th>Vencimento</th><th class="n">Valor</th><th>Situação</th></tr>';
			foreach ( $rows as $r ) {
				echo '<tr><td>' . esc_html( $r['parcela'] . '/' . $r['total_parcelas'] ) . '</td><td>' . esc_html( dl_date( $r['vencimento'] ) ) . '</td><td class="n">' . esc_html( dl_money( $r['valor'] ) ) . '</td><td>' . esc_html( 'pago' === $r['status'] ? 'Pago em ' . dl_date( $r['data_pagamento'] ) : 'Em aberto' ) . '</td></tr>';
			}
			echo '</table>';
		}
		echo '<p class="muted">Fatura de locação de bens móveis — operação não sujeita ao ISS (LC 116/2003, item 3.01 vetado; STF Súmula Vinculante 31). Documento sem valor fiscal de nota.</p>';
		if ( dl_opt( 'dados_bancarios' ) ) {
			echo '<h2>Dados para pagamento</h2><div class="box">' . esc_html( dl_opt( 'dados_bancarios' ) ) . '</div>';
		}
	}

	private static function render_os( $os ) {
		$e = DL_DB::get( 'equipamentos', (int) $os['equipamento_id'] );
		if ( $os['cliente_id'] ) {
			self::client_block( $os['cliente_id'] );
		}
		$tipos = array( 'preventiva' => 'Preventiva', 'corretiva' => 'Corretiva', 'revisao' => 'Revisão de retorno', 'externa' => 'Serviço para cliente' );
		echo '<h2>Equipamento</h2><div class="grid"><div><strong>' . esc_html( $e ? $e['nome'] : '—' ) . '</strong></div><div>' . esc_html( $e ? trim( $e['codigo'] . ' · ' . $e['marca'] . ' ' . $e['modelo'] . ' · série ' . $e['numero_serie'], ' ·' ) : '' ) . '</div>';
		echo '<div>Tipo: ' . esc_html( $tipos[ $os['tipo'] ] ?? $os['tipo'] ) . ' · Situação: ' . esc_html( dl_statuses( 'os' )[ $os['status'] ] ?? $os['status'] ) . '</div><div>Abertura ' . esc_html( dl_date( $os['data_abertura'] ) ) . ' · Conclusão ' . esc_html( dl_date( $os['data_conclusao'] ) ) . '</div>';
		echo '<div>Técnico: ' . esc_html( $os['tecnico'] ) . '</div><div>Horímetro: ' . esc_html( null !== $os['horimetro'] ? dl_num( $os['horimetro'], 1 ) : '' ) . '</div></div>';
		echo '<h2>Defeito relatado</h2><div class="box">' . esc_html( $os['defeito'] ) . '</div>';
		echo '<h2>Diagnóstico</h2><div class="box">' . esc_html( $os['diagnostico'] ) . '</div>';
		echo '<h2>Serviço executado</h2><div class="box">' . esc_html( $os['solucao'] ) . '</div>';
		self::product_items( 'os', $os['id'], array( 'Peças e serviços' => $os['subtotal'], 'Mão de obra' => $os['mao_obra'] ), $os['total'] );
		if ( $os['obs'] ) {
			echo '<h2>Observações</h2><div class="box">' . esc_html( $os['obs'] ) . '</div>';
		}
		echo '<div class="sign"><div>Técnico responsável</div><div>Cliente / aprovação</div></div>';
	}

	private static function render_venda( $v ) {
		self::client_block( $v['cliente_id'] );
		self::product_items( 'venda', $v['id'], array( 'Frete' => $v['valor_frete'], 'Desconto' => -1 * (float) $v['desconto'] ), $v['total'] );
		$pay = dl_payment_methods();
		echo '<p>Pagamento: ' . esc_html( ( $pay[ $v['forma_pagamento'] ] ?? '' ) . ( $v['parcelas'] > 1 ? ' em ' . $v['parcelas'] . 'x' : '' ) ) . '</p>';
		if ( $v['obs'] ) {
			echo '<div class="box">' . esc_html( $v['obs'] ) . '</div>';
		}
	}

	private static function product_items( $doc, $id, $extra, $total ) {
		$items = DL_Items::get( $doc, $id );
		$disc  = array_sum( array_map( function ( $it ) { return (float) $it['desconto']; }, $items ) ) > 0;
		$cols  = $disc ? 4 : 3;
		echo '<h2>Itens</h2><table><thead><tr><th>Descrição</th><th class="n">Qtd</th><th class="n">Valor unit.</th>' . ( $disc ? '<th class="n">Desconto</th>' : '' ) . '<th class="n">Total</th></tr></thead><tbody>';
		foreach ( $items as $it ) {
			echo '<tr><td>' . esc_html( $it['descricao'] ) . '</td><td class="n">' . esc_html( dl_num( $it['qtd'], (float) $it['qtd'] === floor( (float) $it['qtd'] ) ? 0 : 2 ) ) . '</td><td class="n">' . esc_html( dl_money( $it['valor_unit'] ) ) . '</td>';
			if ( $disc ) {
				echo '<td class="n">' . ( (float) $it['desconto'] > 0 ? '− ' . esc_html( dl_money( $it['desconto'] ) ) : '' ) . '</td>';
			}
			echo '<td class="n">' . esc_html( dl_money( $it['total'] ) ) . '</td></tr>';
		}
		echo '</tbody><tfoot>';
		foreach ( $extra as $label => $value ) {
			if ( 0.0 !== (float) $value ) {
				echo '<tr><td colspan="' . (int) $cols . '" class="n">' . esc_html( $label ) . '</td><td class="n">' . esc_html( dl_money( $value ) ) . '</td></tr>';
			}
		}
		echo '<tr class="tot"><td colspan="' . (int) $cols . '" class="n">Total</td><td class="n big">' . esc_html( dl_money( $total ) ) . '</td></tr></tfoot></table>';
	}

	private static function render_recibo( $f ) {
		if ( 'pago' !== $f['status'] ) {
			echo '<p><strong>Este lançamento ainda não foi pago.</strong></p>';
			return;
		}
		$cli   = DL_DB::get( 'clientes', (int) $f['cliente_id'] );
		$total = (float) $f['valor_pago'] + (float) $f['juros'] + (float) $f['multa'] - (float) $f['desconto'];
		$pay   = dl_payment_methods();
		echo '<p class="big" style="margin-top:30px">Recebemos de <strong>' . esc_html( $cli ? $cli['nome'] : '________' ) . '</strong>' . ( $cli && $cli['documento'] ? ', ' . esc_html( dl_format_document( $cli['documento'] ) ) : '' ) . ', a importância de <strong>' . esc_html( dl_money( $total ) ) . '</strong>, referente a ' . esc_html( $f['descricao'] ) . ', paga em ' . esc_html( dl_date( $f['data_pagamento'] ) ) . ( $f['forma_pagamento'] ? ' via ' . esc_html( $pay[ $f['forma_pagamento'] ] ?? $f['forma_pagamento'] ) : '' ) . '.</p>';
		if ( (float) $f['juros'] + (float) $f['multa'] + (float) $f['desconto'] > 0 ) {
			echo '<p>Principal ' . esc_html( dl_money( $f['valor_pago'] ) ) . ' · multa ' . esc_html( dl_money( $f['multa'] ) ) . ' · juros ' . esc_html( dl_money( $f['juros'] ) ) . ' · desconto ' . esc_html( dl_money( $f['desconto'] ) ) . '</p>';
		}
		echo '<p>Damos plena quitação do valor acima.</p>';
		echo '<p>' . esc_html( ( dl_opt( 'empresa_cidade' ) ?: '________' ) . ', ' . date_i18n( 'j \d\e F \d\e Y', strtotime( $f['data_pagamento'] ) ) ) . '.</p>';
		echo '<div class="sign"><div>' . esc_html( dl_opt( 'empresa_nome' ) ) . '</div></div>';
	}
}
