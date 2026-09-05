<?php
/**
 * Plugin Name: Loja de Afiliados com IA
 * Plugin URI: https://github.com/spessoto/loja-wp
 * Description: Cadastra produtos de afiliado (Amazon, Mercado Livre, etc.) importando os dados via IA (Claude, sem chave de API) e exibe uma vitrine simplificada, com comparador, lista de desejos e recomendações automáticas. O site nunca fecha a venda: o visitante é sempre direcionado à loja parceira através do seu link de afiliado.
 * Version: 1.0.0
 * Author: Caio Spessoto
 * Text Domain: loja-afiliados-ia
 * Requires PHP: 7.4
 * Requires at least: 5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LAI_VERSION', '1.0.0' );
define( 'LAI_PLUGIN_FILE', __FILE__ );
define( 'LAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LAI_CPT', 'produto_afiliado' );
define( 'LAI_TAX_CATEGORIA', 'categoria_afiliado' );
define( 'LAI_TAX_MARCA', 'marca_afiliado' );

require_once LAI_PLUGIN_DIR . 'includes/class-lai-cpt.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-meta-boxes.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-importer.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-admin-import-page.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-rest-api.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-redirect.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-wishlist.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-compare.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-recommendations.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-shortcodes.php';
require_once LAI_PLUGIN_DIR . 'includes/class-lai-frontend.php';

/**
 * Boots every plugin module. Each class wires its own hooks in its constructor.
 */
final class Loja_Afiliados_IA {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new LAI_Post_Type();
		new LAI_Meta_Boxes();
		new LAI_Admin_Import_Page();
		new LAI_REST_API();
		new LAI_Redirect();
		new LAI_Wishlist();
		new LAI_Compare();
		new LAI_Shortcodes();
		new LAI_Frontend();

		register_activation_hook( LAI_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( LAI_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	public static function activate() {
		( new LAI_Post_Type() )->register_post_type();
		( new LAI_Post_Type() )->register_taxonomies();
		( new LAI_Redirect() )->add_rewrite_rules();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

Loja_Afiliados_IA::instance();
