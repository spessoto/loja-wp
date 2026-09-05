<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Quem viu este, também comprou": automatic recommendations based on the
 * product's category and a similar price range, falling back to the same
 * brand and finally to best-rated products if the category is empty.
 */
class LAI_Recommendations {

	public static function get_related( $post_id, $limit = 4 ) {
		$preco      = (float) get_post_meta( $post_id, '_lai_preco_atual', true );
		$categorias = wp_get_post_terms( $post_id, LAI_TAX_CATEGORIA, array( 'fields' => 'ids' ) );
		$marcas     = wp_get_post_terms( $post_id, LAI_TAX_MARCA, array( 'fields' => 'ids' ) );

		$base_args = array(
			'post_type'      => LAI_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'rand',
			'meta_query'     => array(),
		);

		if ( $preco > 0 ) {
			$base_args['meta_query'][] = array(
				'key'     => '_lai_preco_atual',
				'value'   => array( $preco * 0.6, $preco * 1.6 ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			);
		}

		$attempts = array();

		if ( ! empty( $categorias ) ) {
			$attempts[] = array_merge(
				$base_args,
				array(
					'tax_query' => array(
						array(
							'taxonomy' => LAI_TAX_CATEGORIA,
							'field'    => 'term_id',
							'terms'    => $categorias,
						),
					),
				)
			);
		}

		if ( ! empty( $marcas ) ) {
			$attempts[] = array_merge(
				$base_args,
				array(
					'tax_query' => array(
						array(
							'taxonomy' => LAI_TAX_MARCA,
							'field'    => 'term_id',
							'terms'    => $marcas,
						),
					),
				)
			);
		}

		$fallback           = $base_args;
		unset( $fallback['meta_query'] );
		$fallback['orderby'] = 'meta_value_num';
		$fallback['meta_key'] = '_lai_avaliacao_total';
		$fallback['order']    = 'DESC';
		$attempts[]           = $fallback;

		$found = array();
		foreach ( $attempts as $args ) {
			if ( count( $found ) >= $limit ) {
				break;
			}
			$args['post__not_in'] = array_merge( array( $post_id ), $found );
			$args['posts_per_page'] = $limit - count( $found );
			$query = new WP_Query( $args );
			foreach ( $query->posts as $post ) {
				$found[] = $post->ID;
			}
		}

		return array_slice( array_unique( $found ), 0, $limit );
	}
}
