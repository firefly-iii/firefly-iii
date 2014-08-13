<?php

use Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface as RTR;

/**
 * Class RecurringController
 */
class RecurringController extends BaseController
{
    protected $_repository;

    /**
     * @param RTR $repository
     */
    public function __construct(RTR $repository)
    {
        $this->_repository = $repository;
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('recurring.create')->with('periods', $periods);
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this
     */
    public function delete(RecurringTransaction $recurringTransaction)
    {
        return View::make('recurring.delete')->with('recurringTransaction', $recurringTransaction);
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RecurringTransaction $recurringTransaction)
    {
        $result = $this->_repository->destroy($recurringTransaction);
        if ($result === true) {
            Session::flash('success', 'The recurring transaction was deleted.');
        } else {
            Session::flash('error', 'Could not delete the recurring transaction. Check the logs to be sure.');
        }

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

        return View::make('recurring.edit')->with('periods', $periods)->with(
            'recurringTransaction', $recurringTransaction
        );
    }

    /**
     * @return $this
     */
    public function index()
    {
        $list = $this->_repository->get();

        return View::make('recurring.index')->with('list', $list);
    }

    /**
     *
     */
    public function show()
    {
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $recurringTransaction = $this->_repository->store(Input::all());
        if ($recurringTransaction->validate()) {
            Session::flash('success', 'Recurring transaction "' . $recurringTransaction->name . '" saved!');
            if (Input::get('create') == '1') {
                return Redirect::route('recurring.create')->withInput();
            } else {
                return Redirect::route('recurring.index');
            }
        } else {
            Session::flash(
                'error', 'Could not save the recurring transaction: ' . $recurringTransaction->errors()->first()
            );

            return Redirect::route('recurring.create')->withInput()->withErrors($recurringTransaction->errors());
        }
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     */
    public function update(RecurringTransaction $recurringTransaction)
    {
    }
} 