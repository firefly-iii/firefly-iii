<?php
/**
 * TransactionController.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\SingleCacheProperties;
use Illuminate\Http\Request;
use Response;

class TransactionController extends Controller
{
    public function amounts(Request $request, JournalRepositoryInterface $repository)
    {
        $ids = $request->get('transactions');

        $cache = new SingleCacheProperties;
        $cache->addProperty('json-reconcile-amounts');
        $cache->addProperty($ids);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $totals = [];
        // for each transaction, get amount(s)
        foreach ($ids as $transactionId) {
            $transaction     = $repository->findTransaction(intval($transactionId));
            $transactionType = $transaction->transactionJournal->transactionType->type;

            // default amount:
            $currencyId = $transaction->transaction_currency_id;
            if (!isset($totals[$currencyId])) {
                $totals[$currencyId] = [
                    'amount'   => '0',
                    'currency' => $transaction->transactionCurrency,
                    'type'     => $transactionType,
                ];
            }
            // add default amount:
            $totals[$currencyId]['amount'] = bcadd($totals[$currencyId]['amount'], app('steam')->positive($transaction->amount));

            // foreign amount:
            if (null !== $transaction->foreign_amount) {
                $currencyId = $transaction->foreign_currency_id;
                if (!isset($totals[$currencyId])) {
                    $totals[$currencyId] = [
                        'amount'   => '0',
                        'currency' => $transaction->foreignCurrency,
                        'type'     => $transactionType,
                    ];
                }
                // add foreign amount:
                $totals[$currencyId]['amount'] = bcadd($totals[$currencyId]['amount'], app('steam')->positive($transaction->foreign_amount));
            }
        }
        $entries = [];
        foreach ($totals as $entry) {
            $amount = $entry['amount'];
            if (TransactionType::WITHDRAWAL === $entry['type']) {
                $amount = bcmul($entry['amount'], '-1');
            }
            $entries[] = app('amount')->formatAnything($entry['currency'], $amount, false);
        }
        $result = ['amounts' => join(' / ', $entries)];
        $cache->store($result);

        return Response::json($result);
    }
}
