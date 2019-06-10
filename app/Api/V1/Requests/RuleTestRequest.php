<?php
/**
 * RuleTestRequest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Api\V1\Requests;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RuleTestRequest
 */
class RuleTestRequest extends Request
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
    public function getTestParameters(): array
    {
        $return = [
            'page'          => $this->getPage(),
            'start_date'    => $this->getDate('start_date'),
            'end_date'      => $this->getDate('end_date'),
            'search_limit'  => $this->getSearchLimit(),
            'trigger_limit' => $this->getTriggerLimit(),
            'accounts'      => $this->getAccounts(),
        ];


        return $return;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @param string $field
     * @return Carbon|null
     */
    private function getDate(string $field): ?Carbon
    {
        /** @var Carbon $result */
        $result = null === $this->query($field) ? null : Carbon::createFromFormat('Y-m-d', $this->query($field));

        return $result;
    }

    /**
     * @return int
     */
    private function getPage(): int
    {
        return 0 === (int)$this->query('page') ? 1 : (int)$this->query('page');

    }

    /**
     * @return int
     */
    private function getSearchLimit(): int
    {
        return 0 === (int)$this->query('search_limit') ? (int)config('firefly.test-triggers.limit') : (int)$this->query('search_limit');
    }

    /**
     * @return int
     */
    private function getTriggerLimit(): int
    {
        return 0 === (int)$this->query('triggered_limit') ? (int)config('firefly.test-triggers.range') : (int)$this->query('triggered_limit');
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
     * @param Account|null $account
     * @return bool
     */
    private function validAccount(?Account $account): bool
    {
        return null !== $account && AccountType::ASSET === $account->accountType->type;
    }

}