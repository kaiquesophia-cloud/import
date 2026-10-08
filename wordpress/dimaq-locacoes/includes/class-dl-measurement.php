<?php
/**
 * Cobrança por medição (pro-rata).
 *
 * A cada ciclo (padrão 30 dias) mede quantas unidades de cada item ficaram com o cliente e por
 * quantos dias, e cobra diária = valor do período ÷ dias do período (mensal ÷ 30). Devoluções
 * parciais e itens incluídos no meio do caminho entram pela data real, a partir do histórico
 * de movimentos (saídas e retornos).
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'DL_TESTING' ) ) {
	exit;
}

class DL_Measurement {

	public static function init() {
		add_action( 'dl_items_saved_contrato', array( __CLASS__, 'sync_movements' ), 20 );
		add_filter( 'dl_can_delete_contratos', array( __CLASS__, 'can_delete_contract' ), 20, 2 );
	}

	public static function is_measured( $c ) {
		return 'medicao' === ( $c['cobranca'] ?? 'periodo' );
	}

	/* ------------------------------------------------------------ movimentos */

	public static function log( $item_id, $contract_id, $type, $qty, $date ) {
		global $wpdb;
		if ( (float) $qty <= 0 ) {
			return;
		}
		$wpdb->insert(
			dl_table( 'movimentos' ),
			array(
				'item_id'     => (int) $item_id,
				'contrato_id' => (int) $contract_id,
				'tipo'        => 'retorno' === $type ? 'retorno' : 'saida',
				'qtd'         => (float) $qty,
				'data'        => substr( $date, 0, 10 ),
				'criado_em'   => dl_now(),
			)
		);
	}

	/**
	 * Movimentos de um item. Itens anteriores ao histórico são reconstituídos pelas datas
	 * de saída e retorno gravadas no próprio item.
	 */
	public static function movements( $item, $contract ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT tipo, qtd, data FROM ' . dl_table( 'movimentos' ) . ' WHERE item_id = %d ORDER BY data ASC, id ASC', $item['id'] ), ARRAY_A );
		if ( $rows ) {
			return $rows;
		}
		if ( ! in_array( $contract['status'], array( 'ativo', 'encerrado' ), true ) ) {
			return array();
		}
		$out = array( array( 'tipo' => 'saida', 'qtd' => (float) $item['qtd'], 'data' => $item['data_saida'] ? $item['data_saida'] : $contract['data_inicio'] ) );
		if ( (float) $item['qtd_devolvida'] > 0 ) {
			$out[] = array( 'tipo' => 'retorno', 'qtd' => (float) $item['qtd_devolvida'], 'data' => $item['data_retorno'] ? $item['data_retorno'] : ( $contract['data_encerramento'] ? $contract['data_encerramento'] : $contract['data_prev_devolucao'] ) );
		}
		return $out;
	}

	/**
	 * Em locação ativa, itens incluídos depois da entrega saem hoje e quantidades reduzidas
	 * voltam hoje — para a medição enxergar a mudança na data certa.
	 */
	public static function sync_movements( $contract_id ) {
		global $wpdb;
		$c = DL_DB::get( 'contratos', $contract_id );
		if ( ! $c || 'ativo' !== $c['status'] ) {
			return;
		}
		$t = dl_table( 'movimentos' );
		foreach ( DL_Items::get( 'contrato', $contract_id ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE item_id = %d", $it['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			if ( ! $has ) {
				// Item sem histórico: registra a saída (na data de saída, ou hoje se acabou de entrar).
				$date = $it['data_saida'] ? $it['data_saida'] : dl_today();
				self::log( $it['id'], $contract_id, 'saida', $it['qtd'], $date );
				if ( ! $it['data_saida'] ) {
					$wpdb->update( dl_table( 'itens' ), array( 'data_saida' => $date ), array( 'id' => $it['id'] ) );
				}
				continue;
			}
			$out = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN tipo='saida' THEN qtd ELSE 0 END),0) FROM {$t} WHERE item_id = %d", $it['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			$diff = round( (float) $it['qtd'] - $out, 3 );
			if ( $diff > 0 ) {
				self::log( $it['id'], $contract_id, 'saida', $diff, dl_today() );
			} elseif ( $diff < 0 ) {
				self::log( $it['id'], $contract_id, 'retorno', -$diff, dl_today() );
			}
		}
	}

	/* --------------------------------------------------------------- cálculo */

	/**
	 * Faixas de quantidade constante dentro de [início, fim]. Função pura.
	 *
	 * @param array $events    [['tipo'=>'saida'|'retorno','qtd'=>n,'data'=>'Y-m-d'], ...].
	 * @param bool  $inclusive Se o dia do retorno também é cobrado.
	 * @return array [['de'=>, 'ate'=>, 'qtd'=>, 'dias'=>], ...]
	 */
	public static function segments( array $events, $start, $end, $inclusive = true ) {
		$changes = array();
		foreach ( $events as $e ) {
			$q = (float) $e['qtd'];
			if ( 'saida' === $e['tipo'] ) {
				$d             = substr( $e['data'], 0, 10 );
				$changes[ $d ] = ( $changes[ $d ] ?? 0 ) + $q;
			} else {
				$d             = $inclusive ? self::add_days( substr( $e['data'], 0, 10 ), 1 ) : substr( $e['data'], 0, 10 );
				$changes[ $d ] = ( $changes[ $d ] ?? 0 ) - $q;
			}
		}
		ksort( $changes );
		$qty = 0.0;
		foreach ( $changes as $d => $delta ) {
			if ( $d <= $start ) {
				$qty += $delta;
			}
		}
		$out   = array();
		$cur   = $start;
		$dates = array_keys( $changes );
		$dates = array_values( array_filter( $dates, function ( $d ) use ( $start, $end ) { return $d > $start && $d <= $end; } ) );
		$dates[] = self::add_days( $end, 1 );
		foreach ( $dates as $d ) {
			$until = self::add_days( $d, -1 );
			if ( $qty > 0.0001 && $until >= $cur ) {
				$out[] = array( 'de' => $cur, 'ate' => $until, 'qtd' => round( $qty, 3 ), 'dias' => self::days( $cur, $until ) + 1 );
			}
			if ( isset( $changes[ $d ] ) ) {
				$qty += $changes[ $d ];
			}
			$cur = $d;
		}
		return $out;
	}

	private static function add_days( $date, $n ) {
		return gmdate( 'Y-m-d', strtotime( $date . ' 12:00:00 UTC' ) + (int) $n * 86400 );
	}

	private static function days( $a, $b ) {
		return (int) round( ( strtotime( $b . ' 12:00:00 UTC' ) - strtotime( $a . ' 12:00:00 UTC' ) ) / 86400 );
	}

	/** Diária pro-rata de um item: valor do período ÷ dias do período. */
	public static function daily_rate( $item, $contract ) {
		$base  = max( 1, (int) dl_opt( 'medicao_base_dias', 30 ) );
		$types = array( 'diaria' => 1, 'semanal' => 7, 'quinzenal' => 15, 'mensal' => $base );
		if ( isset( $types[ $item['periodo_tipo'] ] ) ) {
			$rate = (float) $item['valor_unit'] / $types[ $item['periodo_tipo'] ];
		} else {
			// Pacote: o valor vale para o período combinado do contrato.
			$rate = (float) $item['valor_unit'] / max( 1, DL_Contracts::rental_days( $contract['data_inicio'], $contract['data_prev_devolucao'] ) );
		}
		$gross = (float) $item['qtd'] * (float) $item['periodos'] * (float) $item['valor_unit'];
		$factor = $gross > 0 ? max( 0, 1 - (float) $item['desconto'] / $gross ) : 1;
		return $rate * $factor;
	}

	public static function list_for( $contract_id, $only_valid = false ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . dl_table( 'medicoes' ) . ' WHERE contrato_id = %d' . ( $only_valid ? " AND status = 'gerada'" : '' ) . ' ORDER BY numero ASC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $contract_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/** Primeiro dia com equipamento na obra. */
	private static function first_day( $c ) {
		$first = null;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			foreach ( self::movements( $it, $c ) as $m ) {
				if ( 'saida' === $m['tipo'] && ( ! $first || $m['data'] < $first ) ) {
					$first = $m['data'];
				}
			}
		}
		return $first ? $first : $c['data_inicio'];
	}

	/** Último dia com equipamento na obra (quando tudo já voltou). */
	private static function last_day( $c ) {
		$last = null;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			foreach ( self::movements( $it, $c ) as $m ) {
				if ( 'retorno' === $m['tipo'] && ( ! $last || $m['data'] > $last ) ) {
					$last = $m['data'];
				}
			}
		}
		return $last;
	}

	/** Período sugerido para a próxima medição. */
	public static function suggestion( $c ) {
		$done  = self::list_for( $c['id'], true );
		$last  = $done ? end( $done ) : null;
		$start = $last ? self::add_days( $last['fim'], 1 ) : self::first_day( $c );
		$cycle = max( 1, (int) ( $c['medicao_ciclo'] ? $c['medicao_ciclo'] : 30 ) );
		$end   = self::add_days( $start, $cycle - 1 );
		$final = false;
		if ( 'encerrado' === $c['status'] ) {
			$ret = self::last_day( $c );
			if ( $ret ) {
				$closing = DL_Contracts::inclusive() ? $ret : self::add_days( $ret, -1 );
				if ( $closing < $end ) {
					$end   = $closing;
					$final = true;
				}
			}
		}
		return array(
			'inicio'  => $start,
			'fim'     => $end,
			'numero'  => $last ? (int) $last['numero'] + 1 : 1,
			'final'   => $final,
			'pronta'  => $end <= dl_today() || $final,
			'nada'    => $end < $start,
		);
	}

	/**
	 * Calcula a medição de um período (sem gravar).
	 *
	 * @return array|WP_Error
	 */
	public static function calculate( $c, $start, $end ) {
		if ( ! self::valid_date( $start ) || ! self::valid_date( $end ) || $end < $start ) {
			return new WP_Error( 'periodo', 'Período inválido.' );
		}
		$inclusive = DL_Contracts::inclusive();
		$lines     = array();
		$items_sum = 0.0;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'equipamento' !== $it['ref_tipo'] ) {
				continue;
			}
			$segs = self::segments( self::movements( $it, $c ), $start, $end, $inclusive );
			if ( ! $segs ) {
				continue;
			}
			$rate     = self::daily_rate( $it, $c );
			$qty_days = 0.0;
			foreach ( $segs as $s ) {
				$qty_days += $s['qtd'] * $s['dias'];
			}
			$value      = round( $qty_days * $rate, 2 );
			$items_sum += $value;
			$e          = DL_DB::get( 'equipamentos', (int) $it['ref_id'] );
			$lines[]    = array(
				'item_id'   => (int) $it['id'],
				'codigo'    => $e ? $e['codigo'] : '',
				'descricao' => $e ? $e['nome'] : $it['descricao'],
				'faixas'    => $segs,
				'diarias'   => round( $qty_days, 3 ),
				'diaria'    => round( $rate, 4 ),
				'valor'     => $value,
			);
		}
		$extras    = array();
		$extra_sum = 0.0;
		foreach ( DL_Items::get( 'contrato', $c['id'] ) as $it ) {
			if ( 'adicional' === $it['ref_tipo'] && ! (int) $it['medicao_id'] ) {
				$extras[]   = array( 'id' => (int) $it['id'], 'descricao' => $it['descricao'], 'valor' => (float) $it['total'] );
				$extra_sum += (float) $it['total'];
			}
		}
		$first    = ! self::list_for( $c['id'], true );
		$freight  = $first ? (float) $c['valor_frete'] : 0.0;
		$discount = $first ? (float) $c['desconto'] : 0.0;
		return array(
			'inicio'           => $start,
			'fim'              => $end,
			'dias'             => self::days( $start, $end ) + 1,
			'linhas'           => $lines,
			'adicionais'       => $extras,
			'valor_itens'      => round( $items_sum, 2 ),
			'valor_adicionais' => round( $extra_sum, 2 ),
			'valor_frete'      => $freight,
			'desconto'         => $discount,
			'total'            => max( 0, round( $items_sum + $extra_sum + $freight - $discount, 2 ) ),
		);
	}

	private static function valid_date( $d ) {
		return is_string( $d ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d );
	}

	/**
	 * Grava a medição e lança a cobrança.
	 *
	 * @return array|WP_Error
	 */
	public static function generate( $c, array $p ) {
		if ( ! self::is_measured( $c ) ) {
			return new WP_Error( 'cobranca', 'Esta locação é cobrada por período, não por medição.' );
		}
		if ( ! in_array( $c['status'], array( 'ativo', 'encerrado' ), true ) ) {
			return new WP_Error( 'status', 'Só dá para medir locação entregue.' );
		}
		$start = sanitize_text_field( $p['inicio'] ?? '' );
		$end   = sanitize_text_field( $p['fim'] ?? '' );
		$done  = self::list_for( $c['id'], true );
		$last  = $done ? end( $done ) : null;
		if ( $last && $start <= $last['fim'] ) {
			return new WP_Error( 'sobreposicao', 'O período começa antes do fim da medição ' . $last['numero'] . ' (' . dl_date( $last['fim'] ) . ').' );
		}
		$calc = self::calculate( $c, $start, $end );
		if ( is_wp_error( $calc ) ) {
			return $calc;
		}
		if ( $calc['total'] <= 0 ) {
			return new WP_Error( 'zero', 'Nada a cobrar neste período.' );
		}
		global $wpdb;
		$numero = $last ? (int) $last['numero'] + 1 : 1;
		$id     = DL_DB::insert(
			'medicoes',
			array(
				'contrato_id'      => $c['id'],
				'numero'           => $numero,
				'inicio'           => $start,
				'fim'              => $end,
				'valor_itens'      => $calc['valor_itens'],
				'valor_adicionais' => $calc['valor_adicionais'],
				'valor_frete'      => $calc['valor_frete'],
				'desconto'         => $calc['desconto'],
				'total'            => $calc['total'],
				'linhas'           => wp_json_encode( array( 'linhas' => $calc['linhas'], 'adicionais' => $calc['adicionais'] ) ),
				'obs'              => sanitize_textarea_field( $p['obs'] ?? '' ),
				'status'           => 'gerada',
				'criado_por'       => get_current_user_id(),
			)
		);
		foreach ( $calc['adicionais'] as $x ) {
			$wpdb->update( dl_table( 'itens' ), array( 'medicao_id' => $id ), array( 'id' => $x['id'] ) );
		}
		$due = sanitize_text_field( $p['vencimento'] ?? '' );
		DL_Finance::create_installments(
			array(
				'tipo'            => 'receber',
				'descricao'       => 'Medição ' . $numero . ' — contrato ' . $c['numero'] . ' (' . dl_date( $start ) . ' a ' . dl_date( $end ) . ')',
				'categoria'       => 'locacao',
				'cliente_id'      => $c['cliente_id'],
				'origem'          => 'contrato',
				'origem_id'       => $c['id'],
				'forma_pagamento' => sanitize_key( $p['forma'] ?? '' ),
				'obs'             => 'medicao:' . $id,
			),
			$calc['total'],
			max( 1, absint( $p['parcelas'] ?? 1 ) ),
			self::valid_date( $due ) ? $due : dl_today(),
			30
		);
		DL_DB::update( 'contratos', $c['id'], array( 'valor_faturado' => round( (float) $c['valor_faturado'] + $calc['total'], 2 ) ) );
		dl_log( 'contratos', $c['id'], 'Medição ' . $numero . ' gerada', dl_date( $start ) . ' a ' . dl_date( $end ) . ' — ' . dl_money( $calc['total'] ) );
		return array( 'message' => 'Medição ' . $numero . ' gerada: ' . dl_money( $calc['total'] ) . ' em contas a receber.', 'id' => $id );
	}

	/** Cancela a última medição (e as cobranças dela, se nada foi pago). */
	public static function cancel( $m ) {
		global $wpdb;
		$c    = DL_DB::get( 'contratos', (int) $m['contrato_id'] );
		$done = self::list_for( $m['contrato_id'], true );
		$last = $done ? end( $done ) : null;
		if ( ! $last || (int) $last['id'] !== (int) $m['id'] ) {
			return new WP_Error( 'ordem', 'Só a última medição pode ser cancelada.' );
		}
		$tf   = dl_table( 'financeiro' );
		$tag  = 'medicao:' . (int) $m['id'];
		$paid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tf} WHERE obs = %s AND status = 'pago'", $tag ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $paid ) {
			return new WP_Error( 'pago', 'Há parcela paga desta medição: estorne o pagamento antes.' );
		}
		$wpdb->query( $wpdb->prepare( "UPDATE {$tf} SET status = 'cancelado' WHERE obs = %s AND status = 'aberto'", $tag ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->update( dl_table( 'itens' ), array( 'medicao_id' => 0 ), array( 'medicao_id' => (int) $m['id'] ) );
		DL_DB::update( 'medicoes', $m['id'], array( 'status' => 'cancelada' ) );
		DL_DB::update( 'contratos', $c['id'], array( 'valor_faturado' => max( 0, round( (float) $c['valor_faturado'] - (float) $m['total'], 2 ) ) ) );
		dl_log( 'contratos', $c['id'], 'Medição ' . $m['numero'] . ' cancelada' );
		return array( 'message' => 'Medição ' . $m['numero'] . ' cancelada.' );
	}

	public static function can_delete_contract( $can, $row ) {
		return ( true === $can && self::list_for( $row['id'], true ) ) ? 'Locação com medições não pode ser excluída.' : $can;
	}

	/** Locações por medição com período vencido e sem medir. */
	public static function pending() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'contratos' ) . " WHERE cobranca = 'medicao' AND status IN ('ativo','encerrado') ORDER BY id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out  = array();
		foreach ( $rows as $c ) {
			$s = self::suggestion( $c );
			if ( ! $s['nada'] && $s['pronta'] ) {
				$out[] = array( 'contrato' => $c, 'sugestao' => $s );
			}
		}
		return $out;
	}
}
