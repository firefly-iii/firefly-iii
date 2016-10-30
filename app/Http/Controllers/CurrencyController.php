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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Cache;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Input;
use Log;
use Preferences;
use Session;
use URL;
use View;

/**
 * Class CurrencyController
 *
 * @package FireflyIII\Http\Controllers
 */
class CurrencyController extends Controller
{


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

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function create()
    {
        $subTitleIcon = 'fa-plus';
        $subTitle     = trans('firefly.create_currency');

        // put previous url in session if not redirect from store (not "create another").
        if (session('currency.create.fromStore') !== true) {
            Session::put('currency.create.url', URL::previous());
        }
        Session::forget('currency.create.fromStore');
        Session::flash('gaEventCategory', 'currency');
        Session::flash('gaEventAction', 'create');

        return view('currency.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function defaultCurrency(TransactionCurrency $currency)
    {

        Preferences::set('currencyPreference', $currency->code);
        Preferences::mark();

        Session::flash('success', trans('firefly.new_default_currency', ['name' => $currency->name]));
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return redirect(route('currency.index'));

    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|View
     */
    public function delete(TransactionCurrency $currency)
    {
        if (!$this->canDeleteCurrency($currency)) {
            Session::flash('error', trans('firefly.cannot_delete_currency', ['name' => $currency->name]));

            return redirect(route('currency.index'));
        }


        // put previous url in session
        Session::put('currency.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'currency');
        Session::flash('gaEventAction', 'delete');
        $subTitle = trans('form.delete_currency', ['name' => $currency->name]);


        return view('currency.delete', compact('currency', 'subTitle'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(TransactionCurrency $currency)
    {
        if (!$this->canDeleteCurrency($currency)) {
            Session::flash('error', trans('firefly.cannot_delete_currency', ['name' => $currency->name]));

            return redirect(route('currency.index'));
        }

        Session::flash('success', trans('firefly.deleted_currency', ['name' => $currency->name]));
        if (auth()->user()->hasRole('owner')) {
            $currency->delete();
        }

        return redirect(session('currency.delete.url'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return View
     */
    public function edit(TransactionCurrency $currency)
    {
        $subTitleIcon     = 'fa-pencil';
        $subTitle         = trans('breadcrumbs.edit_currency', ['name' => $currency->name]);
        $currency->symbol = htmlentities($currency->symbol);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('currency.edit.fromUpdate') !== true) {
            Session::put('currency.edit.url', URL::previous());
        }
        Session::forget('currency.edit.fromUpdate');
        Session::flash('gaEventCategory', 'currency');
        Session::flash('gaEventAction', 'edit');

        return view('currency.edit', compact('currency', 'subTitle', 'subTitleIcon'));

    }

    /**
     * @param CurrencyRepositoryInterface $repository
     *
     * @return View
     */
    public function index(CurrencyRepositoryInterface $repository)
    {
        $currencies      = $repository->get();
        $defaultCurrency = $repository->getCurrencyByPreference(Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR')));


        if (!auth()->user()->hasRole('owner')) {
            Session::flash('warning', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));
        }


        return view('currency.index', compact('currencies', 'defaultCurrency'));
    }

    /**
     *
     * @param CurrencyFormRequest         $request
     * @param CurrencyRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CurrencyFormRequest $request, CurrencyRepositoryInterface $repository)
    {
        if (!auth()->user()->hasRole('owner')) {
            Log::error('User ' . auth()->user()->id . ' is not admin, but tried to store a currency.');

            return redirect(session('currency.create.url'));
        }

        $data     = $request->getCurrencyData();
        $currency = $repository->store($data);
        Session::flash('success', trans('firefly.created_currency', ['name' => $currency->name]));

        if (intval(Input::get('create_another')) === 1) {
            Session::put('currency.create.fromStore', true);

            return redirect(route('currency.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('currency.create.url'));


    }

    /**
     * @param CurrencyFormRequest         $request
     * @param CurrencyRepositoryInterface $repository
     * @param TransactionCurrency         $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CurrencyFormRequest $request, CurrencyRepositoryInterface $repository, TransactionCurrency $currency)
    {
        $data = $request->getCurrencyData();
        if (auth()->user()->hasRole('owner')) {
            $currency = $repository->update($currency, $data);
        }
        Session::flash('success', trans('firefly.updated_currency', ['name' => $currency->name]));
        Preferences::mark();


        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('currency.edit.fromUpdate', true);

            return redirect(route('currency.edit', [$currency->id]));
        }

        // redirect to previous URL.
        return redirect(session('currency.edit.url'));

    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    private function canDeleteCurrency(TransactionCurrency $currency): bool
    {
        $repository = app(CurrencyRepositoryInterface::class);

        // has transactions still
        if ($repository->countJournals($currency) > 0) {
            return false;
        }

        // is the only currency left
        if ($repository->get()->count() === 1) {
            return false;
        }

        // is the default currency for the user or the system
        $defaultCode = Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR'))->data;
        if ($currency->code === $defaultCode) {
            return false;
        }

        // is the default currency for the system
        $defaultSystemCode = config('firefly.default_currency', 'EUR');
        if ($currency->code === $defaultSystemCode) {
            return false;
        }

        // can be deleted
        return true;
    }

}
