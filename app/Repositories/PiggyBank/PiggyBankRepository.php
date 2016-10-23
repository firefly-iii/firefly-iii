<?php
/**
 * PiggyBankRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\PiggyBank;

use Amount;
use Carbon\Carbon;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class PiggyBankRepository
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{

    /** @var User */
    private $user;

    /**
     * PiggyBankRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent
    {
        $event = PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

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
     * @param int $piggyBankid
     *
     * @return PiggyBank
     */
    public function find(int $piggyBankid): PiggyBank
    {
        $piggyBank = $this->user->piggyBanks()->where('piggy_banks.id', $piggyBankid)->first(['piggy_banks.*']);
        if (!is_null($piggyBank)) {
            return $piggyBank;
        }

        return new PiggyBank();
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
     * @return int
     */
    public function getMaxOrder(): int
    {
        return intval($this->user->piggyBanks()->max('order'));
    }

    /**
     * @return Collection
     */
    public function getPiggyBanks(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->piggyBanks()->orderBy('order', 'ASC')->get();

        return $set;
    }

    /**
     * Also add amount in name.
     *
     * @return Collection
     */
    public function getPiggyBanksWithAmount() : Collection
    {
        $set = $this->getPiggyBanks();
        foreach ($set as $piggy) {
            $currentAmount = $piggy->currentRelevantRep()->currentamount ?? '0';
            $piggy->name   = $piggy->name . ' (' . Amount::format($currentAmount, false) . ')';
        }

        return $set;
    }

    /**
     * Set all piggy banks to order 0.
     *
     * @return bool
     */
    public function reset(): bool
    {
        // split query to make it work in sqlite:
        $set = PiggyBank::
        leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.id')
                        ->where('accounts.user_id', $this->user->id)->get(['piggy_banks.*']);
        foreach ($set as $e) {
            $e->order = 0;
            $e->save();
        }

        return true;
    }

    /**
     *
     * set id of piggy bank.
     *
     * @param int $piggyBankId
     * @param int $order
     *
     * @return bool
     */
    public function setOrder(int $piggyBankId, int $order): bool
    {
        $piggyBank = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', $this->user->id)
                              ->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);
        if ($piggyBank) {
            $piggyBank->order = $order;
            $piggyBank->save();
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank
    {
        $data['order'] = $this->getMaxOrder() + 1;
        $piggyBank     = PiggyBank::create($data);

        $this->updateNote($piggyBank, $data['note']);

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

        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = intval($data['account_id']);
        $piggyBank->targetamount = round($data['targetamount'], 2);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->startdate    = $data['startdate'];

        $piggyBank->save();

        $this->updateNote($piggyBank, $data['note']);

        // if the piggy bank is now smaller than the current relevant rep,
        // remove money from the rep.
        $repetition = $piggyBank->currentRelevantRep();
        if ($repetition->currentamount > $piggyBank->targetamount) {

            $diff = bcsub($piggyBank->targetamount, $repetition->currentamount);
            $this->createEvent($piggyBank, $diff);

            $repetition->currentamount = $piggyBank->targetamount;
            $repetition->save();
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
        if (strlen($note) === 0) {
            $dbNote = $piggyBank->notes()->first();
            if (!is_null($dbNote)) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote = $piggyBank->notes()->first();
        if (is_null($dbNote)) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
