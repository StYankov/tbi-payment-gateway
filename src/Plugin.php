<?php

namespace Skills\TbiPaymentGateway;

use SKills\TbiPaymentGateway\BNPLCallback;

final class Plugin {
    private static ?self $instance = null;

    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_rest' ] );

        new Checkout();
    }

    public function register_rest() {
        new Callback();
        new BNPLCallback();
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