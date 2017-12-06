<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Domain;

final class NormalizedDomainEvent implements \JsonSerializable
{
    private $id;

    private $type;

    private $data;

    private $metadata;

    private $version;

    public function __construct(string $id, string $type, array $data, array $metadata = [], int $version = 0)
    {
        $this->id = $id;
        $this->type = $type;
        $this->version = $version;
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
