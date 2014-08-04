<?php

namespace kmiller68;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class DebianInstallerPlugin implements PluginInterface
{
  public function activate(Composer $composer, IOInterface $io)
  {
    $installer = new DebianInstaller($io, $composer);
    $composer->getInstallationManager()->addInstaller($installer);
  }
}
