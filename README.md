Docker PHP
==========

**Docker PHP** is a [Docker](http://docker.com/) client written in PHP.
This library aim to reach 100% API support of the Docker Engine.

Installation
------------

The recommended way to install Docker PHP is of course to use [Composer](http://getcomposer.org/):

```bash
composer require kustov-vitalik/php-docker
```

Docker API Version
------------------

By default, it will use the last version of docker api available, if you want to fix a version (like 1.41) you can add this 
requirement to composer:

```bash
composer require "kustov-vitalik/php-docker-api:4.1.41.*"
```

Usage
-----

See [the documentation](http://docker-php.readthedocs.org/en/latest/).

License
-------

The MIT License (MIT). Please see [License File](LICENSE) for more information.
