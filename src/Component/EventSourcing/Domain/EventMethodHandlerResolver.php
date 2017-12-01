<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

class EventMethodHandlerResolver
{
    const EVENT_HANDLER_SUFFIX = 'when';

    const NAMESPACE_SEPARATOR = '\\';

    /**
     * For an event named My\Ns\ProductWasRegistered will return `whenProductWasRegistered`.
     */
    public static function resolve(DomainEvent $change): string
    {
        return self::EVENT_HANDLER_SUFFIX
            .ltrim(strrchr(get_class($change), self::NAMESPACE_SEPARATOR), self::NAMESPACE_SEPARATOR)
        ;
    }
}
