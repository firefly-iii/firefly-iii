<?php
/**
 * StageImportDataHandler.php
 * Copyright (c) 2018 https://github.com/bnw
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

namespace FireflyIII\Support\Import\Routine\FinTS;


use Fhp\Model\StatementOfAccount\Transaction;
use Fhp\Model\StatementOfAccount\Transaction as FinTSTransaction;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account as LocalAccount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\FinTS\FinTS;
use FireflyIII\Support\FinTS\MetadataParser;
use FireflyIII\Support\Import\Routine\File\OpposingAccountMapper;
use Illuminate\Support\Facades\Log;

/**
 *
 * Class StageImportDataHandler
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
    /** @var array */
    private $transactions;

    /**
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in StageImportDataHandler::run()');

        $localAccount = $this->accountRepository->findNull((int)$this->importJob->configuration['local_account']);
        if (null === $localAccount) {
            throw new FireflyException(sprintf('Cannot find Firefly account with id #%d ', $this->importJob->configuration['local_account']));
        }
        $finTS              = app(FinTS::class, ['config' => $this->importJob->configuration]);
        $fintTSAccount      = $finTS->getAccount($this->importJob->configuration['fints_account']);
        $statementOfAccount = $finTS->getStatementOfAccount(
            $fintTSAccount, new \DateTime($this->importJob->configuration['from_date']), new \DateTime($this->importJob->configuration['to_date'])
        );
        $collection         = [];
        foreach ($statementOfAccount->getStatements() as $statement) {
            foreach ($statement->getTransactions() as $transaction) {
                $collection[] = $this->convertTransaction($transaction, $localAccount);
            }
        }

        $this->transactions = $collection;
    }

    /**
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->transactions      = [];
        $this->importJob         = $importJob;
        $this->repository        = app(ImportJobRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->mapper            = app(OpposingAccountMapper::class);
        $this->mapper->setUser($importJob->user);
        $this->repository->setUser($importJob->user);
        $this->accountRepository->setUser($importJob->user);
    }

    /**
     * @param FinTSTransaction $transaction
     * @param LocalAccount     $source
     *
     * @return array
     */
    private function convertTransaction(FinTSTransaction $transaction, LocalAccount $source): array
    {
        Log::debug(sprintf('Start converting transaction %s', $transaction->getDescription1()));

        $amount        = (string)$transaction->getAmount();
        $debitOrCredit = $transaction->getCreditDebit();
        // assume deposit.
        $type = TransactionType::DEPOSIT;
        Log::debug(sprintf('Amount is %s', $amount));

        // inverse if not.
        if ($debitOrCredit !== Transaction::CD_CREDIT) {
            $type   = TransactionType::WITHDRAWAL;
            $amount = bcmul($amount, '-1');
        }

        $destination = $this->mapper->map(
            null,
            $amount,
            ['iban' => $transaction->getAccountNumber(), 'name' => $transaction->getName()]
        );
        if ($debitOrCredit === Transaction::CD_CREDIT) {
            [$source, $destination] = [$destination, $source];
        }

        if ($source->accountType->type === AccountType::ASSET && $destination->accountType->type === AccountType::ASSET) {
            $type = TransactionType::TRANSFER;
            Log::debug('Both are assets, will make transfer.');
        }

        $metadataParser = new MetadataParser();
        $description    = $metadataParser->getDescription($transaction);

        $storeData = [
            'user'               => $this->importJob->user_id,
            'type'               => $type,
            'date'               => $transaction->getValutaDate()->format('Y-m-d'),
            'description'        => null,
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,
            'tags'               => [],
            'internal_reference' => null,
            'external_id'        => null,
            'notes'              => null,
            'bunq_payment_id'    => null,
            'original-source'    => sprintf('fints-v%s', config('firefly.version')),
            'transactions'       => [
                // single transaction:
                [
                    'type'                  => $type,
                    'description'           => $description,
                    'date'                  => $transaction->getValutaDate()->format('Y-m-d'),
                    'amount'                => $amount,
                    'currency_id'           => null,
                    'currency_code'         => 'EUR',
                    'foreign_amount'        => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'budget_id'             => null,
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => null,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];

        return $storeData;
    }
}
