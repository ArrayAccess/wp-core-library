# Testing

This repository uses PHPUnit for unit testing.
The unit test files are located in the `tests` directory, configured in `phpunit.xml`.


## Preparation

- Install [Composer](https://getcomposer.org/).
- Run `composer install` to install the dependencies.

## Writing Tests

- Create a new test file in the `tests` directory.
- The test file name must be the same as the class name, with the suffix `Test`.
- The test should follow namespace structure.
- The namespace of test is `ArrayAccess\WP\Tests\Libraries\Core`.
- The code also must be passed by php code sniffer. (Refer [Coding Standard](CODING_STANDARD.md))

## Unit Testing

Run the tests: `composer phpunit` to run all tests.
