<?php

namespace Firefly\Storage\Reminder;


use Carbon\Carbon;

/**
 * Class EloquentReminderRepository
 *
 * @package Firefly\Storage\Reminder
 */
class EloquentReminderRepository implements ReminderRepositoryInterface
{
    /**
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }
}