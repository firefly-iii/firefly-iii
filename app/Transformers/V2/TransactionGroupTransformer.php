<?php

/*
 * TransactionGroupTransformer.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Transformers\V2;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Location;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\NullArrayObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class TransactionGroupTransformer
 */
class TransactionGroupTransformer extends AbstractTransformer
{
    private array                 $accountTypes = []; // account types collection.
    private ExchangeRateConverter $converter;         // collection of all journals and some important meta-data.
    private array                 $currencies   = [];
    private TransactionCurrency   $default; // collection of all currencies for this transformer.
    private array                 $journals     = [];
    private array                 $objects      = [];

    //    private array                 $currencies        = [];
    //    private array                 $transactionTypes  = [];
        private array                 $meta              = [];
        private array                 $notes             = [];
    //    private array                 $locations         = [];
        private array                 $tags              = [];
    //    private array                 $amounts           = [];
    //    private array                 $foreignAmounts    = [];
    //    private array                 $journalCurrencies = [];
    //    private array                 $foreignCurrencies = [];

    public function collectMetaData(Collection $objects): Collection
    {
        $collectForObjects = false;

        /** @var array|TransactionGroup $object */
        foreach ($objects as $object) {
            if (is_array($object)) {
                $this->collectForArray($object);
            }
            if ($object instanceof TransactionGroup) {
                $this->collectForObject($object);
                $collectForObjects = true;
            }
        }

        $this->default     = app('amount')->getDefaultCurrency();
        $this->converter   = new ExchangeRateConverter();

        $this->collectAllMetaData();
        $this->collectAllNotes();
        $this->collectAllLocations();
        $this->collectAllTags();
        if ($collectForObjects) {
            $this->collectAllCurrencies();
            //            $this->collectAllAmounts();
            //            $this->collectTransactionTypes();
            //            $this->collectAccounts();
            // source accounts
            // destination accounts
        }

        return $objects;
    }

    private function collectForArray(array $object): void
    {
        foreach ($object['sums'] as $sum) {
            $this->currencies[(int) $sum['currency_id']] ??= TransactionCurrency::find($sum['currency_id']);
        }

        /** @var array $transaction */
        foreach ($object['transactions'] as $transaction) {
            $this->journals[(int) $transaction['transaction_journal_id']] = [];
        }
    }

    private function collectForObject(TransactionGroup $object): void
    {
        foreach ($object->transactionJournals as $journal) {
            $this->journals[$journal->id] = [];
            $this->objects[]              = $journal;
        }
    }

    private function collectAllMetaData(): void
    {
        $meta = TransactionJournalMeta::whereIn('transaction_journal_id', array_keys($this->journals))->get();

        /** @var TransactionJournalMeta $entry */
        foreach ($meta as $entry) {
            $id                                        = $entry->transaction_journal_id;
            $this->journals[$id]['meta'] ??= [];
            $this->journals[$id]['meta'][$entry->name] = $entry->data;
        }
    }

    private function collectAllNotes(): void
    {
        // grab all notes for all journals:
        $notes = Note::whereNoteableType(TransactionJournal::class)->whereIn('noteable_id', array_keys($this->journals))->get();

        /** @var Note $note */
        foreach ($notes as $note) {
            $id                           = $note->noteable_id;
            $this->journals[$id]['notes'] = $note->text;
        }
    }

    private function collectAllLocations(): void
    {
        // grab all locations for all journals:
        $locations = Location::whereLocatableType(TransactionJournal::class)->whereIn('locatable_id', array_keys($this->journals))->get();

        /** @var Location $location */
        foreach ($locations as $location) {
            $id                              = $location->locatable_id;
            $this->journals[$id]['location'] = [
                'latitude'   => $location->latitude,
                'longitude'  => $location->longitude,
                'zoom_level' => $location->zoom_level,
            ];
        }
    }

    private function collectAllTags(): void
    {
        // grab all tags for all journals:
        $tags = DB::table('tag_transaction_journal')
            ->leftJoin('tags', 'tags.id', 'tag_transaction_journal.tag_id')
            ->whereIn('tag_transaction_journal.transaction_journal_id', array_keys($this->journals))
            ->get(['tag_transaction_journal.transaction_journal_id', 'tags.tag'])
        ;

        /** @var \stdClass $tag */
        foreach ($tags as $tag) {
            $id                            = (int) $tag->transaction_journal_id;
            $this->journals[$id]['tags'][] = $tag->tag;
        }
    }

