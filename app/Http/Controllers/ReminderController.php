<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Models\Reminder;
use FireflyIII\Repositories\Reminder\ReminderRepositoryInterface;
use Redirect;
use Session;
use URL;

/**
 * Class ReminderController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReminderController extends Controller
{


    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function act(Reminder $reminder)
    {
        $data = [
            'description'   => 'Money for piggy bank "' . $reminder->remindersable->name . '"',
            'amount'        => round($reminder->metadata->perReminder, 2),
            'account_to_id' => $reminder->remindersable->account_id,
            'piggy_bank_id' => $reminder->remindersable_id,
            'reminder_id'   => $reminder->id,
        ];
        Session::flash('_old_input', $data);

        return Redirect::route('transactions.create', ['transfer']);
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dismiss(Reminder $reminder)
    {
        $reminder->notnow = true;
        $reminder->save();

        return Redirect::to(URL::previous());


    }

    /**
     * @param ReminderRepositoryInterface $repository
     *
     * @return \Illuminate\View\View
     */
    public function index(ReminderRepositoryInterface $repository)
    {


        $active    = $repository->getActiveReminders();
        $expired   = $repository->getExpiredReminders();
        $inactive  = $repository->getInactiveReminders();
        $dismissed = $repository->getDismissedReminders();

        $title         = 'Reminders';
        $mainTitleIcon = 'fa-clock-o';

        return view('reminders.index', compact('dismissed', 'expired', 'inactive', 'active', 'title', 'mainTitleIcon'));
    }

    /**
     * @param Reminder $reminder
     *
     * @return \Illuminate\View\View
     */
    public function show(Reminder $reminder)
    {
        $title         = 'Reminder';
        $mainTitleIcon = 'fa-clock-o';
        if ($reminder->notnow === true) {
            $subTitle = 'Dismissed reminder';
        } else {
            $subTitle = 'Reminder';
        }
        $subTitle .= ' for piggy bank "' . $reminder->remindersable->name . '"';


        return view('reminders.show', compact('reminder', 'title', 'subTitle', 'mainTitleIcon'));


    }

}
