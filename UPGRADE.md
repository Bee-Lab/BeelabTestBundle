UPGRADE
=======

From 1.x to 2.0
---------------

If you were using the static proprerty `$admin` (with password taken from parameter `admin_password`)
for basic auth, this is not working anymore.

Instead, you need to define two static properties: `$authUser` and `$authPw`, containing respectively
username and password. In this way, you'll be able to use different users in different test classes.

