<?php
/**
 * O sistema em si: endereço próprio (/sistema), login separado do WordPress e a
 * aplicação de tela única que conversa com a API interna.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_App {

	const SLUG = 'sistema';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 0 );
	}

	/** Crédito de quem desenvolveu o sistema (logo em assets/app/seo-amplify.png, se existir). */
	public static function credit() {
		$file = 'assets/app/seo-amplify.png';
		return array(
			'nome' => 'SEO Amplify',
			'logo' => file_exists( DL_DIR . $file ) ? DL_URL . $file : null,
		);
	}

	public static function credit_html() {
		$c = self::credit();
		$mark = $c['logo'] ? '<img src="' . esc_url( $c['logo'] ) . '" alt="' . esc_attr( $c['nome'] ) . '">' : '<b class="seo-text">SEO <span>AMPLIFY</span></b>';
		return '<div class="credit">Desenvolvido por ' . $mark . '</div>';
	}

	public static function rewrite() {
		add_rewrite_rule( '^' . self::SLUG . '/?$', 'index.php?dl_app=1', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'dl_app';
		return $vars;
	}

	private static function requested() {
		return get_query_var( 'dl_app' ) || isset( $_GET['dl_app'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	public static function maybe_render() {
		if ( ! self::requested() ) {
			return;
		}
		nocache_headers();
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: same-origin' );

		$error = '';
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['dl_login_nonce'] ) ) {
			$error = self::handle_login();
		}
		if ( ! is_user_logged_in() ) {
			self::render_login( $error );
			exit;
		}
		if ( ! current_user_can( 'dl_operar' ) ) {
			self::render_login( 'Seu usuário não tem acesso ao sistema da locadora. Fale com o administrador.', true );
			exit;
		}
		self::render_app();
		exit;
	}

	/** @return string mensagem de erro (vazia se entrou). */
	private static function handle_login() {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['dl_login_nonce'] ), 'dl_login' ) ) {
			return 'A página expirou. Tente de novo.';
		}
		$user = wp_signon(
			array(
				'user_login'    => sanitize_text_field( wp_unslash( $_POST['usuario'] ?? '' ) ),
				'user_password' => (string) wp_unslash( $_POST['senha'] ?? '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'remember'      => ! empty( $_POST['lembrar'] ),
			),
			is_ssl()
		);
		if ( is_wp_error( $user ) ) {
			return 'Usuário ou senha incorretos.';
		}
		wp_set_current_user( $user->ID );
		wp_safe_redirect( dl_app_url() );
		exit;
	}

	private static function head( $title ) {
		$v = DL_VERSION;
		?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2b2b2b">
<title><?php echo esc_html( $title ); ?></title>
<link rel="icon" href="<?php echo esc_url( DL_URL . 'assets/app/icon.svg' ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( DL_URL . 'assets/app/app.css?v=' . $v ); ?>">
<style>:root{--accent:<?php echo esc_html( dl_opt( 'cor_primaria', '#f5a400' ) ); ?>}</style>
</head>
		<?php
	}

	public static function render_login( $error = '', $logged_without_access = false ) {
		self::head( 'Entrar — ' . dl_opt( 'empresa_nome' ) );
		?>
<body class="dl-login-page">
	<main class="login-wrap">
		<div class="login-brand">
			<img src="<?php echo esc_url( DL_URL . 'assets/app/logo-dimaq.png' ); ?>" alt="<?php echo esc_attr( dl_opt( 'empresa_nome' ) ); ?>" class="login-logo">
			<p>Sistema de gestão de locações</p>
		</div>
		<form class="login-card" method="post" action="<?php echo esc_url( dl_app_url() ); ?>">
			<h1>Entrar</h1>
			<?php if ( $error ) : ?>
				<div class="login-error" role="alert"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>
			<?php if ( $logged_without_access ) : ?>
				<a class="btn btn-block" href="<?php echo esc_url( wp_logout_url( dl_app_url() ) ); ?>">Sair e entrar com outro usuário</a>
			<?php else : ?>
				<?php wp_nonce_field( 'dl_login', 'dl_login_nonce' ); ?>
				<label>Usuário ou e-mail<input type="text" name="usuario" autocomplete="username" required autofocus></label>
				<label>Senha<input type="password" name="senha" autocomplete="current-password" required></label>
				<label class="login-remember"><input type="checkbox" name="lembrar" value="1" checked> Manter conectado</label>
				<button type="submit" class="btn btn-primary btn-block">Entrar no sistema</button>
				<a class="login-forgot" href="<?php echo esc_url( wp_lostpassword_url( dl_app_url() ) ); ?>">Esqueci minha senha</a>
			<?php endif; ?>
		</form>
		<p class="login-foot"><?php echo esc_html( dl_opt( 'empresa_nome' ) . ( dl_opt( 'empresa_telefone' ) ? ' · ' . dl_opt( 'empresa_telefone' ) : '' ) ); ?></p>
		<?php echo self::credit_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em credit_html(). ?>
	</main>
</body>
</html>
		<?php
	}

	/** Dados iniciais entregues à aplicação. */
	public static function bootstrap() {
		$user = wp_get_current_user();
		return array(
			'api'        => esc_url_raw( rest_url( DL_API::NS . '/app/' ) ),
			'wpApi'      => esc_url_raw( rest_url( 'wp/v2/' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'appUrl'     => dl_app_url(),
			'logoutUrl'  => html_entity_decode( wp_logout_url( dl_app_url() ), ENT_QUOTES, 'UTF-8' ), // a tela usa o endereço cru
			'logo'       => DL_URL . 'assets/app/logo-dimaq.png',
			'criador'    => self::credit(),
			'hoje'       => dl_today(),
			'demo'       => DL_Demo::active(),
			'vazio'      => ! DL_Demo::active() && ! (int) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . dl_table( 'contratos' ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			'inclusivo'  => DL_Contracts::inclusive(),
			'empresa'    => array(
				'nome'     => dl_opt( 'empresa_nome' ),
				'whatsapp' => dl_opt( 'empresa_whatsapp' ),
			),
			'usuario'    => array(
				'nome'       => $user->display_name,
				'iniciais'   => strtoupper( mb_substr( $user->display_name, 0, 1 ) . mb_substr( (string) strstr( $user->display_name, ' ' ), 1, 1 ) ),
				'financeiro' => current_user_can( 'dl_financeiro' ),
				'config'     => current_user_can( 'dl_config' ),
				'admin'      => current_user_can( 'manage_options' ),
				'upload'     => current_user_can( 'upload_files' ),
			),
			'wpAdmin'    => current_user_can( 'manage_options' ) ? admin_url() : null,
			'contagens'  => self::counts(),
			'modulos'    => DL_API::schema(),
			'status'     => array(
				'contrato'    => dl_statuses( 'contrato' ) + array( 'atrasado' => 'Em atraso' ),
				'equipamento' => dl_statuses( 'equipamento' ) + array( 'locado' => 'Locado' ),
				'os'          => dl_statuses( 'os' ),
				'venda'       => dl_statuses( 'venda' ),
				'financeiro'  => dl_statuses( 'financeiro' ) + array( 'vencido' => 'Vencido' ),
				'nota'        => dl_statuses( 'nota' ),
			),
			'pagamentos' => dl_payment_methods(),
			'periodos'   => dl_period_types(),
			'relatorios' => array_map(
				function ( $r ) {
					return array( 'titulo' => $r[0], 'ok' => current_user_can( $r[1] ) );
				},
				DL_Reports::reports()
			),
		);
	}

	/** Números do menu lateral (pedidos do site e devoluções atrasadas). */
	private static function counts() {
		global $wpdb;
		$t = dl_table( 'contratos' );
		return array(
			'solicitacoes' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'solicitacao'" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'atrasados'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE status = 'ativo' AND data_prev_devolucao < %s", dl_today() ) ), // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function render_app() {
		self::head( dl_opt( 'empresa_nome' ) . ' — Sistema' );
		$v = DL_VERSION;
		?>
<body class="dl-app-page">
	<div id="app"><div class="boot"><img src="<?php echo esc_url( DL_URL . 'assets/app/logo-dimaq.png' ); ?>" alt=""><span class="spinner"></span></div></div>
	<script>window.DIMAQ = <?php echo wp_json_encode( self::bootstrap() ); ?>;</script>
	<script src="<?php echo esc_url( DL_URL . 'assets/app/vendor/vue.global.prod.js?v=3.5.13' ); ?>"></script>
	<script src="<?php echo esc_url( DL_URL . 'assets/app/vendor/chart.umd.js?v=4.4.7' ); ?>"></script>
	<script src="<?php echo esc_url( DL_URL . 'assets/app/app.js?v=' . $v ); ?>"></script>
</body>
</html>
		<?php
	}
}
