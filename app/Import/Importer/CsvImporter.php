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


use ExpandedForm;
use FireflyIII\Crud\Account\AccountCrud;
use FireflyIII\Import\Role\Map;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;

/**
 * Class CsvImporter
 *
 * @package FireflyIII\Import\Importer
 */
class CsvImporter implements ImporterInterface
{
    /** @var  ImportJob */
    public $job;

    /**
     * @return bool
     */
    public function configure(): bool
    {
        // need to do nothing, for now.

        return true;
    }

    /**
     * @return array
     */
    public function getConfigurationData(): array
    {
        $crud       = app('FireflyIII\Crud\Account\AccountCrudInterface');
        $accounts   = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $delimiters = [
            ','   => trans('form.csv_comma'),
            ';'   => trans('form.csv_semicolon'),
            'tab' => trans('form.csv_tab'),
        ];

        $specifics = [];

        // collect specifics.
        foreach (config('firefly.csv_import_specifics') as $name => $className) {
            $specifics[$name] = [
                'name'        => $className::getName(),
                'description' => $className::getDescription(),
            ];
        }

        $data = [
            'accounts'           => ExpandedForm::makeSelectList($accounts),
            'specifix'           => [],
            'delimiters'         => $delimiters,
            'upload_path'        => storage_path('upload'),
            'is_upload_possible' => is_writable(storage_path('upload')),
            'specifics'          => $specifics,
        ];

        return $data;
    }

    /**
     * Returns a Map thing used to allow the user to
     * define roles for each entry.
     *
     * @return Map
     */
    public function prepareRoles(): Map
    {
        return 'do not work';
        exit;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data): bool
    {
        /** @var AccountCrud $repository */
        $repository    = app(AccountCrud::class);
        $account       = $repository->find(intval($data['csv_import_account']));
        $configuration = [
            'date_format'        => $data['date_format'],
            'csv_delimiter'      => $data['csv_delimiter'],
            'csv_import_account' => 0,
            'specifics'          => [],
        ];

        if (!is_null($account->id)) {
            $configuration['csv_import_account'] = $account->id;
        }
        // loop specifics.
        if (is_array($data['specifics'])) {
            foreach ($data['specifics'] as $name => $enabled) {
                $configuration['specifics'][] = $name;
            }
        }
        $this->job->configuration = $configuration;
        $this->job->save();

        return true;


    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
    }
}