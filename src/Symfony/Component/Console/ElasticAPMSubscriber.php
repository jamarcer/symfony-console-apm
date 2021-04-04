<?php

declare(strict_types=1);

namespace Jamarcer\APM\Symfony\Component\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use function array_key_exists;
use function array_key_first;
use function count;
use function spl_object_id;

final class ElasticAPMSubscriber implements EventSubscriberInterface
{
    private ElasticApmTracer $elasticApmTracer;

    private array $transactions;
    private array $spans;
    private array $actions;

    public function __construct(ElasticApmTracer $elasticApmTracer)
    {
        $this->elasticApmTracer = $elasticApmTracer;
        $this->transactions = [];
        $this->spans = [];
        $this->actions = [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommandEvent'],
            ConsoleEvents::TERMINATE => ['onConsoleTerminateEvent'],
            ConsoleEvents::ERROR => ['onConsoleErrorEvent'],
        ];
    }

    public function onConsoleCommandEvent(ConsoleCommandEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $command = $event->getCommand();
        $key = $this->transactionKey($command);

        if (0 !== count($this->transactions)) {
            $this->spans[$key] = $this->elasticApmTracer->startSpan(
                $command->getName(),
                'console',
            );
        }

        $this->transactions[$key] = $this->elasticApmTracer->startTransaction(
            $command->getName(),
            'console',
        );
        $this->actions[$key] = [
            'name' => $command->getName(),
            'command' => true,
            'error' => false,
            'terminate' => false,
        ];
    }

    public function onConsoleTerminateEvent(ConsoleTerminateEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $key = $this->transactionKey(
            $event->getCommand(),
        );

        $this->transactions[$key]->stop(
            (string) $event->getExitCode(),
        );

        if (true === array_key_exists($key, $this->spans)) {
            $this->spans[$key]->stop();
        }

        if (array_key_first($this->transactions) !== $key) {
            return;
        }

        unset($this->transactions[$key]);
        $this->actions[$key]['terminate'] = true;

        $this->elasticApmTracer->flush();
    }

    public function onConsoleErrorEvent(ConsoleErrorEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $key = $this->transactionKey(
            $event->getCommand(),
        );
        $this->actions[$key]['error'] = true;

        $this->elasticApmTracer->captureException(
            $event->getError(),
        );
    }

    public function isActionOk(string $name): bool
    {
        foreach ($this->actions as $action) {
            if ($name === $action['name']) {
                return $action['command'] && $action['terminate'] && !$action['error'];
            }
        }
        return false;
    }

    public function isActionFailed(string $name): bool
    {
        foreach ($this->actions as $action) {
            if ($name === $action['name']) {
                return $action['command'] && $action['terminate'] && $action['error'];
            }
        }
        return false;
    }

    private function transactionKey(Command $command): int
    {
        return spl_object_id($command);
    }
}