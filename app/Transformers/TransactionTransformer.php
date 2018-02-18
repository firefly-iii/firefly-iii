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
use FireflyIII\Models\Note;
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
     * TransactionTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Include attachments.
     *
     * @codeCoverageIgnore
     *
     * @param Transaction $transaction
     *
     * @return FractalCollection
     */
    public function includeAttachments(Transaction $transaction): FractalCollection
    {
        return $this->collection($transaction->transactionJournal->attachments, new AttachmentTransformer($this->parameters), 'attachments');
    }

    /**
     * Include meta data
     *
     * @codeCoverageIgnore
     *
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
     * Include piggy bank events
     *
     * @codeCoverageIgnore
     *
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
     * Include tags
     *
     * @codeCoverageIgnore
     *
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
     * Include the user.
     *
     * @codeCoverageIgnore
     *
     * @param Transaction $transaction
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Transaction $transaction): Item
    {
        return $this->item($transaction->transactionJournal->user, new UserTransformer($this->parameters), 'users');
    }


    /**
     * Transform the journal.
     *
     * @param Transaction $transaction
     *
     * @return array
     * @throws FireflyException
     */
    public function transform(Transaction $transaction): array
    {
        $categoryId   = null;
        $categoryName = null;
        $budgetId     = null;
        $budgetName   = null;
        $categoryId   = is_null($transaction->transaction_category_id) ? $transaction->transaction_journal_category_id
            : $transaction->transaction_category_id;
        $categoryName = is_null($transaction->transaction_category_name) ? $transaction->transaction_journal_category_name
            : $transaction->transaction_category_name;

        if ($transaction->transaction_type_type === TransactionType::WITHDRAWAL) {
            $budgetId   = is_null($transaction->transaction_budget_id) ? $transaction->transaction_journal_budget_id
                : $transaction->transaction_budget_id;
            $budgetName = is_null($transaction->transaction_budget_name) ? $transaction->transaction_journal_budget_name
                : $transaction->transaction_budget_name;
        }
        /** @var Note $dbNote */
        $dbNote = $transaction->transactionJournal->notes()->first();
        $notes = null;
        if(!is_null($dbNote)) {
            $notes = $dbNote->text;
        }

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
            'amount'                => round($transaction->transaction_amount, intval($transaction->transaction_currency_dp)),
            'currency_id'           => $transaction->transaction_currency_id,
            'currency_code'         => $transaction->transaction_currency_code,
            'currency_dp'           => $transaction->transaction_currency_dp,
            'foreign_amount'        => null,
            'foreign_currency_id'   => $transaction->foreign_currency_id,
            'foreign_currency_code' => $transaction->foreign_currency_code,
            'foreign_currency_dp'   => $transaction->foreign_currency_dp,
            'bill_id'               => $transaction->bill_id,
            'bill_name'             => $transaction->bill_name,
            'category_id'           => $categoryId,
            'category_name'         => $categoryName,
            'budget_id'             => $budgetId,
            'budget_name'           => $budgetName,
            'notes'                 => $notes,
            'links'                 => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $transaction->id,
                ],
            ],
        ];

        // expand foreign amount:
        if (!is_null($transaction->transaction_foreign_amount)) {
            $data['foreign_amount'] = round($transaction->transaction_foreign_amount, intval($transaction->foreign_currency_dp));
        }

        // switch on type for consistency
        switch ($transaction->transaction_type_type) {
            case TransactionType::WITHDRAWAL:
                $data['source_id']        = $transaction->account_id;
                $data['source_name']      = $transaction->account_name;
                $data['source_iban']      = $transaction->account_iban;
                $data['source_type']      = $transaction->account_type;
                $data['destination_id']   = $transaction->opposing_account_id;
                $data['destination_name'] = $transaction->opposing_account_name;
                $data['destination_iban'] = $transaction->opposing_account_iban;
                $data['destination_type'] = $transaction->opposing_account_type;
                break;
            case TransactionType::DEPOSIT:
            case TransactionType::TRANSFER:
            case TransactionType::OPENING_BALANCE:
            case TransactionType::RECONCILIATION:
                $data['source_id']        = $transaction->opposing_account_id;
                $data['source_name']      = $transaction->opposing_account_name;
                $data['source_iban']      = $transaction->opposing_account_iban;
                $data['source_type']      = $transaction->opposing_account_type;
                $data['destination_id']   = $transaction->account_id;
                $data['destination_name'] = $transaction->account_name;
                $data['destination_iban'] = $transaction->account_iban;
                $data['destination_type'] = $transaction->account_type;
                break;
            default:
                // @codeCoverageIgnoreStart
                throw new FireflyException(
                    sprintf('Transaction transformer cannot handle transactions of type "%s"!', $transaction->transaction_type_type)
                );
            // @codeCoverageIgnoreEnd

        }

        // expand description.
        if (strlen(strval($transaction->transaction_description)) > 0) {
            $data['description'] = $transaction->transaction_description . ' (' . $transaction->description . ')';
        }


        return $data;
    }
}