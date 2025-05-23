<?php

namespace Skills\TbiPaymentGateway\Gateways;

use Exception;
use Skills\TbiPaymentGateway\BNPLClient;
use Skills\TbiPaymentGateway\Templates;
use Skills\TbiPaymentGateway\Plugin;
use WC_Payment_Gateway;

class TBILoanPaymentGateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'tbi-loan';
        $this->method_title       = __( 'TBI Loan', 'tbi-payment-gateway' );
        $this->method_description = __( 'Accept payments through TBI loans payment scheme.', 'tbi-payment-gateway' );   
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
                'label'       => __( 'Enable TBI BNPL Payment Gateway', 'tbi-payment-gateway' ),
                'default'     => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'tbi-payment-gateway'),
                'type'        => 'text',
                'description' => __( 'This controls the title shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'TBI BNPL Payment', 'tbi-payment-gateway' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'tbi-payment-gateway'),
                'type'        => 'textarea',
                'description' => __( 'This controls the description shown during checkout.', 'tbi-payment-gateway' ),
                'default'     => __( 'Pay securely through TBI BNPL Bank.', 'tbi-payment-gateway' ),
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
        ];
    }
    
    public function process_payment( $order_id ) {
        $order          = wc_get_order( $order_id );
        $bnpl_client    = new BNPLClient( $this->get_option( 'reseller_code' ), $this->get_option( 'reseller_key' ), $this->get_option( 'encryption_key' ) );
        $installment_id = isset( $_POST['bnpl_installment'] ) ? absint( $_POST['bnpl_installment'] ) : null;

        try {
            $data = $bnpl_client->create_application( $order, $installment_id );

            $order->update_meta_data( '_tbi_bnpl_token', $data['token'] );
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

    public function payment_fields() {
        $client       = BNPLClient::get_client( 'loan' );
        $data         = $this->get_posted_data();
        $cart_total   = floatval( WC()->cart->get_total( 'edit' ) );
        $installments = $client->get_installments( absint( $cart_total ) );
        $current_plan = $this->get_selected_plan( $installments, isset( $data['bnpl_installment'] ) ? absint( $data['bnpl_installment'] ) : 0 );

        if( empty( $current_plan ) ) {
            return;
        }

        Templates::render( 'bnpl-options.php', [
            'installments' => $installments,
            'selected'     => $current_plan,
            'description'  => $this->description,
            'loanMonthly'  => $this->round_up( $current_plan['installment_factor'] * $cart_total ),
            'loanTotal'    => $this->round_up( $current_plan['total_due_factor'] * $cart_total ),
            'loanAPR'      => $current_plan['apr'],
            'laonNIR'      => $current_plan['nir'],
            'assetsUrl'    => Plugin::get_plugin_url() . '/assets',
            'infoIcon'     => file_get_contents( Plugin::get_plugin_path() . '/assets/info.svg' )
        ] );
    }

    private function get_posted_data() {
        if( empty( $_POST['post_data'] ) ) {
            return [];
        }

        $data = [];

        parse_str( $_POST['post_data'], $data );

        return $data;
    }

    private function get_selected_plan( array $installments, int $selected_id ): array | null {
        if( empty( $installments ) ) {
            return null;
        }
        
        foreach( $installments as $item ) {
            if( $item['id'] === $selected_id ) {
                return $item;
            }
        }

        return current( $installments );
    }

    public function round_up( float $number, int $precision = 2 ): float {
        $fig = pow( 10, $precision );

        return ceil( $number * $fig ) / $fig;
    }

    public function get_icon() {
        return '';
    }
}