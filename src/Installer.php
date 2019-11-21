<?php

namespace GuzabaPlatform\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

class Installer extends LibraryInstaller
{
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
        parent::install($Repo, $Package); // TODO: Change the autogenerated stub
    }

    /**
     * {@inheritDoc}
     */
    public function supports($package_type)
    {
        //return 'phpdocumentor-template' === $packageType;
        print 'SUPPORTS: '.$package_type.PHP_EOL;
        return 'guzaba-platform' === $package_type;
    }
}