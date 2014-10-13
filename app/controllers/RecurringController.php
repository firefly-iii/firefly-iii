<?php

use Firefly\Exception\FireflyException;
use Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface as RTR;
use Firefly\Helper\Controllers\RecurringInterface as RI;

/**
 * Class RecurringController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class RecurringController extends BaseController
{
    protected $_repository;
    protected $_helper;

    /**
     * @param RTR $repository
     * @param RI $helper
     */
    public function __construct(RTR $repository, RI $helper)
    {
        $this->_repository = $repository;
        $this->_helper     = $helper;

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

    /**
     * @param RecurringTransaction $recurringTransaction
     * @return mixed
     */
    public function rescan(RecurringTransaction $recurringTransaction)
    {
        if (intval($recurringTransaction->active) == 0) {
            Session::flash('warning', 'Inactive recurring transactions cannot be scanned.');
            return Redirect::back();
        }
        // do something!
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $repo */
        $repo = App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $set  = $repo->get();

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            Event::fire('recurring.rescan', [$recurringTransaction, $journal]);
        }
        Session::flash('success', 'Rescanned everything.');
        return Redirect::back();


    }

    public function store()
    {
        $data = Input::except(['_token', 'post_submit_action']);
        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
            case 'store':
            case 'create_another':
                /*
                 * Try to store:
                 */
                $messageBag = $this->_repository->store($data);

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
            case 'validate_only':
                $messageBags = $this->_helper->validate($data);

                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('recurring.create')->withInput();
                break;
        }

    }

    public function update(RecurringTransaction $recurringTransaction)
    {
        $data = Input::except(['_token', 'post_submit_action']);
        switch (Input::get('post_submit_action')) {
            case 'update':
            case 'return_to_edit':
                $messageBag = $this->_repository->update($recurringTransaction, $data);
                if ($messageBag->count() == 0) {
                    // has been saved, return to index:
                    Session::flash('success', 'Recurring transaction updated!');

                    if (Input::get('post_submit_action') == 'return_to_edit') {
                        return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput();
                    } else {
                        return Redirect::route('recurring.index');
                    }
                } else {
                    Session::flash('error', 'Could not update recurring transaction: ' . $messageBag->first());

                    return Redirect::route('transactions.edit', $recurringTransaction->id)->withInput()
                                   ->withErrors($messageBag);
                }


                break;
            case 'validate_only':
                $data        = Input::all();
                $data['id']  = $recurringTransaction->id;
                $messageBags = $this->_helper->validate($data);

                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput();

                break;
            // update
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
        }


//        /** @var \RecurringTransaction $recurringTransaction */
//        $recurringTransaction = $this->_repository->update($recurringTransaction, Input::all());
//        if ($recurringTransaction->errors()->count() == 0) {
//            Session::flash('success', 'The recurring transaction has been updated.');
//            //Event::fire('recurring.update', [$recurringTransaction]);
//
//            return Redirect::route('recurring.index');
//        } else {
//            Session::flash(
//                'error', 'Could not update the recurring transaction: ' . $recurringTransaction->errors()->first()
//            );
//
//            return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput()->withErrors(
//                $recurringTransaction->errors()
//            );
//        }
    }
}