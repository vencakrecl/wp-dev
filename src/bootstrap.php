<?php

use Symfony\Component\Console\Application;
use WpDev\Command\InitConfigCommand;

require_once __DIR__.'/../vendor/autoload.php';

$version = false;
if (file_exists(__DIR__.'/../config/version')) {
    $version = file_get_contents(__DIR__.'/../config/version');
}

$app = new Application('wp-dev', false === $version ? 'latest' : $version);
$app->addCommand(new InitConfigCommand());

$app->run();
