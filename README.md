# WP-DEV

Castor tasks for local WordPress development. WordPress commands use the `wp`
executable on PATH.

## Setup

On macOS, install WP-CLI with Homebrew (PHP is installed as its dependency):

```sh
brew install wp-cli
wp --info
composer install
```

PHP CS Fixer, PHPStan, PHPUnit, and composer-bin-plugin are Composer development
dependencies. Box is managed locally in the plugin's `vendor-bin/box/` namespace.
Install Castor 1.8.1 separately. Run `castor list` once to generate
`.castor.stub.php` for IDE completion and PHPStan; the stub is ignored by Git.
The project requires PHP 8.5. WP-CLI is installed separately from this project.

## WordPress tasks

```sh
castor wp-download --path=/path/to/wordpress
castor wp-config-backup --path=/path/to/wordpress
castor wp-config-create --path=/path/to/wordpress
castor wp-config-create-force --path=/path/to/wordpress
castor wp-db-create --path=/path/to/wordpress
castor wp-db-replace-url --path=/path/to/wordpress
castor wp-db-replace-to-https --path=/path/to/wordpress
castor wp-db-replace-to-http --path=/path/to/wordpress
```

During source development, Castor uses this repository as its working directory,
so pass the WordPress path explicitly. In the compiled app, `--path` defaults to
its invocation directory.

Export `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST`, and `DB_PREFIX` before creating
configuration. `DB_PASS` may be empty and is sent through stdin. Normal creation
preserves an existing configuration; the `-force` task overwrites it. WP-CLI
checks the database connection during creation.

The backup task requires both `wp-config.php` and `.htaccess`. It copies them to
`backup/wp-config.backup.php` and `backup/.htaccess`, overwriting earlier backups.

Export `PROD_HOST` and `DEV_HOST` for URL replacement. Scheme conversion matches
full `http://` and `https://` prefixes. Replacement tasks write changes immediately.
Use the local CLI directly when additional options are needed:

```sh
wp --path=/path/to/wordpress search-replace old.example new.example --dry-run
```

Database operations require a database server. `wp db create` also needs a local
MySQL/MariaDB CLI client. All tools run locally, without a container wrapper.

## Development

```text
castor.php          # Development loader
src/castor.php      # WP-DEV application tasks
src/_context.php    # Default context and WP-CLI executable lookup
src/_helpers.php    # Shared command execution helper
src/Config.php      # Immutable application environment configuration
build/castor.php    # Packaging and compilation tasks
build/quality.php   # Formatting, analysis, and tests
```

```sh
castor lint:fix
castor lint:check
castor phpstan:check
```

Tool caches and temporary build files stay in `tmp/`.

The default context resolves `wp` from PATH and stores its location in context
data. The command helper reads it with Castor's `variable('wp')`. Context creation
fails immediately with an installation hint if the executable is missing.
It also loads `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST`, `DB_PREFIX`, `PROD_HOST`,
and `DEV_HOST` into a `WPDev\Config` object, available through `variable('config')`.
Required values are validated when used by a task. Passwords are redacted from
configuration debug output.

## Build and test

The optional native build embeds PHP for Castor. The resulting executable still
requires local WP-CLI and its PHP runtime on PATH.

The local Composer bin plugin installs Box with isolated dependencies and links
`vendor/bin/box`. A normal `composer install` also installs the tool namespace.
To update Box separately, run `composer bin box update`.

```sh
composer install
castor build 0.1.0
castor test
```

On Apple Silicon macOS, outputs are `dist/wp-dev.darwin-arm64` and
`dist/wp-dev.darwin-arm64.phar`. Build separately on each target platform.
Castor uses static-php-cli to check build dependencies and compile PHP; missing
build tools may be installed during its dependency check. On macOS, install
Xcode Command Line Tools first with `xcode-select --install`.

Application files in `src/` become the packaged application root. Production
Composer dependencies are installed in staging, preserving local dev dependencies.
WP-CLI source code and build tooling are excluded from the executable.

PHPUnit checks command forwarding with a temporary local `wp` fixture, plus real
configuration generation when WP-CLI is installed. No database is required.
Set `WP_DEV_BINARY` to test a different compiled executable. PHPUnit caches stay
in `tmp/phpunit/`.
