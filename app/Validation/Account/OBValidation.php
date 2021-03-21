<?php

/**
 * OBValidation.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;

/**
 * Trait OBValidation
 */
trait OBValidation
{
    /**
     * @param int|null $accountId
     * @param          $accountName
     *
     * @return bool
     */
    protected function validateOBDestination(?int $accountId, $accountName): bool
    {
        $result = null;
        Log::debug(sprintf('Now in validateOBDestination(%d, "%s")', $accountId, $accountName));

        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a deposit can't be created.
            $this->destError = (string)trans('validation.ob_dest_need_data');
            Log::error('Both values are NULL, cant create OB destination.');
            $result = false;
        }
        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            Log::debug('Can create some of these types, so return true.');
            $result = true;
        }

        if (null === $result) {
            // otherwise try to find the account:
            $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
            if (null === $search) {
                Log::debug('findExistingAccount() returned NULL, so the result is false.', $validTypes);
                $this->destError = (string)trans('validation.ob_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);
                $result          = false;
            }
            if (null !== $search) {
                Log::debug(sprintf('findExistingAccount() returned #%d ("%s"), so the result is true.', $search->id, $search->name));
                $this->destination = $search;
                $result            = true;
            }
        }
        $result = $result ?? false;
        Log::debug(sprintf('validateOBDestination(%d, "%s") will return %s', $accountId, $accountName, var_export($result, true)));

        return $result;
    }

    /**
     * @param array $accountTypes
     *
     * @return bool
     */
    abstract protected function canCreateTypes(array $accountTypes): bool;

    /**
     * Source of an opening balance can either be an asset account
     * or an "initial balance account". The latter can be created.
     *
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    protected function validateOBSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateOBSource(%d, "%s")', $accountId, $accountName));
        Log::debug(sprintf('The account name is null: %s', var_export(null === $accountName, true)));
        $result = null;
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);

        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the source of a deposit can't be created.
            // (this never happens).
            $this->sourceError = (string)trans('validation.ob_source_need_data');
            $result            = false;
        }

        // if the user submits an ID only but that ID is not of the correct type,
        // return false.
        if (null !== $accountId && null === $accountName) {
            Log::debug('Source ID is not null, but name is null.');
            $search = $this->accountRepository->findNull($accountId);

            // the source resulted in an account, but it's not of a valid type.
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                $message = sprintf('User submitted only an ID (#%d), which is a "%s", so this is not a valid source.', $accountId, $search->accountType->type);
                Log::debug($message);
                $this->sourceError = $message;
                $result            = false;
            }
            // the source resulted in an account, AND it's of a valid type.
            if (null !== $search && in_array($search->accountType->type, $validTypes, true)) {
                Log::debug(sprintf('Found account of correct type: #%d, "%s"', $search->id, $search->name));
                $this->source = $search;
                $result       = true;
            }
        }

        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            Log::debug('Result is still null.');
            $result = true;

            // set the source to be a (dummy) initial balance account.
            $account              = new Account;
            $accountType          = AccountType::whereType(AccountType::INITIAL_BALANCE)->first();
            $account->accountType = $accountType;
            $this->source         = $account;
        }

        return $result ?? false;
    }
}
