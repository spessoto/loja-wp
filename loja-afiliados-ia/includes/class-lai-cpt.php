<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the affiliate product post type and its taxonomies.
 */
class LAI_Post_Type {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Produtos', 'loja-afiliados-ia' ),
			'singular_name'      => __( 'Produto', 'loja-afiliados-ia' ),
			'add_new_item'       => __( 'Adicionar novo produto', 'loja-afiliados-ia' ),
			'edit_item'          => __( 'Editar produto', 'loja-afiliados-ia' ),
			'new_item'           => __( 'Novo produto', 'loja-afiliados-ia' ),
			'view_item'          => __( 'Ver produto', 'loja-afiliados-ia' ),
			'search_items'       => __( 'Buscar produtos', 'loja-afiliados-ia' ),
			'not_found'          => __( 'Nenhum produto encontrado', 'loja-afiliados-ia' ),
			'all_items'          => __( 'Todos os produtos', 'loja-afiliados-ia' ),
			'menu_name'          => __( 'Loja de Afiliados', 'loja-afiliados-ia' ),
			'featured_image'     => __( 'Imagem principal', 'loja-afiliados-ia' ),
		);

		register_post_type(
			LAI_CPT,
			array(
				'labels'       => $labels,
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => 'loja',
				'rewrite'      => array( 'slug' => 'produto', 'with_front' => false ),
				'menu_icon'    => 'dashicons-cart',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_menu' => true,
			)
		);
	}

	public function register_taxonomies() {
		register_taxonomy(
			LAI_TAX_CATEGORIA,
			LAI_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Categorias', 'loja-afiliados-ia' ),
					'singular_name' => __( 'Categoria', 'loja-afiliados-ia' ),
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'categoria-produto' ),
			)
		);

		register_taxonomy(
			LAI_TAX_MARCA,
			LAI_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Marcas', 'loja-afiliados-ia' ),
					'singular_name' => __( 'Marca', 'loja-afiliados-ia' ),
				),
				'hierarchical' => false,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'marca' ),
			)
		);
	}
}
