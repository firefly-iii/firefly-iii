<?php

namespace FireflyIII\Repositories\Reminder;
use Illuminate\Support\Collection;


/**
 * Interface ReminderRepositoryInterface
 *
 * @package FireflyIII\Repositories\Reminder
 */
interface ReminderRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getActiveReminders();

    /**
     * @return Collection
     */
    public function getDismissedReminders();

    /**
     * @return Collection
     */
    public function getExpiredReminders();

    /**
     * @return Collection
     */
    public function getInactiveReminders();

}
