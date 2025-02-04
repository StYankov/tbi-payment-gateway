<?php

namespace Skills\TbiPaymentGateway\REST;

use Skills\TbiPaymentGateway\TBISettings;
use WP_REST_Request;
use WP_REST_Response;

class Callback {
    public function __construct() {
        register_rest_route( 'tbi/v1', '/callback', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'checksum' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'status'  => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'orderNumber' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'mdOrder' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );
    }

    public function handle( WP_REST_Request $request ) {
        error_log( json_encode( $request->get_params() ) );

        if( ! $this->verify_checksum( $request ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid checksum' ], 400 );
        }

        if( absint( $request->get_param( 'status' ) ) !== 1 ) {
            return new WP_REST_Response( [ 'error' => 'Invalid status' ], 400 );
        }

        $order = $this->get_order( $request->get_param( 'orderNumber' ) );

        WC()->mailer();

        $order->payment_complete();

        return new WP_REST_Response( [ 'success' => true ] );
    }

    private function verify_checksum( WP_REST_Request $request ): bool {
        $data = sprintf( 
            'mdOrder;%1$s;operation;%2$s;orderNumber;%3$s;status;%4$s;',
            $request->get_param( 'mdOrder' ),
            $request->get_param( 'operation' ),
            $request->get_param( 'orderNumber' ),
            $request->get_param( 'status' )
        );

        $checksum = strtoupper( hash_hmac( 'sha256', $data, TBISettings::get_option( 'sign_key' ) ) );

        return $checksum === $request->get_param( 'checksum' );
    }

    private function get_order( string $order_number ) {
        return wc_get_order( $order_number );
    }
}