<?php

declare(strict_types=1);

use function Castor\mount;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

require_once __DIR__.'/vendor/autoload.php';

mount(__DIR__.'/src');
mount(__DIR__.'/build');
