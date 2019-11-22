<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer\Interfaces;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use GuzabaPlatform\Installer\Installer;

/**
 * Interface PostInstallHookInterface
 * @package GuzabaPlatform\Installer\Interfaces
 * To be implemented by NAME\SPACE\PostInstall class if the package needs to use hooks.
 */
interface PostInstallHookInterface
{
    /**
     * Will be invoked by guzaba-platform-installer composer plugin on package installation.
     * Must exit with exception on error.
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    public static function post_install_hook(Installer $Installer, InstalledRepositoryInterface $Repo, PackageInterface $Package) : void ;
    
}