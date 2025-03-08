<?php

/*
 * PiggyBankRepository.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\UserGroups\PiggyBank;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;

/**
 * Class PiggyBankRepository
 *
 * @deprecated
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{
    use UserGroupTrait;

    public function getPiggyBanks(): Collection
    {
        return PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
                        ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
                        ->where('accounts.user_group_id', $this->userGroup->id)
                        ->with(
                            [
                                'objectGroups',
                            ]
                        )
                        ->orderBy('piggy_banks.order', 'ASC')->distinct()->get(['piggy_banks.*']);
    }
}
