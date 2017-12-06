<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Domain;

class UnknownStream extends \Exception
{
    public static function named($streamName): self
    {
        return new static("EventStore does not know the stream named \"$streamName\"");
    }
}
