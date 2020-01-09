<?php
/**
 * ShowController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Transaction;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    /** @var TransactionGroupRepositoryInterface */
    private $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(TransactionGroupRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, TransactionGroup $transactionGroup)
    {
        /** @var TransactionJournal $first */
        $first    = $transactionGroup->transactionJournals->first();
        $splits   = $transactionGroup->transactionJournals->count();
        $type     = $first->transactionType->type;
        $title    = 1 === $splits ? $first->description : $transactionGroup->title;
        $subTitle = sprintf('%s: "%s"', $type, $title);

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $groupArray = $transformer->transformObject($transactionGroup);

        // do some amount calculations:
        $amounts = $this->getAmounts($groupArray);

        // make sure notes are escaped but not double escaped.
        foreach ($groupArray['transactions'] as $index => $transaction) {
            $search = ['&amp;', '&gt;', '&lt;'];
            if (!Str::contains($transaction['notes'], $search)) {
                $groupArray['transactions'][$index]['notes'] = e($transaction['notes']);
            }
        }

        $events      = $this->repository->getPiggyEvents($transactionGroup);
        $attachments = $this->repository->getAttachments($transactionGroup);
        $links       = $this->repository->getLinks($transactionGroup);

        return view(
            'transactions.show', compact(
                                   'transactionGroup', 'amounts', 'first', 'type', 'subTitle', 'splits', 'groupArray',
                                   'events', 'attachments', 'links'
                               )
        );
    }

    /**
     * @param array $group
     * @return array
     */
    private function getAmounts(array $group): array
    {
        $amounts = [];
        foreach ($group['transactions'] as $transaction) {
            $symbol = $transaction['currency_symbol'];
            if (!isset($amounts[$symbol])) {
                $amounts[$symbol] = [
                    'amount'         => '0',
                    'symbol'         => $symbol,
                    'decimal_places' => $transaction['currency_decimal_places'],
                ];
            }
            $amounts[$symbol]['amount'] = bcadd($amounts[$symbol]['amount'], $transaction['amount']);
            if (null !== $transaction['foreign_amount']) {
                // same for foreign currency:
                $foreignSymbol = $transaction['foreign_currency_symbol'];
                if (!isset($amounts[$foreignSymbol])) {
                    $amounts[$foreignSymbol] = [
                        'amount'         => '0',
                        'symbol'         => $foreignSymbol,
                        'decimal_places' => $transaction['foreign_currency_decimal_places'],
                    ];
                }
                $amounts[$foreignSymbol]['amount'] = bcadd($amounts[$foreignSymbol]['amount'], $transaction['foreign_amount']);
            }
        }

        return $amounts;
    }
}
