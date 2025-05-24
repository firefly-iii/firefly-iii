<?php

/**
 * RegisterController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Events\RegisteredUser;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class RegisterController
 *
 * This controller handles the registration of new users as well as their
 * validation and creation. By default this controller uses a trait to
 * provide this functionality without requiring any additional code.
 */
class RegisterController extends Controller
{
    use CreateStuff;
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');

        if ('web' !== config('firefly.authentication_guard')) {
            throw new FireflyException('Using external identity provider. Cannot continue.');
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @return Application|Redirector|RedirectResponse
     *
     * @throws FireflyException
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        $allowRegistration = $this->allowedToRegister();
        $inviteCode        = (string) $request->get('invite_code');
        $repository        = app(UserRepositoryInterface::class);
        $validCode         = $repository->validateInviteCode($inviteCode);

        if (false === $allowRegistration && false === $validCode) {
            throw new FireflyException('Registration is currently not available :(');
        }

        $this->validator($request->all())->validate();
        $user              = $this->createUser($request->all());
        app('log')->info(sprintf('Registered new user %s', $user->email));
        $owner             = new OwnerNotifiable();
        event(new RegisteredUser($owner, $user));

        $this->guard()->login($user);

        session()->flash('success', (string) trans('firefly.registered'));

        $this->registered($request, $user);

        if ($validCode) {
            $repository->redeemCode($inviteCode);
        }

        return redirect($this->redirectPath());
    }

    /**
     * @throws FireflyException
     */
    protected function allowedToRegister(): bool
    {
        // is allowed to register?
        $allowRegistration = true;

        try {
            $singleUserMode = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
            $singleUserMode = true;
        }
        $userCount         = User::count();
        $guard             = config('auth.defaults.guard');
        if (true === $singleUserMode && $userCount > 0 && 'web' === $guard) {
            $allowRegistration = false;
        }
        if ('web' !== $guard) {
            return false;
        }

        return $allowRegistration;
    }

    /**
     * Show the application registration form if the invitation code is valid.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function showInviteForm(Request $request, string $code)
    {
        $isDemoSite        = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        $pageTitle         = (string) trans('firefly.register_page_title');
        $repository        = app(UserRepositoryInterface::class);
        $allowRegistration = $this->allowedToRegister();
        $inviteCode        = $code;
        $validCode         = $repository->validateInviteCode($inviteCode);

        if (true === $allowRegistration) {
            $message = 'You do not need an invite code on this installation.';

            return view('error', compact('message'));
        }
        if (false === $validCode) {
            $message = 'Invalid code.';

            return view('error', compact('message'));
        }

        $email             = $request->old('email');

        return view('auth.register', compact('isDemoSite', 'email', 'pageTitle', 'inviteCode'));
    }

    /**
     * Show the application registration form.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function showRegistrationForm(?Request $request = null)
    {
        $isDemoSite        = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        $pageTitle         = (string) trans('firefly.register_page_title');
        $allowRegistration = $this->allowedToRegister();

        if (false === $allowRegistration) {
            $message = 'Registration is currently not available. If you are the administrator, you can enable this in the administration.';

            return view('error', compact('message'));
        }

        $email             = $request?->old('email');

        return view('auth.register', compact('isDemoSite', 'email', 'pageTitle'));
    }
}
