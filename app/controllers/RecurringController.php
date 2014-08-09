<?php

use Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface as RTR;

class RecurringController extends BaseController
{
    protected $_repository;

    public function __construct(RTR $repository)
    {
        $this->_repository = $repository;
        View::share('menu', 'home');
    }

    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('recurring.create')->with('periods', $periods);
    }

    public function delete(RecurringTransaction $recurringTransaction)
    {
        return View::make('recurring.delete')->with('recurringTransaction', $recurringTransaction);
    }

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

    public function edit(RecurringTransaction $recurringTransaction)
    {
        $periods = \Config::get('firefly.periods_to_text');

        return View::make('recurring.edit')->with('periods', $periods)->with(
            'recurringTransaction', $recurringTransaction
        );
    }

    public function index()
    {
        $list = $this->_repository->get();

        return View::make('recurring.index')->with('list', $list);
    }

    public function show()
    {
    }

    public function store()
    {
        $recurringTransaction = $this->_repository->store(Input::all());
        if ($recurringTransaction->id) {
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

    public function update(RecurringTransaction $recurringTransaction)
    {
    }
} 