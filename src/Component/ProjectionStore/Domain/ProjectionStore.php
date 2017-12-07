<?php
declare(strict_types=1);

namespace Ubirak\Component\ProjectionStore\Domain;

interface ProjectionStore
{
    public function fetchProjectionOf(string $projectionName, string $streamName): string;

    public function fetchProjection(string $projectionName): string;
}
