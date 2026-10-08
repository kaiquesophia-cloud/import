<?php
/**
 * Tela genérica de cadastro: lista com busca/filtros/exportação e formulário de edição.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Crud {

	const PER_PAGE = 30;

	public static function init() {
		add_action( 'admin_post_dl_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_dl_delete', array( __CLASS__, 'delete' ) );
		add_action( 'admin_post_dl_export', array( __CLASS__, 'export' ) );
	}

	/** Ponto de entrada de cada página de cadastro. */
	public static function render( $key ) {
		$def = DL_Modules::get( $key );
		dl_require_cap( $def['cap'] );
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
			self::render_form( $def, 'edit' === $action ? absint( $_GET['id'] ?? 0 ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		} else {
			self::render_list( $def );
		}
	}

	/* ------------------------------------------------------------------ lista */

	/** Monta WHERE a partir da busca e dos filtros da URL. */
	public static function build_where( $def, $req ) {
		global $wpdb;
		$where = array( '1=1' );
		$q     = isset( $req['s'] ) ? sanitize_text_field( wp_unslash( $req['s'] ) ) : '';
		if ( '' !== $q ) {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$or   = array();
			foreach ( $def['fields'] as $name => $f ) {
				if ( ! empty( $f['search'] ) ) {
					$or[] = $wpdb->prepare( "`{$name}` LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL
				}
			}
			if ( isset( $def['fields']['cliente_id'] ) ) {
				$or[] = $wpdb->prepare( 'cliente_id IN (SELECT id FROM ' . dl_table( 'clientes' ) . ' WHERE nome LIKE %s OR documento LIKE %s)', $like, '%' . $wpdb->esc_like( dl_digits( $q ) ?: $q ) . '%' );
			}
			if ( $or ) {
				$where[] = '(' . implode( ' OR ', $or ) . ')';
			}
		}
		foreach ( $def['filters'] ?? array() as $filter ) {
			if ( isset( $req[ 'f_' . $filter ] ) && '' !== $req[ 'f_' . $filter ] ) {
				$value = sanitize_text_field( wp_unslash( $req[ 'f_' . $filter ] ) );
				$custom = apply_filters( 'dl_filter_where_' . $def['key'], null, $filter, $value );
				if ( null !== $custom ) {
					$where[] = $custom;
				} else {
					$where[] = $wpdb->prepare( "`{$filter}` = %s", $value ); // phpcs:ignore WordPress.DB.PreparedSQL
				}
			}
		}
		if ( ! empty( $def['date_filter'] ) ) {
			$col = $def['date_filter'];
			if ( ! empty( $req['de'] ) ) {
				$where[] = $wpdb->prepare( "`{$col}` >= %s", sanitize_text_field( wp_unslash( $req['de'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			if ( ! empty( $req['ate'] ) ) {
				$where[] = $wpdb->prepare( "`{$col}` <= %s", sanitize_text_field( wp_unslash( $req['ate'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
		}
		return implode( ' AND ', $where );
	}

	public static function render_list( $def ) {
		global $wpdb;
		$req    = $_GET; // phpcs:ignore WordPress.Security.NonceVerification
		$where  = self::build_where( $def, $req );
		$paged  = max( 1, absint( $req['paged'] ?? 1 ) );
		$table  = dl_table( $def['table'] );
		$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$offset = ( $paged - 1 ) * self::PER_PAGE;
		$rows   = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY {$def['order']} LIMIT {$offset}, " . self::PER_PAGE, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$cols   = array_filter( $def['fields'], function ( $f ) {
			return ! empty( $f['list'] );
		} );
		?>
		<div class="wrap dl-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( $def['plural'] ); ?></h1>
			<?php if ( empty( $def['no_create'] ) ) : ?>
				<a href="<?php echo esc_url( dl_admin_url( $def['page'], array( 'action' => 'new' ) ) ); ?>" class="page-title-action">Adicionar</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $req, array( 'action' => 'dl_export', 'module' => $def['key'] ) ), admin_url( 'admin-post.php' ) ), 'dl_export' ) ); ?>" class="page-title-action">Exportar CSV</a>
			<?php do_action( 'dl_list_header_' . $def['key'] ); ?>
			<hr class="wp-header-end">
			<?php dl_render_notice(); ?>

			<form method="get" class="dl-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( $def['page'] ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( wp_unslash( $req['s'] ?? '' ) ); ?>" placeholder="Buscar...">
				<?php
				foreach ( $def['filters'] ?? array() as $filter ) {
					$f       = $def['fields'][ $filter ];
					$options = apply_filters( 'dl_filter_options_' . $def['key'], self::field_options( $f ), $filter );
					$current = isset( $req[ 'f_' . $filter ] ) ? wp_unslash( $req[ 'f_' . $filter ] ) : '';
					echo '<select name="f_' . esc_attr( $filter ) . '"><option value="">' . esc_html( $f['label'] ) . ': todos</option>';
					foreach ( $options as $v => $l ) {
						if ( '' === $v || 0 === $v ) {
							continue;
						}
						echo '<option value="' . esc_attr( $v ) . '" ' . selected( (string) $current, (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
					}
					echo '</select>';
				}
				if ( ! empty( $def['date_filter'] ) ) {
					echo ' <label>de <input type="date" name="de" value="' . esc_attr( $req['de'] ?? '' ) . '"></label> <label>até <input type="date" name="ate" value="' . esc_attr( $req['ate'] ?? '' ) . '"></label>';
				}
				?>
				<button class="button">Filtrar</button>
				<a class="button-link" href="<?php echo esc_url( dl_admin_url( $def['page'] ) ); ?>">limpar</a>
			</form>

			<p class="dl-count"><?php echo esc_html( sprintf( '%d registro(s)', $total ) ); ?></p>
			<table class="wp-list-table widefat fixed striped dl-table">
				<thead><tr>
					<?php foreach ( $cols as $c ) : ?>
						<th><?php echo esc_html( $c['label'] ); ?></th>
					<?php endforeach; ?>
					<th class="dl-actions-col">Ações</th>
				</tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="<?php echo count( $cols ) + 1; ?>">Nenhum registro encontrado.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php $row = apply_filters( 'dl_list_row_' . $def['key'], $row ); ?>
					<tr>
						<?php
						$first = true;
						foreach ( $cols as $name => $c ) {
							$value = self::display_value( $c, $row[ $name ] ?? '', $row, $name );
							if ( $first ) {
								$value = '<a class="row-title" href="' . esc_url( dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $row['id'] ) ) ) . '">' . ( '' === $value ? '#' . (int) $row['id'] : $value ) . '</a>';
								$first = false;
							}
							echo '<td>' . $value . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- display_value escapa.
						}
						?>
						<td class="dl-actions-col">
							<?php
							$actions = array(
								'edit' => '<a href="' . esc_url( dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $row['id'] ) ) ) . '">Abrir</a>',
							);
							$actions = apply_filters( 'dl_row_actions_' . $def['key'], $actions, $row );
							echo implode( ' | ', $actions ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$pages = (int) ceil( $total / self::PER_PAGE );
			if ( $pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				);
				echo '</div></div>';
			}
			do_action( 'dl_list_footer_' . $def['key'], $where );
			?>
		</div>
		<?php
	}

	/** Valor formatado e escapado para a lista. */
	public static function display_value( $f, $value, $row = array(), $name = '' ) {
		$type = $f['type'];
		if ( ! empty( $f['badge'] ) ) {
			return dl_badge( $f['badge'], $value );
		}
		switch ( $type ) {
			case 'money':
			case 'readonly_money':
				return esc_html( dl_money( $value ) );
			case 'decimal':
				return esc_html( dl_num( $value, 2 ) );
			case 'date':
			case 'readonly_date':
				return esc_html( dl_date( $value ) );
			case 'checkbox':
				return $value ? '✔' : '';
			case 'document':
				return esc_html( dl_format_document( $value ) );
			case 'relation':
			case 'readonly_relation':
				return esc_html( DL_DB::label( $f['rel'], (int) $value, $f['rel_label'] ?? 'nome' ) );
			case 'select':
			case 'readonly_status':
				$opts = self::field_options( $f );
				return esc_html( isset( $opts[ $value ] ) ? $opts[ $value ] : $value );
			default:
				return esc_html( (string) $value );
		}
	}

	public static function field_options( $f ) {
		if ( isset( $f['options'] ) ) {
			return $f['options'];
		}
		if ( in_array( $f['type'], array( 'relation', 'readonly_relation' ), true ) ) {
			return array( 0 => '—' ) + DL_DB::options( $f['rel'], $f['rel_label'] ?? 'nome' );
		}
		if ( 'checkbox' === $f['type'] ) {
			return array( '1' => 'Sim', '0' => 'Não' );
		}
		return array();
	}

	/* ------------------------------------------------------------- formulário */

	public static function render_form( $def, $id ) {
		$row = $id ? DL_DB::get( $def['table'], $id ) : null;
		if ( $id && ! $row ) {
			wp_die( 'Registro não encontrado.' );
		}
		if ( ! $id && ! empty( $def['no_create'] ) ) {
			wp_die( 'Este cadastro não pode ser criado manualmente.' );
		}
		if ( ! $row ) {
			$row = array();
			foreach ( $def['fields'] as $name => $f ) {
				$row[ $name ] = isset( $_GET[ $name ] ) ? sanitize_text_field( wp_unslash( $_GET[ $name ] ) ) : ( $f['default'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			$row = apply_filters( 'dl_new_row_' . $def['key'], $row );
		}
		$title = $id ? $def['singular'] . ( ! empty( $row['numero'] ) ? ' ' . $row['numero'] : ' #' . $id ) : 'Novo(a) ' . strtolower( $def['singular'] );
		?>
		<div class="wrap dl-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
			<a href="<?php echo esc_url( dl_admin_url( $def['page'] ) ); ?>" class="page-title-action">← Voltar à lista</a>
			<?php if ( $id ) { do_action( 'dl_form_header_' . $def['key'], $row ); } ?>
			<hr class="wp-header-end">
			<?php dl_render_notice(); ?>
			<div class="dl-form-layout">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dl-form dl-main" id="dl-form">
					<?php wp_nonce_field( 'dl_save_' . $def['key'] ); ?>
					<input type="hidden" name="action" value="dl_save">
					<input type="hidden" name="module" value="<?php echo esc_attr( $def['key'] ); ?>">
					<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
					<div class="dl-card"><div class="dl-grid">
					<?php
					foreach ( $def['fields'] as $name => $f ) {
						if ( ! empty( $f['section'] ) ) {
							echo '</div></div><div class="dl-card"><h2>' . esc_html( $f['section'] ) . '</h2><div class="dl-grid">';
						}
						self::render_field( $name, $f, $row[ $name ] ?? '', $id );
						if ( 'cliente_id' === $name && $id ) {
							do_action( 'dl_after_client_field_' . $def['key'], $row );
						}
					}
					?>
					</div></div>
					<?php
					if ( ! empty( $def['items'] ) ) {
						DL_Items::render_editor( $def, $id, $row );
					}
					do_action( 'dl_form_after_fields_' . $def['key'], $row );
					?>
					<p class="submit"><button type="submit" class="button button-primary button-large">Salvar</button></p>
				</form>
				<?php if ( $id ) : ?>
					<aside class="dl-side">
						<?php do_action( 'dl_sidebar_' . $def['key'], $row ); ?>
						<?php self::render_history( $def['key'], $id ); ?>
						<?php
						$can = apply_filters( 'dl_can_delete_' . $def['key'], true, $row );
						if ( true === $can ) :
							?>
							<div class="dl-card dl-danger">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Excluir definitivamente? Não há como desfazer.');">
									<?php wp_nonce_field( 'dl_delete_' . $def['key'] . '_' . $id ); ?>
									<input type="hidden" name="action" value="dl_delete">
									<input type="hidden" name="module" value="<?php echo esc_attr( $def['key'] ); ?>">
									<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
									<button class="button button-link-delete">Excluir registro</button>
								</form>
							</div>
						<?php elseif ( is_string( $can ) ) : ?>
							<div class="dl-card"><p class="description"><?php echo esc_html( $can ); ?></p></div>
						<?php endif; ?>
					</aside>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function render_field( $name, $f, $value, $id ) {
		$type  = $f['type'];
		$label = $f['label'] . ( ! empty( $f['required'] ) ? ' *' : '' );
		$attr  = 'name="f[' . esc_attr( $name ) . ']" id="f_' . esc_attr( $name ) . '"' . ( ! empty( $f['required'] ) ? ' required' : '' );
		$width = ( 'textarea' === $type || ( $f['width'] ?? '' ) === 'full' ) ? ' dl-full' : '';
		if ( ! empty( $f['readonly_edit'] ) && $id ) {
			$type = 'readonly';
		}
		echo '<div class="dl-field' . esc_attr( $width ) . '"><label for="f_' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
		switch ( $type ) {
			case 'textarea':
				echo '<textarea ' . $attr . ' rows="4">' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'select':
				echo '<select ' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( self::field_options( $f ) as $v => $l ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( (string) $value, (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
				}
				echo '</select>';
				break;
			case 'relation':
				echo '<select ' . $attr . ' class="dl-searchable" data-rel="' . esc_attr( $f['rel'] ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( self::field_options( $f ) as $v => $l ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( (int) $value, (int) $v, false ) . '>' . esc_html( $l ) . '</option>';
				}
				echo '</select>';
				if ( $value && isset( $f['rel'] ) ) {
					$mod = 'ordens_servico' === $f['rel'] ? 'os' : $f['rel'];
					$m   = DL_Modules::get( $mod );
					if ( $m ) {
						echo ' <a href="' . esc_url( dl_admin_url( $m['page'], array( 'action' => 'edit', 'id' => (int) $value ) ) ) . '" class="dl-small">abrir</a>';
					}
				}
				break;
			case 'checkbox':
				echo '<input type="hidden" name="f[' . esc_attr( $name ) . ']" value="0"><label class="dl-check"><input type="checkbox" ' . $attr . ' value="1" ' . checked( (int) $value, 1, false ) . '> Sim</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'money':
				echo '<input type="number" step="0.01" min="0" ' . $attr . ' value="' . esc_attr( '' === $value ? '' : (float) $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'decimal':
				echo '<input type="number" step="0.001" ' . $attr . ' value="' . esc_attr( '' === $value ? '' : (float) $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'int':
				echo '<input type="number" step="1" ' . $attr . ' value="' . esc_attr( $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'date':
				echo '<input type="date" ' . $attr . ' value="' . esc_attr( substr( (string) $value, 0, 10 ) ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'email':
			case 'url':
				echo '<input type="' . esc_attr( $type ) . '" ' . $attr . ' value="' . esc_attr( $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'tel':
				echo '<input type="tel" ' . $attr . ' value="' . esc_attr( $value ) . '" class="dl-mask-phone">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'document':
				echo '<input type="text" ' . $attr . ' value="' . esc_attr( dl_format_document( $value ) ) . '" class="dl-mask-doc">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'cep':
				echo '<input type="text" ' . $attr . ' value="' . esc_attr( $value ) . '" class="dl-cep" maxlength="9" placeholder="00000-000"><span class="dl-small">preenche o endereço</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'user':
				wp_dropdown_users(
					array(
						'name'              => 'f[' . $name . ']',
						'id'                => 'f_' . $name,
						'selected'          => (int) $value,
						'show_option_none'  => '— nenhum —',
						'option_none_value' => 0,
					)
				);
				break;
			case 'media':
				$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
				echo '<div class="dl-media"><input type="hidden" ' . $attr . ' value="' . esc_attr( (int) $value ) . '"><img src="' . esc_url( $src ) . '" class="dl-media-preview" ' . ( $src ? '' : 'style="display:none"' ) . '><button type="button" class="button dl-media-pick">Escolher imagem</button> <button type="button" class="button-link dl-media-clear">remover</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'readonly':
				echo '<div class="dl-readonly">' . esc_html( '' === (string) $value ? '—' : $value ) . '</div>';
				break;
			case 'readonly_money':
				echo '<div class="dl-readonly dl-money" data-field="' . esc_attr( $name ) . '">' . esc_html( dl_money( $value ) ) . '</div>';
				break;
			case 'readonly_date':
				echo '<div class="dl-readonly">' . esc_html( dl_date( $value ) ?: '—' ) . '</div>';
				break;
			case 'readonly_status':
				echo '<div class="dl-readonly">' . ( $value ? dl_badge( $f['badge'], $value ) : '—' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'readonly_relation':
				echo '<div class="dl-readonly">' . esc_html( DL_DB::label( $f['rel'], (int) $value ) ?: '—' ) . '</div>';
				break;
			default:
				echo '<input type="text" ' . $attr . ' value="' . esc_attr( $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</div>';
	}

	public static function render_history( $entity, $id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'historico' ) . ' WHERE entidade = %s AND entidade_id = %d ORDER BY id DESC LIMIT 30', $entity, $id ), ARRAY_A );
		if ( ! $rows ) {
			return;
		}
		echo '<div class="dl-card"><h3>Histórico</h3><ul class="dl-history">';
		foreach ( $rows as $r ) {
			$user = $r['usuario_id'] ? get_the_author_meta( 'display_name', $r['usuario_id'] ) : 'sistema';
			echo '<li><strong>' . esc_html( $r['acao'] ) . '</strong> <span>' . esc_html( dl_datetime( $r['criado_em'] ) . ' · ' . $user ) . '</span>';
			if ( $r['detalhes'] ) {
				echo '<br><small>' . esc_html( $r['detalhes'] ) . '</small>';
			}
			echo '</li>';
		}
		echo '</ul></div>';
	}

	/* --------------------------------------------------------------- gravação */

	/** Converte os campos postados para valores de banco conforme o tipo. */
	public static function sanitize( $def, $input, $is_new ) {
		$data = array();
		foreach ( $def['fields'] as $name => $f ) {
			$type = $f['type'];
			if ( 0 === strpos( $type, 'readonly' ) || ( ! empty( $f['readonly_edit'] ) && ! $is_new ) ) {
				continue;
			}
			if ( ! array_key_exists( $name, $input ) ) {
				continue;
			}
			$v = $input[ $name ];
			switch ( $type ) {
				case 'money':
				case 'decimal':
					$data[ $name ] = round( dl_decimal( $v ), 3 );
					break;
				case 'int':
				case 'relation':
				case 'media':
				case 'user':
					$data[ $name ] = (int) $v;
					break;
				case 'checkbox':
					$data[ $name ] = $v ? 1 : 0;
					break;
				case 'date':
					$data[ $name ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : null;
					break;
				case 'email':
					$data[ $name ] = sanitize_email( $v );
					break;
				case 'url':
					$data[ $name ] = esc_url_raw( $v );
					break;
				case 'document':
				case 'cep':
					$data[ $name ] = dl_digits( $v );
					break;
				case 'textarea':
					$data[ $name ] = sanitize_textarea_field( $v );
					break;
				case 'select':
					$opts          = self::field_options( $f );
					$data[ $name ] = array_key_exists( $v, $opts ) ? $v : ( $f['default'] ?? array_key_first( $opts ) );
					break;
				default:
					$data[ $name ] = sanitize_text_field( $v );
			}
		}
		return $data;
	}

	public static function save() {
		$key = isset( $_POST['module'] ) ? sanitize_key( $_POST['module'] ) : '';
		$def = DL_Modules::get( $key );
		if ( ! $def ) {
			wp_die( 'Módulo inválido.' );
		}
		dl_require_cap( $def['cap'] );
		check_admin_referer( 'dl_save_' . $key );

		$id    = absint( $_POST['id'] ?? 0 );
		$old   = $id ? DL_DB::get( $def['table'], $id ) : null;
		$input = isset( $_POST['f'] ) ? wp_unslash( (array) $_POST['f'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$data  = self::sanitize( $def, $input, ! $id );

		foreach ( $def['fields'] as $name => $f ) {
			if ( ! empty( $f['required'] ) && array_key_exists( $name, $data ) && ( '' === $data[ $name ] || null === $data[ $name ] || ( 'relation' === $f['type'] && ! $data[ $name ] ) ) ) {
				self::fail( $def, $id, 'Preencha o campo obrigatório: ' . $f['label'] . '.' );
			}
		}
		if ( ! $id ) {
			foreach ( $def['fields'] as $name => $f ) {
				if ( ! array_key_exists( $name, $data ) && isset( $f['default'] ) ) {
					$data[ $name ] = $f['default'];
				}
			}
		}

		$data = apply_filters( 'dl_before_save_' . $key, $data, $id, $old );
		if ( is_wp_error( $data ) ) {
			self::fail( $def, $id, $data->get_error_message() );
		}

		if ( $id ) {
			DL_DB::update( $def['table'], $id, $data );
			dl_log( $key, $id, 'Alterado' );
		} else {
			$id = DL_DB::insert( $def['table'], $data );
			if ( ! $id ) {
				global $wpdb;
				self::fail( $def, 0, 'Não foi possível salvar: ' . $wpdb->last_error );
			}
			dl_log( $key, $id, 'Criado' );
		}

		if ( ! empty( $def['items'] ) ) {
			$items  = isset( $_POST['items'] ) ? wp_unslash( (array) $_POST['items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$result = DL_Items::save_items( $def, $id, $items );
			if ( is_wp_error( $result ) ) {
				dl_redirect( dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $id ) ), $result->get_error_message(), 'error' );
			}
		}

		do_action( 'dl_after_save_' . $key, $id, $data, $old );
		$notice  = get_transient( 'dl_notice_' . get_current_user_id() );
		dl_redirect( dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $id ) ), $notice ? '' : $def['singular'] . ' salvo(a).' );
	}

	private static function fail( $def, $id, $message ) {
		$url = $id ? dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $id ) ) : wp_get_referer();
		dl_redirect( $url ? $url : dl_admin_url( $def['page'] ), $message, 'error' );
	}

	public static function delete() {
		$key = isset( $_POST['module'] ) ? sanitize_key( $_POST['module'] ) : '';
		$def = DL_Modules::get( $key );
		$id  = absint( $_POST['id'] ?? 0 );
		if ( ! $def || ! $id ) {
			wp_die( 'Requisição inválida.' );
		}
		dl_require_cap( $def['cap'] );
		check_admin_referer( 'dl_delete_' . $key . '_' . $id );
		$row = DL_DB::get( $def['table'], $id );
		$can = apply_filters( 'dl_can_delete_' . $key, true, $row );
		if ( true !== $can ) {
			dl_redirect( dl_admin_url( $def['page'], array( 'action' => 'edit', 'id' => $id ) ), is_string( $can ) ? $can : 'Não é possível excluir.', 'error' );
		}
		do_action( 'dl_before_delete_' . $key, $row );
		if ( ! empty( $def['doc_tipo'] ) ) {
			global $wpdb;
			$wpdb->delete( dl_table( 'itens' ), array( 'doc_tipo' => $def['doc_tipo'], 'doc_id' => $id ) );
		}
		DL_DB::delete( $def['table'], $id );
		dl_log( $key, $id, 'Excluído', wp_json_encode( array( 'nome' => $row['nome'] ?? ( $row['numero'] ?? '' ) ) ) );
		dl_redirect( dl_admin_url( $def['page'] ), 'Registro excluído.' );
	}

	public static function export() {
		$key = isset( $_GET['module'] ) ? sanitize_key( $_GET['module'] ) : '';
		$def = DL_Modules::get( $key );
		if ( ! $def ) {
			wp_die( 'Módulo inválido.' );
		}
		dl_require_cap( $def['cap'] );
		check_admin_referer( 'dl_export' );
		global $wpdb;
		$where = self::build_where( $def, $_GET );
		$rows  = $wpdb->get_results( 'SELECT * FROM ' . dl_table( $def['table'] ) . " WHERE {$where} ORDER BY {$def['order']}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out   = array();
		$out[] = array_merge( array( 'ID' ), wp_list_pluck( $def['fields'], 'label' ) );
		foreach ( $rows as $row ) {
			$line = array( $row['id'] );
			foreach ( $def['fields'] as $name => $f ) {
				$f2 = $f;
				unset( $f2['badge'] );
				$line[] = wp_strip_all_tags( html_entity_decode( self::display_value( $f2, $row[ $name ] ?? '', $row, $name ), ENT_QUOTES, 'UTF-8' ) );
			}
			$out[] = $line;
		}
		self::send_csv( $key . '-' . current_time( 'Y-m-d' ) . '.csv', $out );
	}

	/** Envia um CSV (separador ;, compatível com Excel em português). */
	public static function send_csv( $filename, array $rows ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		$fh = fopen( 'php://output', 'w' );
		fwrite( $fh, "\xEF\xBB\xBF" );
		foreach ( $rows as $r ) {
			$r = array_map(
				function ( $v ) {
					$v = (string) $v;
					// Evita injeção de fórmula ao abrir no Excel.
					return preg_match( '/^[=+\-@]/', $v ) ? "'" . $v : $v;
				},
				$r
			);
			fputcsv( $fh, $r, ';' );
		}
		fclose( $fh );
		exit;
	}
}
