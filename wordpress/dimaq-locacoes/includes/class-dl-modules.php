<?php
/**
 * Definição dos cadastros: campos, colunas da lista, filtros e permissões.
 * A tela genérica (DL_Crud) monta lista e formulário a partir daqui.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Modules {

	public static function all() {
		static $defs = null;
		if ( null !== $defs ) {
			return $defs;
		}
		$pay   = array( '' => '—' ) + dl_payment_methods();
		$defs  = array();

		$defs['clientes'] = array(
			'table'    => 'clientes',
			'page'     => 'dl-clientes',
			'singular' => 'Cliente',
			'plural'   => 'Clientes',
			'cap'      => 'dl_operar',
			'order'    => 'nome ASC',
			'filters'  => array( 'tipo', 'bloqueado' ),
			'fields'   => array(
				'tipo'            => array( 'label' => 'Tipo', 'type' => 'select', 'options' => array( 'PJ' => 'Pessoa jurídica', 'PF' => 'Pessoa física' ), 'list' => true ),
				'nome'            => array( 'label' => 'Nome / razão social', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'fantasia'        => array( 'label' => 'Nome fantasia', 'type' => 'text', 'search' => true ),
				'documento'       => array( 'label' => 'CPF / CNPJ', 'type' => 'document', 'list' => true, 'search' => true ),
				'ie_rg'           => array( 'label' => 'IE / RG', 'type' => 'text' ),
				'contato'         => array( 'label' => 'Pessoa de contato', 'type' => 'text' ),
				'email'           => array( 'label' => 'E-mail', 'type' => 'email', 'list' => true, 'search' => true ),
				'telefone'        => array( 'label' => 'Telefone', 'type' => 'tel', 'list' => true, 'search' => true ),
				'whatsapp'        => array( 'label' => 'WhatsApp', 'type' => 'tel' ),
				'cep'             => array( 'label' => 'CEP', 'type' => 'cep', 'section' => 'Endereço' ),
				'logradouro'      => array( 'label' => 'Logradouro', 'type' => 'text' ),
				'numero'          => array( 'label' => 'Número', 'type' => 'text' ),
				'complemento'     => array( 'label' => 'Complemento', 'type' => 'text' ),
				'bairro'          => array( 'label' => 'Bairro', 'type' => 'text' ),
				'cidade'          => array( 'label' => 'Cidade', 'type' => 'text', 'list' => true, 'search' => true ),
				'uf'              => array( 'label' => 'UF', 'type' => 'select', 'options' => array( '' => '—' ) + dl_ufs() ),
				'limite_credito'  => array( 'label' => 'Limite de crédito (R$)', 'type' => 'money', 'section' => 'Crédito' ),
				'bloqueado'       => array( 'label' => 'Bloqueado para novas locações', 'type' => 'checkbox', 'list' => true ),
				'motivo_bloqueio' => array( 'label' => 'Motivo do bloqueio', 'type' => 'text' ),
				'wp_user_id'      => array( 'label' => 'Usuário do site (área do cliente)', 'type' => 'user' ),
				'obs'             => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['fornecedores'] = array(
			'table'    => 'fornecedores',
			'page'     => 'dl-fornecedores',
			'singular' => 'Fornecedor',
			'plural'   => 'Fornecedores',
			'cap'      => 'dl_operar',
			'order'    => 'nome ASC',
			'fields'   => array(
				'nome'      => array( 'label' => 'Nome / razão social', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'documento' => array( 'label' => 'CPF / CNPJ', 'type' => 'document', 'list' => true, 'search' => true ),
				'contato'   => array( 'label' => 'Contato', 'type' => 'text' ),
				'email'     => array( 'label' => 'E-mail', 'type' => 'email', 'list' => true ),
				'telefone'  => array( 'label' => 'Telefone', 'type' => 'tel', 'list' => true ),
				'cidade'    => array( 'label' => 'Cidade', 'type' => 'text', 'list' => true ),
				'uf'        => array( 'label' => 'UF', 'type' => 'select', 'options' => array( '' => '—' ) + dl_ufs() ),
				'obs'       => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['categorias'] = array(
			'table'    => 'categorias',
			'page'     => 'dl-categorias',
			'singular' => 'Categoria',
			'plural'   => 'Categorias de equipamentos',
			'cap'      => 'dl_operar',
			'order'    => 'ordem ASC, nome ASC',
			'fields'   => array(
				'nome'      => array( 'label' => 'Nome', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'ordem'     => array( 'label' => 'Ordem no site', 'type' => 'int', 'list' => true ),
				'imagem_id' => array( 'label' => 'Imagem', 'type' => 'media' ),
				'descricao' => array( 'label' => 'Descrição', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['equipamentos'] = array(
			'table'    => 'equipamentos',
			'page'     => 'dl-equipamentos',
			'singular' => 'Equipamento',
			'plural'   => 'Equipamentos',
			'cap'      => 'dl_operar',
			'entity'   => 'equipamento',
			'order'    => 'nome ASC',
			'filters'  => array( 'categoria_id', 'status', 'publicar_site' ),
			'fields'   => array(
				'codigo'          => array( 'label' => 'Código / patrimônio', 'type' => 'text', 'list' => true, 'search' => true ),
				'nome'            => array( 'label' => 'Nome', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'categoria_id'    => array( 'label' => 'Categoria', 'type' => 'relation', 'rel' => 'categorias', 'list' => true ),
				'marca'           => array( 'label' => 'Marca', 'type' => 'text', 'search' => true ),
				'modelo'          => array( 'label' => 'Modelo', 'type' => 'text', 'search' => true ),
				'numero_serie'    => array( 'label' => 'Número de série', 'type' => 'text', 'search' => true ),
				'ano'             => array( 'label' => 'Ano', 'type' => 'text' ),
				'controle'        => array( 'label' => 'Controle', 'type' => 'select', 'options' => array( 'unitario' => 'Unitário (1 patrimônio)', 'quantidade' => 'Por quantidade (andaimes, escoras...)' ), 'help' => 'Por quantidade: um cadastro representa várias peças iguais.' ),
				'qtd_total'       => array( 'label' => 'Quantidade em frota', 'type' => 'int', 'default' => 1, 'list' => true ),
				'status'          => array( 'label' => 'Situação', 'type' => 'select', 'options' => dl_statuses( 'equipamento' ), 'list' => true, 'badge' => 'equipamento' ),
				'valor_diaria'    => array( 'label' => 'Diária (R$)', 'type' => 'money', 'list' => true, 'section' => 'Tabela de preços' ),
				'valor_semanal'   => array( 'label' => 'Semanal (R$)', 'type' => 'money' ),
				'valor_quinzenal' => array( 'label' => 'Quinzenal (R$)', 'type' => 'money' ),
				'valor_mensal'    => array( 'label' => 'Mensal (R$)', 'type' => 'money', 'list' => true ),
				'valor_caucao'    => array( 'label' => 'Caução sugerida (R$)', 'type' => 'money' ),
				'valor_reposicao' => array( 'label' => 'Valor de reposição (R$)', 'type' => 'money', 'help' => 'Aparece no contrato como valor de indenização.' ),
				'valor_aquisicao' => array( 'label' => 'Valor de aquisição (R$)', 'type' => 'money', 'section' => 'Patrimônio e manutenção' ),
				'data_aquisicao'  => array( 'label' => 'Data de aquisição', 'type' => 'date' ),
				'horimetro'       => array( 'label' => 'Horímetro atual', 'type' => 'decimal' ),
				'manutencao_cada_horas' => array( 'label' => 'Manutenção preventiva a cada (horas)', 'type' => 'int' ),
				'ultima_manutencao_horas' => array( 'label' => 'Horímetro na última preventiva', 'type' => 'decimal' ),
				'foto_id'         => array( 'label' => 'Foto', 'type' => 'media', 'section' => 'Site' ),
				'publicar_site'   => array( 'label' => 'Exibir no catálogo do site', 'type' => 'checkbox', 'default' => 1 ),
				'destaque'        => array( 'label' => 'Destaque no site', 'type' => 'checkbox' ),
				'descricao'       => array( 'label' => 'Descrição', 'type' => 'textarea', 'width' => 'full' ),
				'especificacoes'  => array( 'label' => 'Especificações técnicas (uma por linha: Item: valor)', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['produtos'] = array(
			'table'    => 'produtos',
			'page'     => 'dl-produtos',
			'singular' => 'Produto / peça',
			'plural'   => 'Produtos, peças e serviços',
			'cap'      => 'dl_operar',
			'order'    => 'nome ASC',
			'filters'  => array( 'tipo', 'ativo' ),
			'fields'   => array(
				'codigo'         => array( 'label' => 'Código', 'type' => 'text', 'list' => true, 'search' => true ),
				'nome'           => array( 'label' => 'Descrição', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'tipo'           => array( 'label' => 'Tipo', 'type' => 'select', 'options' => array( 'produto' => 'Produto para venda', 'peca' => 'Peça / insumo', 'servico' => 'Serviço' ), 'list' => true ),
				'unidade'        => array( 'label' => 'Unidade', 'type' => 'text', 'default' => 'UN' ),
				'preco_custo'    => array( 'label' => 'Custo (R$)', 'type' => 'money' ),
				'preco_venda'    => array( 'label' => 'Preço de venda (R$)', 'type' => 'money', 'list' => true ),
				'estoque_atual'  => array( 'label' => 'Estoque atual', 'type' => 'decimal', 'list' => true, 'readonly_edit' => true, 'help' => 'Depois de criado, altere pelo botão "Movimentar estoque".' ),
				'estoque_minimo' => array( 'label' => 'Estoque mínimo', 'type' => 'decimal', 'list' => true ),
				'fornecedor_id'  => array( 'label' => 'Fornecedor', 'type' => 'relation', 'rel' => 'fornecedores' ),
				'ncm'            => array( 'label' => 'NCM', 'type' => 'text' ),
				'ativo'          => array( 'label' => 'Ativo', 'type' => 'checkbox', 'default' => 1 ),
				'obs'            => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['contratos'] = array(
			'table'    => 'contratos',
			'page'     => 'dl-contratos',
			'singular' => 'Locação',
			'plural'   => 'Orçamentos e contratos de locação',
			'cap'      => 'dl_operar',
			'entity'   => 'contrato',
			'items'    => 'equipamento',
			'doc_tipo' => 'contrato',
			'order'    => 'id DESC',
			'filters'  => array( 'status', 'cliente_id' ),
			'date_filter' => 'data_inicio',
			'fields'   => array(
				'numero'              => array( 'label' => 'Número', 'type' => 'readonly', 'list' => true, 'search' => true ),
				'cliente_id'          => array( 'label' => 'Cliente', 'type' => 'relation', 'rel' => 'clientes', 'required' => true, 'list' => true ),
				'status'              => array( 'label' => 'Situação', 'type' => 'readonly_status', 'options' => dl_statuses( 'contrato' ), 'list' => true, 'badge' => 'contrato', 'default' => 'orcamento' ),
				'data_inicio'         => array( 'label' => 'Início da locação', 'type' => 'date', 'required' => true, 'list' => true ),
				'data_prev_devolucao' => array( 'label' => 'Devolução prevista', 'type' => 'date', 'required' => true, 'list' => true ),
				'data_encerramento'   => array( 'label' => 'Encerrado em', 'type' => 'readonly_date' ),
				'validade_orcamento'  => array( 'label' => 'Validade do orçamento', 'type' => 'date' ),
				'entrega'             => array( 'label' => 'Entrega', 'type' => 'select', 'options' => array( 'retirada' => 'Cliente retira', 'entrega' => 'Locadora entrega', 'entrega_coleta' => 'Locadora entrega e coleta' ), 'section' => 'Obra e entrega' ),
				'local_obra'          => array( 'label' => 'Obra / local de uso', 'type' => 'text', 'search' => true ),
				'endereco_entrega'    => array( 'label' => 'Endereço de entrega', 'type' => 'text', 'width' => 'full' ),
				'responsavel_obra'    => array( 'label' => 'Responsável na obra', 'type' => 'text' ),
				'telefone_obra'       => array( 'label' => 'Telefone na obra', 'type' => 'tel' ),
				'valor_frete'         => array( 'label' => 'Frete (R$)', 'type' => 'money', 'section' => 'Valores' ),
				'desconto'            => array( 'label' => 'Desconto (R$)', 'type' => 'money' ),
				'caucao'              => array( 'label' => 'Caução (R$)', 'type' => 'money' ),
				'caucao_status'       => array( 'label' => 'Caução', 'type' => 'select', 'options' => array( 'nao_cobrado' => 'Não cobrada', 'recebido' => 'Recebida', 'devolvido' => 'Devolvida', 'retido' => 'Retida' ) ),
				'subtotal'            => array( 'label' => 'Subtotal dos itens', 'type' => 'readonly_money' ),
				'adicionais'          => array( 'label' => 'Adicionais (atraso, avarias)', 'type' => 'readonly_money' ),
				'total'               => array( 'label' => 'Total', 'type' => 'readonly_money', 'list' => true ),
				'valor_faturado'      => array( 'label' => 'Já faturado', 'type' => 'readonly_money' ),
				'forma_pagamento'     => array( 'label' => 'Forma de pagamento', 'type' => 'select', 'options' => $pay ),
				'condicao_pagamento'  => array( 'label' => 'Condição de pagamento', 'type' => 'text', 'help' => 'Ex.: 50% na retirada e 50% na devolução.' ),
				'obs'                 => array( 'label' => 'Observações (saem no contrato)', 'type' => 'textarea', 'width' => 'full' ),
				'obs_interna'         => array( 'label' => 'Observações internas', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['os'] = array(
			'table'    => 'ordens_servico',
			'page'     => 'dl-os',
			'singular' => 'Ordem de serviço',
			'plural'   => 'Ordens de serviço',
			'cap'      => 'dl_operar',
			'entity'   => 'os',
			'items'    => 'produto',
			'doc_tipo' => 'os',
			'order'    => 'id DESC',
			'filters'  => array( 'status', 'tipo', 'equipamento_id' ),
			'date_filter' => 'data_abertura',
			'fields'   => array(
				'numero'         => array( 'label' => 'Número', 'type' => 'readonly', 'list' => true, 'search' => true ),
				'equipamento_id' => array( 'label' => 'Equipamento', 'type' => 'relation', 'rel' => 'equipamentos', 'list' => true ),
				'tipo'           => array( 'label' => 'Tipo', 'type' => 'select', 'options' => array( 'preventiva' => 'Preventiva', 'corretiva' => 'Corretiva', 'revisao' => 'Revisão de retorno', 'externa' => 'Serviço para cliente' ), 'list' => true ),
				'prioridade'     => array( 'label' => 'Prioridade', 'type' => 'select', 'options' => array( 'baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente' ) ),
				'status'         => array( 'label' => 'Situação', 'type' => 'select', 'options' => dl_statuses( 'os' ), 'list' => true, 'badge' => 'os', 'default' => 'aberta' ),
				'cliente_id'     => array( 'label' => 'Cliente (se for cobrar)', 'type' => 'relation', 'rel' => 'clientes', 'list' => true ),
				'contrato_id'    => array( 'label' => 'Contrato relacionado', 'type' => 'relation', 'rel' => 'contratos', 'rel_label' => 'numero' ),
				'tecnico'        => array( 'label' => 'Técnico', 'type' => 'text', 'search' => true ),
				'data_abertura'  => array( 'label' => 'Abertura', 'type' => 'date', 'list' => true ),
				'data_previsao'  => array( 'label' => 'Previsão', 'type' => 'date' ),
				'data_conclusao' => array( 'label' => 'Conclusão', 'type' => 'date' ),
				'horimetro'      => array( 'label' => 'Horímetro', 'type' => 'decimal' ),
				'defeito'        => array( 'label' => 'Defeito relatado', 'type' => 'textarea', 'width' => 'full', 'search' => true ),
				'diagnostico'    => array( 'label' => 'Diagnóstico', 'type' => 'textarea', 'width' => 'full' ),
				'solucao'        => array( 'label' => 'Serviço executado', 'type' => 'textarea', 'width' => 'full' ),
				'mao_obra'       => array( 'label' => 'Mão de obra (R$)', 'type' => 'money', 'section' => 'Valores' ),
				'subtotal'       => array( 'label' => 'Peças e serviços', 'type' => 'readonly_money' ),
				'total'          => array( 'label' => 'Total', 'type' => 'readonly_money', 'list' => true ),
				'cobrar_cliente' => array( 'label' => 'Gerar cobrança ao cliente ao concluir', 'type' => 'checkbox' ),
				'obs'            => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['vendas'] = array(
			'table'    => 'vendas',
			'page'     => 'dl-vendas',
			'singular' => 'Venda',
			'plural'   => 'Vendas',
			'cap'      => 'dl_operar',
			'entity'   => 'venda',
			'items'    => 'produto',
			'doc_tipo' => 'venda',
			'order'    => 'id DESC',
			'filters'  => array( 'status', 'cliente_id' ),
			'date_filter' => 'data_venda',
			'fields'   => array(
				'numero'              => array( 'label' => 'Número', 'type' => 'readonly', 'list' => true, 'search' => true ),
				'cliente_id'          => array( 'label' => 'Cliente', 'type' => 'relation', 'rel' => 'clientes', 'required' => true, 'list' => true ),
				'status'              => array( 'label' => 'Situação', 'type' => 'select', 'options' => dl_statuses( 'venda' ), 'list' => true, 'badge' => 'venda', 'default' => 'orcamento', 'help' => 'Ao confirmar: baixa o estoque e gera as contas a receber.' ),
				'data_venda'          => array( 'label' => 'Data', 'type' => 'date', 'list' => true ),
				'valor_frete'         => array( 'label' => 'Frete (R$)', 'type' => 'money' ),
				'desconto'            => array( 'label' => 'Desconto (R$)', 'type' => 'money' ),
				'subtotal'            => array( 'label' => 'Subtotal', 'type' => 'readonly_money' ),
				'total'               => array( 'label' => 'Total', 'type' => 'readonly_money', 'list' => true ),
				'forma_pagamento'     => array( 'label' => 'Forma de pagamento', 'type' => 'select', 'options' => $pay ),
				'parcelas'            => array( 'label' => 'Parcelas', 'type' => 'int', 'default' => 1 ),
				'primeiro_vencimento' => array( 'label' => '1º vencimento', 'type' => 'date' ),
				'obs'                 => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['financeiro'] = array(
			'table'    => 'financeiro',
			'page'     => 'dl-financeiro',
			'singular' => 'Lançamento',
			'plural'   => 'Contas a receber e a pagar',
			'cap'      => 'dl_financeiro',
			'entity'   => 'financeiro',
			'order'    => 'vencimento ASC, id ASC',
			'filters'  => array( 'tipo', 'status', 'cliente_id', 'categoria' ),
			'date_filter' => 'vencimento',
			'fields'   => array(
				'tipo'            => array( 'label' => 'Tipo', 'type' => 'select', 'options' => array( 'receber' => 'A receber', 'pagar' => 'A pagar' ), 'list' => true ),
				'descricao'       => array( 'label' => 'Descrição', 'type' => 'text', 'required' => true, 'list' => true, 'search' => true ),
				'categoria'       => array( 'label' => 'Categoria', 'type' => 'select', 'options' => self::finance_categories(), 'list' => true ),
				'cliente_id'      => array( 'label' => 'Cliente', 'type' => 'relation', 'rel' => 'clientes', 'list' => true ),
				'fornecedor_id'   => array( 'label' => 'Fornecedor', 'type' => 'relation', 'rel' => 'fornecedores' ),
				'emissao'         => array( 'label' => 'Emissão', 'type' => 'date' ),
				'vencimento'      => array( 'label' => 'Vencimento', 'type' => 'date', 'required' => true, 'list' => true ),
				'valor'           => array( 'label' => 'Valor (R$)', 'type' => 'money', 'required' => true, 'list' => true ),
				'parcela'         => array( 'label' => 'Parcela', 'type' => 'int', 'default' => 1 ),
				'total_parcelas'  => array( 'label' => 'De', 'type' => 'int', 'default' => 1 ),
				'status'          => array( 'label' => 'Situação', 'type' => 'readonly_status', 'options' => dl_statuses( 'financeiro' ), 'list' => true, 'badge' => 'financeiro', 'default' => 'aberto' ),
				'juros'           => array( 'label' => 'Juros (R$)', 'type' => 'money', 'section' => 'Pagamento' ),
				'multa'           => array( 'label' => 'Multa (R$)', 'type' => 'money' ),
				'desconto'        => array( 'label' => 'Desconto (R$)', 'type' => 'money' ),
				'valor_pago'      => array( 'label' => 'Valor pago (R$)', 'type' => 'readonly_money', 'list' => true ),
				'data_pagamento'  => array( 'label' => 'Pago em', 'type' => 'readonly_date', 'list' => true ),
				'forma_pagamento' => array( 'label' => 'Forma', 'type' => 'select', 'options' => $pay ),
				'conta'           => array( 'label' => 'Conta / caixa', 'type' => 'text' ),
				'obs'             => array( 'label' => 'Observações', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		$defs['notas'] = array(
			'table'    => 'notas',
			'page'     => 'dl-notas',
			'singular' => 'Nota fiscal',
			'plural'   => 'Notas fiscais',
			'cap'      => 'dl_financeiro',
			'entity'   => 'nota',
			'order'    => 'id DESC',
			'filters'  => array( 'tipo', 'status' ),
			'no_create' => true,
			'fields'   => array(
				'tipo'       => array( 'label' => 'Tipo', 'type' => 'readonly', 'list' => true ),
				'referencia' => array( 'label' => 'Referência', 'type' => 'readonly', 'list' => true, 'search' => true ),
				'cliente_id' => array( 'label' => 'Cliente', 'type' => 'readonly_relation', 'rel' => 'clientes', 'list' => true ),
				'numero'     => array( 'label' => 'Número', 'type' => 'text', 'list' => true ),
				'serie'      => array( 'label' => 'Série', 'type' => 'text' ),
				'valor'      => array( 'label' => 'Valor', 'type' => 'readonly_money', 'list' => true ),
				'status'     => array( 'label' => 'Situação', 'type' => 'select', 'options' => dl_statuses( 'nota' ), 'list' => true, 'badge' => 'nota' ),
				'chave'      => array( 'label' => 'Chave de acesso / código de verificação', 'type' => 'text' ),
				'pdf_url'    => array( 'label' => 'Link do PDF (DANFE / DANFSe)', 'type' => 'url' ),
				'xml_url'    => array( 'label' => 'Link do XML', 'type' => 'url' ),
				'mensagem'   => array( 'label' => 'Retorno do emissor', 'type' => 'textarea', 'width' => 'full' ),
			),
		);

		/**
		 * Permite adicionar campos ou módulos sem editar o plugin.
		 */
		$defs = apply_filters( 'dl_modules', $defs );
		return $defs;
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] + array( 'key' => $key ) : null;
	}

	public static function by_page( $page ) {
		foreach ( self::all() as $key => $def ) {
			if ( $def['page'] === $page ) {
				return $def + array( 'key' => $key );
			}
		}
		return null;
	}

	public static function finance_categories() {
		return apply_filters(
			'dl_finance_categories',
			array(
				''            => '—',
				'locacao'     => 'Receita de locação',
				'venda'       => 'Receita de venda',
				'servico'     => 'Receita de serviço',
				'frete'       => 'Frete',
				'caucao'      => 'Caução',
				'manutencao'  => 'Manutenção de frota',
				'pecas'       => 'Compra de peças',
				'equipamento' => 'Compra de equipamento',
				'combustivel' => 'Combustível',
				'folha'       => 'Folha de pagamento',
				'impostos'    => 'Impostos',
				'aluguel'     => 'Aluguel / estrutura',
				'outros'      => 'Outros',
			)
		);
	}
}
