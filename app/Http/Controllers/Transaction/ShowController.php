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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\AuditLogEntry\ALERepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

                app('view')->share('title', (string)trans('firefly.transactions'));
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
        /** @var User $admin */
        $admin           = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector       = app(GroupCollectorInterface::class);
        $collector->setUser($admin)->setTransactionGroup($transactionGroup)->withAPIInformation();

        $selectedGroup   = $collector->getGroups()->first();
        if (null === $selectedGroup) {
            throw new NotFoundHttpException();
        }

        // enrich
        $enrichment      = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $selectedGroup   = $enrichment->enrichSingle($selectedGroup);


        /** @var null|TransactionJournal $first */
        $first           = $transactionGroup->transactionJournals()->first(['transaction_journals.*']);
        $splits          = $transactionGroup->transactionJournals()->count();
        $splits          = count($selectedGroup['transactions']);
        $keys            = array_keys($selectedGroup['transactions']);
        $first           = $selectedGroup['transactions'][array_shift($keys)];
        unset($keys);

        if (null === $first) {
            throw new FireflyException('This transaction is broken :(.');
        }
        $type            = (string)trans(sprintf('firefly.%s', $first['transaction_type_type']));
        $title           = 1 === $splits ? $first['description'] : $selectedGroup['title'];
        $subTitle        = sprintf('%s: "%s"', $type, $title);

        // enrich
        $enrichment      = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $selectedGroup   = $enrichment->enrichSingle($selectedGroup);

        /** @var TransactionGroupTransformer $transformer */
        $transformer     = app(TransactionGroupTransformer::class);
        $transformer->setParameters(new ParameterBag());
        $groupArray      = $transformer->transformObject($transactionGroup);

        // do some calculations:
        $amounts         = $this->getAmounts($selectedGroup);
        $accounts        = $this->getAccounts($selectedGroup);

        foreach (array_keys($selectedGroup['transactions']) as $index) {
            $selectedGroup['transactions'][$index]['tags'] = $this->repository->getTagObjects((int)$selectedGroup['transactions'][$index]['transaction_journal_id']);
        }
        // get audit log entries:
        $groupLogEntries = $this->aleRepository->getForObject($transactionGroup);
        $logEntries      = [];
        foreach ($selectedGroup['transactions'] as $journal) {
            $logEntries[$journal['transaction_journal_id']] = $this->aleRepository->getForId(TransactionJournal::class, $journal['transaction_journal_id']);
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
                'selectedGroup',
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
            // add normal amount:
            $symbol                     = $transaction['currency_symbol'];
            $amounts[$symbol] ??= [
                'amount'         => '0',
                'symbol'         => $symbol,
                'decimal_places' => $transaction['currency_decimal_places'],
            ];
            $amounts[$symbol]['amount'] = bcadd($amounts[$symbol]['amount'], (string)$transaction['amount']);

            // add foreign amount:
            if (null !== $transaction['foreign_amount'] && '' !== $transaction['foreign_amount'] && 0 !== bccomp('0', (string)$transaction['foreign_amount'])) {
                // same for foreign currency:
                $foreignSymbol                     = $transaction['foreign_currency_symbol'];
                $amounts[$foreignSymbol] ??= [
                    'amount'         => '0',
                    'symbol'         => $foreignSymbol,
                    'decimal_places' => $transaction['foreign_currency_decimal_places'],
                ];
                $amounts[$foreignSymbol]['amount'] = bcadd($amounts[$foreignSymbol]['amount'], (string)$transaction['foreign_amount']);
            }
            // add primary currency amount
            if (null !== $transaction['pc_amount'] && $transaction['currency_id'] !== $this->primaryCurrency->id) {
                // same for foreign currency:
                $primarySymbol                     = $this->primaryCurrency->symbol;
                $amounts[$primarySymbol] ??= [
                    'amount'         => '0',
                    'symbol'         => $this->primaryCurrency->symbol,
                    'decimal_places' => $this->primaryCurrency->decimal_places,
                ];
                $amounts[$primarySymbol]['amount'] = bcadd($amounts[$primarySymbol]['amount'], (string)$transaction['pc_amount']);
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
                'type' => $transaction['source_account_type'],
                'id'   => $transaction['source_account_id'],
                'name' => $transaction['source_account_name'],
                'iban' => $transaction['source_account_iban'],
            ];
            $accounts['destination'][] = [
                'type' => $transaction['destination_account_type'],
                'id'   => $transaction['destination_account_id'],
                'name' => $transaction['destination_account_name'],
                'iban' => $transaction['destination_account_iban'],
            ];
        }

        $accounts['source']      = array_unique($accounts['source'], SORT_REGULAR);
        $accounts['destination'] = array_unique($accounts['destination'], SORT_REGULAR);

        return $accounts;
    }
}
