<?php
declare(strict_types = 1);
/**
 * ConfigurationFile.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use FireflyIII\Export\Entry\Entry;
use FireflyIII\Models\ExportJob;
use Storage;

/**
 * Class ConfigurationFile
 *
 * @package FireflyIII\Export
 */
class ConfigurationFile
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    /** @var  ExportJob */
    private $job;

    /**
     * ConfigurationFile constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        $this->job        = $job;
        $this->exportDisk = Storage::disk('export');
    }

    /**
     * @return string
     */
    public function make(): string
    {
        $fields = array_keys(Entry::getFieldsAndTypes());
        $types  = Entry::getFieldsAndTypes();

        $configuration = [
            'date-format' => 'Y-m-d', // unfortunately, this is hard-coded.
            'has-headers' => true,
            'map'         => [], // we could build a map if necessary for easy re-import.
            'roles'       => [],
            'mapped'      => [],
            'specifix'    => [],
        ];
        foreach ($fields as $field) {
            $configuration['roles'][] = $types[$field];
        }
        $file = $this->job->key . '-configuration.json';
        $this->exportDisk->put($file, json_encode($configuration, JSON_PRETTY_PRINT));

        return $file;
    }

}
