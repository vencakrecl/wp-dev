<?php

declare(strict_types=1);

namespace Build;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\run;

#[AsTask(namespace: 'lint', name: 'fix', description: 'Fix PHP code style')]
function lint_fix(): void
{
    run(['vendor/bin/php-cs-fixer', 'fix'], context('build'));
}

#[AsTask(namespace: 'lint', name: 'check', description: 'Check PHP code style without changing files')]
function lint_check(): void
{
    run(['vendor/bin/php-cs-fixer', 'fix', '--dry-run', '--diff'], context('build'));
}

#[AsTask(namespace: 'phpstan', name: 'check', description: 'Run PHPStan static analysis')]
function phpstan_check(): void
{
    run(['vendor/bin/phpstan', 'analyse', '--memory-limit=512M'], context('build'));
}

#[AsTask(namespace: '', name: 'test', description: 'Run PHPUnit against the built executable')]
function test(): void
{
    run(['vendor/bin/phpunit'], context('build'));
}
