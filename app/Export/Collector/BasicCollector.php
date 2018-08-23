<?php

/**
 * BasicCollector.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Collector;

use FireflyIII\Models\ExportJob;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class BasicCollector.
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class BasicCollector
{
    /** @var ExportJob The job to export. */
    protected $job;
    /** @var User The user */
    protected $user;
    /** @var Collection All the entries. */
    private $entries;

    /**
     * BasicCollector constructor.
     */
    public function __construct()
    {
        $this->entries = new Collection;
    }

    /**
     * Get all entries.
     *
     * @return Collection
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    /**
     * Set entries.
     *
     * @param Collection $entries
     */
    public function setEntries(Collection $entries): void
    {
        $this->entries = $entries;
    }

    /**
     * Set export job.
     *
     * @param ExportJob $job
     */
    public function setJob(ExportJob $job): void
    {
        $this->job  = $job;
        $this->user = $job->user;
    }

    /**
     * Set user.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
