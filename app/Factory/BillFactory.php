<?php

/**
 * BillFactory.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Models\Bill;
use FireflyIII\Services\Internal\Support\BillServiceTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;
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
     * @param array $data
     *
     * @return Bill|null
     */
    public function create(array $data): ?Bill
    {
        /** @var Bill $bill */
        $bill = Bill::create(
            [
                'name'                    => $data['name'],
                'match'                   => 'MIGRATED_TO_RULES',
                'amount_min'              => $data['amount_min'],
                'user_id'                 => $this->user->id,
                'transaction_currency_id' => $data['transaction_currency_id'],
                'amount_max'              => $data['amount_max'],
                'date'                    => $data['date'],
                'repeat_freq'             => $data['repeat_freq'],
                'skip'                    => $data['skip'],
                'automatch'               => true,
                'active'                  => $data['active'],
            ]
        );

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($bill, $data['notes']);
        }

        return $bill;
    }

    /**
     * @param int|null    $billId
     * @param null|string $billName
     *
     * @return Bill|null
     */
    public function find(?int $billId, ?string $billName): ?Bill
    {
        $billId   = (int)$billId;
        $billName = (string)$billName;

        // first find by ID:
        if ($billId > 0) {
            /** @var Bill $bill */
            $bill = $this->user->bills()->find($billId);
            if (null !== $bill) {
                return $bill;
            }
        }

        // then find by name:
        if (\strlen($billName) > 0) {
            $bill = $this->findByName($billName);
            if (null !== $bill) {
                return $bill;
            }
        }

        return null;

    }

    /**
     * @param string $name
     *
     * @return Bill|null
     */
    public function findByName(string $name): ?Bill
    {
        /** @var Collection $collection */
        $collection = $this->user->bills()->get();
        /** @var Bill $bill */
        foreach ($collection as $bill) {
            Log::debug(sprintf('"%s" vs. "%s"', $bill->name, $name));
            if ($bill->name === $name) {
                return $bill;
            }
        }
        Log::debug(sprintf('Bill::Find by name returns NULL based on "%s"', $name));

        return null;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

}
