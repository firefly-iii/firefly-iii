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

class DateRangeRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'start' => sprintf('date|after:1970-01-02|before:2038-01-17|before:end|required_with:end|%s', $this->required),
            'end'   => sprintf('date|after:1970-01-02|before:2038-01-17|after:start|required_with:start|%s', $this->required),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if (!$validator->valid()) {
                    // set null values
                    $this->attributes->set('start', null);
                    $this->attributes->set('end', null);
                    return;
                }
                $start = $this->getCarbonDate('start')?->startOfDay();
                $end   = $this->getCarbonDate('end')?->endOfDay();

                $this->attributes->set('start', $start);
                $this->attributes->set('end', $end);
            }
        );
    }
}
