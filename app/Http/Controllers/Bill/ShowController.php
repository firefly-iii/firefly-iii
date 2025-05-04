<?php

/**
 * ShowController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Bill;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('showBudget', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->repository = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Rescan bills for transactions.
     *
     * @return Redirector|RedirectResponse
     */
    public function rescan(Request $request, Bill $bill)
    {
        $total      = 0;
        if (false === $bill->active) {
            $request->session()->flash('warning', (string) trans('firefly.cannot_scan_inactive_bill'));

            return redirect(route('bills.show', [$bill->id]));
        }
        $set        = $this->repository->getRulesForBill($bill);
        if (0 === $set->count()) {
            $request->session()->flash('error', (string) trans('firefly.no_rules_for_bill'));

            return redirect(route('bills.show', [$bill->id]));
        }

        // unlink all journals:
        $this->repository->unlinkAll($bill);

        // fire the rules:
        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine = app(RuleEngineInterface::class);
        $ruleEngine->setRules($set);

        // file the rule(s)
        $ruleEngine->fire();

        $request->session()->flash('success', trans_choice('firefly.rescanned_bill', $total));
        app('preferences')->mark();

        return redirect(route('bills.show', [$bill->id]));
    }

    /**
     * Show a bill.
     *
     * @return Factory|View
     */
    public function show(Request $request, Bill $bill)
    {
        // add info about rules:
        $rules                      = $this->repository->getRulesForBill($bill);
        $subTitle                   = $bill->name;

        /** @var Carbon $start */
        $start                      = session('start');

        /** @var Carbon $end */
        $end                        = session('end');
        $year                       = $start->year;
        $page                       = (int) $request->get('page');
        $pageSize                   = (int) app('preferences')->get('listPageSize', 50)->data;
        $yearAverage                = $this->repository->getYearAverage($bill, $start);
        $overallAverage             = $this->repository->getOverallAverage($bill);
        $manager                    = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        $manager->parseIncludes(['attachments', 'notes']);

        // add another period to end, could fix 8163
        $range                      = app('navigation')->getViewRange(true);
        $end                        = app('navigation')->addPeriod($end, $range);

        // Make a resource out of the data and
        $parameters                 = new ParameterBag();
        $parameters->set('start', $start);
        $parameters->set('end', $end);

        /** @var BillTransformer $transformer */
        $transformer                = app(BillTransformer::class);
        $transformer->setParameters($parameters);

        $resource                   = new Item($bill, $transformer, 'bill');
        $object                     = $manager->createData($resource)->toArray();
        $object['data']['currency'] = $bill->transactionCurrency;

        /** @var GroupCollectorInterface $collector */
        $collector                  = app(GroupCollectorInterface::class);
        $collector->setBill($bill)->setLimit($pageSize)->setPage($page)->withBudgetInformation()
            ->withCategoryInformation()->withAccountInformation()
        ;
        $groups                     = $collector->getPaginatedGroups();
        $groups->setPath(route('bills.show', [$bill->id]));

        // transform any attachments as well.
        $collection                 = $this->repository->getAttachments($bill);
        $attachments                = new Collection();

        if ($collection->count() > 0) {
            /** @var AttachmentTransformer $transformer */
            $transformer = app(AttachmentTransformer::class);
            $attachments = $collection->each(
                static fn (Attachment $attachment) => $transformer->transform($attachment)
            );
        }

        return view('bills.show', compact('attachments', 'groups', 'rules', 'yearAverage', 'overallAverage', 'year', 'object', 'bill', 'subTitle'));
    }
}
