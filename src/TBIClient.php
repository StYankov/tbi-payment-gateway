<?php

namespace Skills\TbiPaymentGateway;

use WC_Order;
use WP_Error;

class TBIClient {
    public function __construct( protected string $api_user, protected string $api_password ) { }

    /**
     * @return array{'orderId': string, 'formUrl': string}|null
     */
    public function create_order( WC_Order $order ): ?array {
        $api_url = $this->get_api_url();

        $data = [
            'userName'           => $this->api_user,
            'password'           => $this->api_password,
            'currency'           => '975',
            'language'           => 'en',
            'amount'             => absint( $order->get_total() * 100 ),
            'orderNumber'        => $order->get_id(),
            'email'              => $order->get_billing_email(),
            'returnUrl'          => $order->get_checkout_order_received_url(),
            'failUrl'            => wc_get_checkout_url(),
            'dynamicCallbackUrl' => rest_url( 'tbi/v1/callback' ),
        ];

        $response = wp_remote_post( $api_url . 'rest/register.do', [
            'body'    => http_build_query( $data ),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    public function get_order_status( $order_id ): array|WP_Error {
        $api_url = $this->get_api_url();

        $data = [
            'userName'    => $this->api_user,
            'password'    => $this->api_password,
            'orderNumber' => $order_id,
        ];

        $response = wp_remote_post( $api_url . 'rest/getOrderStatusExtended.do', [
            'body' => http_build_query( $data ),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    public function get_api_url() {
        $test_mode = TBISettings::get_option( 'test_mode', 'yes' );

        return $test_mode === 'yes' ? ' https://ecomadmuat.tbibank.bg/payment/' : 'https://ecomadm.tbibank.bg/payment/';
    }

    public static function get_client() {
        $settings = get_option( 'woocommerce_tbi_settings', [] );

        $api_user = $settings['api_user'] ?? '';
        $api_password = $settings['api_password'] ?? '';

        return new self( $api_user, $api_password );
    }
}