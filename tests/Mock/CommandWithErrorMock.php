<?php

declare(strict_types=1);

namespace Jamarcer\Tests\APM\Mock;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CommandWithErrorMock extends Command
{
    protected static $defaultName = 'app:mock-failed-command';

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
            ->setDescription('Command with error for test.')
            ->setHelp('Testing console subscriber.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new Exception('Fail forced');
    }
}