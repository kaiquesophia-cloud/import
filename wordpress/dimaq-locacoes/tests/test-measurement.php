<?php
/**
 * Testes do cálculo de faixas da medição pro-rata (sem WordPress): php tests/test-measurement.php
 */
define( 'DL_TESTING', true );
require __DIR__ . '/../includes/class-dl-measurement.php';

$fails = 0;
function check( $cond, $msg ) {
	global $fails;
	echo ( $cond ? 'ok   ' : 'FAIL ' ) . $msg . PHP_EOL;
	$fails += $cond ? 0 : 1;
}
function qty_days( $segs ) {
	return array_sum( array_map( function ( $s ) { return $s['qtd'] * $s['dias']; }, $segs ) );
}

// 10 painéis saem em 01/09; 4 voltam em 21/09. Medição de 01/09 a 30/09.
$ev = array(
	array( 'tipo' => 'saida', 'qtd' => 10, 'data' => '2026-09-01' ),
	array( 'tipo' => 'retorno', 'qtd' => 4, 'data' => '2026-09-21' ),
);
$s = DL_Measurement::segments( $ev, '2026-09-01', '2026-09-30', true );
check( 2 === count( $s ), 'duas faixas de quantidade' );
check( 10.0 === (float) $s[0]['qtd'] && 21 === $s[0]['dias'], '10 un × 21 dias (o dia da devolução é cobrado)' );
check( 6.0 === (float) $s[1]['qtd'] && 9 === $s[1]['dias'], '6 un × 9 dias depois da devolução parcial' );
check( 264.0 === (float) qty_days( $s ), 'total 264 diárias' );

$s = DL_Measurement::segments( $ev, '2026-09-01', '2026-09-30', false );
check( 10 * 20 + 6 * 10 === (int) qty_days( $s ), 'sem contar o dia da devolução: 260 diárias' );

// Segundo período: só os 6 restantes.
$s = DL_Measurement::segments( $ev, '2026-10-01', '2026-10-30', true );
check( 1 === count( $s ) && 6.0 === (float) $s[0]['qtd'] && 30 === $s[0]['dias'], 'mês seguinte: 6 un × 30 dias' );

// Item incluído no meio do período.
$ev2 = array( array( 'tipo' => 'saida', 'qtd' => 5, 'data' => '2026-09-11' ) );
$s   = DL_Measurement::segments( $ev2, '2026-09-01', '2026-09-30', true );
check( 1 === count( $s ) && 20 === $s[0]['dias'] && '2026-09-11' === $s[0]['de'], 'incluído em 11/09: 5 un × 20 dias' );

// Tudo devolvido antes do período: nada a medir.
$ev3 = array( array( 'tipo' => 'saida', 'qtd' => 2, 'data' => '2026-08-01' ), array( 'tipo' => 'retorno', 'qtd' => 2, 'data' => '2026-08-20' ) );
check( array() === DL_Measurement::segments( $ev3, '2026-09-01', '2026-09-30', true ), 'devolvido antes: nenhuma faixa' );

// Saídas e retornos no mesmo dia, várias vezes.
$ev4 = array(
	array( 'tipo' => 'saida', 'qtd' => 3, 'data' => '2026-09-01' ),
	array( 'tipo' => 'saida', 'qtd' => 2, 'data' => '2026-09-05' ),
	array( 'tipo' => 'retorno', 'qtd' => 5, 'data' => '2026-09-10' ),
);
check( 3 * 4 + 5 * 6 === (int) qty_days( DL_Measurement::segments( $ev4, '2026-09-01', '2026-09-30', true ) ), 'entradas parceladas e devolução total: 42 diárias' );

echo $fails ? "\n$fails falha(s)\n" : "\nTodos os testes passaram.\n";
exit( $fails ? 1 : 0 );
