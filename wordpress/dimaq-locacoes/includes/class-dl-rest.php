<?php
/**
 * API REST pública (catálogo e disponibilidade) — usada pelo site e por integrações.
 *
 *   GET /wp-json/dimaq/v1/equipamentos
 *   GET /wp-json/dimaq/v1/equipamentos/{id}/disponibilidade?inicio=AAAA-MM-DD&fim=AAAA-MM-DD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Rest {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			'dimaq/v1',
			'/equipamentos',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_equipment' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'categoria' => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);
		register_rest_route(
			'dimaq/v1',
			'/equipamentos/(?P<id>\d+)/disponibilidade',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'availability' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'     => array( 'sanitize_callback' => 'absint' ),
					'inicio' => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'is_date' ) ),
					'fim'    => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'is_date' ) ),
				),
			)
		);
	}

	public static function is_date( $v ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v );
	}

	private static function public_fields( $e ) {
		$show = (bool) dl_opt( 'mostrar_precos' );
		return array(
			'id'          => (int) $e['id'],
			'nome'        => $e['nome'],
			'categoria'   => DL_DB::label( 'categorias', (int) $e['categoria_id'] ),
			'marca'       => $e['marca'],
			'modelo'      => $e['modelo'],
			'descricao'   => $e['descricao'],
			'foto'        => $e['foto_id'] ? wp_get_attachment_image_url( (int) $e['foto_id'], 'large' ) : null,
			'precos'      => $show ? array(
				'diaria'    => (float) $e['valor_diaria'],
				'semanal'   => (float) $e['valor_semanal'],
				'quinzenal' => (float) $e['valor_quinzenal'],
				'mensal'    => (float) $e['valor_mensal'],
			) : null,
		);
	}

	public static function list_equipment( WP_REST_Request $req ) {
		global $wpdb;
		$where = "publicar_site = 1 AND status <> 'inativo'";
		if ( $req['categoria'] ) {
			$where .= $wpdb->prepare( ' AND categoria_id = %d', $req['categoria'] );
		}
		$rows = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE {$where} ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return rest_ensure_response( array_map( array( __CLASS__, 'public_fields' ), $rows ) );
	}

	public static function availability( WP_REST_Request $req ) {
		$e = DL_DB::get( 'equipamentos', (int) $req['id'] );
		if ( ! $e || ! $e['publicar_site'] || 'inativo' === $e['status'] ) {
			return new WP_Error( 'not_found', 'Equipamento não encontrado.', array( 'status' => 404 ) );
		}
		$start = $req['inicio'];
		$end   = $req['fim'];
		if ( $end < $start ) {
			return new WP_Error( 'datas', 'A devolução precisa ser depois da retirada.', array( 'status' => 400 ) );
		}
		$days = DL_Contracts::rental_days( $start, $end );
		$free = DL_Availability::available( $e['id'], $start, $end );
		$out  = array(
			'disponivel' => $free > 0,
			'livres'     => (int) $free,
			'dias'       => $days,
		);
		if ( dl_opt( 'mostrar_precos' ) ) {
			$best             = DL_Pricing::best_price( DL_Contracts::rates( $e ), $days );
			$out['preco']     = $best['total'];
			$out['preco_fmt'] = dl_money( $best['total'] );
			$out['composicao'] = $best['descricao'];
		}
		return rest_ensure_response( $out );
	}
}
