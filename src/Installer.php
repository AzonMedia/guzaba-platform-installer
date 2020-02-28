<?php
declare(strict_types=1);

namespace GuzabaPlatform\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use GuzabaPlatform\Installer\Interfaces\PostInstallHookInterface;
use GuzabaPlatform\Installer\Interfaces\PostUninstallHookInterface;
use GuzabaPlatform\Installer\Interfaces\PostUpdateHookInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class Installer
 * @package GuzabaPlatform\Installer
 * @see https://getcomposer.org/doc/articles/custom-installers.md
 * Custom installer for GuzabaPlatform and GuzabaPlatform Packages
 */
class Installer extends LibraryInstaller
{
    protected const PACKAGE_TYPE_PLATFORM = 'guzaba-platform';
    protected const PACKAGE_TYPE_COMPONENT = 'guzaba-platform-component';

    /**
     * This is the composer.type that is supported by this plugin
     */
    protected const SUPPORTED_TYPES = [self::PACKAGE_TYPE_PLATFORM, self::PACKAGE_TYPE_COMPONENT];

    protected const JSON_ENCODE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;


    public function install(InstalledRepositoryInterface $Repo, PackageInterface $Package)
    {

        parent::install($Repo, $Package);

        $package_type = $Package->getType();

        try {
            if ($package_type === self::PACKAGE_TYPE_PLATFORM) {
                $this->install_guzaba_platform($Repo, $Package);
            } elseif ($package_type === self::PACKAGE_TYPE_COMPONENT) {
                $this->install_guzaba_platform_component($Repo, $Package);
            } else {
                throw new \RuntimeException(sprintf('An unsupported package type %s is provided.', $package_type));
            }
        } catch (\Throwable $Exception) {
            print get_class($Exception).': '.$Exception->getMessage().' in '.$Exception->getFile().'#'.$Exception->getLine();
            throw $Exception;
        }

    }

    /**
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     * @throws \Exception
     */
    public function uninstall(InstalledRepositoryInterface $Repo, PackageInterface $Package)
    {
        parent::uninstall($Repo, $Package);

        try {
            $package_name = $Package->getName();
            $composer_json_dir = $this->getComposerJsonDir();
            $Component = $this->createComponentByPackage($Package);
            $this->update_manifest_on_uninstall($composer_json_dir, $Component);
            $this->update_webpack_config_on_uninstall($composer_json_dir, $Component);
            $this->execute_post_uninstall_hook($Component, $Repo, $Package);
        } catch (\Throwable $Exception) {
            print get_class($Exception).': '.$Exception->getMessage().' in '.$Exception->getFile().'#'.$Exception->getLine();
            throw $Exception;
        }

    }

    /**
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $InitialPackage
     * @param PackageInterface $TargetPackage
     * @throws \Exception
     */
    public function update(InstalledRepositoryInterface $Repo, PackageInterface $InitialPackage, PackageInterface $TargetPackage)
    {
        try {
            parent::update($Repo, $InitialPackage, $TargetPackage);
            $composer_json_dir = $this->getComposerJsonDir();
            $Component = $this->createComponentByPackage($TargetPackage);
            $this->update_manifest_on_update($composer_json_dir, $Component);
            $this->update_webpack_config_on_update($composer_json_dir, $Component);
            $this->execute_post_update_hook($Component, $Repo, $InitialPackage, $TargetPackage);
            //update the timestamp in the manifest.json of the last time the package was updated
        } catch (\Throwable $Exception) {
            print get_class($Exception).': '.$Exception->getMessage().' in '.$Exception->getFile().'#'.$Exception->getLine();
            throw $Exception;
        }

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
            //throw new \RuntimeException(sprintf('The directory %s already exists.', $composer_json_dir.'/app'));
        } else {
            `mkdir $composer_json_dir/app`;
        }

        if (!file_exists($composer_json_dir.'/app/src')) {
            `mkdir $composer_json_dir/app/src`;
        }

