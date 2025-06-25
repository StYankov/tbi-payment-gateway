<?php

namespace Skills\TbiPaymentGateway;

use WC_Order;

class Orders {
    public function __construct() {
        add_filter( 'woocommerce_cancel_unpaid_order', [ $this, 'tbi_loan_extended_hold_period' ], 10, 2 );
    }
    public function tbi_loan_extended_hold_period( bool $cancel, WC_Order $order ) {
        if( $order->get_payment_method() !== 'tbi-bnpl' && $order->get_payment_method() !== 'tbi-loan' && $order->get_payment_method() !== 'tbi-loan-fixed' ) {
            return $cancel;
        }

        $created_time   = $order->get_date_modified()->getTimestamp();
        $minutes_passed = floor( ( time() - $created_time ) / 60 );

        // Cancel the order if more than 15 days have passed
        // and the status is still 'pending payment
        return ( 60 * 24 * 15 ) < $minutes_passed;
    }
}