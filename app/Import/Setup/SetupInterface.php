<?php
/**
 * SetupInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Setup;

use FireflyIII\Models\ImportJob;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Interface SetupInterface
 *
 * @package FireflyIII\Import\Setup
 */
interface SetupInterface
{

    /**
     * After uploading, and after setJob(), prepare anything that is
     * necessary for the configure() line.
     *
     * @return bool
     */
    public function configure(): bool;

    /**
     * Returns any data necessary to do the configuration.
     *
     * @return array
     */
    public function getConfigurationData(): array;

    /**
     * This method returns the data required for the view that will let the user add settings to the import job.
     *
     * @return array
     */
    public function getDataForSettings(): array;

    /**
     * This method returns the name of the view that will be shown to the user to further configure
     * the import job.
     *
     * @return string
     */
    public function getViewForSettings(): string;

    /**
     * This method returns whether or not the user must configure this import
     * job further.
     *
     * @return bool
     */
    public function requireUserSettings(): bool;

    /**
     * @param array   $data
     *
     * @param FileBag $files
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data, FileBag $files): bool;

    /**
     * @param ImportJob $job
     *
     */
    public function setJob(ImportJob $job);

    /**
     * Store the settings filled in by the user, if applicable.
     *
     * @param Request $request
     *
     */
    public function storeSettings(Request $request);
}
