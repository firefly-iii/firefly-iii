<?php

/**
 * TransactionFactory.php
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

namespace FireflyIII\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    use TransactionServiceTrait;

    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Transaction
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(array $data): ?Transaction
    {
        Log::debug('Start of TransactionFactory::create()');
        $currencyId = $data['currency_id'] ?? null;
        $currencyId = isset($data['currency']) ? $data['currency']->id : $currencyId;
        if ('' === $data['amount']) {
            Log::error('Empty string in data.', $data);
            throw new FireflyException('Amount is an empty string, which Firefly III cannot handle. Apologies.');
        }
        if (null === $currencyId) {
            throw new FireflyException('Cannot store transaction without currency information.');
        }
        $data['foreign_amount'] = '' === (string)$data['foreign_amount'] ? null : $data['foreign_amount'];
        Log::debug(sprintf('Create transaction for account #%d ("%s") with amount %s', $data['account']->id, $data['account']->name, $data['amount']));

        return Transaction::create(
            [
                'reconciled'              => $data['reconciled'],
                'account_id'              => $data['account']->id,
                'transaction_journal_id'  => $data['transaction_journal']->id,
                'description'             => $data['description'],
                'transaction_currency_id' => $currencyId,
                'amount'                  => $data['amount'],
                'foreign_amount'          => $data['foreign_amount'],
                'foreign_currency_id'     => null,
                'identifier'              => $data['identifier'],
            ]
        );
    }

    /**
     * Create a pair of transactions based on the data given in the array.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return Collection
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createPair(TransactionJournal $journal, array $data): Collection
    {
        Log::debug('Start of TransactionFactory::createPair()', $data);
        // all this data is the same for both transactions:
        $currency    = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $description = $journal->description === $data['description'] ? null : $data['description'];

        // type of source account and destination account depends on journal type:
        $sourceType      = $this->accountType($journal, 'source');
        $destinationType = $this->accountType($journal, 'destination');

        Log::debug(sprintf('Expect source account to be of type "%s"', $sourceType));
        Log::debug(sprintf('Expect source destination to be of type "%s"', $destinationType));

        // find source and destination account:
        $sourceAccount      = $this->findAccount($sourceType, $data['source_id'], $data['source_name']);
        $destinationAccount = $this->findAccount($destinationType, $data['destination_id'], $data['destination_name']);

        if (null === $sourceAccount || null === $destinationAccount) {
            $debugData                = $data;
            $debugData['source_type'] = $sourceType;
            $debugData['dest_type']   = $destinationType;
            Log::error('Info about source/dest:', $debugData);
            throw new FireflyException('Could not determine source or destination account.');
        }

        Log::debug(sprintf('Source type is "%s", destination type is "%s"', $sourceAccount->accountType->type, $destinationAccount->accountType->type));

        // based on the source type, destination type and transaction type, the system can start throwing FireflyExceptions.
        $this->validateTransaction($sourceAccount->accountType->type, $destinationAccount->accountType->type, $journal->transactionType->type);

        $source = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->negative((string)$data['amount']),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $sourceAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );
        $dest   = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->positive((string)$data['amount']),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $destinationAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );
        if (null === $source || null === $dest) {
            throw new FireflyException('Could not create transactions.'); // @codeCoverageIgnore
        }

        // set foreign currency
        $foreign = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        $this->setForeignCurrency($source, $foreign);
        $this->setForeignCurrency($dest, $foreign);

        // set foreign amount:
        if (null !== $data['foreign_amount']) {
            $this->setForeignAmount($source, app('steam')->negative((string)$data['foreign_amount']));
            $this->setForeignAmount($dest, app('steam')->positive((string)$data['foreign_amount']));
        }

        // set budget:
        if ($journal->transactionType->type !== TransactionType::WITHDRAWAL) {
            $data['budget_id']   = null;
            $data['budget_name'] = null;
        }

        $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
        $this->setBudget($source, $budget);
        $this->setBudget($dest, $budget);

        // set category
        $category = $this->findCategory($data['category_id'], $data['category_name']);
        $this->setCategory($source, $category);
        $this->setCategory($dest, $category);

        return new Collection([$source, $dest]);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param string $sourceType
     * @param string $destinationType
     * @param string $transactionType
     *
     * @throws FireflyException
     */
    private function validateTransaction(string $sourceType, string $destinationType, string $transactionType): void
    {
        // throw big fat error when source type === dest type and it's not a transfer or reconciliation.
        if ($sourceType === $destinationType && $transactionType !== TransactionType::TRANSFER) {
            throw new FireflyException(sprintf('Source and destination account cannot be both of the type "%s"', $destinationType));
        }
        // source must be in this list AND dest must be in this list:
        $list = [AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH, AccountType::DEBT, AccountType::MORTGAGE, AccountType::LOAN, AccountType::MORTGAGE];
        if (
            !\in_array($sourceType, $list, true) &&
            !\in_array($destinationType, $list, true)) {
            throw new FireflyException(sprintf('At least one of the accounts must be an asset account (%s, %s).', $sourceType, $destinationType));
        }
        // either of these must be asset or default account.

    }


}
