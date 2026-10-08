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
			'empresa_nome'        => 'Dimaq Locações',
			'empresa_cnpj'        => '',
			'empresa_ie'          => '',
			'empresa_im'          => '',
			'empresa_endereco'    => '',
			'empresa_cidade'      => '',
			'empresa_uf'          => '',
			'empresa_cep'         => '',
			'empresa_telefone'    => '',
			'empresa_whatsapp'    => '',
			'empresa_email'       => get_option( 'admin_email' ),
			'empresa_site'        => home_url(),
			'logo_url'            => '',
			'cor_primaria'        => '#f5a400',
			'email_notificacao'   => get_option( 'admin_email' ),
			'prefixo_contrato'    => 'LOC',
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

	public static function default_clauses() {
		return "1. OBJETO — A LOCADORA cede ao LOCATÁRIO, a título de locação, os equipamentos relacionados neste contrato, em perfeito estado de funcionamento, conforme checklist de saída.\n\n"
			. "2. PRAZO — A locação vigora de {data_inicio} a {data_devolucao}. A permanência do equipamento após o prazo implica cobrança de diárias adicionais, proporcionais ao período excedente.\n\n"
			. "3. VALOR — O LOCATÁRIO pagará o valor total de {total}, na forma: {condicao_pagamento}. Atrasos de pagamento sofrem multa de {multa_vencimento}% e juros de {juros_mes}% ao mês.\n\n"
			. "4. USO — O equipamento será utilizado exclusivamente no endereço {local_obra}, por pessoa habilitada, sendo vedada a sublocação ou o empréstimo a terceiros.\n\n"
			. "5. CONSERVAÇÃO — O LOCATÁRIO responde por danos, perda, furto ou roubo do equipamento, ressarcindo o valor de reparo ou de reposição constante da relação de itens.\n\n"
			. "6. MANUTENÇÃO — Defeitos decorrentes do uso normal são de responsabilidade da LOCADORA, que deve ser avisada imediatamente. Não é permitida intervenção técnica pelo LOCATÁRIO.\n\n"
			. "7. DEVOLUÇÃO — O equipamento deve ser devolvido limpo e nas mesmas condições da retirada, sendo conferido no checklist de retorno.\n\n"
			. "8. FORO — Fica eleito o foro da comarca de {cidade_empresa} para dirimir dúvidas oriundas deste contrato.";
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
				'prefixo_contrato'     => array( 'Prefixo do contrato', 'text' ),
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
				'clausulas_contrato' => array( 'Cláusulas — variáveis: {cliente} {documento_cliente} {numero} {data_inicio} {data_devolucao} {total} {condicao_pagamento} {local_obra} {multa_vencimento} {juros_mes} {empresa} {cidade_empresa}', 'longtext' ),
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
			if ( in_array( $key, array( 'clausulas_contrato', 'dados_bancarios' ), true ) ) {
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
