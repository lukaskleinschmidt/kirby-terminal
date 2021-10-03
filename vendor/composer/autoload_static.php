<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd592f846d44b411bfe2ccc611e92a733
{
    public static $files = array (
        'adfcb9c4c64eb08a3fce759cb8101757' => __DIR__ . '/../..' . '/helpers.php',
    );

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
            0 => __DIR__ . '/../..' . '/classes/LukasKleinschmidt',
        ),
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
        'LukasKleinschmidt\\Terminal\\Process' => __DIR__ . '/../..' . '/classes/LukasKleinschmidt/Process.php',
        'LukasKleinschmidt\\Terminal\\Script' => __DIR__ . '/../..' . '/classes/LukasKleinschmidt/Script.php',
        'LukasKleinschmidt\\Terminal\\Terminal' => __DIR__ . '/../..' . '/classes/LukasKleinschmidt/Terminal.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd592f846d44b411bfe2ccc611e92a733::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd592f846d44b411bfe2ccc611e92a733::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd592f846d44b411bfe2ccc611e92a733::$classMap;

        }, null, ClassLoader::class);
    }
}
