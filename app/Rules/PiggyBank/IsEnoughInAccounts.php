<?php
/*
 * IsEnoughInAccounts.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Rules\PiggyBank;

use Closure;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\Validation\ValidationRule;

class IsEnoughInAccounts implements ValidationRule
{
    public function __construct(
        private readonly PiggyBank $piggyBank,
        private readonly array     $data
    ) {}


    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // TODO: Implement validate() method.
        if (!array_key_exists('accounts', $this->data)) {
            return;
        }
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var PiggyBankRepositoryInterface $piggyRepos */
        $piggyRepos = app(PiggyBankRepositoryInterface::class);

        $accounts = $this->data['accounts'];
        foreach ($accounts as $info) {
            $account = $repository->find((int)$info['account_id']);
            $amount  = $info['current_amount'] ?? '0';
            if (null === $account) {
                $fail('validation.no_asset_account')->translate();
                return;
            }
            if ('' === $amount || 0 === bccomp($amount, '0')) {
                $fail('validation.more_than_zero_correct')->translate();
                return;
            }
            $diff = bcsub($amount, $piggyRepos->getCurrentAmount($this->piggyBank, $account));
            if (1 === bccomp($diff, '0') && !$piggyRepos->canAddAmount($this->piggyBank, $account, $amount)) {
                $fail('validation.cannot_add_piggy_amount')->translate();
            }
        }
    }
}
