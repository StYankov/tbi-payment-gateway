<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInita9a44dbefbbe296d5ba63f041f8c5ba2
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInita9a44dbefbbe296d5ba63f041f8c5ba2', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInita9a44dbefbbe296d5ba63f041f8c5ba2', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInita9a44dbefbbe296d5ba63f041f8c5ba2::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
