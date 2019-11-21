<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * Class Installer
 * @package GuzabaPlatform\Installer
 * @see https://getcomposer.org/doc/articles/custom-installers.md
 * Custom installer for GuzabaPlatform and GuzabaPlatform Packages
 */
class Installer extends LibraryInstaller
{

    /**
     * This is the composer.type that is supported by this plugin
     */
    protected const SUPPORTED_TYPES = [self::PACKAGE_TYPE_PLATFORM, self::PACKAGE_TYPE_COMPONENT];
    protected const PACKAGE_TYPE_PLATFORM = 'guzaba-platform';
    protected const PACKAGE_TYPE_COMPONENT = 'guzaba-platform-component';
    //protected const SUPPORTED_PACKAGE = 'guzaba-platform/guzaba-platform';
//    /**
//     * {@inheritDoc}
//     */
//    public function getInstallPath(PackageInterface $package)
//    {
//        $prefix = substr($package->getPrettyName(), 0, 23);
//        if ('phpdocumentor/template-' !== $prefix) {
//            throw new \InvalidArgumentException(
//                'Unable to install template, phpdocumentor templates '
//                .'should always start their package name with '
//                .'"phpdocumentor/template-"'
//            );
//        }
//
//        return 'data/templates/'.substr($package->getPrettyName(), 23);
//    }

    public function install(InstalledRepositoryInterface $Repo, PackageInterface $Package)
    {

        parent::install($Repo, $Package);
//        name: guzaba-platform/guzaba-platform
//dir
//autoload Array
//    (
//        [psr-4] => Array
//        (
//            [GuzabaPlatform\] => app/src/GuzabaPlatform
//        )
//
//    )
        $package_type = $Package->getType();

        if ($package_type === self::PACKAGE_TYPE_PLATFORM) {
            $this->install_guzaba_platform($Repo, $Package);
        } elseif ($package_type === self::PACKAGE_TYPE_COMPONENT) {
            $this->install_guzaba_platform_component($Repo, $Package);
        } else {
            //throw
        }


//        print 'name: '.$package_name.PHP_EOL;
//        print 'dir: '.$target_dir.PHP_EOL;
//        print 'autoload '.print_r($autoload, TRUE).PHP_EOL;
        //$vendor_dir = $PackageEvent->getComposer()->getConfig()->get('vendor-dir');
    }

