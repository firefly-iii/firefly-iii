<?php

/**
 * HomeController.php
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

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Middleware\Installer;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class HomeController.
 */
class HomeController extends Controller
{
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('title', 'Firefly III');
        app('view')->share('mainTitleIcon', 'fa-fire');
        $this->middleware(Installer::class);
    }

    /**
     * Change index date range.
     *
     * @throws \Exception
     */
    public function dateRange(Request $request): JsonResponse
    {
        $stringStart   = '';
        $stringEnd     = '';

        try {
            $stringStart = e((string) $request->get('start'));
            $start       = Carbon::createFromFormat('Y-m-d', $stringStart);
        } catch (InvalidFormatException) {
            app('log')->error(sprintf('Start: could not parse date string "%s" so ignore it.', $stringStart));
            $start = Carbon::now()->startOfMonth();
        }

        try {
            $stringEnd = e((string) $request->get('end'));
            $end       = Carbon::createFromFormat('Y-m-d', $stringEnd);
        } catch (InvalidFormatException) {
            app('log')->error(sprintf('End could not parse date string "%s" so ignore it.', $stringEnd));
            $end = Carbon::now()->endOfMonth();
        }
        if (null === $start) {
            $start = Carbon::now()->startOfMonth();
        }
        if (null === $end) {
            $end = Carbon::now()->endOfMonth();
        }

        $label         = $request->get('label');
        $isCustomRange = false;

        app('log')->debug('dateRange: Received dateRange', ['start' => $stringStart, 'end' => $stringEnd, 'label' => $request->get('label')]);
        // check if the label is "everything" or "Custom range" which will betray
        // a possible problem with the budgets.
        if ($label === (string) trans('firefly.everything') || $label === (string) trans('firefly.customRange')) {
            $isCustomRange = true;
            app('log')->debug('Range is now marked as "custom".');
        }

        $diff          = $start->diffInDays($end, true) + 1;

        if ($diff > 366) {
            $request->session()->flash('warning', (string) trans('firefly.warning_much_data', ['days' => (int) $diff]));
        }

        $request->session()->put('is_custom_range', $isCustomRange);
        app('log')->debug(sprintf('Set is_custom_range to %s', var_export($isCustomRange, true)));
        $request->session()->put('start', $start);
        app('log')->debug(sprintf('Set start to %s', $start->format('Y-m-d H:i:s')));
        $request->session()->put('end', $end);
        app('log')->debug(sprintf('Set end to %s', $end->format('Y-m-d H:i:s')));

        return response()->json(['ok' => 'ok']);
    }

    /**
     * Show index.
     *
     * @throws FireflyException
     */
    public function index(AccountRepositoryInterface $repository): mixed
    {
        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $repository->count($types);
        Log::channel('audit')->info('User visits homepage.');

        if (0 === $count) {
            return redirect(route('new-user.index'));
        }

        if ('v1' === (string) config('view.layout')) {
            return $this->indexV1($repository);
        }
        if ('v2' === (string) config('view.layout')) {
            return $this->indexV2();
        }

        throw new FireflyException('Invalid layout configuration');
    }

    private function indexV1(AccountRepositoryInterface $repository): mixed
    {
        $types          = config('firefly.accountTypesByIdentifier.asset');
        $pageTitle      = (string) trans('firefly.main_dashboard_page_title');
        $count          = $repository->count($types);
        $subTitle       = (string) trans('firefly.welcome_back');
        $transactions   = [];
        $frontpage      = app('preferences')->getFresh('frontpageAccounts', $repository->getAccountsByType([AccountTypeEnum::ASSET->value])->pluck('id')->toArray());
        $frontpageArray = $frontpage->data;
        if (!is_array($frontpageArray)) {
            $frontpageArray = [];
        }

        /** @var Carbon $start */
        $start          = session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end            = session('end', today(config('app.timezone'))->endOfMonth());
        $accounts       = $repository->getAccountsById($frontpageArray);
        $today          = today(config('app.timezone'));
        $accounts       = $accounts->sortBy('order'); // sort frontpage accounts by order

        app('log')->debug('Frontpage accounts are ', $frontpageArray);

        /** @var BillRepositoryInterface $billRepository */
        $billRepository = app(BillRepositoryInterface::class);
        $billCount      = $billRepository->getBills()->count();
        // collect groups for each transaction.
        foreach ($accounts as $account) {
            /** @var GroupCollectorInterface $collector */
            $collector      = app(GroupCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->withAccountInformation()->setRange($start, $end)->setLimit(10)->setPage(1);
            $set            = $collector->getExtractedJournals();
            $transactions[] = ['transactions' => $set, 'account' => $account];
        }

        /** @var User $user */
        $user           = auth()->user();
        event(new RequestedVersionCheckStatus($user));

        return view('index', compact('count', 'subTitle', 'transactions', 'billCount', 'start', 'end', 'today', 'pageTitle'));
    }

    private function indexV2(): mixed
    {
        $subTitle  = (string) trans('firefly.welcome_back');
        $pageTitle = (string) trans('firefly.main_dashboard_page_title');

        $start     = session('start', today(config('app.timezone'))->startOfMonth());
        $end       = session('end', today(config('app.timezone'))->endOfMonth());

        /** @var User $user */
        $user      = auth()->user();
        event(new RequestedVersionCheckStatus($user));

        return view('index', compact('subTitle', 'start', 'end', 'pageTitle'));
    }
}
