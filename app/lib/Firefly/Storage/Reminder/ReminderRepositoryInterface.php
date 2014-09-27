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
     * @param \Reminder $reminder
     *
     * @return mixed
     */
    public function deactivate(\Reminder $reminder);


    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

} 