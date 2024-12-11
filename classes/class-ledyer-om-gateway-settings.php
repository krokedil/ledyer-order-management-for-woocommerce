<?php
/**
 *
 * The gateway plugin specific settings.
 *
 * @package LedyerOm
 */

namespace LedyerOm;

\defined( 'ABSPATH' ) || die();

/**
 * Gateway_Settings class.
 *
 * The gateway plugin specific settings.
 */
class Gateway_Settings {

	/**
	 * The gateway.
	 *
	 * @var string
	 */
	private $gateway;

	/**
	 * The settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string $gateway The gateway to get settings for. Either 'lco' or 'ledyer_payments'. Defaults to the latter.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;

		if ( 'lco' === $gateway ) {
			$this->settings = get_option( 'woocommerce_lco_settings' );
		} else {
			$this->settings = get_option( 'woocommerce_ledyer_payments_settings' );
		}
	}

	/**
	 * Get the test environment.
	 *
	 * For Ledyer Payments, we'll return `false` as the 'development_test_environment' setting doesn't exist.
	 *
	 * @return string
	 */
	public function is_test_environment() {
		$environment = wc_get_var( $this->settings['development_test_environment'], false );
		return apply_filters( 'lco_wc_credentials_from_session', $environment, $this->is_test_mode() );
	}

	/**
	 * Whether test mode is enabled.
	 *
	 * @return bool
	 */
	public function is_test_mode() {
		$key       = 'lco' === $this->gateway ? 'testmode' : 'test_mode';
		$test_mode = wc_string_to_bool( $this->settings[ $key ] );

		return apply_filters( 'lco_wc_credentials_from_session', $test_mode, $test_mode );
	}

	/**
	 * Whether logging is enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		$logging = $this->settings['logging'];
		return apply_filters( 'lco_wc_credentials_from_session', $logging, $this->is_test_mode() );
	}

	/**
	 * Get the client credentials.
	 *
	 * @return array|bool The client credentials or false if they are not set.
	 */
	public function get_client_credentials() {
		if ( 'lco' === $this->gateway ) {
			$mode          = $this->is_test_mode() ? 'test_' : '';
			$client_id     = $this->settings[ "{$mode}merchant_id" ];
			$client_secret = $this->settings[ "{$mode}shared_secret" ];

		} else {
			$client_id     = $this->settings['client_id'];
			$client_secret = $this->settings['client_secret'];
		}

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return false;
		}

		return array(
			'client_id'     => $client_id,
			'client_secret' => htmlspecialchars_decode( $client_secret ),
		);
	}
}
