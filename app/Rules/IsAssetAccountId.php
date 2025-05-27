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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

/**
 * Class IsAssetAccountId
 */
class IsAssetAccountId implements ValidationRule
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $accountId = (int) $value;

        /** @var null|Account $account */
        $account   = Account::with('accountType')->find($accountId);
        if (null === $account) {
            $fail('validation.no_asset_account')->translate();

            return;
        }
        if (AccountTypeEnum::ASSET->value !== $account->accountType->type && AccountTypeEnum::DEFAULT->value !== $account->accountType->type) {
            $fail('validation.no_asset_account')->translate();
        }
    }
}
