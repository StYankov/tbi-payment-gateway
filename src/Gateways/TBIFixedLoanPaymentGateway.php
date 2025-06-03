<?php

namespace Skills\TbiPaymentGateway\Gateways;

use Exception;
use Skills\TbiPaymentGateway\BNPLClient;
use Skills\TbiPaymentGateway\Plugin;
use WC_Payment_Gateway;

class TBIFixedLoanPaymentGateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'tbi-loan-fixed';
        $this->method_title       = __( 'TBI Loan Fixed', 'tbi-payment-gateway' );
        $this->method_description = __( 'Accept payments through TBI loan payment scheme which is selected from admin panel.', 'tbi-payment-gateway' );   
        $this->has_fields         = true;
        $this->icon               = Plugin::get_plugin_url() . '/assets/tbi-logo.png';

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
                'label'       => __( 'Enable TBI Fixed Loan Payment Gateway', 'tbi-payment-gateway' ),
                'default'     => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'tbi-payment-gateway'),
                'type'        => 'text',
                'description' => __( 'This controls the title shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'TBI Fixed Loan Payment', 'tbi-payment-gateway' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'tbi-payment-gateway'),
                'type'        => 'textarea',
                'description' => __( 'This controls the description shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'Pay securely through using a TBI loan', 'tbi-payment-gateway' ),
            ],
            'reseller_code' => [
                'title'       => __( 'Reseller Code', 'tbi-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'Reseller code provided by TBI Bank.', 'tbi-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'reseller_key'  => [
                'title'       => __( 'Reseller Key', 'tbi-payment-gateway' ),
                'type'        => 'password',
                'description' => __( 'Reseller key provided by TBI Bank.', 'tbi-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'encryption_key' => [
                'title'       => __( 'Encryption Key', 'tbi-payment-gateway' ),
                'type'        => 'password',
                'description' => __( 'Encryption Key provided by TBI Bank.', 'tbi-payment-gateway' ),
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
            'installment_id' => [
                'title'       => __( 'Selected Installment', 'tbi-payment-gateway' ),
                'type'        => 'select',
                'description' => __( 'Select which installment this payment method will be fixed to. You need to enter reseller code and key first!', 'tbi-payment-gateway' ),
                'desc_tip'    => true,
                'options'     => $this->get_installment_options()
            ]
        ];
    }

    public function process_payment( $order_id ) {
        $order          = wc_get_order( $order_id );
        $client         = BNPLClient::get_client( 'loan-fixed' );
        $installment_id = $this->get_option( 'installment_id' );

        try {
            $data = $client->create_application( $order, $installment_id );

            $order->update_meta_data( '_tbi_loan_token', $data['token'] );
            $order->save_meta_data();

            return [
                'result'   => 'success',
                'redirect' => $data['url']
            ];
        } catch( Exception $e ) {
            return [
                'result'   => 'failure',
                'redirect' => wc_get_checkout_url()
            ];
        }
    }

    private function get_installment_options() {
        if( $this->enabled === 'no' ) {
            return [];
        }

        $client       = BNPLClient::get_client( 'loan-fixed' );
        $installments = $client->get_installments();

        $options      = [
            '' => __( 'None', 'tbi-payment-gateway' )
        ];

        if( empty( $installments ) ) {
            return [];
        }

        foreach( $installments as $item ) {
            $options[ $item['id'] ] = sprintf( '%1$s | %4$s (%2$s - %3$s)', $item['name'], $item['amount_min'], $item['amount_max'], $item['bank_product'] );
        }

        return $options;
    }

    public function get_icon() {
        return '';
    }
}