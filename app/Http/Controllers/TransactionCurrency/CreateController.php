<?php

/*
 * CreateController.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\CurrencyFormRequest;
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
 * Class CreateController
 */
class CreateController extends Controller
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
     * Create a currency.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function create(Request $request)
    {
        /** @var User $user */
        $user         = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string) trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));

            return redirect(route('currencies.index'));
        }

        $subTitleIcon = 'fa-plus';
        $subTitle     = (string) trans('firefly.create_currency');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('currencies.create.fromStore')) {
            $this->rememberPreviousUrl('currencies.create.url');
        }
        $request->session()->forget('currencies.create.fromStore');

        Log::channel('audit')->info('Create new currency.');

        return view('currencies.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * Store new currency.
     *
     * @return $this|Redirector|RedirectResponse
     */
    public function store(CurrencyFormRequest $request)
    {
        /** @var User $user */
        $user            = auth()->user();
        $data            = $request->getCurrencyData();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            app('log')->error('User '.auth()->user()->id.' is not admin, but tried to store a currency.');
            Log::channel('audit')->warning('Tried to create (POST) currency without admin rights.', $data);

            return redirect($this->getPreviousUrl('currencies.create.url'))->withInput();
        }

        $data['enabled'] = true;

        try {
            $currency = $this->repository->store($data);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            Log::channel('audit')->warning('Could not store (POST) currency without admin rights.', $data);
            $request->session()->flash('error', (string) trans('firefly.could_not_store_currency'));
            $currency = null;
        }
        $redirect        = redirect($this->getPreviousUrl('currencies.create.url'));

        if (null !== $currency) {
            $request->session()->flash('success', (string) trans('firefly.created_currency', ['name' => $currency->name]));
            Log::channel('audit')->info('Created (POST) currency.', $data);
            if (1 === (int) $request->get('create_another')) {
                $request->session()->put('currencies.create.fromStore', true);

                $redirect = redirect(route('currencies.create'))->withInput();
            }
        }

        return $redirect;
    }
}
