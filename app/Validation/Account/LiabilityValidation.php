<?php

/*
 * LiabilityValidation.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Validation\Account;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Trait LiabilityValidation
 */
trait LiabilityValidation
{
    protected function validateLCDestination(array $array): bool
    {
        app('log')->debug('Now in validateLCDestination', $array);
        $result      = null;
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        $validTypes  = config('firefly.valid_liabilities');

        // if the ID is not null the source account should be a dummy account of the type liability credit.
        // the ID of the destination must belong to a liability.
        if (null !== $accountId) {
            if (AccountTypeEnum::LIABILITY_CREDIT->value !== $this->source?->accountType?->type) {
                app('log')->error('Source account is not a liability.');

                return false;
            }
            $result = $this->findExistingAccount($validTypes, $array);
            if (null === $result) {
                app('log')->error('Destination account is not a liability.');

                return false;
            }

            return true;
        }

        if (null !== $accountName && '' !== $accountName) {
            app('log')->debug('Destination ID is null, now we can assume the destination is a (new) liability credit account.');

            return true;
        }
        app('log')->error('Destination ID is null, but destination name is also NULL.');

        return false;
    }

    /**
     * Source of a liability credit must be a liability or liability credit account.
     */
    protected function validateLCSource(array $array): bool
    {
        app('log')->debug('Now in validateLCSource', $array);
        // if the array has an ID and ID is not null, try to find it and check type.
        // this account must be a liability
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        if (null !== $accountId) {
            app('log')->debug('Source ID is not null, assume were looking for a liability.');
            // find liability credit:
            $result = $this->findExistingAccount(config('firefly.valid_liabilities'), $array);
            if (null === $result) {
                app('log')->error('Did not find a liability account, return false.');

                return false;
            }
            app('log')->debug(sprintf('Return true, found #%d ("%s")', $result->id, $result->name));
            $this->setSource($result);

            return true;
        }

        // if array has name and is not null, return true.
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;

        $result      = true;
        if ('' === $accountName || null === $accountName) {
            app('log')->error('Array must have a name, is not the case, return false.');
            $result = false;
        }
        if (true === $result) {
            app('log')->error('Array has a name, return true.');
            // set the source to be a (dummy) revenue account.
            $account              = new Account();
            $accountType          = AccountType::whereType(AccountTypeEnum::LIABILITY_CREDIT->value)->first();
            $account->accountType = $accountType;
            $this->setSource($account);
        }

        return $result;
    }
}
