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

use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\Account;
use FireflyIII\Events\Model\PiggyBank\ChangedAmount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

use function Safe\json_encode;

/**
 * Class PiggyBankFactory
 */
class PiggyBankFactory
{
    use CreatesObjectGroups;

    public User $user;
    private AccountRepositoryInterface $accountRepository;
    private CurrencyRepositoryInterface $currencyRepository;
    private PiggyBankRepositoryInterface $piggyBankRepository;

    public function __construct()
    {
        $this->currencyRepository  = app(CurrencyRepositoryInterface::class);
        $this->accountRepository   = app(AccountRepositoryInterface::class);
        $this->piggyBankRepository = app(PiggyBankRepositoryInterface::class);
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->currencyRepository->setUser($user);
        $this->accountRepository->setUser($user);
        $this->piggyBankRepository->setUser($user);
    }

    /**
     * Store a piggy bank or come back with an exception.
     */
    public function store(array $data): PiggyBank
    {

        $piggyBankData                            = $data;

        // unset some fields
        unset($piggyBankData['object_group_title'], $piggyBankData['transaction_currency_code'], $piggyBankData['transaction_currency_id'], $piggyBankData['accounts'], $piggyBankData['object_group_id'], $piggyBankData['notes']);

        // validate amount:
        if (array_key_exists('target_amount', $piggyBankData) && '' === (string)$piggyBankData['target_amount']) {
            $piggyBankData['target_amount'] = '0';
        }

        $piggyBankData['start_date_tz']           = $piggyBankData['start_date']?->format('e');
        $piggyBankData['target_date_tz']          = $piggyBankData['target_date']?->format('e');
        $piggyBankData['account_id']              = null;
        $piggyBankData['transaction_currency_id'] = $this->getCurrency($data)->id;
        $piggyBankData['order']                   = 131337;

        try {
            /** @var PiggyBank $piggyBank */
            $piggyBank = PiggyBank::createQuietly($piggyBankData);
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not store piggy bank: %s', $e->getMessage()), $piggyBankData);

            throw new FireflyException('400005: Could not store new piggy bank.', 0, $e);
        }
        $piggyBank                                = $this->setOrder($piggyBank, $data);
        $this->linkToAccountIds($piggyBank, $data['accounts']);
        $this->piggyBankRepository->updateNote($piggyBank, $data['notes']);

        $objectGroupTitle                         = $data['object_group_title'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if ($objectGroup instanceof ObjectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
            }
        }
        // try also with ID
        $objectGroupId                            = (int)($data['object_group_id'] ?? 0);
        if (0 !== $objectGroupId) {
            $objectGroup = $this->findObjectGroupById($objectGroupId);
            if ($objectGroup instanceof ObjectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
            }
        }
        Log::debug('Touch piggy bank');
        $piggyBank->encrypted                     = false;
        $piggyBank->save();
        $piggyBank->touch();

        return $piggyBank;
    }

