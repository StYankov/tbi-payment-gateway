<?php

namespace Skills\TbiPaymentGateway;

use Error;

class Checkout {
    public function __construct() {
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_hide_bnpl_method' ] );
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_hide_tbi_loan_method' ] );
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_hide_tbi_fixed_loan_method' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_checkout_script' ] );
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateways' ] );
    }

    public function maybe_hide_bnpl_method( array $gateways ) {
        if( is_admin() || empty( WC()->cart ) ) {
            return $gateways;
        }

        if( false === array_key_exists( 'tbi-bnpl', $gateways ) ) {
            return $gateways;
        }

        if( BNPLClient::get_client( 'bnpl' )->is_empty() ) {
            unset( $gateways['tbi-bnpl'] );

            return $gateways;
        }

        $cart_total = floatval( WC()->cart->get_total( 'edit' ) );

        if( $cart_total < 40 || $cart_total >= 400 ) {
            unset( $gateways['tbi-bnpl'] );
        }

        return $gateways;
    }

    public function maybe_hide_tbi_loan_method( array $gateways ) {
        if( is_admin() || empty( WC()->cart ) ) {
            return $gateways;
        }

        if( false === array_key_exists( 'tbi-loan', $gateways ) ) {
            return $gateways;
        }

        if( BNPLClient::get_client( 'loan' )->is_empty() ) {
            unset( $gateways['tbi-loan'] );

            return $gateways;
        }

        $cart_total = floatval( WC()->cart->get_total( 'edit' ) );

        if( $cart_total < 100 ) {
            unset( $gateways['tbi-loan'] );
        }

        return $gateways;
    }

    public function maybe_hide_tbi_fixed_loan_method( array $gateways ) {
        if( is_admin() || empty( WC()->cart ) ) {
            return $gateways;
        }

        if( false === array_key_exists( 'tbi-loan-fixed', $gateways ) ) {
            return $gateways;
        }

        if( BNPLClient::get_client( 'loan-fixed' )->is_empty() ) {
            unset( $gateways['tbi-loan-fixed'] );

            return $gateways;
        }

        $cart_total = floatval( WC()->cart->get_total( 'edit' ) );

        if( $cart_total < 100 ) {
            unset( $gateways['tbi-loan-fixed'] );
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
        wp_enqueue_script( 'popperjs', 'https://unpkg.com/@popperjs/core@2', [ 'tbibnpl-checkout' ], 1, true );
        wp_enqueue_script( 'tippy', 'https://unpkg.com/tippy.js@6', [ 'tbibnpl-checkout', 'popperjs' ], 1, true );

        wp_add_inline_script( 'tbibnpl-checkout', 'window.bnpl_data = ' . json_encode( $data ), 'before' );
    }

    public function is_bnpl_enabled(): bool {
        $settings = get_option( 'woocommerce_tbi-loan_settings', [] );

        if( empty( $settings ) || empty( $settings['enabled'] ) ) {
            return false;
        }

        return $settings['enabled'] === 'yes'; 
    }

    public function register_gateways( $methods ) {
        $methods[ 'tbi' ]            = Gateways\TBIPaymentGateway::class;
        $methods[ 'tbi-bnpl' ]       = Gateways\TBIBNPLPaymentGateway::class;
        $methods[ 'tbi-loan' ]       = Gateways\TBILoanPaymentGateway::class;
        $methods[ 'tbi-loan-fixed' ] = Gateways\TBIFixedLoanPaymentGateway::class;

        return $methods;
    }
}