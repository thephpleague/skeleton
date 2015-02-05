<?php

namespace Exo;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class Plugin implements PluginInterface
{
    /**
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $path = $this->getDefaultPath();

        $config = $composer->getConfig();

        if ($config) {
            $path = $this->getConfigPath($config);
        }

        $filesystem = $this->getFilesystem($path);

        $name = trim($this->getName());
        $name = $io->ask("Project Owner [{$name}]: ", $name);

        $installEverything = $io->askConfirmation("Install everything [no]: ", false);

        $srcPath = "src";
        $testsPath = "tests";
        $coveragePath = "coverage";
        $phpunitPath = "phpunit.xml";
        $phpcsPath = "vendor/bin/phpcs";
        $phpcsParameters = "--standard=PSR2";
        $scrutinizerPath = ".scrutinizer.xml";
        $editorConfigPath = ".editorconfig";
        $gitIgnorePath = ".gitignore";
        $licensePath = "license.md";

        $package = $composer->getPackage();

        if ($package) {
            $extra = $extra = $package->getExtra();

            if ($extra) {
                $src = $this->getExtraPath($extra, "src");

                if ($src) {
                    $srcPath = $src;
                }

                $tests = $this->getExtraPath($extra, "tests");

                if ($tests) {
                    $testsPath = $tests;
                }

                $phpcs = $this->getExtraPath($extra, "phpcs");

                if ($phpcs) {
                    $phpcsPath = $phpcs;
                }

                $phpcs = $this->getExtraParameter($extra, "phpcs");

                if ($phpcs) {
                    $phpcsParameters = $phpcs;
                }

                $coverage = $this->getExtraPath($extra, "coverage");

                if ($coverage) {
                    $coveragePath = $coverage;
                }

                $phpunit = $this->getExtraPath($extra, "phpunit");

                if ($phpunit) {
                    $phpunitPath = $phpunit;
                }

                $scrutinizer = $this->getExtraPath($extra, "scrutinizer");

                if ($scrutinizer) {
                    $scrutinizerPath = $scrutinizer;
                }

                $editorConfig = $this->getExtraPath($extra, "editorconfig");

                if ($editorConfig) {
                    $editorConfigPath = $editorConfig;
                }

                $gitIgnore = $this->getExtraPath($extra, "gitignore");

                if ($gitIgnore) {
                    $gitIgnorePath = $gitIgnore;
                }

                $license = $this->getExtraPath($extra, "license");

                if ($license) {
                    $licensePath = $license;
                }
            }
        }

        $this->copyTests($filesystem, $io, $installEverything, $testsPath, $phpunitPath, "__DIR__ . \"/../{$phpcsPath} \" . __DIR__ . \"/../{$srcPath} {$phpcsParameters}\"");

        $this->copyScrutinizer($filesystem, $io, $installEverything, $srcPath, $testsPath, $scrutinizerPath);

        $this->copyEditorConfig($filesystem, $io, $installEverything, $editorConfigPath);

        $this->copyGitIgnore($filesystem, $io, $installEverything, $coveragePath, $gitIgnorePath);

        $this->copyLicense($filesystem, $io, $installEverything, $name, $licensePath);
    }

    /**
     * @return string
     */
    protected function getDefaultPath()
    {
        return realpath(__DIR__."/../../../..");
    }

    /**
     * @param Config $config
     *
     * @return string
     */
    protected function getConfigPath(Config $config)
    {
        return realpath($config->get("vendor-dir")."/..");
    }

