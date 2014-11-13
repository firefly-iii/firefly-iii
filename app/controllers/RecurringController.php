<?php
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\MessageBag;

/**
 * Class RecurringController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class RecurringController extends BaseController
{
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

        /** @var \FireflyIII\Database\RecurringTransaction $repository */
        $repository = App::make('FireflyIII\Database\RecurringTransaction');

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
        return View::make('recurring.index');
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
        throw new NotImplementedException;
        Session::flash('success', 'Rescanned everything.');

        return Redirect::back();
    }

    /**
     *
     */
    public function show(RecurringTransaction $recurringTransaction)
    {
        return View::make('recurring.show')->with('recurring', $recurringTransaction)->with('subTitle', $recurringTransaction->name);
    }

    public function store()
    {
        $data            = Input::except('_token');
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

    public function update(RecurringTransaction $recurringTransaction)
    {
        throw new NotImplementedException;
    }
}