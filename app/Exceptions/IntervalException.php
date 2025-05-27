<?php

/*
 * IntervalException.php
 * Copyright (c) 2023 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Exceptions;

use FireflyIII\Support\Calendar\Periodicity;
use Exception;
use Throwable;

/**
 * Class IntervalException
 */
final class IntervalException extends Exception
{
    public array       $availableIntervals;
    public Periodicity $periodicity;

    /** @var mixed */
    protected $message = 'The periodicity %s is unknown. Choose one of available periodicity: %s';

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->availableIntervals = [];
        $this->periodicity        = Periodicity::Monthly;
    }

    public static function unavailable(
        Periodicity $periodicity,
        array       $intervals,
        int         $code = 0,
        ?Throwable $previous = null
    ): self {
        $message                       = sprintf(
            'The periodicity %s is unknown. Choose one of available periodicity: %s',
            $periodicity->name,
            implode(', ', $intervals)
        );

        $exception                     = new self($message, $code, $previous);
        $exception->periodicity        = $periodicity;
        $exception->availableIntervals = $intervals;

        return $exception;
    }
}
