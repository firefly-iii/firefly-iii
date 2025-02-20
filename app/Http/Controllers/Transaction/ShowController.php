<?php

/**
 * ShowController.php
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

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\AuditLogEntry\ALERepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private ALERepositoryInterface              $aleRepository;
    private TransactionGroupRepositoryInterface $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->repository    = app(TransactionGroupRepositoryInterface::class);
                $this->aleRepository = app(ALERepositoryInterface::class);

                app('view')->share('title', (string) trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @return JsonResponse
     */
    public function debugShow(TransactionGroup $transactionGroup)
    {
        return response()->json($this->repository->expandGroup($transactionGroup));
    }

    /**
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function show(TransactionGroup $transactionGroup)
    {
        /** @var null|TransactionJournal $first */
        $first           = $transactionGroup->transactionJournals()->first(['transaction_journals.*']);
        $splits          = $transactionGroup->transactionJournals()->count();

        if (null === $first) {
            throw new FireflyException('This transaction is broken :(.');
        }

        $type            = (string) trans(sprintf('firefly.%s', $first->transactionType->type));
        $title           = 1 === $splits ? $first->description : $transactionGroup->title;
        $subTitle        = sprintf('%s: "%s"', $type, $title);

        /** @var TransactionGroupTransformer $transformer */
        $transformer     = app(TransactionGroupTransformer::class);
        $transformer->setParameters(new ParameterBag());
        $groupArray      = $transformer->transformObject($transactionGroup);

        // do some calculations:
        $amounts         = $this->getAmounts($groupArray);
        $accounts        = $this->getAccounts($groupArray);

        foreach (array_keys($groupArray['transactions']) as $index) {
            $groupArray['transactions'][$index]['tags'] = $this->repository->getTagObjects(
                (int) $groupArray['transactions'][$index]['transaction_journal_id']
            );
        }

        // get audit log entries:
        $groupLogEntries = $this->aleRepository->getForObject($transactionGroup);
        $logEntries      = [];
        foreach ($transactionGroup->transactionJournals as $journal) {
            $logEntries[$journal->id] = $this->aleRepository->getForObject($journal);
        }

        $events          = $this->repository->getPiggyEvents($transactionGroup);
        $attachments     = $this->repository->getAttachments($transactionGroup);
        $links           = $this->repository->getLinks($transactionGroup);

        return view(
            'transactions.show',
            compact(
                'transactionGroup',
                'amounts',
                'first',
                'type',
                'logEntries',
                'groupLogEntries',
                'subTitle',
                'splits',
                'groupArray',
                'events',
                'attachments',
                'links',
                'accounts',
            )
        );
    }

    private function getAmounts(array $group): array
    {
        $amounts = [];
        foreach ($group['transactions'] as $transaction) {
            $symbol                     = $transaction['currency_symbol'];
            if (!array_key_exists($symbol, $amounts)) {
                $amounts[$symbol] = [
                    'amount'         => '0',
                    'symbol'         => $symbol,
                    'decimal_places' => $transaction['currency_decimal_places'],
                ];
            }
            $amounts[$symbol]['amount'] = bcadd($amounts[$symbol]['amount'], $transaction['amount']);
            if (null !== $transaction['foreign_amount'] && '' !== $transaction['foreign_amount']
                && 0 !== bccomp(
                    '0',
                    $transaction['foreign_amount']
                )) {
                // same for foreign currency:
                $foreignSymbol                     = $transaction['foreign_currency_symbol'];
                if (!array_key_exists($foreignSymbol, $amounts)) {
                    $amounts[$foreignSymbol] = [
                        'amount'         => '0',
                        'symbol'         => $foreignSymbol,
                        'decimal_places' => $transaction['foreign_currency_decimal_places'],
                    ];
                }
                $amounts[$foreignSymbol]['amount'] = bcadd(
                    $amounts[$foreignSymbol]['amount'],
                    $transaction['foreign_amount']
                );
            }
        }

        return $amounts;
    }

    private function getAccounts(array $group): array
    {
        $accounts                = [
            'source'      => [],
            'destination' => [],
        ];

        foreach ($group['transactions'] as $transaction) {
            $accounts['source'][]      = [
                'type' => $transaction['source_type'],
                'id'   => $transaction['source_id'],
                'name' => $transaction['source_name'],
                'iban' => $transaction['source_iban'],
            ];
            $accounts['destination'][] = [
                'type' => $transaction['destination_type'],
                'id'   => $transaction['destination_id'],
                'name' => $transaction['destination_name'],
                'iban' => $transaction['destination_iban'],
            ];
        }

        $accounts['source']      = array_unique($accounts['source'], SORT_REGULAR);
        $accounts['destination'] = array_unique($accounts['destination'], SORT_REGULAR);

        return $accounts;
    }
}
