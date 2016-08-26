<?php
/**
 * ImportProcedure.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use FireflyIII\Crud\Account\AccountCrud;
use FireflyIII\Import\Importer\ImporterInterface;
use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;

/**
 * Class ImportProcedure
 *
 * @package FireflyIII\Import
 */
class ImportProcedure
{

    /**
     * @param ImportJob $job
     *
     * @return Collection
     */
    public static function runImport(ImportJob $job): Collection
    {
        // update job to say we started.
        $job->status = 'import_running';
        $job->save();

        // create Importer
        $valid = array_keys(config('firefly.import_formats'));
        $class = 'INVALID';
        if (in_array($job->file_type, $valid)) {
            $class = config('firefly.import_formats.' . $job->file_type);
        }

        /** @var ImporterInterface $importer */
        $importer = app($class);
        $importer->setJob($job);

        // create import entries
        $collection = $importer->createImportEntries();

        // validate / clean collection:
        $validator = new ImportValidator($collection);
        $validator->setUser($job->user);
        $validator->setJob($job);
        if ($job->configuration['import-account'] != 0) {
            $repository = app(AccountCrud::class, [$job->user]);
            $validator->setDefaultImportAccount($repository->find($job->configuration['import-account']));
        }

        $cleaned = $validator->clean();

        // then import collection:
        $storage = new ImportStorage($job->user, $cleaned);
        $storage->setJob($job);

        // and run store routine:
        $result = $storage->store();

        // grab import tag:
        $status               = $job->extended_status;
        $status['importTag']  = $storage->importTag->id;
        $job->extended_status = $status;
        $job->status          = 'import_complete';
        $job->save();

        return $result;
    }

}