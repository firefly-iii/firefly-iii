<?php

/*
 * Copyright (c) 2025 https://github.com/ctrl-f5
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

namespace FireflyIII\Api\V1\Requests;

use Illuminate\Validation\Validator;

class DateRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'date'  => 'date|after:1970-01-02|before:2038-01-17|'.$this->required,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $this->attributes->set('date', null);
                if (!$validator->valid()) {
                    return;
                }
                $date  = $this->getCarbonDate('date')?->endOfDay();

                // if we also have a range, date must be in that range
                $start = $this->attributes->get('start');
                $end   = $this->attributes->get('end');
                if ($date && $start && $end && !$date->between($start, $end)) {
                    $validator->errors()->add('date', (string)trans('validation.between_date'));
                }

                $this->attributes->set('date', $date);
            }
        );
    }
}
