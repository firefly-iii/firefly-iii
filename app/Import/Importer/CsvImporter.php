<?php
/**
 * CsvImporter.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Importer;

use FireflyIII\Crud\Account\AccountCrud;
use FireflyIII\Import\Converter\ConverterInterface;
use FireflyIII\Import\ImportEntry;
use FireflyIII\Import\ImportResult;
use FireflyIII\Models\Account;
use FireflyIII\Models\ImportJob;
use League\Csv\Reader;
use Log;

/**
 * Class CsvImporter
 *
 * @package FireflyIII\Import\Importer
 */
class CsvImporter implements ImporterInterface
{
    /** @var  ImportJob */
    public $job;

    /** @var  Account */
    public $defaultImportAccount;

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     * Run the actual import
     *
     * @return bool
     */
    public function start(): bool
    {
        $config  = $this->job->configuration;
        $content = $this->job->uploadFileContents();

        if ($config['import-account'] != 0) {
            $repository                 = app(AccountCrud::class, [$this->job->user]);
            $this->defaultImportAccount = $repository->find($config['import-account']);
        }

        // create CSV reader.
        $reader  = Reader::createFromString($content);
        $start   = $config['has-headers'] ? 1 : 0;
        $results = $reader->fetch();
        foreach ($results as $index => $row) {
            if ($index >= $start) {
                Log::debug(sprintf('Now going to import row %d.', $index));
                $this->importSingleRow($index, $row);
            }
        }

        Log::debug('This call should be intercepted somehow.');

        return true;
    }


    /**
     * @param int   $index
     * @param array $row
     *
     * @return ImportResult
     */
    private function importSingleRow(int $index, array $row): ImportResult
    {
        // create import object:
        $object = new ImportEntry;

        // set some vars:
        $object->setUser($this->job->user);
        $config = $this->job->configuration;

        foreach ($row as $index => $value) {
            // find the role for this column:
            $role           = $config['column-roles'][$index] ?? '_ignore';
            $doMap          = $config['column-do-mapping'][$index] ?? false;
            $converterClass = config('csv.import_roles.' . $role . '.converter');
            $mapping        = $config['column-mapping-config'][$index] ?? [];
            /** @var ConverterInterface $converter */
            $converter = app('FireflyIII\\Import\\Converter\\' . $converterClass);
            // set some useful values for the converter:
            $converter->setMapping($mapping);
            $converter->setDoMap($doMap);
            $converter->setUser($this->job->user);
            $converter->setConfig($config);

            // run the converter for this value:
            $convertedValue = $converter->convert($value);
            $certainty      = $converter->getCertainty();

            // log it.
            Log::debug('Value ', ['index' => $index, 'value' => $value, 'role' => $role]);

            // store in import entry:
            $object->importValue($role, $value, $certainty, $convertedValue);
        }

        $result = $object->import();
        if ($result->failed()) {
            Log::error(sprintf('Import of row %d has failed.', $index), $result->errors->toArray());
        }

        exit;

        return true;
    }
}