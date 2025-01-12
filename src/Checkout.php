<?php

namespace Skills\TbiPaymentGateway;

use Exception;

class Checkout {
    public function __construct() {
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_hide_bnpl_method' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_checkout_script' ] );
    }

    public function maybe_hide_bnpl_method( array $gateways ) {
        if( is_admin() ) {
            return $gateways;
        }

        try {
            BNPLClient::get_client();
        } catch( Exception $e ) {
            unset( $gateways['tbi-bnpl'] );

            return $gateways;
        }

        if( floatval( WC()->cart->get_total( 'edit' ) ) < 100 ) {
            unset( $gateways[ 'tbi-bnpl' ] );
        }

        return $gateways;
    }

    public function maybe_enqueue_checkout_script() {
        if( false === is_checkout() ) {
            return;
        }

        if( false === $this->is_bnpl_enabled() ) {
            return;
        }

        $data = [
            'currency' => get_woocommerce_currency_symbol()
        ];

        wp_enqueue_style( 'tbibnpl-checkout', Plugin::get_plugin_url() . '/assets/styles.css' );
        wp_enqueue_script( 'tbibnpl-checkout', Plugin::get_plugin_url() . '/assets/checkout.js', [], 1.0, true );
        wp_add_inline_script( 'tbibnpl-checkout', 'window.bnpl_data = ' . json_encode( $data ), 'before' );
    }

    public function is_bnpl_enabled(): bool {
        $settings = get_option( 'woocommerce_tbi-bnpl_settings', [] );

        if( empty( $settings ) || empty( $settings['enabled'] ) ) {
            return false;
        }

        return $settings['enabled'] === 'yes'; 
    }
}