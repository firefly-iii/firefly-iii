<?php
/**
 * CurrencyController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Cache;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use Preferences;
use View;

/**
 * Class CurrencyController
 *
 * @package FireflyIII\Http\Controllers
 */
class CurrencyController extends Controller
{

    /** @var CurrencyRepositoryInterface */
    protected $repository;

    /** @var  UserRepositoryInterface */
    protected $userRepository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.currencies'));
                View::share('mainTitleIcon', 'fa-usd');
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function create(Request $request)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            $request->session()->flash('error', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));

            return redirect(route('currencies.index'));
        }

        $subTitleIcon = 'fa-plus';
        $subTitle     = trans('firefly.create_currency');

        // put previous url in session if not redirect from store (not "create another").
        if (session('currencies.create.fromStore') !== true) {
            $this->rememberPreviousUri('currencies.create.uri');
        }
        $request->session()->forget('currencies.create.fromStore');
        $request->session()->flash('gaEventCategory', 'currency');
        $request->session()->flash('gaEventAction', 'create');

        return view('currencies.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function defaultCurrency(Request $request, TransactionCurrency $currency)
    {

        Preferences::set('currencyPreference', $currency->code);
        Preferences::mark();

        $request->session()->flash('success', trans('firefly.new_default_currency', ['name' => $currency->name]));
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return redirect(route('currencies.index'));

    }


    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(Request $request, TransactionCurrency $currency)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        if (!$this->repository->canDeleteCurrency($currency)) {
            $request->session()->flash('error', trans('firefly.cannot_delete_currency', ['name' => $currency->name]));

            return redirect(route('currencies.index'));
        }


        // put previous url in session
        $this->rememberPreviousUri('currencies.delete.uri');
        $request->session()->flash('gaEventCategory', 'currency');
        $request->session()->flash('gaEventAction', 'delete');
        $subTitle = trans('form.delete_currency', ['name' => $currency->name]);


        return view('currencies.delete', compact('currency', 'subTitle'));
    }

    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, TransactionCurrency $currency)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        if (!$this->repository->canDeleteCurrency($currency)) {
            $request->session()->flash('error', trans('firefly.cannot_delete_currency', ['name' => $currency->name]));

            return redirect(route('currencies.index'));
        }

        $this->repository->destroy($currency);
        $request->session()->flash('success', trans('firefly.deleted_currency', ['name' => $currency->name]));

        return redirect($this->getPreviousUri('currencies.delete.uri'));
    }

    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function edit(Request $request, TransactionCurrency $currency)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        $subTitleIcon     = 'fa-pencil';
        $subTitle         = trans('breadcrumbs.edit_currency', ['name' => $currency->name]);
        $currency->symbol = htmlentities($currency->symbol);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('currencies.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('currencies.edit.uri');
        }
        $request->session()->forget('currencies.edit.fromUpdate');
        $request->session()->flash('gaEventCategory', 'currency');
        $request->session()->flash('gaEventAction', 'edit');

        return view('currencies.edit', compact('currency', 'subTitle', 'subTitleIcon'));

    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function index(Request $request)
    {
        $currencies      = $this->repository->get();
        $defaultCurrency = $this->repository->getCurrencyByPreference(Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR')));
        $isOwner = true;
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            $request->session()->flash('info', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));
            $isOwner = false;
        }

        return view('currencies.index', compact('currencies', 'defaultCurrency','isOwner'));
    }

    /**
     * @param CurrencyFormRequest $request
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CurrencyFormRequest $request)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            // @codeCoverageIgnoreStart
            Log::error('User ' . auth()->user()->id . ' is not admin, but tried to store a currency.');

            return redirect($this->getPreviousUri('currencies.create.uri'));
            // @codeCoverageIgnoreEnd
        }

        $data     = $request->getCurrencyData();
        $currency = $this->repository->store($data);
        $request->session()->flash('success', trans('firefly.created_currency', ['name' => $currency->name]));

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('currencies.create.fromStore', true);

            return redirect(route('currencies.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('currencies.create.uri'));
    }

    /**
     * @param CurrencyFormRequest $request
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CurrencyFormRequest $request, TransactionCurrency $currency)
    {
        if (!$this->userRepository->hasRole(auth()->user(), 'owner')) {
            // @codeCoverageIgnoreStart
            $request->session()->flash('error', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));

            return redirect(route('currencies.index'));
            // @codeCoverageIgnoreEnd
        }

        $data     = $request->getCurrencyData();
        $currency = $this->repository->update($currency, $data);
        $request->session()->flash('success', trans('firefly.updated_currency', ['name' => $currency->name]));
        Preferences::mark();


        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('currencies.edit.fromUpdate', true);

            return redirect(route('currencies.edit', [$currency->id]));
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('currencies.edit.uri'));
    }
}
