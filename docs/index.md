BeelabTestBundle Documentation
==============================

## Installation

Run from terminal:

```bash
$ composer require --dev beelab/test-bundle
```

Bundle should be enabled automatically by Flex.

## Usage

In your functional test class, extend `Beelab\TestBundle\Test\WebTestCase` instead of
`Symfony\Bundle\FrameworkBundle\Test\WebTestCase`.

This requires only a change in your ``use`` statements.

With standard Symfony tests:
```php
<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MyTest extends WebTestCase
{
    // ...
}
```

With this bundle:
```php
<?php

use Beelab\TestBundle\Test\WebTestCase;

final class MyTest extends WebTestCase
{
    // ...
}
```

## Features

### General

* Default client

  You don't need to create a `$client` for every single test.
  You can use `self::$client` instead.

* Support for paratest

  Just define your new environments, e.g. `test1`, `test2`, etc.
  See [paratest](https://github.com/brianium/paratest) for more info.

* Browser output debug

  You can output the content of response in your browser, just calling `self::saveOutput()`.
  You can define a parameter named `domain`, otherwise standard localhost will be used. 
  The output will be save under document root and displayed with browser (by default, `/usr/bin/firefox`),
  then the page will be deleted.
  You can pass `false` as argument to prevent page deletion (in this case, you can get it from your document
  root directory.
  Don't forget to remove it by hand, then).
  If you want to change browser path, define it in your configuration:
  ```yaml
  # config/packages/test/beelab_test.yaml
  beelab_test:
      browser: /usr/local/bin/chrome
  ```

* Fast ajax calls

  `self::ajax($method, $uri)` is a convenient shortcut for
  `self::$client->request($method, $uri, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])`.
  You can also pass POST and FILES parameters using 3rd and 4th arguments.

* Assert presence of many selectors

  `self::assertSelectorCounts()` method works like native `self::assertSelectorExists()`.
  For example, you can check for presence of `div.alert-success` using 
  `self::assertSelectorExists('div.alert-success')` or using `self::assertSelectorCounts(1, 'div.alert-success')`
  (the latter being more specific, and more flexible).

### Authentication-related

* Automatic login

  This is integrated by default with [BeelabUserBundle](https://github.com/Bee-Lab/BeelabUserBundle).
  Anyway, you can use any user provider, passing the name of service as third argument or configuring.
  For example, if you want to login users provided by FOSUserBundle in your tests, you can do something like
  `self::login('myuser', 'main', 'fos_user.user_provider.username');`.
  Another notable service you can use is Symfony's built-in `security.user.provider.concrete.in_memory`.
  For basic usage, just pass the username as first argument.

  Example of configuration:
  ```yaml
  # config/packages/test/beelab_test.yaml
  beelab_test:
      firewall: my_firewall
      service: fos_user.user_provider.username
  ```

* Test login exception

  You can use `setSessionException` static method to test a generic exception in authentication.
  Just call such method and then visit your login page, e.g. `self::$client->request('GET', '/login')`.
 
* Support for basic authentication

  If you need basic authentication in tests, set static public class properties named `$authUser` and `$authPw`.
  Doing so, `self::$client` will be authenticated with such user and password.

### Form-related

* Files for forms

  Use `self::getImageFile()`, `self::getPdfFile()`, `self::getZipFile()`, and `self::getTxtFile()` to get
  files of various types for your uploadable fields.
  In forms with more than a field of the same type, use `self::getImageFile('1')`, `self::getImageFile('2')`, etc.
  You can also use `self::getFile('0', $data, 'png', 'image/png')` and pass directly your file data.

* Form values shortcut

  If you need to retrieve the value of a form field, you can use `self::getFormValue('form_field')`.
  This is useful for retrieving CSRF token values or select values.
  In case of a select, note that you need to add `option` after your field's id, and you can pass a third 
  optional parameter with the position.
  If, for example, you want to retrieve the value of your second option in a `bar` field of a `foo` form
  (maybe beacuse the first one is empty), you can do `self::getFormValue('foo_bar option', 1)`

* Forms with collections
  
  Tipically, a form with a collection is a problem during tests, because the values of collections are not displayed
  in the HTML (but, instead, added via JavaScript).
  You can solve such problema by visiting form URL and then using `self::postForm('your_form_name', $values)`
  (where`$values` can include collection values).

* Selecting checkboxes

  You can check many checkboxes using `self::tickCheckboxes($form, $values)`, using for values the same
  array you would use for a select multiple. This allows you to easily switch bewteen `true` and `false`
  in `multiple` option of `ChoiceType`.

### Fixtures-related

* Fast fixtures load

  Load fixtures simply with `$this->loadFixtures(['YourFixtureClassName', 'YourOtherFixtureClassName'])`.
  Dependencies are resolved (as long as you implement `DependentFixtureInterface`), so you don't need to explicitly
  load all your fixtures.

* AbstractContainerAwareFixture

  When you need the service container in your fixtures, instead of implementing
  `Symfony\Component\DependencyInjection\ContainerAwareInterface`, you can extend
  `Beelab\TestBundle\DataFixtures\AbstractContainerAwareFixture`.

* Get entity by reference

  If you need an entity in a functional test, you can get it by calling `$this->getReference('refname')`, where
  `refname` is a name of a reference you used inside a fixture.
  E.g., if you used `$this->addReference('foo')` or `$this->setReference('foo')` inside one of your fixtures, you
  can call `$this->getReference('foo')` to retrieve the entity you created in that fixture.
  ⚠️️ **Warning**: this method is working only if you are loading fixtures during test execution.

### Selection-related

* Click link by data

  You add a `data` attribute to your link (e.g. `<a href="#" data-edit>edit entity</a>`), you can call link
  using that attribute instead of link text (in the same example, use `self::clickLinkByData('edit'))`).
  This is mostly useful in multi-language applications or when your labels change frequently.

* Submit form by data

  Similar to previous, but for buttons (e.g. `<button type="submit" data-submit>submit this form</button>`).
  Use `self::submitFormByData('submit', $values)`.
  You can also pass an array of checkboxes (`tickCheckboxes` method is used internally) as additional argument,
  for example:
  `self::submitFormByData('submit', $values, [], 'POST', [], ['foo[bar]' => ['value1', 'value2', 'value3']])`

### E-mail related

* Mail sent assertion

  Check how many mails has been sent with `self::assertMailSent(1)` (or 2, 3, etc.).
  You need to call `$self::client->enableProfiler()` before.
  Currently, this is working only with SwiftMailer.

### Command related

* Test commands

  You can test a command by executing something like `$output = self::commandTest('app:alert', new AlertCommand());`
  and then doing assertions on `$output`. You can pass an array of arguments and options as third argument
  (remember that options need to be prefixed with a double dash).
