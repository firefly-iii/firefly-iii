<?php

namespace FireflyIII\Repositories\Reminder;

use App;
use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Reminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class ReminderRepository
 *
 * @package FireflyIII\Repositories\Reminder
 */
class ReminderRepository implements ReminderRepositoryInterface
{
    /** @var \FireflyIII\Helpers\Reminders\ReminderHelperInterface */
    protected $helper;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        /** @var \FireflyIII\Helpers\Reminders\ReminderHelperInterface helper */
        $this->helper = App::make('FireflyIII\Helpers\Reminders\ReminderHelperInterface');
    }

    /**
     * @return Collection
     */
    public function getActiveReminders()
    {
        $today = new Carbon;
        // active reminders:
        $active = Auth::user()->reminders()
                      ->where('notnow', 0)
                      ->where('active', 1)
                      ->where('startdate', '<=', $today->format('Y-m-d 00:00:00'))
                      ->where('enddate', '>=', $today->format('Y-m-d 00:00:00'))
                      ->get();

        $active->each(
            function (Reminder $reminder) {
                $reminder->description = $this->helper->getReminderText($reminder);
            }
        );

        return $active;

    }

    /**
     * @return Collection
     */
    public function getDismissedReminders()
    {
        $dismissed = Auth::user()->reminders()
                         ->where('notnow', 1)
                         ->get();

        $dismissed->each(
            function (Reminder $reminder) {
                $reminder->description = $this->helper->getReminderText($reminder);
            }
        );

        return $dismissed;
    }

    /**
     * @return Collection
     */
    public function getExpiredReminders()
    {

        $expired = Auth::user()->reminders()
                       ->where('notnow', 0)
                       ->where('active', 1)
                       ->where(
                           function (Builder $q) {
                               $today = new Carbon;
                               $q->where('startdate', '>', $today->format('Y-m-d 00:00:00'));
                               $q->orWhere('enddate', '<', $today->format('Y-m-d 00:00:00'));
                           }
                       )->get();

        $expired->each(
            function (Reminder $reminder) {
                $reminder->description = $this->helper->getReminderText($reminder);
            }
        );

        return $expired;
    }

    /**
     * @return Collection
     */
    public function getInactiveReminders()
    {
        $inactive = Auth::user()->reminders()
                        ->where('active', 0)
                        ->get();

        $inactive->each(
            function (Reminder $reminder) {
                $reminder->description = $this->helper->getReminderText($reminder);
            }
        );

        return $inactive;
    }
}
