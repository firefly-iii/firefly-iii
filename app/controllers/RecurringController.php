<?php

use Firefly\Exception\FireflyException;
use Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface as RTR;

/**
 * Class RecurringController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
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

        View::share('title', 'Recurring transactions');
        View::share('mainTitleIcon', 'fa-rotate-right');
    }

    /**
     * @return $this
     */
    public function create()
    {
        View::share('subTitle', 'Create new');
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
        View::share('subTitle', 'Delete "' . $recurringTransaction->name . '"');
        return View::make('recurring.delete')->with('recurringTransaction', $recurringTransaction);
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RecurringTransaction $recurringTransaction)
    {
        //Event::fire('recurring.destroy', [$recurringTransaction]);
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

        View::share('subTitle', 'Edit "' . $recurringTransaction->name . '"');

        return View::make('recurring.edit')->with('periods', $periods)->with(
            'recurringTransaction', $recurringTransaction
        );
    }

    /**
     * @return $this
     */
    public function index()
    {
        return View::make('recurring.index');
    }

    /**
     *
     */
    public function show(RecurringTransaction $recurringTransaction)
    {
        View::share('subTitle', $recurringTransaction->name);
        return View::make('recurring.show')->with('recurring', $recurringTransaction);

    }

    public function store()
    {

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
            case 'store':
            case 'create_another':
                /*
                 * Try to store:
                 */
                $messageBag = $this->_repository->store(Input::all());

                /*
                 * Failure!
                 */
                if ($messageBag->count() > 0) {
                    Session::flash('error', 'Could not save recurring transaction: ' . $messageBag->first());
                    return Redirect::route('recurring.create')->withInput()->withErrors($messageBag);
                }

                /*
                 * Success!
                 */
                Session::flash('success', 'Recurring transaction "' . e(Input::get('name')) . '" saved!');

                /*
                 * Redirect to original location or back to the form.
                 */
                if (Input::get('post_submit_action') == 'create_another') {
                    return Redirect::route('recurring.create')->withInput();
                } else {
                    return Redirect::route('recurring.index');
                }
                break;
        }

//
//
//        if ($recurringTransaction->errors()->count() == 0) {
//            Session::flash('success', 'Recurring transaction "' . $recurringTransaction->name . '" saved!');
//            //Event::fire('recurring.store', [$recurringTransaction]);
//            if (Input::get('create') == '1') {
//                return Redirect::route('recurring.create')->withInput();
//            } else {
//                return Redirect::route('recurring.index');
//            }
//        } else {
//            Session::flash(
//                'error', 'Could not save the recurring transaction: ' . $recurringTransaction->errors()->first()
//            );
//
//            return Redirect::route('recurring.create')->withInput()->withErrors($recurringTransaction->errors());
//        }
    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(RecurringTransaction $recurringTransaction)
    {
        /** @var \RecurringTransaction $recurringTransaction */
        $recurringTransaction = $this->_repository->update($recurringTransaction, Input::all());
        if ($recurringTransaction->errors()->count() == 0) {
            Session::flash('success', 'The recurring transaction has been updated.');
            //Event::fire('recurring.update', [$recurringTransaction]);

            return Redirect::route('recurring.index');
        } else {
            Session::flash(
                'error', 'Could not update the recurring transaction: ' . $recurringTransaction->errors()->first()
            );

            return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput()->withErrors(
                $recurringTransaction->errors()
            );
        }
    }
}