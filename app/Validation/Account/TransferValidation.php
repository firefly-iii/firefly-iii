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
use Log;

/**
 * Trait TransferValidation
 */
trait TransferValidation
{
    /**
     * @param int|null $accountId
     * @param          $accountName
     *
     * @return bool
     */
    protected function validateTransferDestination(?int $accountId, $accountName): bool
    {
        Log::debug(sprintf('Now in validateTransferDestination(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the destination of a transfer can't be created.
            $this->destError = (string)trans('validation.transfer_dest_need_data');
            Log::error('Both values are NULL, cant create transfer destination.');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
        if (null === $search) {
            $this->destError = (string)trans('validation.transfer_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);

            return false;
        }
        $this->destination = $search;

        // must not be the same as the source account
        if (null !== $this->source && $this->source->id === $this->destination->id) {
            $this->sourceError = 'Source and destination are the same.';
            $this->destError   = 'Source and destination are the same.';

            return false;
        }

        return true;
    }

    /**
     * @param array $accountTypes
     *
     * @return bool
     */
    abstract protected function canCreateTypes(array $accountTypes): bool;

    /**
     * @param array  $validTypes
     * @param int    $accountId
     * @param string $accountName
     *
     * @return Account|null
     */
    abstract protected function findExistingAccount(array $validTypes, int $accountId, string $accountName): ?Account;

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    protected function validateTransferSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateTransferSource(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string)trans('validation.transfer_source_need_data');
            Log::warning('Not a valid source, need more data.');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int)$accountId, (string)$accountName);
        if (null === $search) {
            $this->sourceError = (string)trans('validation.transfer_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            Log::warning('Not a valid source, cant find it.', $validTypes);

            return false;
        }
        $this->source = $search;
        Log::debug('Valid source!');

        return true;
    }
}
