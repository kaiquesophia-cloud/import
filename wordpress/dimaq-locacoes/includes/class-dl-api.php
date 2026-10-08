<?php
/**
 * API interna do sistema (namespace dimaq/v1/app). Autenticação por cookie do WordPress
 * + nonce wp_rest; cada rota confere a permissão do módulo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_API {

	const NS = 'dimaq/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	private static function route( $path, $methods, $callback, $cap = 'dl_operar' ) {
		register_rest_route(
			self::NS,
			'/app/' . $path,
			array(
				'methods'             => $methods,
				'callback'            => function ( WP_REST_Request $req ) use ( $callback ) {
					$result = call_user_func( $callback, $req );
					if ( is_wp_error( $result ) ) {
						$codes = array( 'perm' => 403, 'nao_encontrado' => 404 );
						$result->add_data( array( 'status' => $codes[ $result->get_error_code() ] ?? 400 ) );
						return $result;
					}
					if ( is_array( $result ) ) {
						$notices = dl_take_notices();
						if ( $notices ) {
							$result['avisos'] = $notices;
						}
					}
					return rest_ensure_response( $result );
				},
				'permission_callback' => function () use ( $cap ) {
					return is_user_logged_in() && current_user_can( $cap );
				},
			)
		);
	}

	public static function routes() {
		self::route( 'dashboard', 'GET', array( __CLASS__, 'dashboard' ) );
		self::route( 'timeline', 'GET', array( __CLASS__, 'timeline' ) );
		self::route( 'search', 'GET', array( __CLASS__, 'search' ) );
		self::route( 'lookup/(?P<table>[a-z_]+)', 'GET', array( __CLASS__, 'lookup' ) );
		self::route( 'quote', 'GET', array( __CLASS__, 'quote' ) );
		self::route( 'list/(?P<module>[a-z_]+)', 'GET', array( __CLASS__, 'list_records' ) );
		self::route( 'new/(?P<module>[a-z_]+)', 'GET', array( __CLASS__, 'new_record' ) );
		self::route( 'record/(?P<module>[a-z_]+)/(?P<id>\d+)', 'GET', array( __CLASS__, 'get_record' ) );
		self::route( 'record/(?P<module>[a-z_]+)(?:/(?P<id>\d+))?', 'POST', array( __CLASS__, 'save_record' ) );
		self::route( 'record/(?P<module>[a-z_]+)/(?P<id>\d+)', 'DELETE', array( __CLASS__, 'delete_record' ) );
		self::route( 'contract/(?P<id>\d+)/action', 'POST', array( __CLASS__, 'contract_action' ) );
		self::route( 'contract/(?P<id>\d+)/status', 'POST', array( __CLASS__, 'contract_move_status' ) );
		self::route( 'contract/(?P<id>\d+)/medicao', 'GET', array( __CLASS__, 'measurement_preview' ), 'dl_financeiro' );
		self::route( 'finance/(?P<id>\d+)/action', 'POST', array( __CLASS__, 'finance_action' ), 'dl_financeiro' );
		self::route( 'stock/(?P<id>\d+)', 'POST', array( __CLASS__, 'stock_move' ) );
		self::route( 'fiscal', 'POST', array( __CLASS__, 'fiscal_emit' ), 'dl_financeiro' );
		self::route( 'client/(?P<id>\d+)/access', 'POST', array( __CLASS__, 'client_access' ) );
		self::route( 'report/(?P<key>[a-z_]+)', 'GET', array( __CLASS__, 'report' ) );
		self::route( 'settings', 'GET', array( __CLASS__, 'get_settings' ), 'dl_config' );
		self::route( 'settings', 'POST', array( __CLASS__, 'save_settings' ), 'dl_config' );
	}

	/* ------------------------------------------------------------- utilidades */

	private static function module( $key ) {
		$def = DL_Modules::get( $key );
		if ( ! $def ) {
			return new WP_Error( 'modulo', 'Módulo inválido.' );
		}
		if ( ! current_user_can( $def['cap'] ) ) {
			return new WP_Error( 'perm', 'Sem permissão para este módulo.' );
		}
		return $def;
	}

	private static function body( WP_REST_Request $req ) {
		$b = $req->get_json_params();
		return is_array( $b ) ? $b : (array) $req->get_body_params();
	}

	private static function date_param( $v, $default ) {
		return is_string( $v ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : $default;
	}

	/** Esquema dos módulos para a interface montar listas e formulários. */
	public static function schema() {
		$out = array();
		foreach ( DL_Modules::all() as $key => $def ) {
			if ( ! current_user_can( $def['cap'] ) ) {
				continue;
			}
			$fields = array();
			foreach ( $def['fields'] as $name => $f ) {
				$f2 = array_intersect_key( $f, array_flip( array( 'label', 'type', 'required', 'list', 'help', 'section', 'width', 'rel', 'rel_label', 'default', 'readonly_edit', 'badge' ) ) );
				if ( isset( $f['options'] ) ) {
					$f2['options'] = array();
					foreach ( $f['options'] as $v => $l ) {
						$f2['options'][] = array( 'value' => (string) $v, 'label' => $l );
					}
				}
				$fields[ $name ] = $f2;
			}
			$filters = array();
			foreach ( $def['filters'] ?? array() as $fk ) {
				$f    = $def['fields'][ $fk ];
				$opts = array();
				foreach ( DL_Crud::field_options( $f ) as $v => $l ) {
					if ( '' !== (string) $v ) {
						$opts[] = array( 'value' => (string) $v, 'label' => $l );
					}
				}
				if ( 'contratos' === $key && 'status' === $fk ) {
					$opts[] = array( 'value' => 'atrasado', 'label' => 'Em atraso' );
				}
				if ( 'financeiro' === $key && 'status' === $fk ) {
					$opts[] = array( 'value' => 'vencido', 'label' => 'Vencido' );
					$opts[] = array( 'value' => 'hoje', 'label' => 'Vence hoje' );
				}
				$filters[] = array( 'field' => $fk, 'label' => $f['label'], 'type' => $f['type'], 'rel' => $f['rel'] ?? null, 'options' => $opts );
			}
			$out[ $key ] = array(
				'key'         => $key,
				'singular'    => $def['singular'],
				'plural'      => $def['plural'],
				'entity'      => $def['entity'] ?? null,
				'items'       => $def['items'] ?? null,
				'no_create'   => ! empty( $def['no_create'] ),
				'date_filter' => $def['date_filter'] ?? null,
				'fields'      => $fields,
				'filters'     => $filters,
			);
		}
		return $out;
	}

	/** Valores de exibição para os campos de relação de um registro. */
	private static function labels( $def, $row ) {
		$out = array();
		foreach ( $def['fields'] as $name => $f ) {
			if ( in_array( $f['type'], array( 'relation', 'readonly_relation' ), true ) && ! empty( $row[ $name ] ) ) {
				$out[ $name ] = DL_DB::label( $f['rel'], (int) $row[ $name ], $f['rel_label'] ?? 'nome' );
			}
			if ( 'media' === $f['type'] && ! empty( $row[ $name ] ) ) {
				$out[ $name ] = wp_get_attachment_image_url( (int) $row[ $name ], 'medium' );
			}
			if ( 'user' === $f['type'] && ! empty( $row[ $name ] ) ) {
				$u            = get_userdata( (int) $row[ $name ] );
				$out[ $name ] = $u ? $u->display_name . ' (' . $u->user_email . ')' : '';
			}
		}
		return $out;
	}

	private static function docs( array $types, $id ) {
		$names = array(
			'orcamento' => 'Orçamento',
			'contrato'  => 'Contrato de locação',
			'checklist' => 'Checklist de saída e retorno',
			'devolucao' => 'Devolução de equipamento (para a retirada)',
			'medicao'   => 'Boletim de medição',
			'fatura'    => 'Fatura de locação',
			'os'        => 'Ordem de serviço',
			'venda'     => 'Pedido de venda',
			'recibo'    => 'Recibo',
		);
		$out = array();
		foreach ( $types as $t ) {
			$out[] = array( 'tipo' => $t, 'nome' => $names[ $t ], 'url' => DL_Documents::url( $t, $id ), 'publico' => DL_Documents::public_url( $t, $id ) );
		}
		return $out;
	}

	/* ----------------------------------------------------------------- painel */

	public static function dashboard() {
		return array(
			'kpis'       => DL_Dashboard::kpis(),
			'ativos'     => DL_Dashboard::active(),
			'funil'      => DL_Dashboard::pipeline(),
			'agenda'     => DL_Dashboard::agenda(),
			'receita'    => DL_Dashboard::revenue( 6 ),
			'categorias' => DL_Dashboard::by_category(),
			'alertas'    => DL_Dashboard::alerts(),
			'gerado_em'  => current_time( 'H:i' ),
		);
	}

	public static function timeline( WP_REST_Request $req ) {
		return DL_Dashboard::timeline( self::date_param( $req['de'], dl_add_days( dl_today(), -3 ) ), absint( $req['dias'] ? $req['dias'] : 30 ), absint( $req['cat'] ), sanitize_text_field( (string) $req['q'] ) );
	}

	/** Busca geral do topo: contratos, clientes e equipamentos. */
	public static function search( WP_REST_Request $req ) {
		global $wpdb;
		$q = trim( sanitize_text_field( (string) $req['q'] ) );
		if ( mb_strlen( $q ) < 2 ) {
			return array( 'resultados' => array() );
		}
		$like   = '%' . $wpdb->esc_like( $q ) . '%';
		$digits = dl_digits( $q );
		$out    = array();
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT c.id, c.numero, c.status, c.data_prev_devolucao, k.nome FROM ' . dl_table( 'contratos' ) . ' c LEFT JOIN ' . dl_table( 'clientes' ) . ' k ON k.id = c.cliente_id WHERE c.numero LIKE %s OR k.nome LIKE %s OR c.local_obra LIKE %s ORDER BY c.id DESC LIMIT 8', $like, $like, $like ), ARRAY_A );
		$st     = dl_statuses( 'contrato' );
		foreach ( $rows as $r ) {
			$out[] = array( 'tipo' => 'Locação', 'titulo' => $r['numero'] . ' — ' . $r['nome'], 'sub' => ( $st[ $r['status'] ] ?? $r['status'] ) . ' · devolução ' . dl_date( $r['data_prev_devolucao'] ), 'rota' => 'contratos/' . $r['id'] );
		}
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, nome, documento, telefone FROM ' . dl_table( 'clientes' ) . ' WHERE nome LIKE %s OR fantasia LIKE %s OR email LIKE %s' . ( $digits ? $wpdb->prepare( ' OR documento LIKE %s OR telefone LIKE %s', '%' . $digits . '%', $like ) : '' ) . ' ORDER BY nome LIMIT 6', $like, $like, $like ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $rows as $r ) {
			$out[] = array( 'tipo' => 'Cliente', 'titulo' => $r['nome'], 'sub' => trim( dl_format_document( $r['documento'] ) . ' ' . $r['telefone'] ), 'rota' => 'clientes/' . $r['id'] );
		}
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, codigo, nome, marca, modelo FROM ' . dl_table( 'equipamentos' ) . ' WHERE nome LIKE %s OR codigo LIKE %s OR numero_serie LIKE %s OR modelo LIKE %s ORDER BY nome LIMIT 6', $like, $like, $like, $like ), ARRAY_A );
		foreach ( $rows as $r ) {
			$out[] = array( 'tipo' => 'Equipamento', 'titulo' => trim( $r['codigo'] . ' ' . $r['nome'] ), 'sub' => trim( $r['marca'] . ' ' . $r['modelo'] ), 'rota' => 'equipamentos/' . $r['id'] );
		}
		return array( 'resultados' => $out );
	}

	/** Autocompletar de relações. */
	public static function lookup( WP_REST_Request $req ) {
		global $wpdb;
		$table   = $req['table'];
		$allowed = array( 'clientes', 'fornecedores', 'categorias', 'equipamentos', 'produtos', 'contratos' );
		if ( ! in_array( $table, $allowed, true ) ) {
			return new WP_Error( 'tabela', 'Tabela inválida.' );
		}
		$q    = trim( sanitize_text_field( (string) $req['q'] ) );
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$t    = dl_table( $table );
		switch ( $table ) {
			case 'clientes':
				$d    = dl_digits( $q );
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, nome AS label, documento, telefone, whatsapp, bloqueado FROM {$t} WHERE nome LIKE %s OR fantasia LIKE %s OR documento LIKE %s ORDER BY nome LIMIT 20", $like, $like, '%' . ( $d ? $d : $wpdb->esc_like( $q ) ) . '%' ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as &$r ) {
					$r['sub'] = trim( dl_format_document( $r['documento'] ) . ' ' . ( $r['whatsapp'] ? $r['whatsapp'] : $r['telefone'] ) ) . ( $r['bloqueado'] ? ' · BLOQUEADO' : '' );
				}
				break;
			case 'equipamentos':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, codigo, nome, controle, qtd_total, status, valor_diaria, valor_semanal, valor_quinzenal, valor_mensal, valor_caucao FROM {$t} WHERE status <> 'inativo' AND (nome LIKE %s OR codigo LIKE %s OR modelo LIKE %s) ORDER BY nome LIMIT 25", $like, $like, $like ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as &$r ) {
					$r['label'] = trim( $r['codigo'] . ' ' . $r['nome'] );
					$r['sub']   = ( 'quantidade' === $r['controle'] ? 'frota ' . $r['qtd_total'] . ' · ' : '' ) . dl_money( $r['valor_diaria'] ) . '/dia' . ( 'manutencao' === $r['status'] ? ' · EM MANUTENÇÃO' : '' );
				}
				break;
			case 'produtos':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, codigo, nome, tipo, unidade, preco_venda, estoque_atual FROM {$t} WHERE ativo = 1 AND (nome LIKE %s OR codigo LIKE %s) ORDER BY nome LIMIT 25", $like, $like ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as &$r ) {
					$r['label'] = trim( $r['codigo'] . ' ' . $r['nome'] );
					$r['sub']   = dl_money( $r['preco_venda'] ) . ( 'servico' === $r['tipo'] ? ' · serviço' : ' · estoque ' . dl_num( $r['estoque_atual'], 0 ) . ' ' . $r['unidade'] );
				}
				break;
			case 'contratos':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, numero AS label FROM {$t} WHERE numero LIKE %s ORDER BY id DESC LIMIT 20", $like ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				break;
			default:
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, nome AS label FROM {$t} WHERE nome LIKE %s ORDER BY nome LIMIT 25", $like ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		unset( $r );
		return array( 'itens' => $rows );
	}

	/** Disponibilidade e melhor preço de um equipamento num período. */
	public static function quote( WP_REST_Request $req ) {
		$e = DL_DB::get( 'equipamentos', absint( $req['equip'] ) );
		if ( ! $e ) {
			return new WP_Error( 'equip', 'Equipamento não encontrado.' );
		}
		$start = self::date_param( $req['inicio'], dl_today() );
		$end   = max( $start, self::date_param( $req['fim'], $start ) );
		$days  = DL_Contracts::rental_days( $start, $end );
		$best  = DL_Pricing::best_price( DL_Contracts::rates( $e ), $days );
		return array(
			'dias'      => $days,
			'livres'    => DL_Availability::available( $e['id'], $start, $end, absint( $req['excluir'] ) ),
			'frota'     => 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1,
			'status'    => $e['status'],
			'melhor'    => $best,
			'tarifas'   => DL_Contracts::rates( $e ),
			'caucao'    => (float) $e['valor_caucao'],
		);
	}

	/* ---------------------------------------------------------------- registros */

	public static function list_records( WP_REST_Request $req ) {
		$def = self::module( $req['module'] );
		if ( is_wp_error( $def ) ) {
			return $def;
		}
		$params = $req->get_query_params();
		$per    = min( 2000, max( 5, absint( $params['por_pagina'] ?? DL_Crud::PER_PAGE ) ) );
		$res    = DL_Crud::query( $def, $params, $per );
		if ( 'financeiro' === $def['key'] ) {
			$res['totais'] = DL_Finance::totals( $res['where'] );
		}
		unset( $res['where'] );
		return $res;
	}

	public static function new_record( WP_REST_Request $req ) {
		$def = self::module( $req['module'] );
		if ( is_wp_error( $def ) ) {
			return $def;
		}
		$row = array();
		foreach ( $def['fields'] as $name => $f ) {
			$row[ $name ] = isset( $req[ $name ] ) ? sanitize_text_field( (string) $req[ $name ] ) : ( $f['default'] ?? '' );
		}
		$row = apply_filters( 'dl_new_row_' . $def['key'], $row );
		foreach ( $def['fields'] as $name => $f ) {
			// Seleção sem valor válido começa na primeira opção, como o banco gravaria.
			if ( 'select' === $f['type'] && ! array_key_exists( (string) $row[ $name ], $f['options'] ) ) {
				$row[ $name ] = (string) array_key_first( $f['options'] );
			}
		}
		return array( 'registro' => $row, 'rotulos' => self::labels( $def, $row ) );
	}

	public static function get_record( WP_REST_Request $req ) {
		$def = self::module( $req['module'] );
		if ( is_wp_error( $def ) ) {
			return $def;
		}
		$id  = (int) $req['id'];
		$row = DL_DB::get( $def['table'], $id );
		if ( ! $row ) {
			return new WP_Error( 'nao_encontrado', 'Registro não encontrado.' );
		}
		$fin = current_user_can( 'dl_financeiro' );
		$can = DL_Crud::can_delete( $def['key'], $row );
		$out = array(
			'registro'  => $row,
			'rotulos'   => self::labels( $def, $row ),
			'historico' => DL_Crud::history( $def['key'], $id ),
			'excluir'   => true === $can ? true : ( is_string( $can ) ? $can : false ),
		);
		if ( ! empty( $def['doc_tipo'] ) ) {
			$items = DL_Items::get( $def['doc_tipo'], $id );
			foreach ( $items as &$it ) {
				if ( 'equipamento' === $it['ref_tipo'] && $it['ref_id'] ) {
					$e = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
					if ( $e ) {
						$it['tarifas'] = DL_Contracts::rates( $e );
						$it['nome']    = $e['nome'];
					}
				}
			}
			unset( $it );
			$out['itens'] = array_values( array_filter( $items, function ( $i ) { return 'adicional' !== $i['ref_tipo']; } ) );
			$out['adicionais'] = array_values( array_filter( $items, function ( $i ) { return 'adicional' === $i['ref_tipo']; } ) );
		}
		switch ( $def['key'] ) {
			case 'contratos':
				$out['cartao']      = DL_Dashboard::card( $row );
				$out['alertas']     = DL_Contracts::client_alerts( $row['cliente_id'] );
				$out['acoes']       = DL_Contracts::available_actions( $row );
				$out['documentos']  = self::docs( array( 'orcamento', 'contrato', 'checklist', 'devolucao', 'fatura' ), $id );
				$out['multa_atraso_pct'] = (float) dl_opt( 'multa_atraso_pct', 0 );
				$cli                = DL_DB::get( 'clientes', (int) $row['cliente_id'] );
				$out['cliente']     = $cli ? array( 'email' => $cli['email'], 'telefone' => $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : null;
				if ( DL_Measurement::is_measured( $row ) ) {
					$out['medicoes'] = array();
					foreach ( DL_Measurement::list_for( $id ) as $m ) {
						$out['medicoes'][] = array( 'id' => (int) $m['id'], 'numero' => (int) $m['numero'], 'inicio' => $m['inicio'], 'fim' => $m['fim'], 'total' => (float) $m['total'], 'status' => $m['status'], 'doc' => DL_Documents::url( 'medicao', $m['id'] ), 'publico' => DL_Documents::public_url( 'medicao', $m['id'] ) );
					}
					$out['medicao_sugestao'] = in_array( $row['status'], array( 'ativo', 'encerrado' ), true ) ? DL_Measurement::suggestion( $row ) : null;
				}
				if ( $fin ) {
					$out['cobrancas'] = DL_Finance::linked( 'contrato', $id );
					$out['notas']     = DL_Fiscal::linked( 'contrato', $id );
					$out['nota_sugestao'] = DL_Fiscal::suggestion( 'contrato', $row );
				}
				break;
			case 'equipamentos':
				$out['info'] = DL_Admin::equipment_info( $row );
				break;
			case 'clientes':
				$out['info'] = DL_Admin::client_info( $row );
				break;
			case 'produtos':
				$out['movimentos'] = DL_Stock::history( $id );
				break;
			case 'os':
			case 'vendas':
				$origin            = 'os' === $def['key'] ? 'os' : 'venda';
				$out['documentos'] = self::docs( array( $origin ), $id );
				if ( $fin ) {
					$out['cobrancas']     = DL_Finance::linked( $origin, $id );
					$out['notas']         = DL_Fiscal::linked( $origin, $id );
					$out['nota_sugestao'] = DL_Fiscal::suggestion( $origin, $row );
				}
				break;
			case 'financeiro':
				$out['situacao']  = DL_Finance::status_of( $row );
				$out['encargos']  = DL_Finance::suggested_charges( $row );
				$out['restante']  = DL_Finance::remaining( $row );
				$map              = array( 'contrato' => 'contratos', 'venda' => 'vendas', 'os' => 'os' );
				$out['origem']    = isset( $map[ $row['origem'] ] ) ? array( 'rota' => $map[ $row['origem'] ] . '/' . $row['origem_id'], 'nome' => DL_DB::label( 'contrato' === $row['origem'] ? 'contratos' : ( 'venda' === $row['origem'] ? 'vendas' : 'ordens_servico' ), (int) $row['origem_id'], 'numero' ) ) : null;
				$out['documentos'] = 'pago' === $row['status'] && 'receber' === $row['tipo'] ? self::docs( array( 'recibo' ), $id ) : array();
				break;
		}
		return $out;
	}

	public static function save_record( WP_REST_Request $req ) {
		$def = self::module( $req['module'] );
		if ( is_wp_error( $def ) ) {
			return $def;
		}
		$body  = self::body( $req );
		$id    = absint( $req['id'] ?? 0 );
		$items = isset( $body['itens'] ) && is_array( $body['itens'] ) ? $body['itens'] : null;
		$res   = DL_Crud::save_record( $def['key'], $id, (array) ( $body['campos'] ?? array() ), $items );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$fake = new WP_REST_Request( 'GET' );
		$fake->set_url_params( array( 'module' => $def['key'], 'id' => $res ) );
		$out = self::get_record( $fake );
		$out['id']       = $res;
		$out['mensagem'] = $def['singular'] . ' salvo(a).';
		return $out;
	}

	public static function delete_record( WP_REST_Request $req ) {
		$def = self::module( $req['module'] );
		if ( is_wp_error( $def ) ) {
			return $def;
		}
		$r = DL_Crud::delete_record( $def['key'], (int) $req['id'] );
		return is_wp_error( $r ) ? $r : array( 'mensagem' => 'Registro excluído.' );
	}

	/* --------------------------------------------------------------- operações */

	public static function contract_action( WP_REST_Request $req ) {
		$c = DL_DB::get( 'contratos', (int) $req['id'] );
		if ( ! $c ) {
			return new WP_Error( 'nao_encontrado', 'Contrato não encontrado.' );
		}
		$body = self::body( $req );
		$op   = sanitize_key( $body['op'] ?? '' );
		if ( in_array( $op, array( 'faturar', 'medir', 'cancelar_medicao' ), true ) && ! current_user_can( 'dl_financeiro' ) ) {
			return new WP_Error( 'perm', 'Faturamento exige permissão do financeiro.' );
		}
		$r = DL_Contracts::perform( $c, $op, (array) ( $body['dados'] ?? array() ) );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		return array( 'mensagem' => $r['message'], 'id' => $r['id'] ?? (int) $c['id'] );
	}

	/**
	 * Arrastar no quadro: traduz a coluna de destino na ação correspondente.
	 * Entrega e devolução precisam de checklist, então a tela abre o formulário.
	 */
	public static function contract_move_status( WP_REST_Request $req ) {
		$c = DL_DB::get( 'contratos', (int) $req['id'] );
		if ( ! $c ) {
			return new WP_Error( 'nao_encontrado', 'Contrato não encontrado.' );
		}
		$to  = sanitize_key( self::body( $req )['para'] ?? '' );
		$map = array(
			'orcamento' => array( 'reservado' => 'voltar_orcamento' ),
			'reservado' => array( 'orcamento' => 'reservar', 'solicitacao' => 'reservar' ),
			'cancelado' => array( 'orcamento' => 'cancelar', 'solicitacao' => 'cancelar', 'reservado' => 'cancelar' ),
		);
		if ( 'orcamento' === $to && 'solicitacao' === $c['status'] ) {
			DL_DB::update( 'contratos', $c['id'], array( 'status' => 'orcamento' ) );
			dl_log( 'contratos', $c['id'], 'Pedido do site virou orçamento' );
			return array( 'mensagem' => 'Pedido movido para orçamentos.' );
		}
		$op = $map[ $to ][ $c['status'] ] ?? null;
		if ( ! $op ) {
			return new WP_Error( 'mover', 'Esta mudança não pode ser feita arrastando.' );
		}
		$r = DL_Contracts::perform( $c, $op, array() );
		return is_wp_error( $r ) ? $r : array( 'mensagem' => $r['message'] );
	}

	/** Prévia da medição de um período (nada é gravado). */
	public static function measurement_preview( WP_REST_Request $req ) {
		$c = DL_DB::get( 'contratos', (int) $req['id'] );
		if ( ! $c ) {
			return new WP_Error( 'nao_encontrado', 'Contrato não encontrado.' );
		}
		$sug   = DL_Measurement::suggestion( $c );
		$start = self::date_param( $req['inicio'], $sug['inicio'] );
		$end   = self::date_param( $req['fim'], $sug['fim'] );
		$calc  = DL_Measurement::calculate( $c, $start, $end );
		return is_wp_error( $calc ) ? $calc : $calc + array( 'sugestao' => $sug );
	}

	public static function finance_action( WP_REST_Request $req ) {
		$f = DL_DB::get( 'financeiro', (int) $req['id'] );
		if ( ! $f ) {
			return new WP_Error( 'nao_encontrado', 'Lançamento não encontrado.' );
		}
		$body = self::body( $req );
		$r    = DL_Finance::perform( $f, sanitize_key( $body['op'] ?? '' ), (array) ( $body['dados'] ?? array() ) );
		return is_wp_error( $r ) ? $r : array( 'mensagem' => $r['message'] );
	}

	public static function stock_move( WP_REST_Request $req ) {
		$p = DL_DB::get( 'produtos', (int) $req['id'] );
		if ( ! $p ) {
			return new WP_Error( 'nao_encontrado', 'Produto não encontrado.' );
		}
		if ( 'servico' === $p['tipo'] ) {
			return new WP_Error( 'servico', 'Serviço não tem estoque.' );
		}
		$b    = self::body( $req );
		$type = in_array( $b['tipo'] ?? '', array( 'entrada', 'saida', 'ajuste' ), true ) ? $b['tipo'] : 'entrada';
		$qty  = dl_decimal( $b['qtd'] ?? 0 );
		if ( $qty <= 0 && 'ajuste' !== $type ) {
			return new WP_Error( 'qtd', 'Informe a quantidade.' );
		}
		DL_Stock::move( $p['id'], $type, $qty, 'manual', 0, sanitize_text_field( $b['obs'] ?? '' ), dl_decimal( $b['custo'] ?? 0 ) );
		dl_log( 'produtos', $p['id'], 'Estoque: ' . $type, dl_num( $qty, 3 ) );
		return array( 'mensagem' => 'Estoque atualizado.', 'estoque' => (float) DL_DB::get( 'produtos', $p['id'] )['estoque_atual'] );
	}

	public static function fiscal_emit( WP_REST_Request $req ) {
		$b      = self::body( $req );
		$origin = sanitize_key( $b['origem'] ?? '' );
		$map    = array( 'contrato' => 'contratos', 'os' => 'ordens_servico', 'venda' => 'vendas' );
		if ( ! isset( $map[ $origin ] ) ) {
			return new WP_Error( 'origem', 'Origem inválida.' );
		}
		$row = DL_DB::get( $map[ $origin ], absint( $b['origem_id'] ?? 0 ) );
		if ( ! $row ) {
			return new WP_Error( 'nao_encontrado', 'Documento não encontrado.' );
		}
		$value = round( dl_decimal( $b['valor'] ?? 0 ), 2 );
		if ( $value <= 0 ) {
			return new WP_Error( 'valor', 'Informe o valor da nota.' );
		}
		$note = DL_DB::get( 'notas', DL_Fiscal::emit( $origin, $row, $value, sanitize_text_field( $b['discriminacao'] ?? '' ) ) );
		$st   = dl_statuses( 'nota' );
		return array( 'mensagem' => strtoupper( $note['tipo'] ) . ': ' . ( $st[ $note['status'] ] ?? $note['status'] ) . ( $note['mensagem'] ? ' — ' . $note['mensagem'] : '' ), 'nota' => $note['id'] );
	}

	public static function client_access( WP_REST_Request $req ) {
		$cli = DL_DB::get( 'clientes', (int) $req['id'] );
		if ( ! $cli ) {
			return new WP_Error( 'nao_encontrado', 'Cliente não encontrado.' );
		}
		$r = DL_Admin::create_client_user( $cli );
		return is_wp_error( $r ) ? $r : array( 'mensagem' => $r['message'] );
	}

	public static function report( WP_REST_Request $req ) {
		$all = DL_Reports::reports();
		$key = $req['key'];
		if ( ! isset( $all[ $key ] ) || ! current_user_can( $all[ $key ][1] ) ) {
			return new WP_Error( 'relatorio', 'Relatório indisponível.' );
		}
		$from = self::date_param( $req['de'], current_time( 'Y-m-01' ) );
		$to   = max( $from, self::date_param( $req['ate'], current_time( 'Y-m-t' ) ) );
		return DL_Reports::build( $key, $from, $to ) + array( 'de' => $from, 'ate' => $to, 'titulo' => $all[ $key ][0] );
	}

	public static function get_settings() {
		$pages = array();
		foreach ( get_pages() as $p ) {
			$pages[] = array( 'value' => (string) $p->ID, 'label' => $p->post_title );
		}
		return array( 'secoes' => DL_Settings::fields(), 'valores' => DL_Settings::values(), 'paginas' => $pages );
	}

	public static function save_settings( WP_REST_Request $req ) {
		DL_Settings::save_values( (array) ( self::body( $req )['valores'] ?? array() ) );
		return array( 'mensagem' => 'Configurações salvas.', 'valores' => DL_Settings::values() );
	}
}
