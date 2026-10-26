<?php

declare(strict_types=1);

use function Castor\context;
use function Castor\run;
use function Castor\variable;

/**
 * @param string[]        $arguments
 * @param resource|string $input
 */
function run_wordpress(array $arguments, ?string $path = null, mixed $input = STDIN): int
{
    if (null !== $path) {
        $arguments[] = '--path='.$path;
    }

    return run(
        [variable('wp'), ...$arguments],
        context()->withAllowFailure()->withPty(false)->withInput($input),
    )->getExitCode() ?? 1;
}
