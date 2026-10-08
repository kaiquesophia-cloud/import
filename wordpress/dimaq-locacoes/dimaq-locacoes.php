<?php
/**
 * Plugin Name:       Dimaq Locações — Gestão de Locação
 * Description:       Sistema de gestão para locadora de equipamentos: clientes, equipamentos, contratos de locação, devoluções, ordens de serviço, vendas, estoque, financeiro, faturamento, notas fiscais, relatórios, catálogo no site e área do cliente.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Dimaq Locações
 * Text Domain:       dimaq-locacoes
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DL_VERSION', '2.0.0' );
define( 'DL_DB_VERSION', '2' );
define( 'DL_FILE', __FILE__ );
define( 'DL_DIR', plugin_dir_path( __FILE__ ) );
define( 'DL_URL', plugin_dir_url( __FILE__ ) );

require_once DL_DIR . 'includes/functions.php';
require_once DL_DIR . 'includes/class-dl-pricing.php';
require_once DL_DIR . 'includes/class-dl-install.php';
require_once DL_DIR . 'includes/class-dl-db.php';
require_once DL_DIR . 'includes/class-dl-settings.php';
require_once DL_DIR . 'includes/class-dl-modules.php';
require_once DL_DIR . 'includes/class-dl-crud.php';
require_once DL_DIR . 'includes/class-dl-items.php';
require_once DL_DIR . 'includes/class-dl-availability.php';
require_once DL_DIR . 'includes/class-dl-contracts.php';
require_once DL_DIR . 'includes/class-dl-stock.php';
require_once DL_DIR . 'includes/class-dl-finance.php';
require_once DL_DIR . 'includes/class-dl-service-orders.php';
require_once DL_DIR . 'includes/class-dl-sales.php';
require_once DL_DIR . 'includes/class-dl-fiscal.php';
require_once DL_DIR . 'includes/class-dl-documents.php';
require_once DL_DIR . 'includes/class-dl-reports.php';
require_once DL_DIR . 'includes/class-dl-dashboard.php';
require_once DL_DIR . 'includes/class-dl-admin.php';
require_once DL_DIR . 'includes/class-dl-frontend.php';
require_once DL_DIR . 'includes/class-dl-rest.php';
require_once DL_DIR . 'includes/class-dl-api.php';
require_once DL_DIR . 'includes/class-dl-app.php';
require_once DL_DIR . 'includes/class-dl-cron.php';

register_activation_hook( __FILE__, array( 'DL_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DL_Cron', 'unschedule' ) );

add_action(
	'plugins_loaded',
	function () {
		DL_Admin::init();
		DL_Contracts::init();
		DL_Finance::init();
		DL_Service_Orders::init();
		DL_Sales::init();
		DL_Documents::init();
		DL_Frontend::init();
		DL_Rest::init();
		DL_API::init();
		DL_App::init();
		DL_Cron::init();
		// Depois de registrar o endereço /sistema, para a atualização poder gravar as regras.
		add_action( 'init', array( 'DL_Install', 'maybe_upgrade' ), 20 );
	}
);
