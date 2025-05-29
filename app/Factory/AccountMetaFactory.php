<?php

/**
 * AccountMetaFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Support\Facades\Steam;

/**
 * Class AccountMetaFactory
 */
class AccountMetaFactory
{
    /**
     * Create update or delete meta data.
     */
    public function crud(Account $account, string $field, string $value): ?AccountMeta
    {
        /** @var null|AccountMeta $entry */
        $entry = $account->accountMeta()->where('name', $field)->first();
        // must not be an empty string:
        if ('' !== $value) {
            if('account_number' === $field) {
                $value = Steam::filterSpaces($value);
                $value = trim(str_replace([' ',"\t", "\n", "\r"], '', $value));
            }
            // if $data has field and $entry is null, create new one:
            if (null === $entry) {
                return $this->create(['account_id' => $account->id, 'name' => $field, 'data' => $value]);
            }


            // if $data has field and $entry is not null, update $entry:
            $entry->data = $value;
            $entry->save();
        }
        if ('' === $value && null !== $entry) {
            $entry->delete();

            return null;
        }

        return $entry;
    }

    public function create(array $data): ?AccountMeta
    {
        return AccountMeta::create($data);
    }
}
