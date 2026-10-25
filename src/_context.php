<?php

declare(strict_types=1);

use Castor\Attribute\AsContext;
use Castor\Context;
use Symfony\Component\Process\ExecutableFinder;
use WPDev\Config;

#[AsContext(default: true)]
function default_context(): Context
{
    $executable = (new ExecutableFinder())->find('wp');
    if (null === $executable) {
        throw new RuntimeException('WP-CLI is missing. Install it with: brew install wp-cli');
    }

    return new Context(data: [
        'wp' => $executable,
        'config' => Config::fromEnvironment(),
    ]);
}
