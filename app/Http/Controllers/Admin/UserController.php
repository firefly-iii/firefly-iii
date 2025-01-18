<?php

/**
 * UserController.php
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

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\InviteUserFormRequest;
use FireflyIII\Http\Requests\UserFormRequest;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class UserController.
 */
class UserController extends Controller
{
    protected bool                  $externalIdentity;
    private UserRepositoryInterface $repository;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.system_settings'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');
                $this->repository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index', 'show']);
        $this->externalIdentity = 'web' !== config('firefly.authentication_guard');
    }

    /**
     * @return Application|Factory|Redirector|RedirectResponse|View
     */
    public function delete(User $user)
    {
        if ($this->externalIdentity) {
            request()->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('settings.users'));
        }

        $subTitle = (string) trans('firefly.delete_user', ['email' => $user->email]);

        return view('settings.users.delete', compact('user', 'subTitle'));
    }

    public function deleteInvite(InvitedUser $invitedUser): JsonResponse
    {
        app('log')->debug('Will now delete invitation');
        if (true === $invitedUser->redeemed) {
            app('log')->debug('Is already redeemed.');
            session()->flash('error', trans('firefly.invite_is_already_redeemed', ['address' => $invitedUser->email]));

            return response()->json(['success' => false]);
        }
        app('log')->debug('Delete!');
        session()->flash('success', trans('firefly.invite_is_deleted', ['address' => $invitedUser->email]));
        $this->repository->deleteInvite($invitedUser);

        return response()->json(['success' => true]);
    }

    /**
     * Destroy a user.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(User $user)
    {
        if ($this->externalIdentity) {
            request()->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('settings.users'));
        }
        $this->repository->destroy($user);
        session()->flash('success', (string) trans('firefly.user_deleted'));

        return redirect(route('settings.users'));
    }

    /**
     * Edit user form.
     *
     * @return Factory|View
     */
    public function edit(User $user)
    {
        $canEditDetails = true;
        if ($this->externalIdentity) {
            $canEditDetails = false;
        }
        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('users.edit.fromUpdate')) {
            $this->rememberPreviousUrl('users.edit.url');
        }
        session()->forget('users.edit.fromUpdate');

        $subTitle       = (string) trans('firefly.edit_user', ['email' => $user->email]);
        $subTitleIcon   = 'fa-user-o';
        $currentUser    = auth()->user();
        $isAdmin        = $this->repository->hasRole($user, 'owner');
        $codes          = [
            ''              => (string) trans('firefly.no_block_code'),
            'bounced'       => (string) trans('firefly.block_code_bounced'),
            'expired'       => (string) trans('firefly.block_code_expired'),
            'email_changed' => (string) trans('firefly.block_code_email_changed'),
        ];

        return view('settings.users.edit', compact('user', 'canEditDetails', 'subTitle', 'subTitleIcon', 'codes', 'currentUser', 'isAdmin'));
    }

    /**
     * Show index of user manager.
     *
     * @return Factory|View
     */
    public function index()
    {
        $subTitle       = (string) trans('firefly.user_administration');
        $subTitleIcon   = 'fa-users';
        $users          = $this->repository->all();
        $singleUserMode = (bool) app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $allowInvites   = false;
        if (!$this->externalIdentity && $singleUserMode) {
            // also registration enabled.
            $allowInvites = true;
        }

        $invitedUsers   = $this->repository->getInvitedUsers();

        // add meta stuff.
        $users->each(
            function (User $user): void {
                $user->isAdmin = $this->repository->hasRole($user, 'owner');
                $user->has2FA  = null !== $user->mfa_secret;
            }
        );

        return view('settings.users.index', compact('subTitle', 'subTitleIcon', 'users', 'allowInvites', 'invitedUsers'));
    }

    public function invite(InviteUserFormRequest $request): RedirectResponse
    {
        $address = (string) $request->get('invited_user');
        $invitee = $this->repository->inviteUser(auth()->user(), $address);
        session()->flash('info', trans('firefly.user_is_invited', ['address' => $address]));

        // event!
        event(new InvitationCreated($invitee));

        return redirect(route('settings.users'));
    }

    /**
     * Show single user.
     *
     * @return Factory|View
     */
    public function show(User $user)
    {
        $title         = (string) trans('firefly.system_settings');
        $mainTitleIcon = 'fa-hand-spock-o';
        $subTitle      = (string) trans('firefly.single_user_administration', ['email' => $user->email]);
        $subTitleIcon  = 'fa-user';
        $information   = $this->repository->getUserData($user);

        return view(
            'settings.users.show',
            compact(
                'title',
                'mainTitleIcon',
                'subTitle',
                'subTitleIcon',
                'information',
                'user'
            )
        );
    }

    /**
     * Update single user.
     *
     * @return $this|Redirector|RedirectResponse
     */
    public function update(UserFormRequest $request, User $user)
    {
        app('log')->debug('Actually here');
        $data     = $request->getUserData();

        // var_dump($data);

        // update password
        if (array_key_exists('password', $data) && '' !== $data['password']) {
            $this->repository->changePassword($user, $data['password']);
        }
        if (true === $data['is_owner']) {
            $this->repository->attachRole($user, 'owner');
            session()->flash('info', trans('firefly.give_admin_careful'));
        }
        if (false === $data['is_owner'] && $user->id !== auth()->user()->id) {
            $this->repository->removeRole($user, 'owner');
        }

        $this->repository->changeStatus($user, $data['blocked'], $data['blocked_code']);
        $this->repository->updateEmail($user, $data['email']);

        session()->flash('success', (string) trans('firefly.updated_user', ['email' => $user->email]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUrl('users.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            session()->put('users.edit.fromUpdate', true);

            $redirect = redirect(route('settings.users.edit', [$user->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return $redirect;
    }
}
