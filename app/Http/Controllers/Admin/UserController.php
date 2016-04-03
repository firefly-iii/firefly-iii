<?php
/**
 * UserController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Log;
use Preferences;

/**
 * Class UserController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     * @param UserRepositoryInterface $repository
     */
    public function index(UserRepositoryInterface $repository)
    {
        $title          = strval(trans('firefly.administration'));
        $mainTitleIcon  = 'fa-hand-spock-o';
        $subTitle       = strval(trans('firefly.user_administration'));
        $subTitleIcon   = 'fa-users';
        $confirmAccount = env('MUST_CONFIRM_ACCOUNT', false);

        // list all users:
        $users = $repository->all();

        // add meta stuff.
        $users->each(
            function (User $user) use ($confirmAccount) {
                // user must be logged in, then continue:
                $isConfirmed = Preferences::getForUser($user, 'user_confirmed', false)->data;
                if ($isConfirmed === false && $confirmAccount === true) {
                    $user->activated = false;
                } else {
                    $user->activated = true;
                }


            }
        );


        return view('admin.users.index', compact('title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'users'));

    }

}