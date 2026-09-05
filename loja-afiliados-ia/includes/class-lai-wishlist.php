<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wishlist page shortcode. The list of product IDs itself is kept
 * client-side (localStorage, see assets/js/frontend.js) so it works for
 * guests without accounts; this shortcode just renders the container that
 * the JS fills in via the REST "produtos por IDs" endpoint.
 */
class LAI_Wishlist {

	public function __construct() {
		add_shortcode( 'lai_wishlist', array( $this, 'shortcode' ) );
	}

	public function shortcode() {
		ob_start();
		?>
		<div class="lai-wishlist" id="lai-wishlist">
			<div class="lai-grid" id="lai-wishlist-grid"></div>
			<p class="lai-empty-state" id="lai-wishlist-vazio" hidden><?php esc_html_e( 'Sua lista de desejos está vazia. Navegue pela loja e clique no coração ♡ para salvar produtos.', 'loja-afiliados-ia' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}
}
