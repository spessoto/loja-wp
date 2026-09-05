<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end plumbing: assets, price formatting and swapping in the plugin's
 * own single/archive templates for the product post type.
 */
class LAI_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_action( 'pre_get_posts', array( $this, 'filtrar_loja_por_get' ) );
	}

	/**
	 * Lets ?categoria=slug and ?marca=slug on the store archive filter the
	 * main query, used by the <select> filters in archive-produto.php.
	 */
	public function filtrar_loja_por_get( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( LAI_CPT ) ) {
			return;
		}

		$tax_query = array();
		if ( ! empty( $_GET['categoria'] ) ) {
			$tax_query[] = array(
				'taxonomy' => LAI_TAX_CATEGORIA,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['categoria'] ) ),
			);
		}
		if ( ! empty( $_GET['marca'] ) ) {
			$tax_query[] = array(
				'taxonomy' => LAI_TAX_MARCA,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['marca'] ) ),
			);
		}
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
	}

	public function enqueue_assets() {
		if ( ! is_singular( LAI_CPT ) && ! is_post_type_archive( LAI_CPT ) && ! $this->page_has_shortcode() ) {
			return;
		}

		wp_enqueue_style( 'lai-frontend', LAI_PLUGIN_URL . 'assets/css/frontend.css', array(), LAI_VERSION );
		wp_enqueue_script( 'lai-frontend', LAI_PLUGIN_URL . 'assets/js/frontend.js', array(), LAI_VERSION, true );
		wp_localize_script(
			'lai-frontend',
			'LAI_DATA',
			array(
				'restUrl' => esc_url_raw( rest_url( 'loja-afiliados-ia/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'comparadorCheio' => __( 'Você já pode comparar no máximo 4 produtos. Remova um para adicionar outro.', 'loja-afiliados-ia' ),
				),
			)
		);
	}

	private function page_has_shortcode() {
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'lai_vitrine' )
			|| has_shortcode( $post->post_content, 'lai_wishlist' )
			|| has_shortcode( $post->post_content, 'lai_comparador' );
	}

	public function template_include( $template ) {
		if ( is_singular( LAI_CPT ) ) {
			$custom = LAI_PLUGIN_DIR . 'templates/single-produto.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		if ( is_post_type_archive( LAI_CPT ) ) {
			$custom = LAI_PLUGIN_DIR . 'templates/archive-produto.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}

	public static function formatar_preco( $valor ) {
		return 'R$ ' . number_format( (float) $valor, 2, ',', '.' );
	}
}
