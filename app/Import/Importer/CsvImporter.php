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
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Class CsvImporter
 *
 * @package FireflyIII\Import\Importer
 */
class CsvImporter implements ImporterInterface
{
    const EXAMPLE_ROWS = 5;
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
        foreach (config('csv.import_specifics') as $name => $className) {
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
     * This method returns the data required for the view that will let the user add settings to the import job.
     *
     * @return array
     */
    public function getDataForSettings(): array
    {
        $config = $this->job->configuration;
        $data   = [
            'columns'     => [],
            'columnCount' => 0,
        ];

        if (!isset($config['columns'])) {

            // show user column configuration.
            $content = $this->job->uploadFileContents();

            // create CSV reader.
            $reader = Reader::createFromString($content);
            $start  = $config['has_headers'] ? 1 : 0;
            $end    = $start + self::EXAMPLE_ROWS; // first X rows
            while ($start < $end) {
                $row = $reader->fetchOne($start);
                foreach ($row as $index => $value) {
                    $value = trim($value);
                    if (strlen($value) > 0) {
                        $data['columns'][$index][] = $value;
                    }
                }
                $start++;
                $data['columnCount'] = count($row);
            }

            // make unique
            foreach ($data['columns'] as $index => $values) {
                $data['columns'][$index] = array_unique($values);
            }
            // TODO preset roles from config
            $data['set_roles'] = [];
            // collect possible column roles:
            $data['available_roles'] = [];
            foreach (array_keys(config('csv.import_roles')) as $role) {
                $data['available_roles'][$role] = trans('csv.csv_column_'.$role);
            }

            return $data;
        }

    }

    /**
     * This method returns the name of the view that will be shown to the user to further configure
     * the import job.
     *
     * @return string
     */
    public function getViewForSettings(): string
    {
        return 'import.csv.map';
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
     * This method returns whether or not the user must configure this import
     * job further.
     *
     * @return bool
     */
    public function requireUserSettings(): bool
    {
        // does the job have both a 'map' array and a 'columns' array.
        $config = $this->job->configuration;
        if (isset($config['map']) && isset($config['columns'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data, FileBag $files): bool
    {
        /*
         * TODO file upload is ignored for now.
         */

        /** @var AccountCrud $repository */
        $repository    = app(AccountCrud::class);
        $account       = $repository->find(intval($data['csv_import_account']));
        $hasHeaders    = isset($data['has_headers']) && intval($data['has_headers']) === 1 ? true : false;
        $configuration = [
            'has_headers'        => $hasHeaders,
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