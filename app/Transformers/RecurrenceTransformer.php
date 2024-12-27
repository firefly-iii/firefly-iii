<?php

/**
 * RecurrenceTransformer.php
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

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;

/**
 * Class RecurringTransactionTransformer
 */
class RecurrenceTransformer extends AbstractTransformer
{
    private BillRepositoryInterface      $billRepos;
    private BudgetRepositoryInterface    $budgetRepos;
    private CategoryFactory              $factory;
    private PiggyBankRepositoryInterface $piggyRepos;
    private RecurringRepositoryInterface $repository;

    /**
     * RecurrenceTransformer constructor.
     */
    public function __construct()
    {
        $this->repository  = app(RecurringRepositoryInterface::class);
        $this->piggyRepos  = app(PiggyBankRepositoryInterface::class);
        $this->factory     = app(CategoryFactory::class);
        $this->budgetRepos = app(BudgetRepositoryInterface::class);
        $this->billRepos   = app(BillRepositoryInterface::class);
    }

    /**
     * Transform the recurring transaction.
     *
     * @throws FireflyException
     */
    public function transform(Recurrence $recurrence): array
    {
        app('log')->debug('Now in Recurrence::transform()');
        $this->repository->setUser($recurrence->user);
        $this->piggyRepos->setUser($recurrence->user);
        $this->factory->setUser($recurrence->user);
        $this->budgetRepos->setUser($recurrence->user);
        app('log')->debug('Set user.');

        $shortType = (string) config(sprintf('firefly.transactionTypesToShort.%s', $recurrence->transactionType->type));
        $notes     = $this->repository->getNoteText($recurrence);
        $reps      = 0 === (int) $recurrence->repetitions ? null : (int) $recurrence->repetitions;
        app('log')->debug('Get basic data.');

        // basic data.
        return [
            'id'                => (string) $recurrence->id,
            'created_at'        => $recurrence->created_at->toAtomString(),
            'updated_at'        => $recurrence->updated_at->toAtomString(),
            'type'              => $shortType,
            'title'             => $recurrence->title,
            'description'       => $recurrence->description,
            'first_date'        => $recurrence->first_date->format('Y-m-d'),
            'latest_date'       => $recurrence->latest_date?->format('Y-m-d'),
            'repeat_until'      => $recurrence->repeat_until?->format('Y-m-d'),
            'apply_rules'       => $recurrence->apply_rules,
            'active'            => $recurrence->active,
            'nr_of_repetitions' => $reps,
            'notes'             => '' === $notes ? null : $notes,
            'repetitions'       => $this->getRepetitions($recurrence),
            'transactions'      => $this->getTransactions($recurrence),
            'links'             => [
                [
                    'rel' => 'self',
                    'uri' => '/recurring/'.$recurrence->id,
                ],
            ],
        ];
    }

