<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

final class EventListener
{
    private $eventBusConsumer;

    private $processManager;

    private $exceptionHandler;

    public function __construct(EventBusConsumer $eventBusConsumer, ProcessManager $processManager, ExceptionHandler $exceptionHandler)
    {
        $this->eventBusConsumer = $eventBusConsumer;
        $this->processManager = $processManager;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function listen(): \Generator
    {
        $eventStats = EventStats::bare();
        yield $eventStats;

        foreach ($this->eventBusConsumer->consume($this->processManager->listensTo()) as $message) {
            try {
                $this->processManager->handleEvent($message->getData());
                $eventStats = $this->acknowledge($message, $eventStats);
                yield $eventStats;
            } catch (\Exception $e) {
                if ($this->exceptionHandler->isHandled($e)) {
                    $eventStats = $this->skip($message, $eventStats);
                    yield $eventStats;

                    continue;
                }

                $eventStats = $this->retry($message, $eventStats);
                yield $eventStats;
            }
        }
    }

    private function acknowledge(NormalizedDomainEvent $message, EventStats $eventStats)
    {
        $this->eventBusConsumer->acknowledge($message);

        return $eventStats->acknowledged();
    }

    private function skip(NormalizedDomainEvent $message, EventStats $eventStats)
    {
        $this->eventBusConsumer->skip($message);

        return $eventStats->skipped();
    }

    private function retry(NormalizedDomainEvent $message, EventStats $eventStats)
    {
        $this->eventBusConsumer->retry($message);

        return $eventStats->retried();
    }
}
