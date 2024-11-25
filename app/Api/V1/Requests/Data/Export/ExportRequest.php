<?php

/*
 * ExportRequest.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Requests\Data\Export;

use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * Class ExportRequest
 */
class ExportRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getAll(): array
    {
        $result             = [
            'start' => $this->getCarbonDate('start') ?? today(config('app.timezone'))->subYear(),
            'end'   => $this->getCarbonDate('end') ?? today(config('app.timezone')),
            'type'  => $this->convertString('type'),
        ];
        $parts              = explode(',', $this->convertString('accounts'));
        $repository         = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());

        $accounts           = new Collection();
        foreach ($parts as $part) {
            $accountId = (int)$part;
            if (0 !== $accountId) {
                $account = $repository->find($accountId);
                if (null !== $account && AccountType::ASSET === $account->accountType->type) {
                    $accounts->push($account);
                }
            }
        }
        $result['accounts'] = $accounts;

        return $result;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'type'     => 'in:csv',
            'accounts' => 'min:1|max:32768',
            'start'    => 'date|before:end',
            'end'      => 'date|after:start',
        ];
    }
}
