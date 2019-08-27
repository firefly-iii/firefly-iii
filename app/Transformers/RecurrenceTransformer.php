<?php
/**
 * RecurringTransactionTransformer.php
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

namespace FireflyIII\Transformers;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Log;

/**
 *
 * Class RecurringTransactionTransformer
 */
class RecurrenceTransformer extends AbstractTransformer
{
    /** @var BudgetRepositoryInterface */
    private $budgetRepos;
    /** @var CategoryFactory */
    private $factory;
    /** @var PiggyBankRepositoryInterface */
    private $piggyRepos;
    /** @var RecurringRepositoryInterface */
    private $repository;

    /**
     * RecurrenceTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository  = app(RecurringRepositoryInterface::class);
        $this->piggyRepos  = app(PiggyBankRepositoryInterface::class);
        $this->factory     = app(CategoryFactory::class);
        $this->budgetRepos = app(BudgetRepositoryInterface::class);

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform the recurring transaction.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     * @throws FireflyException
     */
    public function transform(Recurrence $recurrence): array
    {
        Log::debug('Now in Recurrence::transform()');
        $this->repository->setUser($recurrence->user);
        $this->piggyRepos->setUser($recurrence->user);
        $this->factory->setUser($recurrence->user);
        $this->budgetRepos->setUser($recurrence->user);

        $shortType = (string)config(sprintf('firefly.transactionTypesToShort.%s', $recurrence->transactionType->type));
        $notes     = $this->repository->getNoteText($recurrence);
        // basic data.
        $return = [
            'id'                => (int)$recurrence->id,
            'created_at'        => $recurrence->created_at->toAtomString(),
            'updated_at'        => $recurrence->updated_at->toAtomString(),
            'type'              => $shortType,
            'title'             => $recurrence->title,
            'description'       => $recurrence->description,
            'first_date'        => $recurrence->first_date->format('Y-m-d'),
            'latest_date'       => null === $recurrence->latest_date ? null : $recurrence->latest_date->format('Y-m-d'),
            'repeat_until'      => null === $recurrence->repeat_until ? null : $recurrence->repeat_until->format('Y-m-d'),
            'apply_rules'       => $recurrence->apply_rules,
            'active'            => $recurrence->active,
            'nr_of_repetitions' => $recurrence->repetitions,
            'notes'             => '' === $notes ? null : $notes,
            'repetitions'       => $this->getRepetitions($recurrence),
            'transactions'      => $this->getTransactions($recurrence),
            'links'             => [
                [
                    'rel' => 'self',
                    'uri' => '/recurring/' . $recurrence->id,
                ],
            ],
        ];


        return $return;
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return array
     * @throws FireflyException
     */
    private function getRepetitions(Recurrence $recurrence): array
    {
        $fromDate = $recurrence->latest_date ?? $recurrence->first_date;
        // date in the past? use today:
        $today    = new Carbon;
        $fromDate = $fromDate->lte($today) ? $today : $fromDate;
        $return   = [];

        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            $repetitionArray = [
                'id'          => $repetition->id,
                'created_at'  => $repetition->created_at->toAtomString(),
                'updated_at'  => $repetition->updated_at->toAtomString(),
                'type'        => $repetition->repetition_type,
                'moment'      => $repetition->repetition_moment,
                'skip'        => (int)$repetition->repetition_skip,
                'weekend'     => (int)$repetition->weekend,
                'description' => $this->repository->repetitionDescription($repetition),
                'occurrences' => [],
            ];

            // get the (future) occurrences for this specific type of repetition:
            $occurrences = $this->repository->getXOccurrences($repetition, $fromDate, 5);
            /** @var Carbon $carbon */
            foreach ($occurrences as $carbon) {
                $repetitionArray['occurrences'][] = $carbon->format('Y-m-d');
            }

            $return[] = $repetitionArray;
        }

        return $return;
    }

