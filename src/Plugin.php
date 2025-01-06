<?php

namespace Skills\TbiPaymentGateway;

final class Plugin {
    private static ?self $instance = null;

    private function __construct() {
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateways' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest' ] );
    }

    public function register_gateways( $methods ) {
        $methods[] = 'Skills\TbiPaymentGateway\Gateways\TBIPaymentGateway';

        return $methods;
    }

    public function register_rest() {
        new Callback();
    }

    public static function init() {
        if( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}