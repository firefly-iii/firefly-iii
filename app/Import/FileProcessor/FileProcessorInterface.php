<?php
/**
 * FileProcessorInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\FileProcessor;

use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;

/**
 * Interface FileProcessorInterface
 *
 * @package FireflyIII\Import\FileProcessor
 */
interface FileProcessorInterface
{
    /**
     * FileProcessorInterface constructor.
     *
     * @param ImportJob $job
     */
    public function __construct(ImportJob $job);

    /**
     * @return bool
     */
    public function run(): bool;

    /**
     * @return Collection
     */
    public function getObjects(): Collection;
}