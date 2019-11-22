<?php

namespace GuzabaPlatform\Installer\Interfaces;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

interface PostInstallHookInterface
{
    /**
     * Will be invoked by guzaba-platform-installer composer plugin on package installation.
     * Must exit with exception on error.
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    public static function post_install_hook(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void ;
}