<?php

namespace Skills\TbiPaymentGateway;

final class Plugin {
    private static ?self $instance = null;

    private function __construct() {
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateways' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest' ] );

        new Checkout();
    }

    public function register_gateways( $methods ) {
        $methods[ 'tbi' ] = 'Skills\TbiPaymentGateway\Gateways\TBIPaymentGateway';
        $methods[ 'tbi-bnpl' ] = 'Skills\TbiPaymentGateway\Gateways\TBIBNPLPaymentGateway';

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

    public static function get_plugin_path() {
        return path_join( plugin_dir_path( TBI_PAYMENT_GATEWAY_FILE ), 'src' );
    }

    public static function get_plugin_url() {
        return plugin_dir_url( TBI_PAYMENT_GATEWAY_FILE ) . 'src'; 
    }
}