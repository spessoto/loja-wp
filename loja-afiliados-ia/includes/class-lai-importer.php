<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Turns the structured JSON produced by the AI reading step into a product post.
 *
 * The plugin does not call any AI API. The workflow is: paste the affiliate
 * link into your Claude chat, ask it to read the page and return the JSON in
 * the schema documented on the import screen, then paste that JSON here (or
 * POST it to the REST endpoint) to create/update the product automatically.
 */
class LAI_Importer {

	/**
	 * @param array $data   Decoded JSON payload (see admin import page for the schema).
	 * @param int   $post_id Existing product ID to update, or 0 to create a new one.
	 * @return int|WP_Error Post ID on success.
	 */
	public static function import( array $data, $post_id = 0 ) {
		if ( empty( $data['titulo'] ) ) {
			return new WP_Error( 'lai_missing_title', __( 'O campo "titulo" é obrigatório.', 'loja-afiliados-ia' ) );
		}
		if ( empty( $data['link_afiliado'] ) ) {
			return new WP_Error( 'lai_missing_link', __( 'O campo "link_afiliado" é obrigatório.', 'loja-afiliados-ia' ) );
		}

		$postarr = array(
			'post_type'    => LAI_CPT,
			'post_title'   => sanitize_text_field( $data['titulo'] ),
			'post_content' => wp_kses_post( $data['descricao'] ?? '' ),
			'post_excerpt' => sanitize_text_field( $data['resumo_curto'] ?? '' ),
			'post_status'  => 'publish',
		);

		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_lai_link_afiliado', esc_url_raw( $data['link_afiliado'] ) );
		update_post_meta( $post_id, '_lai_loja_destino', sanitize_text_field( $data['loja_destino'] ?? self::detectar_loja( $data['link_afiliado'] ) ) );
		update_post_meta( $post_id, '_lai_resumo_curto', sanitize_text_field( $data['marca'] ?? ( $data['resumo_curto'] ?? '' ) ) );
		update_post_meta( $post_id, '_lai_sku', sanitize_text_field( $data['sku'] ?? '' ) );
		update_post_meta( $post_id, '_lai_badge', sanitize_text_field( $data['badge'] ?? '' ) );
		update_post_meta( $post_id, '_lai_preco_atual', floatval( $data['preco_atual'] ?? 0 ) );
		update_post_meta( $post_id, '_lai_preco_original', floatval( $data['preco_original'] ?? 0 ) );
		update_post_meta( $post_id, '_lai_parcelamento', sanitize_text_field( $data['parcelamento'] ?? '' ) );
		update_post_meta( $post_id, '_lai_avaliacao_nota', floatval( $data['avaliacao_nota'] ?? 0 ) );
		update_post_meta( $post_id, '_lai_avaliacao_total', absint( $data['avaliacao_total'] ?? 0 ) );

		update_post_meta( $post_id, '_lai_bullets', self::sanitize_string_list( $data['bullets'] ?? array() ) );
		update_post_meta( $post_id, '_lai_indicado_para', self::sanitize_string_list( $data['indicado_para'] ?? array() ) );
		update_post_meta( $post_id, '_lai_nao_indicado_para', self::sanitize_string_list( $data['nao_indicado_para'] ?? array() ) );
		update_post_meta( $post_id, '_lai_destaques', self::sanitize_pairs( $data['destaques'] ?? array(), 'valor', 'label' ) );
		update_post_meta( $post_id, '_lai_especificacoes', self::sanitize_pairs( $data['especificacoes'] ?? array(), 'chave', 'valor' ) );
		update_post_meta( $post_id, '_lai_avaliacoes', self::sanitize_avaliacoes( $data['avaliacoes'] ?? array() ) );

		if ( ! empty( $data['categoria'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['categoria'] ), LAI_TAX_CATEGORIA );
		}
		if ( ! empty( $data['marca'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['marca'] ), LAI_TAX_MARCA );
		}

		$imagens = array();
		if ( ! empty( $data['imagem_destaque'] ) ) {
			$imagens[] = $data['imagem_destaque'];
		}
		foreach ( (array) ( $data['imagens'] ?? array() ) as $url ) {
			if ( ! in_array( $url, $imagens, true ) ) {
				$imagens[] = $url;
			}
		}

		if ( $imagens ) {
			$attachment_ids = array();
			foreach ( $imagens as $index => $url ) {
				$attachment_id = self::sideload_image( $url, $post_id );
				if ( $attachment_id ) {
					$attachment_ids[] = $attachment_id;
					if ( 0 === $index ) {
						set_post_thumbnail( $post_id, $attachment_id );
					}
				}
			}
			if ( $attachment_ids ) {
				update_post_meta( $post_id, '_lai_galeria', $attachment_ids );
			}
		}

		return $post_id;
	}

