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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Middleware\IsLimitedUser;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
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
        $this->middleware(IsLimitedUser::class);

    }

    /**
     * @return View
     */
    public function changePassword()
    {
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
        // the request has already validated both new passwords must be equal.
        $current = $request->get('current_password');
        $new     = $request->get('new_password');

        try {
            $this->validatePassword(auth()->user(), $current, $new);
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());

            return redirect(route('profile.change-password'));
        }

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
     * @param User   $user
     * @param string $current
     * @param string $new
     *
     * @return bool
     * @throws ValidationException
     */
    protected function validatePassword(User $user, string $current, string $new): bool
    {
        if (!Hash::check($current, $user->password)) {
            throw new ValidationException(strval(trans('firefly.invalid_current_password')));
        }

        if ($current === $new) {
            throw new ValidationException(strval(trans('firefly.should_change')));
        }

        return true;

    }


}
