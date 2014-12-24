<?php
use FireflyIII\Database\RecurringTransaction\RecurringTransaction as Repository;
use FireflyIII\Exception\FireflyException;

/**
 * Class RecurringController
 *
 */
class RecurringController extends BaseController
{

    /** @var  Repository */
    protected $_repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->_repository = $repository;

        View::share('title', 'Recurring transactions');
        View::share('mainTitleIcon', 'fa-rotate-right');
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('recurring.create')->with('periods', $periods)->with('subTitle', 'Create new');
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this
     */
    public function delete(RecurringTransaction $recurringTransaction)
    {
        return View::make('recurring.delete')->with('recurringTransaction', $recurringTransaction)->with(
            'subTitle', 'Delete "' . $recurringTransaction->name . '"'
        );
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RecurringTransaction $recurringTransaction)
    {
        $this->_repository->destroy($recurringTransaction);
        Session::flash('success', 'The recurring transaction was deleted.');

        return Redirect::route('recurring.index');

    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this
     */
    public function edit(RecurringTransaction $recurringTransaction)
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('recurring.edit')->with('periods', $periods)->with('recurringTransaction', $recurringTransaction)->with(
            'subTitle', 'Edit "' . $recurringTransaction->name . '"'
        );
    }

    /**
     * @return $this
     */
    public function index()
    {
        $recurring = $this->_repository->get();

        return View::make('recurring.index', compact('recurring'));
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return mixed
     */
    public function rescan(RecurringTransaction $recurringTransaction)
    {
        if (intval($recurringTransaction->active) == 0) {
            Session::flash('warning', 'Inactive recurring transactions cannot be scanned.');

            return Redirect::intended('/');
        }

        $this->_repository->scanEverything($recurringTransaction);

        Session::flash('success', 'Rescanned everything.');

        return Redirect::intended('/');
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return mixed
     */
    public function show(RecurringTransaction $recurringTransaction)
    {
        $journals      = $recurringTransaction->transactionjournals()->withRelevantData()->orderBy('date', 'DESC')->get();
        $hideRecurring = true;


        return View::make('recurring.show', compact('journals', 'hideRecurring', 'finalDate'))->with('recurring', $recurringTransaction)->with(
            'subTitle', $recurringTransaction->name
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
            Session::flash('error', 'Could not store recurring transaction: ' . $messages['errors']->first());
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('recurring.create')->withInput();
        }

        // store:
        $this->_repository->store($data);
        Session::flash('success', 'Recurring transaction "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('recurring.index');
        }

        return Redirect::route('recurring.create')->withInput();

    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(RecurringTransaction $recurringTransaction)
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
            Session::flash('error', 'Could not update recurring transaction: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput();
        }

        // update
        $this->_repository->update($recurringTransaction, $data);
        Session::flash('success', 'Recurring transaction "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('recurring.index');
        }

        // go back to update screen.
        return Redirect::route('piggy_banks.edit', $recurringTransaction->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }
}