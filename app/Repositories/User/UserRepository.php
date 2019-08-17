<?php
/**
 * UserRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\User;

use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;

/**
 * Class UserRepository.
 *
 *
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return User::orderBy('id', 'DESC')->get(['users.*']);
    }

    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function attachRole(User $user, string $role): bool
    {
        $roleObject = Role::where('name', $role)->first();
        if (null === $roleObject) {
            Log::error(sprintf('Could not find role "%s" in attachRole()', $role));

            return false;
        }

        try {
            $user->roles()->attach($roleObject);
        } catch (QueryException $e) {
            // don't care
            Log::error(sprintf('Query exception when giving user a role: %s', $e->getMessage()));
        }

        return true;
    }

    /**
     * This updates the users email address and records some things so it can be confirmed or undone later.
     * The user is blocked until the change is confirmed.
     *
     * @param User   $user
     * @param string $newEmail
     *
     * @see updateEmail
     *
     * @return bool
     * @throws \Exception
     */
    public function changeEmail(User $user, string $newEmail): bool
    {
        $oldEmail = $user->email;

        // save old email as pref
        app('preferences')->setForUser($user, 'previous_email_latest', $oldEmail);
        app('preferences')->setForUser($user, 'previous_email_' . date('Y-m-d-H-i-s'), $oldEmail);

        // set undo and confirm token:
        app('preferences')->setForUser($user, 'email_change_undo_token', bin2hex(random_bytes(16)));
        app('preferences')->setForUser($user, 'email_change_confirm_token', bin2hex(random_bytes(16)));
        // update user

        $user->email        = $newEmail;
        $user->blocked      = 1;
        $user->blocked_code = 'email_changed';
        $user->save();

        return true;
    }

    /**
     * @param User   $user
     * @param string $password
     *
     * @return bool
     */
    public function changePassword(User $user, string $password): bool
    {
        $user->password = bcrypt($password);
        $user->save();

        return true;
    }

    /**
     * @param User   $user
     * @param bool   $isBlocked
     * @param string $code
     *
     * @return bool
     */
    public function changeStatus(User $user, bool $isBlocked, string $code): bool
    {
        // change blocked status and code:
        $user->blocked      = $isBlocked;
        $user->blocked_code = $code;
        $user->save();

        return true;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * @param string $name
     * @param string $displayName
     * @param string $description
     *
     * @return Role
     */
    public function createRole(string $name, string $displayName, string $description): Role
    {
        return Role::create(['name' => $name, 'display_name' => $displayName, 'description' => $description]);
    }

    /**
     * @param User $user
     *
     * @return bool
     * @throws \Exception
     */
    public function destroy(User $user): bool
    {
        Log::debug(sprintf('Calling delete() on user %d', $user->id));
        $user->delete();

        return true;
    }

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * @param int $userId
     *
     * @return User|null
     */
    public function findNull(int $userId): ?User
    {
        return User::find($userId);
    }

    /**
     * Returns the first user in the DB. Generally only works when there is just one.
     *
     * @return null|User
     */
    public function first(): ?User
    {
        return User::orderBy('id', 'ASC')->first();
    }

    /**
     * @param string $role
     *
     * @return Role|null
     */
    public function getRole(string $role): ?Role
    {
        return Role::where('name', $role)->first();
    }

    /**
     * @param User $user
     *
     * @return string|null
     */
    public function getRoleByUser(User $user): ?string
    {
        /** @var Role $role */
        $role = $user->roles()->first();
        if (null !== $role) {
            return $role->name;
        }

        return null;
    }

    /**
     * Return basic user information.
     *
     * @param User $user
     *
     * @return array
     */
    public function getUserData(User $user): array
    {
        $return = [];

        // two factor:
        $return['has_2fa']      = $user->mfa_secret !== null;
        $return['is_admin']     = $this->hasRole($user, 'owner');
        $return['blocked']      = 1 === (int)$user->blocked;
        $return['blocked_code'] = $user->blocked_code;
        $return['accounts']     = $user->accounts()->count();
        $return['journals']     = $user->transactionJournals()->count();
        $return['transactions'] = $user->transactions()->count();
        $return['attachments']  = $user->attachments()->count();
        $return['attachments_size']    = $user->attachments()->sum('size');
        $return['bills']               = $user->bills()->count();
        $return['categories']          = $user->categories()->count();
        $return['budgets']             = $user->budgets()->count();
        $return['budgets_with_limits'] = BudgetLimit::distinct()
                                                    ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                                    ->where('amount', '>', 0)
                                                    ->whereNull('budgets.deleted_at')
                                                    ->where('budgets.user_id', $user->id)->get(['budget_limits.budget_id'])->count();
        $return['import_jobs']         = $user->importJobs()->count();
        $return['import_jobs_success'] = $user->importJobs()->where('status', 'finished')->count();
        $return['rule_groups']         = $user->ruleGroups()->count();
        $return['rules']               = $user->rules()->count();
        $return['tags']                = $user->tags()->count();

        return $return;
    }

    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(User $user, string $role): bool
    {
        // TODO no longer need to loop like this

        /** @var Role $userRole */
        foreach ($user->roles as $userRole) {
            if ($userRole->name === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function store(array $data): User
    {
        $user = User::create(
            [
                'blocked'      => $data['blocked'] ?? false,
                'blocked_code' => $data['blocked_code'] ?? null,
                'email'        => $data['email'],
                'password'     => str_random(24),
            ]
        );
        $role = $data['role'] ?? '';
        if ('' !== $role) {
            $this->attachRole($user, $role);
        }

        return $user;
    }

    /**
     * @param User $user
     */
    public function unblockUser(User $user): void
    {
        $user->blocked      = 0;
        $user->blocked_code = '';
        $user->save();

    }

    /**
     * Update user info.
     *
     * @param User  $user
     * @param array $data
     *
     * @return User
     */
    public function update(User $user, array $data): User
    {
        $this->updateEmail($user, $data['email']);
        $user->blocked      = $data['blocked'] ?? false;
        $user->blocked_code = $data['blocked_code'] ?? null;
        $user->save();

        return $user;
    }

    /**
     * This updates the users email address. Same as changeEmail just without most logging. This makes sure that the undo/confirm routine can't catch this one.
     * The user is NOT blocked.
     *
     * @param User   $user
     * @param string $newEmail
     *
     * @see changeEmail
     *
     * @return bool
     */
    public function updateEmail(User $user, string $newEmail): bool
    {
        $oldEmail = $user->email;

        // save old email as pref
        app('preferences')->setForUser($user, 'admin_previous_email_latest', $oldEmail);
        app('preferences')->setForUser($user, 'admin_previous_email_' . date('Y-m-d-H-i-s'), $oldEmail);

        $user->email = $newEmail;
        $user->save();

        return true;
    }

    /**
     * Set MFA code.
     *
     * @param User   $user
     * @param string|null $code
     */
    public function setMFACode(User $user, ?string $code): void
    {
        $user->mfa_secret = $code;
        $user->save();
    }
}
