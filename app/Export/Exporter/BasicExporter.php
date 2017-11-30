<?php
/**
 * BasicExporter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Export\Exporter;

use FireflyIII\Models\ExportJob;
use Illuminate\Support\Collection;

/**
 * Class BasicExporter.
 */
class BasicExporter
{
    /** @var ExportJob */
    protected $job;
    /** @var Collection */
    private $entries;

    /**
     * BasicExporter constructor.
     */
    public function __construct()
    {
        $this->entries = new Collection;
    }

    /**
     * @return Collection
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    /**
     * @param Collection $entries
     */
    public function setEntries(Collection $entries)
    {
        $this->entries = $entries;
    }

    /**
     * @param ExportJob $job
     */
    public function setJob(ExportJob $job)
    {
        $this->job = $job;
    }
}
