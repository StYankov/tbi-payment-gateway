<?php
/*
Plugin Name: TBI Payment Gateway
Plugin URI: https://stoilyankov.com/tbi-payment-gateway
Description: A custom payment gateway integration for TBI Bank. This plugin allows seamless payments through TBI Bank's system on your WooCommerce store.
Version: 1.0.0
Author: Stoil Yankov
Author URI: https://stoilyankov.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tbi-payment-gateway
Domain Path: /languages
*/

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', [ \Skills\TbiPaymentGateway\Plugin::class, 'init' ] );