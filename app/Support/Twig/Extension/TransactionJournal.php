<?php
/**
 * TransactionJournal.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Twig\Extension;

use Carbon\Carbon;
use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionJournal as JournalModel;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Twig_Extension;

/**
 * Class TransactionJournal
 */
class TransactionJournal extends Twig_Extension
{
    /**
     * @param JournalModel $journal
     * @param string       $field
     *
     * @return null|Carbon
     */
    public function getMetaDate(JournalModel $journal, string $field): ?Carbon
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);

        return $repository->getMetaDate($journal, $field);
    }

    /**
     * @param JournalModel $journal
     * @param string       $field
     *
     * @return string
     */
    public function getMetaField(JournalModel $journal, string $field): string
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $result     = $repository->getMetaField($journal, $field);
        if (null === $result) {
            return '';
        }

        return $result;
    }

    /**
     * Return if journal HAS field.
     *
     * @param JournalModel $journal
     * @param string       $field
     *
     * @return bool
     */
    public function hasMetaField(JournalModel $journal, string $field): bool
    {
        // HIER BEN JE
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $result     = $repository->getMetaField($journal, $field);
        if (null === $result) {
            return false;
        }
        if ('' === (string)$result) {
            return false;
        }

        return true;
    }

    /**
     * @param JournalModel $journal
     *
     * @return string
     */
    public function totalAmount(JournalModel $journal): string
    {
        $type   = $journal->transactionType->type;
        $totals = $this->getTotalAmount($journal);
        $array  = [];
        foreach ($totals as $total) {
            if (TransactionType::WITHDRAWAL === $type) {
                $total['amount'] = bcmul($total['amount'], '-1');
            }
            if (null !== $total['currency']) {
                $array[] = app('amount')->formatAnything($total['currency'], $total['amount']);
            }
        }

        return implode(' / ', $array);
    }

    /**
     * @param JournalModel $journal
     *
     * @return string
     */
    public function totalAmountPlain(JournalModel $journal): string
    {
        $type   = $journal->transactionType->type;
        $totals = $this->getTotalAmount($journal);
        $array  = [];

        foreach ($totals as $total) {
            if (TransactionType::WITHDRAWAL === $type) {
                $total['amount'] = bcmul($total['amount'], '-1');
            }
            $array[] = app('amount')->formatAnything($total['currency'], $total['amount'], false);
        }

        return implode(' / ', $array);
    }

    /**
     * @param JournalModel $journal
     *
     * @return array
     */
    private function getTotalAmount(JournalModel $journal): array
    {
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        $totals       = [];

        /** @var TransactionModel $transaction */
        foreach ($transactions as $transaction) {
            $currencyId = $transaction->transaction_currency_id;
            $currency   = $transaction->transactionCurrency;

            if (!isset($totals[$currencyId])) {
                $totals[$currencyId] = [
                    'amount'   => '0',
                    'currency' => $currency,
                ];
            }
            $totals[$currencyId]['amount'] = bcadd($transaction->amount, $totals[$currencyId]['amount']);

            if (null !== $transaction->foreign_currency_id) {
                $foreignAmount = $transaction->foreign_amount ?? '0';
                $foreignId = $transaction->foreign_currency_id;
                $foreign   = $transaction->foreignCurrency;
                if (!isset($totals[$foreignId])) {
                    $totals[$foreignId] = [
                        'amount'   => '0',
                        'currency' => $foreign,
                    ];
                }
                $totals[$foreignId]['amount'] = bcadd(
                    $foreignAmount,
                    $totals[$foreignId]['amount']
                );
            }
        }

        return $totals;
    }
}
