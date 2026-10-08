<?php
/**
 * Serviço genérico de cadastros: busca, filtros, gravação e exclusão a partir das
 * definições de DL_Modules. Usado pela API do sistema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Crud {

	const PER_PAGE = 30;

	/** Monta WHERE a partir da busca e dos filtros. */
	public static function build_where( $def, $req ) {
		global $wpdb;
		$where = array( '1=1' );
		$q     = isset( $req['s'] ) ? sanitize_text_field( wp_unslash( $req['s'] ) ) : '';
		if ( '' !== $q ) {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$or   = array();
			foreach ( $def['fields'] as $name => $f ) {
				if ( ! empty( $f['search'] ) ) {
					$or[] = $wpdb->prepare( "`{$name}` LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL
				}
			}
			if ( isset( $def['fields']['cliente_id'] ) ) {
				$digits = dl_digits( $q );
				$or[]   = $wpdb->prepare( 'cliente_id IN (SELECT id FROM ' . dl_table( 'clientes' ) . ' WHERE nome LIKE %s OR fantasia LIKE %s OR documento LIKE %s)', $like, $like, '%' . $wpdb->esc_like( $digits ? $digits : $q ) . '%' );
			}
			if ( isset( $def['fields']['equipamento_id'] ) ) {
				$or[] = $wpdb->prepare( 'equipamento_id IN (SELECT id FROM ' . dl_table( 'equipamentos' ) . ' WHERE nome LIKE %s OR codigo LIKE %s)', $like, $like );
			}
			if ( $or ) {
				$where[] = '(' . implode( ' OR ', $or ) . ')';
			}
		}
		foreach ( $def['filters'] ?? array() as $filter ) {
			if ( isset( $req[ 'f_' . $filter ] ) && '' !== $req[ 'f_' . $filter ] ) {
				$value  = sanitize_text_field( wp_unslash( $req[ 'f_' . $filter ] ) );
				$custom = apply_filters( 'dl_filter_where_' . $def['key'], null, $filter, $value );
				$where[] = null !== $custom ? $custom : $wpdb->prepare( "`{$filter}` = %s", $value ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
		}
		if ( ! empty( $def['date_filter'] ) ) {
			$col = $def['date_filter'];
			if ( ! empty( $req['de'] ) ) {
				$where[] = $wpdb->prepare( "`{$col}` >= %s", sanitize_text_field( wp_unslash( $req['de'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			if ( ! empty( $req['ate'] ) ) {
				$where[] = $wpdb->prepare( "`{$col}` <= %s", sanitize_text_field( wp_unslash( $req['ate'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
		}
		return implode( ' AND ', $where );
	}

	/**
	 * Página de registros com valores formatados para exibição.
	 *
	 * @return array ['total'=>int, 'pages'=>int, 'rows'=>[['id'=>, 'raw'=>[], 'cells'=>[]]]]
	 */
	public static function query( $def, $req, $per_page = self::PER_PAGE ) {
		global $wpdb;
		$where  = self::build_where( $def, $req );
		$table  = dl_table( $def['table'] );
		$paged  = max( 1, absint( $req['page'] ?? 1 ) );
		$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$offset = ( $paged - 1 ) * $per_page;
		$rows   = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY {$def['order']} LIMIT {$offset}, " . (int) $per_page, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out    = array();
		foreach ( $rows as $row ) {
			$row   = apply_filters( 'dl_list_row_' . $def['key'], $row );
			$cells = array();
			foreach ( $def['fields'] as $name => $f ) {
				if ( ! empty( $f['list'] ) ) {
					$cells[ $name ] = self::display_value( $f, $row[ $name ] ?? '' );
				}
			}
			$out[] = array( 'id' => (int) $row['id'], 'raw' => $row, 'cells' => $cells );
		}
		return array(
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
			'page'  => $paged,
			'rows'  => $out,
			'where' => $where,
		);
	}

	/** Valor legível de um campo (texto puro). */
	public static function display_value( $f, $value ) {
		switch ( $f['type'] ) {
			case 'money':
			case 'readonly_money':
				return dl_money( $value );
			case 'decimal':
				return dl_num( $value, 2 );
			case 'date':
			case 'readonly_date':
				return dl_date( $value );
			case 'checkbox':
				return $value ? 'Sim' : '';
			case 'document':
				return dl_format_document( $value );
			case 'relation':
			case 'readonly_relation':
				return DL_DB::label( $f['rel'], (int) $value, $f['rel_label'] ?? 'nome' );
			case 'select':
			case 'readonly_status':
				$opts = self::field_options( $f );
				if ( isset( $opts[ $value ] ) ) {
					return $opts[ $value ];
				}
				$extra = array( 'atrasado' => 'Em atraso', 'vencido' => 'Vencido', 'locado' => 'Locado' );
				return $extra[ $value ] ?? (string) $value;
			default:
				return (string) $value;
		}
	}

	public static function field_options( $f ) {
		if ( isset( $f['options'] ) ) {
			return $f['options'];
		}
		if ( 'checkbox' === $f['type'] ) {
			return array( '1' => 'Sim', '0' => 'Não' );
		}
		return array();
	}

	/** Converte os campos recebidos para valores de banco conforme o tipo. */
	public static function sanitize( $def, $input, $is_new ) {
		$data = array();
		foreach ( $def['fields'] as $name => $f ) {
			$type = $f['type'];
			if ( 0 === strpos( $type, 'readonly' ) || ( ! empty( $f['readonly_edit'] ) && ! $is_new ) ) {
				continue;
			}
			if ( ! array_key_exists( $name, $input ) ) {
				continue;
			}
			$v = $input[ $name ];
			if ( is_array( $v ) ) {
				continue;
			}
			switch ( $type ) {
				case 'money':
				case 'decimal':
					$data[ $name ] = round( dl_decimal( $v ), 3 );
					break;
				case 'int':
				case 'relation':
				case 'media':
				case 'user':
					$data[ $name ] = (int) $v;
					break;
				case 'checkbox':
					$data[ $name ] = ( $v && 'false' !== $v ) ? 1 : 0;
					break;
				case 'date':
					$data[ $name ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : null;
					break;
				case 'email':
					$data[ $name ] = sanitize_email( $v );
					break;
				case 'url':
					$data[ $name ] = esc_url_raw( $v );
					break;
				case 'document':
				case 'cep':
					$data[ $name ] = dl_digits( $v );
					break;
				case 'textarea':
					$data[ $name ] = sanitize_textarea_field( $v );
					break;
				case 'select':
					$opts          = self::field_options( $f );
					$data[ $name ] = array_key_exists( $v, $opts ) ? $v : ( $f['default'] ?? array_key_first( $opts ) );
					break;
				default:
					$data[ $name ] = sanitize_text_field( $v );
			}
		}
		return $data;
	}

	/**
	 * Cria ou altera um registro (e seus itens, se o módulo tiver).
	 *
	 * @return int|WP_Error id gravado.
	 */
	public static function save_record( $key, $id, array $input, $items = null ) {
		$def = DL_Modules::get( $key );
		if ( ! $def ) {
			return new WP_Error( 'modulo', 'Módulo inválido.' );
		}
		if ( ! empty( $def['no_create'] ) && ! $id ) {
			return new WP_Error( 'modulo', 'Este cadastro não pode ser criado manualmente.' );
		}
		$old = $id ? DL_DB::get( $def['table'], $id ) : null;
		if ( $id && ! $old ) {
			return new WP_Error( 'nao_encontrado', 'Registro não encontrado.' );
		}
		$data = self::sanitize( $def, $input, ! $id );

		foreach ( $def['fields'] as $name => $f ) {
			$missing = ! $id ? ( ! isset( $data[ $name ] ) || '' === $data[ $name ] ) : ( array_key_exists( $name, $data ) && ( '' === $data[ $name ] || null === $data[ $name ] ) );
			if ( ! empty( $f['required'] ) && 0 !== strpos( $f['type'], 'readonly' ) && ( $missing || ( 'relation' === $f['type'] && array_key_exists( $name, $data ) && ! $data[ $name ] ) ) ) {
				return new WP_Error( 'obrigatorio', 'Preencha o campo obrigatório: ' . $f['label'] . '.' );
			}
		}
		if ( ! $id ) {
			foreach ( $def['fields'] as $name => $f ) {
				if ( ! array_key_exists( $name, $data ) && isset( $f['default'] ) ) {
					$data[ $name ] = $f['default'];
				}
			}
		}

		$data = apply_filters( 'dl_before_save_' . $key, $data, $id, $old );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( $id ) {
			DL_DB::update( $def['table'], $id, $data );
			dl_log( $key, $id, 'Alterado' );
		} else {
			$id = DL_DB::insert( $def['table'], $data );
			if ( ! $id ) {
				global $wpdb;
				return new WP_Error( 'db', 'Não foi possível salvar: ' . $wpdb->last_error );
			}
			dl_log( $key, $id, 'Criado' );
		}

		if ( ! empty( $def['items'] ) && is_array( $items ) ) {
			$result = DL_Items::save_items( $def, $id, $items );
			if ( is_wp_error( $result ) ) {
				dl_notice( $result->get_error_message(), 'error' );
			}
		}

		do_action( 'dl_after_save_' . $key, $id, $data, $old );
		return $id;
	}

	/** @return true|WP_Error */
	public static function delete_record( $key, $id ) {
		$def = DL_Modules::get( $key );
		$row = $def ? DL_DB::get( $def['table'], $id ) : null;
		if ( ! $row ) {
			return new WP_Error( 'nao_encontrado', 'Registro não encontrado.' );
		}
		$can = apply_filters( 'dl_can_delete_' . $key, true, $row );
		if ( true !== $can ) {
			return new WP_Error( 'bloqueado', is_string( $can ) ? $can : 'Não é possível excluir.' );
		}
		do_action( 'dl_before_delete_' . $key, $row );
		if ( ! empty( $def['doc_tipo'] ) ) {
			global $wpdb;
			$wpdb->delete( dl_table( 'itens' ), array( 'doc_tipo' => $def['doc_tipo'], 'doc_id' => $id ) );
		}
		DL_DB::delete( $def['table'], $id );
		dl_log( $key, $id, 'Excluído', wp_json_encode( array( 'nome' => $row['nome'] ?? ( $row['numero'] ?? '' ) ) ) );
		return true;
	}

	/** Motivo pelo qual o registro não pode ser excluído (ou true). */
	public static function can_delete( $key, $row ) {
		return apply_filters( 'dl_can_delete_' . $key, true, $row );
	}

	/** Histórico de alterações de um registro. */
	public static function history( $entity, $id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'historico' ) . ' WHERE entidade = %s AND entidade_id = %d ORDER BY id DESC LIMIT 40', $entity, $id ), ARRAY_A );
		$out  = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'acao'     => $r['acao'],
				'detalhes' => $r['detalhes'],
				'quando'   => dl_datetime( $r['criado_em'] ),
				'usuario'  => $r['usuario_id'] ? get_the_author_meta( 'display_name', $r['usuario_id'] ) : 'sistema',
			);
		}
		return $out;
	}
}
