<?php

namespace GuzabaPlatform\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $Compoer, IOInterface $Io)
    {
        $Installer = new Installer($Io, $Compoer);
        $Compoer->getInstallationManager()->addInstaller($Installer);
    }
}