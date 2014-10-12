<?php
namespace Firefly\Storage\Reminder;


/**
 * Interface ReminderRepositoryInterface
 *
 * @package Firefly\Storage\Reminder
 */
interface ReminderRepositoryInterface
{

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

} 