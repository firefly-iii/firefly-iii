<?php
/**
 * RecurringTransactionTrait.php
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

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Validation\AccountValidator;
use Log;


/**
 * Trait RecurringTransactionTrait
 *
 */
trait RecurringTransactionTrait
{
    /**
     * @param Recurrence $recurrence
     * @param array $repetitions
     */
    protected function createRepetitions(Recurrence $recurrence, array $repetitions): void
    {
        /** @var array $array */
        foreach ($repetitions as $array) {
            RecurrenceRepetition::create(
                [
                    'recurrence_id'     => $recurrence->id,
                    'repetition_type'   => $array['type'],
                    'repetition_moment' => $array['moment'] ?? '',
                    'repetition_skip'   => $array['skip'],
                    'weekend'           => $array['weekend'] ?? 1,
                ]
            );

        }
    }

    /**
     * @param array $expectedTypes
     * @param Account|null $account
     * @param int|null $accountId
     * @param string|null $accountName
     *
     * @return Account
     */
    protected function findAccount(array $expectedTypes, ?int $accountId, ?string $accountName): Account
    {
        $result      = null;
        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);

        // if user has submitted an account ID, search for it.
        $result = $repository->findNull((int)$accountId);
        if (null !== $result) {
            return $result;
        }

        // if user has submitted a name, search for it:
        $result = $repository->findByName($accountName, $expectedTypes);
        if (null !== $result) {
            return $result;
        }

