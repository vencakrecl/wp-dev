<?php

declare(strict_types=1);

use function Castor\import;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

import(__DIR__.'/src/castor.php');
import(__DIR__.'/build/castor.php');
import(__DIR__.'/build/quality.php');
