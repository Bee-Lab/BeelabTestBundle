UPGRADE
=======

From 3.0 to 4.0
---------------

Since Symfony Test class introduced a static `$client` property, we had to move our
own non-static property. So, you need to change every occurrence of `$this->client`
to `static::$client` in your tests.

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

