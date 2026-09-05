<?php
/**
 * Loja (arquivo de produtos) — grid simples com filtro por categoria e marca.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categoria_atual = isset( $_GET['categoria'] ) ? sanitize_title( wp_unslash( $_GET['categoria'] ) ) : '';
$marca_atual      = isset( $_GET['marca'] ) ? sanitize_title( wp_unslash( $_GET['marca'] ) ) : '';
$categorias       = get_terms( array( 'taxonomy' => LAI_TAX_CATEGORIA, 'hide_empty' => true ) );
$marcas           = get_terms( array( 'taxonomy' => LAI_TAX_MARCA, 'hide_empty' => true ) );
?>

<div class="lai-loja">
	<header class="lai-loja__cabecalho">
		<h1><?php post_type_archive_title(); ?></h1>
		<div class="lai-loja__acoes-topo">
			<a href="<?php echo esc_url( home_url( '/comparador/' ) ); ?>" class="lai-btn lai-btn--secundario"><?php esc_html_e( 'Comparador', 'loja-afiliados-ia' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/favoritos/' ) ); ?>" class="lai-btn lai-btn--secundario"><?php esc_html_e( 'Favoritos', 'loja-afiliados-ia' ); ?></a>
		</div>
	</header>

	<?php if ( ! empty( $categorias ) || ! empty( $marcas ) ) : ?>
		<form class="lai-loja__filtros" method="get">
			<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
				<select name="categoria" onchange="this.form.submit()">
					<option value=""><?php esc_html_e( 'Todas as categorias', 'loja-afiliados-ia' ); ?></option>
					<?php foreach ( $categorias as $categoria ) : ?>
						<option value="<?php echo esc_attr( $categoria->slug ); ?>" <?php selected( $categoria_atual, $categoria->slug ); ?>><?php echo esc_html( $categoria->name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<?php if ( ! empty( $marcas ) && ! is_wp_error( $marcas ) ) : ?>
				<select name="marca" onchange="this.form.submit()">
					<option value=""><?php esc_html_e( 'Todas as marcas', 'loja-afiliados-ia' ); ?></option>
					<?php foreach ( $marcas as $marca ) : ?>
						<option value="<?php echo esc_attr( $marca->slug ); ?>" <?php selected( $marca_atual, $marca->slug ); ?>><?php echo esc_html( $marca->name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</form>
	<?php endif; ?>

	<div class="lai-grid">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php LAI_Shortcodes::render_card( get_the_ID() ); ?>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nenhum produto cadastrado ainda.', 'loja-afiliados-ia' ); ?></p>
		<?php endif; ?>
	</div>

	<?php the_posts_pagination(); ?>
</div>

<?php
get_footer();
