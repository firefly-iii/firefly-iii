<?php
/**
 * CurrencyController.php
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

namespace FireflyIII\Http\Controllers;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use View;

/**
 * Class CurrencyController.
 */
class CurrencyController extends Controller
{
    /** @var CurrencyRepositoryInterface The currency repository */
    protected $repository;

    /** @var UserRepositoryInterface The user repository */
    protected $userRepository;

    /**
     * CurrencyController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.currencies'));
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
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function create(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));

            return redirect(route('currencies.index'));
        }

        $subTitleIcon = 'fa-plus';
        $subTitle     = (string)trans('firefly.create_currency');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('currencies.create.fromStore')) {
            $this->rememberPreviousUri('currencies.create.uri');
        }
        $request->session()->forget('currencies.create.fromStore');

        Log::channel('audit')->info('Create new currency.');

        return view('currencies.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * Make currency the default currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function defaultCurrency(Request $request, TransactionCurrency $currency)
    {
        app('preferences')->set('currencyPreference', $currency->code);
        app('preferences')->mark();

        Log::channel('audit')->info(sprintf('Make %s the default currency.', $currency->code));

        $this->repository->enable($currency);
        $request->session()->flash('success', (string)trans('firefly.new_default_currency', ['name' => $currency->name]));

        return redirect(route('currencies.index'));
    }

    /**
     * Deletes a currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info(sprintf('Tried to visit page to delete currency %s but is not site owner.', $currency->code));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        if ($this->repository->currencyInUse($currency)) {
            $request->session()->flash('error', (string)trans('firefly.cannot_delete_currency', ['name' => e($currency->name)]));
            Log::channel('audit')->info(sprintf('Tried to visit page to delete currency %s but currency is in use.', $currency->code));

            return redirect(route('currencies.index'));
        }

        // put previous url in session
        $this->rememberPreviousUri('currencies.delete.uri');
        $subTitle = (string)trans('form.delete_currency', ['name' => $currency->name]);
        Log::channel('audit')->info(sprintf('Visit page to delete currency %s.', $currency->code));

        return view('currencies.delete', compact('currency', 'subTitle'));
    }

    /**
     * Destroys a currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info(sprintf('Tried to delete currency %s but is not site owner.', $currency->code));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        if ($this->repository->currencyInUse($currency)) {
            $request->session()->flash('error', (string)trans('firefly.cannot_delete_currency', ['name' => e($currency->name)]));
            Log::channel('audit')->info(sprintf('Tried to delete currency %s but is in use.', $currency->code));

            return redirect(route('currencies.index'));
        }
        Log::channel('audit')->info(sprintf('Deleted currency %s.', $currency->code));
        $this->repository->destroy($currency);

        $request->session()->flash('success', (string)trans('firefly.deleted_currency', ['name' => $currency->name]));

        return redirect($this->getPreviousUri('currencies.delete.uri'));
    }

    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function disableCurrency(Request $request, TransactionCurrency $currency)
    {
        app('preferences')->mark();

        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info(sprintf('Tried to disable currency %s but is not site owner.', $currency->code));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        if ($this->repository->currencyInUse($currency)) {
            $request->session()->flash('error', (string)trans('firefly.cannot_disable_currency', ['name' => e($currency->name)]));
            Log::channel('audit')->info(sprintf('Tried to disable currency %s but is in use.', $currency->code));

            return redirect(route('currencies.index'));
        }

        $this->repository->disable($currency);
        Log::channel('audit')->info(sprintf('Disabled currency %s.', $currency->code));
        // if no currencies are enabled, enable the first one in the DB (usually the EUR)
        if (0 === $this->repository->get()->count()) {
            /** @var TransactionCurrency $first */
            $first = $this->repository->getAll()->first();
            if (null === $first) {
                throw new FireflyException('No currencies found.');
            }
            Log::channel('audit')->info(sprintf('Auto-enabled currency %s.', $first->code));
            $this->repository->enable($first);
            app('preferences')->set('currencyPreference', $first->code);
            app('preferences')->mark();
        }

        session()->flash('success', (string)trans('firefly.currency_is_now_disabled', ['name' => $currency->name]));

        return redirect(route('currencies.index'));
    }

    /**
     * Edit a currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function edit(Request $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info(sprintf('Tried to edit currency %s but is not owner.', $currency->code));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        $subTitleIcon     = 'fa-pencil';
        $subTitle         = (string)trans('breadcrumbs.edit_currency', ['name' => $currency->name]);
        $currency->symbol = htmlentities($currency->symbol);

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'enabled' => $hasOldInput ? (bool)$request->old('enabled') : $currency->enabled,
        ];

        $request->session()->flash('preFilled', $preFilled);
        Log::channel('audit')->info('Edit currency.', $currency->toArray());

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('currencies.edit.fromUpdate')) {
            $this->rememberPreviousUri('currencies.edit.uri');
        }
        $request->session()->forget('currencies.edit.fromUpdate');

        return view('currencies.edit', compact('currency', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function enableCurrency(TransactionCurrency $currency)
    {
        app('preferences')->mark();

        $this->repository->enable($currency);
        session()->flash('success', (string)trans('firefly.currency_is_now_enabled', ['name' => $currency->name]));
        Log::channel('audit')->info(sprintf('Enabled currency %s.', $currency->code));

        return redirect(route('currencies.index'));
    }

    /**
     * Show overview of currencies.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user       = auth()->user();
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->repository->getAll();
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);
        $currencies = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $currencies->setPath(route('currencies.index'));

        $defaultCurrency = $this->repository->getCurrencyByPreference(app('preferences')->get('currencyPreference', config('firefly.default_currency', 'EUR')));
        $isOwner         = true;
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('info', (string)trans('firefly.ask_site_owner', ['owner' => config('firefly.site_owner')]));
            $isOwner = false;
        }

        return view('currencies.index', compact('currencies', 'defaultCurrency', 'isOwner'));
    }


    /**
     * Store new currency.
     *
     * @param CurrencyFormRequest $request
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CurrencyFormRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $data = $request->getCurrencyData();
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            Log::error('User ' . auth()->user()->id . ' is not admin, but tried to store a currency.');
            Log::channel('audit')->info('Tried to create (POST) currency without admin rights.', $data);

            return redirect($this->getPreviousUri('currencies.create.uri'));
            // @codeCoverageIgnoreEnd
        }

        $data['enabled'] = true;
        $currency        = $this->repository->store($data);
        $redirect        = redirect($this->getPreviousUri('currencies.create.uri'));

        if (null !== $currency) {
            $request->session()->flash('success', (string)trans('firefly.created_currency', ['name' => $currency->name]));
            Log::channel('audit')->info('Created (POST) currency.', $data);
            if (1 === (int)$request->get('create_another')) {
                // @codeCoverageIgnoreStart
                $request->session()->put('currencies.create.fromStore', true);

                $redirect = redirect(route('currencies.create'))->withInput();
                // @codeCoverageIgnoreEnd
            }
        }
        if (null === $currency) {
            Log::channel('audit')->info('Could not store (POST) currency without admin rights.', $data);
            $request->session()->flash('error', (string)trans('firefly.could_not_store_currency'));
        }

        return $redirect;
    }


    /**
     * Updates a currency.
     *
     * @param CurrencyFormRequest $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CurrencyFormRequest $request, TransactionCurrency $currency)
    {
        /** @var User $user */
        $user = auth()->user();
        $data = $request->getCurrencyData();

        if (false === $data['enabled'] && $this->repository->currencyInUse($currency)) {
            $data['enabled'] = true;
        }
        if (!$this->userRepository->hasRole($user, 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', (string)trans('firefly.ask_site_owner', ['owner' => e(config('firefly.site_owner'))]));
            Log::channel('audit')->info('Tried to update (POST) currency without admin rights.', $data);

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }


        $currency = $this->repository->update($currency, $data);
        Log::channel('audit')->info('Updated (POST) currency.', $data);
        $request->session()->flash('success', (string)trans('firefly.updated_currency', ['name' => $currency->name]));
        app('preferences')->mark();

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('currencies.edit.fromUpdate', true);

            return redirect(route('currencies.edit', [$currency->id]));
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('currencies.edit.uri'));
    }
}
