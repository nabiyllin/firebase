# FIREBASE PUSH NOTIFICATION


INTRODUCTION
------------

Provides a simple way to connect with Google Firebase Notifications.


INSTALLATION
------------

Install as usual, see
 https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
 for more information.


CONFIGURATION
-------------

The module has a simple configuration screen, where you provide Server Key and
Firebase's endpoint.

  * In order to trigger the push:

```php
 $firebase = \Drupal::service('firebase.notification');
 $firebase->send($token, [
   'title' => 'Title goes here',
   'body' => 'Body goes here',
   'data' => [
     'score' => '3x1',
     'date' => '2017-10-10',
   ],
 ]);
```

MAINTAINERS
-----------

Current maintainers:

 * Leonardo Paccanaro - https://www.drupal.org/user/1901878
