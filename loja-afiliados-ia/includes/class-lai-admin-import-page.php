<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screen: "Importar com IA". Explains the workflow and accepts the
 * JSON that Claude generated after reading the affiliate product page.
 */
class LAI_Admin_Import_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . LAI_CPT,
			__( 'Importar com IA', 'loja-afiliados-ia' ),
			__( 'Importar com IA', 'loja-afiliados-ia' ),
			'edit_posts',
			'lai-importar',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$result = null;
		if ( isset( $_POST['lai_import_json'] ) && check_admin_referer( 'lai_import' ) ) {
			$json = wp_unslash( $_POST['lai_import_json'] );
			$data = json_decode( $json, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
				$result = new WP_Error( 'lai_json_invalido', __( 'JSON inválido. Revise o texto colado.', 'loja-afiliados-ia' ) );
			} else {
				$result = LAI_Importer::import( $data );
			}
		}

		$schema_example = wp_json_encode(
			array(
				'titulo'            => 'Aspirador Vertical Sem Fio Vertax V12 Ciclônico 450W',
				'resumo_curto'      => 'Aspirador Vertical Sem Fio',
				'marca'             => 'Vertax',
				'categoria'         => 'Aspiradores',
				'sku'               => 'B412-V12',
				'link_afiliado'     => 'https://www.amazon.com.br/dp/EXEMPLO?tag=seu-afiliado-20',
				'loja_destino'      => 'Amazon',
				'badge'             => 'Mais vendido',
				'preco_atual'       => 699.9,
				'preco_original'    => 1029.9,
				'parcelamento'      => '10x de R$ 69,99 sem juros',
				'avaliacao_nota'    => 4.8,
				'avaliacao_total'   => 1284,
				'descricao'         => 'Texto/HTML da descrição completa do produto.',
				'bullets'           => array( 'Indicado para pets', 'Sem saco', 'Filtro HEPA lavável' ),
				'destaques'         => array( array( 'valor' => '450W', 'label' => 'Potência de sucção' ) ),
				'especificacoes'    => array( array( 'chave' => 'Tipo', 'valor' => 'Vertical sem fio 2 em 1' ) ),
				'indicado_para'     => array( 'Casas e apartamentos de até 100 m²' ),
				'nao_indicado_para' => array( 'Limpeza de líquidos ou obra' ),
				'avaliacoes'        => array(
					array(
						'nome'       => 'Camila R.',
						'local'      => 'Belo Horizonte, MG',
						'nota'       => 5,
						'data'       => 'há 2 semanas',
						'verificada' => true,
						'texto'      => 'Resolveu meu problema com pelo de gato no sofá.',
					),
				),
				'imagem_destaque'   => 'https://exemplo.com/imagens/produto-1.jpg',
				'imagens'           => array( 'https://exemplo.com/imagens/produto-2.jpg' ),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Importar produto com IA', 'loja-afiliados-ia' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Como funciona (sem gastar API):', 'loja-afiliados-ia' ); ?></strong><br>
					<?php esc_html_e( '1. Copie o link de afiliado do produto (Amazon, Mercado Livre, etc).', 'loja-afiliados-ia' ); ?><br>
					<?php esc_html_e( '2. Cole o link em uma conversa com o Claude (app/site do seu plano pago) e peça para ele ler a página e devolver os dados no formato JSON abaixo.', 'loja-afiliados-ia' ); ?><br>
					<?php esc_html_e( '3. Cole o JSON gerado no campo abaixo e clique em Importar. As imagens são baixadas automaticamente para a biblioteca de mídia.', 'loja-afiliados-ia' ); ?>
				</p>
			</div>

			<?php if ( $result instanceof WP_Error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
			<?php elseif ( is_int( $result ) ) : ?>
				<div class="notice notice-success">
					<p>
						<?php esc_html_e( 'Produto importado com sucesso!', 'loja-afiliados-ia' ); ?>
						<a href="<?php echo esc_url( get_edit_post_link( $result ) ); ?>"><?php esc_html_e( 'Editar produto', 'loja-afiliados-ia' ); ?></a> ·
						<a href="<?php echo esc_url( get_permalink( $result ) ); ?>" target="_blank"><?php esc_html_e( 'Ver na loja', 'loja-afiliados-ia' ); ?></a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'lai_import' ); ?>
				<textarea name="lai_import_json" rows="16" class="large-text code" placeholder="Cole aqui o JSON gerado pela IA" required></textarea>
				<p><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Importar produto', 'loja-afiliados-ia' ); ?></button></p>
			</form>

			<h2><?php esc_html_e( 'Modelo de JSON para pedir à IA', 'loja-afiliados-ia' ); ?></h2>
			<p><?php esc_html_e( 'Peça ao Claude, por exemplo:', 'loja-afiliados-ia' ); ?> <em>"Leia esta página de produto de afiliado e devolva os dados exatamente neste formato JSON, sem comentários: ..."</em></p>
			<textarea readonly rows="20" class="large-text code" onclick="this.select()"><?php echo esc_textarea( $schema_example ); ?></textarea>

			<h2><?php esc_html_e( 'Importação automática (avançado)', 'loja-afiliados-ia' ); ?></h2>
			<p><?php esc_html_e( 'Se preferir automatizar via ferramentas de agente (ex.: MCP do WordPress), envie o mesmo JSON por POST para o endpoint REST abaixo, autenticado com um usuário com permissão de editor.', 'loja-afiliados-ia' ); ?></p>
			<code><?php echo esc_html( rest_url( 'loja-afiliados-ia/v1/importar' ) ); ?></code>
		</div>
		<?php
	}
}
