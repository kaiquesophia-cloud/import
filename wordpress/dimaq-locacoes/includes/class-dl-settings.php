<?php
/**
 * Configurações: dados da empresa, regras de cobrança, contrato, site e nota fiscal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Settings {

	public static function defaults() {
		return array(
			'empresa_nome'        => 'DIMAQ LOCACOES DE MAQUINAS E SERVICOS LTDA',
			'empresa_cnpj'        => '09.436.484/0001-90',
			'empresa_ie'          => '148.181.878.115',
			'empresa_im'          => '',
			'empresa_endereco'    => 'Rua Antonio Caserta, 31',
			'empresa_bairro'      => 'Jardim Apurá',
			'empresa_cidade'      => 'São Paulo',
			'empresa_uf'          => 'SP',
			'empresa_cep'         => '04470-060',
			'empresa_telefone'    => '(11) 5560-3539',
			'empresa_whatsapp'    => '(11) 94752-4204',
			'empresa_email'       => get_option( 'admin_email' ),
			'empresa_site'        => home_url(),
			'logo_url'            => '',
			'cor_primaria'        => '#f5a400',
			'email_notificacao'   => get_option( 'admin_email' ),
			'prefixo_contrato'    => 'LOC',
			'numeracao_contrato'  => 'sequencial',
			'proximo_contrato'    => 1,
			'contagem_inclusiva'  => 1,
			'prefixo_os'          => 'OS',
			'prefixo_venda'       => 'VD',
			'locacao_minima_dias' => 1,
			'validade_orcamento'  => 7,
			'multa_atraso_pct'    => 0,
			'multa_vencimento_pct' => 2,
			'juros_mes_pct'       => 1,
			'mostrar_precos'      => 1,
			'pagina_orcamento'    => 0,
			'pagina_catalogo'     => 0,
			'email_cliente'       => 1,
			'lembrete_dias'       => 1,
			'dados_bancarios'     => '',
			'clausulas_partes'    => self::default_clauses_parties(),
			'clausulas_objeto'    => self::default_clauses_object(),
			'clausulas_contrato'  => self::default_clauses(),
			'fiscal_ativo'        => 0,
			'fiscal_ambiente'     => 'homologacao',
			'fiscal_url_nfse'     => '',
			'fiscal_url_nfe'      => '',
			'fiscal_token'        => '',
			'fiscal_codigo_servico' => '',
			'fiscal_aliquota_iss' => '',
		);
	}

	/** Cláusulas depois da identificação das partes (posição "CLÁUSULA 1 e 2" do modelo). */
	public static function default_clauses_parties() {
		return "CLÁUSULA 1 — OBJETO: A LOCADORA cede ao LOCATÁRIO, a título de locação, os equipamentos relacionados neste contrato, em perfeito estado de funcionamento, conforme checklist de saída.\n"
			. "CLÁUSULA 2 — USO: Os equipamentos serão utilizados exclusivamente no endereço de entrega informado, por pessoa habilitada, sendo vedada a sublocação ou o empréstimo a terceiros.";
	}

	/** Cláusula antes da relação de equipamentos (posição "CLÁUSULA 10" do modelo). */
	public static function default_clauses_object() {
		return "CLÁUSULA 10 — O LOCATÁRIO declara receber os equipamentos abaixo, responsabilizando-se por danos, perda, furto ou roubo, e ressarcindo à LOCADORA o valor de reposição indicado em caso de não devolução.";
	}

	/** Cláusulas finais, depois dos acessórios (posição "CLÁUSULA 20" do modelo). */
	public static function default_clauses() {
		return "CLÁUSULA 20 — A permanência dos equipamentos após o prazo implica cobrança proporcional ao período excedente. Atrasos de pagamento sofrem multa de {multa_vencimento}% e juros de {juros_mes}% ao mês. Os equipamentos devem ser devolvidos limpos e nas mesmas condições da retirada. Fica eleito o foro da comarca de {cidade_empresa} para dirimir dúvidas oriundas deste contrato.";
	}

	/** Campos da tela de configurações, por seção. */
	public static function fields() {
		return array(
			'Empresa'          => array(
				'empresa_nome'     => array( 'Razão social / nome', 'text' ),
				'empresa_cnpj'     => array( 'CNPJ', 'text' ),
				'empresa_ie'       => array( 'Inscrição estadual', 'text' ),
				'empresa_im'       => array( 'Inscrição municipal', 'text' ),
				'empresa_endereco' => array( 'Endereço', 'text' ),
				'empresa_bairro'   => array( 'Bairro', 'text' ),
				'empresa_cidade'   => array( 'Cidade', 'text' ),
				'empresa_uf'       => array( 'UF', 'text' ),
				'empresa_cep'      => array( 'CEP', 'text' ),
				'empresa_telefone' => array( 'Telefone', 'text' ),
				'empresa_whatsapp' => array( 'WhatsApp (com DDD)', 'text' ),
				'empresa_email'    => array( 'E-mail', 'email' ),
				'empresa_site'     => array( 'Site', 'url' ),
				'logo_url'         => array( 'Logotipo nos documentos (URL; vazio = logo Dimaq)', 'url' ),
				'cor_primaria'     => array( 'Cor de destaque', 'color' ),
				'dados_bancarios'  => array( 'Dados bancários / chave PIX', 'textarea' ),
			),
			'Locação e cobrança' => array(
				'numeracao_contrato'   => array( 'Numeração do contrato', 'select', array( 'sequencial' => 'Sequencial (ex.: 6.576 / 1)', 'ano' => 'Prefixo e ano (ex.: LOC2026-00001)' ) ),
				'proximo_contrato'     => array( 'Próximo número de contrato (sequencial)', 'number' ),
				'prefixo_contrato'     => array( 'Prefixo do contrato (numeração por ano)', 'text' ),
				'contagem_inclusiva'   => array( 'Contar o dia da retirada e o da devolução (08/10 a 06/11 = 30 dias)', 'checkbox' ),
				'prefixo_os'           => array( 'Prefixo da OS', 'text' ),
				'prefixo_venda'        => array( 'Prefixo da venda', 'text' ),
				'locacao_minima_dias'  => array( 'Locação mínima (dias)', 'number' ),
				'validade_orcamento'   => array( 'Validade do orçamento (dias)', 'number' ),
				'multa_atraso_pct'     => array( 'Acréscimo sobre diária em atraso (%)', 'number' ),
				'multa_vencimento_pct' => array( 'Multa por pagamento em atraso (%)', 'number' ),
				'juros_mes_pct'        => array( 'Juros por pagamento em atraso (% ao mês)', 'number' ),
				'email_notificacao'    => array( 'E-mail que recebe avisos', 'email' ),
				'email_cliente'        => array( 'Enviar e-mails automáticos ao cliente', 'checkbox' ),
				'lembrete_dias'        => array( 'Avisar devolução com quantos dias de antecedência', 'number' ),
			),
			'Contrato'         => array(
				'clausulas_partes'   => array( 'Cláusulas logo depois das partes (Cláusulas 1 e 2)', 'longtext' ),
				'clausulas_objeto'   => array( 'Cláusula antes dos equipamentos (Cláusula 10)', 'longtext' ),
				'clausulas_contrato' => array( 'Cláusulas finais, depois dos acessórios (Cláusula 20). Variáveis aceitas em todas: {cliente} {documento_cliente} {numero} {data_inicio} {data_devolucao} {dias} {total} {condicao_pagamento} {local_obra} {multa_vencimento} {juros_mes} {empresa} {cidade_empresa}', 'longtext' ),
			),
			'Site'             => array(
				'mostrar_precos'   => array( 'Mostrar preços no catálogo', 'checkbox' ),
				'pagina_catalogo'  => array( 'Página do catálogo [dimaq_catalogo]', 'page' ),
				'pagina_orcamento' => array( 'Página de orçamento [dimaq_orcamento]', 'page' ),
			),
			'Nota fiscal'      => array(
				'fiscal_ativo'          => array( 'Integração com emissor ativa', 'checkbox' ),
				'fiscal_ambiente'       => array( 'Ambiente', 'select', array( 'homologacao' => 'Homologação (teste)', 'producao' => 'Produção' ) ),
				'fiscal_url_nfse'       => array( 'URL de emissão de NFS-e', 'url' ),
				'fiscal_url_nfe'        => array( 'URL de emissão de NF-e', 'url' ),
				'fiscal_token'          => array( 'Token do emissor', 'password' ),
				'fiscal_codigo_servico' => array( 'Código do serviço (LC 116)', 'text' ),
				'fiscal_aliquota_iss'   => array( 'Alíquota de ISS (%)', 'number' ),
			),
		);
	}

	/** Valores atuais para a tela (o token nunca sai do servidor). */
	public static function values() {
		$o = wp_parse_args( get_option( 'dl_settings', array() ), self::defaults() );
		$o['fiscal_token'] = '';
		$o['fiscal_token_definido'] = (bool) dl_opt( 'fiscal_token' );
		return $o;
	}

	/** Grava as configurações recebidas. */
	public static function save_values( array $input ) {
		$current = wp_parse_args( get_option( 'dl_settings', array() ), self::defaults() );
		$out     = $current;
		foreach ( self::defaults() as $key => $default ) {
			if ( ! array_key_exists( $key, $input ) || is_array( $input[ $key ] ) ) {
				continue;
			}
			$value = $input[ $key ];
			if ( 'fiscal_token' === $key && '' === $value ) {
				continue; // mantém o token já salvo
			}
			if ( in_array( $key, array( 'clausulas_contrato', 'clausulas_partes', 'clausulas_objeto', 'dados_bancarios' ), true ) ) {
				$out[ $key ] = sanitize_textarea_field( $value );
			} elseif ( in_array( $key, array( 'empresa_email', 'email_notificacao' ), true ) ) {
				$out[ $key ] = sanitize_email( $value );
			} elseif ( in_array( $key, array( 'logo_url', 'empresa_site', 'fiscal_url_nfse', 'fiscal_url_nfe' ), true ) ) {
				$out[ $key ] = esc_url_raw( $value );
			} elseif ( 'cor_primaria' === $key ) {
				$out[ $key ] = sanitize_hex_color( $value ) ? sanitize_hex_color( $value ) : $default;
			} elseif ( is_int( $default ) ) {
				$out[ $key ] = is_numeric( $value ) ? $value + 0 : ( ( true === $value || 'true' === $value ) ? 1 : 0 );
			} else {
				$out[ $key ] = sanitize_text_field( $value );
			}
		}
		update_option( 'dl_settings', $out );
		return true;
	}
}
