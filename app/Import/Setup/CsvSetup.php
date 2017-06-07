<?php
/**
 * CsvSetup.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Setup;


use ExpandedForm;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Import\CsvImportSupportTrait;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Class CsvSetup
 *
 * @package FireflyIII\Import\Importer
 */
class CsvSetup implements SetupInterface
{
    use CsvImportSupportTrait;
    /** @var  Account */
    public $defaultImportAccount;
    /** @var  ImportJob */
    public $job;

    /**
     * CsvImporter constructor.
     */
    public function __construct()
    {
        $this->defaultImportAccount = new Account;
    }

    /**
     * Create initial (empty) configuration array.
     *
     * @return bool
     */
    public function configure(): bool
    {
        if (is_null($this->job->configuration) || (is_array($this->job->configuration) && count($this->job->configuration) === 0)) {
            Log::debug('No config detected, will create empty one.');
            $this->job->configuration = config('csv.default_config');
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
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $delimiters        = [
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
        Log::debug('Now in getDataForSettings()');
        if ($this->doColumnRoles()) {
            Log::debug('doColumnRoles() is true.');
            $data = $this->getDataForColumnRoles();

            return $data;
        }

        if ($this->doColumnMapping()) {
            Log::debug('doColumnMapping() is true.');
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
     * @throws FireflyException
     */
    public function getViewForSettings(): string
    {
        if ($this->doColumnRoles()) {
            return 'import.csv.roles';
        }

        if ($this->doColumnMapping()) {
            return 'import.csv.map';
        }
        throw new FireflyException('There is no view for the current CSV import step.');
    }

    /**
     * This method returns whether or not the user must configure this import
     * job further.
     *
     * @return bool
     */
    public function requireUserSettings(): bool
    {
        Log::debug(sprintf('doColumnMapping is %s', $this->doColumnMapping()));
        Log::debug(sprintf('doColumnRoles is %s', $this->doColumnRoles()));
        if ($this->doColumnMapping() || $this->doColumnRoles()) {
            Log::debug('Return true');

            return true;
        }
        Log::debug('Return false');

        return false;
    }

    /**
     * @param array   $data
     *
     * @param FileBag $files
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data, FileBag $files): bool
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $importId   = $data['csv_import_account'] ?? 0;
        $account    = $repository->find(intval($importId));

        $hasHeaders            = isset($data['has_headers']) && intval($data['has_headers']) === 1 ? true : false;
        $config                = $this->job->configuration;
        $config['has-headers'] = $hasHeaders;
        $config['date-format'] = $data['date_format'];
        $config['delimiter']   = $data['csv_delimiter'];
        $config['delimiter']   = $config['delimiter'] === 'tab' ? "\t" : $config['delimiter'];

        Log::debug('Entered import account.', ['id' => $importId]);


        if (!is_null($account->id)) {
            Log::debug('Found account.', ['id' => $account->id, 'name' => $account->name]);
            $config['import-account'] = $account->id;
        } else {
            Log::error('Could not find anything for csv_import_account.', ['id' => $importId]);
        }
        // loop specifics.
        if (isset($data['specifics']) && is_array($data['specifics'])) {
            foreach ($data['specifics'] as $name => $enabled) {
                // verify their content.
                $className = sprintf('FireflyIII\Import\Specifics\%s', $name);
                if (class_exists($className)) {
                    $config['specifics'][$name] = 1;
                }
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
}
