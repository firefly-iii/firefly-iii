<?php

/**
 * UserRepository.php
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

namespace FireflyIII\Repositories\User;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Models\Role;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Class UserRepository.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * This updates the users email address and records some things so it can be confirmed or undone later.
     * The user is blocked until the change is confirmed.
     *
     * @throws \Exception
     *
     * @see updateEmail
     */
    public function changeEmail(User $user, string $newEmail): bool
    {
        $oldEmail           = $user->email;

        // save old email as pref
        app('preferences')->setForUser($user, 'previous_email_latest', $oldEmail);
        app('preferences')->setForUser($user, 'previous_email_'.date('Y-m-d-H-i-s'), $oldEmail);

        // set undo and confirm token:
        app('preferences')->setForUser($user, 'email_change_undo_token', bin2hex(random_bytes(16)));
        app('preferences')->setForUser($user, 'email_change_confirm_token', bin2hex(random_bytes(16)));
        // update user

        $user->email        = $newEmail;
        $user->blocked      = true;
        $user->blocked_code = 'email_changed';
        $user->save();

        return true;
    }

    public function changePassword(User $user, string $password): bool
    {
        $user->password = bcrypt($password);
        $user->save();

        return true;
    }

    public function changeStatus(User $user, bool $isBlocked, string $code): bool
    {
        // change blocked status and code:
        $user->blocked      = $isBlocked;
        $user->blocked_code = $code;
        $user->save();

        return true;
    }

    public function createRole(string $name, string $displayName, string $description): Role
    {
        return Role::create(['name' => $name, 'display_name' => $displayName, 'description' => $description]);
    }

    public function deleteInvite(InvitedUser $invite): void
    {
        app('log')->debug(sprintf('Deleting invite #%d', $invite->id));
        $invite->delete();
    }

    /**
     * @throws \Exception
     */
    public function destroy(User $user): bool
    {
        app('log')->debug(sprintf('Calling delete() on user %d', $user->id));

        $user->groupMemberships()->delete();
        $user->delete();
        $this->deleteEmptyGroups();

        return true;
    }

    public function deleteEmptyGroups(): void
    {
        $groups = UserGroup::get();

        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $count = $group->groupMemberships()->count();
            if (0 === $count) {
                app('log')->info(sprintf('Deleted empty group #%d ("%s")', $group->id, $group->title));
                $group->delete();
            }
        }
    }

    public function count(): int
    {
        return $this->all()->count();
    }

    public function all(): Collection
    {
        return User::orderBy('id', 'DESC')->get(['users.*']);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Returns the first user in the DB. Generally only works when there is just one.
     */
    public function first(): ?User
    {
        return User::orderBy('id', 'ASC')->first();
    }

    public function getInvitedUsers(): Collection
    {
        return InvitedUser::with('user')->get();
    }

    public function getRoleByUser(User $user): ?string
    {
        /** @var null|Role $role */
        $role = $user->roles()->first();
        if (null !== $role) {
            return $role->name;
        }

        return null;
    }

    /**
     * @throws FireflyException
     */
    public function getRolesInGroup(User $user, int $groupId): array
    {
        /** @var null|UserGroup $group */
        $group       = UserGroup::find($groupId);
        if (null === $group) {
            throw new FireflyException(sprintf('Could not find group #%d', $groupId));
        }
        $memberships = $group->groupMemberships()->where('user_id', $user->id)->get();
        $roles       = [];

        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            $role    = $membership->userRole;
            $roles[] = $role->title;
        }

        return $roles;
    }

    public function find(int $userId): ?User
    {
        return User::find($userId);
    }

    /**
     * Return basic user information.
     */
    public function getUserData(User $user): array
    {
        $return                        = [];

        // two factor:
        $return['has_2fa']             = null !== $user->mfa_secret;
        $return['is_admin']            = $this->hasRole($user, 'owner');
        $return['blocked']             = 1 === (int) $user->blocked;
        $return['blocked_code']        = $user->blocked_code;
        $return['accounts']            = $user->accounts()->count();
        $return['journals']            = $user->transactionJournals()->count();
        $return['transactions']        = $user->transactions()->count();
        $return['attachments']         = $user->attachments()->count();
        $return['attachments_size']    = $user->attachments()->sum('size');
        $return['bills']               = $user->bills()->count();
        $return['categories']          = $user->categories()->count();
        $return['budgets']             = $user->budgets()->count();
        $return['budgets_with_limits'] = BudgetLimit::distinct()
            ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
            ->where('amount', '>', 0)
            ->whereNull('budgets.deleted_at')
            ->where('budgets.user_id', $user->id)
            ->count('budget_limits.budget_id')
        ;
        $return['rule_groups']         = $user->ruleGroups()->count();
        $return['rules']               = $user->rules()->count();
        $return['tags']                = $user->tags()->count();

        return $return;
    }

    public function hasRole(null|Authenticatable|User $user, string $role): bool
    {
        if (null === $user) {
            return false;
        }
        if ($user instanceof User) {
            /** @var Role $userRole */
            foreach ($user->roles as $userRole) {
                if ($userRole->name === $role) {
                    return true;
                }
            }
        }

        return false;
    }

    #[\Override]
    public function getUserGroups(User $user): Collection
    {
        $memberships = $user->groupMemberships()->get();
        $set         = [];
        $collection  = new Collection();

        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var null|UserGroup $group */
            $group = $membership->userGroup()->first();
            if (null !== $group) {
                $groupId       = $group->id;
                if (in_array($groupId, array_keys($set), true)) {
                    continue;
                }
                $set[$groupId] = $group;
            }
        }
        $collection->push(...$set);

        return $collection;
    }

    public function inviteUser(null|Authenticatable|User $user, string $email): InvitedUser
    {
        $now                  = today(config('app.timezone'));
        $now->addDays(2);
        $invitee              = new InvitedUser();
        $invitee->user()->associate($user);
        $invitee->invite_code = \Str::random(64);
        $invitee->email       = $email;
        $invitee->redeemed    = false;
        $invitee->expires     = $now;
        $invitee->expires_tz  = $now->format('e');
        $invitee->save();

        return $invitee;
    }

    public function redeemCode(string $code): void
    {
        $obj = InvitedUser::where('invite_code', $code)->where('redeemed', 0)->first();
        if (null !== $obj) {
            $obj->redeemed = true;
            $obj->save();
        }
    }

    /**
     * Set MFA code.
     */
    public function setMFACode(User $user, ?string $code): void
    {
        $user->mfa_secret = $code;
        $user->save();
    }

    public function store(array $data): User
    {
        $user = User::create(
            [
                'blocked'      => $data['blocked'] ?? false,
                'blocked_code' => $data['blocked_code'] ?? null,
                'email'        => $data['email'],
                'password'     => \Str::random(24),
            ]
        );
        $role = $data['role'] ?? '';
        if ('' !== $role) {
            $this->attachRole($user, $role);
        }

        return $user;
    }

    public function attachRole(User $user, string $role): bool
    {
        $roleObject = Role::where('name', $role)->first();
        if (null === $roleObject) {
            app('log')->error(sprintf('Could not find role "%s" in attachRole()', $role));

            return false;
        }

        try {
            $user->roles()->attach($roleObject);
        } catch (QueryException $e) {
            // don't care
            app('log')->error(sprintf('Query exception when giving user a role: %s', $e->getMessage()));
        }

        return true;
    }

    public function unblockUser(User $user): void
    {
        $user->blocked      = false;
        $user->blocked_code = '';
        $user->save();
    }

    /**
     * Update user info.
     *
     * @throws FireflyException
     */
    public function update(User $user, array $data): User
    {
        $this->updateEmail($user, $data['email'] ?? '');
        if (array_key_exists('blocked', $data) && is_bool($data['blocked'])) {
            $user->blocked = $data['blocked'];
        }
        if (array_key_exists('blocked_code', $data) && '' !== $data['blocked_code'] && is_string($data['blocked_code'])) {
            $user->blocked_code = $data['blocked_code'];
        }
        if (array_key_exists('role', $data) && '' === $data['role']) {
            $this->removeRole($user, 'owner');
            $this->removeRole($user, 'demo');
        }

        $user->save();

        return $user;
    }

    /**
     * This updates the users email address. Same as changeEmail just without most logging. This makes sure that the
     * undo/confirm routine can't catch this one. The user is NOT blocked.
     *
     * @throws FireflyException
     *
     * @see changeEmail
     */
    public function updateEmail(User $user, string $newEmail): bool
    {
        if ('' === $newEmail) {
            return true;
        }
        $oldEmail    = $user->email;

        // save old email as pref
        app('preferences')->setForUser($user, 'admin_previous_email_latest', $oldEmail);
        app('preferences')->setForUser($user, 'admin_previous_email_'.date('Y-m-d-H-i-s'), $oldEmail);

        $user->email = $newEmail;
        $user->save();

        return true;
    }

    /**
     * Remove any role the user has.
     */
    public function removeRole(User $user, string $role): void
    {
        $roleObj = $this->getRole($role);
        if (null === $roleObj) {
            return;
        }
        $user->roles()->detach($roleObj->id);
    }

    public function getRole(string $role): ?Role
    {
        return Role::where('name', $role)->first();
    }

    public function validateInviteCode(string $code): bool
    {
        $now     = today(config('app.timezone'));
        $invitee = InvitedUser::where('invite_code', $code)->where('expires', '>', $now->format('Y-m-d H:i:s'))->where('redeemed', 0)->first();

        return null !== $invitee;
    }
}
