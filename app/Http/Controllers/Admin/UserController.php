<?php
/**
 * UserController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @param User $user
     */
    public function edit(User $user)
    {


    }

    /**
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(UserRepositoryInterface $repository)
    {
        $title          = strval(trans('firefly.administration'));
        $mainTitleIcon  = 'fa-hand-spock-o';
        $subTitle       = strval(trans('firefly.user_administration'));
        $subTitleIcon   = 'fa-users';
        $confirmAccount = env('MUST_CONFIRM_ACCOUNT', false);
        $users          = $repository->all();

        // add meta stuff.
        $users->each(
            function (User $user) use ($confirmAccount) {
                // is user activated?
                $isConfirmed     = Preferences::getForUser($user, 'user_confirmed', false)->data;
                $user->activated = true;
                if ($isConfirmed === false && $confirmAccount === true) {
                    $user->activated = false;
                }

                $user->isAdmin = $user->hasRole('owner');
                $is2faEnabled  = Preferences::getForUser($user, 'twoFactorAuthEnabled', false)->data;
                $has2faSecret  = !is_null(Preferences::getForUser($user, 'twoFactorAuthSecret'));
                $user->has2FA  = false;
                if ($is2faEnabled && $has2faSecret) {
                    $user->has2FA = true;
                }
            }
        );


        return view('admin.users.index', compact('title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'users'));

    }


}
