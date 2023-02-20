<?php
/**
 * Order mapper woo -> ledyer
 *
 * @package Ledyer
 */
namespace LedyerOm;

use Brick\Money\Money;
use Brick\Math\RoundingMode;

defined( 'ABSPATH' ) || exit();

class OrderMapper {
	

	/**
	 * Ledyer order lines.
	 *
	 * @var array
	 */
	public $ledyer_order_lines = array();

	/**
	 * Ledyer order amount in minor units.
	 *
	 * @var integer
	 */
	public $ledyer_total_order_amount = 0;

	/**
	 * Ledyer order amount excl vat in minor units.
	 *
	 * @var integer
	 */
	public $ledyer_total_order_amount_excl_vat = 0;

	/**
	 * Ledyer order vat amount in minor units.
	 *
	 * @var integer
	 */
	public $ledyer_total_order_vat_amount = 0;


	/**
	 * WooCommerce order.
	 *
	 * @var bool|WC_Order|WC_Order_Refund
	 */
	public $order;

	/**
	 * Order mapper constructor.
	 *
	 * @param int    $order WooCommerce order
	 */
	public function __construct( $order ) {
		$this->order = $order;
	}

	public function woo_to_ledyer_edit_order_lines() {
		$this->process_order_line_items();
		return array(
			'orderLines'                => $this->ledyer_order_lines,
			'totalOrderAmount'          => $this->ledyer_total_order_amount,
			'totalOrderAmountExclVat'   => $this->ledyer_total_order_amount_excl_vat,
			'totalOrderVatAmount'       => $this->ledyer_total_order_vat_amount,
		);
	}

	public function woo_to_ledyer_capture_order_lines() {
		$this->process_order_line_items();
		return array(
			'orderLines'                => $this->ledyer_order_lines,
			'totalCaptureAmount'        => $this->ledyer_total_order_amount,
		);
	}


	/**
	 * Process WooCommerce order items to Ledyer order lines.
	 */
	private function process_order_line_items() {
		$this->order->calculate_shipping();
		$this->order->calculate_taxes();
		$this->order->calculate_totals();

		$total = Money::of($this->order->get_total(), $this->order->get_currency(), null, RoundingMode::HALF_UP);
		$totalTax = Money::of($this->order->get_total_tax(), $this->order->get_currency(), null, RoundingMode::HALF_UP);
		$totalExclTax = $total->minus($totalTax);
		$this->ledyer_total_order_amount = $total->getMinorAmount()->toInt();
		$this->ledyer_total_order_vat_amount  = $totalTax->getMinorAmount()->toInt();
		$this->ledyer_total_order_amount_excl_vat  = $totalExclTax->getMinorAmount()->toInt();

		foreach ( $this->order->get_items() as $order_item ) {
			$ledyer_item = $this->process_order_item( $order_item, $this->order );
			$order_line_item = apply_filters( 'lom_wc_order_line_item', $ledyer_item, $order_item );
			if ( $order_line_item ) {
				$this->ledyer_order_lines[] = $order_line_item;
			}
		}

		foreach ( $this->order->get_items( 'shipping' ) as $order_item ) {
			$this->ledyer_order_lines[] = $this->process_order_item( $order_item, $this->order, 'shippingFee', 1);
		}

		foreach ( $this->order->get_items( 'fee' ) as $order_item ) {
			$this->ledyer_order_lines[] = $this->process_order_item( $order_item, $this->order, 'surcharge', 1 );
		}
	}

	private function process_order_item( $order_item, $order, $ledyerType = null, $quantity = null) {
		return array(
			'type'                  => $ledyerType ? $ledyerType : $this->get_item_type( $order_item ),
			'reference'             => $this->get_item_reference( $order_item ),
			'description'           => $this->get_item_name( $order_item ),
			'quantity'              => $quantity ? $quantity : $this->get_item_quantity( $order_item ),
			'unitPrice'             => $this->get_item_unit_price( $order_item, $order->get_currency()),
			'unitDiscountAmount'    => $this->get_item_discount_amount( $order_item, $order->get_currency()),
			'vat'                   => $this->get_item_tax_rate( $order, $order_item ),
			'totalAmount'           => $this->get_item_total_amount( $order_item, $order->get_currency()),
			'totalVatAmount'        => $this->get_item_tax_amount( $order_item, $order->get_currency()),
		);
	}

	private function get_item_type( $order_line_item ) {
		$product = $order_line_item->get_product();
		return $product && ! $product->is_virtual() ? 'physical' : 'digital';
	}

