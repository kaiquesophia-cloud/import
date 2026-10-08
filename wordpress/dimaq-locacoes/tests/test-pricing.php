<?php
/**
 * Testes do cálculo de preço (sem WordPress): php tests/test-pricing.php
 */
define( 'DL_TESTING', true );
require __DIR__ . '/../includes/class-dl-pricing.php';

$fails = 0;
function check( $cond, $msg ) {
	global $fails;
	echo ( $cond ? 'ok   ' : 'FAIL ' ) . $msg . PHP_EOL;
	$fails += $cond ? 0 : 1;
}

$r = array( 'diaria' => 100, 'semanal' => 500, 'quinzenal' => 900, 'mensal' => 1500 );
check( 100.0 === DL_Pricing::best_price( $r, 1 )['total'], '1 dia = 1 diária' );
check( 500.0 === DL_Pricing::best_price( $r, 5 )['total'], '5 dias: semana (500) sai igual a 5 diárias' );
check( 500.0 === DL_Pricing::best_price( $r, 6 )['total'], '6 dias: semana é mais barata que 6 diárias' );
check( 800.0 === DL_Pricing::best_price( $r, 10 )['total'], '10 dias = semana + 3 diárias' );
check( 900.0 === DL_Pricing::best_price( $r, 14 )['total'], '14 dias: quinzena (900) bate 2 semanas (1000)' );
check( 1500.0 === DL_Pricing::best_price( $r, 25 )['total'], '25 dias: mês' );
check( 1600.0 === DL_Pricing::best_price( $r, 31 )['total'], '31 dias = mês + diária' );
check( 300.0 === DL_Pricing::best_price( array( 'diaria' => 100 ), 3 )['total'], 'só diária' );
check( 0.0 === DL_Pricing::best_price( array(), 3 )['total'], 'sem tarifa = 0' );
check( '1 semana + 3 diárias' === DL_Pricing::best_price( $r, 10 )['descricao'], 'descrição da composição' );

check( 250.0 === DL_Pricing::item_total( 2, 5, 30, 50 ), 'item: 2 × 5 × 30 − 50' );
check( 0.0 === DL_Pricing::item_total( 1, 1, 10, 50 ), 'desconto não deixa negativo' );
check( 330.0 === DL_Pricing::late_fee( 100, 3, 1, 10 ), 'atraso: 3 diárias + 10%' );
check( 0.0 === DL_Pricing::late_fee( 100, 0 ), 'sem atraso, sem cobrança' );

$c = DL_Pricing::overdue_charges( 1000, 15, 2, 1 );
check( 20.0 === $c['multa'] && 5.0 === $c['juros'], 'vencido 15 dias: multa 2% e juros 1% a.m. pró-rata' );

$p = DL_Pricing::installments( 100, 3 );
check( array( 33.33, 33.33, 33.34 ) === $p, 'parcelas 100/3 com centavos na última' );
check( abs( array_sum( DL_Pricing::installments( 1234.57, 7 ) ) - 1234.57 ) < 0.0001, 'soma das parcelas fecha' );

echo $fails ? "\n$fails falha(s)\n" : "\nTodos os testes passaram.\n";
exit( $fails ? 1 : 0 );
