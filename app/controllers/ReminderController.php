<?php
use FireflyIII\Exception\FireflyException;

/**
 * Class ReminderController
 *
 */
class ReminderController extends BaseController
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Reminders');
        View::share('mainTitleIcon', 'fa-lightbulb-o');
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws FireflyException
     */
    public function act(Reminder $reminder)
    {

        $class = get_class($reminder->remindersable);

        if ($class == 'PiggyBank') {
            $amount    = Reminders::amountForReminder($reminder);
            $preFilled = [
                'amount'        => round($amount, 2),
                'description'   => 'Money for ' . $reminder->remindersable->name,
                'piggy_bank_id' => $reminder->remindersable_id,
                'account_to_id' => $reminder->remindersable->account_id
            ];
            Session::flash('preFilled', $preFilled);

            return Redirect::route('transactions.create', 'transfer');
        }

        return View::make('error')->with('message', 'This reminder has an invalid class connected to it.');
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dismiss(Reminder $reminder)
    {
        $reminder->active = 0;
        $reminder->save();
        Session::flash('success', 'Reminder dismissed');

        return Redirect::route('index');
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notNow(Reminder $reminder)
    {
        $reminder->active = 0;
        $reminder->notnow = 1;
        $reminder->save();
        Session::flash('success', 'Reminder dismissed');

        return Redirect::route('index');
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\View\View
     */
    public function show(Reminder $reminder)
    {

        $amount = null;
        if (get_class($reminder->remindersable) == 'PiggyBank') {

            $amount = Reminders::amountForReminder($reminder);
        }

        return View::make('reminders.show', compact('reminder', 'amount'));
    }

}