	private function get_item_reference( $order_line_item ) {
		if ( 'line_item' === $order_line_item->get_type() ) {
			$product = $order_line_item['variation_id'] ? wc_get_product( $order_line_item['variation_id'] ) : wc_get_product( $order_line_item['product_id'] );
			if ( $product ) {
				if ( $product->get_sku() ) {
					$item_reference = $product->get_sku();
				} else {
					$item_reference = $product->get_id();
				}
			} else {
				$item_reference = $order_line_item->get_name();
			}
		} elseif ( 'shipping' === $order_line_item->get_type() ) {
			$item_reference = $order_line_item->get_method_id() . ':' . $order_line_item->get_instance_id();
		} elseif ( 'coupon' === $order_line_item->get_type() ) {
			$item_reference = 'Discount';
		} elseif ( 'fee' === $order_line_item->get_type() ) {
			$item_reference = 'Fee';
		} else {
			$item_reference = $order_line_item->get_name();
		}

		return substr( (string) $item_reference, 0, 200 );
	}

	private function get_item_name( $order_line_item ) {
		$order_line_item_name = $order_line_item->get_name();
		return substr( (string) wp_strip_all_tags( $order_line_item_name ), 0, 200);
	}

	private function get_item_quantity( $order_line_item ) {
		if ( $order_line_item->get_quantity() ) {
			return $order_line_item->get_quantity();
		} else {
			return 1;
		}
	}

	private function get_item_unit_price( $order_line_item, $currency) {
		if ( 'shipping' === $order_line_item->get_type() || 'fee' === $order_line_item->get_type() ) {
			$item_price = $order_line_item->get_total() + $order_line_item->get_total_tax();
			$item_quantity = 1;
		} elseif ( 'coupon' === $order_line_item->get_type() ) {
			$item_price    = $order_line_item->get_discount();
			$item_quantity = 1;
		} else {
			$item_price = $order_line_item->get_subtotal() + $order_line_item->get_subtotal_tax();
			$item_quantity = $order_line_item->get_quantity() ? $order_line_item->get_quantity() : 1;
		}

		$money = Money::of( $item_price, $currency, null, RoundingMode::HALF_UP);
		$money = $money->dividedBy( $item_quantity, RoundingMode::HALF_UP);
		return $money->getMinorAmount()->toInt();
	}

	public function get_item_tax_rate( $order, $order_line_item = false) {
		if ( 'coupon' === $order_line_item->get_type() ) {
			return 0;
		}
		$tax_items = $order->get_items( 'tax' );
		foreach ( $tax_items as $tax_item ) {
			$rate_id = $tax_item->get_rate_id();
			foreach ( $order_line_item->get_taxes()['total'] as $key => $value ) {
				if ( '' !== $value ) {
					if ( $rate_id === $key ) {
						return round( \WC_Tax::_get_tax_rate( $rate_id )['tax_rate'] * 100 );
					}
				}
			}
		}
		return 0;
	}

	private function get_item_total_amount( $order_line_item, $currency ) {
		if ( 'shipping' === $order_line_item->get_type() || 'fee' === $order_line_item->get_type() ) {
			$item_total_amount = $order_line_item->get_total() + $order_line_item->get_total_tax();
		} elseif ( 'coupon' === $order_line_item->get_type() ) {
			$item_total_amount = $order_line_item->get_discount();
		} else {
			$item_total_amount = $order_line_item->get_total() + $order_line_item->get_total_tax();
		}
		return $this->amount_to_minor( $item_total_amount, $currency );
	}

	private function get_item_discount_amount( $order_line_item, $currency ) {
		if ( $order_line_item['subtotal'] > $order_line_item['total'] ) {
			$item_discount_amount = ( $order_line_item['subtotal'] + $order_line_item['subtotal_tax'] - $order_line_item['total'] - $order_line_item['total_tax'] );
		} else {
			$item_discount_amount = 0;
		}

		$money = Money::of($item_discount_amount, $currency, null, RoundingMode::HALF_UP);
		$quantity = $this->get_item_quantity( $order_line_item);
		$money = $money->dividedBy($quantity, RoundingMode::HALF_UP);
		return $money->getMinorAmount()->toInt();
	}

	private function get_item_tax_amount( $order_line_item, $currency ) {
		if ( in_array( $order_line_item->get_type(), array( 'line_item', 'fee', 'shipping' ), true ) ) {
			$item_tax_amount = $order_line_item->get_total_tax();
		} elseif ( 'coupon' === $order_line_item->get_type() ) {
			$item_tax_amount = $order_line_item->get_discount_tax();
		} else {
			$item_tax_amount = 0;
		}
		$amount = $this->amount_to_minor( $item_tax_amount, $currency);
		return $amount;
	}

	private function amount_to_minor($amount, $currency) {
		$money = Money::of($amount, $currency, null, RoundingMode::HALF_UP);
		return $money->getMinorAmount()->toInt();
	}

}