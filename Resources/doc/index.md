BeelabTestBundle Documentation
==============================

## Installation

Run from terminal:

```bash
$ php composer require --dev beelab/test-bundle:1.0.*
```

Enable bundle in the kernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    // ...
    if (in_array($this->getEnvironment(), ['dev', 'test'])) {
        // ...
        $bundles[] = new Beelab\TestBundle\BeelabTestBundle();
    }
}
```

## Usage

In your functional test class, extend ``Beelab\Test\WebTestCase`` instead of ``Symfony\Bundle\FrameworkBundle\Test\WebTestCase``.

This requires only a change in your ``use`` statements.

With standard Symfony tests:
```php
<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase
{
    // ...
}
```

With this bundle:
```php
<?php

use Beelab\BeelabTest\Test\WebTestCase;

class MyTest extends WebTestCase
{
    // ...
}
```

## Features

* Default client

  You don't need to create a ``$client`` for every single test. You can use ``$this->client`` instead.

* Support for paratest

  Just define your new environments, e.g. ``test1``, ``test2``, etc. See [paratest](https://github.com/brianium/paratest)
  for more info.

* Support for basic authentication

  If you add a static public class property named ``$admin``, ``$this->client`` will be authenticated with user ``admin``
  and a password took from a parameter named ``admin_password``

* Browser output debug

  You can output the content of response in your browser, just calling ``$this->saveOutput``. You need to define a
  parameter named ``domain``. The output will be save under document root and displayed with browser (by default,
  ``/usr/bin/firefox``), then the page will be deleted. You can pass ``false`` as argument to prevent page deletion (in
  this case, you can get it from your document root directory. Don't forget to remove it by hand, then). If you want
  to change browser path, define it in your configuration:
  ```yaml
  # app/config/config_test.yml
  beelab_test:
      browser: /usr/local/bin/chrome
  ```

* Automatic login

  This is integrated with [BeelabUserBundle](https://github.com/Bee-Lab/BeelabUserBundle).

* Files for forms

  Use ``$this->getImageFile()``, ``$this->getPdfFile()``, ``$this->getZipFile()`` to get files for your uploadable fields.
  In forms with more than a field of the same type, use ``$this->getImageFile(1)``, ``$this->getImageFile(2)``, etc.

* Fast fixtures load

  Load fixtures simply with ``$this->loadFixtures(['YourFixtureClassName', 'YourOtherFixtureClassName'])``. Dependencies
  are resolved, so you don't need to explicitly load all your fixtures.

* Mail sent assertion

  Check how many mails has been sent with ``$this->assertMailSent(1)`` (or 2, 3, etc.). You need to call
  ``$this->client->enableProfiler()`` before.

* Fast ajax calls

  ``$this->ajax($method, $uri)`` is a convenient shortcut for
  ``$this->client->request($method, $uri, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])``. Of course, you
  can also pass POST and FILES parameters using 3rd and 4th arguments.

* Test commands

  You can test a command simply executing something like ``$output = $this->commandTest('app:alert', new AlertCommand());``
  and then doing assertion on ``$output``.
