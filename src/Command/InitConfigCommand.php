<?php

namespace WpDev\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:init')]
class InitConfigCommand extends Command
{
    protected function configure()
    {
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $input->getOption('config') ?? getcwd();
        $configFile = \sprintf('%s/.wp-dev.json', $configPath);

        if (!file_exists($configFile)) {
            file_put_contents($configFile, json_encode([
                'themes' => [],
                'plugins' => [],
            ], \JSON_PRETTY_PRINT));

            $output->writeln(\sprintf('Configuration file "%s" created successfully.', $configFile));

            return Command::SUCCESS;
        }

        $output->writeln(\sprintf('Configuration file "%s" already exists.', $configFile));

        return Command::SUCCESS;
    }
}
