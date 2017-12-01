<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

interface ProcessManager
{
    public function listensTo(): string;

    public function handleEvent($event);
}
