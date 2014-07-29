<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29-7-14
 * Time: 10:42
 */

namespace Firefly\Helper\Controllers;


interface ChartInterface
{

    public function account(\Account $account);
    public function accounts();
}