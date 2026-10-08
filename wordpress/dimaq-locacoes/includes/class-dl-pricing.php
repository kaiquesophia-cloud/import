<?php
/**
 * Cálculo de preço de locação. Não depende do WordPress (testável isolado).
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'DL_TESTING' ) ) {
	exit;
}

class DL_Pricing {

	/**
	 * Encontra a combinação mais barata de diárias, semanas, quinzenas e meses que cobre
	 * pelo menos $days dias. Tarifas zeradas são ignoradas.
	 *
	 * @param array $rates ['diaria'=>x, 'semanal'=>y, 'quinzenal'=>z, 'mensal'=>w].
	 * @param int   $days  Dias de locação (mínimo 1).
	 * @return array ['total'=>float, 'partes'=>['mensal'=>n, ...], 'descricao'=>string] ou total 0 sem tarifa.
	 */
	public static function best_price( array $rates, $days ) {
		$days    = max( 1, (int) $days );
		$periods = array(
			'mensal'    => 30,
			'quinzenal' => 15,
			'semanal'   => 7,
			'diaria'    => 1,
		);
		$options = array();
		foreach ( $periods as $key => $len ) {
			if ( ! empty( $rates[ $key ] ) && (float) $rates[ $key ] > 0 ) {
				$options[ $key ] = array( $len, (float) $rates[ $key ] );
			}
		}
		if ( ! $options ) {
			return array( 'total' => 0.0, 'partes' => array(), 'descricao' => '' );
		}

		// Programação dinâmica: cost[d] = menor custo para cobrir pelo menos d dias.
		$cost   = array( 0 => 0.0 );
		$choice = array( 0 => null );
		for ( $d = 1; $d <= $days; $d++ ) {
			$cost[ $d ]   = INF;
			$choice[ $d ] = null;
			foreach ( $options as $key => $opt ) {
				$prev = max( 0, $d - $opt[0] );
				$c    = $cost[ $prev ] + $opt[1];
				if ( $c < $cost[ $d ] - 0.00001 ) {
					$cost[ $d ]   = $c;
					$choice[ $d ] = array( $key, $prev );
				}
			}
		}

		$parts = array();
		$d     = $days;
		while ( $d > 0 && $choice[ $d ] ) {
			list( $key, $prev ) = $choice[ $d ];
			$parts[ $key ]      = isset( $parts[ $key ] ) ? $parts[ $key ] + 1 : 1;
			$d                  = $prev;
		}

		$labels = array(
			'mensal'    => array( 'mês', 'meses' ),
			'quinzenal' => array( 'quinzena', 'quinzenas' ),
			'semanal'   => array( 'semana', 'semanas' ),
			'diaria'    => array( 'diária', 'diárias' ),
		);
		$desc = array();
		foreach ( array_keys( $periods ) as $key ) {
			if ( ! empty( $parts[ $key ] ) ) {
				$desc[] = $parts[ $key ] . ' ' . $labels[ $key ][ $parts[ $key ] > 1 ? 1 : 0 ];
			}
		}

		return array(
			'total'     => round( $cost[ $days ], 2 ),
			'partes'    => $parts,
			'descricao' => implode( ' + ', $desc ),
		);
	}

	/**
	 * Valor de um item: quantidade × períodos × valor unitário, menos desconto.
	 */
	public static function item_total( $qty, $periods, $unit, $discount = 0 ) {
		$total = (float) $qty * max( 0, (float) $periods ) * (float) $unit - (float) $discount;
		return round( max( 0, $total ), 2 );
	}

	/**
	 * Cobrança por atraso: dias excedentes × diária × quantidade, com multa percentual opcional.
	 */
	public static function late_fee( $daily_rate, $late_days, $qty = 1, $penalty_pct = 0 ) {
		if ( $late_days <= 0 ) {
			return 0.0;
		}
		$base = (float) $daily_rate * (int) $late_days * (float) $qty;
		return round( $base * ( 1 + (float) $penalty_pct / 100 ), 2 );
	}

	/**
	 * Juros e multa de conta vencida: multa fixa % + juros simples % ao mês pró-rata dia.
	 */
	public static function overdue_charges( $value, $days_late, $fine_pct, $interest_month_pct ) {
		if ( $days_late <= 0 ) {
			return array( 'multa' => 0.0, 'juros' => 0.0 );
		}
		$fine     = (float) $value * (float) $fine_pct / 100;
		$interest = (float) $value * ( (float) $interest_month_pct / 100 / 30 ) * (int) $days_late;
		return array( 'multa' => round( $fine, 2 ), 'juros' => round( $interest, 2 ) );
	}

	/**
	 * Divide um valor em parcelas; a diferença de centavos fica na última.
	 */
	public static function installments( $total, $count ) {
		$count = max( 1, (int) $count );
		$base  = floor( (float) $total / $count * 100 ) / 100;
		$list  = array_fill( 0, $count, $base );
		$list[ $count - 1 ] = round( (float) $total - $base * ( $count - 1 ), 2 );
		return $list;
	}
}
