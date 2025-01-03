<?php

/*
 * IsValidBulkClause.php
 * Copyright (c) 2021 james@firefly-iii.org
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

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * Class IsValidBulkClause
 */
class IsValidBulkClause implements ValidationRule
{
    private string $error;
    private array  $rules;

    public function __construct(string $type)
    {
        $this->rules = config(sprintf('bulk.%s', $type));
        $this->error = (string) trans('firefly.belongs_user');
    }

    public function message(): string
    {
        return $this->error;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $result = $this->basicValidation((string) $value);
        if (false === $result) {
            $fail($this->error);
        }
    }

    /**
     * Does basic rule based validation.
     */
    private function basicValidation(string $value): bool
    {
        try {
            $array = json_decode($value, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error = (string) trans('validation.json');

            return false;
        }
        $clauses = ['where', 'update'];
        foreach ($clauses as $clause) {
            if (!array_key_exists($clause, $array)) {
                $this->error = (string) trans(sprintf('validation.missing_%s', $clause));

                return false;
            }

            /**
             * @var string $arrayKey
             * @var mixed  $arrayValue
             */
            foreach ($array[$clause] as $arrayKey => $arrayValue) {
                if (!array_key_exists($arrayKey, $this->rules[$clause])) {
                    $this->error = (string) trans(sprintf('validation.invalid_%s_key', $clause));

                    return false;
                }
                // validate!
                $validator = Validator::make(['value' => $arrayValue], [
                    'value' => $this->rules[$clause][$arrayKey],
                ]);
                if ($validator->fails()) {
                    $this->error = sprintf('%s: %s: %s', $clause, $arrayKey, implode(', ', $validator->errors()->get('value')));

                    return false;
                }
            }
        }

        return true;
    }
}
