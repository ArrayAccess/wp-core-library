# Contributing

Contributions are welcome from everyone and will be fully credited to the respective contributor.

The contributions are not limited to code only; you can also contribute to the documentation, bug report, feature request, etc.

## Guidelines

- WordPress should be installed in the `root` project directory.
- Place this repository under your (custom) plugin directory.
- Install [Composer](https://getcomposer.org/).
- Run `composer install` to install the dependencies.
- Run `composer phpcs` to check the coding standard.
- Run `composer phpunit` to run the tests.
- Run `composer phpcbf` to fix the coding standard automatically.
- Install Node.js and NPM.
- Run `npm install` to install the dependencies.
- Run `npm build-watch` to build the assets automatically, watch the changes of assets js & css.
- Run `npm watch` to watch the changes of assets js & css.

## Pull Requests

- Even this project for WordPress, the code uses PSR2 as the coding standard. (Refer [Coding Standard](CODING_STANDARD.md))
- Before pushing your code, please make sure the code is passing the test. (Refer [Testing](TESTING.md))
- Always being respectful to others.
- Always be responsible for your code, words, and actions.
- Please being security first, and then performance.
