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
use FireflyIII\Import\Converter\ConverterInterface;
use FireflyIII\Import\ImportEntry;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Import\MapperPreProcess\PreProcessorInterface;
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
        Log::debug('doColumnMapping is ' . ($this->doColumnMapping() ? 'true' : 'false'));
        Log::debug('doColumnRoles is ' . ($this->doColumnRoles() ? 'true' : 'false'));
        if ($this->doColumnMapping() || $this->doColumnRoles()) {
            Log::debug('Return true');

            return true;
        }
        Log::debug('Return false');

        return false;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data, FileBag $files): bool
    {
        /** @var AccountCrud $repository */
        $repository = app(AccountCrud::class, [auth()->user()]);
        $account    = $repository->find(intval($data['csv_import_account']));

        $hasHeaders            = isset($data['has_headers']) && intval($data['has_headers']) === 1 ? true : false;
        $config                = $this->job->configuration;
        $config['has-headers'] = $hasHeaders;
        $config['date-format'] = $data['date_format'];
        $config['delimiter']   = $data['csv_delimiter'];

        Log::debug('Entered import account.', ['id' => $data['csv_import_account']]);


        if (!is_null($account->id)) {
            Log::debug('Found account.', ['id' => $account->id, 'name' => $account->name]);
            $config['import-account'] = $account->id;
        } else {
            Log::error('Could not find anything for csv_import_account.', ['id' => $data['csv_import_account']]);
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
     * Run the actual import
     *
     * @return bool
     */
    public function start(): bool
    {
        $config  = $this->job->configuration;
        $content = $this->job->uploadFileContents();

        // create CSV reader.
        $reader  = Reader::createFromString($content);
        $start   = $config['has-headers'] ? 1 : 0;
        $results = $reader->fetch();
        foreach ($results as $index => $row) {
            if ($index >= $start) {
                Log::debug(sprintf('Now going to import row %d.', $index));
                $this->importSingleRow($row);
            }
        }

        Log::debug('This call should be intercepted somehow.');

        return true;
    }

    /**
     * Store the settings filled in by the user, if applicable.
     *
     * @param Request $request
     *
     */
    public function storeSettings(Request $request)
    {
        $config = $this->job->configuration;
        $all    = $request->all();
        if ($request->get('settings') == 'roles') {
            $count = $config['column-count'];

            $roleSet = 0; // how many roles have been defined
            $mapSet  = 0;  // how many columns must be mapped
            for ($i = 0; $i < $count; $i++) {
                $selectedRole = $all['role'][$i] ?? '_ignore';
                $doMapping    = isset($all['map'][$i]) && $all['map'][$i] == '1' ? true : false;
                if ($selectedRole == '_ignore' && $doMapping === true) {
                    $doMapping = false; // cannot map ignored columns.
                }
                if ($selectedRole != '_ignore') {
                    $roleSet++;
                }
                if ($doMapping === true) {
                    $mapSet++;
                }
                $config['column-roles'][$i]      = $selectedRole;
                $config['column-do-mapping'][$i] = $doMapping;
            }
            if ($roleSet > 0) {
                $config['column-roles-complete'] = true;
                $this->job->configuration        = $config;
                $this->job->save();
            }
            if ($mapSet === 0) {
                // skip setting of map:
                $config['column-mapping-complete'] = true;
            }
        }
        if ($request->get('settings') == 'map') {
            if (isset($all['mapping'])) {
                foreach ($all['mapping'] as $index => $data) {
                    $config['column-mapping-config'][$index] = [];
                    foreach ($data as $value => $mapId) {
                        $mapId = intval($mapId);
                        if ($mapId !== 0) {
                            $config['column-mapping-config'][$index][$value] = intval($mapId);
                        }
                    }
                }
            }

            // set thing to be completed.
            $config['column-mapping-complete'] = true;
            $this->job->configuration          = $config;
            $this->job->save();
        }
    }

    /**
     * @return bool
     */
    private function doColumnMapping(): bool
    {
        $mapArray = $this->job->configuration['column-do-mapping'] ?? [];
        $doMap    = false;
        foreach ($mapArray as $value) {
            if ($value === true) {
                $doMap = true;
                break;
            }
        }

        return $this->job->configuration['column-mapping-complete'] === false && $doMap;
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
        $config  = $this->job->configuration;
        $data    = [];
        $indexes = [];

        foreach ($config['column-do-mapping'] as $index => $mustBeMapped) {
            if ($mustBeMapped) {
                $column        = $config['column-roles'][$index] ?? '_ignore';
                $canBeMapped   = config('csv.import_roles.' . $column . '.mappable');
                $preProcessMap = config('csv.import_roles.' . $column . '.pre-process-map');
                if ($canBeMapped) {
                    $mapperName = '\FireflyIII\Import\Mapper\\' . config('csv.import_roles.' . $column . '.mapper');
                    /** @var MapperInterface $mapper */
                    $mapper       = new $mapperName;
                    $indexes[]    = $index;
                    $data[$index] = [
                        'name'          => $column,
                        'mapper'        => $mapperName,
                        'index'         => $index,
                        'options'       => $mapper->getMap(),
                        'preProcessMap' => null,
                        'values'        => [],
                    ];
                    if ($preProcessMap) {
                        $data[$index]['preProcessMap'] = '\FireflyIII\Import\MapperPreProcess\\' .
                                                         config('csv.import_roles.' . $column . '.pre-process-mapper');
                    }
                }

            }
        }

        // in order to actually map we also need all possible values from the CSV file.
        $content = $this->job->uploadFileContents();
        $reader  = Reader::createFromString($content);
        $results = $reader->fetch();

        foreach ($results as $rowIndex => $row) {
            //do something here
            foreach ($indexes as $index) { // this is simply 1, 2, 3, etc.
                $value = $row[$index];
                if (strlen($value) > 0) {

                    // we can do some preprocessing here,
                    // which is exclusively to fix the tags:
                    if (!is_null($data[$index]['preProcessMap'])) {
                        /** @var PreProcessorInterface $preProcessor */
                        $preProcessor           = app($data[$index]['preProcessMap']);
                        $result                 = $preProcessor->run($value);
                        $data[$index]['values'] = array_merge($data[$index]['values'], $result);

                        Log::debug($rowIndex . ':' . $index . 'Value before preprocessor', ['value' => $value]);
                        Log::debug($rowIndex . ':' . $index . 'Value after preprocessor', ['value-new' => $result]);
                        Log::debug($rowIndex . ':' . $index . 'Value after joining', ['value-complete' => $data[$index]['values']]);


                        continue;
                    }

                    $data[$index]['values'][] = $value;
                }
            }
        }
        foreach ($data as $index => $entry) {
            $data[$index]['values'] = array_unique($data[$index]['values']);
        }

        return $data;
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

    /**
     * @param array $row
     *
     * @return bool
     */
    private function importSingleRow(array $row): bool
    {
        $object = new ImportEntry;
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
            // $object->fromRawValue($role, $value);


        }

        exit;

        return true;
    }
}