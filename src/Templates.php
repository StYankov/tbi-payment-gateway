<?php

namespace Skills\TbiPaymentGateway;

class Templates {
    public static function render( string $template, array $data = [] ): void {
        echo self::load_template( $template, $data );
    }

    public static function load_template( string $template, array $data = [] ): string {
        ob_start();

        extract( $data );

        include Plugin::get_plugin_path() . '/templates/' . $template;

        return ob_get_clean();
    }
}