<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Reminder;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

class ReminderController extends Controller
{


    /**
     *
     */
    public function index(PiggyBankRepositoryInterface $repository)
    {

        $reminders = Auth::user()->reminders()->get();

        $today     = new Carbon;
        // active reminders:
        $active = $reminders->filter(
            function (Reminder $reminder) use ($today, $repository) {
                if ($reminder->notnow === false && $reminder->active === true && $reminder->startdate <= $today && $reminder->enddate >= $today) {
                    $reminder->description = $repository->getReminderText($reminder);
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


    }


}
