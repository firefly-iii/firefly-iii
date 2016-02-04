<?php
/**
 * BasicExporter.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Exporter;


use FireflyIII\Models\ExportJob;
use Illuminate\Support\Collection;

/**
 * Class BasicExporter
 *
 * @package FireflyIII\Export\Exporter
 */
class BasicExporter
{
    private $entries;

    /** @var  ExportJob */
    protected $job;

    /**
     * BasicExporter constructor.
     */
    public function __construct(ExportJob $job)
    {
        $this->entries = new Collection;
        $this->job = $job;
    }

    /**
     * @return Collection
     */
    public function getEntries()
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



}