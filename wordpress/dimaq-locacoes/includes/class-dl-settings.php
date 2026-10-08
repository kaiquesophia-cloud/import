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
			'cor_primaria'        => '#f2a900',
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

	public static function render() {
		dl_require_cap( 'dl_config' );
		$o = wp_parse_args( get_option( 'dl_settings', array() ), self::defaults() );
		$pages = get_pages();
		?>
		<div class="wrap dl-wrap">
			<h1>Configurações</h1>
			<?php dl_render_notice(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'dl_settings' ); ?>
				<input type="hidden" name="action" value="dl_save_settings">

				<h2>Dados da empresa</h2>
				<p class="description">Aparecem no cabeçalho de contratos, orçamentos, recibos e faturas.</p>
				<table class="form-table">
					<?php
					self::row( 'empresa_nome', 'Razão social / nome', $o );
					self::row( 'empresa_cnpj', 'CNPJ', $o );
					self::row( 'empresa_ie', 'Inscrição estadual', $o );
					self::row( 'empresa_im', 'Inscrição municipal', $o );
					self::row( 'empresa_endereco', 'Endereço', $o );
					self::row( 'empresa_cidade', 'Cidade', $o );
					self::row( 'empresa_uf', 'UF', $o );
					self::row( 'empresa_cep', 'CEP', $o );
					self::row( 'empresa_telefone', 'Telefone', $o );
					self::row( 'empresa_whatsapp', 'WhatsApp (com DDD)', $o );
					self::row( 'empresa_email', 'E-mail', $o, 'email' );
					self::row( 'empresa_site', 'Site', $o, 'url' );
					self::row( 'logo_url', 'URL do logotipo', $o, 'url' );
					self::row( 'cor_primaria', 'Cor principal', $o, 'color' );
					self::row( 'dados_bancarios', 'Dados bancários / chave PIX', $o, 'textarea' );
					?>
				</table>

				<h2>Locação e cobrança</h2>
				<table class="form-table">
					<?php
					self::row( 'prefixo_contrato', 'Prefixo do contrato', $o );
					self::row( 'prefixo_os', 'Prefixo da ordem de serviço', $o );
					self::row( 'prefixo_venda', 'Prefixo da venda', $o );
					self::row( 'locacao_minima_dias', 'Locação mínima (dias)', $o, 'number' );
					self::row( 'validade_orcamento', 'Validade do orçamento (dias)', $o, 'number' );
					self::row( 'multa_atraso_pct', 'Acréscimo sobre diária em atraso de devolução (%)', $o, 'number' );
					self::row( 'multa_vencimento_pct', 'Multa por pagamento em atraso (%)', $o, 'number' );
					self::row( 'juros_mes_pct', 'Juros por pagamento em atraso (% ao mês)', $o, 'number' );
					self::row( 'email_notificacao', 'E-mail que recebe avisos', $o, 'email' );
					self::row( 'email_cliente', 'Enviar e-mails automáticos ao cliente', $o, 'checkbox' );
					self::row( 'lembrete_dias', 'Avisar devolução com quantos dias de antecedência', $o, 'number' );
					?>
				</table>

				<h2>Contrato</h2>
				<p class="description">Variáveis: {cliente} {documento_cliente} {numero} {data_inicio} {data_devolucao} {total} {condicao_pagamento} {local_obra} {multa_vencimento} {juros_mes} {empresa} {cidade_empresa}</p>
				<textarea name="dl[clausulas_contrato]" rows="18" class="large-text code"><?php echo esc_textarea( $o['clausulas_contrato'] ); ?></textarea>

				<h2>Site</h2>
				<table class="form-table">
					<?php self::row( 'mostrar_precos', 'Mostrar preços no catálogo', $o, 'checkbox' ); ?>
					<tr><th>Página do catálogo</th><td><?php self::page_select( 'pagina_catalogo', $o, $pages ); ?><p class="description">Página com o shortcode <code>[dimaq_catalogo]</code>.</p></td></tr>
					<tr><th>Página de orçamento</th><td><?php self::page_select( 'pagina_orcamento', $o, $pages ); ?><p class="description">Página com o shortcode <code>[dimaq_orcamento]</code>.</p></td></tr>
				</table>

				<h2>Nota fiscal (integração)</h2>
				<p class="description">O plugin monta os dados da nota e envia para um emissor (Focus NFe, PlugNotas, eNotas, NFE.io etc.). Locação pura de bem móvel não tem ISS (STF, Súmula Vinculante 31): nesses casos emita a <strong>fatura de locação</strong>. Use NFS-e para serviços (manutenção, frete, montagem) e NF-e para venda de produtos.</p>
				<table class="form-table">
					<?php
					self::row( 'fiscal_ativo', 'Integração ativa', $o, 'checkbox' );
					?>
					<tr><th>Ambiente</th><td><select name="dl[fiscal_ambiente]"><option value="homologacao" <?php selected( $o['fiscal_ambiente'], 'homologacao' ); ?>>Homologação (teste)</option><option value="producao" <?php selected( $o['fiscal_ambiente'], 'producao' ); ?>>Produção</option></select></td></tr>
					<?php
					self::row( 'fiscal_url_nfse', 'URL de emissão de NFS-e', $o, 'url' );
					self::row( 'fiscal_url_nfe', 'URL de emissão de NF-e', $o, 'url' );
					self::row( 'fiscal_token', 'Token do emissor', $o, 'password' );
					self::row( 'fiscal_codigo_servico', 'Código do serviço (LC 116)', $o );
					self::row( 'fiscal_aliquota_iss', 'Alíquota de ISS (%)', $o, 'number' );
					?>
				</table>

				<?php submit_button( 'Salvar configurações' ); ?>
			</form>
		</div>
		<?php
	}

	private static function row( $key, $label, $o, $type = 'text' ) {
		$name = 'dl[' . $key . ']';
		echo '<tr><th><label for="dl_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea id="dl_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( $o[ $key ] ) . '</textarea>';
		} elseif ( 'checkbox' === $type ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><label><input type="checkbox" id="dl_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (int) $o[ $key ], 1, false ) . '> Sim</label>';
		} elseif ( 'password' === $type ) {
			echo '<input type="password" autocomplete="new-password" id="dl_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="" placeholder="' . ( $o[ $key ] ? '•••••••• (já configurado — deixe em branco para manter)' : '' ) . '" class="regular-text">';
		} else {
			$step = 'number' === $type ? ' step="0.01"' : '';
			echo '<input type="' . esc_attr( $type ) . '"' . $step . ' id="dl_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $o[ $key ] ) . '" class="regular-text">'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</td></tr>';
	}

	private static function page_select( $key, $o, $pages ) {
		echo '<select name="dl[' . esc_attr( $key ) . ']"><option value="0">— selecione —</option>';
		foreach ( $pages as $p ) {
			echo '<option value="' . (int) $p->ID . '" ' . selected( (int) $o[ $key ], $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
		}
		echo '</select>';
	}

	public static function save() {
		dl_require_cap( 'dl_config' );
		check_admin_referer( 'dl_settings' );
		$current = wp_parse_args( get_option( 'dl_settings', array() ), self::defaults() );
		$input   = isset( $_POST['dl'] ) ? wp_unslash( (array) $_POST['dl'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out     = $current;
		foreach ( self::defaults() as $key => $default ) {
			if ( ! array_key_exists( $key, $input ) ) {
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
				$out[ $key ] = is_numeric( $value ) ? $value + 0 : 0;
			} else {
				$out[ $key ] = sanitize_text_field( $value );
			}
		}
		update_option( 'dl_settings', $out );
		dl_redirect( dl_admin_url( 'dl-config' ), 'Configurações salvas.' );
	}
}
