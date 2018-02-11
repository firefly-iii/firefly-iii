<?php
/**
 * JournalTransformer.php
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


use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class TransactionJournalTransformer
 */
class TransactionJournalTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['attachments', 'transactions', 'user', 'tags', 'budget', 'category', 'bill', 'journal_meta', 'piggy_bank_events'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['attachments', 'transactions', 'user', 'tags', 'budget', 'category', 'bill', 'journal_meta', 'piggy_bank_events'];

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
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includeAttachments(TransactionJournal $journal): FractalCollection
    {
        return $this->collection($journal->attachments, new AttachmentTransformer($this->parameters), 'attachments');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Item|null
     */
    public function includeBill(TransactionJournal $journal): ?Item
    {
        $bill = $journal->bill()->first();
        if (!is_null($bill)) {
            return $this->item($bill, new BillTransformer($this->parameters), 'bills');
        }

        return null;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Item|null
     */
    public function includeBudget(TransactionJournal $journal): ?Item
    {
        $budget = $journal->budgets()->first();
        if (!is_null($budget)) {
            return $this->item($budget, new BudgetTransformer($this->parameters), 'budgets');
        }

        return null;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Item|null
     */
    public function includeCategory(TransactionJournal $journal): ?Item
    {
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            return $this->item($category, new CategoryTransformer($this->parameters), 'categories');
        }

        return null;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includeJournalMeta(TransactionJournal $journal): FractalCollection
    {
        $meta = $journal->transactionJournalMeta()->get();

        return $this->collection($meta, new JournalMetaTransformer($this->parameters), 'journal_meta');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includePiggyBankEvents(TransactionJournal $journal): FractalCollection
    {
        $events = $journal->piggyBankEvents()->get();

        return $this->collection($events, new PiggyBankEventTransformer($this->parameters), 'piggy_bank_events');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includeTags(TransactionJournal $journal): FractalCollection
    {
        $set = $journal->tags;

        return $this->collection($set, new TagTransformer($this->parameters), 'tag');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return FractalCollection
     */
    public function includeTransactions(TransactionJournal $journal): FractalCollection
    {
        $set = $journal->transactions()->where('amount', '<', 0)->get(['transactions.*']);

        return $this->collection($set, new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(TransactionJournal $journal): Item
    {
        return $this->item($journal->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function transform(TransactionJournal $journal): array
    {
        $data = [
            'id'          => (int)$journal->id,
            'updated_at'  => $journal->updated_at->toAtomString(),
            'created_at'  => $journal->created_at->toAtomString(),
            'type'        => $journal->transactionType->type,
            'description' => $journal->description,
            'date'        => $journal->date->format('Y-m-d'),
            'order'       => $journal->order,
            'completed'   => $journal->completed,
            'notes'       => null,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/journals/' . $journal->id,
                ],
            ],
        ];
        /** @var Note $note */
        $note = $journal->notes()->first();
        if (!is_null($note)) {
            $data['notes'] = $note->text;
        }

        return $data;
    }

}