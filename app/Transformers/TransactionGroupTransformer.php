<?php
/**
 * TransactionGroupTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\NullArrayObject;

/**
 * Class TransactionGroupTransformer
 */
class TransactionGroupTransformer extends AbstractTransformer
{
    /** @var TransactionGroupRepositoryInterface */
    private $groupRepos;
    /** @var array Array with meta date fields. */
    private $metaDateFields;
    /** @var array Array with meta fields. */
    private $metaFields;

    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->groupRepos     = app(TransactionGroupRepositoryInterface::class);
        $this->metaFields     = [
            'sepa_cc', 'sepa_ct_op', 'sepa_ct_id', 'sepa_db', 'sepa_country', 'sepa_ep',
            'sepa_ci', 'sepa_batch_id', 'internal_reference', 'bunq_payment_id', 'import_hash_v2',
            'recurrence_id', 'external_id', 'original_source',
        ];
        $this->metaDateFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];

        if ('testing' === config('app.env')) {
            app('log')->warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param array $group
     *
     * @return array
     */
    public function transform(array $group): array
    {
        $data   = new NullArrayObject($group);
        $first  = new NullArrayObject(reset($group['transactions']));
        $result = [
            'id'           => (int)$first['transaction_group_id'],
            'created_at'   => $first['created_at']->toAtomString(),
            'updated_at'   => $first['updated_at']->toAtomString(),
            'user'         => (int)$data['user_id'],
            'group_title'  => $data['title'],
            'transactions' => $this->transformTransactions($data),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $first['transaction_group_id'],
                ],
            ],
        ];

        // do something else.

        return $result;
    }

    /**
     * @param NullArrayObject $data
     *
     * @return array
     */
    private function transformTransactions(NullArrayObject $data): array
    {
        $result       = [];
        $transactions = $data['transactions'] ?? [];
        foreach ($transactions as $transaction) {
            $row = new NullArrayObject($transaction);

            // amount:
            $type   = $row['transaction_type_type'] ?? TransactionType::WITHDRAWAL;
            $amount = $row['amount'] ?? '0';
            if (TransactionType::WITHDRAWAL !== $type) {
                $amount = bcmul($amount, '-1');
            }
            $foreignAmount = null;
            if (null !== $row['foreign_amount']) {
                $foreignAmount = TransactionType::WITHDRAWAL !== $type ? bcmul($row['foreign_amount'], '-1') : $row['foreign_amount'];
            }

            $metaFieldData = $this->groupRepos->getMetaFields((int)$row['transaction_journal_id'], $this->metaFields);
            $metaDateData  = $this->groupRepos->getMetaDateFields((int)$row['transaction_journal_id'], $this->metaDateFields);

            $result[] = [
                'user'                   => (int)$row['user_id'],
                'transaction_journal_id' => $row['transaction_journal_id'],
                'type'                   => strtolower($type),
                'date'                   => $row['date']->toAtomString(),

                'currency_id'             => $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],

                'foreign_currency_id'             => $row['foreign_currency_id'],
                'foreign_currency_code'           => $row['foreign_currency_code'],
                'foreign_currency_symbol'         => $row['foreign_currency_symbol'],
                'foreign_currency_decimal_places' => $row['foreign_currency_decimal_places'],

                'amount'         => $amount,
                'foreign_amount' => $foreignAmount,

                'description' => $row['description'],

                'source_id'   => $row['source_account_id'],
                'source_name' => $row['source_account_name'],
                'source_iban' => $row['source_account_iban'],
                'source_type' => $row['source_account_type'],

                'destination_id'   => $row['destination_account_id'],
                'destination_name' => $row['destination_account_name'],
                'destination_iban' => $row['destination_account_iban'],
                'destination_type' => $row['destination_account_type'],

                'budget_id'   => $row['budget_id'],
                'budget_name' => $row['budget_name'],

                'category_id'   => $row['category_id'],
                'category_name' => $row['category_name'],

                'bill_id'   => $row['bill_id'],
                'bill_name' => $row['bill_name'],

                'reconciled' => $row['reconciled'],
                'notes'      => $this->groupRepos->getNoteText((int)$row['transaction_journal_id']),
                'tags'       => $this->groupRepos->getTags((int)$row['transaction_journal_id']),

                'internal_reference' => $metaFieldData['internal_reference'],
                'external_id'        => $metaFieldData['external_id'],
                'original_source'    => $metaFieldData['original_source'],
                'recurrence_id'      => $metaFieldData['recurrence_id'],
                'bunq_payment_id'    => $metaFieldData['bunq_payment_id'],
                'import_hash_v2'       => $metaFieldData['import_hash_v2'],

                'sepa_cc'       => $metaFieldData['sepa_cc'],
                'sepa_ct_op'    => $metaFieldData['sepa_ct_op'],
                'sepa_ct_id'    => $metaFieldData['sepa_ct_id'],
                'sepa_db'       => $metaFieldData['sepa_ddb'],
                'sepa_country'  => $metaFieldData['sepa_country'],
                'sepa_ep'       => $metaFieldData['sepa_ep'],
                'sepa_ci'       => $metaFieldData['sepa_ci'],
                'sepa_batch_id' => $metaFieldData['sepa_batch_id'],

                'interest_date' => $metaDateData['interest_date'] ? $metaDateData['interest_date']->toAtomString() : null,
                'book_date'     => $metaDateData['book_date'] ? $metaDateData['book_date']->toAtomString() : null,
                'process_date'  => $metaDateData['process_date'] ? $metaDateData['process_date']->toAtomString() : null,
                'due_date'      => $metaDateData['due_date'] ? $metaDateData['due_date']->toAtomString() : null,
                'payment_date'  => $metaDateData['payment_date'] ? $metaDateData['payment_date']->toAtomString() : null,
                'invoice_date'  => $metaDateData['invoice_date'] ? $metaDateData['invoice_date']->toAtomString() : null,
            ];
        }

        return $result;
    }
}