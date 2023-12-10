<?php

/**
 * IsAssetAccountId.php
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

namespace FireflyIII\Rules;

use Closure;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 *
 * Class IsAssetAccountId
 */
class IsAssetAccountId implements ValidationRule
{
    /**
     * @param string  $attribute
     * @param mixed   $value
     * @param Closure $fail
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $accountId = (int)$value;
        /** @var Account|null $account */
        $account = Account::with('accountType')->find($accountId);
        if (null === $account) {
            $fail('validation.no_asset_account')->translate();
            return;
        }
        if ($account->accountType->type !== AccountType::ASSET && $account->accountType->type !== AccountType::DEFAULT) {
            $fail('validation.no_asset_account')->translate();
        }
    }
}
