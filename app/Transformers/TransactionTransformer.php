<?php
/**
 * TransactionTransformer.php
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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class TransactionTransformer
 */
class TransactionTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['attachments', 'user', 'tags', 'journal_meta', 'piggy_bank_events'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * BillTransformer constructor.
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param Transaction $transaction
     *
     * @return FractalCollection
     */
    public function includeAttachments(Transaction $transaction): FractalCollection
    {
        return $this->collection($transaction->transactionJournal->attachments, new AttachmentTransformer($this->parameters), 'attachments');
    }

    /**
     * @param Transaction $transaction
     *
     * @return FractalCollection
     */
    public function includeJournalMeta(Transaction $transaction): FractalCollection
    {
        $meta = $transaction->transactionJournal->transactionJournalMeta()->get();

        return $this->collection($meta, new JournalMetaTransformer($this->parameters), 'journal_meta');
    }

    /**
     * @param Transaction $transaction
     *
     * @return FractalCollection
     */
    public function includePiggyBankEvents(Transaction $transaction): FractalCollection
    {
        $events = $transaction->transactionJournal->piggyBankEvents()->get();

        return $this->collection($events, new PiggyBankEventTransformer($this->parameters), 'piggy_bank_events');
    }

    /**
     * @param Transaction $transaction
     *
     * @return FractalCollection
     */
    public function includeTags(Transaction $transaction): FractalCollection
    {
        $set = $transaction->transactionJournal->tags;

        return $this->collection($set, new TagTransformer($this->parameters), 'tags');
    }

    /**
     * @param Transaction $transaction
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Transaction $transaction): Item
    {
        return $this->item($transaction->transactionJournal->user, new UserTransformer($this->parameters), 'users');
    }


    /**
     * @param Transaction $transaction
     *
     * @return array
     * @throws FireflyException
     */
    public function transform(Transaction $transaction): array
    {
        $data = [
            'id'                    => (int)$transaction->id,
            'updated_at'            => $transaction->updated_at->toAtomString(),
            'created_at'            => $transaction->created_at->toAtomString(),
            'description'           => $transaction->description,
            'date'                  => $transaction->date->format('Y-m-d'),
            'type'                  => $transaction->transaction_type_type,
            'identifier'            => $transaction->identifier,
            'journal_id'            => (int)$transaction->journal_id,
            'reconciled'            => (bool)$transaction->reconciled,
            'amount'                => round($transaction->transaction_amount, $transaction->transaction_currency_dp),
            'currency_id'           => $transaction->transaction_currency_id,
            'currency_code'         => $transaction->transaction_currency_code,
            'currency_dp'           => $transaction->transaction_currency_dp,
            'foreign_amount'        => null,
            'foreign_currency_id'   => $transaction->foreign_currency_id,
            'foreign_currency_code' => $transaction->foreign_currency_code,
            'foreign_currency_dp'   => $transaction->foreign_currency_dp,
            'bill_id'               => $transaction->bill_id,
            'bill_name'             => $transaction->bill_name,
            'category_id'           => is_null($transaction->transaction_category_id) ? $transaction->transaction_journal_category_id
                : $transaction->transaction_category_id,
            'category_name'         => is_null($transaction->transaction_category_name) ? $transaction->transaction_journal_category_name
                : $transaction->transaction_category_name,
            'budget_id'             => is_null($transaction->transaction_budget_id) ? $transaction->transaction_journal_budget_id
                : $transaction->transaction_budget_id,
            'budget_name'           => is_null($transaction->transaction_budget_name) ? $transaction->transaction_journal_budget_name
                : $transaction->transaction_budget_name,
            'links'                 => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $transaction->id,
                ],
            ],
        ];

        // expand foreign amount:
        if (!is_null($transaction->transaction_foreign_amount)) {
            $data['foreign_amount'] = round($transaction->transaction_foreign_amount, $transaction->foreign_currency_dp);
        }

        // switch on type for consistency
        switch (true) {
            case TransactionType::WITHDRAWAL === $transaction->transaction_type_type:
                $data['source_id']        = $transaction->account_id;
                $data['source_name']      = $transaction->account_name;
                $data['source_iban']      = $transaction->account_iban;
                $data['source_type']      = $transaction->account_type;
                $data['destination_id']   = $transaction->opposing_account_id;
                $data['destination_name'] = $transaction->opposing_account_name;
                $data['destination_iban'] = $transaction->opposing_account_iban;
                $data['destination_type'] = $transaction->opposing_account_type;
                break;
            case TransactionType::DEPOSIT === $transaction->transaction_type_type:
                $data['source_id']        = $transaction->opposing_account_id;
                $data['source_name']      = $transaction->opposing_account_name;
                $data['source_iban']      = $transaction->opposing_account_iban;
                $data['source_type']      = $transaction->opposing_account_type;
                $data['destination_id']   = $transaction->account_id;
                $data['destination_name'] = $transaction->account_name;
                $data['destination_iban'] = $transaction->account_iban;
                $data['destination_type'] = $transaction->account_type;
                break;
            case TransactionType::TRANSFER === $transaction->transaction_type_type && bccomp($transaction->transaction_amount, '0') > 0:
                $data['source_id']        = $transaction->opposing_account_id;
                $data['source_name']      = $transaction->opposing_account_name;
                $data['source_iban']      = $transaction->opposing_account_iban;
                $data['source_type']      = $transaction->opposing_account_type;
                $data['destination_id']   = $transaction->account_id;
                $data['destination_name'] = $transaction->account_name;
                $data['destination_iban'] = $transaction->account_iban;
                $data['destination_type'] = $transaction->account_type;
                break;
            case TransactionType::TRANSFER === $transaction->transaction_type_type && bccomp($transaction->transaction_amount, '0') < 0:
                $data['source_id']        = $transaction->account_id;
                $data['source_name']      = $transaction->account_name;
                $data['source_iban']      = $transaction->account_iban;
                $data['source_type']      = $transaction->account_type;
                $data['destination_id']   = $transaction->opposing_account_id;
                $data['destination_name'] = $transaction->opposing_account_name;
                $data['destination_iban'] = $transaction->opposing_account_iban;
                $data['destination_type'] = $transaction->opposing_account_type;
                $data['amount']           = $data['amount'] * -1;
                $data['foreign_amount']   = is_null($data['foreign_amount']) ? null : $data['foreign_amount'] * -1;
                break;
            default:
                throw new FireflyException(sprintf('Cannot handle % s!', $transaction->transaction_type_type));

        }

        // expand description.
        if (strlen(strval($transaction->transaction_description)) > 0) {
            $data['description'] = $transaction->transaction_description . ' (' . $transaction->description . ')';
        }


        return $data;
    }
}