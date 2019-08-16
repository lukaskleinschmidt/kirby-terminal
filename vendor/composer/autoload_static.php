<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc076d605bc02f3a2cd89a5768757176b
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LukasKleinschmidt\\Terminal\\' => 27,
        ),
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LukasKleinschmidt\\Terminal\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
    );

    public static $classMap = array (
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
        'LukasKleinschmidt\\Terminal\\Process' => __DIR__ . '/../..' . '/src/Process.php',
        'LukasKleinschmidt\\Terminal\\Script' => __DIR__ . '/../..' . '/src/Script.php',
        'LukasKleinschmidt\\Terminal\\Terminal' => __DIR__ . '/../..' . '/src/Terminal.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc076d605bc02f3a2cd89a5768757176b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc076d605bc02f3a2cd89a5768757176b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc076d605bc02f3a2cd89a5768757176b::$classMap;

        }, null, ClassLoader::class);
    }
}