	private static function detectar_loja( $url ) {
		if ( false !== strpos( $url, 'amazon.' ) || false !== strpos( $url, 'amzn.to' ) ) {
			return 'Amazon';
		}
		if ( false !== strpos( $url, 'mercadolivre.' ) || false !== strpos( $url, 'mercadolibre.' ) ) {
			return 'Mercado Livre';
		}
		return 'Outro';
	}

	private static function sanitize_string_list( $list ) {
		$out = array();
		foreach ( (array) $list as $item ) {
			$item = sanitize_text_field( $item );
			if ( '' !== $item ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	private static function sanitize_pairs( $list, $key_a, $key_b ) {
		$out = array();
		foreach ( (array) $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$out[] = array(
				$key_a => sanitize_text_field( $item[ $key_a ] ?? '' ),
				$key_b => sanitize_text_field( $item[ $key_b ] ?? '' ),
			);
		}
		return $out;
	}

	private static function sanitize_avaliacoes( $list ) {
		$out = array();
		foreach ( (array) $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$out[] = array(
				'nome'       => sanitize_text_field( $item['nome'] ?? '' ),
				'local'      => sanitize_text_field( $item['local'] ?? '' ),
				'nota'       => floatval( $item['nota'] ?? 5 ),
				'data'       => sanitize_text_field( $item['data'] ?? '' ),
				'verificada' => ! empty( $item['verificada'] ),
				'texto'      => sanitize_textarea_field( $item['texto'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * Downloads a remote image and attaches it to the product post.
	 */
	private static function sideload_image( $url, $post_id ) {
		$url = esc_url_raw( $url );
		if ( ! $url ) {
			return 0;
		}
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}
		$file_array = array(
			'name'     => sanitize_file_name( basename( parse_url( $url, PHP_URL_PATH ) ?: 'produto.jpg' ) ),
			'tmp_name' => $tmp,
		);
		if ( ! pathinfo( $file_array['name'], PATHINFO_EXTENSION ) ) {
			$file_array['name'] .= '.jpg';
		}
		$attachment_id = media_handle_sideload( $file_array, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return 0;
		}
		return $attachment_id;
	}

	/**
	 * Helpers shared with the admin meta boxes to convert repeaters to/from
	 * the "one entry per line, columns separated by |" text format.
	 */
	public static function pairs_to_lines( $pairs, $key_a, $key_b ) {
		$lines = array();
		foreach ( (array) $pairs as $pair ) {
			$lines[] = ( $pair[ $key_a ] ?? '' ) . ' | ' . ( $pair[ $key_b ] ?? '' );
		}
		return implode( "\n", $lines );
	}

	public static function lines_to_pairs( $text, $key_a, $key_b ) {
		$out = array();
		foreach ( explode( "\n", $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$cols  = array_map( 'trim', explode( '|', $line, 2 ) );
			$out[] = array(
				$key_a => sanitize_text_field( $cols[0] ?? '' ),
				$key_b => sanitize_text_field( $cols[1] ?? '' ),
			);
		}
		return $out;
	}
}
