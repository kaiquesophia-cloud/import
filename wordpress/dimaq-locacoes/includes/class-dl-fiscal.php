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

	/** Tipo de nota adequado para cada origem. */
	public static function kind_for( $origin ) {
		return 'venda' === $origin ? 'nfe' : 'nfse';
	}

	/** Notas ligadas a um documento. */
	public static function linked( $origin, $origin_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT id, tipo, numero, referencia, valor, status, pdf_url, mensagem FROM ' . dl_table( 'notas' ) . ' WHERE origem = %s AND origem_id = %d ORDER BY id DESC', $origin, $origin_id ), ARRAY_A );
	}

	/** Valor e discriminação sugeridos para a nota de um documento. */
	public static function suggestion( $origin, $row ) {
		return array(
			'tipo'          => self::kind_for( $origin ),
			'valor'         => 'contrato' === $origin ? (float) $row['valor_frete'] : (float) $row['total'],
			'discriminacao' => self::default_description( $origin, $row ),
			'ativo'         => (bool) dl_opt( 'fiscal_ativo' ),
		);
	}

	public static function default_description( $origin, $row ) {
		if ( 'os' === $origin ) {
			return 'Serviço de manutenção conforme OS ' . $row['numero'];
		}
		if ( 'contrato' === $origin ) {
			return 'Frete / transporte de equipamentos — contrato ' . $row['numero'];
		}
		return 'Venda ' . $row['numero'];
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
