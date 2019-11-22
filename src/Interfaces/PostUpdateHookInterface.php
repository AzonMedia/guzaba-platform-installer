<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer\Interfaces;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use GuzabaPlatform\Installer\Installer;

/**
 * Interface PostUpdateHookInterface
 * @package GuzabaPlatform\Installer\Interfaces
 * To be implemented by NAME\SPACE\PostUpdate class if the package needs to use hooks.
 */
interface PostUpdateHookInterface
{
    /**
     * Will be invoked by guzaba-platform-installer composer plugin on package update.
     * @param Installer $Installer
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $InitialPackage
     * @param PackageInterface $TargetPackage
     */
    public static function post_update_hook(Installer $Installer, InstalledRepositoryInterface $Repo, PackageInterface $InitialPackage, PackageInterface $TargetPackage) : void ;
}