BeelabTestBundle Documentation
==============================

## Installation

Run from terminal:

```bash
$ composer require --dev beelab/test-bundle
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

In your functional test class, extend `Beelab\TestBundle\Test\WebTestCase` instead of
`Symfony\Bundle\FrameworkBundle\Test\WebTestCase`.

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

use Beelab\TestBundle\Test\WebTestCase;

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

  You can output the content of response in your browser, just calling `$this->saveOutput()`. You can define a
  parameter named `domain`, otherwise standard localhost will be used. The output will be save under document root and
  displayed with browser (by default, `/usr/bin/firefox`), then the page will be deleted. You can pass `false` as argument
  to prevent page deletion (in this case, you can get it from your document root directory. Don't forget to remove it by
  hand, then). If you want to change browser path, define it in your configuration:
  ```yaml
  # app/config/config_test.yml
  beelab_test:
      browser: /usr/local/bin/chrome
  ```

* Automatic login

  This is integrated by default with [BeelabUserBundle](https://github.com/Bee-Lab/BeelabUserBundle).
  Anyway, you can use any user provider, passing the name of your service as third argument.
  For example, if you want to login users provided by FOSUserBundle in your tests, you can do something like
  `$this->login('foo', 'main', 'fos_user.user_provider.username');`. For basic usage, just pass the username as first argument.

* Files for forms

  Use ``$this->getImageFile()``, ``$this->getPdfFile()``, ``$this->getZipFile()``, and ``$this->getTxtFile()`` to get
  files of various types for your uploadable fields.
  In forms with more than a field of the same type, use ``$this->getImageFile(1)``, ``$this->getImageFile(2)``, etc.

* Fast fixtures load

  Load fixtures simply with `$this->loadFixtures(['YourFixtureClassName', 'YourOtherFixtureClassName'])`. Dependencies
  are resolved (as long as you implement `DependentFixtureInterface`), so you don't need to explicitly load all your fixtures.

* PHPCR fixtures load

  Load fixtures simply with `$this->loadPhpcrFixtures(['YourFixtureClassName', 'YourOtherFixtureClassName'])`. Dependencies
  are resolved (as long as you implement `DependentFixtureInterface`), so you don't need to explicitly load all your fixtures.

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

* Form values shortcut

  If you need to retrieve the value of a form field, you can use ``$this->getFormValue($crawler, 'form_field')``. This
  is useful for retrieving CSRF token values or select values. In case of a select, note that you need to add ``option``
  after your field's id, and you can pass a third optional parameter with the position. If, for example, you want to
  retrieve the value of your second option in a ``bar`` field of a ``foo`` form (maybe beacuse the first one is empty),
  you can do ``$this->getFormValue($crawler, 'foo_bar option', 1)``

* AbstractContainerAwareFixture

  When you need the service container in your fixtures, instead of implementing
  ``Symfony\Component\DependencyInjection\ContainerAwareInterface``, you can extends
  ``Beelab\TestBundle\DataFixtures\AbstractContainerAwareFixture``.

* Get entity by reference

  If you need an entity in a functional test, you can get it by calling ``$this->getReference('refname')``, where
  ``refname`` is a name of a reference you used inside a fixture. E.g., if you used ``$this->addReference('foo')`` or
  ``$this->setReference('foo')`` inside one of your fixtures, you can call ``$this->getReference('foo')`` to retrieve
  the entity you created in that fixture.
