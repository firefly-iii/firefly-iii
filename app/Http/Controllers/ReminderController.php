<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Helpers\Reminders\ReminderHelperInterface;
use FireflyIII\Models\Reminder;
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

        return Redirect::route('transactions.create', 'transfer');
    }

    /**
     * @param Reminder $reminder
     */
    public function dismiss(Reminder $reminder)
    {
        $reminder->notnow = true;
        $reminder->save();

        return Redirect::to(URL::previous());


    }

    /**
     *
     */
    public function index(ReminderHelperInterface $helper)
    {

        $reminders = Auth::user()->reminders()->get();

        $reminders->each(
            function (Reminder $reminder) use ($helper) {
                $reminder->description = $helper->getReminderText($reminder);
            }
        );

        $today = new Carbon;
        // active reminders:
        $active = $reminders->filter(
            function (Reminder $reminder) use ($today) {
                if ($reminder->notnow === false && $reminder->active === true && $reminder->startdate <= $today && $reminder->enddate >= $today) {
                    return $reminder;
                }
            }
        );

        // expired reminders:
        $expired = $reminders->filter(
            function (Reminder $reminder) use ($today) {
                if ($reminder->notnow === false && $reminder->active === true && $reminder->startdate > $today || $reminder->enddate < $today) {
                    return $reminder;
                }
            }
        );

        // inactive reminders
        $inactive = $reminders->filter(
            function (Reminder $reminder) use ($today) {
                if ($reminder->active === false) {
                    return $reminder;
                }
            }
        );

        // dismissed reminders
        $dismissed = $reminders->filter(
            function (Reminder $reminder) use ($today) {
                if ($reminder->notnow === true) {
                    return $reminder;
                }
            }
        );

        $title         = 'Reminders';
        $mainTitleIcon = 'fa-clock-o';

        return view('reminders.index', compact('dismissed', 'expired', 'inactive', 'active', 'title', 'mainTitleIcon'));
    }

    /**
     * @param Reminder $reminder
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
