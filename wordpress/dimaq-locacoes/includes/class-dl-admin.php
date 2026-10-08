<?php
/**
 * Menu do painel, scripts e laterais de equipamentos e clientes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Admin {

	public static function init() {
		DL_Crud::init();
		DL_Stock::init();
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_dl_save_settings', array( 'DL_Settings', 'save' ) );
		add_action( 'admin_post_dl_client_user', array( __CLASS__, 'create_client_user' ) );
		add_action( 'dl_sidebar_equipamentos', array( __CLASS__, 'equipment_sidebar' ) );
		add_action( 'dl_sidebar_clientes', array( __CLASS__, 'client_sidebar' ) );
		add_filter( 'dl_before_save_clientes', array( __CLASS__, 'validate_client' ), 10, 3 );
		add_filter( 'dl_before_save_equipamentos', array( __CLASS__, 'validate_equipment' ), 10, 3 );
		add_filter( 'dl_list_row_equipamentos', array( __CLASS__, 'equipment_row' ) );
		add_filter( 'dl_can_delete_equipamentos', array( __CLASS__, 'can_delete_equipment' ), 10, 2 );
		add_filter( 'dl_can_delete_clientes', array( __CLASS__, 'can_delete_client' ), 10, 2 );
		add_filter( 'dl_can_delete_categorias', array( __CLASS__, 'can_delete_category' ), 10, 2 );
		add_filter( 'dl_row_actions_clientes', array( __CLASS__, 'client_row_actions' ), 10, 2 );
	}

	public static function menu() {
		$pages = array(
			array( 'dl-contratos', 'Locações', 'dl_operar', array( 'DL_Crud', 'render' ), 'contratos' ),
			array( 'dl-disponibilidade', 'Disponibilidade', 'dl_operar', array( 'DL_Dashboard', 'render_availability' ), null ),
			array( 'dl-clientes', 'Clientes', 'dl_operar', array( 'DL_Crud', 'render' ), 'clientes' ),
			array( 'dl-equipamentos', 'Equipamentos', 'dl_operar', array( 'DL_Crud', 'render' ), 'equipamentos' ),
			array( 'dl-categorias', 'Categorias', 'dl_operar', array( 'DL_Crud', 'render' ), 'categorias' ),
			array( 'dl-os', 'Ordens de serviço', 'dl_operar', array( 'DL_Crud', 'render' ), 'os' ),
			array( 'dl-vendas', 'Vendas', 'dl_operar', array( 'DL_Crud', 'render' ), 'vendas' ),
			array( 'dl-produtos', 'Produtos e estoque', 'dl_operar', array( 'DL_Crud', 'render' ), 'produtos' ),
			array( 'dl-fornecedores', 'Fornecedores', 'dl_operar', array( 'DL_Crud', 'render' ), 'fornecedores' ),
			array( 'dl-financeiro', 'Financeiro', 'dl_financeiro', array( 'DL_Crud', 'render' ), 'financeiro' ),
			array( 'dl-notas', 'Notas fiscais', 'dl_financeiro', array( 'DL_Crud', 'render' ), 'notas' ),
			array( 'dl-relatorios', 'Relatórios', 'dl_operar', array( 'DL_Reports', 'render' ), null ),
			array( 'dl-config', 'Configurações', 'dl_config', array( 'DL_Settings', 'render' ), null ),
		);

		$solic = self::pending_count();
		add_menu_page( 'Locação', 'Locação' . ( $solic ? ' <span class="awaiting-mod">' . (int) $solic . '</span>' : '' ), 'dl_operar', 'dl-painel', array( 'DL_Dashboard', 'render' ), 'dashicons-hammer', 26 );
		add_submenu_page( 'dl-painel', 'Painel', 'Painel', 'dl_operar', 'dl-painel', array( 'DL_Dashboard', 'render' ) );
		foreach ( $pages as $p ) {
			$cb = $p[4] ? function () use ( $p ) {
				DL_Crud::render( $p[4] );
			} : $p[3];
			add_submenu_page( 'dl-painel', $p[1], $p[1], $p[2], $p[0], $cb );
		}
		// Página sem item de menu: só se abre a partir de um contrato.
		$hook = add_submenu_page( '', 'Entrega e devolução', 'Entrega e devolução', 'dl_operar', 'dl-movimento', array( 'DL_Contracts', 'render_move' ) );
		add_action(
			'load-' . $hook,
			function () {
				$GLOBALS['title'] = 'Entrega e devolução';
			}
		);
	}

	private static function pending_count() {
		global $wpdb;
		$n = wp_cache_get( 'dl_pending' );
		if ( false === $n ) {
			$n = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dl_table( 'contratos' ) . " WHERE status = 'solicitacao'" ); // phpcs:ignore WordPress.DB.PreparedSQL
			wp_cache_set( 'dl_pending', $n, '', 60 );
		}
		return $n;
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'dl-' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'dl-admin', DL_URL . 'assets/admin.css', array(), DL_VERSION );
		wp_enqueue_script( 'dl-admin', DL_URL . 'assets/admin.js', array( 'jquery' ), DL_VERSION, true );
		wp_localize_script(
			'dl-admin',
			'DL',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'dl_admin' ),
			)
		);
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
			if ( empty( $data['tipo'] ) || ! $old ) {
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

	public static function client_row_actions( $actions, $row ) {
		$actions['locar'] = '<a href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'new', 'cliente_id' => $row['id'] ) ) ) . '">Nova locação</a>';
		return $actions;
	}

	public static function client_sidebar( $cli ) {
		global $wpdb;
		$id = (int) $cli['id'];
		echo '<div class="dl-card"><h3>Cliente</h3>';
		echo '<p><a class="button button-primary" href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'new', 'cliente_id' => $id ) ) ) . '">Nova locação</a></p>';
		$phone = $cli['whatsapp'] ? $cli['whatsapp'] : $cli['telefone'];
		if ( $phone ) {
			echo '<p><a class="button" target="_blank" href="' . esc_url( dl_whatsapp_link( $phone ) ) . '">Abrir WhatsApp</a></p>';
		}
		if ( current_user_can( 'dl_financeiro' ) ) {
			echo '<p>Em aberto: <strong>' . esc_html( dl_money( DL_Finance::client_open( $id ) ) ) . '</strong><br>Vencido: <strong>' . esc_html( dl_money( DL_Finance::client_overdue( $id ) ) ) . '</strong></p>';
		}
		if ( ! $cli['wp_user_id'] && is_email( $cli['email'] ) && current_user_can( 'create_users' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'dl_client_user_' . $id );
			echo '<input type="hidden" name="action" value="dl_client_user"><input type="hidden" name="id" value="' . (int) $id . '">';
			echo '<button class="button">Criar acesso à área do cliente</button><p class="description">Cria um usuário com o e-mail do cliente e envia o link para definir a senha.</p></form>';
		}
		echo '</div>';
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'contratos' ) . ' WHERE cliente_id = %d ORDER BY id DESC LIMIT 15', $id ), ARRAY_A );
		if ( $rows ) {
			echo '<div class="dl-card"><h3>Locações</h3><table class="widefat striped"><tbody>';
			foreach ( $rows as $c ) {
				echo '<tr><td><a href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $c['id'] ) ) ) . '">' . esc_html( $c['numero'] ) . '</a></td><td>' . esc_html( dl_date( $c['data_inicio'] ) ) . '</td><td>' . dl_badge( 'contrato', DL_Contracts::is_late( $c ) ? 'atrasado' : $c['status'] ) . '</td><td>' . esc_html( dl_money( $c['total'] ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tbody></table></div>';
		}
	}

	public static function create_client_user() {
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'dl_client_user_' . $id );
		if ( ! current_user_can( 'create_users' ) ) {
			wp_die( 'Sem permissão.' );
		}
		$cli  = DL_DB::get( 'clientes', $id );
		$back = dl_admin_url( 'dl-clientes', array( 'action' => 'edit', 'id' => $id ) );
		if ( ! $cli || ! is_email( $cli['email'] ) ) {
			dl_redirect( $back, 'Cliente sem e-mail válido.', 'error' );
		}
		$user = get_user_by( 'email', $cli['email'] );
		if ( ! $user ) {
			$login   = sanitize_user( current( explode( '@', $cli['email'] ) ), true );
			$login   = username_exists( $login ) ? $login . '_' . $id : $login;
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
				dl_redirect( $back, $user_id->get_error_message(), 'error' );
			}
			wp_new_user_notification( $user_id, null, 'user' );
		} else {
			$user_id = $user->ID;
		}
		DL_DB::update( 'clientes', $id, array( 'wp_user_id' => $user_id ) );
		dl_log( 'clientes', $id, 'Acesso à área do cliente criado' );
		dl_redirect( $back, 'Acesso criado. O cliente recebeu um e-mail para definir a senha.' );
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

	public static function equipment_row( $row ) {
		$out = DL_Availability::out_now( $row['id'] );
		if ( $out > 0 && 'disponivel' === $row['status'] ) {
			$fleet = 'quantidade' === $row['controle'] ? (int) $row['qtd_total'] : 1;
			$row['qtd_total'] = $row['qtd_total'] . ' (' . dl_num( $out, 0 ) . ' locado' . ( $out > 1 ? 's' : '' ) . ')';
			if ( $out >= $fleet ) {
				$row['status'] = 'locado';
			}
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

	public static function equipment_sidebar( $e ) {
		global $wpdb;
		$id    = (int) $e['id'];
		$fleet = 'quantidade' === $e['controle'] ? (int) $e['qtd_total'] : 1;
		$out   = DL_Availability::out_now( $id );
		echo '<div class="dl-card"><h3>Situação agora</h3>';
		echo '<p>' . dl_badge( 'equipamento', $out >= $fleet && 'disponivel' === $e['status'] ? 'locado' : $e['status'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p>Frota: ' . (int) $fleet . ' · Locados: ' . esc_html( dl_num( $out, 0 ) ) . ' · Livres hoje: ' . esc_html( dl_num( DL_Availability::available( $id, dl_today(), dl_today() ), 0 ) ) . '</p>';
		if ( $e['manutencao_cada_horas'] > 0 ) {
			$since = (float) $e['horimetro'] - (float) $e['ultima_manutencao_horas'];
			echo '<p>' . ( $since >= $e['manutencao_cada_horas'] ? '<strong class="dl-alert">Preventiva vencida</strong>' : 'Próxima preventiva em ' . esc_html( dl_num( $e['manutencao_cada_horas'] - $since, 0 ) ) . ' h' ) . '</p>';
		}
		echo '<p><a class="button" href="' . esc_url( dl_admin_url( 'dl-os', array( 'action' => 'new', 'equipamento_id' => $id ) ) ) . '">Abrir ordem de serviço</a></p>';
		echo '<p><a class="button" href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'new', 'equip' => $id ) ) ) . '">Orçar locação</a></p>';
		echo '</div>';

		$sched = DL_Availability::schedule( $id, dl_today() );
		if ( $sched ) {
			echo '<div class="dl-card"><h3>Agenda</h3><table class="widefat striped"><tbody>';
			foreach ( $sched as $s ) {
				echo '<tr><td><a href="' . esc_url( dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $s['id'] ) ) ) . '">' . esc_html( $s['numero'] ) . '</a></td><td>' . esc_html( dl_date( $s['data_inicio'] ) . ' – ' . dl_date( $s['data_prev_devolucao'] ) ) . '</td><td>' . dl_badge( 'contrato', $s['status'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tbody></table></div>';
		}

		$os = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'ordens_servico' ) . ' WHERE equipamento_id = %d ORDER BY id DESC LIMIT 10', $id ), ARRAY_A );
		if ( $os ) {
			echo '<div class="dl-card"><h3>Manutenções</h3><table class="widefat striped"><tbody>';
			foreach ( $os as $o ) {
				echo '<tr><td><a href="' . esc_url( dl_admin_url( 'dl-os', array( 'action' => 'edit', 'id' => $o['id'] ) ) ) . '">' . esc_html( $o['numero'] ) . '</a></td><td>' . esc_html( dl_date( $o['data_abertura'] ) ) . '</td><td>' . dl_badge( 'os', $o['status'] ) . '</td><td>' . esc_html( dl_money( $o['total'] ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tbody></table></div>';
		}
	}
}