    /**
     * @throws FireflyException
     */
    private function getRepetitions(Recurrence $recurrence): array
    {
        app('log')->debug('Now in getRepetitions().');
        $fromDate = $recurrence->latest_date ?? $recurrence->first_date;
        $return   = [];

        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            $repetitionArray = [
                'id'          => (string) $repetition->id,
                'created_at'  => $repetition->created_at->toAtomString(),
                'updated_at'  => $repetition->updated_at->toAtomString(),
                'type'        => $repetition->repetition_type,
                'moment'      => $repetition->repetition_moment,
                'skip'        => $repetition->repetition_skip,
                'weekend'     => $repetition->weekend,
                'description' => $this->repository->repetitionDescription($repetition),
                'occurrences' => [],
            ];

            // get the (future) occurrences for this specific type of repetition:
            $amount          = 'daily' === $repetition->repetition_type ? 9 : 5;
            $occurrences     = $this->repository->getXOccurrencesSince($repetition, $fromDate, now(), $amount);

            /** @var Carbon $carbon */
            foreach ($occurrences as $carbon) {
                $repetitionArray['occurrences'][] = $carbon->toAtomString();
            }

            $return[]        = $repetitionArray;
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    private function getTransactions(Recurrence $recurrence): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $return = [];

        // get all transactions:
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions()->get() as $transaction) {
            /** @var null|Account $sourceAccount */
            $sourceAccount         = $transaction->sourceAccount;

            /** @var null|Account $destinationAccount */
            $destinationAccount    = $transaction->destinationAccount;
            $foreignCurrencyCode   = null;
            $foreignCurrencySymbol = null;
            $foreignCurrencyDp     = null;
            $foreignCurrencyId     = null;
            if (null !== $transaction->foreign_currency_id) {
                $foreignCurrencyId     = (int) $transaction->foreign_currency_id;
                $foreignCurrencyCode   = $transaction->foreignCurrency->code;
                $foreignCurrencySymbol = $transaction->foreignCurrency->symbol;
                $foreignCurrencyDp     = $transaction->foreignCurrency->decimal_places;
            }

            // source info:
            $sourceName            = '';
            $sourceId              = null;
            $sourceType            = null;
            $sourceIban            = null;
            if (null !== $sourceAccount) {
                $sourceName = $sourceAccount->name;
                $sourceId   = $sourceAccount->id;
                $sourceType = $sourceAccount->accountType->type;
                $sourceIban = $sourceAccount->iban;
            }
            $destinationName       = '';
            $destinationId         = null;
            $destinationType       = null;
            $destinationIban       = null;
            if (null !== $destinationAccount) {
                $destinationName = $destinationAccount->name;
                $destinationId   = $destinationAccount->id;
                $destinationType = $destinationAccount->accountType->type;
                $destinationIban = $destinationAccount->iban;
            }
            $amount                = app('steam')->bcround($transaction->amount, $transaction->transactionCurrency->decimal_places);
            $foreignAmount         = null;
            if (null !== $transaction->foreign_currency_id && null !== $transaction->foreign_amount) {
                $foreignAmount = app('steam')->bcround($transaction->foreign_amount, $foreignCurrencyDp);
            }
            $transactionArray      = [
                'id'                              => (string) $transaction->id,
                'currency_id'                     => (string) $transaction->transaction_currency_id,
                'currency_code'                   => $transaction->transactionCurrency->code,
                'currency_symbol'                 => $transaction->transactionCurrency->symbol,
                'currency_decimal_places'         => $transaction->transactionCurrency->decimal_places,
                'foreign_currency_id'             => null === $foreignCurrencyId ? null : (string) $foreignCurrencyId,
                'foreign_currency_code'           => $foreignCurrencyCode,
                'foreign_currency_symbol'         => $foreignCurrencySymbol,
                'foreign_currency_decimal_places' => $foreignCurrencyDp,
                'source_id'                       => (string) $sourceId,
                'source_name'                     => $sourceName,
                'source_iban'                     => $sourceIban,
                'source_type'                     => $sourceType,
                'destination_id'                  => (string) $destinationId,
                'destination_name'                => $destinationName,
                'destination_iban'                => $destinationIban,
                'destination_type'                => $destinationType,
                'amount'                          => $amount,
                'foreign_amount'                  => $foreignAmount,
                'description'                     => $transaction->description,
            ];
            $transactionArray      = $this->getTransactionMeta($transaction, $transactionArray);
            if (null !== $transaction->foreign_currency_id) {
                $transactionArray['foreign_currency_code']           = $transaction->foreignCurrency->code;
                $transactionArray['foreign_currency_symbol']         = $transaction->foreignCurrency->symbol;
                $transactionArray['foreign_currency_decimal_places'] = $transaction->foreignCurrency->decimal_places;
            }

            // store transaction in recurrence array.
            $return[]              = $transactionArray;
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    private function getTransactionMeta(RecurrenceTransaction $transaction, array $array): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $array['tags']            = [];
        $array['category_id']     = null;
        $array['category_name']   = null;
        $array['budget_id']       = null;
        $array['budget_name']     = null;
        $array['piggy_bank_id']   = null;
        $array['piggy_bank_name'] = null;
        $array['bill_id']         = null;
        $array['bill_name']       = null;

        /** @var RecurrenceTransactionMeta $transactionMeta */
        foreach ($transaction->recurrenceTransactionMeta as $transactionMeta) {
            switch ($transactionMeta->name) {
                default:
                    throw new FireflyException(sprintf('Recurrence transformer cant handle field "%s"', $transactionMeta->name));

                case 'bill_id':
                    $bill          = $this->billRepos->find((int) $transactionMeta->value);
                    if (null !== $bill) {
                        $array['bill_id']   = (string) $bill->id;
                        $array['bill_name'] = $bill->name;
                    }

                    break;

                case 'tags':
                    $array['tags'] = json_decode($transactionMeta->value);

                    break;

                case 'piggy_bank_id':
                    $piggy         = $this->piggyRepos->find((int) $transactionMeta->value);
                    if (null !== $piggy) {
                        $array['piggy_bank_id']   = (string) $piggy->id;
                        $array['piggy_bank_name'] = $piggy->name;
                    }

                    break;

                case 'category_id':
                    $category      = $this->factory->findOrCreate((int) $transactionMeta->value, null);
                    if (null !== $category) {
                        $array['category_id']   = (string) $category->id;
                        $array['category_name'] = $category->name;
                    }

                    break;

                case 'category_name':
                    $category      = $this->factory->findOrCreate(null, $transactionMeta->value);
                    if (null !== $category) {
                        $array['category_id']   = (string) $category->id;
                        $array['category_name'] = $category->name;
                    }

                    break;

                case 'budget_id':
                    $budget        = $this->budgetRepos->find((int) $transactionMeta->value);
                    if (null !== $budget) {
                        $array['budget_id']   = (string) $budget->id;
                        $array['budget_name'] = $budget->name;
                    }

                    break;
            }
        }

        return $array;
    }
}
