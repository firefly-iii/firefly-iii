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
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Database\QueryException;

/**
 * Trait ModifiesPiggyBanks
 */
trait ModifiesPiggyBanks
{
    use CreatesObjectGroups;

    public function addAmountToRepetition(PiggyBankRepetition $repetition, string $amount, TransactionJournal $journal): void
    {
        throw new FireflyException('[a] Piggy bank repetitions are EOL.');
        app('log')->debug(sprintf('addAmountToRepetition: %s', $amount));
        if (-1 === bccomp($amount, '0')) {
            app('log')->debug('Remove amount.');
            $this->removeAmount($repetition->piggyBank, bcmul($amount, '-1'), $journal);
        }
        if (1 === bccomp($amount, '0')) {
            app('log')->debug('Add amount.');
            $this->addAmount($repetition->piggyBank, $amount, $journal);
        }
    }

    public function removeAmount(PiggyBank $piggyBank, string $amount, ?TransactionJournal $journal = null): bool
    {
        $repetition                = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return false;
        }
        $repetition->current_amount = bcsub($repetition->current_amount, $amount);
        $repetition->save();

        app('log')->debug('addAmount [a]: Trigger change for negative amount.');
        event(new ChangedAmount($piggyBank, bcmul($amount, '-1'), $journal, null));

        return true;
    }

    public function addAmount(PiggyBank $piggyBank, string $amount, ?TransactionJournal $journal = null): bool
    {
        $repetition                = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return false;
        }
        $currentAmount             = $repetition->current_amount ?? '0';
        $repetition->current_amount = bcadd($currentAmount, $amount);
        $repetition->save();

        app('log')->debug('addAmount [b]: Trigger change for positive amount.');
        event(new ChangedAmount($piggyBank, $amount, $journal, null));

        return true;
    }

    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $today         = today(config('app.timezone'));
        $leftOnAccount = $this->leftOnAccount($piggyBank, $today);
        $savedSoFar    = $this->getRepetition($piggyBank)->current_amount;
        $maxAmount     = $leftOnAccount;
        $leftToSave    = null;
        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $leftToSave = bcsub($piggyBank->target_amount, $savedSoFar);
            $maxAmount  = 1 === bccomp($leftOnAccount, $leftToSave) ? $leftToSave : $leftOnAccount;
        }

        $compare       = bccomp($amount, $maxAmount);
        $result        = $compare <= 0;

        app('log')->debug(sprintf('Left on account: %s on %s', $leftOnAccount, $today->format('Y-m-d')));
        app('log')->debug(sprintf('Saved so far: %s', $savedSoFar));
        app('log')->debug(sprintf('Left to save: %s', $leftToSave));
        app('log')->debug(sprintf('Maximum amount: %s', $maxAmount));
        app('log')->debug(sprintf('Compare <= 0? %d, so %s', $compare, var_export($result, true)));

        return $result;
    }

    public function canRemoveAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return false;
        }
        $savedSoFar = $repetition->current_amount;

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
        $repetition                = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return $piggyBank;
        }
        $max                       = $piggyBank->target_amount;
        if (1 === bccomp($amount, $max) && 0 !== bccomp($piggyBank->target_amount, '0')) {
            $amount = $max;
        }
        $difference                = bcsub($amount, $repetition->current_amount);
        $repetition->current_amount = $amount;
        $repetition->save();

        if (-1 === bccomp($difference, '0')) {
            app('log')->debug('addAmount [c]: Trigger change for negative amount.');
            event(new ChangedAmount($piggyBank, $difference, null, null));
        }
        if (1 === bccomp($difference, '0')) {
            app('log')->debug('addAmount [d]: Trigger change for positive amount.');
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


    public function setOrder(PiggyBank $piggyBank, int $newOrder): bool
    {
        $oldOrder         = $piggyBank->order;
        // app('log')->debug(sprintf('Will move piggy bank #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
        if ($newOrder > $oldOrder) {
            $this->user->piggyBanks()->where('piggy_banks.order', '<=', $newOrder)->where('piggy_banks.order', '>', $oldOrder)
                ->where('piggy_banks.id', '!=', $piggyBank->id)
                ->decrement('piggy_banks.order')
            ;
            $piggyBank->order = $newOrder;
            app('log')->debug(sprintf('[1] Order of piggy #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
            $piggyBank->save();

            return true;
        }

        $this->user->piggyBanks()->where('piggy_banks.order', '>=', $newOrder)->where('piggy_banks.order', '<', $oldOrder)
            ->where('piggy_banks.id', '!=', $piggyBank->id)
            ->increment('piggy_banks.order')
        ;
        $piggyBank->order = $newOrder;
        app('log')->debug(sprintf('[2] Order of piggy #%d ("%s") from %d to %d', $piggyBank->id, $piggyBank->name, $oldOrder, $newOrder));
        $piggyBank->save();

        return true;
    }

    public function updateNote(PiggyBank $piggyBank, string $note): void
    {
        if ('' === $note) {
            $dbNote = $piggyBank->notes()->first();
            $dbNote?->delete();
            return ;
        }
        $dbNote       = $piggyBank->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();
    }

    public function update(PiggyBank $piggyBank, array $data): PiggyBank
    {
        $piggyBank  = $this->updateProperties($piggyBank, $data);
        if (array_key_exists('notes', $data)) {
            $this->updateNote($piggyBank, (string)$data['notes']);
        }

        // update the order of the piggy bank:
        $oldOrder   = $piggyBank->order;
        $newOrder   = (int)($data['order'] ?? $oldOrder);
        if ($oldOrder !== $newOrder) {
            $this->setOrder($piggyBank, $newOrder);
        }

        // if the piggy bank is now smaller than the current relevant rep,
        // remove money from the rep.
        $repetition = $this->getRepetition($piggyBank);
        if (null !== $repetition && $repetition->current_amount > $piggyBank->target_amount && 0 !== bccomp($piggyBank->target_amount, '0')) {
            $difference                = bcsub($piggyBank->target_amount, $repetition->current_amount);

            // an amount will be removed, create "negative" event:
            event(new ChangedAmount($piggyBank, $difference, null, null));

            $repetition->current_amount = $piggyBank->target_amount;
            $repetition->save();
        }

        // update using name:
        if (array_key_exists('object_group_title', $data)) {
            $objectGroupTitle = (string)$data['object_group_title'];
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
            $objectGroupId = (int)($data['object_group_id'] ?? 0);
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
        if (array_key_exists('account_id', $data) && 0 !== $data['account_id']) {
            $piggyBank->account_id = (int)$data['account_id'];
        }
        if (array_key_exists('targetamount', $data) && '' !== $data['targetamount']) {
            $piggyBank->target_amount = $data['targetamount'];
        }
        if (array_key_exists('targetamount', $data) && '' === $data['targetamount']) {
            $piggyBank->target_amount = '0';
        }
        if (array_key_exists('targetdate', $data) && '' !== $data['targetdate']) {
            $piggyBank->target_date    = $data['targetdate'];
            $piggyBank->target_date_tz = $data['targetdate']?->format('e');
        }
        if (array_key_exists('startdate', $data)) {
            $piggyBank->start_date    = $data['startdate'];
            $piggyBank->start_date_tz = $data['targetdate']?->format('e');
        }
        $piggyBank->save();

        return $piggyBank;
    }
}
