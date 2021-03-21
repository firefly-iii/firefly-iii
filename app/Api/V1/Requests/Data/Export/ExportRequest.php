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

namespace FireflyIII\Api\V1\Requests\Data\Export;


use Carbon\Carbon;
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
    use ChecksLogin, ConvertsDataTypes;

    public function getAll(): array
    {
        $result     = [
            'start' => $this->date('start') ?? Carbon::now()->subYear(),
            'end'   => $this->date('end') ?? Carbon::now(),
            'type'  => $this->string('type'),
        ];
        $parts      = explode(',', $this->string('accounts'));
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());

        $accounts = new Collection;
        foreach ($parts as $part) {
            $accountId = (int)$part;
            if (0 !== $accountId) {
                $account = $repository->findNull($accountId);
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
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'     => 'in:csv',
            'accounts' => 'min:1',
            'start'    => 'date|before:end',
            'end'      => 'date|after:start',
        ];
    }
}