<?php

declare(strict_types=1);
/*
 * AutocompleteApiRequest.php
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

namespace FireflyIII\Api\V1\Requests\Autocomplete;

use FireflyIII\Api\V1\Requests\AggregateFormRequest;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Api\V1\Requests\Generic\ObjectTypeApiRequest;
use FireflyIII\Api\V1\Requests\Generic\QueryRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Override;

class AutocompleteTransactionApiRequest extends AggregateFormRequest
{
    #[Override]
    protected function getRequests(): array
    {
        return [
            DateRequest::class,
            [PaginationRequest::class, 'sort_class' => Account::class],
            [ObjectTypeApiRequest::class, 'object_type' => Transaction::class],
            QueryRequest::class,
        ];
    }
}
