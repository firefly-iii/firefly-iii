<?php
/**
 * StageImportDataHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Import\Routine\Spectre;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account as LocalAccount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account as SpectreAccount;
use FireflyIII\Services\Spectre\Object\Transaction as SpectreTransaction;
use FireflyIII\Services\Spectre\Request\ListTransactionsRequest;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use Log;

/**
 * Class StageImportDataHandler
 *
 */
class StageImportDataHandler
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var ImportJob */
    private $importJob;
    /** @var OpposingAccountMapper */
    private $mapper;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in StageImportDataHandler::run()');
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        Log::debug(sprintf('Count of accounts in array is %d', count($accounts)));
        if (0 === count($accounts)) {
            throw new FireflyException('There are no accounts in this import job. Cannot continue.'); // @codeCoverageIgnore
        }
        $toImport = $config['account_mapping'] ?? [];
        $totalSet = [[]];
        foreach ($toImport as $spectreId => $localId) {
            if ((int)$localId > 0) {
                Log::debug(sprintf('Will get transactions from Spectre account #%d and save them in Firefly III account #%d', $spectreId, $localId));
                $spectreAccount = $this->getSpectreAccount((int)$spectreId);
                $localAccount   = $this->getLocalAccount((int)$localId);
                $merge          = $this->getTransactions($spectreAccount, $localAccount);
                $totalSet[]     = $merge;
                Log::debug(
                    sprintf('Found %d transactions in account "%s" (%s)', count($merge), $spectreAccount->getName(), $spectreAccount->getCurrencyCode())
                );
                continue;
            }
            Log::debug(sprintf('Local account is = zero, will not import from Spectr account with ID #%d', $spectreId));
        }
        $totalSet = array_merge(...$totalSet);
        Log::debug(sprintf('Found %d transactions in total.', count($totalSet)));

        $this->repository->setTransactions($this->importJob, $totalSet);
    }


    /**
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob         = $importJob;
        $this->repository        = app(ImportJobRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->mapper            = app(OpposingAccountMapper::class);
        $this->accountRepository->setUser($importJob->user);
        $this->repository->setUser($importJob->user);
        $this->mapper->setUser($importJob->user);
    }

    /**
     * @param array          $transactions
     * @param SpectreAccount $spectreAccount
     * @param LocalAccount   $originalSource
     *
     * @return array
     *
     */
    private function convertToArray(array $transactions, SpectreAccount $spectreAccount, LocalAccount $originalSource): array
    {
        $array = [];
        $total = count($transactions);
        Log::debug(sprintf('Now in StageImportDataHandler::convertToArray() with count %d', count($transactions)));
        /** @var SpectreTransaction $transaction */
        foreach ($transactions as $index => $transaction) {
            Log::debug(sprintf('Now creating array for transaction %d of %d', $index + 1, $total));
            $extra = [];
            if (null !== $transaction->getExtra()) {
                $extra = $transaction->getExtra()->toArray();
            }
            $destinationData     = $transaction->getOpposingAccountData();
            $amount              = $transaction->getAmount();
            $source              = $originalSource;
            $destination         = $this->mapper->map(null, $amount, $destinationData);
            $notes               = trans('import.imported_from_account', ['account' => $spectreAccount->getName()]) . '  ' . "\n";
            $foreignAmount       = null;
            $foreignCurrencyCode = null;

            $currencyCode = $transaction->getCurrencyCode();
            $type         = 'withdrawal';
            // switch source and destination if amount is greater than zero.
            if (1 === bccomp($amount, '0')) {
                [$source, $destination] = [$destination, $source];
                $type = 'deposit';
            }

            Log::debug(sprintf('Mapped destination to #%d ("%s")', $destination->id, $destination->name));
            Log::debug(sprintf('Set source to #%d ("%s")', $source->id, $source->name));

            // put some data in tags:
            $tags   = [];
            $tags[] = $transaction->getMode();
            $tags[] = $transaction->getStatus();
            if ($transaction->isDuplicated()) {
                $tags[] = 'possibly-duplicated';
            }

            // get extra fields:
            foreach ($extra as $key => $value) {
                if ('' === (string)$value) {
                    continue;
                }
                switch ($key) {
                    case 'original_category':
                    case 'original_subcategory':
                    case 'customer_category_code':
                    case 'customer_category_name':
                        $tags[] = $value;
                        break;
                    case 'original_amount':
                        $foreignAmount = $value;
                        Log::debug(sprintf('Foreign amount is now %s', $value));
                        break;
                    case 'original_currency_code':
                        $foreignCurrencyCode = $value;
                        Log::debug(sprintf('Foreign currency code is now %s', $value));
                        break;
                    default:
                        $notes .= $key . ': ' . $value . '  ' . "\n"; // for newline in Markdown.
                }
            }

            $entry   = [
                // transaction data:
                'transactions'    => [
                    [
                        'date'            => $transaction->getMadeOn()->format('Y-m-d'),
                        'tags'            => $tags,
                        'user'            => $this->importJob->user_id,
                        'notes'           => $notes,

                        // all custom fields:
                        'external_id'     => (string)$transaction->getId(),

                        // journal data:
                        'description'     => $transaction->getDescription(),
                        'piggy_bank_id'   => null,
                        'piggy_bank_name' => null,
                        'bill_id'         => null,
                        'bill_name'       => null,
                        'original-source' => sprintf('spectre-v%s', config('firefly.version')),
                        'type'            => $type,
                        'currency_id'     => null,
                        'currency_code'   => $currencyCode,
                        'amount'          => $amount,
                        'budget_id'       => null,
                        'budget_name'     => null,
                        'category_id'     => null,
                        'category_name'   => $transaction->getCategory(),
                        'source_id'       => $source->id,
                        'source_name'           => null,
                        'destination_id'        => $destination->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => $foreignCurrencyCode,
                        'foreign_amount'        => $foreignAmount,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];
            $array[] = $entry;
        }
        Log::debug(sprintf('Return %d entries', count($array)));

        return $array;
    }

    /**
     * @param int $accountId
     *
     * @return LocalAccount
     * @throws FireflyException
     */
    private function getLocalAccount(int $accountId): LocalAccount
    {
        $account = $this->accountRepository->findNull($accountId);
        if (null === $account) {
            throw new FireflyException(sprintf('Cannot find Firefly III asset account with ID #%d. Job must stop now.', $accountId)); // @codeCoverageIgnore
        }
        if (!in_array($account->accountType->type, [AccountType::ASSET, AccountType::LOAN, AccountType::MORTGAGE, AccountType::DEBT], true)) {
            throw new FireflyException(
                sprintf('Account with ID #%d is not an asset/loan/mortgage/debt account. Job must stop now.', $accountId)
            ); // @codeCoverageIgnore
        }

        return $account;
    }

    /**
     * @param int $accountId
     *
     * @return SpectreAccount
     * @throws FireflyException
     */
    private function getSpectreAccount(int $accountId): SpectreAccount
    {
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        foreach ($accounts as $account) {
            $spectreId = (int)($account['id'] ?? 0.0);
            if ($spectreId === $accountId) {
                return new SpectreAccount($account);
            }
        }
        throw new FireflyException(sprintf('Cannot find Spectre account with ID #%d in configuration. Job will exit.', $accountId)); // @codeCoverageIgnore
    }

    /**
     * @param SpectreAccount $spectreAccount
     * @param LocalAccount   $localAccount
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactions(SpectreAccount $spectreAccount, LocalAccount $localAccount): array
    {
        // grab all transactions
        /** @var ListTransactionsRequest $request */
        $request = app(ListTransactionsRequest::class);
        $request->setUser($this->importJob->user);

        $request->setAccount($spectreAccount);
        $request->call();

        $transactions = $request->getTransactions();

        return $this->convertToArray($transactions, $spectreAccount, $localAccount);
    }


}
