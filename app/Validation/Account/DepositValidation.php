<?php
/**
 * DepositValidation.php
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
 * Trait DepositValidation
 */
trait DepositValidation
{
    /**
     * @param array $accountTypes
     *
     * @return bool
     */
    abstract protected function canCreateTypes(array $accountTypes): bool;

    /**
     * @param array $validTypes
     * @param array $data
     *
     * @return Account|null
     */
    abstract protected function findExistingAccount(array $validTypes, array $data): ?Account;

    /**
     * @param array $array
     *
     * @return bool
     */
    protected function validateDepositDestination(array $array): bool
    {
        $result      = null;
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;

        Log::debug('Now in validateDepositDestination', $array);

        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a deposit can't be created.
            $this->destError = (string) trans('validation.deposit_dest_need_data');
            Log::error('Both values are NULL, cant create deposit destination.');
            $result = false;
        }
        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            Log::debug('Can create some of these types, so return true.');
            $result = true;
        }

        if (null === $result) {
            // otherwise try to find the account:
            $search = $this->findExistingAccount($validTypes, $array);
            if (null === $search) {
                Log::debug('findExistingAccount() returned NULL, so the result is false.');
                $this->destError = (string) trans('validation.deposit_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);
                $result          = false;
            }
            if (null !== $search) {
                Log::debug(sprintf('findExistingAccount() returned #%d ("%s"), so the result is true.', $search->id, $search->name));
                $this->destination = $search;
                $result            = true;
            }
        }
        $result = $result ?? false;
        Log::debug(sprintf('validateDepositDestination will return %s', var_export($result, true)));

        return $result;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    protected function validateDepositSource(array $array): bool
    {
        $accountId     = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName   = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban   = array_key_exists('iban', $array) ? $array['iban'] : null;
        $accountNumber = array_key_exists('number', $array) ? $array['number'] : null;
        Log::debug('Now in validateDepositSource', $array);
        $result = null;
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the source of a deposit can't be created.
            // (this never happens).
            $this->sourceError = (string) trans('validation.deposit_source_need_data');
            $result            = false;
        }

        // if the user submits an ID, but that ID is not of the correct type,
        // return false.
        if (null !== $accountId) {
            $search = $this->accountRepository->find($accountId);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                Log::debug(sprintf('User submitted an ID (#%d), which is a "%s", so this is not a valid source.', $accountId, $search->accountType->type));
                $result = false;
            }
        }

        // if user submits an IBAN:
        if (null !== $accountIban) {
            $search = $this->accountRepository->findByIbanNull($accountIban, $validTypes);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                Log::debug(sprintf('User submitted IBAN ("%s"), which is a "%s", so this is not a valid source.', $accountIban, $search->accountType->type));
                $result = false;
            }
        }

        // if user submits a number:
        if (null !== $accountNumber) {
            $search = $this->accountRepository->findByAccountNumber($accountNumber, $validTypes);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                Log::debug(
                    sprintf('User submitted number ("%s"), which is a "%s", so this is not a valid source.', $accountNumber, $search->accountType->type)
                );
                $result = false;
            }
        }

        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            $result = true;

            // set the source to be a (dummy) revenue account.
            $account              = new Account;
            $accountType          = AccountType::whereType(AccountType::REVENUE)->first();
            $account->accountType = $accountType;
            $this->source         = $account;
        }

        return $result ?? false;
    }
}
