<?php
/*
 * AutocompleteRequest.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Request\Autocomplete;

use FireflyIII\Models\AccountType;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\ParsesQueryFilters;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AutocompleteRequest
 */
class AutocompleteRequest extends FormRequest
{
    use AccountFilter;
    use ChecksLogin;
    use ConvertsDataTypes;
    use ParsesQueryFilters;

    /**
     * Loops over all possible query parameters (these are shared over ALL auto complete requests)
     * and returns a validated array of parameters.
     *
     * The advantage is a single class. But you may also submit "account types" to an endpoint that doesn't use these.
     */
    public function getParameters(): array
    {
        $array = [
            'date'              => $this->convertDateTime('date'),
            'query'             => $this->clearString((string) $this->get('query')),
            'size'              => $this->integerFromValue('size'),
            'page'              => $this->integerFromValue('page'),
            'account_types'     => $this->arrayFromValue($this->get('account_types')),
            'transaction_types' => $this->arrayFromValue($this->get('transaction_types')),
        ];
        $array['size'] = $array['size'] < 1 || $array['size'] > 100 ? 15 : $array['size'];
        $array['page'] = max($array['page'], 1);
        if (null === $array['account_types']) {
            $array['account_types'] = [];
        }
        if (null === $array['transaction_types']) {
            $array['transaction_types'] = [];
        }

        // remove 'initial balance' from allowed types. its internal
        $array['account_types'] = array_diff($array['account_types'], [AccountType::INITIAL_BALANCE, AccountType::RECONCILIATION, AccountType::CREDITCARD]);
        $array['account_types'] = $this->getAccountTypeParameter($array['account_types']);
        return $array;
    }

    public function rules(): array
    {
        $valid = array_keys($this->types);
        return [
            'date'              => 'nullable|date|after:1900-01-01|before:2100-01-01',
            'query'             => 'nullable|string',
            'size'              => 'nullable|integer|min:1|max:100',
            'page'              => 'nullable|integer|min:1',
            'account_types'     => sprintf('nullable|in:%s', join(',', $valid)),
            'transaction_types' => 'nullable|in:todo',
        ];
    }

    private function getAccountTypeParameter(array $types): array
    {
        $return = [];
        foreach ($types as $type) {
            $return = array_merge($return, $this->mapAccountTypes($type));
        }

        return array_unique($return);
    }
}
