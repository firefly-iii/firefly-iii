<?php

/**
 * ReconciliationValidation.php
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

use FireflyIII\Models\AccountType;
use Log;

/**
 * Trait ReconciliationValidation
 */
trait ReconciliationValidation
{

    /**
     * @param int|null $accountId
     *
     * @return bool
     */
    protected function validateReconciliationDestination(?int $accountId): bool
    {
        Log::debug('Now in validateReconciliationDestination');
        if (null === $accountId) {
            Log::debug('Return FALSE');

            return false;
        }
        $result = $this->accountRepository->findNull($accountId);
        if (null === $result) {
            $this->destError = (string)trans('validation.deposit_dest_bad_data', ['id' => $accountId, 'name' => '']);
            Log::debug('Return FALSE');

            return false;
        }
        // types depends on type of source:
        $types = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
        // if source is reconciliation, destination can't be.
        if (null !== $this->source && AccountType::RECONCILIATION === $this->source->accountType->type) {
            $types = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
        }
        // if source is not reconciliation, destination MUST be.
        if (null !== $this->source
            && in_array(
                $this->source->accountType->type, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE], true
            )) {
            $types = [AccountType::RECONCILIATION];
        }

        if (in_array($result->accountType->type, $types, true)) {
            $this->destination = $result;
            Log::debug('Return TRUE');

            return true;
        }
        $this->destError = (string)trans('validation.deposit_dest_wrong_type');
        Log::debug('Return FALSE');

        return false;
    }

    /**
     * @param int|null $accountId
     *
     * @return bool
     */
    protected function validateReconciliationSource(?int $accountId): bool
    {
        Log::debug('In validateReconciliationSource');
        if (null === $accountId) {
            Log::debug('Return FALSE');

            return false;
        }
        $result = $this->accountRepository->findNull($accountId);
        $types  = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE, AccountType::RECONCILIATION];
        if (null === $result) {
            Log::debug('Return FALSE');

            return false;
        }
        if (in_array($result->accountType->type, $types, true)) {
            $this->source = $result;
            Log::debug('Return TRUE');

            return true;
        }
        Log::debug('Return FALSE');

        return false;
    }

}
