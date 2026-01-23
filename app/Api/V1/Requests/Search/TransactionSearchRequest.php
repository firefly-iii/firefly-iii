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
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\TransactionJournal;
use Override;

class TransactionSearchRequest extends AggregateFormRequest
{
    #[Override]
    protected function getRequests(): array
    {
        return [
            [PaginationRequest::class, 'sort_class' => TransactionJournal::class],
            SearchQueryRequest::class
            // [ObjectTypeApiRequest::class, 'object_type' => Account::class],
        ];
    }
}
