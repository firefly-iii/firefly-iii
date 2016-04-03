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
                // is user activated?
                $isConfirmed = Preferences::getForUser($user, 'user_confirmed', false)->data;
                if ($isConfirmed === false && $confirmAccount === true) {
                    $user->activated = false;
                } else {
                    $user->activated = true;
                }

                // is user admin?
                $user->isAdmin = $user->hasRole('owner');

                // user has 2FA enabled?
                $is2faEnabled = Preferences::getForUser($user, 'twoFactorAuthEnabled', false)->data;
                $has2faSecret = !is_null(Preferences::getForUser($user, 'twoFactorAuthSecret'));
                if ($is2faEnabled && $has2faSecret) {
                    $user->has2FA = true;
                } else {
                    $user->has2FA = false;
                }

            }
        );


        return view('admin.users.index', compact('title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'users'));

    }

}