    /**
     * @param string $path
     *
     * @return Filesystem
     */
    protected function getFilesystem($path)
    {
        return new Filesystem(new Local($path));
    }

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getName()
    {
        $process = new Process("git config user.name");
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                $process->getErrorOutput()
            );
        }

        return $process->getOutput();
    }

    /**
     * @param array  $extra
     * @param string $key
     *
     * @return null|string
     */
    protected function getExtraPath(array $extra, $key)
    {
        if (!empty($extra["exo"]["paths"][$key])) {
            return $extra["exo"]["paths"][$key];
        }
    }

    /**
     * @param array  $extra
     * @param string $key
     *
     * @return null|string
     */
    protected function getExtraParameter(array $extra, $key)
    {
        if (!empty($extra["exo"]["parameters"][$key])) {
            return $extra["exo"]["parameters"][$key];
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param IOInterface $io
     * @param boolean     $installEverything
     * @param string      $testsPath
     * @param string      $phpunitPath
     * @param string      $command
     */
    protected function copyTests(Filesystem $filesystem, IOInterface $io, $installEverything, $testsPath, $phpunitPath, $command)
    {
        if ($installEverything or $io->askConfirmation("Create PHPUnit config [no]: ", false)) {
            if (!$filesystem->has($phpunitPath)) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/phpunit.xml.template");

                $content = str_replace("{{tests}}", $testsPath, $content);

                $filesystem->write($phpunitPath, $content);
            }
        }

        if ($installEverything or $io->askConfirmation("Create tests [no]: ", false)) {
            if (!$filesystem->has($testsPath)) {
                $filesystem->createDir($testsPath);
            }
        }

        if ($installEverything or $io->askConfirmation("Create style tests [no]: ", false)) {
            if (!$filesystem->has($testsPath."/StyleTest.php")) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/tests/StyleTest.php.template");

                $content = str_replace("{{command}}", $command, $content);

                $filesystem->write($testsPath."/StyleTest.php", $content);
            }
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param IOInterface $io
     * @param boolean     $installEverything
     * @param string      $srcPath
     * @param string      $testsPath
     * @param string      $scrutinizerPath
     */
    protected function copyScrutinizer(Filesystem $filesystem, IOInterface $io, $installEverything, $srcPath, $testsPath, $scrutinizerPath)
    {
        if ($installEverything or $io->askConfirmation("Create Scrutinizer config [no]: ", false)) {
            if (!$filesystem->has($scrutinizerPath)) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/.scrutinizer.yml.template");

                $content = str_replace("{{src}}", $srcPath, $content);
                $content = str_replace("{{tests}}", $testsPath, $content);

                $filesystem->write($scrutinizerPath, $content);
            }
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param IOInterface $io
     * @param boolean     $installEverything
     * @param string      $editorConfigPath
     */
    protected function copyEditorConfig(Filesystem $filesystem, IOInterface $io, $installEverything, $editorConfigPath)
    {
        if ($installEverything or $io->askConfirmation("Create Editor config [no]: ", false)) {
            if (!$filesystem->has($editorConfigPath)) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/.editorconfig.template");

                $filesystem->write($editorConfigPath, $content);
            }
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param IOInterface $io
     * @param boolean     $installEverything
     * @param string      $coveragePath
     * @param string      $gitIgnorePath
     */
    protected function copyGitIgnore(Filesystem $filesystem, IOInterface $io, $installEverything, $coveragePath, $gitIgnorePath)
    {
        if ($installEverything or $io->askConfirmation("Create Git ignore config [no]: ", false)) {
            if (!$filesystem->has($gitIgnorePath)) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/.gitignore.template");

                $content = str_replace("{{coverage}}", $coveragePath, $content);

                $filesystem->write($gitIgnorePath, $content);
            }
        }
    }

    /**
     * @param Filesystem  $filesystem
     * @param IOInterface $io
     * @param boolean     $installEverything
     * @param string      $name
     * @param string      $licensePath
     */
    protected function copyLicense(Filesystem $filesystem, IOInterface $io, $installEverything, $name, $licensePath)
    {
        if ($installEverything or $io->askConfirmation("Create License [no]: ", false)) {
            if (!$filesystem->has($licensePath)) {
                $content = $filesystem->read("vendor/assertchris/exo/templates/license.md.template");

                $content = str_replace("{{name}}", $name, $content);
                $content = str_replace("{{year}}", date("Y"), $content);

                $filesystem->write($licensePath, $content);
            }
        }
    }
}