        // maybe we can create it? Try to avoid LOAN and other asset types.
        $cannotCreate = [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD];
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);
        foreach ($expectedTypes as $expectedType) {
            if (in_array($expectedType, $cannotCreate, true)) {
                continue;
            }
            if (!in_array($expectedType, $cannotCreate, true)) {
                try {
                    $result = $factory->findOrCreate($accountName, $expectedType);
                    // @codeCoverageIgnoreStart
                } catch (FireflyException $e) {
                    Log::error($e->getMessage());
                }
                // @codeCoverageIgnoreEnd
            }
        }

        return $result ?? $repository->getCashAccount();
    }

    /**
     * Store transactions of a recurring transactions. It's complex but readable.
     *
     * @param Recurrence $recurrence
     * @param array $transactions
     * @throws FireflyException
     */
    protected function createTransactions(Recurrence $recurrence, array $transactions): void
    {
        foreach ($transactions as $array) {
            $sourceTypes = config(sprintf('firefly.expected_source_types.source.%s', $recurrence->transactionType->type));
            $destTypes   = config(sprintf('firefly.expected_source_types.destination.%s', $recurrence->transactionType->type));
            $source      = $this->findAccount($sourceTypes, $array['source_id'], $array['source_name']);
            $destination = $this->findAccount($destTypes, $array['destination_id'], $array['destination_name']);

            /** @var TransactionCurrencyFactory $factory */
            $factory         = app(TransactionCurrencyFactory::class);
            $currency        = $factory->find($array['currency_id'] ?? null, $array['currency_code'] ?? null);
            $foreignCurrency = $factory->find($array['foreign_currency_id'] ?? null, $array['foreign_currency_code'] ?? null);
            if (null === $currency) {
                $currency = app('amount')->getDefaultCurrencyByUser($recurrence->user);
            }

            // once the accounts have been determined, we still verify their validity:
            /** @var AccountValidator $validator */
            $validator = app(AccountValidator::class);
            $validator->setUser($recurrence->user);
            $validator->setTransactionType($recurrence->transactionType->type);
            if (!$validator->validateSource($source->id, null)) {
                throw new FireflyException(sprintf('Source invalid: %s', $validator->sourceError)); // @codeCoverageIgnore
            }

            if (!$validator->validateDestination($destination->id, null)) {
                throw new FireflyException(sprintf('Destination invalid: %s', $validator->destError)); // @codeCoverageIgnore
            }

            $transaction = new RecurrenceTransaction(
                [
                    'recurrence_id'           => $recurrence->id,
                    'transaction_currency_id' => $currency->id,
                    'foreign_currency_id'     => null === $foreignCurrency ? null : $foreignCurrency->id,
                    'source_id'               => $source->id,
                    'destination_id'          => $destination->id,
                    'amount'                  => $array['amount'],
                    'foreign_amount'          => '' === (string)$array['foreign_amount'] ? null : (string)$array['foreign_amount'],
                    'description'             => $array['description'],
                ]
            );
            $transaction->save();

            /** @var BudgetFactory $budgetFactory */
            $budgetFactory = app(BudgetFactory::class);
            $budgetFactory->setUser($recurrence->user);
            $budget = $budgetFactory->find($array['budget_id'], $array['budget_name']);

            /** @var CategoryFactory $categoryFactory */
            $categoryFactory = app(CategoryFactory::class);
            $categoryFactory->setUser($recurrence->user);
            $category = $categoryFactory->findOrCreate($array['category_id'], $array['category_name']);

            // create recurrence transaction meta:
            if (null !== $budget) {
                RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'budget_id',
                        'value' => $budget->id,
                    ]
                );
            }
            if (null !== $category) {
                RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'category_name',
                        'value' => $category->name,
                    ]
                );
            }
        }
    }

    /**
     * @param Recurrence $recurrence
     *
     * @codeCoverageIgnore
     */
    protected function deleteRepetitions(Recurrence $recurrence): void
    {
        $recurrence->recurrenceRepetitions()->delete();
    }

    /**
     * @param Recurrence $recurrence
     *
     * @codeCoverageIgnore
     */
    protected function deleteTransactions(Recurrence $recurrence): void
    {
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions as $transaction) {
            $transaction->recurrenceTransactionMeta()->delete();
            try {
                $transaction->delete();
            } catch (Exception $e) {
                Log::debug($e->getMessage());
            }
        }
    }

    /**
     * Update meta data for recurring transaction.
     *
     * @param Recurrence $recurrence
     * @param array $data
     */
    protected function updateMetaData(Recurrence $recurrence, array $data): void
    {
        // only two special meta fields right now. Let's just hard code them.
        $piggyId   = (int)($data['meta']['piggy_bank_id'] ?? 0.0);
        $piggyName = $data['meta']['piggy_bank_name'] ?? '';
        $this->updatePiggyBank($recurrence, $piggyId, $piggyName);


        $tags = $data['meta']['tags'] ?? [];
        $this->updateTags($recurrence, $tags);

    }

    /**
     * @param Recurrence $recurrence
     * @param int $piggyId
     * @param string $piggyName
     */
    protected function updatePiggyBank(Recurrence $recurrence, int $piggyId, string $piggyName): void
    {

        /** @var PiggyBankFactory $factory */
        $factory = app(PiggyBankFactory::class);
        $factory->setUser($recurrence->user);
        $piggyBank = $factory->find($piggyId, $piggyName);
        if (null !== $piggyBank) {
            /** @var RecurrenceMeta $entry */
            $entry = $recurrence->recurrenceMeta()->where('name', 'piggy_bank_id')->first();
            if (null === $entry) {
                $entry = RecurrenceMeta::create(['recurrence_id' => $recurrence->id, 'name' => 'piggy_bank_id', 'value' => $piggyBank->id]);
            }
            $entry->value = $piggyBank->id;
            $entry->save();
        }
        if (null === $piggyBank) {
            // delete if present
            $recurrence->recurrenceMeta()->where('name', 'piggy_bank_id')->delete();
        }
    }

    /**
     * @param Recurrence $recurrence
     * @param array $tags
     */
    protected function updateTags(Recurrence $recurrence, array $tags): void
    {
        if (count($tags) > 0) {
            /** @var RecurrenceMeta $entry */
            $entry = $recurrence->recurrenceMeta()->where('name', 'tags')->first();
            if (null === $entry) {
                $entry = RecurrenceMeta::create(['recurrence_id' => $recurrence->id, 'name' => 'tags', 'value' => implode(',', $tags)]);
            }
            $entry->value = implode(',', $tags);
            $entry->save();
        }
        if (0 === count($tags)) {
            // delete if present
            $recurrence->recurrenceMeta()->where('name', 'tags')->delete();
        }
    }
}
