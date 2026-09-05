<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint used to import a product from the JSON schema without
 * touching the admin UI — handy for MCP/agent-driven automation.
 */
class LAI_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'loja-afiliados-ia/v1',
			'/importar',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'importar' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'loja-afiliados-ia/v1',
			'/produtos',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'produtos_por_ids' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Used by the wishlist and comparison pages to fetch product data for
	 * the IDs stored in the visitor's browser (localStorage).
	 */
	public function produtos_por_ids( WP_REST_Request $request ) {
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) );
		if ( ! $ids ) {
			return rest_ensure_response( array() );
		}

		$query = new WP_Query(
			array(
				'post_type'      => LAI_CPT,
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
			)
		);

		$produtos = array();
		foreach ( $query->posts as $post ) {
			$produtos[] = array(
				'id'              => $post->ID,
				'titulo'          => get_the_title( $post ),
				'permalink'       => get_permalink( $post ),
				'imagem'          => get_the_post_thumbnail_url( $post, 'medium' ),
				'marca'           => get_post_meta( $post->ID, '_lai_resumo_curto', true ),
				'preco_atual'     => (float) get_post_meta( $post->ID, '_lai_preco_atual', true ),
				'preco_original'  => (float) get_post_meta( $post->ID, '_lai_preco_original', true ),
				'preco_formatado' => LAI_Frontend::formatar_preco( get_post_meta( $post->ID, '_lai_preco_atual', true ) ),
				'avaliacao_nota'  => (float) get_post_meta( $post->ID, '_lai_avaliacao_nota', true ),
				'avaliacao_total' => (int) get_post_meta( $post->ID, '_lai_avaliacao_total', true ),
				'especificacoes'  => (array) get_post_meta( $post->ID, '_lai_especificacoes', true ),
				'link_redirect'   => LAI_Redirect::get_redirect_url( $post->ID ),
			);
		}

		return rest_ensure_response( $produtos );
	}

	public function importar( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) {
			$data = LAI_Importer::decode_json_colado( $request->get_body() );
		}
		if ( is_wp_error( $data ) ) {
			$data->add_data( array( 'status' => 400 ) );
			return $data;
		}
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'lai_json_invalido', __( 'Corpo da requisição precisa ser um JSON válido.', 'loja-afiliados-ia' ), array( 'status' => 400 ) );
		}

		$post_id = absint( $request->get_param( 'post_id' ) );
		$result  = LAI_Importer::import( $data, $post_id );

		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		return rest_ensure_response(
			array(
				'id'        => $result,
				'edit_link' => get_edit_post_link( $result, 'raw' ),
				'permalink' => get_permalink( $result ),
			)
		);
	}
}
