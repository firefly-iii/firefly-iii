<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 23/08/14
 * Time: 20:59
 */

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