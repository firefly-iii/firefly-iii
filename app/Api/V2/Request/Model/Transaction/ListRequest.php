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
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ListRequest
 * Used specifically to list transactions.
 */
class ListRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;
    use TransactionFilter;

    public function buildParams(int $pageSize): string
    {
        $array = [
            'page'  => $this->getPage(),
            'limit' => $pageSize,
        ];

        $start = $this->getStartDate();
        $end   = $this->getEndDate();
        if ($start instanceof Carbon && $end instanceof Carbon) {
            $array['start'] = $start->format('Y-m-d');
            $array['end']   = $end->format('Y-m-d');
        }

        return http_build_query($array);
    }

    public function getPage(): int
    {
        $page = $this->convertInteger('page');

        return 0 === $page || $page > 65536 ? 1 : $page;
    }

    public function getStartDate(): ?Carbon
    {
        return $this->getCarbonDate('start');
    }

    public function getEndDate(): ?Carbon
    {
        return $this->getCarbonDate('end');
    }

    public function getTransactionTypes(): array
    {
        $type = (string) $this->get('type', 'default');

        return $this->mapTransactionTypes($type);
    }

    public function rules(): array
    {
        return [
            'start' => 'date|after:1900-01-01|before:2099-12-31',
            'end'   => 'date|after:start|after:1900-01-01|before:2099-12-31',
        ];
    }
}
