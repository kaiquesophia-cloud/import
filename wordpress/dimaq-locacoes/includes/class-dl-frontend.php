<?php
/**
 * Site: catálogo de equipamentos, página do equipamento, pedido de orçamento e área do cliente.
 *
 * Shortcodes:
 *   [dimaq_catalogo categoria="ID" colunas="3" destaque="0" limite="0"]
 *   [dimaq_categorias]
 *   [dimaq_orcamento]
 *   [dimaq_area_cliente]
 *   [dimaq_whatsapp texto="Fale conosco"]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Frontend {

	public static function init() {
		add_shortcode( 'dimaq_catalogo', array( __CLASS__, 'catalog' ) );
		add_shortcode( 'dimaq_categorias', array( __CLASS__, 'categories' ) );
		add_shortcode( 'dimaq_orcamento', array( __CLASS__, 'quote_form' ) );
		add_shortcode( 'dimaq_area_cliente', array( __CLASS__, 'client_area' ) );
		add_shortcode( 'dimaq_whatsapp', array( __CLASS__, 'whatsapp_button' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_nopriv_dl_quote', array( __CLASS__, 'handle_quote' ) );
		add_action( 'admin_post_dl_quote', array( __CLASS__, 'handle_quote' ) );
		add_action( 'admin_post_dl_client_request', array( __CLASS__, 'handle_client_request' ) );
	}

	public static function assets() {
		wp_register_style( 'dl-front', DL_URL . 'assets/front.css', array(), DL_VERSION );
		wp_register_script( 'dl-front', DL_URL . 'assets/front.js', array(), DL_VERSION, true );
		wp_localize_script( 'dl-front', 'DLF', array( 'api' => esc_url_raw( rest_url( 'dimaq/v1/' ) ) ) );
	}

	private static function enqueue() {
		wp_enqueue_style( 'dl-front' );
		wp_enqueue_script( 'dl-front' );
		$color = dl_opt( 'cor_primaria', '#f2a900' );
		wp_add_inline_style( 'dl-front', ':root{--dl-primary:' . esc_html( $color ) . ';}' );
	}

	private static function quote_page_url( $equip_id = 0 ) {
		$page = (int) dl_opt( 'pagina_orcamento' );
		$url  = $page ? get_permalink( $page ) : home_url( '/' );
		return $equip_id ? add_query_arg( 'equip', (int) $equip_id, $url ) : $url;
	}

	private static function lowest_price( $e ) {
		$prices = array_filter(
			array(
				'dia'      => (float) $e['valor_diaria'],
				'semana'   => (float) $e['valor_semanal'],
				'quinzena' => (float) $e['valor_quinzenal'],
				'mês'      => (float) $e['valor_mensal'],
			)
		);
		if ( ! $prices ) {
			return '';
		}
		$unit = array_key_first( $prices );
		return 'a partir de ' . dl_money( $prices[ $unit ] ) . '/' . $unit;
	}

	/* ------------------------------------------------------------ catálogo */

	public static function catalog( $atts ) {
		self::enqueue();
		$atts = shortcode_atts(
			array(
				'categoria' => 0,
				'colunas'   => 3,
				'destaque'  => 0,
				'limite'    => 0,
			),
			$atts
		);
		if ( ! empty( $_GET['equipamento'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return self::detail( absint( $_GET['equipamento'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		global $wpdb;
		$cat   = absint( $_GET['categoria'] ?? $atts['categoria'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$q     = sanitize_text_field( wp_unslash( $_GET['busca'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$where = "publicar_site = 1 AND status <> 'inativo'";
		if ( $cat ) {
			$where .= $wpdb->prepare( ' AND categoria_id = %d', $cat );
		}
		if ( $atts['destaque'] ) {
			$where .= ' AND destaque = 1';
		}
		if ( $q ) {
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= $wpdb->prepare( ' AND (nome LIKE %s OR marca LIKE %s OR modelo LIKE %s OR descricao LIKE %s)', $like, $like, $like, $like );
		}
		$limit = (int) $atts['limite'] > 0 ? ' LIMIT ' . (int) $atts['limite'] : '';
		$rows  = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'equipamentos' ) . " WHERE {$where} ORDER BY destaque DESC, nome ASC{$limit}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$cats  = DL_DB::options( 'categorias' );
		$base  = get_permalink();
		ob_start();
		?>
		<div class="dl-catalog">
			<?php if ( ! $atts['destaque'] ) : ?>
			<form class="dl-catalog-filter" method="get" action="<?php echo esc_url( $base ); ?>">
				<input type="search" name="busca" value="<?php echo esc_attr( $q ); ?>" placeholder="Buscar equipamento...">
				<select name="categoria" onchange="this.form.submit()">
					<option value="0">Todas as categorias</option>
					<?php foreach ( $cats as $id => $name ) : ?>
						<option value="<?php echo (int) $id; ?>" <?php selected( $cat, $id ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit">Buscar</button>
			</form>
			<?php endif; ?>
			<div class="dl-grid-cards" style="--dl-cols:<?php echo (int) max( 1, min( 6, $atts['colunas'] ) ); ?>">
				<?php if ( ! $rows ) : ?>
					<p>Nenhum equipamento encontrado.</p>
				<?php endif; ?>
				<?php foreach ( $rows as $e ) : ?>
					<?php $link = add_query_arg( 'equipamento', $e['id'], $base ); ?>
					<article class="dl-card-equip">
						<a href="<?php echo esc_url( $link ); ?>" class="dl-card-img">
							<?php echo $e['foto_id'] ? wp_get_attachment_image( (int) $e['foto_id'], 'medium', false, array( 'alt' => esc_attr( $e['nome'] ) ) ) : '<span class="dl-noimg">sem foto</span>'; ?>
						</a>
						<div class="dl-card-body">
							<?php if ( $e['categoria_id'] && isset( $cats[ $e['categoria_id'] ] ) ) : ?>
								<span class="dl-cat"><?php echo esc_html( $cats[ $e['categoria_id'] ] ); ?></span>
							<?php endif; ?>
							<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $e['nome'] ); ?></a></h3>
							<?php if ( $e['marca'] || $e['modelo'] ) : ?>
								<p class="dl-model"><?php echo esc_html( trim( $e['marca'] . ' ' . $e['modelo'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( dl_opt( 'mostrar_precos' ) && self::lowest_price( $e ) ) : ?>
								<p class="dl-price"><?php echo esc_html( self::lowest_price( $e ) ); ?></p>
							<?php endif; ?>
							<div class="dl-card-actions">
								<a class="dl-btn" href="<?php echo esc_url( self::quote_page_url( $e['id'] ) ); ?>">Solicitar orçamento</a>
								<?php if ( dl_opt( 'empresa_whatsapp' ) ) : ?>
									<a class="dl-btn dl-btn-wa" target="_blank" rel="noopener" href="<?php echo esc_url( dl_whatsapp_link( dl_opt( 'empresa_whatsapp' ), 'Olá! Tenho interesse em alugar: ' . $e['nome'] ) ); ?>">WhatsApp</a>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function detail( $id ) {
		$e = DL_DB::get( 'equipamentos', $id );
		if ( ! $e || ! $e['publicar_site'] || 'inativo' === $e['status'] ) {
			return '<p>Equipamento não encontrado.</p>';
		}
		$specs = array_filter( array_map( 'trim', explode( "\n", (string) $e['especificacoes'] ) ) );
		ob_start();
		?>
		<div class="dl-detail" data-equip="<?php echo (int) $e['id']; ?>">
			<p><a href="<?php echo esc_url( remove_query_arg( 'equipamento' ) ); ?>">← voltar ao catálogo</a></p>
			<div class="dl-detail-grid">
				<div class="dl-detail-img"><?php echo $e['foto_id'] ? wp_get_attachment_image( (int) $e['foto_id'], 'large', false, array( 'alt' => esc_attr( $e['nome'] ) ) ) : ''; ?></div>
				<div>
					<h2><?php echo esc_html( $e['nome'] ); ?></h2>
					<?php if ( $e['marca'] || $e['modelo'] ) : ?><p class="dl-model"><?php echo esc_html( trim( $e['marca'] . ' ' . $e['modelo'] ) ); ?></p><?php endif; ?>
					<?php if ( $e['descricao'] ) : ?><div class="dl-desc"><?php echo wp_kses_post( wpautop( $e['descricao'] ) ); ?></div><?php endif; ?>
					<?php if ( dl_opt( 'mostrar_precos' ) ) : ?>
						<table class="dl-prices">
							<?php foreach ( array( 'valor_diaria' => 'Diária', 'valor_semanal' => 'Semanal', 'valor_quinzenal' => 'Quinzenal', 'valor_mensal' => 'Mensal' ) as $k => $l ) : ?>
								<?php if ( (float) $e[ $k ] > 0 ) : ?><tr><th><?php echo esc_html( $l ); ?></th><td><?php echo esc_html( dl_money( $e[ $k ] ) ); ?></td></tr><?php endif; ?>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
					<div class="dl-avail-check">
						<strong>Consultar disponibilidade e valor</strong>
						<label>Retirada <input type="date" class="dl-av-start" min="<?php echo esc_attr( dl_today() ); ?>" value="<?php echo esc_attr( dl_today() ); ?>"></label>
						<label>Devolução <input type="date" class="dl-av-end" min="<?php echo esc_attr( dl_today() ); ?>" value="<?php echo esc_attr( dl_add_days( dl_today(), 7 ) ); ?>"></label>
						<button type="button" class="dl-btn dl-av-btn">Consultar</button>
						<div class="dl-av-result" aria-live="polite"></div>
					</div>
					<div class="dl-card-actions">
						<a class="dl-btn" href="<?php echo esc_url( self::quote_page_url( $e['id'] ) ); ?>">Solicitar orçamento</a>
						<?php if ( dl_opt( 'empresa_whatsapp' ) ) : ?>
							<a class="dl-btn dl-btn-wa" target="_blank" rel="noopener" href="<?php echo esc_url( dl_whatsapp_link( dl_opt( 'empresa_whatsapp' ), 'Olá! Tenho interesse em alugar: ' . $e['nome'] ) ); ?>">Chamar no WhatsApp</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php if ( $specs ) : ?>
				<h3>Especificações técnicas</h3>
				<table class="dl-specs">
					<?php foreach ( $specs as $line ) : ?>
						<?php $parts = array_map( 'trim', explode( ':', $line, 2 ) ); ?>
						<tr><th><?php echo esc_html( $parts[0] ); ?></th><td><?php echo esc_html( $parts[1] ?? '' ); ?></td></tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function categories() {
		self::enqueue();
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . dl_table( 'categorias' ) . ' ORDER BY ordem, nome', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$page = (int) dl_opt( 'pagina_catalogo' );
		$base = $page ? get_permalink( $page ) : get_permalink();
		ob_start();
		echo '<div class="dl-grid-cards dl-cats" style="--dl-cols:4">';
		foreach ( $rows as $c ) {
			echo '<a class="dl-card-cat" href="' . esc_url( add_query_arg( 'categoria', $c['id'], $base ) ) . '">' . ( $c['imagem_id'] ? wp_get_attachment_image( (int) $c['imagem_id'], 'medium' ) : '' ) . '<span>' . esc_html( $c['nome'] ) . '</span></a>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function whatsapp_button( $atts ) {
		self::enqueue();
		$atts = shortcode_atts( array( 'texto' => 'Fale conosco no WhatsApp', 'mensagem' => 'Olá! Gostaria de um orçamento de locação.' ), $atts );
		if ( ! dl_opt( 'empresa_whatsapp' ) ) {
			return '';
		}
		return '<a class="dl-btn dl-btn-wa" target="_blank" rel="noopener" href="' . esc_url( dl_whatsapp_link( dl_opt( 'empresa_whatsapp' ), $atts['mensagem'] ) ) . '">' . esc_html( $atts['texto'] ) . '</a>';
	}

	/* ------------------------------------------------------------ orçamento */

	public static function quote_form() {
		self::enqueue();
		global $wpdb;
		if ( isset( $_GET['dl_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$num = sanitize_text_field( wp_unslash( $_GET['dl_ok'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$msg = '<div class="dl-success"><h3>Pedido recebido!</h3><p>Seu protocolo é <strong>' . esc_html( $num ) . '</strong>. Vamos conferir a disponibilidade e retornar em breve com o orçamento.</p>';
			if ( dl_opt( 'empresa_whatsapp' ) ) {
				$msg .= '<p><a class="dl-btn dl-btn-wa" target="_blank" rel="noopener" href="' . esc_url( dl_whatsapp_link( dl_opt( 'empresa_whatsapp' ), 'Olá! Acabei de enviar o pedido de orçamento ' . $num . ' pelo site.' ) ) . '">Agilizar pelo WhatsApp</a></p>';
			}
			return $msg . '</div>';
		}
		$equips   = $wpdb->get_results( 'SELECT id, nome FROM ' . dl_table( 'equipamentos' ) . " WHERE publicar_site = 1 AND status <> 'inativo' ORDER BY nome", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$selected = absint( $_GET['equip'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$error    = isset( $_GET['dl_erro'] ) ? sanitize_text_field( wp_unslash( $_GET['dl_erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$user     = wp_get_current_user();
		ob_start();
		?>
		<form class="dl-quote" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'dl_quote', 'dl_nonce' ); ?>
			<input type="hidden" name="action" value="dl_quote">
			<input type="hidden" name="voltar" value="<?php echo esc_url( get_permalink() ); ?>">
			<div class="dl-hp" aria-hidden="true"><label>Não preencha <input type="text" name="site_url" tabindex="-1" autocomplete="off"></label></div>
			<?php if ( $error ) : ?><div class="dl-error"><?php echo esc_html( $error ); ?></div><?php endif; ?>

			<fieldset><legend>Equipamentos</legend>
				<div class="dl-quote-items">
					<div class="dl-quote-item">
						<select name="equip[]" required>
							<option value="">Escolha o equipamento</option>
							<?php foreach ( $equips as $e ) : ?>
								<option value="<?php echo (int) $e['id']; ?>" <?php selected( $selected, $e['id'] ); ?>><?php echo esc_html( $e['nome'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="number" name="qtd[]" value="1" min="1" max="999" aria-label="Quantidade">
					</div>
				</div>
				<button type="button" class="dl-link dl-quote-add">+ adicionar outro equipamento</button>
				<div class="dl-row">
					<label>Data de retirada / entrega <input type="date" name="inicio" min="<?php echo esc_attr( dl_today() ); ?>" value="<?php echo esc_attr( dl_today() ); ?>" required></label>
					<label>Data de devolução <input type="date" name="fim" min="<?php echo esc_attr( dl_today() ); ?>" value="<?php echo esc_attr( dl_add_days( dl_today(), 7 ) ); ?>" required></label>
				</div>
				<label>Precisa de entrega?
					<select name="entrega"><option value="retirada">Vou retirar</option><option value="entrega">Quero entrega no local</option><option value="entrega_coleta">Entrega e coleta</option></select>
				</label>
				<label>Endereço da obra / entrega <input type="text" name="endereco" maxlength="250"></label>
			</fieldset>

			<fieldset><legend>Seus dados</legend>
				<div class="dl-row">
					<label>Nome ou razão social * <input type="text" name="nome" required maxlength="190" value="<?php echo esc_attr( $user->exists() ? $user->display_name : '' ); ?>"></label>
					<label>CPF ou CNPJ <input type="text" name="documento" maxlength="18" inputmode="numeric"></label>
				</div>
				<div class="dl-row">
					<label>WhatsApp / telefone * <input type="tel" name="telefone" required maxlength="20"></label>
					<label>E-mail * <input type="email" name="email" required maxlength="190" value="<?php echo esc_attr( $user->exists() ? $user->user_email : '' ); ?>"></label>
				</div>
				<label>Cidade <input type="text" name="cidade" maxlength="100"></label>
				<label>Mensagem <textarea name="mensagem" rows="3" maxlength="2000"></textarea></label>
				<label class="dl-consent"><input type="checkbox" name="lgpd" value="1" required> Autorizo o uso dos meus dados para receber este orçamento (LGPD).</label>
			</fieldset>
			<button type="submit" class="dl-btn dl-btn-lg">Enviar pedido de orçamento</button>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function handle_quote() {
		$back = esc_url_raw( wp_unslash( $_POST['voltar'] ?? home_url( '/' ) ) );
		$back = wp_validate_redirect( $back, home_url( '/' ) );
		if ( ! isset( $_POST['dl_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['dl_nonce'] ), 'dl_quote' ) ) {
			wp_safe_redirect( add_query_arg( 'dl_erro', rawurlencode( 'Sessão expirada, envie novamente.' ), $back ) );
			exit;
		}
		if ( ! empty( $_POST['site_url'] ) ) { // honeypot
			wp_safe_redirect( add_query_arg( 'dl_ok', 'OK', $back ) );
			exit;
		}
		$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$key = 'dl_rl_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= 5 ) {
			wp_safe_redirect( add_query_arg( 'dl_erro', rawurlencode( 'Muitos pedidos em sequência. Tente de novo em alguns minutos ou chame no WhatsApp.' ), $back ) );
			exit;
		}
		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		$name  = sanitize_text_field( wp_unslash( $_POST['nome'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone = sanitize_text_field( wp_unslash( $_POST['telefone'] ?? '' ) );
		$doc   = dl_digits( wp_unslash( $_POST['documento'] ?? '' ) );
		$start = sanitize_text_field( wp_unslash( $_POST['inicio'] ?? '' ) );
		$end   = sanitize_text_field( wp_unslash( $_POST['fim'] ?? '' ) );
		$fail  = function ( $msg ) use ( $back ) {
			wp_safe_redirect( add_query_arg( 'dl_erro', rawurlencode( $msg ), $back ) );
			exit;
		};
		if ( ! $name || ! is_email( $email ) || strlen( dl_digits( $phone ) ) < 10 ) {
			$fail( 'Preencha nome, e-mail válido e telefone com DDD.' );
		}
		if ( $doc && ! dl_valid_document( $doc ) ) {
			$fail( 'CPF/CNPJ inválido.' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) || $end < $start || $start < dl_today() ) {
			$fail( 'Confira as datas de retirada e devolução.' );
		}
		$equips = array_map( 'absint', (array) ( $_POST['equip'] ?? array() ) );
		$qtys   = array_map( 'absint', (array) ( $_POST['qtd'] ?? array() ) );
		$lines  = array();
		foreach ( $equips as $i => $eid ) {
			$e = $eid ? DL_DB::get( 'equipamentos', $eid ) : null;
			if ( $e && $e['publicar_site'] && 'inativo' !== $e['status'] ) {
				$lines[] = array( $e, max( 1, min( 999, $qtys[ $i ] ?? 1 ) ) );
			}
		}
		if ( ! $lines ) {
			$fail( 'Escolha pelo menos um equipamento.' );
		}

		$client_id = self::find_or_create_client( $name, $email, $phone, $doc, sanitize_text_field( wp_unslash( $_POST['cidade'] ?? '' ) ) );
		$entrega   = in_array( $_POST['entrega'] ?? '', array( 'retirada', 'entrega', 'entrega_coleta' ), true ) ? sanitize_key( $_POST['entrega'] ) : 'retirada';
		$contract  = DL_DB::insert(
			'contratos',
			array(
				'cliente_id'          => $client_id,
				'status'              => 'solicitacao',
				'data_inicio'         => $start,
				'data_prev_devolucao' => $end,
				'validade_orcamento'  => dl_add_days( dl_today(), (int) dl_opt( 'validade_orcamento', 7 ) ),
				'entrega'             => $entrega,
				'endereco_entrega'    => sanitize_text_field( wp_unslash( $_POST['endereco'] ?? '' ) ),
				'obs_interna'         => 'Mensagem do site: ' . sanitize_textarea_field( wp_unslash( $_POST['mensagem'] ?? '' ) ),
				'origem'              => 'site',
			)
		);
		$number = dl_doc_number( dl_opt( 'prefixo_contrato', 'LOC' ), $contract );
		DL_DB::update( 'contratos', $contract, array( 'numero' => $number ) );
		$days = DL_Contracts::rental_days( $start, $end );
		global $wpdb;
		$order   = 0;
		$summary = array();
		foreach ( $lines as $l ) {
			list( $e, $q ) = $l;
			$best          = DL_Pricing::best_price( DL_Contracts::rates( $e ), $days );
			$wpdb->insert(
				dl_table( 'itens' ),
				array(
					'doc_tipo'     => 'contrato',
					'doc_id'       => $contract,
					'ref_tipo'     => 'equipamento',
					'ref_id'       => $e['id'],
					'descricao'    => $e['nome'] . ( $best['descricao'] ? ' — ' . $best['descricao'] : '' ),
					'qtd'          => $q,
					'periodo_tipo' => 'pacote',
					'periodos'     => 1,
					'valor_unit'   => $best['total'],
					'total'        => DL_Pricing::item_total( $q, 1, $best['total'] ),
					'ordem'        => $order++,
				)
			);
			$free      = DL_Availability::available( $e['id'], $start, $end );
			$summary[] = sprintf( '%s × %d (livres no período: %s)', $e['nome'], $q, dl_num( $free, 0 ) );
		}
		DL_Contracts::recalc( $contract );
		dl_log( 'contratos', $contract, 'Pedido recebido pelo site' );
		wp_cache_delete( 'dl_pending' );

		$admin_link = dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $contract ) );
		wp_mail(
			dl_opt( 'email_notificacao' ),
			'Novo pedido de orçamento ' . $number . ' — ' . $name,
			"Cliente: {$name}\nTelefone: {$phone}\nE-mail: {$email}\nPeríodo: " . dl_date( $start ) . ' a ' . dl_date( $end ) . " ({$days} dias)\n\n" . implode( "\n", $summary ) . "\n\nAbrir no sistema: {$admin_link}",
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
		if ( dl_opt( 'email_cliente' ) ) {
			wp_mail( $email, 'Recebemos seu pedido ' . $number . ' — ' . dl_opt( 'empresa_nome' ), "Olá, {$name}!\n\nRecebemos seu pedido de orçamento ({$number}) para o período de " . dl_date( $start ) . ' a ' . dl_date( $end ) . ".\n\nEm breve retornaremos com valores e disponibilidade confirmados.\n\n" . dl_opt( 'empresa_nome' ) . ' · ' . dl_opt( 'empresa_whatsapp' ) );
		}
		do_action( 'dl_quote_received', $contract );
		wp_safe_redirect( add_query_arg( 'dl_ok', rawurlencode( $number ), remove_query_arg( array( 'dl_erro', 'equip' ), $back ) ) );
		exit;
	}

	/**
	 * Reaproveita cliente pelo CPF/CNPJ (ou pelo e-mail) sem sobrescrever o cadastro existente.
	 */
	private static function find_or_create_client( $name, $email, $phone, $doc, $city ) {
		global $wpdb;
		$t  = dl_table( 'clientes' );
		$id = 0;
		if ( $doc ) {
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE documento = %s LIMIT 1", $doc ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		if ( ! $id ) {
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE email = %s LIMIT 1", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		if ( $id ) {
			return $id;
		}
		return DL_DB::insert(
			'clientes',
			array(
				'tipo'      => 11 === strlen( $doc ) ? 'PF' : 'PJ',
				'nome'      => $name,
				'documento' => $doc,
				'email'     => $email,
				'telefone'  => $phone,
				'whatsapp'  => $phone,
				'cidade'    => $city,
				'wp_user_id' => is_user_logged_in() && wp_get_current_user()->user_email === $email ? get_current_user_id() : 0,
				'obs'       => 'Cadastrado pelo formulário do site.',
			)
		);
	}

	/* -------------------------------------------------------- área do cliente */

	public static function current_client() {
		if ( ! is_user_logged_in() ) {
			return null;
		}
		global $wpdb;
		$user = wp_get_current_user();
		$row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'clientes' ) . ' WHERE wp_user_id = %d LIMIT 1', $user->ID ), ARRAY_A );
		return $row ? $row : null;
	}

	public static function client_area() {
		self::enqueue();
		if ( ! is_user_logged_in() ) {
			return '<div class="dl-login"><p>Entre para acompanhar suas locações, faturas e documentos.</p>' . wp_login_form( array( 'echo' => false, 'redirect' => get_permalink() ) ) . '</div>';
		}
		$cli = self::current_client();
		if ( ! $cli ) {
			return '<p>Seu usuário ainda não está ligado a um cadastro de cliente. Fale com a ' . esc_html( dl_opt( 'empresa_nome' ) ) . ' para liberar o acesso.</p>';
		}
		global $wpdb;
		$contracts = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'contratos' ) . " WHERE cliente_id = %d AND status <> 'cancelado' ORDER BY id DESC LIMIT 50", $cli['id'] ), ARRAY_A );
		$bills     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'financeiro' ) . " WHERE cliente_id = %d AND tipo = 'receber' AND status <> 'cancelado' ORDER BY vencimento DESC LIMIT 50", $cli['id'] ), ARRAY_A );
		$notes     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'notas' ) . " WHERE cliente_id = %d AND status = 'autorizada' ORDER BY id DESC LIMIT 30", $cli['id'] ), ARRAY_A );
		$msg       = isset( $_GET['dl_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['dl_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		ob_start();
		?>
		<div class="dl-client-area">
			<h2>Olá, <?php echo esc_html( $cli['contato'] ? $cli['contato'] : $cli['nome'] ); ?></h2>
			<?php if ( $msg ) : ?><div class="dl-success"><?php echo esc_html( $msg ); ?></div><?php endif; ?>
			<p><a class="dl-btn" href="<?php echo esc_url( self::quote_page_url() ); ?>">Novo pedido de orçamento</a></p>

			<h3>Minhas locações</h3>
			<div class="dl-table-wrap"><table class="dl-table-front">
				<thead><tr><th>Nº</th><th>Período</th><th>Situação</th><th>Total</th><th>Documentos</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $contracts ) : ?><tr><td colspan="6">Nenhuma locação ainda.</td></tr><?php endif; ?>
				<?php foreach ( $contracts as $c ) : ?>
					<?php $type = in_array( $c['status'], array( 'orcamento', 'solicitacao' ), true ) ? 'orcamento' : 'contrato'; ?>
					<tr>
						<td><?php echo esc_html( $c['numero'] ); ?></td>
						<td><?php echo esc_html( dl_date( $c['data_inicio'] ) . ' a ' . dl_date( $c['data_prev_devolucao'] ) ); ?></td>
						<td><?php echo dl_badge( 'contrato', DL_Contracts::is_late( $c ) ? 'atrasado' : $c['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><?php echo esc_html( dl_money( $c['total'] ) ); ?></td>
						<td><a target="_blank" href="<?php echo esc_url( DL_Documents::public_url( $type, $c['id'] ) ); ?>"><?php echo 'orcamento' === $type ? 'Orçamento' : 'Contrato'; ?></a>
							<?php if ( in_array( $c['status'], array( 'ativo', 'encerrado' ), true ) ) : ?> · <a target="_blank" href="<?php echo esc_url( DL_Documents::public_url( 'fatura', $c['id'] ) ); ?>">Fatura</a><?php endif; ?></td>
						<td>
							<?php if ( 'ativo' === $c['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dl-inline">
									<?php wp_nonce_field( 'dl_client_request_' . $c['id'] ); ?>
									<input type="hidden" name="action" value="dl_client_request"><input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>"><input type="hidden" name="voltar" value="<?php echo esc_url( get_permalink() ); ?>">
									<select name="pedido"><option value="renovar">Quero renovar</option><option value="coleta">Pode coletar / vou devolver</option><option value="suporte">Equipamento com problema</option></select>
									<button class="dl-btn dl-btn-sm">Enviar</button>
								</form>
							<?php elseif ( 'orcamento' === $c['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dl-inline">
									<?php wp_nonce_field( 'dl_client_request_' . $c['id'] ); ?>
									<input type="hidden" name="action" value="dl_client_request"><input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>"><input type="hidden" name="voltar" value="<?php echo esc_url( get_permalink() ); ?>">
									<input type="hidden" name="pedido" value="aprovar"><button class="dl-btn dl-btn-sm">Aprovar orçamento</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>

			<h3>Faturas</h3>
			<div class="dl-table-wrap"><table class="dl-table-front">
				<thead><tr><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Situação</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $bills ) : ?><tr><td colspan="5">Nenhuma fatura.</td></tr><?php endif; ?>
				<?php foreach ( $bills as $b ) : ?>
					<?php $st = 'aberto' === $b['status'] && $b['vencimento'] < dl_today() ? 'vencido' : $b['status']; ?>
					<tr>
						<td><?php echo esc_html( $b['descricao'] ); ?></td>
						<td><?php echo esc_html( dl_date( $b['vencimento'] ) ); ?></td>
						<td><?php echo esc_html( dl_money( $b['valor'] ) ); ?></td>
						<td><?php echo dl_badge( 'financeiro', $st ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><?php if ( 'pago' === $b['status'] ) : ?><a target="_blank" href="<?php echo esc_url( DL_Documents::public_url( 'recibo', $b['id'] ) ); ?>">Recibo</a><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<?php if ( dl_opt( 'dados_bancarios' ) ) : ?>
				<p class="dl-bank"><strong>Para pagamento:</strong><br><?php echo nl2br( esc_html( dl_opt( 'dados_bancarios' ) ) ); ?></p>
			<?php endif; ?>

			<?php if ( $notes ) : ?>
				<h3>Notas fiscais</h3>
				<ul>
				<?php foreach ( $notes as $n ) : ?>
					<li><?php echo esc_html( strtoupper( $n['tipo'] ) . ' ' . $n['numero'] . ' — ' . dl_money( $n['valor'] ) ); ?> <?php if ( $n['pdf_url'] ) : ?><a target="_blank" href="<?php echo esc_url( $n['pdf_url'] ); ?>">PDF</a><?php endif; ?></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function handle_client_request() {
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'dl_client_request_' . $id );
		$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['voltar'] ?? home_url( '/' ) ) ), home_url( '/' ) );
		$cli  = self::current_client();
		$c    = DL_DB::get( 'contratos', $id );
		if ( ! $cli || ! $c || (int) $c['cliente_id'] !== (int) $cli['id'] ) {
			wp_die( 'Sem permissão.', '', 403 );
		}
		$labels = array(
			'renovar' => 'Cliente pediu RENOVAÇÃO',
			'coleta'  => 'Cliente pediu COLETA / vai devolver',
			'suporte' => 'Cliente relatou PROBLEMA no equipamento',
			'aprovar' => 'Cliente APROVOU o orçamento',
		);
		$req = sanitize_key( $_POST['pedido'] ?? '' );
		if ( ! isset( $labels[ $req ] ) ) {
			wp_die( 'Pedido inválido.' );
		}
		if ( 'aprovar' === $req && 'orcamento' === $c['status'] ) {
			$r = DL_Contracts::reserve( $c );
			if ( is_wp_error( $r ) ) {
				$labels[ $req ] .= ' — reserva automática não foi possível: ' . $r->get_error_message();
			}
		}
		dl_log( 'contratos', $id, $labels[ $req ], 'pela área do cliente' );
		wp_mail( dl_opt( 'email_notificacao' ), $labels[ $req ] . ' — ' . $c['numero'], $cli['nome'] . ' (' . $cli['telefone'] . ")\n\n" . dl_admin_url( 'dl-contratos', array( 'action' => 'edit', 'id' => $id ) ) );
		wp_safe_redirect( add_query_arg( 'dl_msg', rawurlencode( 'Recebemos sua solicitação. Entraremos em contato.' ), $back ) );
		exit;
	}
}
