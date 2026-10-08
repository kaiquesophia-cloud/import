<?php
/**
 * Funções utilitárias usadas por todo o plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Nome completo de uma tabela do plugin. */
function dl_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'dl_' . $name;
}

/** Formata valor em reais: 1234.5 → "R$ 1.234,50". */
function dl_money( $value ) {
	return 'R$ ' . number_format( (float) $value, 2, ',', '.' );
}

/** Formata número decimal no padrão brasileiro sem símbolo. */
function dl_num( $value, $decimals = 2 ) {
	return number_format( (float) $value, $decimals, ',', '.' );
}

/**
 * Converte texto digitado em número: aceita "1.234,56", "1234,56" e "1234.56".
 */
function dl_decimal( $value ) {
	if ( is_numeric( $value ) ) {
		return (float) $value;
	}
	$value = preg_replace( '/[^0-9,.\-]/', '', (string) $value );
	if ( false !== strpos( $value, ',' ) ) {
		$value = str_replace( '.', '', $value );
		$value = str_replace( ',', '.', $value );
	}
	return (float) $value;
}

/** Data Y-m-d → d/m/Y (vazio se não houver data). */
function dl_date( $date ) {
	if ( empty( $date ) || '0000-00-00' === substr( $date, 0, 10 ) ) {
		return '';
	}
	$ts = strtotime( $date );
	return $ts ? date( 'd/m/Y', $ts ) : '';
}

/** Data/hora → d/m/Y H:i. */
function dl_datetime( $date ) {
	if ( empty( $date ) ) {
		return '';
	}
	$ts = strtotime( $date );
	return $ts ? date( 'd/m/Y H:i', $ts ) : '';
}

/** Hoje no fuso do site, formato Y-m-d. */
function dl_today() {
	return current_time( 'Y-m-d' );
}

/** Agora no fuso do site, formato MySQL. */
function dl_now() {
	return current_time( 'mysql' );
}

/** Diferença em dias corridos entre duas datas (fim - início). */
function dl_days_between( $start, $end ) {
	$a = strtotime( substr( (string) $start, 0, 10 ) );
	$b = strtotime( substr( (string) $end, 0, 10 ) );
	if ( ! $a || ! $b ) {
		return 0;
	}
	return (int) round( ( $b - $a ) / DAY_IN_SECONDS );
}

/** Soma dias a uma data Y-m-d. */
function dl_add_days( $date, $days ) {
	return date( 'Y-m-d', strtotime( substr( $date, 0, 10 ) . ' ' . ( (int) $days >= 0 ? '+' : '' ) . (int) $days . ' days' ) );
}

/** Lê uma configuração do plugin. */
function dl_opt( $key, $default = '' ) {
	$opts = get_option( 'dl_settings', array() );
	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}
	$defaults = DL_Settings::defaults();
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
}

/** Registra um evento no histórico. */
function dl_log( $entity, $entity_id, $action, $details = '' ) {
	global $wpdb;
	$wpdb->insert(
		dl_table( 'historico' ),
		array(
			'entidade'    => $entity,
			'entidade_id' => (int) $entity_id,
			'acao'        => $action,
			'detalhes'    => is_array( $details ) ? wp_json_encode( $details ) : (string) $details,
			'usuario_id'  => get_current_user_id(),
			'criado_em'   => dl_now(),
		)
	);
}

/**
 * Endereço do sistema (fora do painel do WordPress). $route é a tela interna,
 * ex.: 'contratos/12' abre o contrato 12.
 */
function dl_app_url( $route = '' ) {
	$base = get_option( 'permalink_structure' ) ? home_url( '/' . DL_App::SLUG . '/' ) : add_query_arg( 'dl_app', '1', home_url( '/' ) );
	return $route ? $base . '#/' . ltrim( $route, '/' ) : $base;
}

/** Guarda um aviso para devolver junto com a resposta da operação. */
function dl_notice( $message, $type = 'warning' ) {
	$GLOBALS['dl_notices'][] = array( 'type' => $type, 'message' => wp_strip_all_tags( $message ) );
}

/** Retorna e limpa os avisos acumulados. */
function dl_take_notices() {
	$n = isset( $GLOBALS['dl_notices'] ) ? $GLOBALS['dl_notices'] : array();
	$GLOBALS['dl_notices'] = array();
	return $n;
}

/** Interrompe se o usuário não tiver a permissão. */
function dl_require_cap( $cap ) {
	if ( ! current_user_can( $cap ) ) {
		wp_die( esc_html__( 'Você não tem permissão para esta ação.', 'dimaq-locacoes' ), 403 );
	}
}

/** Rótulos dos status de cada entidade. */
function dl_statuses( $entity ) {
	$map = array(
		'contrato'    => array(
			'solicitacao' => 'Solicitação do site',
			'orcamento'   => 'Orçamento',
			'reservado'   => 'Reservado',
			'ativo'       => 'Em locação',
			'encerrado'   => 'Encerrado',
			'cancelado'   => 'Cancelado',
		),
		'equipamento' => array(
			'disponivel' => 'Disponível',
			'manutencao' => 'Em manutenção',
			'inativo'    => 'Inativo / baixado',
		),
		'os'          => array(
			'aberta'          => 'Aberta',
			'em_andamento'    => 'Em andamento',
			'aguardando_peca' => 'Aguardando peça',
			'concluida'       => 'Concluída',
			'cancelada'       => 'Cancelada',
		),
		'venda'       => array(
			'orcamento'  => 'Orçamento',
			'confirmada' => 'Confirmada',
			'cancelada'  => 'Cancelada',
		),
		'financeiro'  => array(
			'aberto'    => 'Em aberto',
			'pago'      => 'Pago',
			'cancelado' => 'Cancelado',
		),
		'nota'        => array(
			'pendente'    => 'Pendente',
			'processando' => 'Processando',
			'autorizada'  => 'Autorizada',
			'rejeitada'   => 'Rejeitada',
			'cancelada'   => 'Cancelada',
		),
	);
	return isset( $map[ $entity ] ) ? $map[ $entity ] : array();
}

