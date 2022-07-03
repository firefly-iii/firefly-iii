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

namespace FireflyIII\Transformers\V2;

use FireflyIII\Models\TransactionType;
use FireflyIII\Support\NullArrayObject;

/**
 * Class TransactionGroupTransformer
 */
class TransactionGroupTransformer extends AbstractTransformer
{
    /**
     * @param array $group
     *
     * @return array
     */
    public function transform(array $group): array
    {
        $first = reset($group['transactions']);

        return [
            'id'           => (string) $group['id'],
            'created_at'   => $first['created_at']->toAtomString(),
            'updated_at'   => $first['updated_at']->toAtomString(),
            'user'         => (string) $first['user_id'],
            'group_title'  => $group['group_title'] ?? null,
            'transactions' => $this->transformTransactions($group['transactions'] ?? []),
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/transactions/%d', $group['id']),
                ],
            ],
        ];
    }

    /**
     * @param array $transactions
     * @return array
     */
    private function transformTransactions(array $transactions): array
    {
        $return = [];
        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return[] = $this->transformTransaction($transaction);
        }
        return $return;
    }

    private function transformTransaction(array $transaction): array
    {
        $transaction   = new NullArrayObject($transaction);
        $type          = $this->stringFromArray($transaction, 'transaction_type_type', TransactionType::WITHDRAWAL);
        $amount        = app('steam')->positive((string) ($row['amount'] ?? '0'));
        $foreignAmount = null;
        if (null !== $transaction['foreign_amount']) {
            $foreignAmount = app('steam')->positive($transaction['foreign_amount']);
        }

        return [
            'user'                            => (string) $transaction['user_id'],
            'transaction_journal_id'          => (string) $transaction['transaction_journal_id'],
            'type'                            => strtolower($type),
            'date'                            => $transaction['date']->toAtomString(),
            'order'                           => $transaction['order'],
            'currency_id'                     => (string) $transaction['currency_id'],
            'currency_code'                   => $transaction['currency_code'],
            'currency_name'                   => $transaction['currency_name'],
            'currency_symbol'                 => $transaction['currency_symbol'],
            'currency_decimal_places'         => (int) $transaction['currency_decimal_places'],
            'foreign_currency_id'             => $this->stringFromArray($transaction, 'foreign_currency_id', null),
            'foreign_currency_code'           => $transaction['foreign_currency_code'],
            'foreign_currency_symbol'         => $transaction['foreign_currency_symbol'],
            'foreign_currency_decimal_places' => $transaction['foreign_currency_decimal_places'],
            'amount'                          => $amount,
            'foreign_amount'                  => $foreignAmount,
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

            //'notes'      => $this->groupRepos->getNoteText((int) $row['transaction_journal_id']),
            //'tags'       => $this->groupRepos->getTags((int) $row['transaction_journal_id']),

            //            'internal_reference' => $metaFieldData['internal_reference'],
            //            'external_id'        => $metaFieldData['external_id'],
            //            'original_source'    => $metaFieldData['original_source'],
            //            'recurrence_id'      => $this->stringFromArray($metaFieldData->getArrayCopy(), 'recurrence_id', null),
            //            'recurrence_total'   => $this->integerFromArray($metaFieldData->getArrayCopy(), 'recurrence_total'),
            //            'recurrence_count'   => $this->integerFromArray($metaFieldData->getArrayCopy(), 'recurrence_count'),
            //            'bunq_payment_id'    => $metaFieldData['bunq_payment_id'],
            //            'external_url'       => $metaFieldData['external_url'],
            //            'import_hash_v2'     => $metaFieldData['import_hash_v2'],

            //            'sepa_cc'       => $metaFieldData['sepa_cc'],
            //            'sepa_ct_op'    => $metaFieldData['sepa_ct_op'],
            //            'sepa_ct_id'    => $metaFieldData['sepa_ct_id'],
            //            'sepa_db'       => $metaFieldData['sepa_db'],
            //            'sepa_country'  => $metaFieldData['sepa_country'],
            //            'sepa_ep'       => $metaFieldData['sepa_ep'],
            //            'sepa_ci'       => $metaFieldData['sepa_ci'],
            //            'sepa_batch_id' => $metaFieldData['sepa_batch_id'],

            //            'interest_date' => $this->dateFromArray($metaDateData, 'interest_date'),
            //            'book_date'     => $this->dateFromArray($metaDateData, 'book_date'),
            //            'process_date'  => $this->dateFromArray($metaDateData, 'process_date'),
            //            'due_date'      => $this->dateFromArray($metaDateData, 'due_date'),
            //            'payment_date'  => $this->dateFromArray($metaDateData, 'payment_date'),
            //            'invoice_date'  => $this->dateFromArray($metaDateData, 'invoice_date'),

            // location data
            //            'longitude'     => $longitude,
            //            'latitude'      => $latitude,
            //            'zoom_level'    => $zoomLevel,
            //
            //            'has_attachments' => $this->hasAttachments((int) $row['transaction_journal_id']),
        ];
    }

    /**
     * TODO also in the old transformer.
     *
     * @param NullArrayObject $array
     * @param string          $key
     * @param string|null     $default
     *
     * @return string|null
     */
    private function stringFromArray(NullArrayObject $array, string $key, ?string $default): ?string
    {
        if (null === $array[$key] && null === $default) {
            return null;
        }
        if (null !== $array[$key]) {
            return (string) $array[$key];
        }

        if (null !== $default) {
            return $default;
        }

        return null;
    }

}
