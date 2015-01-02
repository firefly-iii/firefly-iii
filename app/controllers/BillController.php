<?php
use FireflyIII\Database\Bill\Bill as Repository;
use FireflyIII\Exception\FireflyException;

/**
 *
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 * @SuppressWarnings("NPathComplexity")
 * Class BillController
 *
 */
class BillController extends BaseController
{

    /** @var  Repository */
    protected $_repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->_repository = $repository;

        View::share('title', 'Bills');
        View::share('mainTitleIcon', 'fa-calendar-o');
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('bills.create')->with('periods', $periods)->with('subTitle', 'Create new');
    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function delete(Bill $bill)
    {
        return View::make('bills.delete')->with('bill', $bill)->with(
            'subTitle', 'Delete "' . e($bill->name) . '"'
        );
    }

    /**
     * @param Bill $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Bill $bill)
    {
        $this->_repository->destroy($bill);
        Session::flash('success', 'The bill was deleted.');

        return Redirect::route('bills.index');

    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function edit(Bill $bill)
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('bills.edit')->with('periods', $periods)->with('bill', $bill)->with(
            'subTitle', 'Edit "' . e($bill->name) . '"'
        );
    }

    /**
     * @return $this
     */
    public function index()
    {
        $bills = $this->_repository->get();
        $bills->each(
            function (Bill $bill) {
                $bill->nextExpectedMatch = $this->_repository->nextExpectedMatch($bill);
                $bill->lastFoundMatch    = $this->_repository->lastFoundMatch($bill);
            }
        );

        return View::make('bills.index', compact('bills'));
    }

    /**
     * @param Bill $bill
     *
     * @return mixed
     */
    public function rescan(Bill $bill)
    {
        if (intval($bill->active) == 0) {
            Session::flash('warning', 'Inactive bills cannot be scanned.');

            return Redirect::intended('/');
        }

        $this->_repository->scanEverything($bill);

        Session::flash('success', 'Rescanned everything.');

        return Redirect::intended('/');
    }

    /**
     * @param Bill $bill
     *
     * @return mixed
     */
    public function show(Bill $bill)
    {
        $journals = $bill->transactionjournals()->withRelevantData()->orderBy('date', 'DESC')->get();
        $bill->nextExpectedMatch = $this->_repository->nextExpectedMatch($bill);
        $hideBill = true;


        return View::make('bills.show', compact('journals', 'hideBill', 'bill'))->with(
            'subTitle', e($bill->name)
        );
    }

    /**
     * @return $this
     * @throws FireflyException
     */
    public function store()
    {
        $data            = Input::all();
        $data['user_id'] = Auth::user()->id;


        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store bill: ' . $messages['errors']->first());
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('bills.create')->withInput();
        }

        // store
        $this->_repository->store($data);
        Session::flash('success', 'Bill "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('bills.index');
        }

        return Redirect::route('bills.create')->withInput();

    }

    /**
     * @param Bill $bill
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(Bill $bill)
    {
        $data              = Input::except('_token');
        $data['active']    = isset($data['active']) ? 1 : 0;
        $data['automatch'] = isset($data['automatch']) ? 1 : 0;
        $data['user_id']   = Auth::user()->id;

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update bill: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('bills.edit', $bill->id)->withInput();
        }

        // update
        $this->_repository->update($bill, $data);
        Session::flash('success', 'Bill "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('bills.index');
        }

        // go back to update screen.
        return Redirect::route('bills.edit', $bill->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }
}
