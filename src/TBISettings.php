<?php

namespace Skills\TbiPaymentGateway;

class TBISettings {
    public const OPTION_KEY = 'tbi';

    public static function get_option( string $key, mixed $default = '' ): mixed {
        $settings = self::get_settings();

        return $settings[ $key ] ?? $default;
    }

    public static function get_settings() {
        return get_option( 'woocommerce_' . self::OPTION_KEY . '_settings', [] );
    }
}