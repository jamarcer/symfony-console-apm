<?php

declare(strict_types=1);

namespace Jamarcer\Tests\APM\Symfony\Component\Console;

use Jamarcer\APM\Symfony\Component\Console\ElasticAPMSubscriber;
use Jamarcer\Tests\APM\Mock\CommandMock;
use Jamarcer\Tests\APM\Mock\CommandWithErrorMock;
use Jamarcer\Tests\APM\Mock\EventDispatcherMock;
use Jamarcer\Tests\APM\Mock\ReporterMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Tester\ApplicationTester;
use ZoiloMora\ElasticAPM\Configuration\CoreConfiguration;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use ZoiloMora\ElasticAPM\Pool\Memory\MemoryPoolFactory;

final class ElasticAPMSubscriberTest extends TestCase
{
    /** @test */
    public function check_if_apm_subscriber_is_subscribed(): void
    {
        self::assertArrayHasKey(ConsoleEvents::COMMAND, ElasticAPMSubscriber::getSubscribedEvents());
        self::assertArrayHasKey(ConsoleEvents::TERMINATE, ElasticAPMSubscriber::getSubscribedEvents());
        self::assertArrayHasKey(ConsoleEvents::ERROR, ElasticAPMSubscriber::getSubscribedEvents());
    }

    /** @test */
    public function when_application_run_ok_apm_registered_actions(): void
    {
        $subscriber = $this->getSubscriber();

        $dispatcher = new EventDispatcherMock();
        $dispatcher->addSubscriber($subscriber);

        $application = new Application();
        $application->setAutoExit(false);
        $application->setDispatcher($dispatcher);

        $command = new CommandMock('app:test-command');
        $application->add($command);
        $application->setDefaultCommand($command->getName(), true);
        $tester = new ApplicationTester($application);
        $tester->run([]);

        self::assertTrue($subscriber->isActionOk('app:test-command'));
    }

    /** @test */
    public function when_application_run_and_fails_apm_registered_actions(): void
    {
        $subscriber = $this->getSubscriber();

        $dispatcher = new EventDispatcherMock();
        $dispatcher->addSubscriber($subscriber);

        $application = new Application();
        $application->setAutoExit(false);
        $application->setDispatcher($dispatcher);

        $command = new CommandWithErrorMock('app:fail-command');
        $application->add($command);
        $application->setDefaultCommand($command->getName(), true);
        $tester = new ApplicationTester($application);
        $tester->run([]);

        self::assertTrue($subscriber->isActionFailed('app:fail-command'));
    }

    protected function getSubscriber(bool $startTransaction = false): ElasticAPMSubscriber
    {
        $configurator = CoreConfiguration::create(['appName' => 'Test',]);
        $reporter = new ReporterMock();
        $factory = MemoryPoolFactory::create();
        $tracer = new ElasticApmTracer($configurator, $reporter, $factory);
        if ($startTransaction) {
            $tracer->startTransaction('testTransaction', 'testType');
        }

        return new ElasticAPMSubscriber($tracer);
    }
}