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

use FireflyIII\Models\Account;
use Log;

/**
 * Trait WithdrawalValidation
 */
trait WithdrawalValidation
{
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
    protected function validateWithdrawalDestination(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateWithdrawalDestination(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = $this->combinations[$this->transactionType][$this->source->accountType->type] ?? [];
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL return false,
            // because the destination of a withdrawal can never be created automatically.
            $this->destError = (string) trans('validation.withdrawal_dest_need_data');

            return false;
        }

        // if there's an ID it must be of the "validTypes".
        if (null !== $accountId && 0 !== $accountId) {
            $found = $this->accountRepository->findNull($accountId);
            if (null !== $found) {
                $type = $found->accountType->type;
                if (in_array($type, $validTypes, true)) {
                    return true;
                }
                $this->destError = (string) trans('validation.withdrawal_dest_bad_data', ['id' => $accountId, 'name' => $accountName]);

                return false;
            }
        }

        // if the account can be created anyway don't need to search.
        return true === $this->canCreateTypes($validTypes);
    }

    /**
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return bool
     */
    protected function validateWithdrawalSource(?int $accountId, ?string $accountName): bool
    {
        Log::debug(sprintf('Now in validateWithdrawalSource(%d, "%s")', $accountId, $accountName));
        // source can be any of the following types.
        $validTypes = array_keys($this->combinations[$this->transactionType]);
        if (null === $accountId && null === $accountName && false === $this->canCreateTypes($validTypes)) {
            // if both values are NULL we return false,
            // because the source of a withdrawal can't be created.
            $this->sourceError = (string) trans('validation.withdrawal_source_need_data');
            Log::warning('Not a valid source. Need more data.');

            return false;
        }

        // otherwise try to find the account:
        $search = $this->findExistingAccount($validTypes, (int) $accountId, (string) $accountName);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.withdrawal_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            Log::warning('Not a valid source. Cant find it.', $validTypes);

            return false;
        }
        $this->source = $search;
        Log::debug('Valid source account!');

        return true;
    }
}