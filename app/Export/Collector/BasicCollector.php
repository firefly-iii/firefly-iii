<?php
/**
 * BasicCollector.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
    /** @var Collection */
    private $files;

    /** @var ExportJob */
    protected $job;

    /**
     * BasicCollector constructor.
     */
    public function __construct(ExportJob $job)
    {
        $this->files = new Collection;
        $this->job   = $job;
    }

    /**
     * @return Collection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param Collection $files
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }


}