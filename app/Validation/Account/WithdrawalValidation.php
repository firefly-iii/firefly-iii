<?php

/**
 * WithdrawalValidation.php
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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;

/**
 * Trait WithdrawalValidation
 */
trait WithdrawalValidation
{
    protected function validateGenericSource(array $array): bool
    {
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban = array_key_exists('iban', $array) ? $array['iban'] : null;
        app('log')->debug('Now in validateGenericSource', $array);
        // source can be any of the following types.
        $validTypes  = [AccountTypeEnum::ASSET->value, AccountTypeEnum::REVENUE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        if (null === $accountId && null === $accountName && null === $accountIban && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return TRUE
            // because we assume the user doesn't want to submit / change anything.
            $this->sourceError = (string) trans('validation.withdrawal_source_need_data');
            app('log')->warning('[a] Not a valid source. Need more data.');

            return false;
        }

        // otherwise try to find the account:
        $search      = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.withdrawal_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            app('log')->warning('Not a valid source. Cant find it.', $validTypes);

            return false;
        }
        $this->setSource($search);
        app('log')->debug('Valid source account!');

        return true;
    }

    abstract protected function canCreateTypes(array $accountTypes): bool;

    abstract protected function findExistingAccount(array $validTypes, array $data): ?Account;

    protected function validateWithdrawalDestination(array $array): bool
    {
        $accountId     = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName   = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban   = array_key_exists('iban', $array) ? $array['iban'] : null;
        $accountNumber = array_key_exists('number', $array) ? $array['number'] : null;
        app('log')->debug('Now in validateWithdrawalDestination()', $array);
        // source can be any of the following types.
        $validTypes    = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        app('log')->debug('Source type can be: ', $validTypes);
        if (null === $accountId && null === $accountName && null === $accountIban && null === $accountNumber && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the destination of a withdrawal can never be created automatically.
            $this->destError = (string) trans('validation.withdrawal_dest_need_data');

            return false;
        }

        // if there's an ID it must be of the "validTypes".
        if (null !== $accountId && 0 !== $accountId) {
            $found = $this->accountRepository->find($accountId);
            if (null !== $found) {
                $type            = $found->accountType->type;
                if (in_array($type, $validTypes, true)) {
                    $this->setDestination($found);

                    return true;
                }
                // todo explain error in log message.
                $this->destError = (string) trans('validation.withdrawal_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);

                return false;
            }
        }
        // if there is an iban, it can only be in use by a valid destination type, or we will fail.
        // the inverse of $validTypes is
        if (null !== $accountIban && '' !== $accountIban) {
            app('log')->debug('Check if there is not already an account with this IBAN');
            // the inverse flag reverses the search, searching for everything that is NOT a valid type.
            $existing = $this->findExistingAccount($validTypes, ['iban' => $accountIban], true);
            if (null !== $existing) {
                $this->destError = (string) trans('validation.withdrawal_dest_iban_exists');

                return false;
            }
        }

        // if the account can be created anyway don't need to search.
        return true === $this->canCreateTypes($validTypes);
    }

    protected function validateWithdrawalSource(array $array): bool
    {
        $accountId     = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName   = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban   = array_key_exists('iban', $array) ? $array['iban'] : null;
        $accountNumber = array_key_exists('number', $array) ? $array['number'] : null;

        app('log')->debug('Now in validateWithdrawalSource', $array);
        // source can be any of the following types.
        $validTypes    = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && null === $accountNumber && null === $accountIban && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string) trans('validation.withdrawal_source_need_data');
            app('log')->warning('[b] Not a valid source. Need more data.');

            return false;
        }

        // otherwise try to find the account:
        $search        = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.withdrawal_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            app('log')->warning('Not a valid source. Cant find it.', $validTypes);

            return false;
        }
        $this->setSource($search);
        app('log')->debug('Valid source account!');

        return true;
    }
}
