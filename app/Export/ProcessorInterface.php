<?php
/**
 * ProcessorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export;

use Illuminate\Support\Collection;

/**
 * Interface ProcessorInterface
 *
 * @package FireflyIII\Export
 */
interface ProcessorInterface
{

    /**
     * Processor constructor.
     */
    public function __construct();

    /**
     * @return bool
     */
    public function collectAttachments(): bool;

    /**
     * @return bool
     */
    public function collectJournals(): bool;

    /**
     * @return bool
     */
    public function collectOldUploads(): bool;

    /**
     * @return bool
     */
    public function convertJournals(): bool;

    /**
     * @return bool
     */
    public function createZipFile(): bool;

    /**
     * @return bool
     */
    public function exportJournals(): bool;

    /**
     * @return Collection
     */
    public function getFiles(): Collection;

    /**
     * @param array $settings
     */
    public function setSettings(array $settings);
}
