<?php

/**
 * PiggyBankFactory.php
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
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\QueryException;

/**
 * Class PiggyBankFactory
 */
class PiggyBankFactory
{
    private User $user;
    private CurrencyRepositoryInterface $currencyRepository;
    private AccountRepositoryInterface $accountRepository;
    private PiggyBankRepositoryInterface $piggyBankRepository;

    /**
     * Store a piggy bank or come back with an exception.
     *
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank {
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->piggyBankRepository = app(PiggyBankRepositoryInterface::class);
        $this->currencyRepository->setUser($this->user);
        $this->accountRepository->setUser($this->user);
        $this->piggyBankRepository->setUser($this->user);
        $piggyBankData                  =$data;

        // unset some fields
        unset($piggyBankData['object_group_title'],$piggyBankData['transaction_currency_code'],$piggyBankData['transaction_currency_id'],$piggyBankData['accounts'], $piggyBankData['object_group_id'], $piggyBankData['notes']);

        // validate amount:
        if (array_key_exists('target_amount', $piggyBankData) && '' === (string)$piggyBankData['target_amount']) {
            $piggyBankData['target_amount'] = '0';
        }

        $piggyBankData['start_date_tz']  = $piggyBankData['start_date']?->format('e');
        $piggyBankData['target_date_tz'] = $piggyBankData['target_date']?->format('e');
        $piggyBankData['account_id'] = null;
        $piggyBankData['transaction_currency_id'] = $this->getCurrency($data)->id;
        $piggyBankData['order'] = 131337;

        try {
            /** @var PiggyBank $piggyBank */
            $piggyBank = PiggyBank::create($piggyBankData);
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not store piggy bank: %s', $e->getMessage()), $piggyBankData);

            throw new FireflyException('400005: Could not store new piggy bank.', 0, $e);
        }
        $piggyBank = $this->setOrder($piggyBank, $data);
        $this->linkToAccountIds($piggyBank, $data['accounts']);
        $this->piggyBankRepository->updateNote($piggyBank, $data['notes']);

        $objectGroupTitle           = $data['object_group_title'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if (null !== $objectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
                $piggyBank->save();
            }
        }
        // try also with ID
        $objectGroupId              = (int)($data['object_group_id'] ?? 0);
        if (0 !== $objectGroupId) {
            $objectGroup = $this->findObjectGroupById($objectGroupId);
            if (null !== $objectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
                $piggyBank->save();
            }
        }

        return $piggyBank;
    }

    public function find(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank
    {
        $piggyBankId   = (int)$piggyBankId;
        $piggyBankName = (string)$piggyBankName;
        if ('' === $piggyBankName && 0 === $piggyBankId) {
            return null;
        }
        // first find by ID:
        if ($piggyBankId > 0) {
            /** @var null|PiggyBank $piggyBank */
            $piggyBank = $this->user->piggyBanks()->find($piggyBankId);
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        // then find by name:
        if ('' !== $piggyBankName) {
            /** @var null|PiggyBank $piggyBank */
            $piggyBank = $this->findByName($piggyBankName);
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        return null;
    }

    public function findByName(string $name): ?PiggyBank
    {
        return $this->user->piggyBanks()->where('piggy_banks.name', $name)->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    private function getCurrency(array $data): TransactionCurrency {
        // currency:
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $currency        = null;
        if (array_key_exists('transaction_currency_code', $data)) {
            $currency = $this->currencyRepository->findByCode((string)($data['transaction_currency_code'] ?? ''));
        }
        if (array_key_exists('transaction_currency_id', $data)) {
            $currency = $this->currencyRepository->find((int)($data['transaction_currency_id'] ?? 0));
        }
        $currency ??= $defaultCurrency;
        return $currency;
    }

    private function setOrder(PiggyBank $piggyBank, array $data): PiggyBank {
        $this->resetOrder();
        $order                      = $this->getMaxOrder() + 1;
        if (array_key_exists('order', $data)) {
            $order = $data['order'];
        }
        $piggyBank->order = $order;
        $piggyBank->save();
        return $piggyBank;

    }

    private function resetOrder(): void
    {
        $set     = $this->user->piggyBanks()->orderBy('piggy_banks.order', 'ASC')->get(['piggy_banks.*']);
        $current = 1;
        foreach ($set as $piggyBank) {
            if ($piggyBank->order !== $current) {
                app('log')->debug(sprintf('Piggy bank #%d ("%s") was at place %d but should be on %d', $piggyBank->id, $piggyBank->name, $piggyBank->order, $current));
                $piggyBank->order = $current;
                $piggyBank->save();
            }
            ++$current;
        }
    }


    private function getMaxOrder(): int
    {
        return (int)$this->user->piggyBanks()->max('piggy_banks.order');
    }

    private function linkToAccountIds(PiggyBank $piggyBank, array $accounts): void {
        /** @var array $info */
        foreach($accounts as $info) {
            $account = $this->accountRepository->find((int)($info['account_id'] ?? 0));
            if(null === $account) {
                continue;
            }
            $piggyBank->accounts()->syncWithoutDetaching([$account->id, ['current_amount' => $info['current_amount'] ?? '0']]);
        }
    }
}
