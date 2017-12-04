<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Domain;

class OptimisticConcurrencyConflicted extends \LogicException
{
    public static function wrongVersionOfStream($streamName, $version): self
    {
        return new static("Cannot update stream \"$streamName\" from version $version");
    }
}
