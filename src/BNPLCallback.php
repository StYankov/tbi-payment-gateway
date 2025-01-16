<?php

namespace SKills\TbiPaymentGateway;

use WP_REST_Request;

class BNPLCallback {
    public function __construct() {
        register_rest_route( 'tbi/v1', '/bnpl/callback', [
            'methods'             => [ 'GET', 'POST' ],
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true'
        ] );
    }

    public function handle( WP_REST_Request $request ) {

        if( file_exists( './callback.json' ) ) {
            $contents = file_get_contents( './callback.json' );
        } else {
            $contents = '';
        }

        $contents .= "\n" . json_encode( $request->get_params(), JSON_PRETTY_PRINT );

        file_put_contents( './callback.json', $contents );

        return true;
    }
}