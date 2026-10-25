<?php

declare(strict_types=1);

namespace Build;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\context;
use function Castor\fs;
use function Castor\run;

#[AsTask(name: 'build', namespace: '', description: 'Build a standalone WP-DEV executable for this machine')]
function build(#[AsArgument] string $version = '0.1.0-dev'): void
{
    if (!preg_match('/^[0-9A-Za-z][0-9A-Za-z.+_-]*$/D', $version)) {
        throw new \InvalidArgumentException('Invalid version: use letters, numbers, dots, plus signs, underscores, or hyphens.');
    }
    [$repackOs, $compileOs] = match (PHP_OS_FAMILY) {
        'Darwin' => ['darwin', 'macos'],
        'Linux' => ['linux', 'linux'],
        default => throw new \RuntimeException('Build on macOS or Linux.'),
    };
    [$repackArch, $compileArch] = match (php_uname('m')) {
        'arm64', 'aarch64' => ['arm64', 'aarch64'],
        'x86_64', 'amd64' => ['amd64', 'x86_64'],
        default => throw new \RuntimeException('Unsupported CPU architecture.'),
    };

    $root = dirname(__DIR__);
    fs()->mkdir([$root.'/tmp', $root.'/dist']);

    $finder = new ExecutableFinder();
    foreach (['php', 'composer', 'castor', 'box'] as $tool) {
        if (null === $finder->find($tool, extraDirs: [$root.'/vendor/bin'])) {
            throw new \RuntimeException('Missing build dependency: '.$tool.' (see README.md).');
        }
    }
    $buildContext = context()
        ->withWorkingDirectory($root)
        ->withAllowFailure(false)
        ->withPty(false)
        ->withEnvironment([
            'PATH' => $root.'/vendor/bin'.PATH_SEPARATOR.getenv('PATH'),
            'CASTOR_CACHE_DIR' => $root.'/tmp/cache',
            'CASTOR_GENERATE_STUBS' => 'false',
        ]);

    $stage = $root.'/tmp/app.'.bin2hex(random_bytes(6));
    fs()->mkdir($stage, 0o700);

    try {
        fs()->mirror($root.'/src', $stage.'/src');
        fs()->remove($stage.'/src/.castor.stub.php');
        fs()->dumpFile($stage.'/castor.php', "<?php\n\nrequire __DIR__.'/vendor/autoload.php';\nrequire __DIR__.'/src/castor.php';\n");
        foreach (['composer.json', 'composer.lock'] as $file) {
            fs()->copy($root.'/'.$file, $stage.'/'.$file);
        }
        run([
            'composer', 'install', '--working-dir='.$stage,
            '--no-dev', '--prefer-dist', '--no-interaction',
        ], $buildContext);
        fs()->dumpFile($stage.'/box.json', json_encode([
            'directories' => ['vendor', 'src'],
            'exclude-composer-files' => false,
        ], JSON_THROW_ON_ERROR));
        $stageContext = $buildContext->withWorkingDirectory($stage);
        run([
            'castor', 'repack', '--app-name=wp-dev', '--app-version='.$version,
            '--castor-version=v1.8.1', '--os='.$repackOs, '--arch='.$repackArch,
            '--no-logo', '--output-directory='.$root.'/dist',
        ], $stageContext);

        $output = $root.'/dist/wp-dev.'.$repackOs.'-'.$repackArch;
        run([
            'castor', 'compile', $output.'.phar', '--os='.$compileOs,
            '--arch='.$compileArch, '--php-version=8.5',
            '--php-extensions=mbstring,phar,posix,tokenizer,curl,filter,openssl',
            '--binary-path='.$output,
        ], $stageContext);
        run([$output, 'list', '--raw'], $buildContext);
        run([$output, 'wp', 'cli', 'version'], $buildContext);
    } finally {
        fs()->remove($stage);
    }
}
