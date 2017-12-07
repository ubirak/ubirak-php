<?php
declare(strict_types=1);

namespace Ubirak\Component\ProjectionStore\Domain;

class UnknownProjection extends \Exception
{
    public static function named(string $projectionName, string $part): self
    {
        return new static("EventStore does not know the projection named \"$projectionName\" with part \"$part\"");
    }
}
