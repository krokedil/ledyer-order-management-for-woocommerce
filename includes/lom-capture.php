<?php

\defined( 'ABSPATH' ) || die();

/**
 * Captures a Ledyer order.
 *
 * @param int                      $order_id Order ID.
 * @param bool                     $action If this was triggered by an action.
 * @param $api The lom api instance
 */
function lom_capture_ledyer_order( $order_id, $api, $action = false ) {
	$options                         = get_option( 'lom_settings', array() );
	$auto_capture                    = $options['lom_auto_capture'] ?? 'yes';
	$lom_status_mapping_ledyer_error = $options['lom_status_mapping_ledyer_error'] ?? 'wc-on-hold';

	// If the capture on complete is not enabled in lom-settings.
	if ( 'no' === $auto_capture ) {
		return;
	}

	$order = wc_get_order( $order_id );

	// Check if the order has been paid.
	if ( empty( $order->get_date_paid() ) ) {
		return;
	}

	// Only support Ledyer orders
	$is_ledyer_order = lom_order_placed_with_ledyer( $order->get_payment_method() );
	if ( ! $is_ledyer_order ) {
		return;
	}

	// Do nothing if Ledyer order was already captured according to the woo-order
	if ( $order->get_meta( '_wc_ledyer_capture_id', true ) ) {
		$order->add_order_note( 'Ledyer order has already been captured.' );
		$order->save();
		return;
	}

	$ledyer_order_id = $order->get_meta( '_wc_ledyer_order_id', true );

	// Do nothing if we don't have Ledyer order ID.
	if ( $ledyer_order_id && ! $order->get_meta( '_transaction_id', true ) ) {
		$errmsg = 'Ledyer order ID is missing, Ledyer order could not be captured at this time.';
		if ( 'none' !== $lom_status_mapping_ledyer_error ) {
			$order->update_status( $lom_status_mapping_ledyer_error, $errmsg );
		} else {
			$order->add_order_note( $errmsg );
		}
		$order->save();
		return;
	}

	// Fetch the ledyer order
	$ledyer_order = $api->get_order( $ledyer_order_id );

	if ( is_wp_error( $ledyer_order ) ) {
		$errmsg = 'Ledyer order could not be captured due to an error: ' . $ledyer_order->get_error_message();
		if ( 'none' !== $lom_status_mapping_ledyer_error ) {
			$order->update_status( $lom_status_mapping_ledyer_error, $errmsg );
		} else {
			$order->add_order_note( $errmsg );
		}
		$order->save();
		return;
	}

	if ( in_array( LedyerOmOrderStatus::fullyCaptured, $ledyer_order['status'] ) ) {
		$first_captured       = lom_get_first_captured( $ledyer_order );
		$captured_at          = $first_captured['createdAt'];
		$formatted_capture_at = date( 'Y-m-d H:i:s', strtotime( $captured_at ) );
		$capture_id           = $first_captured['ledgerId'];

		$order->add_order_note( 'Ledyer order has already been captured on ' . $formatted_capture_at );
		$order->update_meta_data( '_wc_ledyer_capture_id', $capture_id );
		$order->save();
		return;
	} elseif ( in_array( LedyerOmOrderStatus::cancelled, $ledyer_order['status'] ) ) {
		$order->add_order_note( 'Ledyer order failed to capture, the order has already been cancelled' );
		$order->save();
		return;
	}

	/*
	// To be implemented in the future.
	if ( ! $order->get_meta( '_ledyer_ready_for_capture', true ) ) {
		$errmsg = 'Ledyer order is not ready for capture.';

		if ( 'none' !== $lom_status_mapping_ledyer_error ) {
			$order->update_status( $lom_status_mapping_ledyer_error, $errmsg );
		} else {
			$order->add_order_note( $errmsg );
		}
		$order->save();
		return;
	} */

	$orderMapper = new \LedyerOm\OrderMapper( $order );
	$data        = $orderMapper->woo_to_ledyer_capture_order_lines();
	$response    = $api->capture_order( $ledyer_order_id, $data );

	if ( ! is_wp_error( $response ) ) {
		$first_captured = lom_get_first_captured( $response );
		$capture_id     = $first_captured['ledgerId'];

		$order->add_order_note( 'Ledyer order captured. Capture amount: ' . $order->get_formatted_order_total( '', false ) . '. Capture ID: ' . $capture_id );
		$order->update_meta_data( '_wc_ledyer_capture_id', $capture_id, true );
		$order->save();
		return;
	}

	$errmsg = 'Ledyer order could not be captured due to an error: ' . $response->get_error_message();
	if ( 'none' !== $lom_status_mapping_ledyer_error ) {
		$order->update_status( $lom_status_mapping_ledyer_error, $errmsg );
	} else {
		$order->add_order_note( $errmsg );
	}
	$order->save();
}
