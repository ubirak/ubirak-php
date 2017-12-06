<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Infra\Persistence;

use Symfony\Component\Serializer\SerializerInterface;
use Ubirak\Component\EventStore\Domain\EventStreamIterator;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;

class ResourceStreamIterator implements EventStreamIterator
{
    private $resource;

    private $serializer;

    private $buffer = false;

    private $offset = -1;

    public function __construct($resource, SerializerInterface $serializer)
    {
        if (false === is_resource($resource)) {
            throw new \InvalidArgumentException('Not a valid resource');
        }
        $this->resource = $resource;
        $this->serializer = $serializer;
        $this->next();
    }

    public function current(): ?NormalizedDomainEvent
    {
        if (false === $this->buffer) {
            return null;
        }

        return $this->serializer->deserialize($this->buffer, NormalizedDomainEvent::class, 'json');
    }

    public function key()
    {
        return $this->offset;
    }

    public function next(): void
    {
        ++$this->offset;
        $this->buffer = fgets($this->resource);
    }

    public function rewind(): void
    {
        rewind($this->resource);
    }

    public function valid(): bool
    {
        return false !== $this->buffer;
    }
}
