<?php

/**
 * BillFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Support\BillServiceTrait;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class BillFactory
 */
class BillFactory
{
    use BillServiceTrait;

    /** @var User */
    private $user;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param array $data
     *
     * @return Bill|null
     * @throws FireflyException
     */
    public function create(array $data): ?Bill
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find((int)($data['currency_id'] ?? null), (string)($data['currency_code'] ?? null));

        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }
        try {
            /** @var Bill $bill */
            $bill = Bill::create(
                [
                    'name'                    => $data['name'],
                    'match'                   => 'MIGRATED_TO_RULES',
                    'amount_min'              => $data['amount_min'],
                    'user_id'                 => $this->user->id,
                    'transaction_currency_id' => $currency->id,
                    'amount_max'              => $data['amount_max'],
                    'date'                    => $data['date'],
                    'repeat_freq'             => $data['repeat_freq'],
                    'skip'                    => $data['skip'],
                    'automatch'               => true,
                    'active'                  => $data['active'] ?? true,
                ]
            );
        } catch(QueryException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException('400000: Could not store bill.');
        }

        if (isset($data['notes'])) {
            $this->updateNote($bill, $data['notes']);
        }

        return $bill;
    }

    /**
     * @param int|null $billId
     * @param null|string $billName
     *
     * @return Bill|null
     */
    public function find(?int $billId, ?string $billName): ?Bill
    {
        $billId   = (int)$billId;
        $billName = (string)$billName;
        $bill     = null;
        // first find by ID:
        if ($billId > 0) {
            /** @var Bill $bill */
            $bill = $this->user->bills()->find($billId);
        }

        // then find by name:
        if (null === $bill && '' !== $billName) {
            $bill = $this->findByName($billName);
        }

        return $bill;

    }

    /**
     * @param string $name
     *
     * @return Bill|null
     */
    public function findByName(string $name): ?Bill
    {
        $query = sprintf('%%%s%%', $name);
        /** @var Bill $first */
        $first = $this->user->bills()->where('name', 'LIKE', $query)->first();

        return $first;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
