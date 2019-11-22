<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer\Interfaces;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use GuzabaPlatform\Installer\Installer;

/**
 * Interface PostUninstallHookInterface
 * @package GuzabaPlatform\Installer\Interfaces
 * To be implemented by NAME\SPACE\PostUninstall class if the package needs to use hooks.
 */
interface PostUninstallHookInterface
{
    /**
     * Will be invoked by guzaba-platform-installer composer plugin on package uninstallation.
     * @param Installer $Installer
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    public static function post_uninstall_hook(Installer $Installer, InstalledRepositoryInterface $Repo, PackageInterface $Package) : void ;
}