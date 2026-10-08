<?php
/**
 * Itens de documentos: equipamentos do contrato, peças da OS e produtos da venda.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DL_Items {

	public static function get( $doc_tipo, $doc_id ) {
		global $wpdb;
		if ( ! $doc_id ) {
			return array();
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dl_table( 'itens' ) . ' WHERE doc_tipo = %s AND doc_id = %d ORDER BY ordem ASC, id ASC', $doc_tipo, $doc_id ), ARRAY_A );
	}

	/** Catálogo usado nos selects do editor (com preços nos atributos data-*). */
	private static function catalog( $kind ) {
		global $wpdb;
		if ( 'equipamento' === $kind ) {
			return $wpdb->get_results( 'SELECT id, codigo, nome, valor_diaria, valor_semanal, valor_quinzenal, valor_mensal, valor_caucao, controle, qtd_total, status FROM ' . dl_table( 'equipamentos' ) . " WHERE status <> 'inativo' ORDER BY nome ASC", ARRAY_A );
		}
		return $wpdb->get_results( 'SELECT id, codigo, nome, tipo, unidade, preco_venda, estoque_atual FROM ' . dl_table( 'produtos' ) . ' WHERE ativo = 1 ORDER BY nome ASC', ARRAY_A );
	}

	public static function render_editor( $def, $id, $row ) {
		$kind    = $def['items'];
		$items   = array_values(
			array_filter(
				self::get( $def['doc_tipo'], $id ),
				function ( $it ) {
					return 'adicional' !== $it['ref_tipo'];
				}
			)
		);
		$catalog = self::catalog( $kind );
		$periods = dl_period_types();
		$locked  = ( 'contrato' === $def['doc_tipo'] && in_array( $row['status'] ?? '', array( 'encerrado', 'cancelado' ), true ) )
			|| ( 'venda' === $def['doc_tipo'] && in_array( $row['status'] ?? '', array( 'confirmada', 'cancelada' ), true ) )
			|| ( 'os' === $def['doc_tipo'] && ! empty( $row['estoque_baixado'] ) );
		?>
		<div class="dl-card dl-items" data-kind="<?php echo esc_attr( $kind ); ?>">
			<h2><?php echo 'equipamento' === $kind ? 'Equipamentos' : 'Produtos, peças e serviços'; ?></h2>
			<?php if ( $locked ) : ?>
				<p class="description">Itens bloqueados para edição nesta situação.</p>
			<?php endif; ?>
			<table class="widefat dl-items-table">
				<thead><tr>
					<th style="width:24%"><?php echo 'equipamento' === $kind ? 'Equipamento' : 'Item'; ?></th>
					<th>Descrição</th>
					<th style="width:70px">Qtd</th>
					<?php if ( 'equipamento' === $kind ) : ?>
						<th style="width:120px">Cobrança</th>
						<th style="width:80px">Períodos</th>
					<?php endif; ?>
					<th style="width:100px">Valor unit.</th>
					<th style="width:90px">Desconto</th>
					<th style="width:100px">Total</th>
					<?php if ( 'equipamento' === $kind && $id ) : ?>
						<th style="width:90px">Devolvido</th>
					<?php endif; ?>
					<th style="width:30px"></th>
				</tr></thead>
				<tbody>
				<?php
				foreach ( $items as $i => $it ) {
					self::render_row( $kind, $i, $it, $catalog, $periods, $locked, (bool) $id );
				}
				?>
				</tbody>
				<tfoot><tr><td colspan="<?php echo 'equipamento' === $kind ? ( $id ? 10 : 9 ) : 7; ?>">
					<?php if ( ! $locked ) : ?>
						<button type="button" class="button dl-add-item">+ Adicionar item</button>
						<?php if ( 'equipamento' === $kind ) : ?>
							<button type="button" class="button dl-best-price" title="Calcula a combinação mais barata de diárias, semanas, quinzenas e meses para o período do contrato">Aplicar melhor tarifa ao período</button>
						<?php endif; ?>
					<?php endif; ?>
					<strong class="dl-items-sum">Total dos itens: <span>R$ 0,00</span></strong>
				</td></tr></tfoot>
			</table>
			<?php if ( ! $locked ) : ?>
				<script type="text/html" id="dl-item-row-tpl"><?php self::render_row( $kind, '__i__', array(), $catalog, $periods, false, (bool) $id ); ?></script>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_row( $kind, $i, $it, $catalog, $periods, $locked, $has_id ) {
		$it  = wp_parse_args(
			$it,
			array(
				'id'           => 0,
				'ref_id'       => 0,
				'descricao'    => '',
				'qtd'          => 1,
				'periodo_tipo' => 'diaria',
				'periodos'     => 1,
				'valor_unit'   => 0,
				'desconto'     => 0,
				'total'        => 0,
				'qtd_devolvida' => 0,
			)
		);
		$n   = 'items[' . $i . ']';
		$dis = $locked ? ' disabled' : '';
		echo '<tr class="dl-item-row">';
		echo '<td><input type="hidden" name="' . esc_attr( $n ) . '[id]" value="' . (int) $it['id'] . '">';
		echo '<select class="dl-item-ref" name="' . esc_attr( $n ) . '[ref_id]"' . $dis . '><option value="0">— selecione —</option>'; // phpcs:ignore WordPress.Security.EscapeOutput
		foreach ( $catalog as $c ) {
			if ( 'equipamento' === $kind ) {
				printf(
					'<option value="%d" data-nome="%s" data-diaria="%s" data-semanal="%s" data-quinzenal="%s" data-mensal="%s" data-caucao="%s" %s>%s</option>',
					(int) $c['id'],
					esc_attr( $c['nome'] ),
					esc_attr( $c['valor_diaria'] ),
					esc_attr( $c['valor_semanal'] ),
					esc_attr( $c['valor_quinzenal'] ),
					esc_attr( $c['valor_mensal'] ),
					esc_attr( $c['valor_caucao'] ),
					selected( (int) $it['ref_id'], (int) $c['id'], false ),
					esc_html( trim( $c['codigo'] . ' ' . $c['nome'] ) )
				);
			} else {
				printf(
					'<option value="%d" data-nome="%s" data-preco="%s" %s>%s</option>',
					(int) $c['id'],
					esc_attr( $c['nome'] ),
					esc_attr( $c['preco_venda'] ),
					selected( (int) $it['ref_id'], (int) $c['id'], false ),
					esc_html( trim( $c['codigo'] . ' ' . $c['nome'] ) )
				);
			}
		}
		echo '</select></td>';
		echo '<td><input type="text" class="dl-item-desc" name="' . esc_attr( $n ) . '[descricao]" value="' . esc_attr( $it['descricao'] ) . '"' . $dis . '></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><input type="number" step="0.001" min="0" class="dl-item-qtd" name="' . esc_attr( $n ) . '[qtd]" value="' . esc_attr( (float) $it['qtd'] ) . '"' . $dis . '></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( 'equipamento' === $kind ) {
			echo '<td><select class="dl-item-period" name="' . esc_attr( $n ) . '[periodo_tipo]"' . $dis . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( $periods as $k => $p ) {
				echo '<option value="' . esc_attr( $k ) . '" data-dias="' . (int) $p['dias'] . '" ' . selected( $it['periodo_tipo'], $k, false ) . '>' . esc_html( $p['label'] ) . '</option>';
			}
			echo '</select></td>';
			echo '<td><input type="number" step="0.01" min="0" class="dl-item-periods" name="' . esc_attr( $n ) . '[periodos]" value="' . esc_attr( (float) $it['periodos'] ) . '"' . $dis . '></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<td><input type="number" step="0.01" min="0" class="dl-item-unit" name="' . esc_attr( $n ) . '[valor_unit]" value="' . esc_attr( (float) $it['valor_unit'] ) . '"' . $dis . '></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><input type="number" step="0.01" min="0" class="dl-item-discount" name="' . esc_attr( $n ) . '[desconto]" value="' . esc_attr( (float) $it['desconto'] ) . '"' . $dis . '></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td class="dl-item-total">' . esc_html( dl_money( $it['total'] ) ) . '</td>';
		if ( 'equipamento' === $kind && $has_id ) {
			echo '<td>' . esc_html( dl_num( $it['qtd_devolvida'], 0 ) . ' / ' . dl_num( $it['qtd'], 0 ) ) . '</td>';
		}
		echo '<td>' . ( $locked ? '' : '<button type="button" class="button-link dl-remove-item" title="Remover">✕</button>' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * Grava os itens postados. Itens que sumiram do formulário são removidos,
	 * exceto os que já tiveram devolução registrada.
	 */
	public static function save_items( $def, $doc_id, array $posted ) {
		global $wpdb;
		$doc_tipo = $def['doc_tipo'];
		$kind     = $def['items'];
		$existing = array();
		foreach ( self::get( $doc_tipo, $doc_id ) as $it ) {
			$existing[ (int) $it['id'] ] = $it;
		}

		$row = DL_DB::get( $def['table'], $doc_id );
		$locked = ( 'contrato' === $doc_tipo && in_array( $row['status'], array( 'encerrado', 'cancelado' ), true ) )
			|| ( 'venda' === $doc_tipo && in_array( $row['status'], array( 'cancelada' ), true ) )
			|| ( 'os' === $doc_tipo && ! empty( $row['estoque_baixado'] ) );
		if ( $locked || ( 'venda' === $doc_tipo && ! empty( $row['estoque_baixado'] ) ) ) {
			do_action( 'dl_items_saved_' . $doc_tipo, $doc_id );
			return true;
		}

		$rows  = array();
		$order = 0;
		foreach ( $posted as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$ref  = absint( $p['ref_id'] ?? 0 );
			$desc = sanitize_text_field( $p['descricao'] ?? '' );
			if ( ! $ref && '' === $desc ) {
				continue;
			}
			$qty     = max( 0, dl_decimal( $p['qtd'] ?? 1 ) );
			$unit    = max( 0, dl_decimal( $p['valor_unit'] ?? 0 ) );
			$disc    = max( 0, dl_decimal( $p['desconto'] ?? 0 ) );
			$ptype   = 'equipamento' === $kind ? sanitize_key( $p['periodo_tipo'] ?? 'diaria' ) : '';
			$periods = 'equipamento' === $kind ? max( 0, dl_decimal( $p['periodos'] ?? 1 ) ) : 1;
			if ( 'equipamento' === $kind && ! isset( dl_period_types()[ $ptype ] ) ) {
				$ptype = 'diaria';
			}
			if ( '' === $desc && $ref ) {
				$desc = DL_DB::label( 'equipamento' === $kind ? 'equipamentos' : 'produtos', $ref );
			}
			$rows[] = array(
				'id'           => absint( $p['id'] ?? 0 ),
				'doc_tipo'     => $doc_tipo,
				'doc_id'       => $doc_id,
				'ref_tipo'     => 'equipamento' === $kind ? 'equipamento' : 'produto',
				'ref_id'       => $ref,
				'descricao'    => $desc,
				'qtd'          => $qty,
				'periodo_tipo' => $ptype,
				'periodos'     => $periods,
				'valor_unit'   => $unit,
				'desconto'     => $disc,
				'total'        => DL_Pricing::item_total( $qty, $periods, $unit, $disc ),
				'ordem'        => $order++,
			);
		}

		$valid = apply_filters( 'dl_validate_items_' . $doc_tipo, true, $doc_id, $rows, $existing );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$kept = array();
		foreach ( $rows as $r ) {
			$item_id = $r['id'];
			unset( $r['id'] );
			if ( $item_id && isset( $existing[ $item_id ] ) ) {
				if ( (float) $existing[ $item_id ]['qtd_devolvida'] > $r['qtd'] ) {
					$r['qtd'] = (float) $existing[ $item_id ]['qtd_devolvida'];
					$r['total'] = DL_Pricing::item_total( $r['qtd'], $r['periodos'], $r['valor_unit'], $r['desconto'] );
				}
				$wpdb->update( dl_table( 'itens' ), $r, array( 'id' => $item_id ) );
				$kept[] = $item_id;
			} else {
				$wpdb->insert( dl_table( 'itens' ), $r );
				$kept[] = (int) $wpdb->insert_id;
			}
		}
		foreach ( $existing as $item_id => $it ) {
			if ( ! in_array( $item_id, $kept, true ) && (float) $it['qtd_devolvida'] <= 0 && 'adicional' !== $it['ref_tipo'] ) {
				$wpdb->delete( dl_table( 'itens' ), array( 'id' => $item_id ) );
			}
		}
		do_action( 'dl_items_saved_' . $doc_tipo, $doc_id );
		return true;
	}

	/** Soma dos itens de um documento. */
	public static function sum( $doc_tipo, $doc_id, $ref_tipo = null ) {
		global $wpdb;
		$sql = 'SELECT COALESCE(SUM(total),0) FROM ' . dl_table( 'itens' ) . ' WHERE doc_tipo = %s AND doc_id = %d';
		$arg = array( $doc_tipo, $doc_id );
		if ( $ref_tipo ) {
			$sql  .= ' AND ref_tipo = %s';
			$arg[] = $ref_tipo;
		}
		return (float) $wpdb->get_var( $wpdb->prepare( $sql, $arg ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
