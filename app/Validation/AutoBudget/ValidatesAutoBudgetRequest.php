<?php

/**
 * ValidatesAutoBudgetRequest.php
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

namespace FireflyIII\Validation\AutoBudget;

use Illuminate\Validation\Validator;

/**
 * Trait ValidatesAutoBudgetRequest
 */
trait ValidatesAutoBudgetRequest
{
    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validateAutoBudgetAmount(Validator $validator): void
    {
        $data         = $validator->getData();
        $type         = $data['auto_budget_type'] ?? '';

        /** @var null|float|int|string $amount */
        $amount       = array_key_exists('auto_budget_amount', $data) ? $data['auto_budget_amount'] : null;
        $period       = array_key_exists('auto_budget_period', $data) ? $data['auto_budget_period'] : null;
        $currencyId   = array_key_exists('auto_budget_currency_id', $data) ? (int) $data['auto_budget_currency_id'] : null;
        $currencyCode = array_key_exists('auto_budget_currency_code', $data) ? $data['auto_budget_currency_code'] : null;
        if (is_numeric($type)) {
            $type = (int) $type;
        }
        if ('' === $type || 0 === $type) {
            return;
        }
        // TODO lots of duplicates with number validator.
        // TODO should be present at more places, stop scientific notification
        if (str_contains(strtoupper((string) $amount), 'E')) {
            $amount = '';
        }
        // basic float check:
        if (!is_numeric($amount)) {
            $validator->errors()->add('auto_budget_amount', (string) trans('validation.amount_required_for_auto_budget'));

            return;
        }

        if (1 !== bccomp((string) $amount, '0')) {
            $validator->errors()->add('auto_budget_amount', (string) trans('validation.auto_budget_amount_positive'));
        }
        if ('' === $period) {
            $validator->errors()->add('auto_budget_period', (string) trans('validation.auto_budget_period_mandatory'));
        }
        if (null !== $currencyId && null !== $currencyCode && '' === $currencyCode && 0 === $currencyId) {
            $validator->errors()->add('auto_budget_amount', (string) trans('validation.require_currency_info'));
        }
        // too big amount
        if ((int) $amount > 268435456) {
            $validator->errors()->add('auto_budget_amount', (string) trans('validation.amount_required_for_auto_budget'));
        }
    }
}
