# Croatian Fiscalization

[![](https://img.shields.io/badge/version-0.0.1-blue.svg)](https://shields.io/)
[![](https://img.shields.io/badge/maintained-yes-green.svg)](https://shields.io/)

PHP package for Croatian fiscalization. 


## Install the packages

- `composer install`


## Update required fields in tests

In order for tests to work, head to `tests\CroatianFiscalizationTest` and change the required fields which you will need to have in order for fiscalization to work:

- Test certificate with path and password
- Company UID
- Operative user UID


## Run the tests

- `php vendor/phpunit/phpunit/phpunit`