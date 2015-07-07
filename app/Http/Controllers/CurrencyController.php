<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Cache;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Input;
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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.currencies'));
        View::share('mainTitleIcon', 'fa-usd');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $subTitleIcon = 'fa-plus';
        $subTitle     = trans('firefly.create_currency');

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('currency.create.fromStore') !== true) {
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

        Session::flash('success', $currency->name . ' is now the default currency.');
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return redirect(route('currency.index'));

    }

    /**
     * @param CurrencyRepositoryInterface $repository
     * @param TransactionCurrency         $currency
     *
     * @return \Illuminate\Http\RedirectResponse|View
     */
    public function delete(CurrencyRepositoryInterface $repository, TransactionCurrency $currency)
    {

        if ($repository->countJournals($currency) > 0) {
            Session::flash('error', 'Cannot delete ' . e($currency->name) . ' because there are still transactions attached to it.');

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
     * @param CurrencyRepositoryInterface $repository
     * @param TransactionCurrency         $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(CurrencyRepositoryInterface $repository, TransactionCurrency $currency)
    {
        if ($repository->countJournals($currency) > 0) {
            Session::flash('error', 'Cannot destroy ' . e($currency->name) . ' because there are still transactions attached to it.');

            return redirect(route('currency.index'));
        }

        Session::flash('success', 'Currency "' . e($currency->name) . '" deleted');
        if (Auth::user()->hasRole('owner')) {
            $currency->delete();
        }

        return redirect(Session::get('currency.delete.url'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\View\View
     */
    public function edit(TransactionCurrency $currency)
    {
        $subTitleIcon     = 'fa-pencil';
        $subTitle         = trans('firefly.edit_currency', ['name' => $currency->name]);
        $currency->symbol = htmlentities($currency->symbol);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('currency.edit.fromUpdate') !== true) {
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
     * @return \Illuminate\View\View
     */
    public function index(CurrencyRepositoryInterface $repository)
    {
        $currencies      = $repository->get();
        $defaultCurrency = $repository->getCurrencyByPreference(Preferences::get('currencyPreference', 'EUR'));


        if (!Auth::user()->hasRole('owner')) {
            Session::flash('warning', 'Please ask ' . env('SITE_OWNER') . ' to add, remove or edit currencies.');
        }


        return view('currency.index', compact('currencies', 'defaultCurrency'));
    }

    /**
     *
     * @param CurrencyFormRequest         $request
     * @param CurrencyRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(CurrencyFormRequest $request, CurrencyRepositoryInterface $repository)
    {
        $data = $request->getCurrencyData();
        if (Auth::user()->hasRole('owner')) {
            $currency = $repository->store($data);
            Session::flash('success', 'Currency "' . $currency->name . '" created');

        }

        if (intval(Input::get('create_another')) === 1) {
            Session::put('currency.create.fromStore', true);

            return redirect(route('currency.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(Session::get('currency.create.url'));


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
        if (Auth::user()->hasRole('owner')) {
            $currency = $repository->update($currency, $data);
        }
        Session::flash('success', 'Currency "' . e($currency->name) . '" updated.');
        Preferences::mark();


        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('currency.edit.fromUpdate', true);

            return redirect(route('currency.edit', [$currency->id]));
        }

        // redirect to previous URL.
        return redirect(Session::get('currency.edit.url'));

    }

}
