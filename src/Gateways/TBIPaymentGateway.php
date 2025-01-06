<?php

namespace Skills\TbiPaymentGateway\Gateways;

use Skills\TbiPaymentGateway\TBIClient;
use WC_Payment_Gateway;

class TBIPaymentGateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'tbi';
        $this->method_title       = __( 'TBI Payment', 'tbi-payment-gateway' );
        $this->method_description = __('Accept payments through TBI Bank.', 'tbi-payment-gateway');   
        $this->has_fields         = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled     = $this->get_option( 'enabled' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'       => __( 'Enable/Disable', 'tbi-payment-gateway' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable TBI Payment Gateway', 'tbi-payment-gateway' ),
                'default'     => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'tbi-payment-gateway'),
                'type'        => 'text',
                'description' => __( 'This controls the title shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'TBI Payment', 'tbi-payment-gateway' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'tbi-payment-gateway'),
                'type'        => 'textarea',
                'description' => __( 'This controls the description shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'Pay securely through TBI Bank.', 'tbi-payment-gateway' ),
            ],
            'test_mode'  => [
                'title'       => __( 'Test Mode', 'tbi-payment-gateway' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Test Mode', 'tbi-payment-gateway' ),
                'default'     => 'yes',
            ],
            'api_user'   => [
                'title'       => __( 'API User', 'tbi-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This is the API user provided by TBI Bank.', 'tbi-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'api_password' => [
                'title'       => __( 'API Password', 'tbi-payment-gateway' ),
                'type'        => 'password',
                'description' => __( 'This is the API password provided by TBI Bank.', 'tbi-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'sign_key' => [
                'title'       => __( 'Sign Key', 'tbi-payment-gateway' ),
                'type'        => 'password',
                'description' => __( 'This is the sign key from TBI Bank merchant settings.', 'tbi-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'callback_url' => [
                'title'       => __( 'Callback URL', 'tbi-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This is the URL where TBI Bank will send the payment status.', 'tbi-payment-gateway' ),
                'default'     => rest_url( 'tbi/v1/callback' ),
                'desc_tip'    => true,
                'custom_attributes' => [
                    'readonly' => 'readonly',
                ],
            ],
        ];
    }

    public function process_payment( $order_id ) {
        $order  = wc_get_order( $order_id );
        $client = new TBIClient( $this->get_option( 'api_user' ), $this->get_option( 'api_password' ) );

        $response = $client->create_order( $order );

        if( empty( $response ) ) {
            return [
                'result'   => 'fail',
                'redirect' => ''
            ];
        }

        $order->update_meta_data( '_tbi_order_id', $response['orderId'] );
        $order->save_meta_data();

        return [
            'result'   => 'success',
            'redirect' => $response['formUrl']
        ];
    }
}