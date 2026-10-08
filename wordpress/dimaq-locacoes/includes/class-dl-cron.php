<?php
/**
 * Rotina diária: resumo para a equipe e lembretes automáticos aos clientes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Cron {

	const HOOK = 'dl_daily';

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			$first = strtotime( 'tomorrow 07:00', current_time( 'timestamp' ) ) - ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			wp_schedule_event( $first, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	private static function already( $entity, $id, $action ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'historico' ) . ' WHERE entidade = %s AND entidade_id = %d AND acao = %s', $entity, $id, $action ) );
	}

	public static function run() {
		global $wpdb;
		$today = dl_today();
		$tc    = dl_table( 'contratos' );
		$tf    = dl_table( 'financeiro' );
		$lines = array();

		$late = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='ativo' AND data_prev_devolucao < %s ORDER BY data_prev_devolucao", $today ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $late ) {
			$lines[] = 'DEVOLUÇÕES ATRASADAS (' . count( $late ) . ')';
			foreach ( $late as $c ) {
				$lines[] = sprintf( '  %s — %s — previsto %s (%d dia(s))', $c['numero'], DL_DB::label( 'clientes', $c['cliente_id'] ), dl_date( $c['data_prev_devolucao'] ), dl_days_between( $c['data_prev_devolucao'], $today ) );
			}
		}

		$ahead = max( 0, (int) dl_opt( 'lembrete_dias', 1 ) );
		$soon  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='ativo' AND data_prev_devolucao BETWEEN %s AND %s ORDER BY data_prev_devolucao", $today, dl_add_days( $today, $ahead ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $soon ) {
			$lines[] = '';
			$lines[] = 'DEVOLUÇÕES PREVISTAS ATÉ ' . dl_date( dl_add_days( $today, $ahead ) );
			foreach ( $soon as $c ) {
				$lines[] = sprintf( '  %s — %s — %s', $c['numero'], DL_DB::label( 'clientes', $c['cliente_id'] ), dl_date( $c['data_prev_devolucao'] ) );
				self::remind_return( $c );
			}
		}

		$starts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tc} WHERE status='reservado' AND data_inicio <= %s ORDER BY data_inicio", dl_add_days( $today, 1 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $starts ) {
			$lines[] = '';
			$lines[] = 'SAÍDAS DE HOJE E AMANHÃ';
			foreach ( $starts as $c ) {
				$lines[] = sprintf( '  %s — %s — %s', $c['numero'], DL_DB::label( 'clientes', $c['cliente_id'] ), dl_date( $c['data_inicio'] ) );
			}
		}

		$overdue = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tf} WHERE tipo='receber' AND status='aberto' AND vencimento < %s ORDER BY vencimento", $today ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $overdue ) {
			$sum = 0;
			foreach ( $overdue as $f ) {
				$sum += (float) $f['valor'] - (float) $f['valor_pago'];
				if ( dl_add_days( $f['vencimento'], 1 ) === $today ) {
					self::remind_bill( $f );
				}
			}
			$lines[] = '';
			$lines[] = 'CONTAS A RECEBER VENCIDAS: ' . count( $overdue ) . ' título(s), ' . dl_money( $sum );
		}

		$pay = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tf} WHERE tipo='pagar' AND status='aberto' AND vencimento <= %s ORDER BY vencimento", dl_add_days( $today, 2 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $pay ) {
			$lines[] = '';
			$lines[] = 'CONTAS A PAGAR ATÉ ' . dl_date( dl_add_days( $today, 2 ) );
			foreach ( $pay as $f ) {
				$lines[] = sprintf( '  %s — %s — %s', dl_date( $f['vencimento'] ), $f['descricao'], dl_money( (float) $f['valor'] - (float) $f['valor_pago'] ) );
			}
		}

		$meas = DL_Measurement::pending();
		if ( $meas ) {
			$lines[] = '';
			$lines[] = 'MEDIÇÕES A FAZER';
			foreach ( $meas as $m ) {
				$lines[] = sprintf( '  %s — %s — %s a %s', $m['contrato']['numero'], DL_DB::label( 'clientes', $m['contrato']['cliente_id'] ), dl_date( $m['sugestao']['inicio'] ), dl_date( $m['sugestao']['fim'] ) );
			}
		}
		foreach ( DL_Service_Orders::preventive_due() as $e ) {
			$lines[] = 'Preventiva vencida: ' . $e['nome'];
		}
		foreach ( DL_Stock::low_stock() as $p ) {
			$lines[] = 'Estoque baixo: ' . $p['nome'] . ' (' . dl_num( $p['estoque_atual'], 0 ) . ')';
		}

		$requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tc} WHERE status='solicitacao'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $requests ) {
			$lines[] = '';
			$lines[] = $requests . ' pedido(s) de orçamento do site aguardando resposta.';
		}

		if ( $lines ) {
			wp_mail( dl_opt( 'email_notificacao' ), 'Resumo do dia — ' . dl_opt( 'empresa_nome' ) . ' — ' . dl_date( $today ), implode( "\n", $lines ) . "\n\nPainel: " . dl_app_url() );
		}
		do_action( 'dl_daily_done', $lines );
	}

	private static function remind_return( $c ) {
		if ( ! dl_opt( 'email_cliente' ) || self::already( 'contratos', $c['id'], 'Lembrete de devolução enviado' ) ) {
			return;
		}
		$cli = DL_DB::get( 'clientes', (int) $c['cliente_id'] );
		if ( ! $cli || ! is_email( $cli['email'] ) ) {
			return;
		}
		$ok = wp_mail(
			$cli['email'],
			'Devolução prevista para ' . dl_date( $c['data_prev_devolucao'] ) . ' — ' . $c['numero'],
			sprintf( "Olá, %s!\n\nA locação %s tem devolução prevista para %s.\nPrecisa de mais tempo? Responda este e-mail ou chame no WhatsApp %s para renovar.\n\n%s", $cli['contato'] ? $cli['contato'] : $cli['nome'], $c['numero'], dl_date( $c['data_prev_devolucao'] ), dl_opt( 'empresa_whatsapp' ), dl_opt( 'empresa_nome' ) )
		);
		if ( $ok ) {
			dl_log( 'contratos', $c['id'], 'Lembrete de devolução enviado', $cli['email'] );
		}
	}

	private static function remind_bill( $f ) {
		if ( ! dl_opt( 'email_cliente' ) || self::already( 'financeiro', $f['id'], 'Aviso de vencimento enviado' ) ) {
			return;
		}
		$cli = DL_DB::get( 'clientes', (int) $f['cliente_id'] );
		if ( ! $cli || ! is_email( $cli['email'] ) ) {
			return;
		}
		$ok = wp_mail(
			$cli['email'],
			'Fatura em aberto — ' . $f['descricao'],
			sprintf( "Olá, %s!\n\nNão identificamos o pagamento de \"%s\", no valor de %s, vencido em %s.\nSe já pagou, desconsidere esta mensagem.\n\n%s\n\n%s", $cli['contato'] ? $cli['contato'] : $cli['nome'], $f['descricao'], dl_money( (float) $f['valor'] - (float) $f['valor_pago'] ), dl_date( $f['vencimento'] ), dl_opt( 'dados_bancarios' ), dl_opt( 'empresa_nome' ) )
		);
		if ( $ok ) {
			dl_log( 'financeiro', $f['id'], 'Aviso de vencimento enviado', $cli['email'] );
		}
	}
}
