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

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;
use RuntimeException;

abstract class AggregateFormRequest extends ApiRequest
{
    /**
     * @var ApiRequest[]
     */
    protected array $requests = [];

    /** @return array<array|string> */
    abstract protected function getRequests(): array;

    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): void
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);

        // instantiate all subrequests and share current requests' bags with them
        Log::debug('Initializing AggregateFormRequest.');

        /** @var array|string $config */
        foreach ($this->getRequests() as $config) {
            $requestClass         = is_array($config) ? array_shift($config) : $config;

            if (!is_a($requestClass, Request::class, true)) {
                throw new RuntimeException('getRequests() must return class-strings of subclasses of Request');
            }
            Log::debug(sprintf('Initializing subrequest %s', $requestClass));

            $instance             = $this->requests[] = new $requestClass();
            $instance->request    = $this->request;
            $instance->query      = $this->query;
            $instance->attributes = $this->attributes;
            $instance->cookies    = $this->cookies;
            $instance->files      = $this->files;
            $instance->server     = $this->server;
            $instance->headers    = $this->headers;

            if ($instance instanceof ApiRequest) {
                $instance->handleConfig(is_array($config) ? $config : []);
            }
        }
        Log::debug('Done initializing AggregateFormRequest.');
    }

    public function rules(): array
    {
        // check all subrequests for rules and combine them
        return array_reduce(
            $this->requests,
            static fn (array $rules, FormRequest $request) => $rules
                + (
                    method_exists($request, 'rules')
                    ? $request->rules()
                    : []
                ),
            [],
        );
    }

    public function withValidator(Validator $validator): void
    {
        // register all subrequests' validators
        foreach ($this->requests as $request) {
            if (method_exists($request, 'withValidator')) {
                Log::debug(sprintf('Process withValidator from class %s', get_class($request)));
                $request->withValidator($validator);
            }
        }
    }
}
