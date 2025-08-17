<?php
/**
 * Order mapper woo -> ledyer
 *
 * @package Ledyer
 */
namespace LedyerOm;

defined( 'ABSPATH' ) || exit();

class CustomerMapper {
	/**
	 * WooCommerce order.
	 *
	 * @var \WC_Order|\WC_Order_Refund
	 */
	public $order;

	/**
	 * Customer mapper constructor.
	 *
	 * @param \WC_Order|\WC_Order_Refund $order WooCommerce order.
	 */
	public function __construct( $order ) {
		$this->order = $order;
	}

	public function woo_to_ledyer_customer() {
		return array(
			'billingAddress'  => $this->process_billing(),
			'shippingAddress' => $this->process_shipping(),
			'email'           => $this->order->get_billing_email(),
			'phone'           => $this->order->get_billing_phone(),
		);
	}

	private function process_billing() {
		$attentionName = $_REQUEST['_billing_attention_name'];
		$careOf        = $_REQUEST['_billing_care_of'];
		return array(
			'companyName'   => $this->order->get_billing_company(),
			'streetAddress' => $this->order->get_billing_address_1(),
			'postalCode'    => $this->order->get_billing_postcode(),
			'city'          => $this->order->get_billing_city(),
			'country'       => $this->order->get_billing_country(),
			'attentionName' => ! empty( $attentionName ) ? $attentionName : '',
			'careOf'        => ! empty( $careOf ) ? $careOf : '',
		);
	}

	private function process_shipping() {
		$attentionName = $_REQUEST['_shipping_attention_name'];
		$careOf        = $_REQUEST['_shipping_care_of'];

		return array(
			'companyName'   => $this->order->get_shipping_company(),
			'streetAddress' => $this->order->get_shipping_address_1(),
			'postalCode'    => $this->order->get_shipping_postcode(),
			'city'          => $this->order->get_shipping_city(),
			'country'       => $this->order->get_shipping_country(),
			'attentionName' => ! empty( $attentionName ) ? $attentionName : '',
			'careOf'        => ! empty( $careOf ) ? $careOf : '',
			'contact'       => $this->process_shipping_contact(),
		);
	}

	private function process_shipping_contact() {
		$shipping_email = $_REQUEST['_shipping_email'];
		return array(
			'firstName' => $this->order->get_shipping_first_name(),
			'lastName'  => $this->order->get_shipping_last_name(),
			'email'     => ! empty( $shipping_email ) ? $shipping_email : '',
			'phone'     => $this->order->get_shipping_phone(),
		);
	}
}
