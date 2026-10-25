<?php

declare(strict_types=1);

namespace WPDev\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class WordPressTasksTest extends TestCase
{
    private string $directory;
    private string $binary;
    /** @var array<string, string> */
    private array $environment;

    protected function setUp(): void
    {
        $root = dirname(__DIR__);
        $os = match (PHP_OS_FAMILY) {
            'Darwin' => 'darwin',
            'Linux' => 'linux',
            default => throw new \RuntimeException('Tests require macOS or Linux.'),
        };
        $arch = match (php_uname('m')) {
            'arm64', 'aarch64' => 'arm64',
            'x86_64', 'amd64' => 'amd64',
            default => throw new \RuntimeException('Unsupported CPU architecture.'),
        };
        $this->binary = getenv('WP_DEV_BINARY') ?: $root.'/dist/wp-dev.'.$os.'-'.$arch;
        self::assertFileExists($this->binary, 'Run vendor/bin/castor build first.');
        $this->directory = $root.'/tmp/tests/'.bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->directory.'/bin');
        $filesystem->dumpFile($this->directory.'/bin/wp', '#!'.PHP_BINARY."\n".<<<'PHP'
<?php
echo json_encode([
    'argv' => $GLOBALS['argv'],
    'input' => stream_get_contents(STDIN),
    'cwd' => getcwd(),
]);
exit((int) getenv('WP_DEV_TEST_EXIT'));
PHP
        );
        $filesystem->chmod($this->directory.'/bin/wp', 0o755);
        $this->environment = [
            'PATH' => $this->directory.'/bin:/usr/bin:/bin',
            'CASTOR_CACHE_DIR' => $this->directory.'/cache',
            'WP_CLI_CONFIG_PATH' => '/dev/null',
            'WP_CLI_CACHE_DIR' => $this->directory.'/wp-cache',
            'WP_DEV_TEST_EXIT' => '0',
            'DB_NAME' => 'wordpress',
            'DB_USER' => 'wordpress',
            'DB_PASS' => "password with spaces and 'quotes'",
            'DB_HOST' => 'localhost:3306',
            'DB_PREFIX' => 'custom_',
            'PROD_HOST' => 'https://production.example',
            'DEV_HOST' => 'http://development.example',
        ];
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            (new Filesystem())->remove($this->directory);
        }
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function taskCommands(): iterable
    {
        yield 'download' => ['wp-download', ['core', 'download']];
        yield 'database' => ['wp-db-create', ['db', 'create']];
        yield 'URL replacement' => ['wp-db-replace-url', ['search-replace', 'https://production.example', 'http://development.example']];
        yield 'to HTTPS' => ['wp-db-replace-to-https', ['search-replace', 'http://', 'https://']];
        yield 'to HTTP' => ['wp-db-replace-to-http', ['search-replace', 'https://', 'http://']];
    }

    /** @param list<string> $arguments */
    #[DataProvider('taskCommands')]
    public function testRoutesTasks(string $task, array $arguments): void
    {
        $result = $this->capture([$task, '--path='.$this->directory]);
        self::assertIsArray($result['argv']);
        self::assertSame([...$arguments, '--path='.$this->directory], array_slice($result['argv'], 1));
        self::assertSame($this->directory, $result['cwd']);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testCreatesConfigWithPasswordOnStdin(bool $force): void
    {
        $result = $this->capture([$force ? 'wp-config-create-force' : 'wp-config-create']);
        self::assertIsArray($result['argv']);
        self::assertSame(['config', 'create'], array_slice($result['argv'], 1, 2));
        self::assertSame($force, in_array('--force', $result['argv'], true));
        foreach (['dbname' => 'DB_NAME', 'dbuser' => 'DB_USER', 'dbhost' => 'DB_HOST', 'dbprefix' => 'DB_PREFIX'] as $option => $variable) {
            self::assertContains('--'.$option.'='.$this->environment[$variable], $result['argv']);
        }
        self::assertContains('--prompt=dbpass', $result['argv']);
        self::assertSame($this->environment['DB_PASS']."\n", $result['input']);
        self::assertStringNotContainsString($this->environment['DB_PASS'], json_encode($result['argv'], JSON_THROW_ON_ERROR));
    }

    public function testAllowsEmptyPassword(): void
    {
        self::assertSame("\n", $this->capture(['wp-config-create'], ['DB_PASS' => ''])['input']);
    }

    public function testPropagatesExitCode(): void
    {
        $this->runCommand(['wp-download'], 7, ['WP_DEV_TEST_EXIT' => '7']);
    }

    public function testRejectsMissingHost(): void
    {
        $process = $this->runCommand(['wp-db-replace-url'], 1, ['PROD_HOST' => '']);
        self::assertStringContainsString('PROD_HOST', $process->getOutput().$process->getErrorOutput());
    }

    public function testBacksUpAndOverwritesConfig(): void
    {
        file_put_contents($this->directory.'/wp-config.php', 'original configuration');
        $this->runCommand(['wp-config-backup'], 1);
        self::assertDirectoryDoesNotExist($this->directory.'/backup');
        file_put_contents($this->directory.'/.htaccess', 'original rewrite rules');
        $this->runCommand(['wp-config-backup']);
        self::assertStringEqualsFile($this->directory.'/backup/wp-config.backup.php', 'original configuration');
        self::assertStringEqualsFile($this->directory.'/backup/.htaccess', 'original rewrite rules');
        file_put_contents($this->directory.'/wp-config.php', 'updated configuration');
        $this->runCommand(['wp-config-backup']);
        self::assertStringEqualsFile($this->directory.'/backup/wp-config.backup.php', 'updated configuration');
    }

    public function testReportsMissingWpCli(): void
    {
        $process = $this->runCommand(['wp-download'], 1, ['PATH' => $this->directory.'/missing']);
        self::assertStringContainsString('brew install wp-cli', $process->getOutput().$process->getErrorOutput());
    }

    public function testRejectsBackupWithoutWpCli(): void
    {
        file_put_contents($this->directory.'/wp-config.php', 'configuration');
        file_put_contents($this->directory.'/.htaccess', 'rewrite rules');
        $process = $this->runCommand(['wp-config-backup'], 1, ['PATH' => $this->directory.'/missing']);
        self::assertStringContainsString('brew install wp-cli', $process->getOutput().$process->getErrorOutput());
        self::assertDirectoryDoesNotExist($this->directory.'/backup');
    }

    public function testGeneratesConfigUsingInstalledWpCli(): void
    {
        if (null === (new ExecutableFinder())->find('wp')) {
            self::markTestSkipped('Install WP-CLI to run the real config generation check.');
        }
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->directory.'/wp-includes');
        $filesystem->dumpFile($this->directory.'/wp-load.php', '<?php');
        $filesystem->dumpFile($this->directory.'/wp-includes/version.php', '<?php $wp_version="6.8"; $wp_db_version=1; $wp_local_package="en_US";');
        $config = $this->directory.'/global-config.yml';
        $filesystem->dumpFile($config, "config create:\n  skip-check: true\n  skip-salts: true\n");
        $this->runCommand(['wp-config-create'], overrides: [
            'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
            'WP_CLI_CONFIG_PATH' => $config,
            'DB_PASS' => 'real-config-password',
        ]);
        self::assertFileExists($this->directory.'/wp-config.php');
        self::assertStringContainsString("define( 'DB_PASSWORD', 'real-config-password' );", $filesystem->readFile($this->directory.'/wp-config.php'));
    }

    /**
     * @param list<string>          $arguments
     * @param array<string, string> $overrides
     *
     * @return array<mixed>
     */
    private function capture(array $arguments, array $overrides = []): array
    {
        $data = json_decode($this->runCommand($arguments, overrides: $overrides)->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    /**
     * @param list<string>          $arguments
     * @param array<string, string> $overrides
     */
    private function runCommand(array $arguments, int $expected = 0, array $overrides = []): Process
    {
        $process = new Process([$this->binary, ...$arguments], $this->directory, [...$this->environment, ...$overrides], '', 30);
        $process->run();
        self::assertSame($expected, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());

        return $process;
    }
}
