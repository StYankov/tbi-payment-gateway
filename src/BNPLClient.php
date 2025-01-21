<?php

namespace Skills\TbiPaymentGateway;

use Error;
use Exception;
use WC_Order;

class BNPLClient {
    public function __construct( public string $resellerCode, public string $resellerKey, public string $encryptionKey ) { }

    public function get_installments( ?float $amount = null ) {
        $data = get_transient( 'tbi_bnpl_installments' );

        if( empty( $data ) ) {
            $response = wp_remote_get( $this->get_api_url() . sprintf( '/api/GetCalculations?reseller_code=%1$s&reseller_key=%2$s', $this->resellerCode, $this->resellerKey ) );

            if( is_wp_error( $response ) ) {
                return [];
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            set_transient( 'tbi_bnpl_installments', $data, 60 * 60 * 4 );
        }

        if( ! $amount ) {
            return $data;
        }

        return array_values( array_filter( $data, function( $item ) use ( $amount ) {
            return $amount >= $item['amount_min']; 
        } ) );
    }

    /**
     * @return array{'order_id': int, 'token': string, 'url': string}
     */
    public function create_application( WC_Order $order, ?int $installment_id  = null ) {
        $order_items  = [];

        foreach( $order->get_items() as $order_item ) {
            /** @var \WC_Product $product */
            $product = $order_item->get_product();

            $order_items[] = [
                'name'  => $order_item->get_name(),
                'qty'   => absint( $order_item->get_quantity() ),
                'price' => round( floatval( $order_item->get_total() ) / $order_item->get_quantity(), 2 ),
                'sku'   => $product->get_id(),
                'category'  => 255,
                'imagelink' => get_the_post_thumbnail_url( $product->get_id() ),
            ];
        }

        if( floatval( $order->get_total_fees() ) !== 0 ) {
            $order_items[0]['price'] += $order->get_total_fees();
        }

        $order_data = [
            'orderid'            => $order->get_id(),
            'firstname'          => $order->get_billing_first_name(),
            'lastname'           => $order->get_billing_last_name(),
            'email'              => $order->get_billing_email(),
            'phone'              => $order->get_billing_phone(),
            'items'              => $order_items,
            'failRedirectURL'    => wc_get_checkout_url(),
            'successRedirectURL' => $order->get_checkout_order_received_url(),
            'statusURL'          => rest_url( 'tbi/v1/bnpl/callback' )
        ];

        if( $installment_id !== null ) {
            $installment  = $this->get_installment( $installment_id );

            if( empty( $installment ) ) {
                throw new Exception( __( 'Installment plan not found', 'tbi-payment-gateway' ), 400 );
            }

            $order_data['period'] = $installment['period'];
            $order_data['promo']  = $installment['total_due_factor'] === 1;
        } else {
            $order_data['bnpl'] = 1;
        }

        $body = [
            'reseller_code' => $this->resellerCode,
            'reseller_key'  => $this->resellerKey,
            'data'          => Cryptor::Encrypt( json_encode( $order_data, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES ), $this->encryptionKey )
        ];

        $response = wp_remote_post( $this->get_api_url() . '/api/RegisterApplication', [
            'body'        => json_encode( $body ),
            'headers'     => [
                'Content-Type' => 'application/json'
            ],
            'data_format' => 'body'
        ] );

        if( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            throw new Exception( __( 'Loan application request failed', 'tbi-payment-gateway' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if( ! is_array( $data ) || ! isset( $data['error'] ) || $data['error'] !== 0 ) {
            throw new Exception( __( 'Loan application request failed', 'tbi-payment-gateway' ), 400 );
        }

        return $data;
    }

    public function get_installment( int $id ): ?array {
        foreach( $this->get_installments() as $installment ) {
            if( $installment['id'] === $id ) {
                return $installment;
            }
        }

        return null;
    }

    public function get_api_url() {
        return 'https://beta.tbibank.support';
    }

    public static function get_client( $type = 'bnpl' ) {
        $settings = get_option( sprintf( 'woocommerce_tbi-%s_settings', $type ) );

        if( empty( $settings ) ) {
            throw new Error( 'TBI BNPL settings are empty' );
        }

        $reseller_code  = isset( $settings['reseller_code'] ) ? $settings['reseller_code'] : null;
        $reseller_key   = isset( $settings['reseller_key'] ) ? $settings['reseller_key'] : null;
        $encryption_key = isset( $settings['encryption_key'] ) ? $settings['encryption_key'] : null;

        if( ! $reseller_code || ! $reseller_key || ! $encryption_key ) {
            throw new Error( 'TBI BNPL settings are empty' );
        }

        return new self( $reseller_code, $reseller_key, $encryption_key );
    }
}