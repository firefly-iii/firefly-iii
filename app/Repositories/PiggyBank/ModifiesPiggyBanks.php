<?php

/**
 * ModifiesPiggyBanks.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Repositories\PiggyBank;

use FireflyIII\Events\Model\PiggyBank\ChangedAmount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

/**
 * Trait ModifiesPiggyBanks
 */
trait ModifiesPiggyBanks
{
    use CreatesObjectGroups;

    public function addAmountToPiggyBank(PiggyBank $piggyBank, string $amount, TransactionJournal $journal): void
    {
        Log::debug(sprintf('addAmountToPiggyBank: %s', $amount));
        if (-1 === bccomp($amount, '0')) {
            /** @var Transaction $source */
            $source = $journal->transactions()->with(['account'])->where('amount', '<', 0)->first();
            Log::debug('Remove amount.');
            $this->removeAmount($piggyBank, $source->account, bcmul($amount, '-1'), $journal);
        }
        if (1 === bccomp($amount, '0')) {
            /** @var Transaction $destination */
            $destination = $journal->transactions()->with(['account'])->where('amount', '>', 0)->first();
            Log::debug('Add amount.');
            $this->addAmount($piggyBank, $destination->account, $amount, $journal);
        }
    }

    public function removeAmount(PiggyBank $piggyBank, Account $account, string $amount, ?TransactionJournal $journal = null): bool
    {
        $currentAmount                = $this->getCurrentAmount($piggyBank, $account);
        $pivot                        = $piggyBank->accounts()->where('accounts.id', $account->id)->first()->pivot;
        $pivot->current_amount        = bcsub($currentAmount, $amount);
        $pivot->native_current_amount = null;

        // also update native_current_amount.
        $userCurrency                 = app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);
        if ($userCurrency->id !== $piggyBank->transaction_currency_id) {
            $converter                    = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $pivot->native_current_amount = $converter->convert($piggyBank->transactionCurrency, $userCurrency, today(), $pivot->current_amount);
        }

        $pivot->save();

        Log::debug('ChangedAmount: removeAmount [a]: Trigger change for negative amount.');
        event(new ChangedAmount($piggyBank, bcmul($amount, '-1'), $journal, null));

