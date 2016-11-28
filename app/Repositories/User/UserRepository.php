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

declare(strict_types = 1);

namespace FireflyIII\Repositories\User;


use FireflyConfig;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Support\Collection;
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
     * @return int
     */
    public function count(): int
    {
        return $this->all()->count();
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

        // is user activated?
        $mustConfirmAccount     = FireflyConfig::get('must_confirm_account', config('firefly.configuration.must_confirm_account'))->data;
        $isConfirmed            = Preferences::getForUser($user, 'user_confirmed', false)->data;
        $return['is_activated'] = true;
        if ($isConfirmed === false && $mustConfirmAccount === true) {
            $return['is_activated'] = false;
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
}
