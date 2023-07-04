<?php

/**
 * Copyright (c) 2023 Antonio Spinelli https://github.com/tonicospinelli
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Support\Calendar\Exceptions;

use FireflyIII\Support\Calendar\Periodicity;

final class IntervalException extends \Exception
{
    protected $message = 'The periodicity %s is unknown. Choose one of available periodicity: %s';

    public readonly Periodicity $periodicity;
    public readonly array $availableIntervals;

    public static function unavailable(
        Periodicity $periodicity,
        array       $intervals,
        int         $code = 0,
        ?\Throwable $previous = null
    ): IntervalException
    {
        $message = sprintf(
            'The periodicity %s is unknown. Choose one of available periodicity: %s',
            $periodicity->name,
            join(', ', $intervals)
        );

        $exception                     = new IntervalException($message, $code, $previous);
        $exception->periodicity        = $periodicity;
        $exception->availableIntervals = $intervals;
        return $exception;
    }
}
