<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

abstract class Entity extends ChangesRecorder
{
    protected $id;

    protected $parentId;

    protected function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    protected function defineParent(IdentifiesAggregate $parentId)
    {
        $this->parentId = $parentId;
    }
}
