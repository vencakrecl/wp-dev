<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\io;
use function Castor\variable;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

require_once __DIR__.'/_context.php';
require_once __DIR__.'/_helpers.php';

/** @param string[] $arguments */
#[AsTask(description: 'Run the local WP-CLI executable')]
function wp(#[AsRawTokens] array $arguments): int
{
    return run_wordpress($arguments);
}

#[AsTask(name: 'wp-download', description: 'Download WordPress')]
function wordpress_download(#[AsOption] string $path = '.'): int
{
    return run_wordpress(['core', 'download'], $path);
}

#[AsTask(name: 'wp-config-backup', description: 'Back up wp-config.php and .htaccess')]
function wordpress_config_backup(#[AsOption] string $path = '.'): void
{
    $directory = realpath($path);
    if (false === $directory || !is_dir($directory)) {
        throw new RuntimeException('WordPress directory does not exist: '.$path);
    }
    foreach (['wp-config.php', '.htaccess'] as $file) {
        if (!is_file($directory.'/'.$file)) {
            throw new RuntimeException('Cannot back up missing file: '.$file);
        }
    }
    fs()->mkdir($directory.'/backup');
    fs()->copy($directory.'/wp-config.php', $directory.'/backup/wp-config.backup.php', true);
    fs()->copy($directory.'/.htaccess', $directory.'/backup/.htaccess', true);
    io()->success('WordPress configuration backed up.');
}

#[AsTask(name: 'wp-config-create', description: 'Create wp-config.php using DB_* environment variables')]
function wordpress_config_create(#[AsOption] string $path = '.'): int
{
    return create_wordpress_config($path);
}

#[AsTask(name: 'wp-config-create-force', description: 'Overwrite wp-config.php using DB_* environment variables')]
function wordpress_config_create_force(#[AsOption] string $path = '.'): int
{
    return create_wordpress_config($path, true);
}

#[AsTask(name: 'wp-db-create', description: 'Create the configured WordPress database')]
function wordpress_db_create(#[AsOption] string $path = '.'): int
{
    return run_wordpress(['db', 'create'], $path);
}

#[AsTask(name: 'wp-db-replace-url', description: 'Replace PROD_HOST with DEV_HOST in the database')]
function wordpress_db_replace_url(#[AsOption] string $path = '.'): int
{
    $config = variable('config');

    return run_wordpress(['search-replace', $config->productionHost(), $config->developmentHost()], $path);
}

#[AsTask(name: 'wp-db-replace-to-https', description: 'Replace http:// with https:// in the database')]
function wordpress_db_replace_to_https(#[AsOption] string $path = '.'): int
{
    return run_wordpress(['search-replace', 'http://', 'https://'], $path);
}

#[AsTask(name: 'wp-db-replace-to-http', description: 'Replace https:// with http:// in the database')]
function wordpress_db_replace_to_http(#[AsOption] string $path = '.'): int
{
    return run_wordpress(['search-replace', 'https://', 'http://'], $path);
}

function create_wordpress_config(string $path, bool $force = false): int
{
    $config = variable('config');
    $arguments = [
        'config', 'create',
        '--dbname='.$config->databaseName(),
        '--dbuser='.$config->databaseUser(),
        '--dbhost='.$config->databaseHost(),
        '--dbprefix='.$config->databasePrefix(),
        '--prompt=dbpass',
    ];
    $password = $config->databasePassword();
    if ($force) {
        $arguments[] = '--force';
    }

    return run_wordpress($arguments, $path, $password."\n");
}
