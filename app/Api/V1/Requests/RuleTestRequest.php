<?php

/**
 * RuleTestRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
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


use Carbon\Carbon;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RuleTestRequest
 */
class RuleTestRequest extends FormRequest
{
    use ConvertsDataTypes;

    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getTestParameters(): array
    {
        return [
            'page'     => $this->getPage(),
            'start'    => $this->getDate('start'),
            'end'      => $this->getDate('end'),
            'accounts' => $this->getAccounts(),

        ];
    }

    /**
     * @return int
     */
    private function getPage(): int
    {
        return 0 === (int) $this->query('page') ? 1 : (int) $this->query('page');

    }

    /**
     * @param string $field
     *
     * @return Carbon|null
     */
    private function getDate(string $field): ?Carbon
    {
        /** @var Carbon $result */
        $result = null === $this->query($field) ? null : Carbon::createFromFormat('Y-m-d', $this->query($field));

        return $result;
    }

    /**
     * @return string
     */
    private function getAccounts(): string
    {
        return (string) $this->query('accounts');
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'start'      => 'date',
            'end'        => 'date|after:start',
            'accounts'   => '',
            'accounts.*' => 'required|exists:accounts,id|belongsToUser:accounts',
        ];
    }

}
