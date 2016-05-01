<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use DB;
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
        $data['remind_me']     = false;
        $data['reminder_skip'] = 0;

        $piggyBank = PiggyBank::create($data);

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

        // if the piggy bank is now smaller than the current relevant rep,
        // remove money from the rep.
        $repetition = $piggyBank->currentRelevantRep();
        if ($repetition->currentamount > $piggyBank->targetamount) {
            $repetition->currentamount = $piggyBank->targetamount;
            $repetition->save();
        }

        return $piggyBank;
    }
}
