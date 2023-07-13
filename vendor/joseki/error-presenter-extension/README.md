Joseki/ErrorPresenter-extension
=======================

This extension sets dynamically error presenters for Nette Framework applications.
If you have a presenter `Application:Admin:Products` (with an action e.g. `Application:Admin:Products:default`), and your default error presenter name is `DefaultErrorPresenter`, then `ErrorPresenterFactory` will set the closest existing error presenter for given action as follows:

1. Application:Admin:Error
2. Application:Error
3. DefaultError (default)

Installation
------------

The best way to install is using  [Composer](http://getcomposer.org/):

```sh
$ composer require joseki/error-presenter-extension:@stable
```

Usage
-----

```
# config.neon

extensions:
    ErrorPresenter: Joseki\Application\DI\ErrorPresenterExtension

nette:
    application:
        # you can use any nette-like presenter name e.g. Application:Error
        errorPresenter: 'Error'
```
