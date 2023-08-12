<?php

/*
 * ListRequest.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Request\Transaction;

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

    /**
     * @return string
     */
    public function buildParams(): string
    {
        $array = [
            'page' => $this->getPage(),
        ];

        $start = $this->getStartDate();
        $end   = $this->getEndDate();
        if (null !== $start && null !== $end) {
            $array['start'] = $start->format('Y-m-d');
            $array['end']   = $end->format('Y-m-d');
        }
        if (0 !== $this->getLimit()) {
            $array['limit'] = $this->getLimit();
        }
        return http_build_query($array);
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        $page = $this->convertInteger('page');
        return 0 === $page || $page > 65536 ? 1 : $page;
    }

    /**
     * @return Carbon|null
     */
    public function getStartDate(): ?Carbon
    {
        return $this->getCarbonDate('start');
    }

    /**
     * @return Carbon|null
     */
    public function getEndDate(): ?Carbon
    {
        return $this->getCarbonDate('end');
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->convertInteger('limit');
    }

    /**
     * @return array
     */
    public function getTransactionTypes(): array
    {
        $type = (string)$this->get('type', 'default');
        return $this->mapTransactionTypes($type);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'start' => 'date',
            'end'   => 'date|after:start',
        ];
    }
}
