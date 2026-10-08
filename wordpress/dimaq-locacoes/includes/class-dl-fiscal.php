<?php
/**
 * Notas fiscais: monta os dados da NF-e (venda) ou NFS-e (serviço) e envia a um emissor externo.
 *
 * O formato exato de cada emissor (Focus NFe, PlugNotas, eNotas, NFE.io...) muda; o payload
 * padrão abaixo é neutro e pode ser adaptado pelo filtro `dl_fiscal_payload`, e a resposta
 * pelo filtro `dl_fiscal_parse_response`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Fiscal {

	public static function init() {
		add_action( 'admin_post_dl_fiscal', array( __CLASS__, 'handle' ) );
		add_filter( 'dl_row_actions_notas', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	public static function row_actions( $actions, $row ) {
		if ( $row['pdf_url'] ) {
			$actions['pdf'] = '<a target="_blank" href="' . esc_url( $row['pdf_url'] ) . '">PDF</a>';
		}
		return $actions;
	}

	/** Tipo de nota adequado para cada origem. */
	public static function kind_for( $origin ) {
		return 'venda' === $origin ? 'nfe' : 'nfse';
	}

	public static function render_linked( $origin, $origin_id, $row ) {
		if ( ! current_user_can( 'dl_financeiro' ) ) {
			return;
		}
		global $wpdb;
		$notes = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'notas' ) . ' WHERE origem = %s AND origem_id = %d ORDER BY id DESC', $origin, $origin_id ), ARRAY_A );
		$kind  = self::kind_for( $origin );
		echo '<div class="dl-card"><h3>Nota fiscal</h3>';
		foreach ( $notes as $n ) {
			echo '<p>' . esc_html( strtoupper( $n['tipo'] ) . ' ' . ( $n['numero'] ? 'nº ' . $n['numero'] : $n['referencia'] ) ) . ' ' . dl_badge( 'nota', $n['status'] ) . ' <a href="' . esc_url( dl_admin_url( 'dl-notas', array( 'action' => 'edit', 'id' => $n['id'] ) ) ) . '">abrir</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			if ( $n['pdf_url'] ) {
				echo ' · <a target="_blank" href="' . esc_url( $n['pdf_url'] ) . '">PDF</a>';
			}
			echo '</p>';
		}
		if ( 'contrato' === $origin ) {
			echo '<p class="description">Locação de bem móvel não é serviço para fins de ISS (Súmula Vinculante 31 do STF): use a <a target="_blank" href="' . esc_url( DL_Documents::url( 'fatura', $origin_id ) ) . '">fatura de locação</a>. Emita NFS-e só para frete, montagem ou operador, se sua prefeitura exigir.</p>';
		}
		$value = (float) ( $row['total'] ?? 0 );
		if ( 'contrato' === $origin ) {
			$value = (float) $row['valor_frete'];
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="dl-inline-form">';
		wp_nonce_field( 'dl_fiscal_' . $origin . '_' . $origin_id );
		echo '<input type="hidden" name="action" value="dl_fiscal"><input type="hidden" name="origem" value="' . esc_attr( $origin ) . '"><input type="hidden" name="origem_id" value="' . (int) $origin_id . '">';
		echo '<label>Valor da nota<br><input type="number" step="0.01" min="0.01" name="valor" value="' . esc_attr( $value ) . '" required></label>';
		echo '<label>Discriminação<br><input type="text" name="discriminacao" value="' . esc_attr( self::default_description( $origin, $row ) ) . '"></label>';
		echo '<button class="button">' . ( 'nfe' === $kind ? 'Emitir NF-e' : 'Emitir NFS-e' ) . '</button></form>';
		if ( ! dl_opt( 'fiscal_ativo' ) ) {
			echo '<p class="description">Integração desligada: o registro fica pendente para você emitir no portal e anotar número e chave.</p>';
		}
		echo '</div>';
	}

	private static function default_description( $origin, $row ) {
		if ( 'os' === $origin ) {
			return 'Serviço de manutenção conforme OS ' . $row['numero'];
		}
		if ( 'contrato' === $origin ) {
			return 'Frete / transporte de equipamentos — contrato ' . $row['numero'];
		}
		return 'Venda ' . $row['numero'];
	}

	public static function handle() {
		dl_require_cap( 'dl_financeiro' );
		$origin    = sanitize_key( $_POST['origem'] ?? '' );
		$origin_id = absint( $_POST['origem_id'] ?? 0 );
		check_admin_referer( 'dl_fiscal_' . $origin . '_' . $origin_id );
		$map = array( 'contrato' => array( 'contratos', 'dl-contratos' ), 'os' => array( 'ordens_servico', 'dl-os' ), 'venda' => array( 'vendas', 'dl-vendas' ) );
		if ( ! isset( $map[ $origin ] ) ) {
			wp_die( 'Origem inválida.' );
		}
		$row = DL_DB::get( $map[ $origin ][0], $origin_id );
		if ( ! $row ) {
			wp_die( 'Documento não encontrado.' );
		}
		$note_id = self::emit( $origin, $row, round( dl_decimal( wp_unslash( $_POST['valor'] ?? 0 ) ), 2 ), sanitize_text_field( wp_unslash( $_POST['discriminacao'] ?? '' ) ) );
		$note    = DL_DB::get( 'notas', $note_id );
		dl_redirect( dl_admin_url( $map[ $origin ][1], array( 'action' => 'edit', 'id' => $origin_id ) ), 'Nota ' . strtoupper( $note['tipo'] ) . ': ' . ( dl_statuses( 'nota' )[ $note['status'] ] ?? $note['status'] ) . ( $note['mensagem'] ? ' — ' . esc_html( $note['mensagem'] ) : '' ), 'rejeitada' === $note['status'] ? 'error' : 'success' );
	}

	public static function build_payload( $kind, $origin, $row, $value, $description ) {
		$cli   = DL_DB::get( 'clientes', (int) $row['cliente_id'] );
		$items = array();
		if ( 'nfe' === $kind ) {
			foreach ( DL_Items::get( 'venda', $row['id'] ) as $it ) {
				$p       = DL_DB::get( 'produtos', (int) $it['ref_id'] );
				$items[] = array(
					'codigo'     => $p ? $p['codigo'] : '',
					'descricao'  => $it['descricao'],
					'ncm'        => $p ? $p['ncm'] : '',
					'unidade'    => $p ? $p['unidade'] : 'UN',
					'quantidade' => (float) $it['qtd'],
					'valor_unitario' => (float) $it['valor_unit'],
					'valor_total'    => (float) $it['total'],
				);
			}
		}
		$payload = array(
			'ambiente'  => dl_opt( 'fiscal_ambiente', 'homologacao' ),
			'tipo'      => $kind,
			'data_emissao' => current_time( 'c' ),
			'prestador' => array(
				'cnpj'               => dl_digits( dl_opt( 'empresa_cnpj' ) ),
				'inscricao_municipal' => dl_opt( 'empresa_im' ),
				'inscricao_estadual' => dl_opt( 'empresa_ie' ),
				'razao_social'       => dl_opt( 'empresa_nome' ),
			),
			'tomador'   => $cli ? array(
				'cpf_cnpj'     => $cli['documento'],
				'razao_social' => $cli['nome'],
				'email'        => $cli['email'],
				'telefone'     => dl_digits( $cli['telefone'] ),
				'endereco'     => array(
					'logradouro'  => $cli['logradouro'],
					'numero'      => $cli['numero'],
					'complemento' => $cli['complemento'],
					'bairro'      => $cli['bairro'],
					'cidade'      => $cli['cidade'],
					'uf'          => $cli['uf'],
					'cep'         => $cli['cep'],
				),
			) : array(),
			'valor'     => $value,
		);
		if ( 'nfse' === $kind ) {
			$payload['servico'] = array(
				'discriminacao'    => $description,
				'codigo_servico'   => dl_opt( 'fiscal_codigo_servico' ),
				'aliquota_iss'     => (float) dl_opt( 'fiscal_aliquota_iss' ),
				'valor_servicos'   => $value,
			);
		} else {
			$payload['itens']           = $items;
			$payload['natureza_operacao'] = 'Venda de mercadoria';
			$payload['informacoes_adicionais'] = $description;
		}
		return apply_filters( 'dl_fiscal_payload', $payload, $kind, $origin, $row );
	}

	/** Cria o registro da nota e, se a integração estiver ativa, envia ao emissor. */
	public static function emit( $origin, $row, $value, $description ) {
		$kind = self::kind_for( $origin );
		$ref  = $origin . '-' . $row['id'] . '-' . time();
		$payload = self::build_payload( $kind, $origin, $row, $value, $description );
		$note_id = DL_DB::insert(
			'notas',
			array(
				'tipo'       => $kind,
				'origem'     => $origin,
				'origem_id'  => $row['id'],
				'cliente_id' => (int) $row['cliente_id'],
				'referencia' => $ref,
				'valor'      => $value,
				'status'     => 'pendente',
				'payload'    => wp_json_encode( $payload ),
			)
		);
		$url = dl_opt( 'nfe' === $kind ? 'fiscal_url_nfe' : 'fiscal_url_nfse' );
		if ( ! dl_opt( 'fiscal_ativo' ) || ! $url ) {
			DL_DB::update( 'notas', $note_id, array( 'mensagem' => 'Integração desligada — emita no portal e informe número e chave.' ) );
			return $note_id;
		}
		$response = wp_remote_post(
			add_query_arg( 'ref', rawurlencode( $ref ), $url ),
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( dl_opt( 'fiscal_token' ) . ':' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
					'X-API-KEY'     => dl_opt( 'fiscal_token' ),
				),
				'body'    => wp_json_encode( $payload ),
			)
		);
		if ( is_wp_error( $response ) ) {
			DL_DB::update( 'notas', $note_id, array( 'status' => 'rejeitada', 'mensagem' => 'Falha de comunicação: ' . $response->get_error_message() ) );
			return $note_id;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );
		$json = is_array( $json ) ? $json : array();
		$parsed = array(
			'status'   => $code >= 200 && $code < 300 ? ( ( $json['status'] ?? '' ) === 'autorizado' ? 'autorizada' : 'processando' ) : 'rejeitada',
			'numero'   => (string) ( $json['numero'] ?? '' ),
			'serie'    => (string) ( $json['serie'] ?? '' ),
			'chave'    => (string) ( $json['chave_nfe'] ?? ( $json['codigo_verificacao'] ?? ( $json['chave'] ?? '' ) ) ),
			'pdf_url'  => (string) ( $json['caminho_danfe'] ?? ( $json['url'] ?? ( $json['pdf'] ?? '' ) ) ),
			'xml_url'  => (string) ( $json['caminho_xml_nota_fiscal'] ?? ( $json['xml'] ?? '' ) ),
			'mensagem' => (string) ( $json['mensagem'] ?? ( $json['message'] ?? ( $code >= 300 ? 'HTTP ' . $code : '' ) ) ),
		);
		$parsed = apply_filters( 'dl_fiscal_parse_response', $parsed, $json, $code, $kind );
		$parsed['resposta'] = mb_substr( $body, 0, 20000 );
		DL_DB::update( 'notas', $note_id, $parsed );
		dl_log( 'notas', $note_id, 'Enviada ao emissor', 'HTTP ' . $code );
		return $note_id;
	}
}
