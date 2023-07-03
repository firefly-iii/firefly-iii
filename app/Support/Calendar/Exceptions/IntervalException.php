<?php

namespace FireflyIII\Support\Calendar\Exceptions;

use FireflyIII\Support\Calendar\Periodicity;
use JetBrains\PhpStorm\Pure;

final class IntervalException extends \Exception
{
    protected $message = 'The periodicity %s is unknown. Choose one of available periodicity: %s';

    public readonly Periodicity $periodicity;
    public readonly array $availableIntervals;

    public static function unavailable(Periodicity $periodicity, array $instervals, int $code = 0, ?\Throwable $previous = null): IntervalException
    {
        $message = sprintf(
            'The periodicity %s is unknown. Choose one of available periodicity: %s',
            $periodicity->name,
            join(', ', $instervals)
        );

        $exception                     = new IntervalException($message, $code, $previous);
        $exception->periodicity        = $periodicity;
        $exception->availableIntervals = $instervals;
        return $exception;
    }
}
