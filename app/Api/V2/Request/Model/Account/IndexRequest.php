<?php

/*
 * IndexRequest.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V2\Request\Model\Account;

use Carbon\Carbon;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use FireflyIII\Support\Request\GetFilterInstructions;
use FireflyIII\Support\Request\GetSortInstructions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexRequest
 *
 * Lots of code stolen from the SingleDateRequest.
 */
class IndexRequest extends FormRequest
{
    use AccountFilter;
    use ChecksLogin;
    use ConvertsDataTypes;
    use GetFilterInstructions;
    use GetSortInstructions;

    public function getAccountTypes(): array
    {
        $type = (string) $this->get('type', 'default');

        return $this->mapAccountTypes($type);
    }

    public function getDate(): Carbon
    {
        return $this->getCarbonDate('date');
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'date'  => 'date|after:1970-01-02|before:2038-01-17',
            'start' => 'date|after:1970-01-02|before:2038-01-17|before:end|required_with:end',
            'end'   => 'date|after:1970-01-02|before:2038-01-17|after:start|required_with:start',
        ];
    }
}
