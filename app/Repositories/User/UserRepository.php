<?php
/**
 * UserRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\User;


use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class UserRepository
 *
 * @package FireflyIII\Repositories\User
 */
class UserRepository implements UserRepositoryInterface
{

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
        $admin = Role::where('name', 'owner')->first();
        $user->attachRole($admin);
        $user->save();

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
     */
    public function changeEmail(User $user, string $newEmail): bool
    {
        $oldEmail = $user->email;

        // save old email as pref
        Preferences::setForUser($user, 'previous_email_latest', $oldEmail);
        Preferences::setForUser($user, 'previous_email_' . date('Y-m-d-H-i-s'), $oldEmail);

        // set undo and confirm token:
        Preferences::setForUser($user, 'email_change_undo_token', strval(bin2hex(random_bytes(16))));
        Preferences::setForUser($user, 'email_change_confirm_token', strval(bin2hex(random_bytes(16))));
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
     * @param User $user
     *
     * @return bool
     */
    public function destroy(User $user): bool
    {
        Log::debug(sprintf('Calling delete() on user %d', $user->id));
        $user->delete();

        return true;
    }

    /**
     * @param int $userId
     *
     * @return User
     */
    public function find(int $userId): User
    {
        $user = User::find($userId);
        if (!is_null($user)) {
            return $user;
        }

        return new User;
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
        $is2faEnabled      = Preferences::getForUser($user, 'twoFactorAuthEnabled', false)->data;
        $has2faSecret      = !is_null(Preferences::getForUser($user, 'twoFactorAuthSecret'));
        $return['has_2fa'] = false;
        if ($is2faEnabled && $has2faSecret) {
            $return['has_2fa'] = true;
        }

        $return['is_admin']            = $user->hasRole('owner');
        $return['blocked']             = intval($user->blocked) === 1;
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
                                                    ->where('budgets.user_id', $user->id)->get(['budget_limits.budget_id'])->count();
        $return['export_jobs']         = $user->exportJobs()->count();
        $return['export_jobs_success'] = $user->exportJobs()->where('status', 'export_downloaded')->count();
        $return['import_jobs']         = $user->exportJobs()->count();
        $return['import_jobs_success'] = $user->exportJobs()->where('status', 'import_complete')->count();
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
        return $user->hasRole($role);
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
        Preferences::setForUser($user, 'admin_previous_email_latest', $oldEmail);
        Preferences::setForUser($user, 'admin_previous_email_' . date('Y-m-d-H-i-s'), $oldEmail);

        $user->email = $newEmail;
        $user->save();

        return true;
    }
}
