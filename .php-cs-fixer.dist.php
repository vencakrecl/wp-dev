<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__.'/tmp/php-cs-fixer.cache')
    ->setFinder(Finder::create()
        ->in([__DIR__.'/src', __DIR__.'/build', __DIR__.'/tests'])
        ->exclude('vendor')
        ->append([__DIR__.'/castor.php', __FILE__])
    );
