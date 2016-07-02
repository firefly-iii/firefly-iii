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
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use Illuminate\Http\Request;
use League\Csv\Reader;
use Log;
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
     * Create initial (empty) configuration array.
     *
     *
     *
     * @return bool
     */
    public function configure(): bool
    {
        if (is_null($this->job->configuration) || (is_array($this->job->configuration) && count($this->job->configuration) === 0)) {
            Log::debug('No config detected, will create empty one.');

            $config                   = [
                'has-headers'             => false, // assume
                'date-format'             => 'Ymd', // assume
                'delimiter'               => ',', // assume
                'import-account'          => 0, // none,
                'specifics'               => [], // none
                'column-count'            => 0, // unknown
                'column-roles'            => [], // unknown
                'column-do-mapping'       => [], // not yet set which columns must be mapped
                'column-roles-complete'   => false, // not yet configured roles for columns
                'column-mapping-config'   => [], // no mapping made yet.
                'column-mapping-complete' => false, // so mapping is not complete.
            ];
            $this->job->configuration = $config;
            $this->job->save();

            return true;
        }

        // need to do nothing, for now.
        Log::debug('Detected config in upload, will use that one. ', $this->job->configuration);

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

        if ($this->doColumnRoles()) {
            $data = $this->getDataForColumnRoles();

            return $data;
        }

        if ($this->doColumnMapping()) {
            $data = $this->getDataForColumnMapping();

            return $data;
        }

        echo 'no settings to do.';
        exit;

    }

    /**
     * This method returns the name of the view that will be shown to the user to further configure
     * the import job.
     *
     * @return string
     */
    public function getViewForSettings(): string
    {
        if ($this->doColumnRoles()) {
            return 'import.csv.roles';
        }

        if ($this->doColumnMapping()) {
            return 'import.csv.map';
        }

        echo 'no view for settings';
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
        /** @var AccountCrud $repository */
        $repository            = app(AccountCrud::class);
        $account               = $repository->find(intval($data['csv_import_account']));
        $hasHeaders            = isset($data['has_headers']) && intval($data['has_headers']) === 1 ? true : false;
        $config                = $this->job->configuration;
        $config['has-headers'] = $hasHeaders;
        $config['date-format'] = $data['date_format'];
        $config['delimiter']   = $data['csv_delimiter'];

        if (!is_null($account->id)) {
            $config['import-account'] = $account->id;
        }
        // loop specifics.
        if (isset($data['specifics']) && is_array($data['specifics'])) {
            foreach ($data['specifics'] as $name => $enabled) {
                $config['specifics'][$name] = 1;
            }
        }
        $this->job->configuration = $config;
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

    /**
     * Store the settings filled in by the user, if applicable.
     *
     * @param Request $request
     *
     */
    public function storeSettings(Request $request)
    {
        $config  = $this->job->configuration;
        $count   = $config['column-count'];
        $all     = $request->all();
        $roleSet = 0;
        for ($i = 0; $i < $count; $i++) {
            $selectedRole = $all['role'][$i] ?? '_ignore';
            $doMapping    = isset($all['map'][$i]) && $all['map'][$i] == '1' ? true : false;
            if ($selectedRole == '_ignore' && $doMapping === true) {
                $doMapping = false; // cannot map ignored columns.
            }
            if ($selectedRole != '_ignore') {
                $roleSet++;
            }
            $config['column-roles'][$i]      = $selectedRole;
            $config['column-do-mapping'][$i] = $doMapping;
        }
        if ($roleSet > 0) {
            $config['column-roles-complete'] = true;
            $this->job->configuration        = $config;
            $this->job->save();
        }
    }

    /**
     * @return bool
     */
    private function doColumnMapping(): bool
    {
        return $this->job->configuration['column-mapping-complete'] === false;
    }

    /**
     * @return bool
     */
    private function doColumnRoles(): bool
    {
        return $this->job->configuration['column-roles-complete'] === false;
    }

    /**
     * @return array
     */
    private function getDataForColumnMapping(): array
    {
        $config = $this->job->configuration;
        $data   = [];

        foreach ($config['column-do-mapping'] as $index => $mustBeMapped) {
            if ($mustBeMapped) {
                $column      = $config['column-roles'][$index] ?? '_ignore';
                $canBeMapped = config('csv.import_roles.' . $column . '.mappable');
                if ($canBeMapped) {
                    $mapperName = '\FireflyIII\Import\Mapper\\' . config('csv.import_roles.' . $column . '.mapper');
                    /** @var MapperInterface $mapper */
                    $mapper       = new $mapperName;
                    $data[$index] = [
                        'name'    => $column,
                        'mapper'  => $mapperName,
                        'options' => $mapper->getMap(),
                        'values'  => [],
                    ];
                }
            }
        }


        echo '<pre>';
        var_dump($data);
        var_dump($config);


        exit;


    }

    /**
     * @return array
     */
    private function getDataForColumnRoles():array
    {
        $config = $this->job->configuration;
        $data   = [
            'columns'     => [],
            'columnCount' => 0,
        ];

        // show user column role configuration.
        $content = $this->job->uploadFileContents();

        // create CSV reader.
        $reader = Reader::createFromString($content);
        $start  = $config['has-headers'] ? 1 : 0;
        $end    = $start + self::EXAMPLE_ROWS; // first X rows

        // collect example data in $data['columns']
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

        // make unique example data
        foreach ($data['columns'] as $index => $values) {
            $data['columns'][$index] = array_unique($values);
        }

        $data['set_roles'] = [];
        // collect possible column roles:
        $data['available_roles'] = [];
        foreach (array_keys(config('csv.import_roles')) as $role) {
            $data['available_roles'][$role] = trans('csv.column_' . $role);
        }

        $config['column-count']   = $data['columnCount'];
        $this->job->configuration = $config;
        $this->job->save();

        return $data;


    }
}