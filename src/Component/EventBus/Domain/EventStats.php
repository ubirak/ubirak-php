<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

final class EventStats implements \JsonSerializable
{
    protected $acknowledged;

    protected $skipped;

    protected $retried;

    protected $total;

    public function __construct(int $acknowledged, int $skipped, int $retried)
    {
        $this->acknowledged = $acknowledged;
        $this->skipped = $skipped;
        $this->retried = $retried;
        $this->total = $this->acknowledged + $this->skipped + $this->retried;
    }

    public static function bare()
    {
        return new static(0, 0, 0);
    }

    public function acknowledged()
    {
        return new self($this->acknowledged + 1, $this->skipped, $this->retried);
    }

    public function skipped()
    {
        return new self($this->acknowledged, $this->skipped + 1, $this->retried);
    }

    public function retried()
    {
        return new self($this->acknowledged, $this->skipped, $this->retried + 1);
    }

    public function jsonSerialize()
    {
        return [
            'acknowledged' => $this->acknowledged,
            'skipped' => $this->skipped,
            'retried' => $this->retried,
            'total' => $this->total,
        ];
    }
}
