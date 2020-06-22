<?php
declare(strict_types=1);
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

namespace FireflyIII\Repositories\PiggyBank;


use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use Illuminate\Database\QueryException;
use Log;

/**
 * Trait ModifiesPiggyBanks
 */
trait ModifiesPiggyBanks
{
    use CreatesObjectGroups;
    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function addAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return false;
        }
        $currentAmount             = $repetition->currentamount ?? '0';
        $repetition->currentamount = bcadd($currentAmount, $amount);
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, $amount);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function removeObjectGroup(PiggyBank $piggyBank): PiggyBank
    {
        $piggyBank->objectGroups()->sync([]);

        return $piggyBank;
    }


    /**
     * @param PiggyBankRepetition $repetition
     * @param string              $amount
     *
     * @return string
     */
    public function addAmountToRepetition(PiggyBankRepetition $repetition, string $amount): string
    {
        $newAmount                 = bcadd($repetition->currentamount, $amount);
        $repetition->currentamount = $newAmount;
        $repetition->save();

        return $newAmount;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $leftOnAccount = $this->leftOnAccount($piggyBank, new Carbon);
        $savedSoFar    = (string) $this->getRepetition($piggyBank)->currentamount;
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = (string) min(round($leftOnAccount, 12), round($leftToSave, 12));
        $compare       = bccomp($amount, $maxAmount);
        $result        = $compare <= 0;

        Log::debug(sprintf('Left on account: %s', $leftOnAccount));
        Log::debug(sprintf('Saved so far: %s', $savedSoFar));
        Log::debug(sprintf('Left to save: %s', $leftToSave));
        Log::debug(sprintf('Maximum amount: %s', $maxAmount));
        Log::debug(sprintf('Compare <= 0? %d, so %s', $compare, var_export($result, true)));

        return $result;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
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
     * Correct order of piggies in case of issues.
     */
    public function correctOrder(): void
    {
        $set     = $this->user->piggyBanks()->orderBy('order', 'ASC')->get();
        $current = 1;
        foreach ($set as $piggyBank) {
            if ((int) $piggyBank->order !== $current) {
                $piggyBank->order = $current;
                $piggyBank->save();
            }
            $current++;
        }
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent
    {
        if (0 === bccomp('0', $amount)) {
            return new PiggyBankEvent;
        }
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

        return $event;
    }

    /**
     * @param PiggyBank          $piggyBank
     * @param string             $amount
     * @param TransactionJournal $journal
     *
     * @return PiggyBankEvent
     */
    public function createEventWithJournal(PiggyBank $piggyBank, string $amount, TransactionJournal $journal): PiggyBankEvent
    {
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggyBank->id,
                'transaction_journal_id' => $journal->id,
                'date'                   => $journal->date->format('Y-m-d'),
                'amount'                 => $amount]
        );

        return $event;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @throws \Exception
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank): bool
    {
        $piggyBank->objectGroups()->sync([]);
        $piggyBank->delete();

        return true;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function removeAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return false;
        }
        $repetition->currentamount = bcsub($repetition->currentamount, $amount);
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, bcmul($amount, '-1'));

        return true;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBank
     */
    public function setCurrentAmount(PiggyBank $piggyBank, string $amount): PiggyBank
    {
        $repetition = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return $piggyBank;
        }
        $max = $piggyBank->targetamount;
        if (1 === bccomp($amount, $max)) {
            $amount = $max;
        }
        $difference                = bcsub($amount, $repetition->currentamount);
        $repetition->currentamount = $amount;
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, $difference);

        return $piggyBank;
    }

    /**
     * set id of piggy bank.
     *
     * @param PiggyBank $piggyBank
     * @param int       $order
     *
     * @return bool
     */
    public function setOrder(PiggyBank $piggyBank, int $order): bool
    {
        $piggyBank->order = $order;
        $piggyBank->save();

        return true;
    }


    /**
     * @inheritDoc
     */
    public function setObjectGroup(PiggyBank $piggyBank, string $objectGroupTitle): PiggyBank
    {
        $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
        if (null !== $objectGroup) {
            $piggyBank->objectGroups()->sync([$objectGroup->id]);
        }

        return $piggyBank;

    }


    /**
     * @param array $data
     *
     * @throws FireflyException
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank
    {
        $data['order'] = $this->getMaxOrder() + 1;
        try {
            /** @var PiggyBank $piggyBank */
            $piggyBank = PiggyBank::create($data);
        } catch (QueryException $e) {
            Log::error(sprintf('Could not store piggy bank: %s', $e->getMessage()));
            throw new FireflyException('400005: Could not store new piggy bank.');
        }

        $this->updateNote($piggyBank, $data['notes']);

        // repetition is auto created.
        $repetition = $this->getRepetition($piggyBank);
        if (null !== $repetition && isset($data['current_amount']) && '' !== $data['current_amount']) {
            $repetition->currentamount = $data['current_amount'];
            $repetition->save();
        }

        $objectGroupTitle = $data['object_group'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if (null !== $objectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
                $piggyBank->save();
            }
        }
        // try also with ID:
        $objectGroupId = (int) ($data['object_group_id'] ?? 0);
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
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank
    {
        if (isset($data['name']) && '' !== $data['name']) {
            $piggyBank->name = $data['name'];
        }
        if (isset($data['account_id']) && 0 !== $data['account_id']) {
            $piggyBank->account_id = (int) $data['account_id'];
        }
        if (isset($data['targetamount']) && '' !== $data['targetamount']) {
            $piggyBank->targetamount = $data['targetamount'];
        }
        if (isset($data['targetdate']) && '' !== $data['targetdate']) {
            $piggyBank->targetdate = $data['targetdate'];
        }
        $piggyBank->startdate = $data['startdate'] ?? $piggyBank->startdate;
        $piggyBank->save();

        $this->updateNote($piggyBank, $data['notes'] ?? '');

        // if the piggy bank is now smaller than the current relevant rep,
        // remove money from the rep.
        $repetition = $this->getRepetition($piggyBank);
        if (null !== $repetition && $repetition->currentamount > $piggyBank->targetamount) {
            $diff = bcsub($piggyBank->targetamount, $repetition->currentamount);
            $this->createEvent($piggyBank, $diff);

            $repetition->currentamount = $piggyBank->targetamount;
            $repetition->save();
        }

        $objectGroupTitle = $data['object_group'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if (null !== $objectGroup) {
                $piggyBank->objectGroups()->sync([$objectGroup->id]);
                $piggyBank->save();
            }
        }

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $note
     *
     * @return bool
     */
    private function updateNote(PiggyBank $piggyBank, string $note): bool
    {
        if ('' === $note) {
            $dbNote = $piggyBank->notes()->first();
            if (null !== $dbNote) {
                try {
                    $dbNote->delete();
                } catch (Exception $e) {
                    Log::debug(sprintf('Could not delete note: %s', $e->getMessage()));
                }
            }

            return true;
        }
        $dbNote = $piggyBank->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }

}
