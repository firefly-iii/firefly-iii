<?php

/**
 * RuleTriggerRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RuleTriggerRequest
 */
class RuleTriggerRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getTriggerParameters(): array
    {
        $return = [
            'start_date' => $this->getDate('start_date'),
            'end_date'   => $this->getDate('end_date'),
            'accounts'   => $this->getAccounts(),
        ];


        return $return;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ];
    }

    /**
     * @return Collection
     */
    private function getAccounts(): Collection
    {
        $accountList = '' === (string)$this->query('accounts') ? [] : explode(',', $this->query('accounts'));
        $accounts    = new Collection;

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        foreach ($accountList as $accountId) {
            Log::debug(sprintf('Searching for asset account with id "%s"', $accountId));
            $account = $accountRepository->findNull((int)$accountId);
            if ($this->validAccount($account)) {
                /** @noinspection NullPointerExceptionInspection */
                Log::debug(sprintf('Found account #%d ("%s") and its an asset account', $account->id, $account->name));
                $accounts->push($account);
            }
        }

        return $accounts;
    }

    /**
     * @param string $field
     *
     * @return Carbon|null
     */
    private function getDate(string $field): ?Carbon
    {
        /** @var Carbon $result */
        $result = null === $this->query($field) ? null : Carbon::createFromFormat('Y-m-d', $this->query($field));

        return $result;
    }

    /**
     * @param Account|null $account
     *
     * @return bool
     */
    private function validAccount(?Account $account): bool
    {
        return null !== $account && AccountType::ASSET === $account->accountType->type;
    }

}
