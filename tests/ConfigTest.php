<?php

declare(strict_types=1);

namespace WPDev\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use WPDev\Config;

require_once dirname(__DIR__).'/src/Config.php';

final class ConfigTest extends TestCase
{
    public function testSnapshotsApplicationEnvironment(): void
    {
        $values = [
            'DB_NAME' => 'database',
            'DB_USER' => 'user',
            'DB_PASS' => 'password',
            'DB_HOST' => 'host:3306',
            'DB_PREFIX' => 'custom_',
            'PROD_HOST' => 'https://prod.example',
            'DEV_HOST' => 'http://dev.example',
        ];
        $previous = [];
        foreach ($values as $name => $value) {
            $previous[$name] = getenv($name);
            putenv($name.'='.$value);
        }

        try {
            $config = Config::fromEnvironment();
            foreach ($values as $name => $value) {
                putenv($name.'=changed');
            }
            self::assertSame('database', $config->databaseName());
            self::assertSame('user', $config->databaseUser());
            self::assertSame('password', $config->databasePassword());
            self::assertSame('host:3306', $config->databaseHost());
            self::assertSame('custom_', $config->databasePrefix());
            self::assertSame('https://prod.example', $config->productionHost());
            self::assertSame('http://dev.example', $config->developmentHost());
        } finally {
            foreach ($previous as $name => $value) {
                putenv(false === $value ? $name : $name.'='.$value);
            }
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function missingValues(): iterable
    {
        yield 'name' => ['databaseName', 'DB_NAME'];
        yield 'user' => ['databaseUser', 'DB_USER'];
        yield 'password' => ['databasePassword', 'DB_PASS'];
        yield 'host' => ['databaseHost', 'DB_HOST'];
        yield 'prefix' => ['databasePrefix', 'DB_PREFIX'];
        yield 'production' => ['productionHost', 'PROD_HOST'];
        yield 'development' => ['developmentHost', 'DEV_HOST'];
    }

    #[DataProvider('missingValues')]
    public function testReportsMissingValue(string $method, string $name): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing environment variable: '.$name);
        (new Config())->$method();
    }

    public function testAllowsEmptyPassword(): void
    {
        self::assertSame('', (new Config(dbPassword: ''))->databasePassword());
    }

    #[TestWith(["password\nother"])]
    #[TestWith(["password\rother"])]
    public function testRejectsPasswordLineBreaks(string $password): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB_PASS must not contain line breaks.');
        (new Config(dbPassword: $password))->databasePassword();
    }

    public function testRejectsEmptyRequiredValue(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing environment variable: DB_NAME');
        (new Config(dbName: ''))->databaseName();
    }

    public function testRedactsPasswordFromDebugOutput(): void
    {
        self::assertSame('[redacted]', (new Config(dbPassword: 'secret'))->__debugInfo()['dbPassword']);
    }
}
