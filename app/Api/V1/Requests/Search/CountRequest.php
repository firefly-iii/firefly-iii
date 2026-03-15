<?php

declare(strict_types=1);

/*
 * SearchRequest.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Search;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Rules\IsBoolean;
use Illuminate\Contracts\Validation\Validator;
use Override;

class CountRequest extends AggregateFormRequest
{
    public function rules(): array
    {
        return [
            'notes'               => 'string|min:1|max:255',
            'external_identifier' => 'string|min:1|max:255',
            'description'         => 'string|min:1|max:255',
            'internal_reference'  => 'string|min:1|max:255',
            'include_deleted'     => new IsBoolean(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (count($validator->failed()) > 0) {
                return;
            }
            $this->attributes->set('include_deleted', $this->convertBoolean($this->input('include_deleted', 'false')));
            $this->attributes->set('notes', $this->convertString('notes'));
            $this->attributes->set('external_identifier', $this->convertString('external_identifier'));
            $this->attributes->set('description', $this->convertString('description'));
            $this->attributes->set('internal_reference', $this->convertString('internal_reference'));
        });
    }

    #[Override]
    protected function getRequests(): array
    {
        return [];
    }
}
