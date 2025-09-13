<?php

declare(strict_types=1);
/*
 * ShowRequest.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Models\Account;

use Illuminate\Validation\Validator;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Rules\IsValidSortInstruction;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\User;
use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    use AccountFilter;
    use ConvertsDataTypes;

    public function getParameters(): array
    {
        $limit = $this->convertInteger('limit');
        if (0 === $limit) {
            // get default for user:
            /** @var User $user */
            $user  = auth()->user();
            $limit = (int)Preferences::getForUser($user, 'listPageSize', 50)->data;
        }

        $page  = $this->convertInteger('page');
        $page  = min(max(1, $page), 2 ** 16);

        return [
            'type'  => $this->convertString('type', 'all'),
            'limit' => $limit,
            'sort'  => $this->convertSortParameters('sort', Account::class),
            'page'  => $page,
        ];
    }

    public function rules(): array
    {
        $keys = implode(',', array_keys($this->types));

        return [
            'date'  => 'date',
            'start' => 'date|present_with:end|before_or_equal:end|before:2038-01-17|after:1970-01-02',
            'end'   => 'date|present_with:start|after_or_equal:start|before:2038-01-17|after:1970-01-02',
            'sort'  => ['nullable', new IsValidSortInstruction(Account::class)],
            'type'  => sprintf('in:%s', $keys),
            'limit' => 'numeric|min:1|max:131337',
            'page'  => 'numeric|min:1|max:131337',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if (count($validator->failed()) > 0) {
                    return;
                }
                $data = $validator->getData();


                if (array_key_exists('date', $data) && array_key_exists('start', $data) && array_key_exists('end', $data)) {
                    // assume valid dates, before we got here.
                    $start = Carbon::parse($data['start'], config('app.timezone'))->startOfDay();
                    $end   = Carbon::parse($data['end'], config('app.timezone'))->endOfDay();
                    $date  = Carbon::parse($data['date'], config('app.timezone'));
                    if (!$date->between($start, $end)) {
                        $validator->errors()->add('date', (string)trans('validation.between_date'));
                    }
                }
            }
        );
    }
}
