<?php
/**
 * ChooseAccountHandler.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\JobConfiguration\FinTSConfigurationSteps;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\FinTS\FinTS;
use Illuminate\Support\MessageBag;

/**
 * Class ChooseAccountHandler
 * @codeCoverageIgnore
 */
class ChooseAccountHandler implements FinTSConfigurationInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
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
        $config                  = $this->repository->getConfiguration($this->importJob);
        $config['fints_account'] = (string)($data['fints_account'] ?? '');
        $config['local_account'] = (string)($data['local_account'] ?? '');
        $config['from_date']     = (string)($data['from_date'] ?? '');
        $config['to_date']       = (string)($data['to_date'] ?? '');
        $this->repository->setConfiguration($this->importJob, $config);

        try {
            $finTS = app(FinTS::class, ['config' => $config]);
            $finTS->getAccount($config['fints_account']);
        } catch (FireflyException $e) {
            return new MessageBag([$e->getMessage()]);
        }

        $this->repository->setStage($this->importJob, FinTSConfigurationSteps::GO_FOR_IMPORT);

        return new MessageBag();
    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $finTS             = app(FinTS::class, ['config' => $this->importJob->configuration]);
        $finTSAccounts     = $finTS->getAccounts();
        $finTSAccountsData = [];
        foreach ($finTSAccounts as $account) {
            $finTSAccountsData[$account->getAccountNumber()] = $account->getIban();
        }

        $localAccounts = [];
        foreach ($this->accountRepository->getAccountsByType([AccountType::ASSET]) as $localAccount) {
            $display_name = $localAccount->name;
            if ($localAccount->iban) {
                $display_name .= sprintf(' - %s', $localAccount->iban);
            }
            $localAccounts[$localAccount->id] = $display_name;
        }

        $data = [
            'fints_accounts' => $finTSAccountsData,
            'fints_account'  => $this->importJob->configuration['fints_account'] ?? null,
            'local_accounts' => $localAccounts,
            'local_account'  => $this->importJob->configuration['local_account'] ?? null,
            'from_date'      => $this->importJob->configuration['from_date'] ?? (new Carbon('now - 1 month'))->format('Y-m-d'),
            'to_date'        => $this->importJob->configuration['to_date'] ?? (new Carbon('now'))->format('Y-m-d'),
        ];

        return $data;
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob         = $importJob;
        $this->repository        = app(ImportJobRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}