        if (!file_exists($composer_json_dir.'/app/src/translations')) {
            `mkdir $composer_json_dir/app/src/translations`;
        }
        if (!file_exists($composer_json_dir.'/app/uploads_temp_dir')) {
            `cp -r $guzaba_platform_dir/app/uploads_temp_dir $composer_json_dir/app/uploads_temp_dir`;
        }

        if (!file_exists($composer_json_dir.'/app/bin')) {
            //`cp -r $guzaba_platform_dir/app/bin $composer_json_dir/app/bin`;
            //`ln -s $guzaba_platform_dir/app/bin $composer_json_dir/app/bin`;//instead of copying make a symlink
            //symlink does not work when the application is started in the containers with ./start_server_in_container
            `cp -r $guzaba_platform_dir/app/bin $composer_json_dir/app/bin`;
        }
        if (!file_exists($composer_json_dir.'/app/certificates')) {
            `cp -r $guzaba_platform_dir/app/certificates $composer_json_dir/app/certificates`;//this is a copy as there will be more files added
            //`ln -s $guzaba_platform_dir/app/certificates $composer_json_dir/app/certificates`;
        }
        if (!file_exists($composer_json_dir.'/app/dockerfiles')) {
            //`cp -r $guzaba_platform_dir/app/dockerfiles $composer_json_dir/app/dockerfiles`;
            //`ln -s $guzaba_platform_dir/app/dockerfiles $composer_json_dir/app/dockerfiles`;
            //lets not have any symlinks...
            `cp -r $guzaba_platform_dir/app/dockerfiles $composer_json_dir/app/dockerfiles`;
        }
        if (!file_exists($composer_json_dir.'/app/logs')) {
            `cp -r $guzaba_platform_dir/app/logs $composer_json_dir/app/logs`;
        } else {
            //make sure this is a dir
            if (!is_dir($composer_json_dir.'/app/logs')) {
                //if it exists and is not a directory this is a critical event - no logs will be written
                throw new \RuntimeException(sprintf('There already exists a file named %s - this must be a directory.', $composer_json_dir.'/app/logs'));
            }
        }

        if (!file_exists($composer_json_dir.'/app/public')) {
            `cp -r $guzaba_platform_dir/app/public $composer_json_dir/app/public`;
        }
        if (!file_exists($composer_json_dir.'/app/registry')) {
            `cp -r $guzaba_platform_dir/app/registry $composer_json_dir/app/registry`;
        }
        if (!file_exists($composer_json_dir.'/app/startup_generated')) {
            `cp -r $guzaba_platform_dir/app/startup_generated $composer_json_dir/app/startup_generated`;
        }
        if (!file_exists($composer_json_dir.'/app/public_src')) {
            `cp -r $guzaba_platform_dir/app/public_src $composer_json_dir/app/public_src`;
            //`mkdir $composer_json_dir/app/public_src`;
            //TODO improve - copy only the needed files instead of removing the unneded ones
            `rm -rf $composer_json_dir.'/app/public_src/assets`;
            `rm -rf $composer_json_dir.'/app/public_src/components`;
            //`rm -rf $composer_json_dir.'/app/public_src/docs`;//leave the docs...
            `rm -rf $composer_json_dir.'/app/public_src/views`;
        }
        if (!file_exists($composer_json_dir.'/app/public_src/components_config')) {
            `mkdir $composer_json_dir/app/public_src/components_config`;
        }


