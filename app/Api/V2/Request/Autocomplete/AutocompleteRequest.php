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

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\JsonApi\Rules\IsValidFilter;
use FireflyIII\JsonApi\Rules\IsValidPage;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

/**
 * Class AutocompleteRequest
 */
class AutocompleteRequest extends FormRequest
{
    use AccountFilter;
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Loops over all possible query parameters (these are shared over ALL auto complete requests)
     * and returns a validated array of parameters.
     *
     * The advantage is a single class. But you may also submit "account types" to an endpoint that doesn't use these.
     */
    public function getParameters(): array
    {
        $queryParameters = QueryParameters::cast($this->all());

        try {
            $date = Carbon::createFromFormat('Y-m-d', $queryParameters->filter()?->value('date', date('Y-m-d')), config('app.timezone'));
        } catch (InvalidFormatException $e) {
            Log::debug(sprintf('Invalid date format in autocomplete request. Using today: %s', $e->getMessage()));
            $date = today();
        }
        $query        = $queryParameters->filter()?->value('query', []) ?? [];
        $query        = is_string($query) ? [$query] : $query;
        $size         = (int) ($queryParameters->page()['size'] ?? 50);
        $accountTypeRequest = $queryParameters->filter()?->value('account_types', []) ?? [];
        $accountTypeRequest = is_string($accountTypeRequest) ? [$accountTypeRequest] : $accountTypeRequest;
        $accountTypes = $this->getAccountTypeParameter($accountTypeRequest);

        return [
            'date'          => $date,
            'query'         => $query,
            'size'          => $size,
            'account_types' => $accountTypes,
        ];
    }


    public function rules(): array
    {
        return [
            'fields'  => JsonApiRule::notSupported(),
            'filter'  => ['nullable', 'array', new IsValidFilter(['query', 'date', 'account_types'])],
            'include' => JsonApiRule::notSupported(),
            'page'    => ['nullable', 'array', new IsValidPage(['size'])],
            'sort'    => JsonApiRule::notSupported(),
        ];
    }

    private function getAccountTypeParameter(mixed $types): array
    {
        if (is_string($types) && str_contains($types, ',')) {
            $types = explode(',', $types);
        }
        if (!is_iterable($types)) {
            $types = [$types];
        }
        $return = [];
        foreach ($types as $type) {
            $return = array_merge($return, $this->mapAccountTypes($type));
        }

        return array_unique($return);
    }
}
