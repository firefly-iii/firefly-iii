<?php

/*
 *
 * IsValidActionExpression.php
 * Copyright (c) 2024 Michael Thomas
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

namespace FireflyIII\Rules;

use FireflyIII\TransactionRules\Expressions\ActionExpression;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IsValidActionExpression implements ValidationRule
{
    /**
     * Check that the given action expression is syntactically valid.
     *
     * @param \Closure(string): PotentiallyTranslatedString $fail
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (false === config('firefly.feature_flags.expression_engine')) {
            return;
        }
        $value ??= '';
        $expr = new ActionExpression($value);

        if (!$expr->isValid()) {
            $fail('validation.rule_action_expression')->translate(
                [
                    'error' => $expr->getValidationError()->getMessage(),
                ]
            );
        }
    }
}
