# Google API Core for PHP

![Build Status](https://github.com/googleapis/gax-php/actions/workflows/tests.yml/badge.svg)

-   [Documentation](https://cloud.google.com/php/docs/reference/gax/latest)

Google API Core for PHP (gax-php) is a set of modules which aids the development
of APIs for clients based on [gRPC][] and Google API conventions.

Application code will rarely need to use most of the classes within this library
directly, but code generated automatically from the API definition files in
[Google APIs][] can use services such as page streaming and retry to provide a
more convenient and idiomatic API surface to callers.

[gRPC]: http://grpc.io
[Google APIs]: https://github.com/googleapis/googleapis/

## PHP Versions

gax-php currently requires PHP 8.1 or higher.

## Contributing

Contributions to this library are always welcome and highly encouraged.

See the [CONTRIBUTING][] documentation for more information on how to get
started.

[CONTRIBUTING]: https://github.com/googleapis/gax-php/blob/main/.github/CONTRIBUTING.md

## Versioning

This library follows [Semantic Versioning][].

This library is considered GA (generally available). As such, it will not
introduce backwards-incompatible changes in any minor or patch releases. We will
address issues and requests with the highest priority.

[Semantic Versioning]: http://semver.org/

## Repository Structure

All code lives under the src/ directory. Handwritten code lives in the
src/ApiCore directory and is contained in the `Google\ApiCore` namespace.

Generated classes for protobuf common types and LongRunning client live under
the src/ directory, in the appropriate directory and namespace.

Code in the metadata/ directory is provided to support generated protobuf
classes, and should not be used directly.

## Development Set-Up

These steps describe the dependencies to install for Linux, and equivalents can
be found for Mac or Windows.

1.  Install dependencies.

    ```sh
    > cd ~/
    > sudo apt-get install php php-dev libcurl3-openssl-dev php-pear php-bcmath php-xml
    > curl -sS https://getcomposer.org/installer | php
    > sudo pecl install protobuf
    ```

2.  Set up this repo.

    ```sh
    > cd /path/to/gax-php
    > composer install
    ```

3.  Run tests.

    ```sh
    > composer test
    ```

4.  Updating dependencies after changing `composer.json`:

    ```sh
    > composer update
    `
    ```

5.  Formatting source:

    ```sh
    > composer cs-lint
    > composer cs-fix
    ```

## License

BSD - See [LICENSE][] for more information.

[LICENSE]: https://github.com/googleapis/gax-php/blob/main/LICENSE
