<?php

namespace Skills\TbiPaymentGateway\REST;

use PYS_PRO_GLOBAL\GuzzleHttp\Psr7\Message;
use WP_REST_Request;
use WP_REST_Response;

class BNPLCallback {
    public function __construct() {
        register_rest_route( 'tbi/v1', '/bnpl/callback', [
            'methods'             => [ 'GET', 'POST' ],
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true'
        ] );
    }

    public function handle( WP_REST_Request $request ) {
        $order_id = $request->get_param( 'OrderId' );
        $message  = $request->get_param( 'Message' );

        if( empty( $order_id ) || empty( $message ) ) {
            return new WP_REST_Response( null, 400 );
        }

        $order = wc_get_order( $order_id );

        if( empty( $order ) ) {
            return new WP_REST_Response( null, 400 );
        }

        $order->add_order_note(
            sprintf( 'Статус на кредитно известие: %s', $message )
        );

        if( $message === 'approved & signed' || $message === 'paid' ) {
            $order->payment_complete();
        }

        if( file_exists( './callback.json' ) ) {
            $contents = file_get_contents( './callback.json' );
        } else {
            $contents = '';
        }

        $contents .= "\n" . json_encode( $request->get_params(), JSON_PRETTY_PRINT );

        file_put_contents( './callback.json', $contents );

        return new WP_REST_Response( null, 204 );
    }
}