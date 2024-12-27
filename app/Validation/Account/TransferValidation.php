<?php

/**
 * TransferValidation.php
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

/**
 * Trait TransferValidation
 */
trait TransferValidation
{
    protected function validateTransferDestination(array $array): bool
    {
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban = array_key_exists('iban', $array) ? $array['iban'] : null;
        app('log')->debug('Now in validateTransferDestination', $array);
        // source can be any of the following types.
        $validTypes  = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && null === $accountIban && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a transfer can't be created.
            $this->destError = (string) trans('validation.transfer_dest_need_data');
            app('log')->error('Both values are NULL, cant create transfer destination.');

            return false;
        }

        // or try to find the account:
        $search      = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->destError = (string) trans('validation.transfer_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);

            return false;
        }
        $this->setDestination($search);

        // must not be the same as the source account
        if (null !== $this->source && $this->source->id === $this->destination->id) {
            $this->sourceError = 'Source and destination are the same.';
            $this->destError   = 'Source and destination are the same.';

            return false;
        }

        return true;
    }

    abstract protected function canCreateTypes(array $accountTypes): bool;

    abstract protected function findExistingAccount(array $validTypes, array $data): ?Account;

    protected function validateTransferSource(array $array): bool
    {
        $accountId     = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName   = array_key_exists('name', $array) ? $array['name'] : null;
        $accountIban   = array_key_exists('iban', $array) ? $array['iban'] : null;
        $accountNumber = array_key_exists('number', $array) ? $array['number'] : null;
        app('log')->debug('Now in validateTransferSource', $array);
        // source can be any of the following types.
        $validTypes    = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName
            && null === $accountIban && null === $accountNumber
            && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string) trans('validation.transfer_source_need_data');
            app('log')->warning('Not a valid source, need more data.');

            return false;
        }

        // otherwise try to find the account:
        $search        = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.transfer_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            app('log')->warning('Not a valid source, cant find it.', $validTypes);

            return false;
        }
        $this->setSource($search);
        app('log')->debug('Valid source!');

        return true;
    }
}
