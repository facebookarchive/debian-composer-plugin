<?php

namespace kmiller68;

use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class ExtensionDirectoryManager {

  protected $filesystem;
  protected $extDir;
  protected $jsonFile;
  protected $json;

  public function __construct(Filesystem $filesystem, $extDir)
  {
      $this->filesystem = $filesystem;
      $this->extDir =  $extDir;
      $this->jsonFile = $extDir.'/packages.json';

      // we get the json here so we do not have to repeatedly get it later
      if (file_exists($this->jsonFile)) {
        $this->json = json_decode(file_get_contents($this->jsonFile));
        //need to put the objects back into arrays
        foreach ((array) $this->json->packages as $pkg => $extensions) {
          $this->json->packages->{$pkg} = get_object_vars($extensions);
        }
      } else {
        $this->json = new \stdClass();
        $this->json->extFiles = new \stdClass();
        $this->json->packages = new \stdClass();
        $this->json->dependencies = new \stdClass();
      }
  }

  public function __destruct()
  {
    $this->createIni();
  }

  public function initializeExtDir()
  {
    $this->filesystem->ensureDirectoryExists($this->extDir);
    $this->extDir = realpath($this->extDir);
  }

  private function createIni()
  {
    $ordering = TopologicalSort::sort(get_object_vars($this->dependencies));
    $content = '';
    foreach ($ordering as $packageName) {
      if (isset($this->extFiles->{$packageName})) {
        foreach ($this->extFiles->{$packageName} as $$file) {
          if(defined('HHVM_VERSION')) {
            $content .= 'extension[] = '.$this->extDir.'/'.$file.'\n';
          } else {
            $content .= 'extension = '.$this->extDir.'/'.$file.'\n';
          }
        }
      }
    }
    file_put_contents($this->extDir.'/extensions.ini', $content);
  }

  private function addDependencies($package)
  {
    $packages = array();
    foreach ($package->getRequires() as $depPackage) {
      $packages[] = $depPackage->getTarget();
    }
    $this->dependencies->{$package->getName()} = $packages;
  }

  public function addExtension(PackageInterface $package, $debPackages, $installPath)
  {
    // copy and mark all the files, first removing all the old ones.
    $this->removeFiles($package);

    $extensions = array();
    if (defined('HHVM_VERSION')) {
      foreach (new \FilesystemIterator($installPath) as $file) {
        if ($file->getExtension() == 'so') {
            $extensions[] = $file->getBasename();
            copy($file, $this->extDir.'/'.$file->getBasename());
        }
      }
    } else {
      $modulesDir = $installPath.'/modules';
      foreach (new \FilesystemIterator($modulesDir) as $file) {
        if ($file->getExtension() == 'so') {
            $extensions[] = $file->getBasename();
        }
        copy($file, $this->extDir.'/'.$file->getBasename());
      }
    }

    $this->json->extFiles->{$package->getName()} = $extensions;
    $this->addDependencies($package);

    return $this->updatePackageList($package, $debPackages);
  }

  public function removeExtension(PackageInterface $package)
  {
    $this->removeFiles($package);
    return $this->updatePackageList($package);
    unset($this->dependencies->{$package->getName()});
  }

  protected function removeFiles(PackageInterface $package)
  {
    if (isset($this->json->extFiles->{$package->getName()})) {
      foreach ($this->json->extFiles->{$package->getName()} as $oldFile) {
        unlink($this->extDir.'/'.$oldFile);
      }
      unset($this->json->extFiles->{$package->getName()});
      $this->createIni();
    }
  }

  /**
   *  Update the json with the new list of needed extensions.
   */
  protected function updatePackageList(PackageInterface $package, $debPackages = array())
  {
    hphpd_break();
    $name = $package->getName();
    $allPackages = array_merge($debPackages, array_keys((array) $this->json->packages));
    $unneeded = array();
    foreach ($allPackages as $pkg) {

      if (!isset($this->json->packages->{$pkg})) {
        $this->json->packages->{$pkg} = array($name => true);
      } else {

        if (in_array($pkg, $debPackages, true)) {
          $this->json->packages->{$pkg}[$name] = true;
        } else {
          unset($this->json->packages->{$pkg}[$name]);
          if (count($this->json->packages->{$pkg}) === 0) {
            $unneeded[] = $pkg;
            unset($this->json->packages->{$pkg});
          }

        }
      }
    }
/*
    $unneeded = array();
    foreach ($debPackages as $debPackage) {
      if (isset($this->json->packages->{$debPackage})) {

      }
    }
    foreach ((array) $this->json->packages as $debianPackage => $extensions) {
      $packageNeeded = in_array($debianPackage, $debPackages);
      $isSet = in_array($package->getName(), $extensions);
      if ($packageNeeded && !$isSet) {
        $extensions[] = $package->getName();
      } elseif (!$packageNeeded && $isSet) {
        unset($extensions[$package->getName()]);
        $unneeded[] = $debianPackage;
      }
    }
*/

    file_put_contents($this->jsonFile, json_encode($this->json, JSON_PRETTY_PRINT));

    return $unneeded;
  }

}
