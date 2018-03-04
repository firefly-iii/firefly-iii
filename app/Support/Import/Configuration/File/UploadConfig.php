<?php
/**
 * UploadConfig.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Import\Configuration\File;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use Log;

/**
 * Class UploadConfig.
 */
class UploadConfig implements ConfigurationInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /**
     * @var ImportJob
     */
    private $job;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @return array
     */
    public function getData(): array
    {
        $accounts              = $this->accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $delimiters            = [
            ','   => trans('form.csv_comma'),
            ';'   => trans('form.csv_semicolon'),
            'tab' => trans('form.csv_tab'),
        ];
        $config                = $this->getConfig();
        $config['date-format'] = $config['date-format'] ?? 'Ymd';
        $specifics             = [];
        $this->saveConfig($config);

        // collect specifics.
        foreach (config('csv.import_specifics') as $name => $className) {
            $specifics[$name] = [
                'name'        => $className::getName(),
                'description' => $className::getDescription(),
            ];
        }

        $data = [
            'accounts'   => app('expandedform')->makeSelectList($accounts),
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
        $this->job               = $job;
        $this->repository        = app(ImportJobRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($job->user);
        $this->accountRepository->setUser($job->user);

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
        Log::debug('Now in Initial::storeConfiguration()');
        $config    = $this->getConfig();
        $importId  = intval($data['csv_import_account'] ?? 0);
        $account   = $this->accountRepository->find($importId);
        $delimiter = strval($data['csv_delimiter']);

        // set "headers":
        $config['has-headers'] = intval($data['has_headers'] ?? 0) === 1;
        $config['date-format'] = strval($data['date_format']);
        $config['delimiter']   = 'tab' === $delimiter ? "\t" : $delimiter;
        $config['apply-rules'] = intval($data['apply_rules'] ?? 0) === 1;
        $config['match-bills'] = intval($data['match_bills'] ?? 0) === 1;

        Log::debug('Entered import account.', ['id' => $importId]);


        if (null !== $account->id) {
            Log::debug('Found account.', ['id' => $account->id, 'name' => $account->name]);
            $config['import-account'] = $account->id;
        }

        if (null === $account->id) {
            Log::error('Could not find anything for csv_import_account.', ['id' => $importId]);
        }
        $config = $this->storeSpecifics($data, $config);
        Log::debug('Final config is ', $config);

        // onto the next stage!

        $config['stage'] = 'roles';
        $this->saveConfig($config);

        return true;
    }

    /**
     * Short hand method.
     *
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * @param array $array
     */
    private function saveConfig(array $array)
    {
        $this->repository->setConfiguration($this->job, $array);
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
