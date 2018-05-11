<?php
/**
 * ConfigureUploadHandlerphp
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;


/**
 * Class ConfigureUploadHandler
 *
 * @package FireflyIII\Support\Import\Configuration\File
 */
class ConfigureUploadHandler implements ConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /** @var AccountRepositoryInterface */
    private $accountRepos;

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $delimiters            = [
            ','   => trans('form.csv_comma'),
            ';'   => trans('form.csv_semicolon'),
            'tab' => trans('form.csv_tab'),
        ];
        $config                = $this->importJob->configuration;
        $config['date-format'] = $config['date-format'] ?? 'Ymd';
        $specifics             = [];
        $this->repository->setConfiguration($this->importJob, $config);

        // collect specifics.
        foreach (config('csv.import_specifics') as $name => $className) {
            $specifics[$name] = [
                'name'        => $className::getName(),
                'description' => $className::getDescription(),
            ];
        }

        $data = [
            'accounts'   => [],
            'specifix'   => [],
            'delimiters' => $delimiters,
            'specifics'  => $specifics,
        ];

        return $data;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob  = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->accountRepos->setUser($job->user);

    }

    /**
     * Store data associated with current stage.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        $config   = $this->importJob->configuration;
        $complete = true;

        // collect values:
        $importId              = isset($data['csv_import_account']) ? (int)$data['csv_import_account'] : 0;
        $delimiter             = (string)$data['csv_delimiter'];
        $config['has-headers'] = (int)($data['has_headers'] ?? 0.0) === 1;
        $config['date-format'] = (string)$data['date_format'];
        $config['delimiter']   = 'tab' === $delimiter ? "\t" : $delimiter;
        $config['apply-rules'] = (int)($data['apply_rules'] ?? 0.0) === 1;
        $config['specifics']   = $this->getSpecifics($data);
        // validate values:
        $account = $this->accountRepos->findNull($importId);

        // respond to invalid account:
        if (null === $account) {
            Log::error('Could not find anything for csv_import_account.', ['id' => $importId]);
            $complete = false;
        }
        if (null !== $account) {
            $config['import-account'] = $account->id;
        }

        $this->repository->setConfiguration($this->importJob, $config);
        if ($complete) {
            $this->repository->setStage($this->importJob, 'roles');
        }
        if (!$complete) {
            $messages = new MessageBag;
            $messages->add('account', trans('import.invalid_import_account'));

            return $messages;
        }

        return new MessageBag;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getSpecifics(array $data): array
    {
        $return = [];
        // check if specifics given are correct:
        if (isset($data['specifics']) && \is_array($data['specifics'])) {

            foreach ($data['specifics'] as $name) {
                // verify their content.
                $className = sprintf('FireflyIII\\Import\\Specifics\\%s', $name);
                if (class_exists($className)) {
                    $return[$name] = 1;
                }
            }
        }

        return $return;
    }
}
