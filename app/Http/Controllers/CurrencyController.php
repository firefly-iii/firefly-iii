<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\CurrencyFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use Preferences;
use Redirect;
use Session;
use View;
use Cache;
use Input;


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

        return view('currency.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function defaultCurrency(TransactionCurrency $currency)
    {

        $currencyPreference       = Preferences::get('currencyPreference', 'EUR');
        $currencyPreference->data = $currency->code;
        $currencyPreference->save();

        Session::flash('success', $currency->name . ' is now the default currency.');
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return Redirect::route('currency.index');

    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function delete(TransactionCurrency $currency)
    {
        if ($currency->transactionJournals()->count() > 0) {
            Session::flash('error', 'Cannot delete ' . e($currency->name) . ' because there are still transactions attached to it.');

            return Redirect::route('currency.index');
        }


        return view('currency.delete', compact('currency'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TransactionCurrency $currency)
    {
        if ($currency->transactionJournals()->count() > 0) {
            Session::flash('error', 'Cannot delete ' . e($currency->name) . ' because there are still transactions attached to it.');

            return Redirect::route('currency.index');
        }

        Session::flash('success', 'Currency "' . e($currency->name) . '" deleted');

        $currency->delete();

        return Redirect::route('currency.index');
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

        return view('currency.edit', compact('currency', 'subTitle', 'subTitleIcon'));

    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $currencies         = TransactionCurrency::get();
        $currencyPreference = Preferences::get('currencyPreference', 'EUR');
        $defaultCurrency    = TransactionCurrency::whereCode($currencyPreference->data)->first();


        return view('currency.index', compact('currencies', 'defaultCurrency'));
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(CurrencyFormRequest $request)
    {


        // no repository, because the currency controller is relatively simple.
        $currency = TransactionCurrency::create(
            [
                'name'   => $request->get('name'),
                'code'   => $request->get('code'),
                'symbol' => $request->get('symbol'),
            ]
        );

        Session::flash('success', 'Currency "' . $currency->name . '" created');

        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('currency.create');
        }

        return Redirect::route('currency.index');


    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(TransactionCurrency $currency, CurrencyFormRequest $request)
    {

        $currency->code   = $request->get('code');
        $currency->symbol = $request->get('symbol');
        $currency->name   = $request->get('name');
        $currency->save();

        Session::flash('success', 'Currency "' . e($currency->name) . '" updated.');


        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('currency.edit', $currency->id);
        }

        return Redirect::route('currency.index');

    }

}
