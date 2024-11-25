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

/**
 * Trait DepositValidation
 */
trait DepositValidation
{
    protected function validateDepositDestination(array $array): bool
    {
        $result      = null;
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban = array_key_exists('iban', $array) ? $array['iban'] : null;

        app('log')->debug('Now in validateDepositDestination', $array);

        // source can be any of the following types.
        $validTypes  = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && null === $accountIban && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a deposit can't be created.
            $this->destError = (string)trans('validation.deposit_dest_need_data');
            app('log')->error('Both values are NULL, cant create deposit destination.');
            $result          = false;
        }
        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            app('log')->debug('Can create some of these types, so return true.');
            $result = true;
        }

        if (null === $result) {
            // otherwise try to find the account:
            $search = $this->findExistingAccount($validTypes, $array);
            if (null === $search) {
                app('log')->debug('findExistingAccount() returned NULL, so the result is false.');
                $this->destError = (string)trans('validation.deposit_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);
                $result          = false;
            }
            if (null !== $search) {
                app('log')->debug(sprintf('findExistingAccount() returned #%d ("%s"), so the result is true.', $search->id, $search->name));
                $this->setDestination($search);
                $result = true;
            }
        }
        app('log')->debug(sprintf('validateDepositDestination will return %s', var_export($result, true)));

        return $result;
    }

    abstract protected function canCreateTypes(array $accountTypes): bool;

    abstract protected function findExistingAccount(array $validTypes, array $data): ?Account;

    /**
     * Pretty complex unfortunately.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateDepositSource(array $array): bool
    {
        $accountId     = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName   = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban   = array_key_exists('iban', $array) ? $array['iban'] : null;
        $accountNumber = array_key_exists('number', $array) ? $array['number'] : null;
        app('log')->debug('Now in validateDepositSource', $array);

        // null = we found nothing at all or didn't even search
        // false = invalid results
        $result        = null;

        // source can be any of the following types.
        $validTypes    = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId
            && null === $accountName
            && null === $accountIban
            && null === $accountNumber
            && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the source of a deposit can't be created.
            // (this never happens).
            $this->sourceError = (string)trans('validation.deposit_source_need_data');
            $result            = false;
        }

        // if there is an iban, it can only be in use by a valid source type, or we will fail.
        if (null !== $accountIban && '' !== $accountIban) {
            app('log')->debug('Check if there is not already another account with this IBAN');
            $existing = $this->findExistingAccount($validTypes, ['iban' => $accountIban], true);
            if (null !== $existing) {
                $this->sourceError = (string)trans('validation.deposit_src_iban_exists');

                return false;
            }
        }

        // if the user submits an ID, but that ID is not of the correct type,
        // return false.
        if (null !== $accountId) {
            $search = $this->getRepository()->find($accountId);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug(sprintf('User submitted an ID (#%d), which is a "%s", so this is not a valid source.', $accountId, $search->accountType->type));
                app('log')->debug(sprintf('Firefly III accepts ID #%d as valid account data.', $accountId));
            }
            if (null !== $search && in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug('ID result is not null and seems valid, save as source account.');
                $this->setSource($search);
                $result = true;
            }
        }

        // if user submits an IBAN:
        if (null !== $accountIban) {
            $search = $this->getRepository()->findByIbanNull($accountIban, $validTypes);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug(sprintf('User submitted IBAN ("%s"), which is a "%s", so this is not a valid source.', $accountIban, $search->accountType->type));
                $result = false;
            }
            if (null !== $search && in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug('IBAN result is not null and seems valid, save as source account.');
                $this->setSource($search);
                $result = true;
            }
        }

        // if user submits a number:
        if (null !== $accountNumber && '' !== $accountNumber) {
            $search = $this->getRepository()->findByAccountNumber($accountNumber, $validTypes);
            if (null !== $search && !in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug(
                    sprintf('User submitted number ("%s"), which is a "%s", so this is not a valid source.', $accountNumber, $search->accountType->type)
                );
                $result = false;
            }
            if (null !== $search && in_array($search->accountType->type, $validTypes, true)) {
                app('log')->debug('Number result is not null and seems valid, save as source account.');
                $this->setSource($search);
                $result = true;
            }
        }

        // if the account can be created anyway we don't need to search.
        if (null === $result && true === $this->canCreateTypes($validTypes)) {
            $result               = true;

            // set the source to be a (dummy) revenue account.
            $account              = new Account();
            $accountType          = AccountType::whereType(AccountType::REVENUE)->first();
            $account->accountType = $accountType;
            $this->setSource($account);
        }

        return $result ?? false;
    }
}
