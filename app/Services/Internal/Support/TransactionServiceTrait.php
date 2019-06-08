<?php
/**
 * TransactionServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Trait TransactionServiceTrait
 *
 */
trait TransactionServiceTrait
{

    /**
     * @param TransactionJournal $journal
     * @param string             $direction
     *
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function accountType(TransactionJournal $journal, string $direction): ?string
    {
        $types = [];
        $type  = $journal->transactionType->type;
        if (TransactionType::WITHDRAWAL === $type) {
            $types['source']      = AccountType::ASSET;
            $types['destination'] = AccountType::EXPENSE;
        }
        if (TransactionType::DEPOSIT === $type) {
            $types['source']      = AccountType::REVENUE;
            $types['destination'] = AccountType::ASSET;
        }
        if (TransactionType::TRANSFER === $type) {
            $types['source']      = AccountType::ASSET;
            $types['destination'] = AccountType::ASSET;
        }
        if (TransactionType::RECONCILIATION === $type) {
            $types['source']      = null;
            $types['destination'] = null;
        }

        return $types[$direction] ?? null;
    }

    /**
     * @param string|null $expectedType
     * @param Account|null $account
     * @param int|null $accountId
     * @param string|null $accountName
     *
     * @return Account|null
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function findAccount(?string $expectedType, ?Account $account, ?int $accountId, ?string $accountName): ?Account
    {
        $result = null;

        if (null !== $account && $account->user_id === $this->user->id) {
            return $account;
        }

        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        $repository  = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);

        if (null === $expectedType) {
            return $repository->findNull($accountId);
        }

        if ($accountId > 0) {
            // must be able to find it based on ID. Validator should catch invalid ID's.
            return $repository->findNull($accountId);
        }
        if (AccountType::ASSET === $expectedType) {
            return $repository->findByName($accountName, [AccountType::ASSET]);
        }
        // for revenue and expense:
        if ('' !== $accountName) {
            /** @var AccountFactory $factory */
            $factory = app(AccountFactory::class);
            $factory->setUser($this->user);

            return $factory->findOrCreate($accountName, $expectedType);
        }

        return $repository->getCashAccount();
    }

    /**
     * @param int|null    $currencyId
     * @param null|string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    protected function findCurrency(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        $factory = app(TransactionCurrencyFactory::class);

        return $factory->find($currencyId, $currencyCode);
    }

    /**
     * @param Transaction $transaction
     * @param string|null $amount
     */
    protected function setForeignAmount(Transaction $transaction, ?string $amount): void
    {
        $amount                      = '' === (string)$amount ? null : $amount;
        $transaction->foreign_amount = $amount;
        $transaction->save();
    }

    /**
     * @param Transaction              $transaction
     * @param TransactionCurrency|null $currency
     */
    protected function setForeignCurrency(Transaction $transaction, ?TransactionCurrency $currency): void
    {
        if (null === $currency) {
            $transaction->foreign_currency_id = null;
            $transaction->save();

            return;
        }
        // enable currency if not enabled:
        if (false === $currency->enabled) {
            $currency->enabled = true;
            $currency->save();
        }

        $transaction->foreign_currency_id = $currency->id;
        $transaction->save();

    }


}
