<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use GuzabaPlatform\Installer\Interfaces\PostInstallHookInterface;

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

    protected const JSON_ENCODE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;


    public function install(InstalledRepositoryInterface $Repo, PackageInterface $Package)
    {

        parent::install($Repo, $Package);

        $package_type = $Package->getType();

        if ($package_type === self::PACKAGE_TYPE_PLATFORM) {
            $this->install_guzaba_platform($Repo, $Package);
        } elseif ($package_type === self::PACKAGE_TYPE_COMPONENT) {
            $this->install_guzaba_platform_component($Repo, $Package);
        } else {
            throw new \RuntimeException(sprintf('An unsupported package type %s is provided.', $package_type));
        }

    }


    /**
     * Performs few additional steps before invoking self::install_guzaba_platform_component()
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    private function install_guzaba_platform(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {

        print sprintf('GuzabaPlatformInstaller: initializing GuzabaPlatofrm').PHP_EOL;

        //TODO - move this in a PostInstall class in GuzabaPlatform... this plugin will handle only component installations
        $installer_dir = __DIR__;
        $composer_json_dir = realpath($installer_dir.'/../../../../');//this is the root dir
        $guzaba_platform_dir = $this->getInstallPath($Package);
        if (file_exists($composer_json_dir.'/app')) {
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
            //`cp -r $guzaba_platform_dir/app/public_src $composer_json_dir/app/public_src`;//will be no longer copied...
            `mkdir $composer_json_dir/app/public_src`;
            `mkdir $composer_json_dir/app/public_src/build`;
            //in app/public_src there will be custom namespaces for the project
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
        $manifest_content->version = $Package->getVersion();
        $manifest_content->installed_time = time();
        $manifest_content->components = [];
        file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));

        $this->install_guzaba_platform_component($Repo, $Package);

    }

    private function install_guzaba_platform_component(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {

        $plugin_dir = $this->getInstallPath($Package);

        $package_name = $Package->getName();
        print sprintf('GuzabaPlatformInstaller: installing component %s', $package_name).PHP_EOL;

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
        if ($namespace[count($namespace)-1] === '\\') {
            $namespace = substr($namespace, 0, -1);
        }
        //TODO add suppport for multiple namespaces;

        $installer_dir = __DIR__;
        $composer_json_dir = realpath($installer_dir.'/../../../../');//this is the root dir

        //check is there post installation hook in the component being installed
        $post_install_class = $namespace.'PostInstall';
        if (class_exists($post_install_class) && is_a($post_install_class, PostInstallHookInterface::class, TRUE)) {
            $post_install_class::post_install_hook($this, $Repo, $Package);
        }

        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (file_exists($manifest_json_file)) {
            $manifest_content = json_decode(file_get_contents($manifest_json_file));
        } else {
            $manifest_content = [];
        }
        if (!isset($manifest_content->components)) {
            $manifest_content->components = [];
        }
        $component = new \stdClass();
        $component->name = $package_name;
        $component->namespace = $namespace;
        $component->root_dir = $plugin_dir;
        $component->src_dir = $plugin_dir.'/app/src';
        $component->public_src_dir = $plugin_dir.'/app/public_src';
        $component->installed_time = time();
        $manifest_content->components[] = $component;

        //TODO make a copy before the file is overwritten

        file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));

        //update the webpack.config.js
        $webpack_components_config_js_file =  $composer_json_dir.'/app/public_src/build/webpack.components.config.js';
        if (file_exists($webpack_components_config_js_file)) {
            $webpack_content = file_get_contents($webpack_components_config_js_file);
        } else {
            //"@site": path.resolve(__dirname, 'src/SomeNs')
            $webpack_content = <<<WEBPACK
const path = require('path')
exports.aliases = {
    vue$: 'vue/dist/vue.esm.js',
    "@": path.resolve(__dirname, 'src'),
}
WEBPACK;

        }
        preg_match('/exports.aliases = {(.*)}/iUms', $webpack_content, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(sprintf('The file %s does not contain an "expprt.aliases = {}" section.', $webpack_components_config_js_file));
        }
        $aliases = $matches[1];
        //GuzabaPlatform\Tags
        $aliases .= "\"@$namespace\": path.resolve(__dirname, '$plugin_dir/app/public_src')".PHP_EOL;
        $aliases_replacement_section = 'exports.aliases = {'.$aliases.'}';
        $webpack_content = preg_replace('/exports.aliases = {(.*)}/iUms', $aliases_replacement_section, $webpack_content);
        file_put_contents($webpack_components_config_js_file, $webpack_content);
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