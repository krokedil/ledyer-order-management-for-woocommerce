<?php
/**
 * File for Parent Settings class.
 *
 * @package LedyerOm
 */

namespace LedyerOm;

\defined( 'ABSPATH' ) || die();

/**
 * Parent Settings class.
 *
 * Gets settings from the parent plugin, either Ledyer Checkout or Ledyer Payments
 */
class ParentSettings {

	use Singleton;

	public function set_settings() {
		// First try and get credentials from Ledyer checkout.
		self::$settings = get_option( 'woocommerce_lco_settings' );

		// If that is not found, try and get from Ledyer Payments.
		if ( empty( self::$settings ) ) {
			self::$settings = get_option( 'woocommerce_ledyer_payments_settings' );
		}
	}

	public function get_test_environment() {
		$environment = self::$settings['development_test_environment'] ?? 'sandbox';

		return apply_filters( 'lco_wc_credentials_from_session', $environment, self::$settings['testmode'] ?? self::$settings['test_mode'] );
	}

	public function get_is_test_mode() {
		$test_mode = self::$settings['testmode'] ?? self::$settings['test_mode'];

		return apply_filters( 'lco_wc_credentials_from_session', $test_mode, self::$settings['testmode'] ?? self::$settings['test_mode'] );
	}

	public function get_logger_enabled() {
		$logging = self::$settings['logging'];

		return apply_filters( 'lco_wc_credentials_from_session', $logging, self::$settings['testmode'] ?? self::$settings['test_mode'] );
	}
}
