<?php

/*
 * Timer.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Debug;

use Illuminate\Support\Facades\Log;

class Timer
{
    private array         $times    = [];
    private static ?Timer $instance = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation.
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function start(string $title): void
    {
        $this->times[$title] = microtime(true);
    }

    public function stop(string $title): void
    {
        $start = $this->times[$title] ?? 0;
        $end   = microtime(true);
        $diff  = $end - $start;
        unset($this->times[$title]);
        Log::debug(sprintf('Timer "%s" took %f seconds', $title, $diff));
    }
}
