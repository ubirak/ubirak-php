<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

class EventListenerHealthCheck
{
    private $healthPath;

    public function __construct(string $healthPath)
    {
        $this->healthPath = $healthPath;
    }

    public function listenAndCheck(EventListener $eventListener)
    {
        $startTime = time();

        foreach ($eventListener->listen() as $eventStats) {
            $heartbeat = json_encode(
                [
                    'heartbeat' => [
                        'date' => date('Y-m-d\TH:i:sP'),
                        'uptime' => time() - $startTime,
                    ],
                    'events' => $eventStats,
                ],
                JSON_PRETTY_PRINT
            );
            file_put_contents($this->healthPath, $heartbeat);
        }
    }
}
