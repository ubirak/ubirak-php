<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

final class NormalizedDomainEvent
{
    private $id;

    private $type;

    private $data;

    private $metadata;

    public function __construct(string $id, string $type, array $data, array $metadata = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function __toString()
    {
        return json_encode([
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ]);
    }
}
