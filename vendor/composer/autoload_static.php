<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit13dc7be80b97760ce5a21b3fa5b8266b
{
    public static $files = array (
        '320cde22f66dd4f5d3fd621d3e88b98f' => __DIR__ . '/..' . '/symfony/polyfill-ctype/bootstrap.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'j' => 
        array (
            'joshtronic\\' => 11,
        ),
        'V' => 
        array (
            'VisualAppeal\\' => 13,
        ),
        'T' => 
        array (
            'Twig\\' => 5,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Polyfill\\Ctype\\' => 23,
        ),
        'P' => 
        array (
            'Psr\\SimpleCache\\' => 16,
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
        'J' => 
        array (
            'Jfcherng\\Utility\\' => 17,
            'Jfcherng\\Diff\\' => 14,
        ),
        'D' => 
        array (
            'Desarrolla2\\Cache\\' => 18,
        ),
        'C' => 
        array (
            'Composer\\Semver\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'joshtronic\\' => 
        array (
            0 => __DIR__ . '/..' . '/joshtronic/php-googleplaces/src',
        ),
        'VisualAppeal\\' => 
        array (
            0 => __DIR__ . '/..' . '/visualappeal/php-auto-update/src',
        ),
        'Twig\\' => 
        array (
            0 => __DIR__ . '/..' . '/twig/twig/src',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Polyfill\\Ctype\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-ctype',
        ),
        'Psr\\SimpleCache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/simple-cache/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
        'Jfcherng\\Utility\\' => 
        array (
            0 => __DIR__ . '/..' . '/jfcherng/php-mb-string/src',
            1 => __DIR__ . '/..' . '/jfcherng/php-color-output/src',
        ),
        'Jfcherng\\Diff\\' => 
        array (
            0 => __DIR__ . '/..' . '/jfcherng/php-sequence-matcher/src',
            1 => __DIR__ . '/..' . '/jfcherng/php-diff/src',
        ),
        'Desarrolla2\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/desarrolla2/cache/src',
        ),
        'Composer\\Semver\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/semver/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit13dc7be80b97760ce5a21b3fa5b8266b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit13dc7be80b97760ce5a21b3fa5b8266b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit13dc7be80b97760ce5a21b3fa5b8266b::$classMap;

        }, null, ClassLoader::class);
    }
}