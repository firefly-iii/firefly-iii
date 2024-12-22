<?php

/*
 * DeleteController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\TransactionCurrency;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    protected CurrencyRepositoryInterface $repository;
    protected UserRepositoryInterface     $userRepository;

    /**
     * CurrencyController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.currencies'));
                app('view')->share('mainTitleIcon', 'fa-usd');
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Deletes a currency.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function delete(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user     = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string) trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->warning(sprintf('Tried to visit page to delete currency %s but is not site owner.', $currency->code));

            return redirect(route('currencies.index'));
        }

        if ($this->repository->currencyInUse($currency)) {
            $location = $this->repository->currencyInUseAt($currency);
            $message  = (string) trans(sprintf('firefly.cannot_disable_currency_%s', $location), ['name' => e($currency->name)]);
            $request->session()->flash('error', $message);
            Log::channel('audit')->warning(sprintf('Tried to visit page to delete currency %s but currency is in use.', $currency->code));

            return redirect(route('currencies.index'));
        }

        // put previous url in session
        $this->rememberPreviousUrl('currencies.delete.url');
        $subTitle = (string) trans('form.delete_currency', ['name' => $currency->name]);
        Log::channel('audit')->info(sprintf('Visit page to delete currency %s.', $currency->code));

        return view('currencies.delete', compact('currency', 'subTitle'));
    }

    /**
     * Destroys a currency.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string) trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->warning(sprintf('Tried to delete currency %s but is not site owner.', $currency->code));

            return redirect(route('currencies.index'));
        }

        if ($this->repository->currencyInUse($currency)) {
            $request->session()->flash('error', (string) trans('firefly.cannot_delete_currency', ['name' => e($currency->name)]));
            Log::channel('audit')->info(sprintf('Tried to delete currency %s but is in use.', $currency->code));

            return redirect(route('currencies.index'));
        }

        if ($this->repository->isFallbackCurrency($currency)) {
            $request->session()->flash('error', (string) trans('firefly.cannot_delete_fallback_currency', ['name' => e($currency->name)]));
            Log::channel('audit')->info(sprintf('Tried to delete currency %s but is FALLBACK.', $currency->code));

            return redirect(route('currencies.index'));
        }

        Log::channel('audit')->info(sprintf('Deleted currency %s.', $currency->code));
        $this->repository->destroy($currency);

        $request->session()->flash('success', (string) trans('firefly.deleted_currency', ['name' => $currency->name]));

        return redirect($this->getPreviousUrl('currencies.delete.url'));
    }
}
