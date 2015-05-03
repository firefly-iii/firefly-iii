<?php namespace FireflyIII\Http\Controllers;

use Cache;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Input;
use Preferences;
use Redirect;
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
        View::share('title', 'Currencies');
        View::share('mainTitleIcon', 'fa-usd');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $subTitleIcon = 'fa-plus';
        $subTitle     = 'Create a new currency';

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('currency.create.fromStore') !== true) {
            Session::put('currency.create.url', URL::previous());
        }
        Session::forget('currency.create.fromStore');

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

        Session::flash('success', $currency->name . ' is now the default currency.');
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return Redirect::route('currency.index');

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

            return Redirect::route('currency.index');
        }

        // put previous url in session
        Session::put('currency.delete.url', URL::previous());


        return view('currency.delete', compact('currency'));
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

            return Redirect::route('currency.index');
        }

        Session::flash('success', 'Currency "' . e($currency->name) . '" deleted');

        $currency->delete();

        return Redirect::to(Session::get('currency.delete.url'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\View\View
     */
    public function edit(TransactionCurrency $currency)
    {
        $subTitleIcon     = 'fa-pencil';
        $subTitle         = 'Edit currency "' . e($currency->name) . '"';
        $currency->symbol = htmlentities($currency->symbol);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('currency.edit.fromUpdate') !== true) {
            Session::put('currency.edit.url', URL::previous());
        }
        Session::forget('currency.edit.fromUpdate');

        return view('currency.edit', compact('currency', 'subTitle', 'subTitleIcon'));

    }

    /**
     * @return \Illuminate\View\View
     */
    public function index(CurrencyRepositoryInterface $repository)
    {
        $currencies      = $repository->get();
        $defaultCurrency = $repository->getCurrencyByPreference(Preferences::get('currencyPreference', 'EUR'));

        return view('currency.index', compact('currencies', 'defaultCurrency'));
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(CurrencyFormRequest $request, CurrencyRepositoryInterface $repository)
    {
        $data     = $request->getCurrencyData();
        $currency = $repository->store($data);


        Session::flash('success', 'Currency "' . $currency->name . '" created');

        if (intval(Input::get('create_another')) === 1) {
            Session::put('currency.create.fromStore', true);

            return Redirect::route('currency.create')->withInput();
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('currency.create.url'));


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
        $data     = $request->getCurrencyData();
        $currency = $repository->update($currency, $data);

        Session::flash('success', 'Currency "' . e($currency->name) . '" updated.');


        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('currency.edit.fromUpdate', true);

            return Redirect::route('currency.edit', $currency->id);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('currency.edit.url'));

    }

}
