<?php
/**
 * AbstractCronjob.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use Exception;
/**
 * Class AbstractCronjob
 *
 * @codeCoverageIgnore
 */
abstract class AbstractCronjob
{
    /** @var int */
    public $timeBetweenRuns = 43200;

    /** @var bool */
    protected $force;

    /** @var Carbon */
    protected $date;

    /**
     * AbstractCronjob constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->force = false;
        $this->date  = today(config('app.timezone'));
    }



    /**
     * @param bool $force
     */
    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }

    /**
     * @return bool
     */
    abstract public function fire(): bool;

}
