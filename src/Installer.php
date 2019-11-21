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
 */
class Installer extends LibraryInstaller
{

    /**
     * This is the composer.type that is supported by this plugin
     */
    protected const SUPPORTED_TYPE = 'guzaba-platform';
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
        print 'INSTALL'.PHP_EOL;


        parent::install($Repo, $Package);

        $package_name = $Package->getName();
        $target_dir = $Package->getTargetDir();
        $autoload = $Package->getAutoload();
        print 'name: '.$package_name.PHP_EOL;
        print 'dir '.$target_dir.PHP_EOL;
        print 'autoload '.print_r($autoload, TRUE).PHP_EOL;
        //$vendor_dir = $PackageEvent->getComposer()->getConfig()->get('vendor-dir');
    }

    /**
     * {@inheritDoc}
     */
    public function supports($package_type)
    {
        //return 'phpdocumentor-template' === $packageType;
        print 'SUPPORTS: '.$package_type.PHP_EOL;
        return self::SUPPORTED_TYPE === $package_type;
    }
}