<?php

/**
 * BasicExporter.php
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

namespace FireflyIII\Export\Exporter;

use FireflyIII\Models\ExportJob;
use Illuminate\Support\Collection;

/**
 * Class BasicExporter.
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class BasicExporter
{
    /** @var ExportJob The export job */
    protected $job;
    /** @var Collection The entries */
    private $entries;

    /**
     * BasicExporter constructor.
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
     * Set all entries.
     *
     * @param Collection $entries
     */
    public function setEntries(Collection $entries): void
    {
        $this->entries = $entries;
    }

    /**
     * Set the job.
     *
     * @param ExportJob $job
     */
    public function setJob(ExportJob $job): void
    {
        $this->job = $job;
    }
}
