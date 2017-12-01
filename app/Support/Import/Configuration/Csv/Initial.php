<?php
/**
 * Initial.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
 * Class CsvInitial.
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
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return '';
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

        $hasHeaders                        = isset($data['has_headers']) && 1 === intval($data['has_headers']) ? true : false;
        $config                            = $this->job->configuration;
        $config['initial-config-complete'] = true;
        $config['has-headers']             = $hasHeaders;
        $config['date-format']             = $data['date_format'];
        $config['delimiter']               = $data['csv_delimiter'];
        $config['delimiter']               = 'tab' === $config['delimiter'] ? "\t" : $config['delimiter'];
        $config['apply_rules']             = isset($data['apply_rules']) && 1 === intval($data['apply_rules']) ? true : false;
        $config['match_bills']             = isset($data['match_bills']) && 1 === intval($data['match_bills']) ? true : false;

        Log::debug('Entered import account.', ['id' => $importId]);

        if (null !== $account->id) {
            Log::debug('Found account.', ['id' => $account->id, 'name' => $account->name]);
            $config['import-account'] = $account->id;
        }

        if (null === $account->id) {
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
            $names = array_keys($data['specifics']);
            foreach ($names as $name) {
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
