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
    $bundles = array(
        // ...
        new Beelab\TestBundle\BeelabTestBundle(),
    );
}
```

## Usage

In your functional test class, extend ``Beelab\Test\WebTestCase`` instead of ``Symfony\Bundle\FrameworkBundle\Test\WebTestCase``.

This requires only a change in your ``use`` statements.

Before:

```php
<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase
{
    // ...
}
```

After:

```php
<?php

use Beelab\Test\WebTestCase;

class MyTest extends WebTestCase
{
    // ...
}
```

## Features

* Default client

  You don't need to create a ``$client`` for every single test. You can use ``$this->client`` instead.

* Support for paratest

  Just define your new environments, e.g. ``test1``, ``test2``, etc.

* Support for basic authentication

  If you add a static public class property named ``$admin``, ``$this->client`` will be authenticated with user ``admin``
  and a password took from a parameter named ``admin_password``

* Browser output debug

  You can output the content of response in your browser, just calling ``$this->saveOutput``. You need to define a
  parameter named ``domain``. Default browser is ``/usr/bin/firefox``. You can change browser path by passing it as
  first argument, whiel a ``false`` as second argument will prevent output page deletion (in this case, you can get it
  from ``web`` directory)

* Automatic login

  This is integrated with [BeelabUserBundle](https://github.com/Bee-Lab/BeelabUserBundle)

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

  ``$this->ajax('GET', 'uri') is a convenient shortcut for
  ``$this->client->request($method, $uri, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])``