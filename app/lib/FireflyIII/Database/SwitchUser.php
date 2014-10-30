<?php

namespace FireflyIII\Database;


/**
 * Class SwitchUser
 *
 * @package FireflyIII\Database
 */
trait SwitchUser
{
    protected $_user;

    public function getUser()
    {
        return $this->_user;
    }

    /**
     * @param $user
     */
    public function setUser($user)
    {
        $this->_user = $user;
    }
} 