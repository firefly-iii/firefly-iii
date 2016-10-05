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

declare(strict_types = 1);

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
    private $files;

    /**
     * BasicCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        $this->files = new Collection;
        $this->job   = $job;
    }

    /**
     * @return Collection
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    /**
     * @param Collection $files
     */
    public function setFiles(Collection $files)
    {
        $this->files = $files;
    }


}