    /**
     * Performs few additional steps before invoking self::install_guzaba_platform_component()
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    private function install_guzaba_platform(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {

        print sprintf('GuzabaPlatformInstaller initializing GuzabaPlatofrm').PHP_EOL;

        $installer_dir = __DIR__;
        $composer_json_dir = realpath($installer_dir.'/../../../../');//this is the root dir
        //$guzaba_platform_dir = realpath($installer_dir.'/../../guzaba-platform/');
        $guzaba_platform_dir = $this->getInstallPath($Package);
        if (file_exists($composer_json_dir.'/app')) {
            //error
            throw new \RuntimeException(sprintf('The directory %s already exists.', $composer_json_dir.'/app'));
        } else {
            `mkdir $composer_json_dir/app`;
            `cp -r $guzaba_platform_dir/app/bin $composer_json_dir/app/bin`;
            `cp -r $guzaba_platform_dir/app/certificates $composer_json_dir/app/certificates`;
            `cp -r $guzaba_platform_dir/app/dockerfiles $composer_json_dir/app/dockerfiles`;
            `cp -r $guzaba_platform_dir/app/logs $composer_json_dir/app/logs`;
            `cp -r $guzaba_platform_dir/app/public $composer_json_dir/app/public`;
            `cp -r $guzaba_platform_dir/app/registry $composer_json_dir/app/registry`;
            `cp -r $guzaba_platform_dir/app/startup_generated $composer_json_dir/app/startup_generated`;
            `cp -r $guzaba_platform_dir/app/public_src $composer_json_dir/app/public_src`;
            //`mkdir $composer_json_dir/app/public_src`;
            //`mkdir $composer_json_dir/app/public_src/build`;
            //`ln -s $guzaba_platform_dir/app/public_src $composer_json_dir/public_src/$namespace`;
        }
        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (file_exists($manifest_json_file)) {
            throw new \RuntimeException(sprintf('The file %s already exists.', $manifest_json_file));
        }
        $manifest_content = new \stdClass();
        $manifest_content->name = 'GuzabaPlatform';
        $manifest_content->url = 'https://platform.guzaba.org/';
        $manifest_content->components = [];
        file_put_contents($manifest_json_file, json_encode($manifest_content, JSON_PRETTY_PRINT));

        $this->install_guzaba_platform_component($Repo, $Package);

    }

    private function install_guzaba_platform_component(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {

        //print 'P1 : '.$this->getInstallPath($Package).PHP_EOL;// /home/local/PROJECTS/guzaba2-platform/Z6/vendor/guzaba-platform/guzaba-platform
        //print 'P2 : '.$this->getPackageBasePath($Package).PHP_EOL;// /home/local/PROJECTS/guzaba2-platform/Z6/vendor/guzaba-platform/guzaba-platform
        $plugin_dir = $this->getInstallPath($Package);

        $package_name = $Package->getName();
        print sprintf('GuzabaPlatformInstaller running for component $s', $package_name).PHP_EOL;

        //$target_dir = $Package->getTargetDir();
        $autoload = $Package->getAutoload();
        if (!isset($autoload['psr-4'])) {
            throw new \RuntimeException(sprintf('The component %s does not define a PSR-4 autoloader.', $package_name));
        }
        if (count($autoload['psr-4'])===0) {
            throw new \RuntimeException(sprintf('The component %s does not define a PSR-4 autoloader.', $package_name));
        }
        //if more than one ns - error too
        //no - multiple namespaces will be supported

        $namespace = array_key_first($autoload['psr-4']);

//        $installer_dir = __DIR__;
//        $composer_json_dir = realpath($installer_dir.'/../../../../');//this is the root dir
//        $guzaba_platform_dir = realpath($installer_dir.'/../../guzaba-platform/');
//        if (file_exists($composer_json_dir.'/bin')) {
//            //error
//        } else {
//            `mkdir $composer_json_dir/app`;
//            `cp -r $guzaba_platform_dir/app/bin $composer_json_dir/app/bin`;
//            `cp -r $guzaba_platform_dir/app/certificates $composer_json_dir/certificates`;
//            `mkdir $composer_json_dir/app/public_src`;
//            `mkdir $composer_json_dir/app/public_src/build`;
//            //`ln -s $guzaba_platform_dir/app/public_src $composer_json_dir/public_src/$namespace`;
//        }
        $installer_dir = __DIR__;
        $composer_json_dir = realpath($installer_dir.'/../../../../');//this is the root dir
        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (file_exists($manifest_json_file)) {
            $manifest_content = json_decode(file_get_contents($manifest_json_file));
        } else {
            $manifest_content = [];
        }
        if (isset($manifest_content->components)) {
            $manifest_content->components = [];
        }
        $component = new \stdClass();
        $component->name = $package_name;
        $component->namespace = $namespace;
        $component->root_dir = $plugin_dir;
        $component->src_dir = $plugin_dir.'/app/src';
        $component->public_src_dir = $plugin_dir.'/app/public_src';
        $manifest_content->components[] = $component;
        file_put_contents($manifest_json_file, json_encode($manifest_content, JSON_PRETTY_PRINT));

        //update the webpack.config.js
    }

    /**
     * {@inheritDoc}
     */
    public function supports($package_type)
    {
        //return 'phpdocumentor-template' === $packageType;
        //print 'SUPPORTS: '.$package_type.PHP_EOL;
        return in_array($package_type, self::SUPPORTED_TYPES);
    }
}