<?php

declare(strict_types=1);

namespace Jamarcer\Tests\APM\Mock;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CommandMock extends Command
{
    protected static $defaultName = 'app:mock-command';

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Command for test.')
            ->setHelp('Testing console subscriber.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return Command::SUCCESS;
    }
}