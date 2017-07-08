<?php
/**
 * CsvInitial.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Configuration\Csv;

use ExpandedForm;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use Log;

/**
 * Class CsvInitial
 *
 * @package FireflyIII\Support\Import\Configuration
 */
class Initial implements ConfigurationInterface
{
    private $job;

    /**
     * @return array
     */
    public function getData(): array
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
            'accounts'   => ExpandedForm::makeSelectList($accounts),
            'specifix'   => [],
            'delimiters' => $delimiters,
            'specifics'  => $specifics,
        ];

        return $data;
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job): ConfigurationInterface
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $importId   = $data['csv_import_account'] ?? 0;
        $account    = $repository->find(intval($importId));

        $hasHeaders                        = isset($data['has_headers']) && intval($data['has_headers']) === 1 ? true : false;
        $config                            = $this->job->configuration;
        $config['initial-config-complete'] = true;
        $config['has-headers']             = $hasHeaders;
        $config['date-format']             = $data['date_format'];
        $config['delimiter']               = $data['csv_delimiter'];
        $config['delimiter']               = $config['delimiter'] === 'tab' ? "\t" : $config['delimiter'];

        Log::debug('Entered import account.', ['id' => $importId]);


        if (!is_null($account->id)) {
            Log::debug('Found account.', ['id' => $account->id, 'name' => $account->name]);
            $config['import-account'] = $account->id;
        }

        if (is_null($account->id)) {
            Log::error('Could not find anything for csv_import_account.', ['id' => $importId]);
        }

        $config                   = $this->storeSpecifics($data, $config);
        $this->job->configuration = $config;
        $this->job->save();

        return true;
    }

    /**
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    private function storeSpecifics(array $data, array $config): array
    {
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

        return $config;
    }
}
