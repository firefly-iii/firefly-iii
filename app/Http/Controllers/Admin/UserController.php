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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\UserFormRequest;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Log;
use Preferences;
use Session;
use View;

/**
 * Class UserController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', strval(trans('firefly.administration')));
                View::share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
    }

    /**
     * @param User $user
     *
     * @return View
     */
    public function edit(User $user)
    {
        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('users.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('users.edit.uri');
        }
        Session::forget('users.edit.fromUpdate');

        $subTitle     = strval(trans('firefly.edit_user', ['email' => $user->email]));
        $subTitleIcon = 'fa-user-o';
        $codes        = [
            ''        => strval(trans('firefly.no_block_code')),
            'bounced' => strval(trans('firefly.block_code_bounced')),
            'expired' => strval(trans('firefly.block_code_expired')),
        ];

        return view('admin.users.edit', compact('user', 'subTitle', 'subTitleIcon', 'codes'));

    }

    /**
     * @param UserRepositoryInterface $repository
     *
     * @return View
     */
    public function index(UserRepositoryInterface $repository)
    {
        $subTitle     = strval(trans('firefly.user_administration'));
        $subTitleIcon = 'fa-users';
        $users        = $repository->all();

        // add meta stuff.
        $users->each(
            function (User $user) {
                $list          = ['twoFactorAuthEnabled', 'twoFactorAuthSecret'];
                $preferences   = Preferences::getArrayForUser($user, $list);
                $user->isAdmin = $user->hasRole('owner');
                $is2faEnabled  = $preferences['twoFactorAuthEnabled'] === true;
                $has2faSecret  = !is_null($preferences['twoFactorAuthSecret']);
                $user->has2FA  = ($is2faEnabled && $has2faSecret) ? true : false;
                $user->prefs   = $preferences;
            }
        );


        return view('admin.users.index', compact('subTitle', 'subTitleIcon', 'users'));

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
        $information   = $repository->getUserData($user);

        return view(
            'admin.users.show',
            compact(
                'title', 'mainTitleIcon', 'subTitle', 'subTitleIcon', 'information', 'user'
            )
        );
    }

    /**
     * @param UserFormRequest         $request
     * @param User                    $user
     *
     * @param UserRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(UserFormRequest $request, User $user, UserRepositoryInterface $repository)
    {
        Log::debug('Actually here');
        $data = $request->getUserData();

        // update password
        if (strlen($data['password']) > 0) {
            $repository->changePassword($user, $data['password']);
        }

        $repository->changeStatus($user, $data['blocked'], $data['blocked_code']);

        Session::flash('success', strval(trans('firefly.updated_user', ['email' => $user->email])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('users.edit.fromUpdate', true);

            return redirect(route('admin.users.edit', [$user->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('users.edit.uri'));

    }


}
