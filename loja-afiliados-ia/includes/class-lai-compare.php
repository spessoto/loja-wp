<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comparison page shortcode. Like the wishlist, the selection of products
 * lives in localStorage; this just renders the table container that
 * assets/js/frontend.js populates via REST.
 */
class LAI_Compare {

	const MAX_PRODUTOS = 4;

	public function __construct() {
		add_shortcode( 'lai_comparador', array( $this, 'shortcode' ) );
	}

	public function shortcode() {
		ob_start();
		?>
		<div class="lai-comparador" id="lai-comparador" data-max="<?php echo esc_attr( self::MAX_PRODUTOS ); ?>">
			<div class="lai-comparador__tabela-wrap">
				<table class="lai-comparador__tabela" id="lai-comparador-tabela"></table>
			</div>
			<p class="lai-empty-state" id="lai-comparador-vazio" hidden><?php esc_html_e( 'Adicione produtos ao comparador clicando no ícone ⇄ nas páginas de produto ou na loja.', 'loja-afiliados-ia' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}
}
