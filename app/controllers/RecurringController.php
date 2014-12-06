<?php
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\MessageBag;

/**
 * Class RecurringController
 *
 */
class RecurringController extends BaseController
{
    /**
     *
     */
    public function __construct()
    {

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
        //Event::fire('recurring.destroy', [$recurringTransaction]);

        /** @var \FireflyIII\Database\Recurring $repository */
        $repository = App::make('FireflyIII\Database\Recurring');

        $result = $repository->destroy($recurringTransaction);
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

        return View::make('recurring.edit')->with('periods', $periods)->with('recurringTransaction', $recurringTransaction)->with(
            'subTitle', 'Edit "' . $recurringTransaction->name . '"'
        );
    }

    /**
     * @return $this
     */
    public function index()
    {
        /** @var \FireflyIII\Database\Recurring $repos */
        $repos = App::make('FireflyIII\Database\Recurring');

        $recurring = $repos->get();

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

            return Redirect::back();
        }

        /** @var \FireflyIII\Database\Recurring $repos */
        $repos = App::make('FireflyIII\Database\Recurring');
        $repos->scanEverything($recurringTransaction);

        Session::flash('success', 'Rescanned everything.');

        return Redirect::back();
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
        $data = Input::except('_token');
        /** @var \FireflyIII\Database\Recurring $repos */
        $repos = App::make('FireflyIII\Database\Recurring');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'create_another':
            case 'store':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save recurring transaction: ' . $messages['errors']->first());

                    return Redirect::route('recurring.create')->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->store($data);
                Session::flash('success', 'New recurring transaction stored!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('recurring.create')->withInput();
                } else {
                    return Redirect::route('recurring.index');
                }
                break;
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('recurring.create')->withInput();
                break;
        }

    }

    /**
     * @param RecurringTransaction $recurringTransaction
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(RecurringTransaction $recurringTransaction)
    {
        /** @var \FireflyIII\Database\Recurring $repos */
        $repos = App::make('FireflyIII\Database\Recurring');
        $data  = Input::except('_token');

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e(Input::get('post_submit_action')) . '"');
                break;
            case 'create_another':
            case 'update':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save recurring transaction: ' . $messages['errors']->first());

                    return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->update($recurringTransaction, $data);
                Session::flash('success', 'Recurring transaction updated!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('recurring.edit', $recurringTransaction->id);
                } else {
                    return Redirect::route('recurring.index');
                }
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('recurring.edit', $recurringTransaction->id)->withInput();
                break;
        }

    }
}