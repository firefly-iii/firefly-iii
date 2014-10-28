<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 14/10/14
 * Time: 09:45
 */

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