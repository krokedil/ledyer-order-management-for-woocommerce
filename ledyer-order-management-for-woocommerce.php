<?php
/**
 * Plugin Name: Ledyer Order Management for WooCommerce
 * Plugin URI: https://github.com/ledyer/ledyer-order-management-for-woocommerce
 * Description: Ledyer Order Management for WooCommerce.
 * Author: Ledyer AB
 * Author URI: https://www.ledyer.com
 * Version: 1.5.5
 * Text Domain: ledyer-order-management-for-woocommerce
 * Domain Path: /languages
 *
 * WC requires at least: 4.0.0
 * WC tested up to: 10.3.5
 *
 * Copyright (c) 2017-2025 Ledyer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use LedyerOm\Ledyer_Order_Management_For_WooCommerce;

\defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/classes/class-ledyer-om-singleton.php';
require_once __DIR__ . '/classes/class-ledyer-om-main.php';

/**
 * Required minimums and constants
 */
\define( 'LOM_WC_VERSION', Ledyer_Order_Management_For_WooCommerce::VERSION );
\define( 'LOM_WC_MIN_PHP_VER', '7.0.0' );
\define( 'LOM_WC_MIN_WC_VER', '4.0.0' );
\define( 'LOM_WC_MAIN_FILE', __FILE__ );
\define( 'LOM_WC_PLUGIN_NAME', dirname( plugin_basename( LOM_WC_MAIN_FILE ) ) );
\define( 'LOM_WC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
\define( 'LOM_WC_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

// Declare HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

function ledyerOm() {
	return Ledyer_Order_Management_For_WooCommerce::instance();
}

ledyerOm();