        //in app/public_src there will be custom namespaces for the project
        //`mkdir $composer_json_dir/app/public_src`;
        //`mkdir $composer_json_dir/app/public_src/build`;
        //`ln -s $guzaba_platform_dir/app/public_src $composer_json_dir/public_src/$namespace`;

        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (!file_exists($manifest_json_file)) {
            //throw new \RuntimeException(sprintf('The file %s already exists.', $manifest_json_file));

            $manifest_content = new \stdClass();
            $manifest_content->name = 'GuzabaPlatform';
            $manifest_content->url = 'https://platform.guzaba.org/';
            $manifest_content->version = $Package->getVersion();
            $manifest_content->installed_time = time();
            $manifest_content->components = [];
            file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));
        }

        $this->install_guzaba_platform_component($Repo, $Package);
    }

    private function install_guzaba_platform_component(InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {
        $Component = $this->createComponentByPackage($Package);
        $composer_json_dir = $this->getComposerJsonDir();
        $this->update_manifest_on_install($composer_json_dir, $Component);
        $this->update_webpack_config_on_install($composer_json_dir, $Component);
        $this->execute_post_install_hook($Component, $Repo,$Package);
    }

    /**
     * If the file $plugin_src_dir./PostInstall.php exists and is a PostInstallHookInterface
     * @param \stdClass $Component
     * @param InstalledRepositoryInterface $Repo
     * @param PackageInterface $Package
     */
    private function execute_post_install_hook(\stdClass $Component, InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {
        //check for post install hook in the newly installed package
        //at this stage the /vendor/autoload.php is not yet regenerated thus the normal autoload will not load this class and it has to be manually required if exists

        //check is there post installation hook in the component being installed
        $post_install_class = $Component->namespace.'\\PostInstall';
        $post_install_class_file = $Component->src_dir.'/PostInstall.php';
        if (file_exists($post_install_class_file)) {
            require_once($post_install_class_file);
        }

        if (class_exists($post_install_class) && is_a($post_install_class, PostInstallHookInterface::class, TRUE)) {
            $post_install_class::post_install_hook($this, $Repo, $Package);
        }
    }

    private function update_manifest_on_install(string $composer_json_dir, \stdClass $Component) : void
    {
        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (file_exists($manifest_json_file)) {
            $manifest_content = json_decode(file_get_contents($manifest_json_file));
        } else {
            $manifest_content = [];
        }
        if (!isset($manifest_content->components)) {
            $manifest_content->components = [];
        }

        $manifest_content->components[] = $Component;

        //TODO make a copy before the file is overwritten

        file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));

    }

    /**
     * @param string $composer_json_dir Contains the path to the composer.json file - this is the root directory of the project.
     * @param \stdClass $Component
     */
    private function update_webpack_config_on_install(string $composer_json_dir, \stdClass $Component) : void
    {

        $namespace = $Component->namespace;
        $plugin_public_src_dir = $Component->public_src_dir;
        $webpack_components_config_js_file =  $composer_json_dir.'/app/public_src/components_config/webpack.components.config.js';

        $vendor_dir = $composer_json_dir.'/vendor';
        if (file_exists($webpack_components_config_js_file)) {
            $webpack_content = file_get_contents($webpack_components_config_js_file);
        } else {
            //"@site": path.resolve(__dirname, 'src/SomeNs')
            $webpack_content = <<<WEBPACK
const path = require('path')
exports.aliases = {
    vue$: 'vue/dist/vue.esm.js',
    "@": path.resolve(__dirname, '../src'),
    "@VENDOR": '{$vendor_dir}',
}
WEBPACK;

        }



        preg_match('/exports.aliases = {(.*)}/iUms', $webpack_content, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(sprintf('The file %s does not contain an "expprt.aliases = {}" section.', $webpack_components_config_js_file));
        }
        $aliases = $matches[1];
        //GuzabaPlatform\Tags
        $namespace = str_replace('\\','.', $namespace);

        $aliases .= "\"@$namespace\": path.resolve(__dirname, '$plugin_public_src_dir/src'),".PHP_EOL;
        $aliases_replacement_section = 'exports.aliases = {'.$aliases.'}';
        $webpack_content = preg_replace('/exports.aliases = {(.*)}/iUms', $aliases_replacement_section, $webpack_content);

        //TODO make a backup copy

        file_put_contents($webpack_components_config_js_file, $webpack_content);
    }

    private function execute_post_uninstall_hook(\stdClass $Component, InstalledRepositoryInterface $Repo, PackageInterface $Package) : void
    {
        //check is there post uninstall hook in the component being installed
        $post_install_class = $Component->namespace.'\\PostUninstall';
        $post_install_class_file = $Component->src_dir.'/PostUninstall.php';
        if (file_exists($post_install_class_file)) {
            require_once($post_install_class_file);
        }

        if (class_exists($post_install_class) && is_a($post_install_class, PostUninstallHookInterface::class, TRUE)) {
            $post_install_class::post_uninstall_hook($this, $Repo, $Package);
        }
    }

    private function update_manifest_on_uninstall(string $composer_json_dir, \stdClass $Component) : void
    {
        $component_name = $Component->name;
        $manifest_json_file = $composer_json_dir.'/manifest.json';
        if (file_exists($manifest_json_file)) {
            $manifest_content = json_decode(file_get_contents($manifest_json_file));
        } else {
            $manifest_content = [];
        }

        $component_removed = false;
        $components_copy = $manifest_content->components;
        foreach ($components_copy as $key => $ManifestComponent) {
            if ($ManifestComponent->name === $component_name) {
                unset($components_copy[$key]);
                $component_removed = true;
                break;
            }
        }
        $manifest_content->components = array_values($components_copy);//ensure this remains an indexed array

        if (!$component_removed) {
            throw new \Exception(sprintf('Component "%s" isn\'t present in manifest json', $component_name));
        }

        if (!isset($manifest_content->components)) {
            $manifest_content->components = [];
        }

        file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));
    }

    private function update_webpack_config_on_uninstall(string $composer_json_dir, \stdClass $Component) : void
    {
        $namespace = $Component->namespace;
        $namespace = str_replace('\\','.', $namespace);
        $plugin_public_src_dir = $Component->public_src_dir;
        $webpack_components_config_js_file =  $composer_json_dir.'/app/public_src/components_config/webpack.components.config.js';
        if (!file_exists($webpack_components_config_js_file)) {
            throw new \RuntimeException(sprintf('Webpack file "%s" does not exist', $webpack_components_config_js_file));
        }
        $webpack_content = file_get_contents($webpack_components_config_js_file);

        preg_match('/exports.aliases = {(.*)}/iUms', $webpack_content, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(sprintf('The file %s does not contain an "expprt.aliases = {}" section.', $webpack_components_config_js_file));
        }
        $aliasesStr = $matches[1];
        $aliases = array_filter(explode(',' . PHP_EOL, $aliasesStr));
        $component_removed = false;
        foreach ($aliases as $key => $alias) {
            if (strpos(trim($alias), "\"@$namespace\"") === 0) {
                unset($aliases[$key]);
                $component_removed = true;
                break;
            }
        }

        if (!$component_removed) {
            throw new \Exception(sprintf('Component "%s" isn\'t present in webpack json', $namespace));
        }

        $aliasesStr = implode(',' . PHP_EOL, $aliases) . ',' . PHP_EOL;
        $aliases_replacement_section = 'exports.aliases = {'.$aliasesStr.'}';
        $webpack_content = preg_replace('/exports.aliases = {(.*)}/iUms', $aliases_replacement_section, $webpack_content);

        file_put_contents($webpack_components_config_js_file, $webpack_content);
    }

    private function execute_post_update_hook(\stdClass $Component, InstalledRepositoryInterface $Repo, PackageInterface $InitialPackage, PackageInterface $TargetPackage) : void
    {
        //check is there post uninstall hook in the component being installed
        $post_install_class = $Component->namespace.'\\PostUpdate';
        $post_install_class_file = $Component->src_dir.'/PostUpdate.php';
        if (file_exists($post_install_class_file)) {
            require_once($post_install_class_file);
        }

        if (class_exists($post_install_class) && is_a($post_install_class, PostUpdateHookInterface::class, TRUE)) {
            $post_install_class::post_update_hook($this, $Repo, $InitialPackage, $TargetPackage);
        }
    }

    /**
     * @param string $composer_json_dir
     * @param \stdClass $Component
     * @throws \Exception
     */
    private function update_manifest_on_update(string $composer_json_dir, \stdClass $Component) : void
    {
        $manifest_json_file = $composer_json_dir . '/manifest.json';
        if (!file_exists($manifest_json_file)) {
            throw new \Exception(sprintf('Manifest file "%s" does not exist', $manifest_json_file));
        }

        $manifest_content = json_decode(file_get_contents($manifest_json_file));
        $component_updated = false;
        foreach ($manifest_content->components as $key => $ManifestComponent) {
            if ($ManifestComponent->name == $Component->name) {
                $manifest_content->components[$key] = $Component;
                $component_updated = true;
                break;
            }
        }

        if (!$component_updated) {
            throw new \Exception(sprintf('Component "%s" missing in manifest json. Cannot be updated', $Component->name));
        }

        file_put_contents($manifest_json_file, json_encode($manifest_content, self::JSON_ENCODE_FLAGS ));
    }

    private function update_webpack_config_on_update(string $composer_json_dir, \stdClass $Component) : void
    {
        $namespace = $Component->namespace;
        $namespace = str_replace('\\','.', $namespace);
        $plugin_public_src_dir = $Component->public_src_dir;
        $webpack_components_config_js_file =  $composer_json_dir.'/app/public_src/components_config/webpack.components.config.js';
        if (!file_exists($webpack_components_config_js_file)) {
            throw new \RuntimeException(sprintf('Webpack file "%s" does not exist', $webpack_components_config_js_file));
        }
        $webpack_content = file_get_contents($webpack_components_config_js_file);

        preg_match('/exports.aliases = {(.*)}/iUms', $webpack_content, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(sprintf('The file %s does not contain an "expprt.aliases = {}" section.', $webpack_components_config_js_file));
        }
        $aliasesStr = $matches[1];
        $aliases = array_filter(explode(',' . PHP_EOL, $aliasesStr));
        $component_updated = false;
        foreach ($aliases as $key => $alias) {
            if (strpos(trim($alias), "\"@$namespace\"") === 0) {
                $aliases[$key] = "\"@$namespace\": path.resolve(__dirname, '$plugin_public_src_dir')";
                $component_updated = true;
                break;
            }
        }

        if (!$component_updated) {
            throw new \Exception(sprintf('Component "%s" is missing in webpack json. Cannot be updated', $namespace));
        }

        $aliasesStr = implode(',' . PHP_EOL, $aliases) . ',' . PHP_EOL;
        $aliases_replacement_section = 'exports.aliases = {'.$aliasesStr.'}';
        $webpack_content = preg_replace('/exports.aliases = {(.*)}/iUms', $aliases_replacement_section, $webpack_content);

        file_put_contents($webpack_components_config_js_file, $webpack_content);
    }

    /**
     * @param PackageInterface $Package
     * @return \stdClass
     */
    private function createComponentByPackage(PackageInterface $Package): \stdClass
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

        $namespace = self::array_key_first($autoload['psr-4']);
        $plugin_src_dir = $plugin_dir.'/'.$autoload['psr-4'][$namespace];
        if ($namespace[strlen($namespace)-1] === '\\') {
            $namespace = substr($namespace, 0, -1);
        }

        $plugin_public_src_dir = realpath( str_replace( str_replace('\\','/', $namespace), '', $plugin_src_dir).'/../public_src');
        //$plugin_public_src_dir = realpath($plugin_src_dir.'/../public_src');//this is incorrect as some plugins may have deeper path containing the namespace
        //TODO add suppport for multiple namespaces;

        $Component = new \stdClass();
        $Component->name = $package_name;
        $Component->namespace = $namespace;
        $Component->root_dir = $plugin_dir;
        $Component->src_dir = $plugin_src_dir;
        $Component->public_src_dir = $plugin_public_src_dir;
        $Component->installed_time = time();

        return $Component;
    }

    /**
     * @return string
     */
    private function getComposerJsonDir(): string
    {
        $installer_dir = __DIR__;
        //this is the root dir (we know the dir where guzaba-platform-installer is located)
        $composer_json_dir = realpath($installer_dir.'/../../../../');
        return $composer_json_dir;
    }

    /**
     * Gets the first key of an array
     * Polyfill for PHP versions lower that 7.3
     *
     * @param array $arr
     * @return int|string|null
     */
    private static function array_key_first(array $arr)
    {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}