    private function getCurrency(array $data): TransactionCurrency
    {
        // currency:
        $defaultCurrency = app('amount')->getNativeCurrency();
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

    public function find(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank
    {
        $piggyBankId   = (int)$piggyBankId;
        $piggyBankName = (string)$piggyBankName;
        if ('' === $piggyBankName && 0 === $piggyBankId) {
            return null;
        }
        // first find by ID:
        if ($piggyBankId > 0) {
            $piggyBank = PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
                ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
                ->where('accounts.user_id', $this->user->id)
                ->where('piggy_banks.id', $piggyBankId)
                ->first(['piggy_banks.*'])
            ;
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
        return PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
            ->where('accounts.user_id', $this->user->id)
            ->where('piggy_banks.name', $name)
            ->first(['piggy_banks.*'])
        ;
    }

    private function setOrder(PiggyBank $piggyBank, array $data): PiggyBank
    {
        $this->resetOrder();
        $order            = $this->getMaxOrder() + 1;
        if (array_key_exists('order', $data)) {
            $order = $data['order'];
        }
        $piggyBank->order = $order;
        $piggyBank->saveQuietly();

        return $piggyBank;

    }

    public function resetOrder(): void
    {
        // TODO duplicate code
        $set     = PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
            ->where('accounts.user_id', $this->user->id)
            ->with(
                [
                    'objectGroups',
                ]
            )
            ->orderBy('piggy_banks.order', 'ASC')->get(['piggy_banks.*'])
        ;
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
        return (int)$this->piggyBankRepository->getPiggyBanks()->max('order');

    }

    public function linkToAccountIds(PiggyBank $piggyBank, array $accounts): void
    {
        Log::debug(sprintf('Linking piggy bank #%d to %d accounts.', $piggyBank->id, count($accounts)), $accounts);
        // collect current current_amount so the sync does not remove them.
        // TODO this is a tedious check. Feels like a hack.
        $toBeLinked     = [];
        $oldSavedAmount = $this->piggyBankRepository->getCurrentAmount($piggyBank);
        foreach ($piggyBank->accounts as $account) {
            Log::debug(sprintf('Checking account #%d', $account->id));
            foreach ($accounts as $info) {
                Log::debug(sprintf('  Checking other account #%d', $info['account_id']));
                if ((int)$account->id === (int)$info['account_id']) {
                    $toBeLinked[$account->id] = ['current_amount' => $account->pivot->current_amount ?? '0'];
                    Log::debug(sprintf('Prefilled for account #%d with amount %s', $account->id, $account->pivot->current_amount ?? '0'));
                }
            }
        }

        /** @var array $info */
        foreach ($accounts as $info) {
            $account = $this->accountRepository->find((int)($info['account_id'] ?? 0));
            if (!$account instanceof Account) {
                Log::debug(sprintf('Account #%d not found, skipping.', (int)($info['account_id'] ?? 0)));

                continue;
            }
            if (array_key_exists('current_amount', $info) && null !== $info['current_amount']) {
                // an amount is set, first check out if there is a difference with the previous amount.
                $previous                 = $toBeLinked[$account->id]['current_amount'] ?? '0';
                $diff                     = bcsub($info['current_amount'], $previous);

                // create event for difference.
                if (0 !== bccomp($diff, '0')) {
                    Log::debug(sprintf('[a] Will save event for difference %s (previous value was %s)', $diff, $previous));
                    event(new ChangedAmount($piggyBank, $diff, null, null));
                }

                $toBeLinked[$account->id] = ['current_amount' => $info['current_amount']];
                Log::debug(sprintf('[a] Will link account #%d with amount %s', $account->id, $info['current_amount']));
            }
            if (array_key_exists('current_amount', $info) && null === $info['current_amount']) {
                // an amount is set, first check out if there is a difference with the previous amount.
                $previous                 = $toBeLinked[$account->id]['current_amount'] ?? '0';
                $diff                     = bcsub('0', $previous);

                // create event for difference.
                if (0 !== bccomp($diff, '0')) {
                    Log::debug(sprintf('[b] Will save event for difference %s (previous value was %s)', $diff, $previous));
                    event(new ChangedAmount($piggyBank, $diff, null, null));
                }

                // no amount set, use previous amount or go to ZERO.
                $toBeLinked[$account->id] = ['current_amount' => $toBeLinked[$account->id]['current_amount'] ?? '0'];
                Log::debug(sprintf('[b] Will link account #%d with amount %s', $account->id, $toBeLinked[$account->id]['current_amount'] ?? '0'));

                // create event:
                Log::debug('linkToAccountIds: Trigger change for positive amount [b].');
                event(new ChangedAmount($piggyBank, $toBeLinked[$account->id]['current_amount'], null, null));
            }
            if (!array_key_exists('current_amount', $info)) {
                $toBeLinked[$account->id] ??= [];
                Log::debug(sprintf('Will link account #%d with info: ', $account->id), $toBeLinked[$account->id]);
            }
        }
        Log::debug(sprintf('Link information: %s', json_encode($toBeLinked)));
        if (0 !== count($toBeLinked)) {
            Log::debug('Syncing accounts to piggy bank.');
            $piggyBank->accounts()->sync($toBeLinked);
            $piggyBank->refresh();
            $newSavedAmount = $this->piggyBankRepository->getCurrentAmount($piggyBank);
            Log::debug(sprintf('Old saved amount: %s, new saved amount is %s', $oldSavedAmount, $newSavedAmount));
            if (0 !== bccomp($oldSavedAmount, $newSavedAmount)) {
                Log::debug('Amount changed, will create event for it.');
                // create event for difference.
                event(new ChangedAmount($piggyBank, bcsub($newSavedAmount, $oldSavedAmount), null, null));
            }
        }
        if (0 === count($toBeLinked)) {
            Log::warning('No accounts to link to piggy bank, will not change whatever is there now.');
        }
    }
}
