Burial
======

Bury dead code in a project using [Tombs](https://github.com/krakjoe/tombs)

## Installation:


### Phar

Download the latest Phar from [https://github.com/SolidWorx/Burial/releases](https://github.com/SolidWorx/Burial/releases)

### Composer

Install into a project (or globally) using Composer

```bash
$ composer require solidworx/burial

# or

$ composer global require solidworx/burial
```

## Usage

You should already have [Tombs](https://github.com/krakjoe/tombs) running on an environment and communicating through a socket.

**NOTE:** You should let Tombs run for at lease a couple of days/weeks, to ensure as much production code is hit as possible.

Run Burial against your code base, providing the Tombs socket as first argument

```bash
$ bin/bury http://127.0.0.1:8015

# or

$ php bury.phar http://127.0.0.1:8015
```

This will then remove all the dead code from your project (defaults to the directory where Burial is executed from).

### Options

Burial takes the following parameters

| Name               | Default                   | Description                                                                                              | Example                                                                      |
|:-------------------|:--------------------------|:---------------------------------------------------------------------------------------------------------|:-----------------------------------------------------------------------------|
| --production-path  | Current Working Directory | Set the path of the code on production. This is used to map the production code against your local code. | `$ bin/bury http://127.0.0.1:8015 --production-path=/var/www/html/myapp`     |
| --ignore-dir       | NULL                      | Add multiple directories to ignore (`vendor` is always ignored by default)                               | `$ bin/bury http://127.0.0.1:8015 --ignore-dir=var/cache  --ignore-dir=app`  |

## Important

**DO NOT** run this directly in your production environment. It will remove code that might still be used.
You should only run this on your local machine or a test environment, where you can carefully verify the changes, run unit tests and do proper testing to ensure that nothing is broken.

## TODO

- Ensure a method is not required from a trait/parent class's interface, extended class etc
- Handle calls without a scope (E.G closures)
- Remove dead functions (only method calls are currently supported)
- Add tests
