<?php

declare(strict_types=1);

namespace Jamarcer\Tests\APM\Mock;

use ZoiloMora\ElasticAPM\Reporter\Reporter;

final class ReporterMock implements Reporter
{
    private array $events = [];

    public function report(array $events): void
    {
        $this->events = array_merge($this->events, $events);
    }

    public function events(): array
    {
        return $this->events;
    }
}