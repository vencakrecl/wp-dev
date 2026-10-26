<?php

declare(strict_types=1);

namespace Build;

/** @return array{string, string} Repack and compiler OS names. */
function detect_os(): array
{
    return match (PHP_OS_FAMILY) {
        'Darwin' => ['darwin', 'macos'],
        'Linux' => ['linux', 'linux'],
        default => throw new \RuntimeException('Build on macOS or Linux.'),
    };
}

/** @return array{string, string} Repack and compiler architecture names. */
function detect_arch(): array
{
    return match (php_uname('m')) {
        'arm64', 'aarch64' => ['arm64', 'aarch64'],
        'x86_64', 'amd64' => ['amd64', 'x86_64'],
        default => throw new \RuntimeException('Unsupported CPU architecture.'),
    };
}
