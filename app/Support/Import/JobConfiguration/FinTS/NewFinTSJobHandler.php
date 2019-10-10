<?php
/**
 * NewFinTSJobHandler.php
 * Copyright (c) 2019 https://github.com/bnw
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);


namespace FireflyIII\Support\Import\JobConfiguration\FinTS;


use FireflyIII\Import\JobConfiguration\FinTSConfigurationSteps;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\FinTS\FinTS;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\MessageBag;

/**
 * Class NewFinTSJobHandler
 * @codeCoverageIgnore
 */
class NewFinTSJobHandler implements FinTSConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Store data associated with current stage.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        $config = [];

        $config['fints_url']       = trim($data['fints_url'] ?? '');
        $config['fints_port']      = (int)($data['fints_port'] ?? '');
        $config['fints_bank_code'] = (string)($data['fints_bank_code'] ?? '');
        $config['fints_username']  = (string)($data['fints_username'] ?? '');
        $config['fints_password']  = (string)(Crypt::encrypt($data['fints_password']) ?? ''); // verified
        $config['apply-rules']     = 1 === (int)($data['apply_rules'] ?? 0);

        // sanitize FinTS URL.
        $config['fints_url'] = $this->validURI($config['fints_url']) ? $config['fints_url'] : '';

        $this->repository->setConfiguration($this->importJob, $config);

        $incomplete = false;
        foreach ($config as $value) {
            $incomplete = '' === $value or $incomplete;
        }

        if ($incomplete) {
            return new MessageBag([trans('import.incomplete_fints_form')]);
        }
        $finTS = app(FinTS::class, ['config' => $this->importJob->configuration]);
        if (true !== ($checkConnection = $finTS->checkConnection())) {
            return new MessageBag([trans('import.fints_connection_failed', ['originalError' => $checkConnection])]);
        }

        $this->repository->setStage($this->importJob, FinTSConfigurationSteps::CHOOSE_ACCOUNT);

        return new MessageBag();
    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $config = $this->importJob->configuration;

        return [
            'fints_url'       => $config['fints_url'] ?? '',
            'fints_port'      => $config['fints_port'] ?? '443',
            'fints_bank_code' => $config['fints_bank_code'] ?? '',
            'fints_username'  => $config['fints_username'] ?? '',
        ];
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }

    /**
     * @param string $fints_url
     *
     * @return bool
     */
    private function validURI(string $fintsUri): bool
    {
        $res = filter_var($fintsUri, FILTER_VALIDATE_URL);
        if (false === $res) {
            return false;
        }
        $scheme = parse_url($fintsUri, PHP_URL_SCHEME);

        return 'https' === $scheme;
    }


}
