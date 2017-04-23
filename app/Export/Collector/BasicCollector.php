<?php
/**
 * BasicCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Collector;


use FireflyIII\Models\ExportJob;
use Illuminate\Support\Collection;

/**
 * Class BasicCollector
 *
 * @package FireflyIII\Export\Collector
 */
class BasicCollector
{
    /** @var ExportJob */
    protected $job;
    /** @var Collection */
    private $entries;

    /**
     * BasicCollector constructor.
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
