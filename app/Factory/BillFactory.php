<?php

/**
 * BillFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Services\Internal\Support\BillServiceTrait;
use FireflyIII\User;
use Illuminate\Database\QueryException;

/**
 * Class BillFactory
 */
class BillFactory
{
    use BillServiceTrait;
    use CreatesObjectGroups;

    private User $user;

    /**
     * @throws FireflyException
     */
    public function create(array $data): ?Bill
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__), $data);
        $factory          = app(TransactionCurrencyFactory::class);
        $currency         = $factory->find((int) ($data['currency_id'] ?? null), (string) ($data['currency_code'] ?? null))
                    ?? app('amount')->getNativeCurrencyByUserGroup($this->user->userGroup);

        try {
            $skip   = array_key_exists('skip', $data) ? $data['skip'] : 0;
            $active = array_key_exists('active', $data) ? $data['active'] : 0;

            /** @var Bill $bill */
            $bill   = Bill::create(
                [
                    'name'                    => $data['name'],
                    'match'                   => 'MIGRATED_TO_RULES',
                    'amount_min'              => $data['amount_min'],
                    'user_id'                 => $this->user->id,
                    'user_group_id'           => $this->user->user_group_id,
                    'transaction_currency_id' => $currency->id,
                    'amount_max'              => $data['amount_max'],
                    'date'                    => $data['date'],
                    'date_tz'                 => $data['date']->format('e'),
                    'end_date'                => $data['end_date'] ?? null,
                    'end_date_tz'             => $data['end_date']?->format('e'),
                    'extension_date'          => $data['extension_date'] ?? null,
                    'extension_date_tz'       => $data['extension_date']?->format('e'),
                    'repeat_freq'             => $data['repeat_freq'],
                    'skip'                    => $skip,
                    'automatch'               => true,
                    'active'                  => $active,
                ]
            );
        } catch (QueryException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException('400000: Could not store bill.', 0, $e);
        }

        if (array_key_exists('notes', $data)) {
            $this->updateNote($bill, (string) $data['notes']);
        }
        $objectGroupTitle = $data['object_group_title'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if (null !== $objectGroup) {
                $bill->objectGroups()->sync([$objectGroup->id]);
                $bill->save();
            }
        }
        // try also with ID:
        $objectGroupId    = (int) ($data['object_group_id'] ?? 0);
        if (0 !== $objectGroupId) {
            $objectGroup = $this->findObjectGroupById($objectGroupId);
            if (null !== $objectGroup) {
                $bill->objectGroups()->sync([$objectGroup->id]);
                $bill->save();
            }
        }

        return $bill;
    }

    public function find(?int $billId, ?string $billName): ?Bill
    {
        $billId   = (int) $billId;
        $billName = (string) $billName;
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

    public function findByName(string $name): ?Bill
    {
        /** @var null|Bill */
        return $this->user->bills()->whereLike('name', sprintf('%%%s%%', $name))->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
