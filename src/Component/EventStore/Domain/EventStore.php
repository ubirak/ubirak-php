<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Domain;

interface EventStore
{
    public function commitToStream(string $streamName, array $events, int $expectedVersion = null): void;

    public function fetchStream(string $streamName): EventStreamIterator;

    public function deleteStream(string $streamName): void;
}
