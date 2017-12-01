<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

use Ubirak\Component\EventSourcing\Domain\DomainEvent as Change;

abstract class AggregateRoot extends Entity
{
    private $versionId;

    protected function __construct(IdentifiesAggregate $aggregateId, int $versionId = 0)
    {
        // Use named constructor in child as it made event sourcing and ubiquitous language easier
        $this->id = $aggregateId;
        $this->versionId = $versionId;
    }

    public function getId(): IdentifiesAggregate
    {
        return $this->id;
    }

    public static function reconstituteFromHistory(AggregateHistory $history): self
    {
        $aggregateRoot = new static($history->getAggregateId(), $history->getVersionId());

        foreach ($history as $change) {
            $aggregateRoot->apply($change);
        }

        return $aggregateRoot;
    }

    public function popHistory(): AggregateHistory
    {
        // For now we pop entities history not in realtime.
        // An event recorded in entity will always be saved after all aggregate ones
        // Need to think to improve that and keep simple design
        foreach ($this->getChildEntities() as $entity) {
            $this->recordedChanges = array_merge(
                $this->recordedChanges,
                $entity->popChanges()
            );
        }

        return new AggregateHistory($this->popChanges(), $this->versionId);
    }

    protected function apply(Change $change): void
    {
        parent::apply($change);

        foreach ($this->getChildEntities() as $entity) {
            $entity->defineParent($this->getId());
            $entity->apply($change);
        }
    }

    protected function getChildEntities(): iterable
    {
        return [];
    }
}
