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
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use Illuminate\Database\QueryException;

/**
 * Trait ModifiesPiggyBanks
 */
trait ModifiesPiggyBanks
{
    use CreatesObjectGroups;

    public function addAmountToRepetition(PiggyBankRepetition $repetition, string $amount, TransactionJournal $journal): void
    {
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
        $repetition->currentamount = bcsub($repetition->currentamount, $amount);
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
        $currentAmount             = $repetition->currentamount ?? '0';
        $repetition->currentamount = bcadd($currentAmount, $amount);
        $repetition->save();

        app('log')->debug('addAmount [b]: Trigger change for positive amount.');
        event(new ChangedAmount($piggyBank, $amount, $journal, null));

        return true;
    }

    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $today         = today(config('app.timezone'));
        $leftOnAccount = $this->leftOnAccount($piggyBank, $today);
        $savedSoFar    = $this->getRepetition($piggyBank)->currentamount;
        $maxAmount     = $leftOnAccount;
        $leftToSave    = null;
        if (0 !== bccomp($piggyBank->targetamount, '0')) {
            $leftToSave = bcsub($piggyBank->targetamount, $savedSoFar);
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
        $savedSoFar = $repetition->currentamount;

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
        $max                       = $piggyBank->targetamount;
        if (1 === bccomp($amount, $max) && 0 !== bccomp($piggyBank->targetamount, '0')) {
            $amount = $max;
        }
        $difference                = bcsub($amount, $repetition->currentamount);
        $repetition->currentamount = $amount;
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
        $order                      = $this->getMaxOrder() + 1;
        if (array_key_exists('order', $data)) {
            $order = $data['order'];
        }
        $data['order']              = 31337; // very high when creating.
        $piggyData                  = $data;
        // unset fields just in case.
        unset($piggyData['object_group_title'], $piggyData['object_group_id'], $piggyData['notes'], $piggyData['current_amount']);

        // validate amount:
        if (array_key_exists('targetamount', $piggyData) && '' === (string)$piggyData['targetamount']) {
            $piggyData['targetamount'] = '0';
        }

        $piggyData['startdate_tz']  = $piggyData['startdate']?->format('e');
        $piggyData['targetdate_tz'] = $piggyData['targetdate']?->format('e');

        try {
            /** @var PiggyBank $piggyBank */
            $piggyBank = PiggyBank::create($piggyData);
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not store piggy bank: %s', $e->getMessage()), $piggyData);

            throw new FireflyException('400005: Could not store new piggy bank.', 0, $e);
        }

        // reset order then set order:
        $this->resetOrder();
        $this->setOrder($piggyBank, $order);

        $this->updateNote($piggyBank, $data['notes']);

        // repetition is auto created.
        $repetition                 = $this->getRepetition($piggyBank);
        if (null !== $repetition && array_key_exists('current_amount', $data) && '' !== $data['current_amount']) {
            $repetition->currentamount = $data['current_amount'];
            $repetition->save();
        }

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

    /**
     * Correct order of piggies in case of issues.
     */
    public function resetOrder(): void
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

    private function updateNote(PiggyBank $piggyBank, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $piggyBank->notes()->first();
            $dbNote?->delete();

            return true;
        }
        $dbNote       = $piggyBank->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
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
        if (null !== $repetition && $repetition->currentamount > $piggyBank->targetamount && 0 !== bccomp($piggyBank->targetamount, '0')) {
            $difference                = bcsub($piggyBank->targetamount, $repetition->currentamount);

            // an amount will be removed, create "negative" event:
            event(new ChangedAmount($piggyBank, $difference, null, null));

            $repetition->currentamount = $piggyBank->targetamount;
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
            $piggyBank->targetamount = $data['targetamount'];
        }
        if (array_key_exists('targetamount', $data) && '' === $data['targetamount']) {
            $piggyBank->targetamount = '0';
        }
        if (array_key_exists('targetdate', $data) && '' !== $data['targetdate']) {
            $piggyBank->targetdate    = $data['targetdate'];
            $piggyBank->targetdate_tz = $data['targetdate']?->format('e');
        }
        if (array_key_exists('startdate', $data)) {
            $piggyBank->startdate    = $data['startdate'];
            $piggyBank->startdate_tz = $data['targetdate']?->format('e');
        }
        $piggyBank->save();

        return $piggyBank;
    }
}
