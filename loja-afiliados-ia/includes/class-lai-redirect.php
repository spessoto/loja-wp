<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the "Comprar agora" click: /ir/{slug-do-produto} counts a click
 * and 302-redirects the visitor straight to the affiliate link. Keeping the
 * affiliate URL behind our own domain avoids exposing it directly in the
 * page markup and gives us a simple click counter per product.
 */
class LAI_Redirect {

	const QUERY_VAR = 'lai_ir';

	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
	}

	public function add_rewrite_rules() {
		add_rewrite_rule( '^ir/([^/]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public static function get_redirect_url( $product_id ) {
		return home_url( '/ir/' . get_post_field( 'post_name', $product_id ) . '/' );
	}

	public function maybe_redirect() {
		$slug = get_query_var( self::QUERY_VAR );
		if ( ! $slug ) {
			return;
		}

		$produtos = get_posts(
			array(
				'name'           => sanitize_title( $slug ),
				'post_type'      => LAI_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
		$product = $produtos ? $produtos[0] : null;

		if ( ! $product ) {
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		}

		$link = get_post_meta( $product->ID, '_lai_link_afiliado', true );
		if ( ! $link ) {
			wp_safe_redirect( get_permalink( $product ), 302 );
			exit;
		}

		$cliques = (int) get_post_meta( $product->ID, '_lai_cliques', true );
		update_post_meta( $product->ID, '_lai_cliques', $cliques + 1 );

		wp_redirect( $link, 302 );
		exit;
	}
}
