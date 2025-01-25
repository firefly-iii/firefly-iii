<?php

/*
 * ListRequest.php
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

namespace FireflyIII\Api\V2\Request\Model\Transaction;

use Carbon\Carbon;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetSortInstructions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class InfiniteListRequest
 * Used specifically to list transactions.
 */
class InfiniteListRequest extends FormRequest
{
    use AccountFilter;
    use ChecksLogin;
    use ConvertsDataTypes;
    use GetSortInstructions;
    use TransactionFilter;

    public function buildParams(): string
    {
        $array = [
            'start_row' => $this->getStartRow(),
            'end_row'   => $this->getEndRow(),
        ];

        $start = $this->getStartDate();
        $end   = $this->getEndDate();
        if (null !== $start && null !== $end) {
            $array['start'] = $start->format('Y-m-d');
            $array['end']   = $end->format('Y-m-d');
        }

        return http_build_query($array);
    }

    public function getStartRow(): int
    {
        $startRow = $this->convertInteger('start_row');

        return $startRow < 0 || $startRow > 4294967296 ? 0 : $startRow;
    }

    public function getEndRow(): int
    {
        $endRow = $this->convertInteger('end_row');

        return $endRow <= 0 || $endRow > 4294967296 ? 100 : $endRow;
    }

    public function getStartDate(): ?Carbon
    {
        return $this->getCarbonDate('start');
    }

    public function getEndDate(): ?Carbon
    {
        return $this->getCarbonDate('end');
    }

    public function getAccountTypes(): array
    {
        $type = (string) $this->get('type', 'default');

        return $this->mapAccountTypes($type);
    }

    public function getPage(): int
    {
        $page = $this->convertInteger('page');

        return 0 === $page || $page > 65536 ? 1 : $page;
    }

    public function getTransactionTypes(): array
    {
        $type = (string) $this->get('type', 'default');

        return $this->mapTransactionTypes($type);
    }

    public function rules(): array
    {
        return [
            'start'     => 'date|after:1900-01-01|before:2099-12-31',
            'end'       => 'date|after:start|after:1900-01-01|before:2099-12-31',
            'start_row' => 'integer|min:0|max:4294967296',
            'end_row'   => 'integer|min:0|max:4294967296|gt:start_row',
        ];
    }
}
