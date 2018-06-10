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
use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class RecurringTransactionTransformer
 */
class RecurrenceTransformer extends TransformerAbstract
{
    /** @noinspection ClassOverridesFieldOfSuperClassInspection */
    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = ['user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
    /** @var ParameterBag */
    protected $parameters;

    /** @var RecurringRepositoryInterface */
    protected $repository;


    public function __construct(ParameterBag $parameters)
    {
        $this->repository = app(RecurringRepositoryInterface::class);
        $this->parameters = $parameters;
    }

    /**
     * Include user data in end result.
     *
     * @codeCoverageIgnore
     *
     * @param Recurrence $recurrence
     *
     *
     * @return Item
     */
    public function includeUser(Recurrence $recurrence): Item
    {
        return $this->item($recurrence->user, new UserTransformer($this->parameters), 'user');
    }

    /**
     * Transform the piggy bank.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     * @throws FireflyException
     */
    public function transform(Recurrence $recurrence): array
    {
        $this->repository->setUser($recurrence->user);
        $return   = [
            'id'                  => (int)$recurrence->id,
            'updated_at'          => $recurrence->updated_at->toAtomString(),
            'created_at'          => $recurrence->created_at->toAtomString(),
            'transaction_type_id' => $recurrence->transaction_type_id,
            'transaction_type'    => $recurrence->transactionType->type,
            'title'               => $recurrence->title,
            'description'         => $recurrence->description,
            'first_date'          => $recurrence->first_date->format('Y-m-d'),
            'latest_date'         => null === $recurrence->latest_date ? null : $recurrence->latest_date->format('Y-m-d'),
            'repeat_until'        => null === $recurrence->repeat_until ? null : $recurrence->repeat_until->format('Y-m-d'),
            'apply_rules'         => $recurrence->apply_rules,
            'active'              => $recurrence->active,
            'notes'               => $this->repository->getNoteText($recurrence),
            'repetitions'         => [],
            'transactions'        => [],
            'meta'                => [],
            'links'               => [
                [
                    'rel' => 'self',
                    'uri' => '/recurring/' . $recurrence->id,
                ],
            ],
        ];
        $fromDate = $recurrence->latest_date ?? $recurrence->first_date;
        // date in the past? use today:
        $today    = new Carbon;
        $fromDate = $fromDate->lte($today) ? $today : $fromDate;

        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            $repetitionArray = [
                'id'                => $repetition->id,
                'updated_at'        => $repetition->updated_at->toAtomString(),
                'created_at'        => $repetition->created_at->toAtomString(),
                'repetition_type'   => $repetition->repetition_type,
                'repetition_moment' => $repetition->repetition_moment,
                'repetition_skip'   => (int)$repetition->repetition_skip,
                'description'       => $this->repository->repetitionDescription($repetition),
                'occurrences'       => [],
            ];

            // get the (future) occurrences for this specific type of repetition:
            $occurrences = $this->repository->getOccurrences($repetition, $fromDate, 5);
            /** @var Carbon $carbon */
            foreach ($occurrences as $carbon) {
                $repetitionArray['occurrences'][] = $carbon->format('Y-m-d');
            }

            $return['repetitions'][] = $repetitionArray;
        }
        unset($repetitionArray);

        // get all transactions:
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions as $transaction) {
            $transactionArray = [
                'currency_id'              => $transaction->transaction_currency_id,
                'currency_code'            => $transaction->transactionCurrency->code,
                'currency_symbol'          => $transaction->transactionCurrency->symbol,
                'currency_dp'              => $transaction->transactionCurrency->decimal_places,
                'foreign_currency_id'      => $transaction->foreign_currency_id,
                'source_account_id'        => $transaction->source_account_id,
                'source_account_name'      => $transaction->sourceAccount->name,
                'destination_account_id'   => $transaction->destination_account_id,
                'destination_account_name' => $transaction->destinationAccount->name,
                'amount'                   => $transaction->amount,
                'foreign_amount'           => $transaction->foreign_amount,
                'description'              => $transaction->description,
                'meta'                     => [],
            ];
            if (null !== $transaction->foreign_currency_id) {
                $transactionArray['foreign_currency_code']   = $transaction->foreignCurrency->code;
                $transactionArray['foreign_currency_symbol'] = $transaction->foreignCurrency->symbol;
                $transactionArray['foreign_currency_dp']     = $transaction->foreignCurrency->decimal_places;
            }

            // get meta data for each transaction:
            /** @var RecurrenceTransactionMeta $transactionMeta */
            foreach ($transaction->recurrenceTransactionMeta as $transactionMeta) {
                $transactionMetaArray = [
                    'name'  => $transactionMeta->name,
                    'value' => $transactionMeta->value,
                ];
                switch ($transactionMeta->name) {
                    default:
                        throw new FireflyException(sprintf('Recurrence transformer cannot handle transaction meta-field "%s"', $transactionMeta->name));
                    case 'category_name':
                        /** @var CategoryFactory $factory */
                        $factory = app(CategoryFactory::class);
                        $factory->setUser($recurrence->user);
                        $category = $factory->findOrCreate(null, $transactionMeta->value);
                        if (null !== $category) {
                            $transactionMetaArray['category_id']   = $category->id;
                            $transactionMetaArray['category_name'] = $category->name;
                        }
                        break;
                    case 'budget_id':
                        /** @var BudgetRepositoryInterface $repository */
                        $repository = app(BudgetRepositoryInterface::class);
                        $budget     = $repository->findNull((int)$transactionMeta->value);
                        if (null !== $budget) {
                            $transactionMetaArray['budget_id']   = $budget->id;
                            $transactionMetaArray['budget_name'] = $budget->name;
                        }
                        break;
                }
                // store transaction meta data in transaction
                $transactionArray['meta'][] = $transactionMetaArray;
            }
            // store transaction in recurrence array.
            $return['transactions'][] = $transactionArray;
        }
        // get all meta data for recurrence itself
        /** @var RecurrenceMeta $recurrenceMeta */
        foreach ($recurrence->recurrenceMeta as $recurrenceMeta) {
            $recurrenceMetaArray = [
                'name'  => $recurrenceMeta->name,
                'value' => $recurrenceMeta->value,
            ];
            switch ($recurrenceMeta->name) {
                default:
                    throw new FireflyException(sprintf('Recurrence transformer cannot handle meta-field "%s"', $recurrenceMeta->name));
                case 'tags':
                    $recurrenceMetaArray['tags'] = explode(',', $recurrenceMeta->value);
                    break;
                case 'notes':
                    break;
                case 'bill_id':
                    /** @var BillRepositoryInterface $repository */
                    $repository = app(BillRepositoryInterface::class);
                    $bill       = $repository->find((int)$recurrenceMeta->value);
                    if (null !== $bill) {
                        $recurrenceMetaArray['bill_id']   = $bill->id;
                        $recurrenceMetaArray['bill_name'] = $bill->name;
                    }
                    break;
                case 'piggy_bank_id':
                    /** @var PiggyBankRepositoryInterface $repository */
                    $repository = app(PiggyBankRepositoryInterface::class);
                    $piggy      = $repository->findNull((int)$recurrenceMeta->value);
                    if (null !== $piggy) {
                        $recurrenceMetaArray['piggy_bank_id']   = $piggy->id;
                        $recurrenceMetaArray['piggy_bank_name'] = $piggy->name;
                    }
                    break;
            }
            // store meta date in recurring array
            $return['meta'][] = $recurrenceMetaArray;

        }

        return $return;
    }

}