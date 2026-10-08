<?php
/**
 * Criação das tabelas, permissões e valores iniciais.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Install {

	public static function activate() {
		self::create_tables();
		self::create_roles();
		if ( false === get_option( 'dl_settings' ) ) {
			add_option( 'dl_settings', DL_Settings::defaults() );
		}
		update_option( 'dl_db_version', DL_DB_VERSION );
		DL_Cron::schedule();
		DL_App::rewrite();
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( 'dl_db_version' ) !== DL_DB_VERSION ) {
			self::activate();
		}
	}

	public static function create_roles() {
		$caps = array( 'dl_operar', 'dl_financeiro', 'dl_config' );
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
		if ( ! get_role( 'dl_operador' ) ) {
			add_role( 'dl_operador', 'Operador de locação', array( 'read' => true, 'upload_files' => true, 'dl_operar' => true ) );
		}
		if ( ! get_role( 'dl_gerente' ) ) {
			add_role( 'dl_gerente', 'Gerente da locadora', array( 'read' => true, 'upload_files' => true, 'dl_operar' => true, 'dl_financeiro' => true ) );
		}
		if ( ! get_role( 'dl_cliente' ) ) {
			add_role( 'dl_cliente', 'Cliente da locadora', array( 'read' => true ) );
		}
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'dl_';

		$sql = array();

		$sql[] = "CREATE TABLE {$p}clientes (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tipo varchar(2) NOT NULL DEFAULT 'PJ',
			nome varchar(190) NOT NULL,
			fantasia varchar(190) NOT NULL DEFAULT '',
			documento varchar(20) NOT NULL DEFAULT '',
			ie_rg varchar(30) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			telefone varchar(30) NOT NULL DEFAULT '',
			whatsapp varchar(30) NOT NULL DEFAULT '',
			contato varchar(120) NOT NULL DEFAULT '',
			cep varchar(10) NOT NULL DEFAULT '',
			logradouro varchar(190) NOT NULL DEFAULT '',
			numero varchar(20) NOT NULL DEFAULT '',
			complemento varchar(100) NOT NULL DEFAULT '',
			bairro varchar(100) NOT NULL DEFAULT '',
			cidade varchar(100) NOT NULL DEFAULT '',
			uf varchar(2) NOT NULL DEFAULT '',
			limite_credito decimal(12,2) NOT NULL DEFAULT 0,
			bloqueado tinyint(1) NOT NULL DEFAULT 0,
			motivo_bloqueio varchar(255) NOT NULL DEFAULT '',
			wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY documento (documento),
			KEY email (email),
			KEY wp_user_id (wp_user_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}fornecedores (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			nome varchar(190) NOT NULL,
			documento varchar(20) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			telefone varchar(30) NOT NULL DEFAULT '',
			contato varchar(120) NOT NULL DEFAULT '',
			cidade varchar(100) NOT NULL DEFAULT '',
			uf varchar(2) NOT NULL DEFAULT '',
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}categorias (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			nome varchar(120) NOT NULL,
			descricao text NULL,
			imagem_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ordem int(11) NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}equipamentos (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			codigo varchar(50) NOT NULL DEFAULT '',
			nome varchar(190) NOT NULL,
			categoria_id bigint(20) unsigned NOT NULL DEFAULT 0,
			marca varchar(100) NOT NULL DEFAULT '',
			modelo varchar(100) NOT NULL DEFAULT '',
			numero_serie varchar(100) NOT NULL DEFAULT '',
			ano varchar(4) NOT NULL DEFAULT '',
			controle varchar(12) NOT NULL DEFAULT 'unitario',
			qtd_total int(11) NOT NULL DEFAULT 1,
			valor_diaria decimal(12,2) NOT NULL DEFAULT 0,
			valor_semanal decimal(12,2) NOT NULL DEFAULT 0,
			valor_quinzenal decimal(12,2) NOT NULL DEFAULT 0,
			valor_mensal decimal(12,2) NOT NULL DEFAULT 0,
			valor_caucao decimal(12,2) NOT NULL DEFAULT 0,
			valor_reposicao decimal(12,2) NOT NULL DEFAULT 0,
			valor_aquisicao decimal(12,2) NOT NULL DEFAULT 0,
			data_aquisicao date NULL,
			horimetro decimal(12,1) NOT NULL DEFAULT 0,
			manutencao_cada_horas int(11) NOT NULL DEFAULT 0,
			ultima_manutencao_horas decimal(12,1) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'disponivel',
			foto_id bigint(20) unsigned NOT NULL DEFAULT 0,
			descricao text NULL,
			especificacoes text NULL,
			acessorio tinyint(1) NOT NULL DEFAULT 0,
			publicar_site tinyint(1) NOT NULL DEFAULT 1,
			destaque tinyint(1) NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY categoria_id (categoria_id),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$p}produtos (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			codigo varchar(50) NOT NULL DEFAULT '',
			nome varchar(190) NOT NULL,
			tipo varchar(12) NOT NULL DEFAULT 'produto',
			unidade varchar(10) NOT NULL DEFAULT 'UN',
			preco_custo decimal(12,2) NOT NULL DEFAULT 0,
			preco_venda decimal(12,2) NOT NULL DEFAULT 0,
			estoque_atual decimal(12,3) NOT NULL DEFAULT 0,
			estoque_minimo decimal(12,3) NOT NULL DEFAULT 0,
			fornecedor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ncm varchar(10) NOT NULL DEFAULT '',
			ativo tinyint(1) NOT NULL DEFAULT 1,
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}estoque_mov (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			produto_id bigint(20) unsigned NOT NULL,
			tipo varchar(10) NOT NULL,
			qtd decimal(12,3) NOT NULL,
			custo_unit decimal(12,2) NOT NULL DEFAULT 0,
			origem varchar(20) NOT NULL DEFAULT 'manual',
			origem_id bigint(20) unsigned NOT NULL DEFAULT 0,
			obs varchar(255) NOT NULL DEFAULT '',
			usuario_id bigint(20) unsigned NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY produto_id (produto_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}contratos (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			numero varchar(30) NOT NULL DEFAULT '',
			cliente_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'orcamento',
			data_inicio date NULL,
			data_prev_devolucao date NULL,
			data_encerramento date NULL,
			validade_orcamento date NULL,
			local_obra varchar(190) NOT NULL DEFAULT '',
			endereco_entrega varchar(255) NOT NULL DEFAULT '',
			responsavel_obra varchar(120) NOT NULL DEFAULT '',
			telefone_obra varchar(30) NOT NULL DEFAULT '',
			entrega varchar(12) NOT NULL DEFAULT 'retirada',
			valor_frete decimal(12,2) NOT NULL DEFAULT 0,
			desconto decimal(12,2) NOT NULL DEFAULT 0,
			caucao decimal(12,2) NOT NULL DEFAULT 0,
			caucao_status varchar(12) NOT NULL DEFAULT 'nao_cobrado',
			subtotal decimal(12,2) NOT NULL DEFAULT 0,
			adicionais decimal(12,2) NOT NULL DEFAULT 0,
			total decimal(12,2) NOT NULL DEFAULT 0,
			valor_faturado decimal(12,2) NOT NULL DEFAULT 0,
			forma_pagamento varchar(20) NOT NULL DEFAULT '',
			condicao_pagamento varchar(120) NOT NULL DEFAULT '',
			obs text NULL,
			obs_interna text NULL,
			origem varchar(10) NOT NULL DEFAULT 'admin',
			criado_por bigint(20) unsigned NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			atualizado_em datetime NULL,
			PRIMARY KEY  (id),
			KEY cliente_id (cliente_id),
			KEY status (status),
			KEY periodo (data_inicio,data_prev_devolucao)
		) $c;";

		$sql[] = "CREATE TABLE {$p}itens (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			doc_tipo varchar(10) NOT NULL,
			doc_id bigint(20) unsigned NOT NULL,
			ref_tipo varchar(12) NOT NULL,
			ref_id bigint(20) unsigned NOT NULL DEFAULT 0,
			descricao varchar(255) NOT NULL DEFAULT '',
			qtd decimal(12,3) NOT NULL DEFAULT 1,
			periodo_tipo varchar(12) NOT NULL DEFAULT '',
			periodos decimal(10,2) NOT NULL DEFAULT 1,
			valor_unit decimal(12,2) NOT NULL DEFAULT 0,
			desconto decimal(12,2) NOT NULL DEFAULT 0,
			total decimal(12,2) NOT NULL DEFAULT 0,
			qtd_devolvida decimal(12,3) NOT NULL DEFAULT 0,
			data_saida date NULL,
			data_retorno date NULL,
			horimetro_saida decimal(12,1) NULL,
			horimetro_retorno decimal(12,1) NULL,
			checklist_saida text NULL,
			checklist_retorno text NULL,
			avarias text NULL,
			valor_avaria decimal(12,2) NOT NULL DEFAULT 0,
			ordem int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY doc (doc_tipo,doc_id),
			KEY ref (ref_tipo,ref_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}ordens_servico (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			numero varchar(30) NOT NULL DEFAULT '',
			equipamento_id bigint(20) unsigned NOT NULL DEFAULT 0,
			cliente_id bigint(20) unsigned NOT NULL DEFAULT 0,
			contrato_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tipo varchar(12) NOT NULL DEFAULT 'corretiva',
			prioridade varchar(10) NOT NULL DEFAULT 'normal',
			status varchar(20) NOT NULL DEFAULT 'aberta',
			tecnico varchar(120) NOT NULL DEFAULT '',
			data_abertura date NULL,
			data_previsao date NULL,
			data_conclusao date NULL,
			horimetro decimal(12,1) NULL,
			defeito text NULL,
			diagnostico text NULL,
			solucao text NULL,
			mao_obra decimal(12,2) NOT NULL DEFAULT 0,
			subtotal decimal(12,2) NOT NULL DEFAULT 0,
			total decimal(12,2) NOT NULL DEFAULT 0,
			cobrar_cliente tinyint(1) NOT NULL DEFAULT 0,
			estoque_baixado tinyint(1) NOT NULL DEFAULT 0,
			faturado tinyint(1) NOT NULL DEFAULT 0,
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY equipamento_id (equipamento_id),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$p}vendas (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			numero varchar(30) NOT NULL DEFAULT '',
			cliente_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'orcamento',
			data_venda date NULL,
			valor_frete decimal(12,2) NOT NULL DEFAULT 0,
			desconto decimal(12,2) NOT NULL DEFAULT 0,
			subtotal decimal(12,2) NOT NULL DEFAULT 0,
			total decimal(12,2) NOT NULL DEFAULT 0,
			forma_pagamento varchar(20) NOT NULL DEFAULT '',
			parcelas int(11) NOT NULL DEFAULT 1,
			primeiro_vencimento date NULL,
			estoque_baixado tinyint(1) NOT NULL DEFAULT 0,
			faturado tinyint(1) NOT NULL DEFAULT 0,
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY cliente_id (cliente_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}financeiro (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tipo varchar(10) NOT NULL DEFAULT 'receber',
			descricao varchar(255) NOT NULL DEFAULT '',
			categoria varchar(60) NOT NULL DEFAULT '',
			cliente_id bigint(20) unsigned NOT NULL DEFAULT 0,
			fornecedor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			origem varchar(12) NOT NULL DEFAULT 'manual',
			origem_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parcela int(11) NOT NULL DEFAULT 1,
			total_parcelas int(11) NOT NULL DEFAULT 1,
			emissao date NULL,
			vencimento date NOT NULL,
			valor decimal(12,2) NOT NULL DEFAULT 0,
			juros decimal(12,2) NOT NULL DEFAULT 0,
			multa decimal(12,2) NOT NULL DEFAULT 0,
			desconto decimal(12,2) NOT NULL DEFAULT 0,
			valor_pago decimal(12,2) NOT NULL DEFAULT 0,
			data_pagamento date NULL,
			forma_pagamento varchar(20) NOT NULL DEFAULT '',
			conta varchar(60) NOT NULL DEFAULT '',
			status varchar(12) NOT NULL DEFAULT 'aberto',
			obs text NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY tipo_status (tipo,status),
			KEY vencimento (vencimento),
			KEY origem (origem,origem_id),
			KEY cliente_id (cliente_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}notas (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tipo varchar(10) NOT NULL DEFAULT 'nfse',
			origem varchar(12) NOT NULL DEFAULT '',
			origem_id bigint(20) unsigned NOT NULL DEFAULT 0,
			cliente_id bigint(20) unsigned NOT NULL DEFAULT 0,
			referencia varchar(60) NOT NULL DEFAULT '',
			numero varchar(30) NOT NULL DEFAULT '',
			serie varchar(10) NOT NULL DEFAULT '',
			valor decimal(12,2) NOT NULL DEFAULT 0,
			status varchar(15) NOT NULL DEFAULT 'pendente',
			chave varchar(60) NOT NULL DEFAULT '',
			xml_url varchar(255) NOT NULL DEFAULT '',
			pdf_url varchar(255) NOT NULL DEFAULT '',
			mensagem text NULL,
			payload longtext NULL,
			resposta longtext NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY origem (origem,origem_id)
		) $c;";

		$sql[] = "CREATE TABLE {$p}historico (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entidade varchar(20) NOT NULL,
			entidade_id bigint(20) unsigned NOT NULL,
			acao varchar(60) NOT NULL,
			detalhes text NULL,
			usuario_id bigint(20) unsigned NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY entidade (entidade,entidade_id)
		) $c;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}
