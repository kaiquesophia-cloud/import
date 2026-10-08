<?php
/**
 * Rota do dia: entregas e coletas que a locadora faz na obra, na ordem do motorista,
 * com endereço para o mapa e baixa (entrega/devolução) pelo próprio celular.
 *
 * Entrega: locação reservada com "Locadora entrega" ou "entrega e coleta", que começa no dia
 * (hoje também traz as que ficaram para trás). Coleta: locação ativa com "Coleta agendada"
 * no dia, ou, sem agendamento, "entrega e coleta" com devolução prevista no dia (hoje também
 * traz as atrasadas). O que já foi feito no dia continua na lista, marcado como feito.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Route {

	const ORDER_OPTION = 'dl_rota_ordem';

	private static function delivers( $c ) {
		return in_array( $c['entrega'], array( 'entrega', 'entrega_coleta' ), true );
	}

	/** Endereço para o mapa: o da entrega, senão o da obra, senão o do cliente. */
	public static function address( $c, $cli ) {
		if ( trim( $c['endereco_entrega'] ) ) {
			return trim( $c['endereco_entrega'] );
		}
		if ( trim( $c['local_obra'] ) && preg_match( '/\d/', $c['local_obra'] ) ) {
			return trim( $c['local_obra'] );
		}
		if ( $cli && $cli['logradouro'] ) {
			$parts = array( trim( $cli['logradouro'] . ( $cli['numero'] ? ', ' . $cli['numero'] : '' ) ), $cli['bairro'], trim( $cli['cidade'] . ( $cli['uf'] ? ' - ' . $cli['uf'] : '' ) ), $cli['cep'] );
			return implode( ', ', array_filter( array_map( 'trim', $parts ) ) );
		}
		return trim( $c['local_obra'] );
	}

	public static function origin() {
		$parts = array( dl_opt( 'empresa_endereco' ), dl_opt( 'empresa_bairro' ), trim( dl_opt( 'empresa_cidade' ) . ( dl_opt( 'empresa_uf' ) ? ' - ' . dl_opt( 'empresa_uf' ) : '' ) ), dl_opt( 'empresa_cep' ) );
		return implode( ', ', array_filter( array_map( 'trim', $parts ) ) );
	}

	public static function maps_url( $address ) {
		return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
	}

	public static function waze_url( $address ) {
		return 'https://waze.com/ul?navigate=yes&q=' . rawurlencode( $address );
	}

	/** Rota inteira no Google Maps, saindo da locadora (o Maps aceita até 9 paradas no meio). */
	public static function full_route_url( array $addresses ) {
		$addresses = array_values( array_filter( $addresses ) );
		if ( ! $addresses ) {
			return null;
		}
		$args = array(
			'api'         => 1,
			'origin'      => self::origin(),
			'destination' => end( $addresses ),
			'travelmode'  => 'driving',
		);
		$middle = array_slice( $addresses, 0, -1 );
		if ( $middle ) {
			$args['waypoints'] = implode( '|', array_slice( $middle, 0, 9 ) );
		}
		$q = array();
		foreach ( $args as $k => $v ) {
			$q[] = $k . '=' . rawurlencode( (string) $v );
		}
		return 'https://www.google.com/maps/dir/?' . implode( '&', $q );
	}

	/** Contratos com movimento de saída/retorno no dia. */
	private static function moved_on( $type, $date ) {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT contrato_id FROM ' . dl_table( 'movimentos' ) . ' WHERE tipo = %s AND data = %s', $type, $date ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array_map( 'intval', $ids );
	}

	/** Paradas do dia, já na ordem salva. */
	public static function stops( $date, $driver = '' ) {
		global $wpdb;
		$t     = dl_table( 'contratos' );
		$today = dl_today();
		$op    = $date === $today ? '<=' : '=';
		$found = array();

		// entregas pendentes
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE status = 'reservado' AND entrega IN ('entrega','entrega_coleta') AND data_inicio {$op} %s ORDER BY data_inicio, id", $date ), ARRAY_A ) as $c ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$found[ 'e' . $c['id'] ] = array( 'entrega', $c, false );
		}
		// coletas pendentes: agendadas no dia (ou antes, se hoje) ou sem agendamento com devolução prevista
		$sql = "SELECT * FROM {$t} WHERE status = 'ativo' AND ( coleta_em {$op} %s OR ( coleta_em IS NULL AND entrega = 'entrega_coleta' AND data_prev_devolucao {$op} %s ) ) ORDER BY data_prev_devolucao, id";
		foreach ( $wpdb->get_results( $wpdb->prepare( $sql, $date, $date ), ARRAY_A ) as $c ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$found[ 'c' . $c['id'] ] = array( 'coleta', $c, false );
		}
		// feitas no dia
		foreach ( self::moved_on( 'saida', $date ) as $id ) {
			$c = DL_DB::get( 'contratos', $id );
			if ( $c && self::delivers( $c ) && 'reservado' !== $c['status'] ) {
				$found[ 'e' . $id ] = array( 'entrega', $c, true );
			}
		}
		foreach ( self::moved_on( 'retorno', $date ) as $id ) {
			$c = DL_DB::get( 'contratos', $id );
			if ( ! $c || isset( $found[ 'c' . $id ] ) ) {
				continue; // devolução parcial: a coleta segue pendente
			}
			if ( 'entrega_coleta' === $c['entrega'] || $c['coleta_em'] === $date ) {
				$found[ 'c' . $id ] = array( 'coleta', $c, true );
			}
		}

		$stops = array();
		foreach ( $found as $key => $row ) {
			list( $type, $c, $done ) = $row;
			if ( '' !== $driver && trim( (string) $c['motorista'] ) !== $driver ) {
				continue;
			}
			$stops[] = self::stop( $key, $type, $c, $done, $date );
		}

		$saved = self::saved_order( $date );
		$pos   = array_flip( $saved );
		usort(
			$stops,
			function ( $a, $b ) use ( $pos ) {
				if ( $a['feito'] !== $b['feito'] ) {
					return $a['feito'] ? 1 : -1; // feitas descem para o fim
				}
				$pa = $pos[ $a['chave'] ] ?? PHP_INT_MAX;
				$pb = $pos[ $b['chave'] ] ?? PHP_INT_MAX;
				if ( $pa !== $pb ) {
					return $pa <=> $pb;
				}
				return array( 'entrega' === $a['tipo'] ? 0 : 1, $a['contrato']['id'] ) <=> array( 'entrega' === $b['tipo'] ? 0 : 1, $b['contrato']['id'] ); // entregas antes das coletas
			}
		);
		return $stops;
	}

	private static function stop( $key, $type, $c, $done, $date ) {
		$cli   = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		$card  = DL_Dashboard::card( $c );
		$addr  = self::address( $c, $cli );
		$phone = $c['telefone_obra'] ? $c['telefone_obra'] : ( $cli ? ( $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'] ) : '' );
		$items = array();
		foreach ( $card['itens'] as $it ) {
			$q = 'coleta' === $type && ! $done ? $it['pendente'] : $it['qtd'];
			if ( $q > 0 ) {
				$items[] = array( 'descricao' => $it['descricao'], 'qtd' => $q );
			}
		}
		$late_from = 'entrega' === $type ? $c['data_inicio'] : ( $c['coleta_em'] ? $c['coleta_em'] : $c['data_prev_devolucao'] );
		return array(
			'chave'       => $key,
			'tipo'        => $type,
			'feito'       => $done,
			'atrasada'    => ! $done && $late_from < $date,
			'desde'       => $late_from,
			'agendada'    => 'coleta' === $type && ! empty( $c['coleta_em'] ),
			'contrato'    => array( 'id' => (int) $c['id'], 'numero' => $c['numero'], 'status' => $card['status'] ),
			'cliente'     => $cli ? $cli['nome'] : '',
			'obra'        => $c['local_obra'],
			'endereco'    => $addr,
			'responsavel' => $c['responsavel_obra'] ? $c['responsavel_obra'] : ( $cli ? $cli['contato'] : '' ),
			'telefone'    => $phone,
			'whatsapp'    => $phone ? dl_whatsapp_link( $phone, sprintf( 'Olá! Aqui é da %s. Estou a caminho para a %s da locação %s.', dl_opt( 'empresa_nome' ), 'entrega' === $type ? 'entrega' : 'coleta', $c['numero'] ) ) : null,
			'motorista'   => (string) $c['motorista'],
			'itens'       => $items,
			'obs'         => $c['obs_interna'],
			'maps'        => $addr ? self::maps_url( $addr ) : null,
			'waze'        => $addr ? self::waze_url( $addr ) : null,
		);
	}

	public static function saved_order( $date ) {
		$all = get_option( self::ORDER_OPTION, array() );
		return is_array( $all ) && isset( $all[ $date ] ) ? (array) $all[ $date ] : array();
	}

	public static function save_order( $date, array $keys ) {
		$all = get_option( self::ORDER_OPTION, array() );
		$all = is_array( $all ) ? $all : array();
		$all[ $date ] = array_values( array_filter( array_map( 'sanitize_key', $keys ) ) );
		$limit = dl_add_days( dl_today(), -7 ); // não guarda ordem de semanas atrás
		foreach ( array_keys( $all ) as $d ) {
			if ( $d < $limit ) {
				unset( $all[ $d ] );
			}
		}
		update_option( self::ORDER_OPTION, $all, false );
	}

	/** Tudo que a tela precisa para um dia. */
	public static function day( $date, $driver = '' ) {
		global $wpdb;
		$stops   = self::stops( $date, $driver );
		$pending = array_filter( $stops, function ( $s ) { return ! $s['feito']; } );
		$drivers = $wpdb->get_col( 'SELECT DISTINCT motorista FROM ' . dl_table( 'contratos' ) . " WHERE motorista <> '' AND status IN ('reservado','ativo') ORDER BY motorista" ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array(
			'data'       => $date,
			'origem'     => self::origin(),
			'paradas'    => $stops,
			'feitas'     => count( $stops ) - count( $pending ),
			'rota_maps'  => self::full_route_url( array_column( $pending, 'endereco' ) ),
			'mais_de_9'  => count( $pending ) > 10,
			'motoristas' => $drivers,
		);
	}
}
