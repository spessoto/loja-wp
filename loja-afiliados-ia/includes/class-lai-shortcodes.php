<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [lai_vitrine] shortcode (product grid for any page) plus the shared
 * product-card renderer used by the vitrine, the archive template, related
 * products and the "quem viu, também comprou" block.
 */
class LAI_Shortcodes {

	public function __construct() {
		add_shortcode( 'lai_vitrine', array( $this, 'shortcode_vitrine' ) );
	}

	public function shortcode_vitrine( $atts ) {
		$atts = shortcode_atts(
			array(
				'quantidade' => 8,
				'categoria'  => '',
				'marca'      => '',
			),
			$atts,
			'lai_vitrine'
		);

		$args = array(
			'post_type'      => LAI_CPT,
			'posts_per_page' => (int) $atts['quantidade'],
			'post_status'    => 'publish',
		);

		$tax_query = array();
		if ( $atts['categoria'] ) {
			$tax_query[] = array(
				'taxonomy' => LAI_TAX_CATEGORIA,
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['categoria'] ),
			);
		}
		if ( $atts['marca'] ) {
			$tax_query[] = array(
				'taxonomy' => LAI_TAX_MARCA,
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['marca'] ),
			);
		}
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $args );
		ob_start();
		echo '<div class="lai-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			self::render_card( get_the_ID() );
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Renders one product card (used in grids, related products, wishlist and comparador).
	 */
	public static function render_card( $post_id ) {
		$preco      = (float) get_post_meta( $post_id, '_lai_preco_atual', true );
		$preco_orig = (float) get_post_meta( $post_id, '_lai_preco_original', true );
		$nota       = (float) get_post_meta( $post_id, '_lai_avaliacao_nota', true );
		$total      = (int) get_post_meta( $post_id, '_lai_avaliacao_total', true );
		$marca      = get_post_meta( $post_id, '_lai_resumo_curto', true );
		$desconto   = ( $preco_orig > $preco && $preco_orig > 0 ) ? round( ( 1 - ( $preco / $preco_orig ) ) * 100 ) : 0;
		?>
		<div class="lai-card" data-product-id="<?php echo esc_attr( $post_id ); ?>">
			<a class="lai-card__media" href="<?php the_permalink( $post_id ); ?>">
				<?php if ( $desconto > 0 ) : ?>
					<span class="lai-badge lai-badge--desconto">-<?php echo esc_html( $desconto ); ?>%</span>
				<?php endif; ?>
				<?php echo get_the_post_thumbnail( $post_id, 'medium', array( 'loading' => 'lazy' ) ); ?>
			</a>
			<div class="lai-card__body">
				<?php if ( $marca ) : ?><p class="lai-card__marca"><?php echo esc_html( strtoupper( $marca ) ); ?></p><?php endif; ?>
				<h3 class="lai-card__titulo"><a href="<?php the_permalink( $post_id ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
				<?php if ( $total > 0 ) : ?>
					<p class="lai-card__avaliacao">★★★★★ <span>(<?php echo esc_html( number_format_i18n( $total ) ); ?>)</span></p>
				<?php endif; ?>
				<p class="lai-card__preco"><?php echo LAI_Frontend::formatar_preco( $preco ); ?></p>
				<div class="lai-card__acoes">
					<a class="lai-btn lai-btn--primario" href="<?php echo esc_url( LAI_Redirect::get_redirect_url( $post_id ) ); ?>" target="_blank" rel="nofollow sponsored noopener"><?php esc_html_e( 'Comprar', 'loja-afiliados-ia' ); ?></a>
					<button type="button" class="lai-icon-btn lai-wishlist-toggle" data-product-id="<?php echo esc_attr( $post_id ); ?>" aria-label="<?php esc_attr_e( 'Adicionar à lista de desejos', 'loja-afiliados-ia' ); ?>">♡</button>
					<button type="button" class="lai-icon-btn lai-compare-toggle" data-product-id="<?php echo esc_attr( $post_id ); ?>" aria-label="<?php esc_attr_e( 'Adicionar ao comparador', 'loja-afiliados-ia' ); ?>">⇄</button>
				</div>
			</div>
		</div>
		<?php
	}
}
