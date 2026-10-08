<?php
/**
 * Integração com o WordPress: tira a equipe do wp-admin, atalho para o sistema,
 * validações e dados de detalhe de clientes e equipamentos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Admin {

	public static function init() {
		DL_Stock::init();
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'keep_staff_out_of_wp_admin' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
		add_filter( 'dl_before_save_clientes', array( __CLASS__, 'validate_client' ), 10, 3 );
		add_filter( 'dl_before_save_equipamentos', array( __CLASS__, 'validate_equipment' ), 10, 3 );
		add_filter( 'dl_list_row_equipamentos', array( __CLASS__, 'equipment_row' ) );
		add_filter( 'dl_can_delete_equipamentos', array( __CLASS__, 'can_delete_equipment' ), 10, 2 );
		add_filter( 'dl_can_delete_clientes', array( __CLASS__, 'can_delete_client' ), 10, 2 );
		add_filter( 'dl_can_delete_categorias', array( __CLASS__, 'can_delete_category' ), 10, 2 );
	}

	/** Equipe da locadora que não administra o site. */
	public static function is_staff_only( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user && $user->exists() && user_can( $user, 'dl_operar' ) && ! user_can( $user, 'manage_options' );
	}

	/** No painel do WordPress, só um atalho que abre o sistema. */
	public static function menu() {
		add_menu_page(
			'Sistema Dimaq',
			'Sistema Dimaq',
			'dl_operar',
			'dl-sistema',
			'__return_null',
			'dashicons-hammer',
			2
		);
	}

	public static function keep_staff_out_of_wp_admin() {
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		$script = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ?? '' ) ) );
		if ( in_array( $script, array( 'admin-post.php', 'async-upload.php', 'admin-ajax.php' ), true ) ) {
			return;
		}
		$open_app = isset( $_GET['page'] ) && 'dl-sistema' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $open_app || self::is_staff_only() ) {
			wp_safe_redirect( dl_app_url() );
			exit;
		}
	}

	public static function admin_bar( $show ) {
		if ( ! is_user_logged_in() ) {
			return $show;
		}
		$roles = (array) wp_get_current_user()->roles;
		return ( self::is_staff_only() || in_array( 'dl_cliente', $roles, true ) ) ? false : $show;
	}

	public static function login_redirect( $redirect, $requested, $user ) {
		if ( $user instanceof WP_User && self::is_staff_only( $user ) ) {
			return dl_app_url();
		}
		return $redirect;
	}

	/* -------------------------------------------------------------- clientes */

	public static function validate_client( $data, $id, $old ) {
		if ( ! empty( $data['documento'] ) ) {
			if ( ! dl_valid_document( $data['documento'] ) ) {
				return new WP_Error( 'doc', 'CPF/CNPJ inválido.' );
			}
			global $wpdb;
			$dup = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dl_table( 'clientes' ) . ' WHERE documento = %s AND id <> %d LIMIT 1', $data['documento'], $id ) );
			if ( $dup ) {
				return new WP_Error( 'dup', 'Já existe cliente com este CPF/CNPJ (#' . $dup . ').' );
			}
			if ( ! $old ) {
				$data['tipo'] = 11 === strlen( $data['documento'] ) ? 'PF' : 'PJ';
			}
		}
		return $data;
	}

	public static function can_delete_client( $can, $row ) {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'contratos' ) . ' WHERE cliente_id = %d', $row['id'] ) )
			+ (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'financeiro' ) . ' WHERE cliente_id = %d', $row['id'] ) );
		return $n ? 'Cliente com contratos ou lançamentos não pode ser excluído — use "bloqueado".' : $can;
	}

	/** Resumo do cliente para a ficha. */
	public static function client_info( $cli ) {
		global $wpdb;
		$rows      = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'contratos' ) . ' WHERE cliente_id = %d ORDER BY id DESC LIMIT 30', $cli['id'] ), ARRAY_A );
		$contracts = array();
		foreach ( $rows as $c ) {
			$contracts[] = array( 'id' => (int) $c['id'], 'numero' => $c['numero'], 'inicio' => $c['data_inicio'], 'fim' => $c['data_prev_devolucao'], 'status' => DL_Contracts::is_late( $c ) ? 'atrasado' : $c['status'], 'total' => (float) $c['total'] );
		}
		$phone = $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'];
		return array(
			'em_aberto'   => current_user_can( 'dl_financeiro' ) ? DL_Finance::client_open( $cli['id'] ) : null,
			'vencido'     => current_user_can( 'dl_financeiro' ) ? DL_Finance::client_overdue( $cli['id'] ) : null,
			'contratos'   => $contracts,
			'whatsapp'    => $phone ? dl_whatsapp_link( $phone ) : '',
			'pode_acesso' => ! $cli['wp_user_id'] && is_email( $cli['email'] ) && current_user_can( 'create_users' ),
			'tem_acesso'  => (bool) $cli['wp_user_id'],
		);
	}

	/** Cria o usuário da área do cliente. @return array|WP_Error */
	public static function create_client_user( $cli ) {
		if ( ! current_user_can( 'create_users' ) ) {
			return new WP_Error( 'perm', 'Só o administrador pode criar acessos.' );
		}
		if ( ! is_email( $cli['email'] ) ) {
			return new WP_Error( 'email', 'Cliente sem e-mail válido.' );
		}
		$user = get_user_by( 'email', $cli['email'] );
		if ( ! $user ) {
			$login   = sanitize_user( current( explode( '@', $cli['email'] ) ), true );
			$login   = username_exists( $login ) ? $login . '_' . $cli['id'] : $login;
			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_email'   => $cli['email'],
					'display_name' => $cli['nome'],
					'user_pass'    => wp_generate_password( 20 ),
					'role'         => 'dl_cliente',
				)
			);
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			wp_new_user_notification( $user_id, null, 'user' );
		} else {
			$user_id = $user->ID;
		}
		DL_DB::update( 'clientes', $cli['id'], array( 'wp_user_id' => $user_id ) );
		dl_log( 'clientes', $cli['id'], 'Acesso à área do cliente criado' );
		return array( 'message' => 'Acesso criado. O cliente recebeu um e-mail para definir a senha.' );
	}

	/* ---------------------------------------------------------- equipamentos */

	public static function validate_equipment( $data, $id, $old ) {
		if ( isset( $data['controle'] ) && 'unitario' === $data['controle'] ) {
			$data['qtd_total'] = 1;
		}
		if ( isset( $data['qtd_total'] ) && $data['qtd_total'] < 1 ) {
			$data['qtd_total'] = 1;
		}
		if ( ! empty( $data['codigo'] ) ) {
			global $wpdb;
			$dup = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dl_table( 'equipamentos' ) . ' WHERE codigo = %s AND id <> %d LIMIT 1', $data['codigo'], $id ) );
			if ( $dup ) {
				return new WP_Error( 'dup', 'Já existe equipamento com este código (#' . $dup . ').' );
			}
		}
		return $data;
	}

	/** Na lista: quantos estão fora, livres, foto e categoria. */
	public static function equipment_row( $row ) {
		$out              = DL_Availability::out_now( $row['id'] );
		$fleet            = 'quantidade' === $row['controle'] ? (int) $row['qtd_total'] : 1;
		$row['locados']   = $out;
		$row['livres']    = 'disponivel' === $row['status'] ? max( 0, $fleet - $out ) : 0;
		$row['foto']      = $row['foto_id'] ? wp_get_attachment_image_url( (int) $row['foto_id'], 'medium' ) : '';
		$row['categoria'] = DL_DB::label( 'categorias', (int) $row['categoria_id'] );
		if ( $out >= $fleet && 'disponivel' === $row['status'] ) {
			$row['status'] = 'locado';
		}
		return $row;
	}

	public static function can_delete_equipment( $can, $row ) {
		global $wpdb;
		$used = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'itens' ) . " WHERE ref_tipo = 'equipamento' AND ref_id = %d", $row['id'] ) );
		return $used ? 'Equipamento com histórico de locação não pode ser excluído — marque como "Inativo / baixado".' : $can;
	}

	public static function can_delete_category( $can, $row ) {
		global $wpdb;
		$used = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dl_table( 'equipamentos' ) . ' WHERE categoria_id = %d', $row['id'] ) );
		return $used ? 'Há equipamentos nesta categoria.' : $can;
	}

	/** Situação, agenda e manutenções do equipamento para a ficha. */
	public static function equipment_info( $e ) {
		global $wpdb;
		$fleet = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
		$since = (float) $e['horimetro'] - (float) $e['ultima_manutencao_horas'];
		$os    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, numero, tipo, status, data_abertura, total FROM ' . dl_table( 'ordens_servico' ) . ' WHERE equipamento_id = %d ORDER BY id DESC LIMIT 15', $e['id'] ), ARRAY_A );
		$sched = array();
		foreach ( DL_Availability::schedule( $e['id'], dl_today() ) as $s ) {
			$sched[] = array( 'id' => (int) $s['id'], 'numero' => $s['numero'], 'status' => $s['status'], 'inicio' => $s['data_inicio'], 'fim' => $s['data_prev_devolucao'], 'cliente' => DL_DB::label( 'clientes', $s['cliente_id'] ), 'qtd' => (float) $s['qtd'] - (float) $s['qtd_devolvida'] );
		}
		return array(
			'frota'       => $fleet,
			'locados'     => DL_Availability::out_now( $e['id'] ),
			'livres_hoje' => DL_Availability::available( $e['id'], dl_today(), dl_today() ),
			'preventiva'  => $e['manutencao_cada_horas'] > 0 ? array( 'vencida' => $since >= $e['manutencao_cada_horas'], 'faltam' => max( 0, $e['manutencao_cada_horas'] - $since ) ) : null,
			'agenda'      => $sched,
			'manutencoes' => $os,
			'foto'        => $e['foto_id'] ? wp_get_attachment_image_url( (int) $e['foto_id'], 'medium' ) : '',
		);
	}
}