    /**
     * @param RecurrenceTransaction $transaction
     * @param array                 $array
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactionMeta(RecurrenceTransaction $transaction, array $array): array
    {
        $array['tags']            = [];
        $array['category_id']     = null;
        $array['category_name']   = null;
        $array['budget_id']       = null;
        $array['budget_name']     = null;
        $array['piggy_bank_id']   = null;
        $array['piggy_bank_name'] = null;

        /** @var RecurrenceTransactionMeta $transactionMeta */
        foreach ($transaction->recurrenceTransactionMeta as $transactionMeta) {
            switch ($transactionMeta->name) {
                default:
                    throw new FireflyException(sprintf('Recurrence transformer cant handle field "%s"', $transactionMeta->name));
                case 'bill_id':
                    break;
                case 'tags':
                    $array['tags'] = json_decode($transactionMeta->value);
                    break;
                case 'piggy_bank_id':
                    $piggy = $this->piggyRepos->findNull((int)$transactionMeta->value);
                    if (null !== $piggy) {
                        $array['piggy_bank_id']   = $piggy->id;
                        $array['piggy_bank_name'] = $piggy->name;
                    }
                    break;
                case 'category_name':
                    $category = $this->factory->findOrCreate(null, $transactionMeta->value);
                    if (null !== $category) {
                        $array['category_id']   = $category->id;
                        $array['category_name'] = $category->name;
                    }
                    break;
                case 'budget_id':
                    $budget = $this->budgetRepos->findNull((int)$transactionMeta->value);
                    if (null !== $budget) {
                        $array['budget_id']   = $budget->id;
                        $array['budget_name'] = $budget->name;
                    }
                    break;
            }
        }

        return $array;
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactions(Recurrence $recurrence): array
    {
        $return = [];
        // get all transactions:
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions()->get() as $transaction) {

            $sourceAccount         = $transaction->sourceAccount;
            $destinationAccount    = $transaction->destinationAccount;
            $foreignCurrencyCode   = null;
            $foreignCurrencySymbol = null;
            $foreignCurrencyDp     = null;
            if (null !== $transaction->foreign_currency_id) {
                $foreignCurrencyCode   = $transaction->foreignCurrency->code;
                $foreignCurrencySymbol = $transaction->foreignCurrency->symbol;
                $foreignCurrencyDp     = $transaction->foreignCurrency->decimal_places;
            }

            // source info:
            $sourceName = '';
            $sourceId   = null;
            $sourceType = null;
            $sourceIban = null;
            if (null !== $sourceAccount) {
                $sourceName = $sourceAccount->name;
                $sourceId   = $sourceAccount->id;
                $sourceType = $sourceAccount->accountType->type;
                $sourceIban = $sourceAccount->iban;
            }
            $destinationName = '';
            $destinationId   = null;
            $destinationType = null;
            $destinationIban = null;
            if (null !== $destinationAccount) {
                $destinationName = $destinationAccount->name;
                $destinationId   = $destinationAccount->id;
                $destinationType = $destinationAccount->accountType->type;
                $destinationIban = $destinationAccount->iban;
            }


            $amount        = round($transaction->amount, $transaction->transactionCurrency->decimal_places);
            $foreignAmount = null;
            if (null !== $transaction->foreign_currency_id && null !== $transaction->foreign_amount) {
                $foreignAmount = round($transaction->foreign_amount, $foreignCurrencyDp);
            }
            $transactionArray = [
                'currency_id'                     => $transaction->transaction_currency_id,
                'currency_code'                   => $transaction->transactionCurrency->code,
                'currency_symbol'                 => $transaction->transactionCurrency->symbol,
                'currency_decimal_places'         => $transaction->transactionCurrency->decimal_places,
                'foreign_currency_id'             => $transaction->foreign_currency_id,
                'foreign_currency_code'           => $foreignCurrencyCode,
                'foreign_currency_symbol'         => $foreignCurrencySymbol,
                'foreign_currency_decimal_places' => $foreignCurrencyDp,
                'source_id'                       => $sourceId,
                'source_name'                     => $sourceName,
                'source_iban'                     => $sourceIban,
                'source_type'                     => $sourceType,
                'destination_id'                  => $destinationId,
                'destination_name'                => $destinationName,
                'destination_iban'                => $destinationIban,
                'destination_type'                => $destinationType,
                'amount'                          => $amount,
                'foreign_amount'                  => $foreignAmount,
                'description'                     => $transaction->description,
            ];
            $transactionArray = $this->getTransactionMeta($transaction, $transactionArray);
            if (null !== $transaction->foreign_currency_id) {
                $transactionArray['foreign_currency_code']           = $transaction->foreignCurrency->code;
                $transactionArray['foreign_currency_symbol']         = $transaction->foreignCurrency->symbol;
                $transactionArray['foreign_currency_decimal_places'] = $transaction->foreignCurrency->decimal_places;
            }

            // store transaction in recurrence array.
            $return[] = $transactionArray;
        }

        return $return;
    }

}
