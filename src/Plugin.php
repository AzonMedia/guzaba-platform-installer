<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Class Plugin
 * @package GuzabaPlatform\Installer
 * Hooks a custom installer to Composer
 */
class Plugin implements PluginInterface
{

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $Composer
     * @param IOInterface $Io
     */
    public function activate(Composer $Composer, IOInterface $Io)
    {
        $Installer = new Installer($Io, $Composer);
        $Composer->getInstallationManager()->addInstaller($Installer);
    }
}