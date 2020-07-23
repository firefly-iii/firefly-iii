<?php
/**
 * PiggyBankRepository.php
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

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class PiggyBankRepository.
 *
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{
    use ModifiesPiggyBanks;
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
     * Find by name or return NULL.
     *
     * @param string $name
     *
     * @return PiggyBank|null
     */
    public function findByName(string $name): ?PiggyBank
    {
        return $this->user->piggyBanks()->where('name', $name)->first(['piggy_banks.*']);
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
        return $this->user->piggyBanks()->with(['account', 'objectGroups'])->orderBy('order', 'ASC')->get();
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
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    /**
     * @inheritDoc
     */
    public function getAttachments(PiggyBank $piggyBank): Collection
    {
        $set = $piggyBank->attachments()->get();

        /** @var Storage $disk */
        $disk = Storage::disk('upload');

        $set = $set->each(
            static function (Attachment $attachment) use ($disk) {
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes       = $notes ? $notes->text : '';

                return $attachment;
            }
        );

        return $set;
    }


    /**
     * @inheritDoc
     */
    public function destroyAll(): void
    {
        $this->user->piggyBanks()->delete();
    }

    /**
     * @inheritDoc
     */
    public function searchPiggyBank(string $query, int $limit): Collection
    {
        $search = $this->user->piggyBanks();
        if ('' !== $query) {
            $search->where('piggy_banks.name', 'LIKE', sprintf('%%%s%%', $query));
        }
        $search->orderBy('piggy_banks.order', 'ASC')
               ->orderBy('piggy_banks.name', 'ASC');

        return $search->take($limit)->get();
    }
}
