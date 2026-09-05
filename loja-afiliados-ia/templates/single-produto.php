<?php
/**
 * Single product template — layout baseado no design fornecido, sem os
 * blocos de frete/garantia/troca, barra de últimas unidades, resumo da
 * oferta e "fale com especialista" (a loja não fecha venda).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id        = get_the_ID();
	$galeria        = array_filter( array_map( 'intval', (array) get_post_meta( $post_id, '_lai_galeria', true ) ) );
	if ( empty( $galeria ) && has_post_thumbnail() ) {
		$galeria = array( get_post_thumbnail_id() );
	}
	$resumo         = get_post_meta( $post_id, '_lai_resumo_curto', true );
	$sku            = get_post_meta( $post_id, '_lai_sku', true );
	$badge          = get_post_meta( $post_id, '_lai_badge', true );
	$preco          = (float) get_post_meta( $post_id, '_lai_preco_atual', true );
	$preco_original = (float) get_post_meta( $post_id, '_lai_preco_original', true );
	$parcelamento   = get_post_meta( $post_id, '_lai_parcelamento', true );
	$nota           = (float) get_post_meta( $post_id, '_lai_avaliacao_nota', true );
	$total_aval     = (int) get_post_meta( $post_id, '_lai_avaliacao_total', true );
	$bullets        = (array) get_post_meta( $post_id, '_lai_bullets', true );
	$destaques      = (array) get_post_meta( $post_id, '_lai_destaques', true );
	$especificacoes = (array) get_post_meta( $post_id, '_lai_especificacoes', true );
	$indicado       = (array) get_post_meta( $post_id, '_lai_indicado_para', true );
	$nao_indicado   = (array) get_post_meta( $post_id, '_lai_nao_indicado_para', true );
	$avaliacoes     = (array) get_post_meta( $post_id, '_lai_avaliacoes', true );
	$desconto       = ( $preco_original > $preco && $preco_original > 0 ) ? round( ( 1 - ( $preco / $preco_original ) ) * 100 ) : 0;
	$economia       = $preco_original > $preco ? $preco_original - $preco : 0;
	$categorias     = get_the_terms( $post_id, LAI_TAX_CATEGORIA );
	$relacionados   = LAI_Recommendations::get_related( $post_id, 4 );
	$comparar_url   = get_post_type_archive_link( LAI_CPT );
	?>

	<nav class="lai-breadcrumb" aria-label="<?php esc_attr_e( 'Trilha de navegação', 'loja-afiliados-ia' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'loja-afiliados-ia' ); ?></a>
		<?php if ( $categorias && ! is_wp_error( $categorias ) ) : ?>
			/ <a href="<?php echo esc_url( get_term_link( $categorias[0] ) ); ?>"><?php echo esc_html( $categorias[0]->name ); ?></a>
		<?php endif; ?>
		/ <span><?php the_title(); ?></span>
	</nav>

	<article class="lai-produto" data-product-id="<?php echo esc_attr( $post_id ); ?>">

		<div class="lai-produto__topo">

			<div class="lai-produto__galeria">
				<?php if ( $badge ) : ?><span class="lai-badge lai-badge--selo"><?php echo esc_html( strtoupper( $badge ) ); ?></span><?php endif; ?>
				<?php if ( $desconto > 0 ) : ?><span class="lai-badge lai-badge--desconto">-<?php echo esc_html( $desconto ); ?>%</span><?php endif; ?>

				<div class="lai-galeria__corpo">
					<?php if ( count( $galeria ) > 1 ) : ?>
						<div class="lai-galeria__miniaturas">
							<?php foreach ( $galeria as $i => $att_id ) : ?>
								<?php echo LAI_Frontend::media_thumb_html( $att_id, 0 === $i ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="lai-galeria__principal" id="lai-galeria-principal">
						<?php if ( ! empty( $galeria ) ) : ?>
							<?php echo LAI_Frontend::media_principal_html( $galeria[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php else : ?>
							<div class="lai-galeria__placeholder"><?php esc_html_e( 'Sem imagem', 'loja-afiliados-ia' ); ?></div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $bullets ) : ?>
					<ul class="lai-bullets">
						<?php foreach ( $bullets as $bullet ) : ?>
							<li>✓ <?php echo esc_html( $bullet ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="lai-produto__info">
				<?php if ( $resumo ) : ?><p class="lai-produto__marca"><?php echo esc_html( strtoupper( $resumo ) ); ?></p><?php endif; ?>
				<h1 class="lai-produto__titulo"><?php the_title(); ?></h1>

				<p class="lai-produto__meta">
					<?php if ( $nota > 0 ) : ?>
						<span class="lai-estrelas" aria-hidden="true">★★★★★</span>
						<strong><?php echo esc_html( number_format_i18n( $nota, 1 ) ); ?></strong>
						· <?php printf( esc_html__( '%s avaliações', 'loja-afiliados-ia' ), esc_html( number_format_i18n( $total_aval ) ) ); ?>
					<?php endif; ?>
					<?php if ( $sku ) : ?><span class="lai-produto__sku"><?php printf( esc_html__( 'Cód. %s', 'loja-afiliados-ia' ), esc_html( $sku ) ); ?></span><?php endif; ?>
				</p>

				<div class="lai-caixa-oferta">
					<?php if ( $preco_original > $preco ) : ?>
						<p class="lai-preco-original">
							<span class="lai-preco-riscado"><?php echo LAI_Frontend::formatar_preco( $preco_original ); ?></span>
							<?php if ( $economia > 0 ) : ?><span class="lai-economize"><?php printf( esc_html__( 'ECONOMIZE %s', 'loja-afiliados-ia' ), LAI_Frontend::formatar_preco( $economia ) ); ?></span><?php endif; ?>
						</p>
					<?php endif; ?>
					<p class="lai-preco-atual"><?php echo LAI_Frontend::formatar_preco( $preco ); ?> <span>no Pix</span></p>
					<?php if ( $parcelamento ) : ?><p class="lai-parcelamento"><?php echo esc_html( $parcelamento ); ?></p><?php endif; ?>

					<a class="lai-btn lai-btn--primario lai-btn--grande" href="<?php echo esc_url( LAI_Redirect::get_redirect_url( $post_id ) ); ?>" target="_blank" rel="nofollow sponsored noopener">
						<?php esc_html_e( 'Comprar agora', 'loja-afiliados-ia' ); ?> →
					</a>
					<p class="lai-produto__aviso"><?php esc_html_e( 'Você finaliza a compra no site oficial da loja parceira', 'loja-afiliados-ia' ); ?></p>

					<div class="lai-produto__acoes-secundarias">
						<a class="lai-btn lai-btn--secundario" href="<?php echo esc_url( $comparar_url ); ?>#lai-comparador" data-lai-add-compare="<?php echo esc_attr( $post_id ); ?>">
							<?php esc_html_e( 'Comparar com outros modelos', 'loja-afiliados-ia' ); ?>
						</a>
						<button type="button" class="lai-icon-btn lai-wishlist-toggle" data-product-id="<?php echo esc_attr( $post_id ); ?>">
							♡ <?php esc_html_e( 'Favoritos', 'loja-afiliados-ia' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>

		<div class="lai-produto__conteudo">
			<div class="lai-produto__coluna-principal">

				<?php if ( get_the_content() ) : ?>
					<section class="lai-secao">
						<div class="lai-produto__descricao"><?php the_content(); ?></div>
					</section>
				<?php endif; ?>

				<?php if ( $destaques ) : ?>
					<section class="lai-secao lai-destaques">
						<?php foreach ( $destaques as $destaque ) : ?>
							<div class="lai-destaque">
								<strong><?php echo esc_html( $destaque['valor'] ); ?></strong>
								<span><?php echo esc_html( $destaque['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<?php if ( $especificacoes ) : ?>
					<section class="lai-secao">
						<h2><?php esc_html_e( 'Especificações técnicas', 'loja-afiliados-ia' ); ?></h2>
						<table class="lai-tabela-especificacoes">
							<?php foreach ( $especificacoes as $linha ) : ?>
								<tr>
									<th><?php echo esc_html( $linha['chave'] ); ?></th>
									<td><?php echo esc_html( $linha['valor'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</section>
				<?php endif; ?>

				<?php if ( $indicado || $nao_indicado ) : ?>
					<section class="lai-secao">
						<h2><?php esc_html_e( 'Este modelo é para você?', 'loja-afiliados-ia' ); ?></h2>
						<div class="lai-para-voce">
							<?php if ( $indicado ) : ?>
								<div class="lai-para-voce__coluna">
									<h3><?php esc_html_e( 'Indicado para', 'loja-afiliados-ia' ); ?></h3>
									<ul>
										<?php foreach ( $indicado as $item ) : ?><li>✓ <?php echo esc_html( $item ); ?></li><?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( $nao_indicado ) : ?>
								<div class="lai-para-voce__coluna lai-para-voce__coluna--negativo">
									<h3><?php esc_html_e( 'Talvez não seja', 'loja-afiliados-ia' ); ?></h3>
									<ul>
										<?php foreach ( $nao_indicado as $item ) : ?><li>✕ <?php echo esc_html( $item ); ?></li><?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( $avaliacoes ) : ?>
					<section class="lai-secao">
						<h2><?php esc_html_e( 'Avaliações de quem comprou', 'loja-afiliados-ia' ); ?></h2>
						<div class="lai-avaliacoes">
							<div class="lai-avaliacoes__resumo">
								<strong><?php echo esc_html( number_format_i18n( $nota, 1 ) ); ?></strong>
								<span class="lai-estrelas">★★★★★</span>
								<p><?php printf( esc_html__( '%s avaliações', 'loja-afiliados-ia' ), esc_html( number_format_i18n( $total_aval ) ) ); ?></p>
							</div>
							<div class="lai-avaliacoes__lista">
								<?php foreach ( $avaliacoes as $avaliacao ) : ?>
									<div class="lai-avaliacao">
										<p class="lai-avaliacao__cabecalho">
											<strong><?php echo esc_html( $avaliacao['nome'] ); ?></strong> — <?php echo esc_html( $avaliacao['local'] ); ?>
											<span class="lai-estrelas"><?php echo esc_html( str_repeat( '★', (int) round( $avaliacao['nota'] ) ) ); ?></span>
										</p>
										<p><?php echo esc_html( $avaliacao['texto'] ); ?></p>
										<p class="lai-avaliacao__rodape">
											<?php if ( ! empty( $avaliacao['verificada'] ) ) : ?>✓ <?php esc_html_e( 'Compra verificada', 'loja-afiliados-ia' ); ?> · <?php endif; ?>
											<?php echo esc_html( $avaliacao['data'] ); ?>
										</p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( $relacionados ) : ?>
					<section class="lai-secao">
						<h2><?php esc_html_e( 'Quem viu este, também comprou', 'loja-afiliados-ia' ); ?></h2>
						<p class="lai-secao__subtitulo"><?php esc_html_e( 'Modelos da mesma faixa de preço com boa avaliação.', 'loja-afiliados-ia' ); ?></p>
						<div class="lai-grid">
							<?php foreach ( $relacionados as $relacionado_id ) : LAI_Shortcodes::render_card( $relacionado_id ); endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

			</div>
		</div>
	</article>

	<div class="lai-barra-fixa">
		<div class="lai-barra-fixa__produto">
			<?php $imagem_barra = LAI_Frontend::primeira_imagem_da_galeria( $galeria ); ?>
			<?php if ( $imagem_barra ) : ?><?php echo wp_get_attachment_image( $imagem_barra, 'thumbnail' ); ?><?php endif; ?>
			<div>
				<p class="lai-barra-fixa__titulo"><?php the_title(); ?></p>
				<p class="lai-barra-fixa__preco"><?php echo LAI_Frontend::formatar_preco( $preco ); ?></p>
			</div>
		</div>
		<a class="lai-btn lai-btn--primario" href="<?php echo esc_url( LAI_Redirect::get_redirect_url( $post_id ) ); ?>" target="_blank" rel="nofollow sponsored noopener"><?php esc_html_e( 'Comprar agora', 'loja-afiliados-ia' ); ?> →</a>
	</div>

	<?php
endwhile;

get_footer();
