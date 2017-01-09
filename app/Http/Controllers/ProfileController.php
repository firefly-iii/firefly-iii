<?php
/**
 * ProfileController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Hash;
use Log;
use Session;
use View;

/**
 * Class ProfileController
 *
 * @package FireflyIII\Http\Controllers
 */
class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.profile'));
                View::share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function changePassword()
    {
        if (auth()->user()->hasRole('demo')) {
            Session::flash('info', strval(trans('firefly.cannot_change_demo')));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.change_your_password'));
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @return View
     */
    public function deleteAccount()
    {
        if (auth()->user()->hasRole('demo')) {
            Session::flash('info', strval(trans('firefly.cannot_delete_demo')));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.delete_account'));
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @return View
     *
     */
    public function index()
    {
        $subTitle = auth()->user()->email;
        $userId   = auth()->user()->id;

        return view('profile.index', compact('subTitle', 'userId'));
    }

    /**
     * @param ProfileFormRequest      $request
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postChangePassword(ProfileFormRequest $request, UserRepositoryInterface $repository)
    {
        if (auth()->user()->hasRole('demo')) {
            Session::flash('info', strval(trans('firefly.cannot_change_demo')));

            return redirect(route('profile.index'));
        }

        // old, new1, new2
        if (!Hash::check($request->get('current_password'), auth()->user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_current_password')));

            return redirect(route('profile.change-password'));
        }

        try {
            $this->validatePassword($request->get('current_password'), $request->get('new_password'));
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());

            return redirect(route('profile.change-password'));
        }

        // update the user with the new password.
        $repository->changePassword(auth()->user(), $request->get('new_password'));
        Session::flash('success', strval(trans('firefly.password_changed')));

        return redirect(route('profile.index'));
    }

    /**
     * @param UserRepositoryInterface  $repository
     * @param DeleteAccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postDeleteAccount(UserRepositoryInterface $repository, DeleteAccountFormRequest $request)
    {
        if (auth()->user()->hasRole('demo')) {
            Session::flash('info', strval(trans('firefly.cannot_delete_demo')));

            return redirect(route('profile.index'));
        }

        // old, new1, new2
        if (!Hash::check($request->get('password'), auth()->user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_password')));

            return redirect(route('profile.delete-account'));
        }
        $user = auth()->user();
        Log::info(sprintf('User #%d has opted to delete their account', auth()->user()->id));
        // make repository delete user:
        auth()->logout();
        Session::flush();
        $repository->destroy($user);

        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');


        return redirect(route('index'));
    }

    /**
     * @param string $old
     * @param string $new
     *
     * @return bool
     * @throws ValidationException
     */
    protected function validatePassword(string $old, string $new): bool
    {
        if ($new === $old) {
            throw new ValidationException(strval(trans('firefly.should_change')));
        }

        return true;

    }


}