/** Selo colorido de status. */
function dl_badge( $entity, $status ) {
	$labels = dl_statuses( $entity );
	$extra  = array(
		'vencido'  => 'Vencido',
		'atrasado' => 'Em atraso',
		'locado'   => 'Locado',
	);
	$label  = isset( $labels[ $status ] ) ? $labels[ $status ] : ( isset( $extra[ $status ] ) ? $extra[ $status ] : $status );
	return sprintf( '<span class="dl-badge dl-badge-%s">%s</span>', esc_attr( $status ), esc_html( $label ) );
}

/** Formas de pagamento aceitas. */
function dl_payment_methods() {
	return array(
		'pix'           => 'PIX',
		'boleto'        => 'Boleto',
		'dinheiro'      => 'Dinheiro',
		'cartao_credito' => 'Cartão de crédito',
		'cartao_debito' => 'Cartão de débito',
		'transferencia' => 'Transferência',
		'cheque'        => 'Cheque',
		'faturado'      => 'Faturado',
	);
}

/** Tipos de período de cobrança (dias de cada um). */
function dl_period_types() {
	return array(
		'diaria'    => array( 'label' => 'Diária', 'dias' => 1, 'campo' => 'valor_diaria' ),
		'semanal'   => array( 'label' => 'Semanal', 'dias' => 7, 'campo' => 'valor_semanal' ),
		'quinzenal' => array( 'label' => 'Quinzenal', 'dias' => 15, 'campo' => 'valor_quinzenal' ),
		'mensal'    => array( 'label' => 'Mensal', 'dias' => 30, 'campo' => 'valor_mensal' ),
		'pacote'    => array( 'label' => 'Pacote (melhor tarifa)', 'dias' => 0, 'campo' => '' ),
	);
}

/** Estados brasileiros. */
function dl_ufs() {
	$ufs = array( 'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO' );
	return array_combine( $ufs, $ufs );
}

/** Mantém só os dígitos (CPF, CNPJ, telefone, CEP). */
function dl_digits( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/** Valida CPF ou CNPJ pelos dígitos verificadores. */
function dl_valid_document( $doc ) {
	$doc = dl_digits( $doc );
	if ( 11 === strlen( $doc ) ) {
		if ( preg_match( '/^(\d)\1{10}$/', $doc ) ) {
			return false;
		}
		for ( $t = 9; $t < 11; $t++ ) {
			$sum = 0;
			for ( $i = 0; $i < $t; $i++ ) {
				$sum += (int) $doc[ $i ] * ( ( $t + 1 ) - $i );
			}
			$digit = ( ( 10 * $sum ) % 11 ) % 10;
			if ( (int) $doc[ $t ] !== $digit ) {
				return false;
			}
		}
		return true;
	}
	if ( 14 === strlen( $doc ) ) {
		if ( preg_match( '/^(\d)\1{13}$/', $doc ) ) {
			return false;
		}
		$weights = array( array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ), array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) );
		foreach ( array( 12, 13 ) as $k => $len ) {
			$sum = 0;
			for ( $i = 0; $i < $len; $i++ ) {
				$sum += (int) $doc[ $i ] * $weights[ $k ][ $i ];
			}
			$rest  = $sum % 11;
			$digit = $rest < 2 ? 0 : 11 - $rest;
			if ( (int) $doc[ $len ] !== $digit ) {
				return false;
			}
		}
		return true;
	}
	return false;
}

/** Formata CPF/CNPJ com pontuação. */
function dl_format_document( $doc ) {
	$d = dl_digits( $doc );
	if ( 11 === strlen( $d ) ) {
		return substr( $d, 0, 3 ) . '.' . substr( $d, 3, 3 ) . '.' . substr( $d, 6, 3 ) . '-' . substr( $d, 9, 2 );
	}
	if ( 14 === strlen( $d ) ) {
		return substr( $d, 0, 2 ) . '.' . substr( $d, 2, 3 ) . '.' . substr( $d, 5, 3 ) . '/' . substr( $d, 8, 4 ) . '-' . substr( $d, 12, 2 );
	}
	return (string) $doc;
}

/** Formata telefone brasileiro: (11) 99999-0000. */
function dl_format_phone( $phone ) {
	$d = dl_digits( $phone );
	if ( 11 === strlen( $d ) ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 5 ) . '-' . substr( $d, 7 );
	}
	if ( 10 === strlen( $d ) ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 4 ) . '-' . substr( $d, 6 );
	}
	return (string) $phone;
}

/** Formata CEP: 04470060 → 04470-060. */
function dl_format_cep( $cep ) {
	$d = dl_digits( $cep );
	return 8 === strlen( $d ) ? substr( $d, 0, 5 ) . '-' . substr( $d, 5 ) : (string) $cep;
}

/** Link de WhatsApp com mensagem pronta. */
function dl_whatsapp_link( $number, $message = '' ) {
	$n = dl_digits( $number );
	if ( $n && strlen( $n ) <= 11 ) {
		$n = '55' . $n;
	}
	return 'https://wa.me/' . $n . ( $message ? '?text=' . rawurlencode( $message ) : '' );
}

/** Número de documento legível: ano + id com zeros. */
function dl_doc_number( $prefix, $id, $date = '' ) {
	$year = $date ? substr( $date, 0, 4 ) : current_time( 'Y' );
	return $prefix . $year . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
}
