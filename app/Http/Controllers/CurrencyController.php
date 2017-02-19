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
    public function create(Request $request)
    {
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
     * @param CurrencyRepositoryInterface $repository
     * @param TransactionCurrency         $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(Request $request, CurrencyRepositoryInterface $repository, TransactionCurrency $currency)
    {
        if (!$repository->canDeleteCurrency($currency)) {
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
     * @param CurrencyRepositoryInterface $repository
     * @param TransactionCurrency         $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, CurrencyRepositoryInterface $repository, TransactionCurrency $currency)
    {
        if (!$repository->canDeleteCurrency($currency)) {
            $request->session()->flash('error', trans('firefly.cannot_delete_currency', ['name' => $currency->name]));

            return redirect(route('currencies.index'));
        }

        $repository->destroy($currency);
        $request->session()->flash('success', trans('firefly.deleted_currency', ['name' => $currency->name]));

        return redirect($this->getPreviousUri('currencies.delete.uri'));
    }

    /**
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return View
     */
    public function edit(Request $request, TransactionCurrency $currency)
    {
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
     * @param Request                     $request
     * @param CurrencyRepositoryInterface $repository
     *
     * @return View
     */
    public function index(Request $request, CurrencyRepositoryInterface $repository)
    {
        $currencies      = $repository->get();
        $defaultCurrency = $repository->getCurrencyByPreference(Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR')));


        if (!auth()->user()->hasRole('owner')) {
            $request->session()->flash('info', trans('firefly.ask_site_owner', ['owner' => env('SITE_OWNER')]));
        }


        return view('currencies.index', compact('currencies', 'defaultCurrency'));
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

            return redirect($this->getPreviousUri('currencies.create.uri'));
        }

        $data     = $request->getCurrencyData();
        $currency = $repository->store($data);
        $request->session()->flash('success', trans('firefly.created_currency', ['name' => $currency->name]));

        if (intval($request->get('create_another')) === 1) {
            $request->session()->put('currencies.create.fromStore', true);

            return redirect(route('currencies.create'))->withInput();
        }

        return redirect($this->getPreviousUri('currencies.create.uri'));
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
        $request->session()->flash('success', trans('firefly.updated_currency', ['name' => $currency->name]));
        Preferences::mark();


        if (intval($request->get('return_to_edit')) === 1) {
            $request->session()->put('currencies.edit.fromUpdate', true);

            return redirect(route('currencies.edit', [$currency->id]));
        }

        return redirect($this->getPreviousUri('currencies.edit.uri'));
    }
}