        return true;
    }

    public function addAmount(PiggyBank $piggyBank, Account $account, string $amount, ?TransactionJournal $journal = null): bool
    {
        $currentAmount                = $this->getCurrentAmount($piggyBank, $account);
        $pivot                        = $piggyBank->accounts()->where('accounts.id', $account->id)->first()->pivot;
        $pivot->current_amount        = bcadd($currentAmount, $amount);
        $pivot->native_current_amount = null;

        // also update native_current_amount.
        $userCurrency                 = app('amount')->getDefaultCurrencyByUserGroup($this->user->userGroup);
        if ($userCurrency->id !== $piggyBank->transaction_currency_id) {
            $converter                    = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $pivot->native_current_amount = $converter->convert($piggyBank->transactionCurrency, $userCurrency, today(), $pivot->current_amount);
        }

        $pivot->save();

        Log::debug('ChangedAmount: addAmount [b]: Trigger change for positive amount.');
        event(new ChangedAmount($piggyBank, $amount, $journal, null));

        return true;
    }

    public function canAddAmount(PiggyBank $piggyBank, Account $account, string $amount): bool
    {
        Log::debug('Now in canAddAmount');
        $today         = today(config('app.timezone'))->endOfDay();
        $leftOnAccount = $this->leftOnAccount($piggyBank, $account, $today);
        $savedSoFar    = $this->getCurrentAmount($piggyBank);
        $maxAmount     = $leftOnAccount;

        Log::debug(sprintf('Left on account: %s on %s', $leftOnAccount, $today->format('Y-m-d H:i:s')));
        Log::debug(sprintf('Saved so far: %s', $savedSoFar));


        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $leftToSave = bcsub($piggyBank->target_amount, $savedSoFar);
            $maxAmount  = 1 === bccomp($leftOnAccount, $leftToSave) ? $leftToSave : $leftOnAccount;
            Log::debug(sprintf('Left to save: %s', $leftToSave));
            Log::debug(sprintf('Maximum amount: %s', $maxAmount));
        }

        $compare       = bccomp($amount, $maxAmount);
        $result        = $compare <= 0;

        Log::debug(sprintf('Compare <= 0? %d, so canAddAmount is %s', $compare, var_export($result, true)));

        return $result;
    }

    public function canRemoveAmount(PiggyBank $piggyBank, Account $account, string $amount): bool
    {
        $savedSoFar = $this->getCurrentAmount($piggyBank, $account);

        return bccomp($amount, $savedSoFar) <= 0;
    }

    /**
     * @throws \Exception
     */
    public function destroy(PiggyBank $piggyBank): bool
    {
        $piggyBank->objectGroups()->sync([]);
        $piggyBank->delete();

        return true;
    }

    public function removeObjectGroup(PiggyBank $piggyBank): PiggyBank
    {
        $piggyBank->objectGroups()->sync([]);

        return $piggyBank;
    }

    public function setCurrentAmount(PiggyBank $piggyBank, string $amount): PiggyBank
    {
        $repetition                 = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return $piggyBank;
        }
        $max                        = $piggyBank->target_amount;
        if (1 === bccomp($amount, $max) && 0 !== bccomp($piggyBank->target_amount, '0')) {
            $amount = $max;
        }
        $difference                 = bcsub($amount, $repetition->current_amount);
        $repetition->current_amount = $amount;
        $repetition->save();

        if (-1 === bccomp($difference, '0')) {
            Log::debug('ChangedAmount: addAmount [c]: Trigger change for negative amount.');
            event(new ChangedAmount($piggyBank, $difference, null, null));
        }
        if (1 === bccomp($difference, '0')) {
            Log::debug('ChangedAmount: addAmount [d]: Trigger change for positive amount.');
            event(new ChangedAmount($piggyBank, $difference, null, null));
        }

        return $piggyBank;
    }

    public function setObjectGroup(PiggyBank $piggyBank, string $objectGroupTitle): PiggyBank
    {
        $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
        if (null !== $objectGroup) {
            $piggyBank->objectGroups()->sync([$objectGroup->id]);
        }

        return $piggyBank;
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): PiggyBank
    {
        $factory       = new PiggyBankFactory();
        $factory->user = $this->user;

        return $factory->store($data);
    }

    public function update(PiggyBank $piggyBank, array $data): PiggyBank
    {
        $piggyBank     = $this->updateProperties($piggyBank, $data);
        if (array_key_exists('notes', $data)) {
            $this->updateNote($piggyBank, (string) $data['notes']);
        }

        // update the order of the piggy bank:
        $oldOrder      = $piggyBank->order;
        $newOrder      = (int) ($data['order'] ?? $oldOrder);
        if ($oldOrder !== $newOrder) {
            $this->setOrder($piggyBank, $newOrder);
        }

        // update the accounts
        $factory       = new PiggyBankFactory();
        $factory->user = $this->user;
        $factory->linkToAccountIds($piggyBank, $data['accounts']);


        // if the piggy bank is now smaller than the sum of the money saved,
        // remove money from all accounts until the piggy bank is the right amount.
        $currentAmount = $this->getCurrentAmount($piggyBank);
        if (1 === bccomp($currentAmount, $piggyBank->target_amount) && 0 !== bccomp($piggyBank->target_amount, '0')) {
            Log::debug(sprintf('Current amount is %s, target amount is %s', $currentAmount, $piggyBank->target_amount));
            $difference = bcsub($piggyBank->target_amount, $currentAmount);

            // an amount will be removed, create "negative" event:
            Log::debug(sprintf('ChangedAmount: is triggered with difference "%s"', $difference));
            event(new ChangedAmount($piggyBank, $difference, null, null));

            // question is, from which account(s) to remove the difference?
            // solution: just start from the top until there is no more money left to remove.
            $this->removeAmountFromAll($piggyBank, app('steam')->positive($difference));
        }

        // update using name:
        if (array_key_exists('object_group_title', $data)) {
            $objectGroupTitle = (string) $data['object_group_title'];
            if ('' !== $objectGroupTitle) {
                $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
                if (null !== $objectGroup) {
                    $piggyBank->objectGroups()->sync([$objectGroup->id]);
                    $piggyBank->save();
                }

                return $piggyBank;
            }
            $piggyBank->objectGroups()->sync([]);
            $piggyBank->save();
        }

        // try also with ID:
        if (array_key_exists('object_group_id', $data)) {
            $objectGroupId = (int) ($data['object_group_id'] ?? 0);
            if (0 !== $objectGroupId) {
                $objectGroup = $this->findObjectGroupById($objectGroupId);
                if (null !== $objectGroup) {
                    $piggyBank->objectGroups()->sync([$objectGroup->id]);
                    $piggyBank->save();
                }

                return $piggyBank;
            }
        }

        return $piggyBank;
    }

    private function updateProperties(PiggyBank $piggyBank, array $data): PiggyBank
    {
        if (array_key_exists('name', $data) && '' !== $data['name']) {
            $piggyBank->name = $data['name'];
        }
        if (array_key_exists('target_amount', $data) && '' !== $data['target_amount']) {
            $piggyBank->target_amount = $data['target_amount'];
        }
        if (array_key_exists('target_amount', $data) && '' === $data['target_amount']) {
            $piggyBank->target_amount = '0';
        }
        if (array_key_exists('target_date', $data) && '' !== $data['target_date']) {
            $piggyBank->target_date    = $data['target_date'];
            $piggyBank->target_date_tz = $data['target_date']?->format('e');
        }
        if (array_key_exists('start_date', $data)) {
            $piggyBank->start_date    = $data['start_date'];
            $piggyBank->start_date_tz = $data['target_date']?->format('e');
        }
        $piggyBank->save();

        return $piggyBank;
    }

    public function updateNote(PiggyBank $piggyBank, string $note): void
    {
        if ('' === $note) {
            $dbNote = $piggyBank->notes()->first();
            $dbNote?->delete();

            return;
        }
        $dbNote       = $piggyBank->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();
    }

    public function setOrder(PiggyBank $piggyBank, int $newOrder): bool
    {
        $oldOrder         = $piggyBank->order;
        // Log::debug(sprintf('Will move piggy bank #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
        if ($newOrder > $oldOrder) {
            PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
                ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
                ->where('accounts.user_id', $this->user->id)
                ->where('piggy_banks.order', '<=', $newOrder)->where('piggy_banks.order', '>', $oldOrder)
                ->where('piggy_banks.id', '!=', $piggyBank->id)
                ->distinct()->decrement('piggy_banks.order')
            ;

            $piggyBank->order = $newOrder;
            Log::debug(sprintf('[1] Order of piggy #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
            $piggyBank->save();

            return true;
        }
        PiggyBank::leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
            ->where('accounts.user_id', $this->user->id)
            ->where('piggy_banks.order', '>=', $newOrder)->where('piggy_banks.order', '<', $oldOrder)
            ->where('piggy_banks.id', '!=', $piggyBank->id)
            ->distinct()->increment('piggy_banks.order')
        ;

        $piggyBank->order = $newOrder;
        Log::debug(sprintf('[2] Order of piggy #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
        $piggyBank->save();

        return true;
    }

    public function removeAmountFromAll(PiggyBank $piggyBank, string $amount): void
    {
        foreach ($piggyBank->accounts as $account) {
            $current = $account->pivot->current_amount;
            // if this account contains more than the amount, remove the amount and return.
            if (1 === bccomp($current, $amount)) {
                $this->removeAmount($piggyBank, $account, $amount);

                return;
            }
            // if this account contains less than the amount, remove the current amount, update the amount and continue.
            if (bccomp($current, $amount) < 1) {
                $this->removeAmount($piggyBank, $account, $current);
                $amount = bcsub($amount, $current);
            }
        }
    }
}
