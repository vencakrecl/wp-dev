<?php

namespace Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use WpDev\Command\InitConfigCommand;

#[CoversClass(InitConfigCommand::class)]
class InitConfigCommandTest extends TestCase
{
    protected function setUp(): void
    {
        if (file_exists(__DIR__.'/.wp-dev.json')) {
            unlink(__DIR__.'/.wp-dev.json');
        }
    }

    public function testExecute(): void
    {
        $tester = new CommandTester(new InitConfigCommand());
        $tester->execute([
            '--config' => __DIR__,
        ]);

        $this->assertSame(\sprintf('Configuration file "%s/.wp-dev.json" created successfully.'.\PHP_EOL, __DIR__), $tester->getDisplay());

        $tester->execute([
            '--config' => __DIR__,
        ]);

        $this->assertSame(\sprintf('Configuration file "%s/.wp-dev.json" already exists.'.\PHP_EOL, __DIR__), $tester->getDisplay());
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__.'/.wp-dev.json')) {
            unlink(__DIR__.'/.wp-dev.json');
        }
    }
}
