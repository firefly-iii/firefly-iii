<?php

/**
 * TransactionGroupTransformer.php
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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Location;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use Illuminate\Support\Collection;

/**
 * Class TransactionGroupTransformer
 */
class TransactionGroupTransformer extends AbstractTransformer
{
    private TransactionGroupRepositoryInterface $groupRepos;
    private array                               $metaDateFields;
    private array                               $metaFields;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->groupRepos     = app(TransactionGroupRepositoryInterface::class);
        $this->metaFields     = [
            'sepa_cc',
            'sepa_ct_op',
            'sepa_ct_id',
            'sepa_db',
            'sepa_country',
            'sepa_ep',
            'sepa_ci',
            'sepa_batch_id',
            'internal_reference',
            'bunq_payment_id',
            'import_hash_v2',
            'recurrence_id',
            'external_id',
            'original_source',
            'external_url',
            'recurrence_count',
            'recurrence_total',
        ];
        $this->metaDateFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];
    }

    public function transform(array $group): array
    {
        $data  = new NullArrayObject($group);
        $first = new NullArrayObject(reset($group['transactions']));

        return [
            'id'           => (int) $first['transaction_group_id'],
            'created_at'   => $first['created_at']->toAtomString(),
            'updated_at'   => $first['updated_at']->toAtomString(),
            'user'         => (string) $data['user_id'],
            'group_title'  => $data['title'],
            'transactions' => $this->transformTransactions($data),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/'.$first['transaction_group_id'],
                ],
            ],
        ];
    }

    private function transformTransactions(NullArrayObject $data): array
    {
        $result       = [];
        $transactions = $data['transactions'] ?? [];
        foreach ($transactions as $transaction) {
            $result[] = $this->transformTransaction($transaction);
        }

        return $result;
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function transformTransaction(array $transaction): array
    {
        $row           = new NullArrayObject($transaction);

        // amount:
        $amount        = app('steam')->positive((string) ($row['amount'] ?? '0'));
        $foreignAmount = null;
        if (null !== $row['foreign_amount'] && '' !== $row['foreign_amount'] && 0 !== bccomp('0', $row['foreign_amount'])) {
            $foreignAmount = app('steam')->positive($row['foreign_amount']);
        }

        $metaFieldData = $this->groupRepos->getMetaFields((int) $row['transaction_journal_id'], $this->metaFields);
        $metaDateData  = $this->groupRepos->getMetaDateFields((int) $row['transaction_journal_id'], $this->metaDateFields);
        $type          = $this->stringFromArray($transaction, 'transaction_type_type', TransactionTypeEnum::WITHDRAWAL->value);

        $longitude     = null;
        $latitude      = null;
        $zoomLevel     = null;
        $location      = $this->getLocationById((int) $row['transaction_journal_id']);
        if (null !== $location) {
            $longitude = $location->longitude;
            $latitude  = $location->latitude;
            $zoomLevel = $location->zoom_level;
        }

        return [
            'user'                            => (string) $row['user_id'],
            'transaction_journal_id'          => (string) $row['transaction_journal_id'],
            'type'                            => strtolower($type),
            'date'                            => $row['date']->toAtomString(),
            'order'                           => $row['order'],

            'currency_id'                     => (string) $row['currency_id'],
            'currency_code'                   => $row['currency_code'],
            'currency_name'                   => $row['currency_name'],
            'currency_symbol'                 => $row['currency_symbol'],
            'currency_decimal_places'         => (int) $row['currency_decimal_places'],

            'foreign_currency_id'             => $this->stringFromArray($transaction, 'foreign_currency_id', null),
            'foreign_currency_code'           => $row['foreign_currency_code'],
            'foreign_currency_symbol'         => $row['foreign_currency_symbol'],
            'foreign_currency_decimal_places' => $row['foreign_currency_decimal_places'],

            'amount'                          => $amount,
            'foreign_amount'                  => $foreignAmount,

            'description'                     => $row['description'],

            'source_id'                       => (string) $row['source_account_id'],
            'source_name'                     => $row['source_account_name'],
            'source_iban'                     => $row['source_account_iban'],
            'source_type'                     => $row['source_account_type'],

            'destination_id'                  => (string) $row['destination_account_id'],
            'destination_name'                => $row['destination_account_name'],
            'destination_iban'                => $row['destination_account_iban'],
            'destination_type'                => $row['destination_account_type'],

            'budget_id'                       => $this->stringFromArray($transaction, 'budget_id', null),
            'budget_name'                     => $row['budget_name'],

            'category_id'                     => $this->stringFromArray($transaction, 'category_id', null),
            'category_name'                   => $row['category_name'],

            'bill_id'                         => $this->stringFromArray($transaction, 'bill_id', null),
            'bill_name'                       => $row['bill_name'],

            'reconciled'                      => $row['reconciled'],
            'notes'                           => $this->groupRepos->getNoteText((int) $row['transaction_journal_id']),
            'tags'                            => $this->groupRepos->getTags((int) $row['transaction_journal_id']),

            'internal_reference'              => $metaFieldData['internal_reference'],
            'external_id'                     => $metaFieldData['external_id'],
            'original_source'                 => $metaFieldData['original_source'],
            'recurrence_id'                   => $this->stringFromArray($metaFieldData->getArrayCopy(), 'recurrence_id', null),
            'recurrence_total'                => $this->integerFromArray($metaFieldData->getArrayCopy(), 'recurrence_total'),
            'recurrence_count'                => $this->integerFromArray($metaFieldData->getArrayCopy(), 'recurrence_count'),
            'bunq_payment_id'                 => $metaFieldData['bunq_payment_id'],
            'external_url'                    => $metaFieldData['external_url'],
            'import_hash_v2'                  => $metaFieldData['import_hash_v2'],

            'sepa_cc'                         => $metaFieldData['sepa_cc'],
            'sepa_ct_op'                      => $metaFieldData['sepa_ct_op'],
            'sepa_ct_id'                      => $metaFieldData['sepa_ct_id'],
            'sepa_db'                         => $metaFieldData['sepa_db'],
            'sepa_country'                    => $metaFieldData['sepa_country'],
            'sepa_ep'                         => $metaFieldData['sepa_ep'],
            'sepa_ci'                         => $metaFieldData['sepa_ci'],
            'sepa_batch_id'                   => $metaFieldData['sepa_batch_id'],

            'interest_date'                   => $this->dateFromArray($metaDateData, 'interest_date'),
            'book_date'                       => $this->dateFromArray($metaDateData, 'book_date'),
            'process_date'                    => $this->dateFromArray($metaDateData, 'process_date'),
            'due_date'                        => $this->dateFromArray($metaDateData, 'due_date'),
            'payment_date'                    => $this->dateFromArray($metaDateData, 'payment_date'),
            'invoice_date'                    => $this->dateFromArray($metaDateData, 'invoice_date'),

            // location data
            'longitude'                       => $longitude,
            'latitude'                        => $latitude,
            'zoom_level'                      => $zoomLevel,

            'has_attachments'                 => $this->hasAttachments((int) $row['transaction_journal_id']),
        ];
    }

    private function stringFromArray(array $array, string $key, ?string $default): ?string
    {
        if (array_key_exists($key, $array) && null === $array[$key]) {
            return null;
        }
        if (array_key_exists($key, $array) && null !== $array[$key]) {
            if (0 === $array[$key]) {
                return $default;
            }
            if ('0' === $array[$key]) {
                return $default;
            }

            return (string) $array[$key];
        }

        if (null !== $default) {
            return $default;
        }

        return null;
    }

    private function getLocationById(int $journalId): ?Location
    {
        return $this->groupRepos->getLocation($journalId);
    }

    private function getLocation(TransactionJournal $journal): ?Location
    {
        /** @var Location|null */
        return $journal->locations()->first();
    }

    private function integerFromArray(array $array, string $key): ?int
    {
        if (array_key_exists($key, $array)) {
            return (int) $array[$key];
        }

        return null;
    }

    private function dateFromArray(NullArrayObject $object, string $key): ?string
    {
        if (null === $object[$key]) {
            return null;
        }

        return $object[$key]->toAtomString();
    }

    private function hasAttachments(int $journalId): bool
    {
        return $this->groupRepos->countAttachments($journalId) > 0;
    }

    /**
     * @throws FireflyException
     */
    public function transformObject(TransactionGroup $group): array
    {
        try {
            $result = [
                'id'           => $group->id,
                'created_at'   => $group->created_at->toAtomString(),
                'updated_at'   => $group->updated_at->toAtomString(),
                'user'         => $group->user_id,
                'group_title'  => $group->title,
                'transactions' => $this->transformJournals($group->transactionJournals),
                'links'        => [
                    [
                        'rel' => 'self',
                        'uri' => '/transactions/'.$group->id,
                    ],
                ],
            ];
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException(sprintf('Transaction group #%d is broken. Please check out your log files.', $group->id), 0, $e);
        }

        // do something else.

        return $result;
    }

    /**
     * @throws FireflyException
     */
    private function transformJournals(Collection $transactionJournals): array
    {
        $result = [];

        /** @var TransactionJournal $journal */
        foreach ($transactionJournals as $journal) {
            $result[] = $this->transformJournal($journal);
        }

        return $result;
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function transformJournal(TransactionJournal $journal): array
    {
        $source          = $this->getSourceTransaction($journal);
        $destination     = $this->getDestinationTransaction($journal);
        $type            = $journal->transactionType->type;
        $currency        = $source->transactionCurrency;
        $amount          = app('steam')->bcround($this->getAmount($source->amount), $currency->decimal_places ?? 0);
        $foreignAmount   = $this->getForeignAmount(null === $source->foreign_amount ? null : $source->foreign_amount);
        $metaFieldData   = $this->groupRepos->getMetaFields($journal->id, $this->metaFields);
        $metaDates       = $this->getDates($this->groupRepos->getMetaDateFields($journal->id, $this->metaDateFields));
        $foreignCurrency = $this->getForeignCurrency($source->foreignCurrency);
        $budget          = $this->getBudget($journal->budgets->first());
        $category        = $this->getCategory($journal->categories->first());
        $bill            = $this->getBill($journal->bill);

        if (null !== $foreignAmount && null !== $source->foreignCurrency) {
            $foreignAmount = app('steam')->bcround($foreignAmount, $foreignCurrency['decimal_places'] ?? 0);
        }

        $longitude       = null;
        $latitude        = null;
        $zoomLevel       = null;
        $location        = $this->getLocation($journal);
        if (null !== $location) {
            $longitude = $location->longitude;
            $latitude  = $location->latitude;
            $zoomLevel = $location->zoom_level;
        }

        return [
            'user'                            => $journal->user_id,
            'transaction_journal_id'          => $journal->id,
            'type'                            => strtolower($type),
            'date'                            => $journal->date->toAtomString(),
            'order'                           => $journal->order,

            'currency_id'                     => $currency->id,
            'currency_code'                   => $currency->code,
            'currency_symbol'                 => $currency->symbol,
            'currency_decimal_places'         => $currency->decimal_places,

            'foreign_currency_id'             => $foreignCurrency['id'],
            'foreign_currency_code'           => $foreignCurrency['code'],
            'foreign_currency_symbol'         => $foreignCurrency['symbol'],
            'foreign_currency_decimal_places' => $foreignCurrency['decimal_places'],

            'amount'                          => app('steam')->bcround($amount, $currency->decimal_places),
            'foreign_amount'                  => $foreignAmount,

            'description'                     => $journal->description,

            'source_id'                       => $source->account_id,
            'source_name'                     => $source->account->name,
            'source_iban'                     => $source->account->iban,
            'source_type'                     => $source->account->accountType->type,

            'destination_id'                  => $destination->account_id,
            'destination_name'                => $destination->account->name,
            'destination_iban'                => $destination->account->iban,
            'destination_type'                => $destination->account->accountType->type,

            'budget_id'                       => $budget['id'],
            'budget_name'                     => $budget['name'],

            'category_id'                     => $category['id'],
            'category_name'                   => $category['name'],

            'bill_id'                         => $bill['id'],
            'bill_name'                       => $bill['name'],

            'reconciled'                      => $source->reconciled,
            'notes'                           => $this->groupRepos->getNoteText($journal->id),
            'tags'                            => $this->groupRepos->getTags($journal->id),

            'internal_reference'              => $metaFieldData['internal_reference'],
            'external_id'                     => $metaFieldData['external_id'],
            'original_source'                 => $metaFieldData['original_source'],
            'recurrence_id'                   => $metaFieldData['recurrence_id'],
            'bunq_payment_id'                 => $metaFieldData['bunq_payment_id'],
            'import_hash_v2'                  => $metaFieldData['import_hash_v2'],

            'sepa_cc'                         => $metaFieldData['sepa_cc'],
            'sepa_ct_op'                      => $metaFieldData['sepa_ct_op'],
            'sepa_ct_id'                      => $metaFieldData['sepa_ct_id'],
            'sepa_db'                         => $metaFieldData['sepa_db'],
            'sepa_country'                    => $metaFieldData['sepa_country'],
            'sepa_ep'                         => $metaFieldData['sepa_ep'],
            'sepa_ci'                         => $metaFieldData['sepa_ci'],
            'sepa_batch_id'                   => $metaFieldData['sepa_batch_id'],

            'interest_date'                   => $metaDates['interest_date'],
            'book_date'                       => $metaDates['book_date'],
            'process_date'                    => $metaDates['process_date'],
            'due_date'                        => $metaDates['due_date'],
            'payment_date'                    => $metaDates['payment_date'],
            'invoice_date'                    => $metaDates['invoice_date'],

            // location data
            'longitude'                       => $longitude,
            'latitude'                        => $latitude,
            'zoom_level'                      => $zoomLevel,
        ];
    }

    /**
     * @throws FireflyException
     */
    private function getSourceTransaction(TransactionJournal $journal): Transaction
    {
        $result = $journal->transactions->first(
            static function (Transaction $transaction) {
                return (float) $transaction->amount < 0; // lame but it works.
            }
        );
        if (null === $result) {
            throw new FireflyException(sprintf('Journal #%d unexpectedly has no source transaction.', $journal->id));
        }

        return $result;
    }

    /**
     * @throws FireflyException
     */
    private function getDestinationTransaction(TransactionJournal $journal): Transaction
    {
        $result = $journal->transactions->first(
            static function (Transaction $transaction) {
                return (float) $transaction->amount > 0; // lame but it works
            }
        );
        if (null === $result) {
            throw new FireflyException(sprintf('Journal #%d unexpectedly has no destination transaction.', $journal->id));
        }

        return $result;
    }

    private function getAmount(string $amount): string
    {
        return app('steam')->positive($amount);
    }

    private function getForeignAmount(?string $foreignAmount): ?string
    {
        $result = null;
        if (null !== $foreignAmount && '' !== $foreignAmount && 0 !== bccomp('0', $foreignAmount)) {
            $result = app('steam')->positive($foreignAmount);
        }

        return $result;
    }

    private function getDates(NullArrayObject $dates): array
    {
        $fields = [
            'interest_date',
            'book_date',
            'process_date',
            'due_date',
            'payment_date',
            'invoice_date',
        ];
        $return = [];
        foreach ($fields as $field) {
            $return[$field] = null;
            if (null !== $dates[$field]) {
                $return[$field] = $dates[$field]->toAtomString();
            }
        }

        return $return;
    }

    private function getForeignCurrency(?TransactionCurrency $currency): array
    {
        $array                   = [
            'id'             => null,
            'code'           => null,
            'symbol'         => null,
            'decimal_places' => null,
        ];
        if (null === $currency) {
            return $array;
        }
        $array['id']             = $currency->id;
        $array['code']           = $currency->code;
        $array['symbol']         = $currency->symbol;
        $array['decimal_places'] = $currency->decimal_places;

        return $array;
    }

    private function getBudget(?Budget $budget): array
    {
        $array         = [
            'id'   => null,
            'name' => null,
        ];
        if (null === $budget) {
            return $array;
        }
        $array['id']   = $budget->id;
        $array['name'] = $budget->name;

        return $array;
    }

    private function getCategory(?Category $category): array
    {
        $array         = [
            'id'   => null,
            'name' => null,
        ];
        if (null === $category) {
            return $array;
        }
        $array['id']   = $category->id;
        $array['name'] = $category->name;

        return $array;
    }

    private function getBill(?Bill $bill): array
    {
        $array         = [
            'id'   => null,
            'name' => null,
        ];
        if (null === $bill) {
            return $array;
        }
        $array['id']   = (string) $bill->id;
        $array['name'] = $bill->name;

        return $array;
    }
}
