<?php
/**
 * ImportDataHandler.php
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

namespace FireflyIII\Support\Import\Routine\Ynab;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Ynab\Request\GetTransactionsRequest;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use Log;

/**
 * Class ImportDataHandler
 */
class ImportDataHandler
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var TransactionCurrency */
    private $defaultCurrency;
    /** @var ImportJob */
    private $importJob;
    /** @var OpposingAccountMapper */
    private $mapper;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Get list of accounts for the selected budget.
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        $config                = $this->repository->getConfiguration($this->importJob);
        $this->defaultCurrency = app('amount')->getDefaultCurrencyByUser($this->importJob->user);
        $token                 = $config['access_token'];
        // make request for each mapping:
        $mapping = $config['mapping'] ?? [];
        $total   = [[]];

        /**
         * @var string $ynabId
         * @var string $localId
         */
        foreach ($mapping as $ynabId => $localId) {
            $localAccount = $this->getLocalAccount((int)$localId);
            $transactions = $this->getTransactions($token, $ynabId);
            $converted    = $this->convertToArray($transactions, $localAccount);
            $total[]      = $converted;
        }

        $totalSet = array_merge(...$total);
        Log::debug(sprintf('Found %d transactions in total.', count($totalSet)));
        $this->repository->setTransactions($this->importJob, $totalSet);

        // assuming this works, store today's date as a preference
        // (combined with the budget from which FF3 imported)
        $budgetId = $this->getSelectedBudget()['id'] ?? '';
        if ('' !== $budgetId) {
            app('preferences')->set('ynab_' . $budgetId, Carbon::now()->format('Y-m-d'));
        }
    }

    /**
     * @param ImportJob $importJob
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
     * @param array   $transactions
     * @param Account $localAccount
     *
     * @return array
     * @throws FireflyException
     */
    private function convertToArray(array $transactions, Account $localAccount): array
    {
        $config = $this->repository->getConfiguration($this->importJob);
        $array  = [];
        $total  = count($transactions);
        $budget = $this->getSelectedBudget();
        Log::debug(sprintf('Now in StageImportDataHandler::convertToArray() with count %d', count($transactions)));
        /** @var array $transaction */
        foreach ($transactions as $index => $transaction) {
            $description = $transaction['memo'] ?? '(empty)';
            Log::debug(sprintf('Now creating array for transaction %d of %d ("%s")', $index + 1, $total, $description));
            $amount = (string)($transaction['amount'] ?? 0);
            if ('0' === $amount) {
                Log::debug(sprintf('Amount is zero (%s), skip this transaction.', $amount));
                continue;
            }
            Log::debug(sprintf('Amount detected is %s', $amount));
            $source                = $localAccount;
            $type                  = 'withdrawal';
            $tags                  = [
                $transaction['cleared'] ?? '',
                $transaction['approved'] ? 'approved' : 'not-approved',
                $transaction['flag_color'] ?? '',
            ];
            $possibleDestinationId = null;
            if (null !== $transaction['transfer_account_id']) {
                // indication that it is a transfer.
                $possibleDestinationId = $config['mapping'][$transaction['transfer_account_id']] ?? null;
                Log::debug(sprintf('transfer_account_id has value %s', $transaction['transfer_account_id']));
                Log::debug(sprintf('Can map this to the following FF3 asset account: %d', $possibleDestinationId));
                $type = 'transfer';

            }

            $destinationData = [
                'name'   => str_replace('Transfer: ', '', $transaction['payee_name']),
                'iban'   => null,
                'number' => $transaction['payee_id'],
                'bic'    => null,
            ];

            $destination = $this->mapper->map($possibleDestinationId, $amount, $destinationData);
            if (1 === bccomp($amount, '0')) {
                [$source, $destination] = [$destination, $source];
                $type = 'transfer' === $type ? 'transfer' : 'deposit';
                Log::debug(sprintf('Amount is %s, so switch source/dest and make this a %s', $amount, $type));
            }

            Log::debug(sprintf('Final source account: #%d ("%s")', $source->id, $source->name));
            Log::debug(sprintf('Final destination account: #%d ("%s")', $destination->id, $destination->name));

            $entry = [
                'type'            => $type,
                'date'            => $transaction['date'] ?? date('Y-m-d'),
                'tags'            => $tags,
                'user'            => $this->importJob->user_id,
                'notes'           => null,

                // all custom fields:
                'external_id'     => $transaction['id'] ?? '',

                // journal data:
                'description'     => $description,
                'piggy_bank_id'   => null,
                'piggy_bank_name' => null,
                'bill_id'         => null,
                'bill_name'       => null,
                'original-source' => sprintf('ynab-v%s', config('firefly.version')),

                // transaction data:
                'transactions'    => [
                    [
                        'type'                  => $type,
                        'date'                  => $transaction['date'] ?? date('Y-m-d'),
                        'tags'                  => $tags,
                        'user'                  => $this->importJob->user_id,
                        'notes'                 => null,
                        'currency_id'           => null,
                        'currency_code'         => $budget['currency_code'] ?? $this->defaultCurrency->code,
                        'amount'                => bcdiv((string)$transaction['amount'], '1000'),
                        'budget_id'             => null,
                        'original-source'       => sprintf('ynab-v%s', config('firefly.version')),
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => $transaction['category_name'],
                        'source_id'             => $source->id,
                        'source_name'           => null,
                        // all custom fields:
                        'external_id'           => $transaction['id'] ?? '',

                        // journal data:
                        'description'           => $description,
                        'destination_id'        => $destination->id,
                        'destination_name'      => null,
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];
            Log::debug(sprintf('Done with entry #%d', $index));
            $array[] = $entry;
        }

        return $array;
    }

    /**
     * @param int $accountId
     *
     * @return Account
     * @throws FireflyException
     */
    private function getLocalAccount(int $accountId): Account
    {
        $account = $this->accountRepository->findNull($accountId);
        if (null === $account) {
            throw new FireflyException(sprintf('Cannot find Firefly III asset account with ID #%d. Job must stop now.', $accountId)); // @codeCoverageIgnore
        }
        if ($account->accountType->type !== AccountType::ASSET) {
            throw new FireflyException(sprintf('Account with ID #%d is not an asset account. Job must stop now.', $accountId)); // @codeCoverageIgnore
        }

        return $account;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function getSelectedBudget(): array
    {
        $config   = $this->repository->getConfiguration($this->importJob);
        $budgets  = $config['budgets'] ?? [];
        $selected = $config['selected_budget'] ?? '';

        if ('' === $selected) {
            $firstBudget = $config['budgets'][0] ?? false;
            if (false === $firstBudget) {
                throw new FireflyException('The configuration contains no budget. Erroring out.');
            }
            $selected = $firstBudget['id'];
        }

        foreach ($budgets as $budget) {
            if ($budget['id'] === $selected) {
                return $budget;
            }
        }

        return $budgets[0] ?? [];
    }

    /**
     * @param string $token
     * @param string $account
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactions(string $token, string $account): array
    {
        $budget             = $this->getSelectedBudget();
        $request            = new GetTransactionsRequest;
        $request->budgetId  = $budget['id'];
        $request->accountId = $account;

        // todo grab latest date for $ynabId
        $request->setAccessToken($token);
        $request->call();

        return $request->transactions;
    }
}
