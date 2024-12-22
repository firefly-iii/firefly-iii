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

use FireflyIII\Models\Account;

/**
 * Trait ReconciliationValidation
 */
trait ReconciliationValidation
{
    public ?Account $destination;
    public ?Account $source;

    protected function validateReconciliationDestination(array $array): bool
    {
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        // if both are NULL, the destination is valid because the reconciliation
        // is expected to be "negative", i.e. the money flows towards the
        // destination to the asset account which is the source.

        if (null === $accountId && null === $accountName) {
            return true;
        }

        // after that, search for it expecting an asset account or a liability.
        app('log')->debug('Now in validateReconciliationDestination', $array);

        // source can be any of the following types.
        $validTypes  = array_keys($this->combinations[$this->transactionType]);
        $search      = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.reconciliation_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            app('log')->warning('Not a valid source. Cant find it.', $validTypes);

            return false;
        }
        $this->setSource($search);
        app('log')->debug('Valid source account!');

        return true;
    }

    /**
     * Basically the same check
     */
    protected function validateReconciliationSource(array $array): bool
    {
        $accountId   = array_key_exists('id', $array) ? $array['id'] : null;
        $accountName = array_key_exists('name', $array) ? $array['name'] : null;
        // if both are NULL, the source is valid because the reconciliation
        // is expected to be "positive", i.e. the money flows from the
        // source to the asset account that is the destination.
        if (null === $accountId && null === $accountName) {
            app('log')->debug('The source is valid because ID and name are NULL.');
            $this->setSource(new Account());

            return true;
        }

        // after that, search for it expecting an asset account or a liability.
        app('log')->debug('Now in validateReconciliationSource', $array);

        // source can be any of the following types.
        $validTypes  = array_keys($this->combinations[$this->transactionType]);
        $search      = $this->findExistingAccount($validTypes, $array);
        if (null === $search) {
            $this->sourceError = (string) trans('validation.reconciliation_source_bad_data', ['id' => $accountId, 'name' => $accountName]);
            app('log')->warning('Not a valid source. Cant find it.', $validTypes);

            return false;
        }
        $this->setSource($search);
        app('log')->debug('Valid source account!');

        return true;
    }
}