    private function collectAllCurrencies(): void
    {
        /** @var TransactionJournal $journal */
        foreach ($this->objects as $journal) {
            $id                                         = $journal->id;
            $this->journals[$id]['reconciled']          = false;
            $this->journals[$id]['foreign_amount']      = null;
            $this->journals[$id]['foreign_currency_id'] = null;
            $this->journals[$id]['amount']              = null;
            $this->journals[$id]['currency_id']         = null;
            $this->journals[$id]['type']                = $journal->transactionType->type;
            $this->journals[$id]['budget_id']           = null;
            $this->journals[$id]['budget_name']         = null;
            $this->journals[$id]['category_id']         = null;
            $this->journals[$id]['category_name']       = null;
            $this->journals[$id]['bill_id']             = null;
            $this->journals[$id]['bill_name']           = null;

            // collect budget:
            /** @var null|Budget $budget */
            $budget                                     = $journal->budgets()->first();
            if (null !== $budget) {
                $this->journals[$id]['budget_id']   = (string) $budget->id;
                $this->journals[$id]['budget_name'] = $budget->name;
            }

            // collect category:
            /** @var null|Category $category */
            $category                                   = $journal->categories()->first();
            if (null !== $category) {
                $this->journals[$id]['category_id']   = (string) $category->id;
                $this->journals[$id]['category_name'] = $category->name;
            }

            // collect bill:
            if (null !== $journal->bill_id) {
                $bill                             = $journal->bill;
                $this->journals[$id]['bill_id']   = (string) $bill->id;
                $this->journals[$id]['bill_name'] = $bill->name;
            }

            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                if (-1 === bccomp($transaction->amount, '0')) {
                    // only collect source account info
                    $account                                    = $transaction->account;
                    $this->accountTypes[$account->account_type_id] ??= $account->accountType->type;
                    $this->journals[$id]['source_account_name'] = $account->name;
                    $this->journals[$id]['source_account_iban'] = $account->iban;
                    $this->journals[$id]['source_account_type'] = $this->accountTypes[$account->account_type_id];
                    $this->journals[$id]['source_account_id']   = $transaction->account_id;
                    $this->journals[$id]['reconciled']          = $transaction->reconciled;

                    continue;
                }

                // add account
                $account                                         = $transaction->account;
                $this->accountTypes[$account->account_type_id] ??= $account->accountType->type;
                $this->journals[$id]['destination_account_name'] = $account->name;
                $this->journals[$id]['destination_account_iban'] = $account->iban;
                $this->journals[$id]['destination_account_type'] = $this->accountTypes[$account->account_type_id];
                $this->journals[$id]['destination_account_id']   = $transaction->account_id;

                // find and set currency
                $currencyId                                      = $transaction->transaction_currency_id;
                $this->currencies[$currencyId]                 ??= $transaction->transactionCurrency;
                $this->journals[$id]['currency_id']              = $currencyId;
                $this->journals[$id]['amount']                   = $transaction->amount;
                // find and set foreign currency
                if (null !== $transaction->foreign_currency_id) {
                    $foreignCurrencyId                          = $transaction->foreign_currency_id;
                    $this->currencies[$foreignCurrencyId] ??= $transaction->foreignCurrency;
                    $this->journals[$id]['foreign_currency_id'] = $foreignCurrencyId;
                    $this->journals[$id]['foreign_amount']      = $transaction->foreign_amount;
                }

                // find and set destination account info.
            }
        }
    }

    public function transform(array|TransactionGroup $group): array
    {
        if (is_array($group)) {
            $first = reset($group['transactions']);

            return [
                'id'           => (string) $group['id'],
                'created_at'   => $group['created_at']->toAtomString(),
                'updated_at'   => $group['updated_at']->toAtomString(),
                'user'         => (string) $first['user_id'],
                'user_group'   => (string) $first['user_group_id'],
                'group_title'  => $group['title'] ?? null,
                'transactions' => $this->transformTransactions($group['transactions'] ?? []),
                'links'        => [
                    [
                        'rel' => 'self',
                        'uri' => sprintf('/transactions/%d', $group['id']),
                    ],
                ],
            ];
        }

        return [
            'id'           => (string) $group->id,
            'created_at'   => $group->created_at->toAtomString(),
            'updated_at'   => $group->created_at->toAtomString(),
            'user'         => (string) $group->user_id,
            'user_group'   => (string) $group->user_group_id,
            'group_title'  => $group->title ?? null,
            'transactions' => $this->transformJournals($group),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/transactions/%d', $group->id),
                ],
            ],
        ];
    }

    private function transformTransactions(array $transactions): array
    {
        $return = [];

        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return[] = $this->transformTransaction($transaction);
        }

        return $return;
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function transformTransaction(array $transaction): array
    {
        $transaction         = new NullArrayObject($transaction);
        $type                = $this->stringFromArray($transaction, 'transaction_type_type', TransactionTypeEnum::WITHDRAWAL->value);
        $journalId           = (int) $transaction['transaction_journal_id'];
        $meta                = new NullArrayObject($this->meta[$journalId] ?? []);

        /**
         * Convert and use amount:
         */
        $amount              = app('steam')->positive((string) ($transaction['amount'] ?? '0'));
        $currencyId          = (int) $transaction['currency_id'];
        $nativeAmount        = $this->converter->convert($this->default, $this->currencies[$currencyId], $transaction['date'], $amount);
        $foreignAmount       = null;
        $nativeForeignAmount = null;
        if (null !== $transaction['foreign_amount']) {
            $foreignCurrencyId   = (int) $transaction['foreign_currency_id'];
            $foreignAmount       = app('steam')->positive($transaction['foreign_amount']);
            $nativeForeignAmount = $this->converter->convert($this->default, $this->currencies[$foreignCurrencyId], $transaction['date'], $foreignAmount);
        }
        $this->converter->summarize();

        $longitude           = null;
        $latitude            = null;
        $zoomLevel           = null;
        if (array_key_exists('location', $this->journals[$journalId])) {
            $latitude  = (string) $this->journals[$journalId]['location']['latitude'];
            $longitude = (string) $this->journals[$journalId]['location']['longitude'];
            $zoomLevel = $this->journals[$journalId]['location']['zoom_level'];
        }

        return [
            'user'                            => (string) $transaction['user_id'],
            'user_group'                      => (string) $transaction['user_group_id'],
            'transaction_journal_id'          => (string) $transaction['transaction_journal_id'],
            'type'                            => strtolower($type),
            'date'                            => $transaction['date']->toAtomString(),
            'order'                           => $transaction['order'],
            'amount'                          => $amount,
            'native_amount'                   => $nativeAmount,
            'foreign_amount'                  => $foreignAmount,
            'native_foreign_amount'           => $nativeForeignAmount,
            'currency_id'                     => (string) $transaction['currency_id'],
            'currency_code'                   => $transaction['currency_code'],
            'currency_name'                   => $transaction['currency_name'],
            'currency_symbol'                 => $transaction['currency_symbol'],
            'currency_decimal_places'         => (int) $transaction['currency_decimal_places'],

            // converted to native currency
            'native_currency_id'              => (string) $this->default->id,
            'native_currency_code'            => $this->default->code,
            'native_currency_name'            => $this->default->name,
            'native_currency_symbol'          => $this->default->symbol,
            'native_currency_decimal_places'  => $this->default->decimal_places,

            // foreign currency amount:
            'foreign_currency_id'             => $this->stringFromArray($transaction, 'foreign_currency_id', null),
            'foreign_currency_code'           => $transaction['foreign_currency_code'],
            'foreign_currency_name'           => $transaction['foreign_currency_name'],
            'foreign_currency_symbol'         => $transaction['foreign_currency_symbol'],
            'foreign_currency_decimal_places' => $transaction['foreign_currency_decimal_places'],

            // foreign converted to native:
            'description'                     => $transaction['description'],
            'source_id'                       => (string) $transaction['source_account_id'],
            'source_name'                     => $transaction['source_account_name'],
            'source_iban'                     => $transaction['source_account_iban'],
            'source_type'                     => $transaction['source_account_type'],
            'destination_id'                  => (string) $transaction['destination_account_id'],
            'destination_name'                => $transaction['destination_account_name'],
            'destination_iban'                => $transaction['destination_account_iban'],
            'destination_type'                => $transaction['destination_account_type'],
            'budget_id'                       => $this->stringFromArray($transaction, 'budget_id', null),
            'budget_name'                     => $transaction['budget_name'],
            'category_id'                     => $this->stringFromArray($transaction, 'category_id', null),
            'category_name'                   => $transaction['category_name'],
            'bill_id'                         => $this->stringFromArray($transaction, 'bill_id', null),
            'bill_name'                       => $transaction['bill_name'],
            'reconciled'                      => $transaction['reconciled'],
            'notes'                           => $this->notes[$journalId] ?? null,
            'tags'                            => $this->tags[$journalId] ?? [],
            'internal_reference'              => $meta['internal_reference'],
            'external_id'                     => $meta['external_id'],
            'original_source'                 => $meta['original_source'],
            'recurrence_id'                   => $meta['recurrence_id'],
            'recurrence_total'                => $meta['recurrence_total'],
            'recurrence_count'                => $meta['recurrence_count'],
            'external_url'                    => $meta['external_url'],
            'import_hash_v2'                  => $meta['import_hash_v2'],
            'sepa_cc'                         => $meta['sepa_cc'],
            'sepa_ct_op'                      => $meta['sepa_ct_op'],
            'sepa_ct_id'                      => $meta['sepa_ct_id'],
            'sepa_db'                         => $meta['sepa_db'],
            'sepa_country'                    => $meta['sepa_country'],
            'sepa_ep'                         => $meta['sepa_ep'],
            'sepa_ci'                         => $meta['sepa_ci'],
            'sepa_batch_id'                   => $meta['sepa_batch_id'],
            'interest_date'                   => $this->date($meta['interest_date']),
            'book_date'                       => $this->date($meta['book_date']),
            'process_date'                    => $this->date($meta['process_date']),
            'due_date'                        => $this->date($meta['due_date']),
            'payment_date'                    => $this->date($meta['payment_date']),
            'invoice_date'                    => $this->date($meta['invoice_date']),

            // location data
            'longitude'                       => $longitude,
            'latitude'                        => $latitude,
            'zoom_level'                      => $zoomLevel,
            //
            //            'has_attachments' => $this->hasAttachments((int) $row['transaction_journal_id']),
        ];
    }

    /**
     * TODO also in the old transformer.
     *
     * Used to extract a value from the given array, and fall back on a sensible default or NULL
     * if it can't be helped.
     */
    private function stringFromArray(NullArrayObject $array, string $key, ?string $default): ?string
    {
        // app('log')->debug(sprintf('%s: %s', $key, var_export($array[$key], true)));
        if (null === $array[$key] && null === $default) {
            return null;
        }
        if (0 === $array[$key]) {
            return $default;
        }
        if ('0' === $array[$key]) {
            return $default;
        }
        if (null !== $array[$key]) {
            return (string) $array[$key];
        }

        if (null !== $default) {
            return $default;
        }

        return null;
    }

    private function date(?string $string): ?Carbon
    {
        if (null === $string) {
            return null;
        }
        //        app('log')->debug(sprintf('Now in date("%s")', $string));
        if (10 === strlen($string)) {
            $res = Carbon::createFromFormat('Y-m-d', $string, config('app.timezone'));
            if (null === $res) {
                return null;
            }

            return $res;
        }
        if (25 === strlen($string)) {
            return Carbon::parse($string, config('app.timezone'));
        }
        if (19 === strlen($string) && str_contains($string, 'T')) {
            $res = Carbon::createFromFormat('Y-m-d\TH:i:s', substr($string, 0, 19), config('app.timezone'));
            if (null === $res) {
                return null;
            }

            return $res;
        }

        // 2022-01-01 01:01:01
        $res = Carbon::createFromFormat('Y-m-d H:i:s', substr($string, 0, 19), config('app.timezone'));
        if (null === $res) {
            return null;
        }

        return $res;
    }

    private function transformJournals(TransactionGroup $group): array
    {
        $return = [];

        /** @var TransactionJournal $journal */
        foreach ($group->transactionJournals as $journal) {
            $return[] = $this->transformJournal($journal);
        }

        return $return;
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function transformJournal(TransactionJournal $journal): array
    {
        $id                  = $journal->id;

        /** @var null|TransactionCurrency $foreignCurrency */
        $foreignCurrency     = null;

        /** @var TransactionCurrency $currency */
        $currency            = $this->currencies[$this->journals[$id]['currency_id']];
        $nativeForeignAmount = null;
        $amount              = $this->journals[$journal->id]['amount'];
        $foreignAmount       = $this->journals[$journal->id]['foreign_amount'];
        $meta                = new NullArrayObject($this->meta[$id] ?? []);

        // has foreign amount?
        if (null !== $foreignAmount) {
            $foreignCurrency     = $this->currencies[$this->journals[$id]['foreign_currency_id']];
            $nativeForeignAmount = $this->converter->convert($this->default, $foreignCurrency, $journal->date, $foreignAmount);
        }

        $nativeAmount        = $this->converter->convert($this->default, $currency, $journal->date, $amount);

        $longitude           = null;
        $latitude            = null;
        $zoomLevel           = null;
        if (array_key_exists('location', $this->journals[$id])) {
            $latitude  = (string) $this->journals[$id]['location']['latitude'];
            $longitude = (string) $this->journals[$id]['location']['longitude'];
            $zoomLevel = $this->journals[$id]['location']['zoom_level'];
        }

        return [
            'user'                            => (string) $journal->user_id,
            'user_group'                      => (string) $journal->user_group_id,
            'transaction_journal_id'          => (string) $journal->id,
            'type'                            => $this->journals[$journal->id]['type'],
            'date'                            => $journal->date->toAtomString(),
            'order'                           => $journal->order,
            'amount'                          => $amount,
            'native_amount'                   => $nativeAmount,
            'foreign_amount'                  => $foreignAmount,
            'native_foreign_amount'           => $nativeForeignAmount,
            'currency_id'                     => (string) $currency->id,
            'currency_code'                   => $currency->code,
            'currency_name'                   => $currency->name,
            'currency_symbol'                 => $currency->symbol,
            'currency_decimal_places'         => $currency->decimal_places,

            // converted to native currency
            'native_currency_id'              => (string) $this->default->id,
            'native_currency_code'            => $this->default->code,
            'native_currency_name'            => $this->default->name,
            'native_currency_symbol'          => $this->default->symbol,
            'native_currency_decimal_places'  => $this->default->decimal_places,

            // foreign currency amount:
            'foreign_currency_id'             => $foreignCurrency?->id,
            'foreign_currency_code'           => $foreignCurrency?->code,
            'foreign_currency_name'           => $foreignCurrency?->name,
            'foreign_currency_symbol'         => $foreignCurrency?->symbol,
            'foreign_currency_decimal_places' => $foreignCurrency?->decimal_places,

            'description'                     => $journal->description,
            'source_id'                       => (string) $this->journals[$id]['source_account_id'],
            'source_name'                     => $this->journals[$id]['source_account_name'],
            'source_iban'                     => $this->journals[$id]['source_account_iban'],
            'source_type'                     => $this->journals[$id]['source_account_type'],

            'destination_id'                  => (string) $this->journals[$id]['destination_account_id'],
            'destination_name'                => $this->journals[$id]['destination_account_name'],
            'destination_iban'                => $this->journals[$id]['destination_account_iban'],
            'destination_type'                => $this->journals[$id]['destination_account_type'],

            'budget_id'                       => $this->journals[$id]['budget_id'],
            'budget_name'                     => $this->journals[$id]['budget_name'],
            'category_id'                     => $this->journals[$id]['category_id'],
            'category_name'                   => $this->journals[$id]['category_name'],
            'bill_id'                         => $this->journals[$id]['bill_id'],
            'bill_name'                       => $this->journals[$id]['bill_name'],
            'reconciled'                      => $this->journals[$id]['reconciled'],
            'notes'                           => $this->journals[$id]['notes'] ?? null,
            'tags'                            => $this->journals[$id]['tags'] ?? [],
            'internal_reference'              => $meta['internal_reference'],
            'external_id'                     => $meta['external_id'],
            'original_source'                 => $meta['original_source'],
            'recurrence_id'                   => $meta['recurrence_id'],
            'recurrence_total'                => $meta['recurrence_total'],
            'recurrence_count'                => $meta['recurrence_count'],
            'external_url'                    => $meta['external_url'],
            'import_hash_v2'                  => $meta['import_hash_v2'],
            'sepa_cc'                         => $meta['sepa_cc'],
            'sepa_ct_op'                      => $meta['sepa_ct_op'],
            'sepa_ct_id'                      => $meta['sepa_ct_id'],
            'sepa_db'                         => $meta['sepa_db'],
            'sepa_country'                    => $meta['sepa_country'],
            'sepa_ep'                         => $meta['sepa_ep'],
            'sepa_ci'                         => $meta['sepa_ci'],
            'sepa_batch_id'                   => $meta['sepa_batch_id'],
            'interest_date'                   => $this->date($meta['interest_date']),
            'book_date'                       => $this->date($meta['book_date']),
            'process_date'                    => $this->date($meta['process_date']),
            'due_date'                        => $this->date($meta['due_date']),
            'payment_date'                    => $this->date($meta['payment_date']),
            'invoice_date'                    => $this->date($meta['invoice_date']),

            // location data
            'longitude'                       => $longitude,
            'latitude'                        => $latitude,
            'zoom_level'                      => $zoomLevel,
            //
            //            'has_attachments' => $this->hasAttachments((int) $row['transaction_journal_id']),
        ];
    }
}
