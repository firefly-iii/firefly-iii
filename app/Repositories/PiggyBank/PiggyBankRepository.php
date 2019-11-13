<?php
/**
 * PiggyBankRepository.php
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

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;

/**
 * Class PiggyBankRepository.
 *
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string $amount
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
     * @param PiggyBankRepetition $repetition
     * @param string $amount
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
     * @param string $amount
     *
     * @return bool
     */
    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $leftOnAccount = $this->leftOnAccount($piggyBank, new Carbon);
        $savedSoFar    = (string)$this->getRepetition($piggyBank)->currentamount;
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = (string)min(round($leftOnAccount, 12), round($leftToSave, 12));
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
     * @param string $amount
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
            if ((int)$piggyBank->order !== $current) {
                $piggyBank->order = $current;
                $piggyBank->save();
            }
            $current++;
        }
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent
    {
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

        return $event;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string $amount
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
     * @return bool
     * @throws \Exception
     */
    public function destroy(PiggyBank $piggyBank): bool
    {
        $piggyBank->delete();

        return true;
    }

    /**
     * Find by name or return NULL.
     *
     * @param string $name
     *
     * @return PiggyBank|null
     */
    public function findByName(string $name): ?PiggyBank
    {
        $set = $this->user->piggyBanks()->get(['piggy_banks.*']);

        // TODO no longer need to loop like this

        /** @var PiggyBank $piggy */
        foreach ($set as $piggy) {
            if ($piggy->name === $name) {
                return $piggy;
            }
        }

        return null;
    }

    /**
     * @param int $piggyBankId
     *
     * @return PiggyBank|null
     */
    public function findNull(int $piggyBankId): ?PiggyBank
    {
        $piggyBank = $this->user->piggyBanks()->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);
        if (null !== $piggyBank) {
            return $piggyBank;
        }

        return null;
    }

    /**
     * @param int|null $piggyBankId
     * @param string|null $piggyBankName
     *
     * @return PiggyBank|null
     */
    public function findPiggyBank(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank
    {
        Log::debug('Searching for piggy information.');

        if (null !== $piggyBankId) {
            $searchResult = $this->findNull((int)$piggyBankId);
            if (null !== $searchResult) {
                Log::debug(sprintf('Found piggy based on #%d, will return it.', $piggyBankId));

                return $searchResult;
            }
        }
        if (null !== $piggyBankName) {
            $searchResult = $this->findByName((string)$piggyBankName);
            if (null !== $searchResult) {
                Log::debug(sprintf('Found piggy based on "%s", will return it.', $piggyBankName));

                return $searchResult;
            }
        }
        Log::debug('Found nothing');

        return null;
    }

    /**
     * Get current amount saved in piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getCurrentAmount(PiggyBank $piggyBank): string
    {
        $rep = $this->getRepetition($piggyBank);
        if (null === $rep) {
            return '0';
        }

        return (string)$rep->currentamount;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank): Collection
    {
        return $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /**
     * Used for connecting to a piggy bank.
     *
     * @param PiggyBank $piggyBank
     * @param PiggyBankRepetition $repetition
     * @param TransactionJournal $journal
     *
     * @return string
     *
     */
    public function getExactAmount(PiggyBank $piggyBank, PiggyBankRepetition $repetition, TransactionJournal $journal): string
    {
        Log::debug(sprintf('Now in getExactAmount(%d, %d, %d)', $piggyBank->id, $repetition->id, $journal->id));

        $operator = null;
        /** @var JournalRepositoryInterface $journalRepost */
        $journalRepost = app(JournalRepositoryInterface::class);
        $journalRepost->setUser($this->user);

        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($this->user);

        $defaultCurrency   = app('amount')->getDefaultCurrencyByUser($this->user);
        $piggyBankCurrency = $accountRepos->getAccountCurrency($piggyBank->account) ?? $defaultCurrency;

        Log::debug(sprintf('Piggy bank #%d currency is %s', $piggyBank->id, $piggyBankCurrency->code));

        /** @var Transaction $source */
        $source = $journal->transactions()->with(['Account'])->where('amount', '<', 0)->first();
        /** @var Transaction $destination */
        $destination = $journal->transactions()->with(['Account'])->where('amount', '>', 0)->first();

        // matches source, which means amount will be removed from piggy:
        if ($source->account_id === $piggyBank->account_id) {
            $operator = 'negative';
            $currency = $accountRepos->getAccountCurrency($source->account) ?? $defaultCurrency;
            Log::debug(sprintf('Currency will draw money out of piggy bank. Source currency is %s', $currency->code));
        }

        // matches destination, which means amount will be added to piggy.
        if ($destination->account_id === $piggyBank->account_id) {
            $operator = 'positive';
            $currency = $accountRepos->getAccountCurrency($destination->account) ?? $defaultCurrency;
            Log::debug(sprintf('Currency will add money to piggy bank. Destination currency is %s', $currency->code));
        }
        if (null === $operator || null === $currency) {
            return '0';
        }
        // currency of the account + the piggy bank currency are almost the same.
        // which amount from the transaction matches?
        // $currency->id === $piggyBankCurrency->id
        $amount = null;
        if ($source->transaction_currency_id === $currency->id) {
            Log::debug('Use normal amount');
            $amount = app('steam')->$operator($source->amount);
        }
        if ($source->foreign_currency_id === $currency->id) {
            Log::debug('Use foreign amount');
            $amount = app('steam')->$operator($source->foreign_amount);
        }
        if (null === $amount) {
            return '0';
        }

        Log::debug(sprintf('The currency is %s and the amount is %s', $currency->code, $amount));


        $room    = bcsub((string)$piggyBank->targetamount, (string)$repetition->currentamount);
        $compare = bcmul($repetition->currentamount, '-1');
        Log::debug(sprintf('Will add/remove %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));

        // if the amount is positive, make sure it fits in piggy bank:
        if (1 === bccomp($amount, '0') && bccomp($room, $amount) === -1) {
            // amount is positive and $room is smaller than $amount
            Log::debug(sprintf('Room in piggy bank for extra money is %f', $room));
            Log::debug(sprintf('There is NO room to add %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
            Log::debug(sprintf('New amount is %f', $room));
            return $room;
        }

        // amount is negative and $currentamount is smaller than $amount
        if (bccomp($amount, '0') === -1 && 1 === bccomp($compare, $amount)) {
            Log::debug(sprintf('Max amount to remove is %f', $repetition->currentamount));
            Log::debug(sprintf('Cannot remove %f from piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
            Log::debug(sprintf('New amount is %f', $compare));
            return $compare;
        }

        return (string)$amount;
    }

    /**
     * @return int
     */
    public function getMaxOrder(): int
    {
        return (int)$this->user->piggyBanks()->max('order');
    }

    /**
     * Return note for piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getNoteText(PiggyBank $piggyBank): string
    {
        /** @var Note $note */
        $note = $piggyBank->notes()->first();
        if (null === $note) {
            return '';
        }

        return $note->text;
    }

    /**
     * @return Collection
     */
    public function getPiggyBanks(): Collection
    {
        return $this->user->piggyBanks()->with(['account'])->orderBy('order', 'ASC')->get();
    }



    /**
     * Also add amount in name.
     *
     * @return Collection
     */
    public function getPiggyBanksWithAmount(): Collection
    {

        $currency = app('amount')->getDefaultCurrency();

        $set = $this->getPiggyBanks();
        /** @var PiggyBank $piggy */
        foreach ($set as $piggy) {
            $currentAmount = $this->getRepetition($piggy)->currentamount ?? '0';
            $piggy->name   = $piggy->name . ' (' . app('amount')->formatAnything($currency, $currentAmount, false) . ')';
        }


        return $set;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return PiggyBankRepetition|null
     */
    public function getRepetition(PiggyBank $piggyBank): ?PiggyBankRepetition
    {
        return $piggyBank->piggyBankRepetitions()->first();
    }

    /**
     * Returns the suggested amount the user should save per month, or "".
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     *
     */
    public function getSuggestedMonthlyAmount(PiggyBank $piggyBank): string
    {
        $savePerMonth = '0';
        $repetition   = $this->getRepetition($piggyBank);
        if (null === $repetition) {
            return $savePerMonth;
        }
        if (null !== $piggyBank->targetdate && $repetition->currentamount < $piggyBank->targetamount) {
            $now             = Carbon::now();
            $diffInMonths    = $now->diffInMonths($piggyBank->targetdate, false);
            $remainingAmount = bcsub($piggyBank->targetamount, $repetition->currentamount);

            // more than 1 month to go and still need money to save:
            if ($diffInMonths > 0 && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = bcdiv($remainingAmount, (string)$diffInMonths);
            }

            // less than 1 month to go but still need money to save:
            if (0 === $diffInMonths && 1 === bccomp($remainingAmount, '0')) {
                $savePerMonth = $remainingAmount;
            }
        }

        return $savePerMonth;
    }

    /**
     * Get for piggy account what is left to put in piggies.
     *
     * @param PiggyBank $piggyBank
     * @param Carbon $date
     *
     * @return string
     */
    public function leftOnAccount(PiggyBank $piggyBank, Carbon $date): string
    {

        $balance = app('steam')->balanceIgnoreVirtual($piggyBank->account, $date);

        /** @var Collection $piggies */
        $piggies = $piggyBank->account->piggyBanks;

        /** @var PiggyBank $current */
        foreach ($piggies as $current) {
            $repetition = $this->getRepetition($current);
            if (null !== $repetition) {
                $balance = bcsub($balance, $repetition->currentamount);
            }
        }

        return $balance;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string $amount
     *
     * @return bool
     */
    public function removeAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition                = $this->getRepetition($piggyBank);
        $repetition->currentamount = bcsub($repetition->currentamount, $amount);
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, bcmul($amount, '-1'));

        return true;
    }

    /**
     * set id of piggy bank.
     *
     * @param PiggyBank $piggyBank
     * @param int $order
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
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return PiggyBank
     * @throws FireflyException
     */
    public function store(array $data): PiggyBank
    {
        $data['order'] = $this->getMaxOrder() + 1;
        try {
        /** @var PiggyBank $piggyBank */
        $piggyBank = PiggyBank::create($data);
        } catch(QueryException $e) {
            Log::error(sprintf('Could not store piggy bank: %s',$e->getMessage()));
            throw new FireflyException('400005: Could not store new piggy bank.');
        }

        $this->updateNote($piggyBank, $data['notes']);

        // repetition is auto created.
        $repetition = $this->getRepetition($piggyBank);
        if (null !== $repetition && isset($data['current_amount'])) {
            $repetition->currentamount = $data['current_amount'];
            $repetition->save();
        }

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param array $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank
    {
        if (isset($data['name']) && '' !== $data['name']) {
            $piggyBank->name = $data['name'];
        }
        if (isset($data['account_id']) && 0 !== $data['account_id']) {
            $piggyBank->account_id = (int)$data['account_id'];
        }
        if (isset($data['targetamount']) && '' !== $data['targetamount']) {
            $piggyBank->targetamount = $data['targetamount'];
        }
        if (isset($data['targetdate']) && '' !== $data['targetdate']) {
            $piggyBank->targetdate = $data['targetdate'];
        }
        $piggyBank->startdate    = $data['startdate'] ?? $piggyBank->startdate;
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

        return $piggyBank;
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
        $repetition->currentamount = $amount;
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, $amount);

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string $note
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
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
