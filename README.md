debian-composer-plugin
======================

A simple tool for installing language extensions with composer for HHVM and PHP on debian based systems.

============================
Installation / Usage (for HHVM)
============================

1. Install the `hhvm-nightly` and `hhvm-dev-nightly` packages (some important features and bug fixes are not in the current release). You will also need [`Composer`](https://getcomposer.org/) itself. Composer can be installed from the [`composer.phar`](https://getcomposer.org/composer.phar) executable or the installer. 

    ``` sh
    $ curl -sS https://getcomposer.org/installer | php
    ```

2. Make a `composer.json` for your project. See [`this example`](https://github.com/kmiller68/test-package/blob/master/composer.json) for an example. Note that this has not gone on packagist so we need to use VCS until then.
3. Run `hhvm composer.phar install` and follow the instructions. The plugin will build all the extensions you need and will place an ini file containing a list of all the extensions in `vendor/ext/extensions.ini`
4. Finally, you can now run `hhvm -c <path-to-project>/vendor/ext/extensions.ini <path-to-project>/<your-main-file>.php` to run your project.
