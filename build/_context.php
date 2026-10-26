<?php

declare(strict_types=1);

namespace Build;

use Castor\Attribute\AsContext;
use Castor\Context;

#[AsContext(name: 'build')]
function build_context(): Context
{
    return new Context(workingDirectory: dirname(__DIR__));
}
