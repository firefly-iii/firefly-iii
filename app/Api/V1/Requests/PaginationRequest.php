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

use FireflyIII\Rules\IsValidSortInstruction;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Contracts\Validation\Validator;
use Override;
use RuntimeException;

class PaginationRequest extends ApiRequest
{
    private ?string $sortClass = null;

    #[Override]
    public function handleConfig(array $config): void
    {
        parent::handleConfig($config);

        $this->sortClass = $config['sort_class'] ?? null;

        if (!$this->sortClass) {
            throw new RuntimeException('PaginationRequest requires a sort_class config');
        }
    }

    public function rules(): array
    {
        return [
            'sort'  => ['nullable', new IsValidSortInstruction((string) $this->sortClass)],
            'limit' => 'numeric|min:1|max:131337',
            'page'  => 'numeric|min:1|max:131337',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->failed()) {
                return;
            }

            $limit  = $this->convertInteger('limit');
            if (0 === $limit) {
                // get default for user:
                /** @var User $user */
                $user  = auth()->user();
                $limit = (int) Preferences::getForUser($user, 'listPageSize', 50)->data;
            }
            $page   = $this->convertInteger('page');
            $page   = min(max(1, $page), 2 ** 16);
            $offset = ($page - 1) * $limit;
            $sort   = $this->sortClass ? $this->convertSortParameters('sort', $this->sortClass) : $this->get('sort');
            $this->attributes->set('limit', $limit);
            $this->attributes->set('sort', $sort);
            $this->attributes->set('page', $page);
            $this->attributes->set('offset', $offset);
        });
    }
}
