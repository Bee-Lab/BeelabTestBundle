UPGRADE
=======

From 5.4 to 6.0
---------------

The `assertMailSent` method has been removed, since Swiftmailer is not supported anymore. You should use the
Symfony Mailer component instead, and the `assertEmailCount` method.
New minimum requirements are PHP 8.1 and Symfony 6.4.

The `AbstractContainerAwareFixture` was removed. Inject your dependencies directly instead of the whole container.

From 4.0 to 5.0
---------------

A bunch of methods are now static. This is affecting you only if you're overriding such methods, since you
need to declare as static your methods too. Calling such method can be left untouched (e.g. you can still call
methods using `$this->` instead of `self::`).
Also, `$em` proteced property is now static. If you were using `$this->em`, you need to use `self::$em` instead.

From 3.0 to 4.0
---------------

Since Symfony Test class introduced a static `$client` property, we had to move our
own non-static property. So, you need to change every occurrence of `$this->client`
to `self::$client` (or `static::$client`) in your tests.

You cannot pass `$crawler` as first parameter of method `getFormValue` anymore.
Such use of parameter was deprecated in version 3, so it has been removed now.

Method `ajax` is now static. Instead of `$this->ajax(...)`, you need to use `self::ajax(...)`

From 2.x to 3.0
---------------

Since Symfony Test class introduced a static `$container` property, we had to remove our
own non-static property. So, if you were using `$this->container` in your tests, you need
to change it in `static::$container`.

Also, methods previously deprecated were removed:

* `$this->getContainer()` (use `static::$container` instead)
* `$this->getClient()` (use `$this->client` instead)

From 1.x to 2.0
---------------

If you were using the static proprerty `$admin` (with password taken from parameter `admin_password`)
for basic auth, this is not working anymore.

Instead, you need to define two static properties: `$authUser` and `$authPw`, containing respectively
username and password. In this way, you'll be able to use different users in different test classes.

