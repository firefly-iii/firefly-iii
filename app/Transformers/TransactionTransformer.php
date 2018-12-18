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
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Log;

/**
 * Class TransactionTransformer
 */
class TransactionTransformer extends AbstractTransformer
{
    /** @var JournalRepositoryInterface */
    protected $repository;

    /**
     * TransactionTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(JournalRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
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
        $categoryId   = $transaction->transaction_category_id ?? $transaction->transaction_journal_category_id;
        $categoryName = $transaction->transaction_category_name ?? $transaction->transaction_journal_category_name;
        $journal      = $transaction->transactionJournal;
        $notes        = $this->repository->getNoteText($journal);
        if ($transaction->transaction_type_type === TransactionType::WITHDRAWAL) {
            $budgetId   = $transaction->transaction_budget_id ?? $transaction->transaction_journal_budget_id;
            $budgetName = $transaction->transaction_budget_name ?? $transaction->transaction_journal_budget_name;
        }
        // get tags:
        $tags = implode(',', $journal->tags->pluck('tag')->toArray());


        $data = [
            'id'                              => (int)$transaction->id,
            'created_at'                      => $transaction->created_at->toAtomString(),
            'updated_at'                      => $transaction->updated_at->toAtomString(),
            'description'                     => $transaction->description,
            'transaction_description'         => $transaction->transaction_description,
            'date'                            => $transaction->date->format('Y-m-d'),
            'type'                            => $transaction->transaction_type_type,
            'identifier'                      => $transaction->identifier,
            'journal_id'                      => (int)$transaction->journal_id,
            'reconciled'                      => (bool)$transaction->reconciled,
            'amount'                          => round($transaction->transaction_amount, (int)$transaction->transaction_currency_dp),
            'currency_id'                     => $transaction->transaction_currency_id,
            'currency_code'                   => $transaction->transaction_currency_code,
            'currency_symbol'                 => $transaction->transaction_currency_symbol,
            'currency_decimal_places'         => $transaction->transaction_currency_dp,
            'foreign_amount'                  => null,
            'foreign_currency_id'             => $transaction->foreign_currency_id,
            'foreign_currency_code'           => $transaction->foreign_currency_code,
            'foreign_currency_symbol'         => $transaction->foreign_currency_symbol,
            'foreign_currency_decimal_places' => $transaction->foreign_currency_dp,
            'bill_id'                         => $transaction->bill_id,
            'bill_name'                       => $transaction->bill_name,
            'category_id'                     => $categoryId,
            'category_name'                   => $categoryName,
            'budget_id'                       => $budgetId,
            'budget_name'                     => $budgetName,
            'notes'                           => $notes,
            'sepa_cc'                         => $this->repository->getMetaField($journal, 'sepa-cc'),
            'sepa_ct_op'                      => $this->repository->getMetaField($journal, 'sepa-ct-op'),
            'sepa_ct_id'                      => $this->repository->getMetaField($journal, 'sepa-ct-ud'),
            'sepa_db'                         => $this->repository->getMetaField($journal, 'sepa-db'),
            'sepa_country'                    => $this->repository->getMetaField($journal, 'sepa-country'),
            'sepa_ep'                         => $this->repository->getMetaField($journal, 'sepa-ep'),
            'sepa_ci'                         => $this->repository->getMetaField($journal, 'sepa-ci'),
            'sepa_batch_id'                   => $this->repository->getMetaField($journal, 'sepa-batch-id'),
            'interest_date'                   => $this->repository->getMetaDateString($journal, 'interest_date'),
            'book_date'                       => $this->repository->getMetaDateString($journal, 'book_date'),
            'process_date'                    => $this->repository->getMetaDateString($journal, 'process_date'),
            'due_date'                        => $this->repository->getMetaDateString($journal, 'due_date'),
            'payment_date'                    => $this->repository->getMetaDateString($journal, 'payment_date'),
            'invoice_date'                    => $this->repository->getMetaDateString($journal, 'invoice_date'),
            'internal_reference'              => $this->repository->getMetaField($journal, 'internal_reference'),
            'bunq_payment_id'                 => $this->repository->getMetaField($journal, 'bunq_payment_id'),
            'importHashV2'                    => $this->repository->getMetaField($journal, 'importHashV2'),
            'recurrence_id'                   => (int)$this->repository->getMetaField($journal, 'recurrence_id'),
            'external_id'                     => $this->repository->getMetaField($journal, 'external_id'),
            'original_source'                 => $this->repository->getMetaField($journal, 'original-source'),
            'tags'                            => '' === $tags ? null : $tags,
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $transaction->id,
                ],
            ],
        ];

        // expand foreign amount:
        if (null !== $transaction->transaction_foreign_amount) {
            $data['foreign_amount'] = round($transaction->transaction_foreign_amount, (int)$transaction->foreign_currency_dp);
        }
        // switch on type for consistency
        switch ($transaction->transaction_type_type) {
            case TransactionType::WITHDRAWAL:
                Log::debug(sprintf('%d is a withdrawal', $transaction->journal_id));
                $data['source_id']        = $transaction->account_id;
                $data['source_name']      = $transaction->account_name;
                $data['source_iban']      = $transaction->account_iban;
                $data['source_type']      = $transaction->account_type;
                $data['destination_id']   = $transaction->opposing_account_id;
                $data['destination_name'] = $transaction->opposing_account_name;
                $data['destination_iban'] = $transaction->opposing_account_iban;
                $data['destination_type'] = $transaction->opposing_account_type;
                Log::debug(sprintf('source_id / account_id is %d', $transaction->account_id));
                Log::debug(sprintf('source_name / account_name is "%s"', $transaction->account_name));
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
        if (\strlen((string)$transaction->transaction_description) > 0) {
            $data['description'] = $transaction->transaction_description . ' (' . $transaction->description . ')';
        }


        return $data;
    }
}
