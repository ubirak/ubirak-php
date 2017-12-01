<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

class ExceptionHandler
{
    private $exceptionsMap;

    public function __construct(iterable $exceptionsMap)
    {
        $this->exceptionsMap = $exceptionsMap;
    }

    public function isHandled(\Exception $exception)
    {
        $exceptionClassName = get_class($exception);

        return 0 < count(array_filter(
            $this->exceptionsMap,
            function ($e) use ($exceptionClassName) {
                return $e === $exceptionClassName;
            }
        ));
    }
}
