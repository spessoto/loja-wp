<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin meta boxes for manually editing a product's data.
 *
 * Repeating fields (destaques, especificações, prós/contras, avaliações) are
 * edited as plain text, one entry per line, with columns separated by " | ".
 * This keeps the admin UI dependency-free while still covering every field
 * the AI import (LAI_Importer) can fill in automatically.
 */
class LAI_Meta_Boxes {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'save_post_' . LAI_CPT, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( LAI_CPT !== get_current_screen()->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'lai-admin', LAI_PLUGIN_URL . 'assets/css/admin.css', array(), LAI_VERSION );
		wp_enqueue_script( 'lai-admin', LAI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), LAI_VERSION, true );
		wp_localize_script(
			'lai-admin',
			'LAI_ADMIN',
			array(
				'tituloSeletor' => __( 'Selecionar imagens ou vídeos do produto', 'loja-afiliados-ia' ),
				'botaoSeletor'  => __( 'Usar selecionado(s)', 'loja-afiliados-ia' ),
			)
		);
	}

	public function add_boxes() {
		add_meta_box( 'lai_oferta', __( 'Oferta e link de afiliado', 'loja-afiliados-ia' ), array( $this, 'render_oferta' ), LAI_CPT, 'normal', 'high' );
		add_meta_box( 'lai_destaques', __( 'Destaques rápidos', 'loja-afiliados-ia' ), array( $this, 'render_destaques' ), LAI_CPT, 'normal' );
		add_meta_box( 'lai_especificacoes', __( 'Ficha técnica', 'loja-afiliados-ia' ), array( $this, 'render_especificacoes' ), LAI_CPT, 'normal' );
		add_meta_box( 'lai_publico', __( 'Para quem é / não é', 'loja-afiliados-ia' ), array( $this, 'render_publico' ), LAI_CPT, 'normal' );
		add_meta_box( 'lai_avaliacoes', __( 'Avaliações de clientes', 'loja-afiliados-ia' ), array( $this, 'render_avaliacoes' ), LAI_CPT, 'normal' );
		add_meta_box( 'lai_galeria', __( 'Galeria de imagens e vídeos', 'loja-afiliados-ia' ), array( $this, 'render_galeria' ), LAI_CPT, 'side' );
	}

	private function nonce_field() {
		wp_nonce_field( 'lai_save_meta', 'lai_meta_nonce' );
	}

	private function get( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, $key, true );
		return '' === $value ? $default : $value;
	}

	public function render_oferta( $post ) {
		$this->nonce_field();
		$link       = $this->get( $post->ID, '_lai_link_afiliado' );
		$loja       = $this->get( $post->ID, '_lai_loja_destino', 'Amazon' );
		$sku        = $this->get( $post->ID, '_lai_sku' );
		$resumo     = $this->get( $post->ID, '_lai_resumo_curto' );
		$badge      = $this->get( $post->ID, '_lai_badge' );
		$preco      = $this->get( $post->ID, '_lai_preco_atual' );
		$preco_orig = $this->get( $post->ID, '_lai_preco_original' );
		$parcela    = $this->get( $post->ID, '_lai_parcelamento' );
		$nota       = $this->get( $post->ID, '_lai_avaliacao_nota' );
		$total      = $this->get( $post->ID, '_lai_avaliacao_total' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lai_link_afiliado"><?php esc_html_e( 'Link de afiliado', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="url" class="large-text" id="lai_link_afiliado" name="lai_link_afiliado" value="<?php echo esc_attr( $link ); ?>" placeholder="https://www.amazon.com.br/dp/...?tag=seu-afiliado" required></td>
			</tr>
			<tr>
				<th><label for="lai_loja_destino"><?php esc_html_e( 'Loja de destino', 'loja-afiliados-ia' ); ?></label></th>
				<td>
					<select id="lai_loja_destino" name="lai_loja_destino">
						<?php foreach ( array( 'Amazon', 'Mercado Livre', 'Outro' ) as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $loja, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="lai_resumo_curto"><?php esc_html_e( 'Marca / linha (texto pequeno acima do título)', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="text" class="regular-text" id="lai_resumo_curto" name="lai_resumo_curto" value="<?php echo esc_attr( $resumo ); ?>"></td>
			</tr>
			<tr>
				<th><label for="lai_sku"><?php esc_html_e( 'Código / SKU', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="text" class="regular-text" id="lai_sku" name="lai_sku" value="<?php echo esc_attr( $sku ); ?>"></td>
			</tr>
			<tr>
				<th><label for="lai_badge"><?php esc_html_e( 'Selo (ex: Mais vendido)', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="text" class="regular-text" id="lai_badge" name="lai_badge" value="<?php echo esc_attr( $badge ); ?>"></td>
			</tr>
			<tr>
				<th><label for="lai_preco_atual"><?php esc_html_e( 'Preço atual (R$)', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="number" step="0.01" min="0" id="lai_preco_atual" name="lai_preco_atual" value="<?php echo esc_attr( $preco ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="lai_preco_original"><?php esc_html_e( 'Preço original / "de" (opcional)', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="number" step="0.01" min="0" id="lai_preco_original" name="lai_preco_original" value="<?php echo esc_attr( $preco_orig ); ?>"></td>
			</tr>
			<tr>
				<th><label for="lai_parcelamento"><?php esc_html_e( 'Texto de parcelamento', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="text" class="regular-text" id="lai_parcelamento" name="lai_parcelamento" value="<?php echo esc_attr( $parcela ); ?>" placeholder="10x de R$ 69,99 sem juros"></td>
			</tr>
			<tr>
				<th><label for="lai_avaliacao_nota"><?php esc_html_e( 'Nota média (0 a 5)', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="number" step="0.1" min="0" max="5" id="lai_avaliacao_nota" name="lai_avaliacao_nota" value="<?php echo esc_attr( $nota ); ?>"></td>
			</tr>
			<tr>
				<th><label for="lai_avaliacao_total"><?php esc_html_e( 'Número de avaliações', 'loja-afiliados-ia' ); ?></label></th>
				<td><input type="number" step="1" min="0" id="lai_avaliacao_total" name="lai_avaliacao_total" value="<?php echo esc_attr( $total ); ?>"></td>
			</tr>
		</table>
		<?php
	}

	public function render_destaques( $post ) {
		$rows = LAI_Importer::pairs_to_lines( get_post_meta( $post->ID, '_lai_destaques', true ), 'valor', 'label' );
		?>
		<p><?php esc_html_e( 'Um por linha, no formato: Valor | Legenda. Ex: 450W | Potência de sucção ciclônica', 'loja-afiliados-ia' ); ?></p>
		<textarea name="lai_destaques" rows="5" class="large-text code"><?php echo esc_textarea( $rows ); ?></textarea>

		<p style="margin-top:16px"><?php esc_html_e( 'Bullets curtos abaixo das fotos (um por linha). Ex: Indicado para pets', 'loja-afiliados-ia' ); ?></p>
		<textarea name="lai_bullets" rows="4" class="large-text code"><?php echo esc_textarea( implode( "\n", (array) get_post_meta( $post->ID, '_lai_bullets', true ) ) ); ?></textarea>
		<?php
	}

	public function render_especificacoes( $post ) {
		$rows = LAI_Importer::pairs_to_lines( get_post_meta( $post->ID, '_lai_especificacoes', true ), 'chave', 'valor' );
		?>
		<p><?php esc_html_e( 'Um por linha, no formato: Característica | Valor. Ex: Potência | 450W (motor digital)', 'loja-afiliados-ia' ); ?></p>
		<textarea name="lai_especificacoes" rows="10" class="large-text code"><?php echo esc_textarea( $rows ); ?></textarea>
		<?php
	}

	public function render_publico( $post ) {
		?>
		<div style="display:flex; gap:20px;">
			<div style="flex:1">
				<p><strong><?php esc_html_e( 'Indicado para (um por linha)', 'loja-afiliados-ia' ); ?></strong></p>
				<textarea name="lai_indicado_para" rows="6" class="large-text"><?php echo esc_textarea( implode( "\n", (array) get_post_meta( $post->ID, '_lai_indicado_para', true ) ) ); ?></textarea>
			</div>
			<div style="flex:1">
				<p><strong><?php esc_html_e( 'Talvez não seja para (um por linha)', 'loja-afiliados-ia' ); ?></strong></p>
				<textarea name="lai_nao_indicado_para" rows="6" class="large-text"><?php echo esc_textarea( implode( "\n", (array) get_post_meta( $post->ID, '_lai_nao_indicado_para', true ) ) ); ?></textarea>
			</div>
		</div>
		<?php
	}

	public function render_avaliacoes( $post ) {
		$avaliacoes = (array) get_post_meta( $post->ID, '_lai_avaliacoes', true );
		$lines      = array();
		foreach ( $avaliacoes as $a ) {
			$lines[] = sprintf(
				'%s | %s | %s | %s | %s | %s',
				$a['nome'] ?? '',
				$a['local'] ?? '',
				$a['nota'] ?? '',
				$a['data'] ?? '',
				! empty( $a['verificada'] ) ? 'sim' : 'nao',
				str_replace( array( "\r", "\n" ), ' ', $a['texto'] ?? '' )
			);
		}
		?>
		<p><?php esc_html_e( 'Um por linha: Nome | Cidade, UF | Nota (1-5) | Data (texto) | Verificada (sim/nao) | Comentário', 'loja-afiliados-ia' ); ?></p>
		<textarea name="lai_avaliacoes" rows="8" class="large-text code"><?php echo esc_textarea( implode( "\n", $lines ) ); ?></textarea>
		<?php
	}

	public function render_galeria( $post ) {
		$ids = array_filter( array_map( 'intval', (array) get_post_meta( $post->ID, '_lai_galeria', true ) ) );
		?>
		<p><?php esc_html_e( 'Adicione fotos e vídeos do produto. O primeiro item é usado como imagem principal (se for uma foto).', 'loja-afiliados-ia' ); ?></p>
		<div class="lai-media-manager">
			<ul class="lai-media-lista" id="lai-galeria-lista">
				<?php foreach ( $ids as $id ) : ?>
					<?php echo self::media_item_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="button lai-media-adicionar"><?php esc_html_e( 'Adicionar imagens/vídeos', 'loja-afiliados-ia' ); ?></button>
			<input type="hidden" name="lai_galeria" id="lai-galeria-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
		</div>
		<p class="description"><?php esc_html_e( 'Dica: ao importar via IA as imagens são baixadas e anexadas automaticamente; vídeos precisam ser adicionados aqui manualmente.', 'loja-afiliados-ia' ); ?></p>
		<?php
	}

	/**
	 * Markup for one item of the media manager (used on initial render and
	 * mirrored in assets/js/admin.js when a new item is picked).
	 */
	public static function media_item_html( $id ) {
		$is_video = wp_attachment_is( 'video', $id );
		if ( $is_video ) {
			$thumb = '<span class="lai-media-item__video-icone dashicons dashicons-video-alt3"></span>';
		} else {
			$thumb = wp_get_attachment_image( $id, array( 60, 60 ) );
		}
		return sprintf(
			'<li class="lai-media-item" data-id="%1$d"><div class="lai-media-item__thumb">%2$s</div><button type="button" class="lai-media-item__remover" aria-label="%3$s">&times;</button></li>',
			$id,
			$thumb,
			esc_attr__( 'Remover', 'loja-afiliados-ia' )
		);
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['lai_meta_nonce'] ) || ! wp_verify_nonce( $_POST['lai_meta_nonce'], 'lai_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$simple_fields = array(
			'lai_link_afiliado'   => 'esc_url_raw',
			'lai_loja_destino'    => 'sanitize_text_field',
			'lai_resumo_curto'    => 'sanitize_text_field',
			'lai_sku'             => 'sanitize_text_field',
			'lai_badge'           => 'sanitize_text_field',
			'lai_preco_atual'     => 'floatval',
			'lai_preco_original'  => 'floatval',
			'lai_parcelamento'    => 'sanitize_text_field',
			'lai_avaliacao_nota'  => 'floatval',
			'lai_avaliacao_total' => 'absint',
		);

		foreach ( $simple_fields as $field => $sanitizer ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, call_user_func( $sanitizer, wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		if ( isset( $_POST['lai_destaques'] ) ) {
			update_post_meta( $post_id, '_lai_destaques', LAI_Importer::lines_to_pairs( wp_unslash( $_POST['lai_destaques'] ), 'valor', 'label' ) );
		}
		if ( isset( $_POST['lai_especificacoes'] ) ) {
			update_post_meta( $post_id, '_lai_especificacoes', LAI_Importer::lines_to_pairs( wp_unslash( $_POST['lai_especificacoes'] ), 'chave', 'valor' ) );
		}
		foreach ( array( 'lai_bullets', 'lai_indicado_para', 'lai_nao_indicado_para' ) as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$lines = array_values( array_filter( array_map( 'sanitize_text_field', explode( "\n", wp_unslash( $_POST[ $field ] ) ) ) ) );
				update_post_meta( $post_id, '_' . $field, $lines );
			}
		}

		if ( isset( $_POST['lai_avaliacoes'] ) ) {
			$avaliacoes = array();
			$lines      = explode( "\n", wp_unslash( $_POST['lai_avaliacoes'] ) );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				$cols         = array_map( 'trim', explode( '|', $line ) );
				$avaliacoes[] = array(
					'nome'       => sanitize_text_field( $cols[0] ?? '' ),
					'local'      => sanitize_text_field( $cols[1] ?? '' ),
					'nota'       => isset( $cols[2] ) ? floatval( $cols[2] ) : 5,
					'data'       => sanitize_text_field( $cols[3] ?? '' ),
					'verificada' => isset( $cols[4] ) && 'sim' === strtolower( $cols[4] ),
					'texto'      => sanitize_textarea_field( $cols[5] ?? '' ),
				);
			}
			update_post_meta( $post_id, '_lai_avaliacoes', $avaliacoes );
		}

		if ( isset( $_POST['lai_galeria'] ) ) {
			$ids = array_filter( array_map( 'intval', explode( ',', wp_unslash( $_POST['lai_galeria'] ) ) ) );
			update_post_meta( $post_id, '_lai_galeria', array_values( $ids ) );
		}
	}
}
