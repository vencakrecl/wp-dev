<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = new Finder()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->append([
        new SplFileInfo(__DIR__ . '/bin/build'),
        new SplFileInfo(__DIR__ . '/bin/wp-dev'),
    ])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return new Config()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '.php-cs-fixer.cache')
    ->setIndent('    ')
    ->setParallelConfig(ParallelConfigFactory::detect(processTimeout: 240))
    ->setUnsupportedPhpVersionAllowed(true);
