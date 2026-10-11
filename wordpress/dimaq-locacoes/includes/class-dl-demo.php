<?php
/**
 * Dados de demonstração: preenche o sistema com uma locadora fictícia em funcionamento
 * (frota, clientes, seis meses de histórico, locações em andamento, medição, OS, vendas e
 * financeiro) para apresentar o sistema, e apaga tudo depois sem tocar nos dados reais.
 *
 * Tudo é criado pelas mesmas regras do sistema (reserva, entrega, devolução, faturamento,
 * medição). Para apagar, guarda a faixa de ids criada em cada tabela: o que veio antes ou
 * depois da demonstração não é tocado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Demo {

	const OPTION = 'dl_demo';

	const TABLES = array( 'clientes', 'fornecedores', 'categorias', 'equipamentos', 'produtos', 'estoque_mov', 'contratos', 'itens', 'movimentos', 'medicoes', 'ordens_servico', 'vendas', 'financeiro', 'notas', 'historico' );

	private static $t;

	public static function active() {
		return (bool) get_option( self::OPTION );
	}

	public static function status() {
		$d = get_option( self::OPTION );
		return array(
			'ativo'      => (bool) $d,
			'criado_em'  => $d ? $d['criado_em'] : null,
			'contagens'  => $d ? $d['contagens'] : null,
		);
	}

	/* ------------------------------------------------------------- apagar */

	public static function remove() {
		global $wpdb;
		$d = get_option( self::OPTION );
		if ( ! $d ) {
			return new WP_Error( 'demo', 'Não há dados de demonstração carregados.' );
		}
		$deleted = 0;
		foreach ( $d['faixas'] as $table => $range ) {
			$deleted += (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . dl_table( $table ) . ' WHERE id > %d AND id <= %d', $range[0], $range[1] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		// a numeração de contratos volta ao ponto de antes, se nenhum contrato real foi criado depois
		if ( (int) get_option( 'dl_contract_last', 0 ) === (int) $d['contrato_fim'] ) {
			update_option( 'dl_contract_last', (int) $d['contrato_antes'], false );
		}
		delete_option( DL_Route::ORDER_OPTION );
		delete_option( self::OPTION );
		return array( 'message' => 'Dados de demonstração apagados (' . $deleted . ' registros). O sistema está pronto para os dados reais.' );
	}

	/* ------------------------------------------------------------- carregar */

	public static function load() {
		global $wpdb;
		if ( self::active() ) {
			return new WP_Error( 'demo', 'Os dados de demonstração já estão carregados.' );
		}
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$before = array();
		foreach ( self::TABLES as $tb ) {
			$before[ $tb ] = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(id),0) FROM ' . dl_table( $tb ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		$counter = (int) get_option( 'dl_contract_last', 0 );
		// marca antes de começar: se algo falhar no meio, o "apagar" ainda limpa o que entrou
		update_option( self::OPTION, array( 'criado_em' => dl_now(), 'faixas' => self::ranges( $before ), 'contrato_antes' => $counter, 'contrato_fim' => $counter, 'contagens' => array() ), false );

		mt_srand( 6576 );
		self::$t = dl_today();
		try {
			self::build();
		} catch ( Throwable $e ) {
			self::finish( $before, $counter );
			return new WP_Error( 'demo', 'A demonstração foi carregada pela metade: ' . $e->getMessage() . ' Use "Apagar dados de demonstração".' );
		}
		self::finish( $before, $counter );
		dl_take_notices(); // avisos internos das regras não interessam aqui
		$c = get_option( self::OPTION )['contagens'];
		return array( 'message' => sprintf( 'Demonstração carregada: %d locações, %d clientes, %d equipamentos e %d lançamentos financeiros.', $c['contratos'], $c['clientes'], $c['equipamentos'], $c['financeiro'] ) );
	}

	private static function ranges( $before ) {
		global $wpdb;
		$out = array();
		foreach ( $before as $tb => $max ) {
			$out[ $tb ] = array( $max, PHP_INT_MAX ); // provisório até terminar
		}
		return $out;
	}

	private static function finish( $before, $counter ) {
		global $wpdb;
		$faixas = array();
		$counts = array();
		foreach ( $before as $tb => $max ) {
			$now             = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(id),0) FROM ' . dl_table( $tb ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			$faixas[ $tb ]   = array( $max, $now );
			$counts[ $tb ]   = $now - $max;
		}
		$d = get_option( self::OPTION );
		$d['faixas']       = $faixas;
		$d['contagens']    = $counts;
		$d['contrato_fim'] = (int) get_option( 'dl_contract_last', 0 );
		update_option( self::OPTION, $d, false );
	}

	/* ------------------------------------------------------------- ajudantes */

	private static function d( $n ) {
		return dl_add_days( self::$t, $n );
	}

	private static function cnpj( $base ) {
		$n = str_pad( (string) $base, 8, '0', STR_PAD_LEFT ) . '0001';
		foreach ( array( array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ), array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) ) as $w ) {
			$s = 0;
			foreach ( $w as $i => $x ) {
				$s += (int) $n[ $i ] * $x;
			}
			$r  = $s % 11;
			$n .= $r < 2 ? '0' : (string) ( 11 - $r );
		}
		return $n;
	}

	private static function cpf( $base ) {
		$n = str_pad( (string) $base, 9, '0', STR_PAD_LEFT );
		for ( $k = 10; $k <= 11; $k++ ) {
			$s = 0;
			for ( $i = 0; $i < $k - 1; $i++ ) {
				$s += (int) $n[ $i ] * ( $k - $i );
			}
			$r  = ( $s * 10 ) % 11;
			$n .= 10 === $r ? '0' : (string) $r;
		}
		return $n;
	}

	/** Cria a locação pelas regras do sistema. $items: [ [equip_id, qtd], ... ] */
	private static function contract( $cli, $start, $end, array $items, array $extra = array() ) {
		$days = DL_Contracts::rental_days( $start, $end );
		$rows = array();
		$med  = 'medicao' === ( $extra['cobranca'] ?? '' );
		foreach ( $items as $it ) {
			$e = DL_DB::get( 'equipamentos', $it[0] );
			if ( $med ) {
				$rows[] = array( 'ref_id' => $it[0], 'qtd' => $it[1], 'periodo_tipo' => 'mensal', 'periodos' => 1, 'valor_unit' => $e['valor_mensal'] );
			} else {
				$best   = DL_Pricing::best_price( DL_Contracts::rates( $e ), $days );
				$rows[] = array( 'ref_id' => $it[0], 'qtd' => $it[1], 'periodo_tipo' => 'pacote', 'periodos' => 1, 'valor_unit' => $best['total'], 'descricao' => $e['nome'] . ' — ' . $best['descricao'] );
			}
		}
		$c   = DL_DB::get( 'clientes', $cli['id'] );
		$in  = array_merge(
			array(
				'cliente_id'          => $cli['id'],
				'data_inicio'         => $start,
				'data_prev_devolucao' => $end,
				'entrega'             => 'entrega_coleta',
				'local_obra'          => $cli['obra'],
				'endereco_entrega'    => $cli['end'],
				'responsavel_obra'    => $c['contato'],
				'telefone_obra'       => $c['whatsapp'],
				'valor_frete'         => 120,
				'forma_pagamento'     => 'boleto',
				'condicao_pagamento'  => 'Boleto 10 dias após a emissão',
			),
			$extra
		);
		$id = DL_Crud::save_record( 'contratos', 0, $in, $rows );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		return $id;
	}

	private static function get( $id ) {
		return DL_DB::get( 'contratos', $id );
	}

	private static function check( $r ) {
		if ( is_wp_error( $r ) ) {
			throw new Exception( $r->get_error_message() );
		}
		return $r;
	}

	private static function reserve( $id ) {
		self::check( DL_Contracts::reserve( self::get( $id ) ) );
	}

	private static function deliver( $id, $date ) {
		self::check( DL_Contracts::reserve( self::get( $id ) ) );
		self::check( DL_Contracts::deliver( self::get( $id ), $date, array() ) );
	}

	/** Devolve tudo, ou só $qty de cada item. */
	private static function receive( $id, $date, $qty = null ) {
		$mv = array();
		foreach ( DL_Items::get( 'contrato', $id ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			$left = (float) $it['qtd'] - (float) $it['qtd_devolvida'];
			$q    = null === $qty ? $left : min( $left, $qty );
			if ( $q > 0 ) {
				$mv[ $it['id'] ] = array( 'qtd' => $q, 'marks' => array( 'Limpo', 'Completo', 'Funcionando' ) );
			}
		}
		self::check( DL_Contracts::receive( self::get( $id ), $date, $mv, false, 'devolvido' ) );
	}

	private static function bill( $id, $value, $due, $parcelas = 1 ) {
		self::check( DL_Contracts::perform( self::get( $id ), 'faturar', array( 'valor' => round( $value, 2 ), 'parcelas' => $parcelas, 'vencimento' => $due, 'intervalo' => 30, 'forma' => 'boleto' ) ) );
	}

	/** Recebe as contas em aberto da locação até a data limite. */
	private static function pay_contract( $id, $until, $method = 'pix' ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'financeiro' ) . " WHERE origem = 'contrato' AND origem_id = %d AND status = 'aberto' AND vencimento <= %s", $id, $until ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $rows as $f ) {
			$when = min( self::$t, dl_add_days( $f['vencimento'], mt_rand( -2, 1 ) ) );
			DL_Finance::pay( $f, $when, (float) $f['valor'], 0, 0, 0, $method );
		}
	}

	/* ------------------------------------------------------------- o cenário */

	private static function build() {
		$t = self::$t;

		/* cadastros */
		$cat = array();
		foreach ( array( 'Compactação', 'Concreto', 'Andaimes e escoras', 'Demolição', 'Geradores e energia', 'Corte e acabamento', 'Elevação de carga' ) as $i => $n ) {
			$cat[ $n ] = DL_DB::insert( 'categorias', array( 'nome' => $n, 'ordem' => $i ) );
		}
		// código, nome, categoria, marca, diária, semanal, quinzenal, mensal, quantidade (0 = unitário), horímetro, preventiva a cada
		$frota = array(
			array( 'CP-01', 'Compactador de solo (sapo)', 'Compactação', 'Wacker Neuson', 95, 430, 760, 1290, 0, 612, 250 ),
			array( 'CP-02', 'Compactador de solo (sapo)', 'Compactação', 'Wacker Neuson', 95, 430, 760, 1290, 0, 388, 250 ),
			array( 'PV-01', 'Placa vibratória', 'Compactação', 'Weber', 75, 330, 580, 980, 0, 205, 250 ),
			array( 'RL-01', 'Rolo compactador 1 t', 'Compactação', 'Ammann', 390, 1750, 3100, 5300, 0, 1740, 250 ),
			array( 'BT-01', 'Betoneira 400 L', 'Concreto', 'Menegotti', 65, 290, 500, 840, 0, 0, 0 ),
			array( 'BT-02', 'Betoneira 400 L', 'Concreto', 'Menegotti', 65, 290, 500, 840, 0, 0, 0 ),
			array( 'BT-03', 'Betoneira 600 L', 'Concreto', 'Menegotti', 85, 380, 660, 1100, 0, 0, 0 ),
			array( 'VB-01', 'Vibrador de concreto', 'Concreto', 'Bosch', 48, 210, 360, 610, 0, 0, 0 ),
			array( 'VB-02', 'Vibrador de concreto', 'Concreto', 'Bosch', 48, 210, 360, 610, 0, 0, 0 ),
			array( 'AN-01', 'Andaime tubular (peça)', 'Andaimes e escoras', 'Rohr', 2, 9, 15, 26, 600, 0, 0 ),
			array( 'ES-01', 'Escora metálica 3 m', 'Andaimes e escoras', 'Rohr', 1.5, 7, 12, 20, 800, 0, 0 ),
			array( 'PI-01', 'Piso metálico para andaime', 'Andaimes e escoras', 'Rohr', 1.2, 5, 9, 15, 300, 0, 0 ),
			array( 'MR-01', 'Martelete rompedor 10 kg', 'Demolição', 'Makita', 88, 395, 690, 1160, 0, 0, 0 ),
			array( 'MR-02', 'Martelete rompedor 10 kg', 'Demolição', 'Makita', 88, 395, 690, 1160, 0, 0, 0 ),
			array( 'MR-03', 'Martelete rompedor 30 kg', 'Demolição', 'Bosch', 135, 610, 1060, 1800, 0, 0, 0 ),
			array( 'MP-01', 'Martelete perfurador SDS', 'Demolição', 'Makita', 55, 240, 420, 700, 0, 0, 0 ),
			array( 'GR-01', 'Gerador 5 kVA', 'Geradores e energia', 'Branco', 160, 720, 1250, 2150, 0, 395, 200 ),
			array( 'GR-02', 'Gerador 12 kVA', 'Geradores e energia', 'Toyama', 260, 1150, 2000, 3400, 0, 830, 200 ),
			array( 'CT-01', 'Cortadora de piso', 'Corte e acabamento', 'Norton', 140, 620, 1080, 1850, 0, 120, 150 ),
			array( 'SM-01', 'Serra mármore', 'Corte e acabamento', 'Makita', 40, 170, 290, 490, 0, 0, 0 ),
			array( 'LX-01', 'Lixadeira de parede (girafa)', 'Corte e acabamento', 'Makita', 70, 310, 540, 910, 0, 0, 0 ),
			array( 'GU-01', 'Guincho de coluna 400 kg', 'Elevação de carga', 'CSM', 120, 540, 940, 1600, 0, 0, 0 ),
			array( 'TA-01', 'Talha elétrica 500 kg', 'Elevação de carga', 'CSM', 95, 420, 730, 1240, 0, 0, 0 ),
		);
		$eq = array();
		foreach ( $frota as $e ) {
			$qty  = $e[8];
			$eq[ $e[0] ] = DL_DB::insert(
				'equipamentos',
				array(
					'codigo'                  => $e[0],
					'nome'                    => $e[1],
					'categoria_id'            => $cat[ $e[2] ],
					'marca'                   => $e[3],
					'numero_serie'            => $qty ? '' : strtoupper( substr( md5( $e[0] ), 0, 8 ) ),
					'ano'                     => 2019 + mt_rand( 0, 6 ),
					'controle'                => $qty ? 'quantidade' : 'unitario',
					'qtd_total'               => $qty ? $qty : 1,
					'valor_diaria'            => $e[4],
					'valor_semanal'           => $e[5],
					'valor_quinzenal'         => $e[6],
					'valor_mensal'            => $e[7],
					'valor_caucao'            => $qty ? 0 : round( $e[4] * 3, -1 ),
					'valor_reposicao'         => $qty ? round( $e[7] * 4, 2 ) : round( $e[4] * 45, -2 ),
					'valor_aquisicao'         => $qty ? round( $e[7] * 3.5, 2 ) : round( $e[4] * 38, -2 ),
					'data_aquisicao'          => self::d( -mt_rand( 300, 1500 ) ),
					'horimetro'               => $e[9],
					'manutencao_cada_horas'   => $e[10],
					'ultima_manutencao_horas' => $e[10] ? ( 'GR-01' === $e[0] ? 180 : floor( $e[9] / $e[10] ) * $e[10] ) : 0,
					'status'                  => 'disponivel',
					'descricao'               => $e[1] . ' ' . $e[3] . ', revisado e higienizado a cada locação.',
					'publicar_site'           => 1,
					'destaque'                => in_array( $e[0], array( 'CP-01', 'BT-01', 'AN-01', 'MR-01', 'GR-01', 'CT-01' ), true ) ? 1 : 0,
				)
			);
		}

		$clientes = array(
			array( 'PJ', 'Construtora Horizonte Ltda', 'Horizonte', 'Marcos Andrade', 'Rua Vergueiro, 2000 - Vila Mariana, São Paulo - SP', 'Edifício Jardins' ),
			array( 'PJ', 'Engenharia Vale Verde S/A', 'Vale Verde', 'Patrícia Lima', 'Av. Ibirapuera, 3100 - Moema, São Paulo - SP', 'Reforma loja Moema' ),
			array( 'PJ', 'Construtora Pedra Alta Ltda', 'Pedra Alta', 'Ricardo Souza', 'Av. Interlagos, 4500 - Interlagos, São Paulo - SP', 'Residencial Interlagos' ),
			array( 'PJ', 'Reformas Rápidas Sul Ltda', 'Reformas Sul', 'Juliana Prado', 'Rua Domingos de Morais, 800 - Vila Mariana, São Paulo - SP', 'Galpão Saúde' ),
			array( 'PJ', 'Condomínio Parque das Árvores', '', 'Síndico Paulo Mendes', 'Rua Jurubatuba, 1200 - Jardim Apurá, São Paulo - SP', 'Fachada bloco B' ),
			array( 'PJ', 'Metalúrgica Santo Amaro Ltda', 'Metal SA', 'Eduardo Ramos', 'Av. Santo Amaro, 6200 - Santo Amaro, São Paulo - SP', 'Ampliação do galpão' ),
			array( 'PJ', 'Colégio Novo Saber', '', 'Fernanda Costa', 'Rua Cupecê, 2300 - Jardim Prudência, São Paulo - SP', 'Quadra poliesportiva' ),
			array( 'PJ', 'Igreja Comunidade Esperança', '', 'Pastor Silas', 'Estrada do Alvarenga, 3500 - Pedreira, São Paulo - SP', 'Novo salão' ),
			array( 'PF', 'José Carlos Ferreira', '', 'José Carlos', 'Rua Antônio do Campo, 250 - Pedreira, São Paulo - SP', 'Casa própria' ),
			array( 'PF', 'Ana Beatriz Moreira', '', 'Ana Beatriz', 'Rua Professor Ciridião Buarque, 90 - Diadema - SP', 'Reforma da cozinha' ),
			array( 'PF', 'Roberto Nunes da Silva', '', 'Roberto', 'Av. Yervant Kissajikian, 1500 - Vila Constância, São Paulo - SP', 'Laje da garagem' ),
			array( 'PJ', 'Eventos Primavera Ltda', 'Primavera Eventos', 'Camila Duarte', 'Parque do Ibirapuera, portão 3 - São Paulo - SP', 'Feira de domingo' ),
		);
		$cli = array();
		foreach ( $clientes as $i => $c ) {
			$tel   = '119' . str_pad( (string) ( 47100000 + 731 * $i * 17 ), 8, '0', STR_PAD_LEFT );
			// "Rua X, 100 - Bairro, Cidade - SP" ou "Rua X, 100 - Cidade - SP"
			$parts  = explode( ' - ', $c[4] );
			$street = array_map( 'trim', explode( ',', $parts[0], 2 ) );
			$place  = array_map( 'trim', explode( ',', $parts[1], 2 ) );
			$id    = DL_DB::insert(
				'clientes',
				array(
					'tipo'       => $c[0],
					'nome'       => $c[1],
					'fantasia'   => $c[2],
					'documento'  => 'PJ' === $c[0] ? self::cnpj( 31000000 + 4127 * $i ) : self::cpf( 271000000 + 9137 * $i ),
					'email'      => 'contato' . ( $i + 1 ) . '@exemplo.com.br',
					'telefone'   => $tel,
					'whatsapp'   => $tel,
					'contato'    => $c[3],
					'logradouro' => $street[0],
					'numero'     => $street[1] ?? '',
					'bairro'     => count( $place ) > 1 ? $place[0] : '',
					'cidade'     => count( $place ) > 1 ? $place[1] : $place[0],
					'uf'         => 'SP',
					'limite_credito' => 'PJ' === $c[0] ? 15000 : 0,
				)
			);
			$cli[] = array( 'id' => $id, 'end' => $c[4], 'obra' => $c[5] );
		}
		$forn = array();
		foreach ( array( array( 'Distribuidora de Peças Paulista', 'Peças e acessórios' ), array( 'Auto Posto Apurá', 'Combustível' ), array( 'Imobiliária Jurubatuba', 'Aluguel do galpão' ) ) as $i => $f ) {
			$forn[] = DL_DB::insert( 'fornecedores', array( 'nome' => $f[0], 'documento' => self::cnpj( 42000000 + 311 * $i ), 'telefone' => '1155' . ( 601000 + $i ), 'cidade' => 'São Paulo', 'uf' => 'SP', 'obs' => $f[1] ) );
		}
		$prod = array();
		foreach ( array(
			array( 'PC-01', 'Ponteira para martelete SDS-Max', 'peca', 'un', 32, 58, 3, 6 ),
			array( 'PC-02', 'Talhadeira para martelete', 'peca', 'un', 35, 62, 8, 4 ),
			array( 'PC-03', 'Óleo 2 tempos (500 ml)', 'produto', 'un', 14, 28, 22, 10 ),
			array( 'PC-04', 'Vela de ignição', 'peca', 'un', 18, 0, 12, 6 ),
			array( 'PC-05', 'Correia para betoneira', 'peca', 'un', 46, 0, 2, 4 ),
			array( 'PC-06', 'Disco diamantado 350 mm', 'produto', 'un', 180, 290, 6, 3 ),
			array( 'PC-07', 'Filtro de ar do gerador', 'peca', 'un', 38, 0, 5, 3 ),
			array( 'SV-01', 'Operador de máquina (hora)', 'servico', 'h', 0, 85, 0, 0 ),
		) as $p ) {
			$prod[ $p[0] ] = DL_DB::insert( 'produtos', array( 'codigo' => $p[0], 'nome' => $p[1], 'tipo' => $p[2], 'unidade' => $p[3], 'preco_custo' => $p[4], 'preco_venda' => $p[5], 'estoque_atual' => 0, 'estoque_minimo' => $p[7], 'fornecedor_id' => $forn[0], 'ativo' => 1 ) );
			if ( 'servico' !== $p[2] ) {
				DL_Stock::move( $prod[ $p[0] ], 'entrada', $p[6], 'manual', 0, 'Estoque inicial', $p[4] );
			}
		}

		/* histórico: seis meses de locações encerradas e recebidas */
		$unit = array( 'CP-01', 'CP-02', 'PV-01', 'RL-01', 'BT-01', 'BT-02', 'BT-03', 'VB-01', 'VB-02', 'MR-01', 'MR-02', 'MR-03', 'MP-01', 'GR-01', 'GR-02', 'CT-01', 'SM-01', 'LX-01', 'GU-01', 'TA-01' );
		$free = array_fill_keys( $unit, self::d( -185 ) );
		$late_unpaid = 0;
		$n           = 34;
		for ( $k = 0; $k < $n; $k++ ) {
			$target = self::d( -178 + intdiv( $k * 156, $n ) + mt_rand( 0, 3 ) ); // espalhadas pelos seis meses
			$code   = null;
			for ( $j = 0; $j < count( $unit ); $j++ ) {
				$try = $unit[ ( $k * 7 + $j ) % count( $unit ) ];
				if ( $free[ $try ] < $target ) {
					$code = $try;
					break;
				}
			}
			$len = array( 5, 7, 7, 10, 14, 15, 21, 30 )[ mt_rand( 0, 7 ) ];
			$end = DL_Contracts::end_for( $target, $len );
			if ( ! $code || $end >= self::d( -18 ) ) {
				continue;
			}
			$items = array( array( $eq[ $code ], 1 ) );
			if ( 0 === $k % 4 ) {
				$items[] = array( $eq['AN-01'], 40 + 20 * mt_rand( 0, 6 ) );
			}
			$c  = $cli[ $k % count( $cli ) ];
			$id = self::contract( $c, $target, $end, $items, array( 'motorista' => array( 'João', 'Pedro' )[ $k % 2 ] ) );
			self::deliver( $id, $target );
			$ret = 0 === $k % 7 ? dl_add_days( $end, 2 ) : $end; // uma ou outra voltou com atraso
			self::receive( $id, $ret );
			$row = self::get( $id );
			self::bill( $id, (float) $row['total'], dl_add_days( $ret, 10 ) );
			if ( dl_add_days( $ret, 10 ) < self::d( -5 ) && $late_unpaid < 2 && $k > 24 ) {
				$late_unpaid++; // ficam em aberto: inadimplência
			} else {
				self::pay_contract( $id, self::$t, 0 === $k % 3 ? 'boleto' : 'pix' );
			}
			$free[ $code ] = dl_add_days( $ret, 1 );
		}

		/* em andamento */
		$J = 'João';
		$P = 'Pedro';
		// atrasada 3 dias, com andaimes
		$a = self::contract( $cli[0], self::d( -15 ), self::d( -3 ), array( array( $eq['CP-01'], 1 ), array( $eq['AN-01'], 80 ) ), array( 'motorista' => $J ) );
		self::deliver( $a, self::d( -15 ) );
		self::bill( $a, (float) self::get( $a )['total'] / 2, self::d( -5 ) );
		self::pay_contract( $a, self::$t );
		// vence hoje: coleta na rota
		$b = self::contract( $cli[1], self::d( -7 ), $t, array( array( $eq['BT-01'], 1 ), array( $eq['VB-01'], 1 ) ), array( 'motorista' => $J ) );
		self::deliver( $b, self::d( -7 ) );
		// vencem nos próximos dias
		$c2 = self::contract( $cli[3], self::d( -6 ), self::d( 1 ), array( array( $eq['MR-01'], 1 ) ), array( 'motorista' => $P, 'entrega' => 'entrega' ) );
		self::deliver( $c2, self::d( -6 ) );
		$c3 = self::contract( $cli[8], self::d( -4 ), self::d( 2 ), array( array( $eq['BT-02'], 1 ) ), array( 'entrega' => 'retirada', 'valor_frete' => 0, 'local_obra' => 'Casa própria', 'forma_pagamento' => 'pix', 'condicao_pagamento' => 'PIX na retirada' ) );
		self::deliver( $c3, self::d( -4 ) );
		self::bill( $c3, (float) self::get( $c3 )['total'], self::d( -4 ) );
		self::pay_contract( $c3, self::$t, 'pix' );
		$c4 = self::contract( $cli[5], self::d( -9 ), self::d( 5 ), array( array( $eq['GR-02'], 1 ), array( $eq['TA-01'], 1 ) ), array( 'motorista' => $P ) );
		self::deliver( $c4, self::d( -9 ) );
		self::bill( $c4, (float) self::get( $c4 )['total'], self::d( 6 ), 1 );
		$c5 = self::contract( $cli[6], self::d( -2 ), self::d( 12 ), array( array( $eq['CT-01'], 1 ), array( $eq['LX-01'], 1 ) ), array( 'motorista' => $J ) );
		self::deliver( $c5, self::d( -2 ) );
		$c6 = self::contract( $cli[11], self::d( -1 ), self::d( 1 ), array( array( $eq['GR-01'], 1 ) ), array( 'motorista' => $P, 'local_obra' => 'Feira de domingo' ) );
		self::deliver( $c6, self::d( -1 ) );
		// cliente retirou e pediu coleta pela área do cliente
		$c7 = self::contract( $cli[10], self::d( -5 ), self::d( 9 ), array( array( $eq['MP-01'], 1 ) ), array( 'entrega' => 'retirada', 'valor_frete' => 0 ) );
		self::deliver( $c7, self::d( -5 ) );
		DL_DB::update( 'contratos', $c7, array( 'coleta_em' => $t, 'motorista' => $J ) );
		dl_log( 'contratos', $c7, 'Cliente pediu COLETA / vai devolver', 'pela área do cliente' );

		// medição pro-rata: obra longa de andaimes, com devolução parcial e uma medição já gerada
		$m1 = self::contract( $cli[4], self::d( -45 ), self::d( 45 ), array( array( $eq['AN-01'], 300 ), array( $eq['ES-01'], 200 ), array( $eq['PI-01'], 80 ) ), array( 'cobranca' => 'medicao', 'medicao_ciclo' => 30, 'motorista' => $P, 'valor_frete' => 350, 'condicao_pagamento' => 'Medição mensal, boleto 15 dias' ) );
		self::deliver( $m1, self::d( -45 ) );
		self::receive( $m1, self::d( -20 ), 100 );
		$r = DL_Measurement::generate( self::get( $m1 ), array( 'inicio' => self::d( -45 ), 'fim' => self::d( -16 ), 'vencimento' => self::d( -1 ), 'forma' => 'boleto' ) );
		self::check( $r );
		self::pay_contract( $m1, self::$t, 'boleto' );
		// segunda obra medida, sem medição ainda: aparece o alerta "Medição pendente"
		$m2 = self::contract( $cli[2], self::d( -33 ), self::d( 57 ), array( array( $eq['ES-01'], 250 ), array( $eq['AN-01'], 120 ) ), array( 'cobranca' => 'medicao', 'medicao_ciclo' => 30, 'motorista' => $J, 'valor_frete' => 280 ) );
		self::deliver( $m2, self::d( -33 ) );

		/* reservas, orçamentos e pedidos do site */
		$r1 = self::contract( $cli[0], $t, self::d( 6 ), array( array( $eq['MR-03'], 1 ), array( $eq['MR-02'], 1 ) ), array( 'motorista' => $J, 'local_obra' => 'Demolição da laje', 'obs_interna' => 'Portão de carga pela rua de trás' ) );
		self::reserve( $r1 );
		$r2 = self::contract( $cli[7], $t, self::d( 13 ), array( array( $eq['BT-03'], 1 ), array( $eq['VB-02'], 1 ), array( $eq['AN-01'], 60 ) ), array( 'motorista' => $P ) );
		self::reserve( $r2 );
		$r3 = self::contract( $cli[2], self::d( 1 ), self::d( 8 ), array( array( $eq['RL-01'], 1 ), array( $eq['PV-01'], 1 ) ), array( 'motorista' => $J ) );
		self::reserve( $r3 );
		$r4 = self::contract( $cli[9], self::d( 1 ), self::d( 3 ), array( array( $eq['SM-01'], 1 ) ), array( 'entrega' => 'retirada', 'valor_frete' => 0 ) );
		self::reserve( $r4 );
		self::contract( $cli[1], self::d( 4 ), self::d( 18 ), array( array( $eq['ES-01'], 50 ), array( $eq['AN-01'], 100 ) ), array( 'validade_orcamento' => self::d( 5 ) ) );
		self::contract( $cli[6], self::d( 10 ), self::d( 40 ), array( array( $eq['AN-01'], 150 ), array( $eq['PI-01'], 40 ) ), array( 'validade_orcamento' => self::d( 6 ) ) );
		self::contract( $cli[5], self::d( 3 ), self::d( 9 ), array( array( $eq['GR-01'], 1 ) ), array( 'validade_orcamento' => self::d( 2 ) ) );
		foreach ( array( array( $cli[9], 'BT-01', 'Reforma da cozinha' ), array( $cli[10], 'CP-02', 'Laje da garagem' ) ) as $i => $s ) {
			$id = self::contract( $s[0], self::d( 2 + $i ), self::d( 8 + $i ), array( array( $eq[ $s[1] ], 1 ) ), array( 'obs' => 'Pedido feito pelo site.' ) );
			DL_DB::update( 'contratos', $id, array( 'status' => 'solicitacao', 'origem' => 'site' ) );
		}
		$x = self::contract( $cli[3], self::d( -3 ), self::d( 4 ), array( array( $eq['MR-02'], 1 ) ), array() );
		self::check( DL_Contracts::perform( self::get( $x ), 'cancelar', array() ) );

		/* ordens de serviço */
		$os_done = self::check(
			DL_Crud::save_record(
				'os',
				0,
				array( 'equipamento_id' => $eq['CP-02'], 'tipo' => 'preventiva', 'prioridade' => 'normal', 'status' => 'aberta', 'tecnico' => 'Valdir', 'data_abertura' => self::d( -24 ), 'defeito' => 'Preventiva de 250 h', 'diagnostico' => 'Troca de óleo, filtro e vela', 'solucao' => 'Preventiva realizada, equipamento testado', 'mao_obra' => 90, 'horimetro' => 250 ),
				array( array( 'ref_id' => $prod['PC-04'], 'qtd' => 1, 'valor_unit' => 18 ), array( 'ref_id' => $prod['PC-03'], 'qtd' => 2, 'valor_unit' => 14 ) )
			)
		);
		self::check( DL_Crud::save_record( 'os', $os_done, array( 'status' => 'concluida', 'data_conclusao' => self::d( -23 ) ) ) );
		self::check(
			DL_Crud::save_record(
				'os',
				0,
				array( 'equipamento_id' => $eq['GU-01'], 'tipo' => 'corretiva', 'prioridade' => 'alta', 'status' => 'aguardando_peca', 'tecnico' => 'Valdir', 'data_abertura' => self::d( -2 ), 'data_previsao' => self::d( 3 ), 'defeito' => 'Motor aquecendo e cabo de aço com desgaste', 'diagnostico' => 'Troca do cabo de aço. Peça encomendada.' ),
				array( array( 'ref_id' => $prod['PC-05'], 'qtd' => 1, 'valor_unit' => 46 ) )
			)
		);
		self::check( DL_Crud::save_record( 'os', 0, array( 'equipamento_id' => $eq['CP-02'], 'tipo' => 'revisao', 'prioridade' => 'normal', 'status' => 'aberta', 'tecnico' => 'Valdir', 'data_abertura' => $t, 'defeito' => 'Revisão de retorno: sapata com folga' ) ) );

		/* vendas */
		$v1 = self::check( DL_Crud::save_record( 'vendas', 0, array( 'cliente_id' => $cli[3]['id'], 'status' => 'orcamento', 'data_venda' => self::d( -8 ), 'forma_pagamento' => 'pix', 'parcelas' => 1, 'primeiro_vencimento' => self::d( -8 ) ), array( array( 'ref_id' => $prod['PC-06'], 'qtd' => 2, 'valor_unit' => 290 ), array( 'ref_id' => $prod['PC-03'], 'qtd' => 4, 'valor_unit' => 28 ) ) ) );
		self::check( DL_Crud::save_record( 'vendas', $v1, array( 'status' => 'confirmada' ) ) );
		self::check( DL_Crud::save_record( 'vendas', 0, array( 'cliente_id' => $cli[5]['id'], 'status' => 'orcamento', 'forma_pagamento' => 'boleto', 'parcelas' => 2, 'primeiro_vencimento' => self::d( 10 ) ), array( array( 'ref_id' => $prod['PC-02'], 'qtd' => 3, 'valor_unit' => 62 ), array( 'ref_id' => $prod['SV-01'], 'qtd' => 8, 'valor_unit' => 85 ) ) ) );

		/* despesas do mês e dos meses anteriores (fluxo de caixa) */
		for ( $mth = 5; $mth >= 0; $mth-- ) {
			$base = date( 'Y-m', strtotime( self::$t . " -{$mth} months" ) );
			foreach ( array( array( 'Aluguel do galpão', 'aluguel', 4800, '05', $forn[2] ), array( 'Combustível dos caminhões', 'combustivel', 1450 + 70 * $mth, '15', $forn[1] ), array( 'Peças de reposição', 'pecas', 780 + 45 * $mth, '20', $forn[0] ) ) as $x ) {
				$due = $base . '-' . $x[3];
				$ids = DL_Finance::create_installments( array( 'tipo' => 'pagar', 'descricao' => $x[0] . ' — ' . dl_date( $due ), 'categoria' => $x[1], 'fornecedor_id' => $x[4], 'forma_pagamento' => 'boleto' ), $x[2], 1, $due );
				if ( $due <= self::$t ) {
					DL_Finance::pay( DL_DB::get( 'financeiro', $ids[0] ), $due, $x[2], 0, 0, 0, 'boleto' );
				}
			}
		}
		DL_Finance::create_installments( array( 'tipo' => 'pagar', 'descricao' => 'Revisão do caminhão Munck', 'categoria' => 'manutencao', 'fornecedor_id' => $forn[0], 'forma_pagamento' => 'boleto' ), 2350, 2, self::d( 4 ) );
	}
}
