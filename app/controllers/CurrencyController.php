<?php
use FireflyIII\Database\TransactionCurrency\TransactionCurrency as Repository;

/**
 * Class CurrencyController
 */
class CurrencyController extends BaseController
{

    /** @var Repository */
    protected $_repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->_repository = $repository;


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

        return View::make('currency.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function defaultCurrency(TransactionCurrency $currency)
    {
        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference       = $preferences->get('currencyPreference', 'EUR');
        $currencyPreference->data = $currency->code;
        $currencyPreference->save();
        Cache::forget('FFCURRENCYSYMBOL');
        Cache::forget('FFCURRENCYCODE');

        return Redirect::route('currency.index');

    }

    /**
     * @param TransactionCurrency $currency
     */
    public function delete(TransactionCurrency $currency)
    {
        if ($currency->transactionJournals()->count() > 0) {
            Session::flash('error', 'Cannot delete ' . e($currency->name) . ' because there are still transactions attached to it.');

            return Redirect::route('currency.index');
        }


        return View::make('currency.delete', compact('currency'));
    }

    public function destroy(TransactionCurrency $currency)
    {
        Session::flash('success', 'Currency "' . e($currency->name) . '" deleted');

        $this->_repository->destroy($currency);

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

        return View::make('currency.edit', compact('currency', 'subTitle', 'subTitleIcon'));

    }

    public function index()
    {
        $currencies = $this->_repository->get();

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference = $preferences->get('currencyPreference', 'EUR');
        $defaultCurrency    = $this->_repository->findByCode($currencyPreference->data);


        return View::make('currency.index', compact('currencies', 'defaultCurrency'));
    }

    public function store()
    {
        $data = Input::except('_token');

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store currency: ' . $messages['errors']->first());
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('currency.create')->withInput();
        }

        // store:
        $this->_repository->store($data);
        Session::flash('success', 'Currency "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('currency.index');
        }

        return Redirect::route('currency.create')->withInput();

    }

    public function update(TransactionCurrency $currency)
    {
        $data            = Input::except('_token');

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update currency: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('currency.edit', $currency->id)->withInput();
        }

        // update
        $this->_repository->update($currency, $data);
        Session::flash('success', 'Currency "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('currency.index');
        }

        return Redirect::route('currency.edit', $currency->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }

}