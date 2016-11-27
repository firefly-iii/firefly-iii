<?php
/**
 * UserController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyConfig;
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
     *
     * @return int
     */
    public function edit(User $user)
    {
        return $user->id;

    }

    /**
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(UserRepositoryInterface $repository)
    {
        $title              = strval(trans('firefly.administration'));
        $mainTitleIcon      = 'fa-hand-spock-o';
        $subTitle           = strval(trans('firefly.user_administration'));
        $subTitleIcon       = 'fa-users';
        $mustConfirmAccount = FireflyConfig::get('must_confirm_account', config('firefly.configuration.must_confirm_account'))->data;
        $users              = $repository->all();

        // add meta stuff.
        $users->each(
            function (User $user) use ($mustConfirmAccount) {
                $list        = ['user_confirmed', 'twoFactorAuthEnabled', 'twoFactorAuthSecret', 'registration_ip_address', 'confirmation_ip_address'];
                $preferences = Preferences::getArrayForUser($user, $list);

                $user->activated = true;
                if (!($preferences['user_confirmed'] === true) && $mustConfirmAccount === true) {
                    $user->activated = false;
                }

                $user->isAdmin = $user->hasRole('owner');
                $is2faEnabled  = $preferences['twoFactorAuthEnabled'] === true;
                $has2faSecret  = !is_null($preferences['twoFactorAuthSecret']);
                $user->has2FA  = false;
                if ($is2faEnabled && $has2faSecret) {
                    $user->has2FA = true;
                }
                $user->prefs = $preferences;
            }
        );


        return view('admin.users.index', compact('title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'users'));

    }

    /**
     * @param UserRepositoryInterface $repository
     * @param User                    $user
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(UserRepositoryInterface $repository, User $user)
    {
        $title         = strval(trans('firefly.administration'));
        $mainTitleIcon = 'fa-hand-spock-o';
        $subTitle      = strval(trans('firefly.single_user_administration', ['email' => $user->email]));
        $subTitleIcon  = 'fa-user';

        // get IP info:
        $defaultIp    = '0.0.0.0';
        $regPref      = Preferences::getForUser($user, 'registration_ip_address');
        $registration = $defaultIp;
        $conPref      = Preferences::getForUser($user, 'confirmation_ip_address');
        $confirmation = $defaultIp;
        if (!is_null($regPref)) {
            $registration = $regPref->data;
        }
        if (!is_null($conPref)) {
            $confirmation = $conPref->data;
        }

        $registrationHost = '';
        $confirmationHost = '';

        if ($registration != $defaultIp) {
            $registrationHost = gethostbyaddr($registration);
        }
        if ($confirmation != $defaultIp) {
            $confirmationHost = gethostbyaddr($confirmation);
        }

        $information = $repository->getUserData($user);

        return view(
            'admin.users.show',
            compact(
                'title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'information',
                'user', 'registration', 'confirmation', 'registrationHost', 'confirmationHost'
            )
        );
    }


}
