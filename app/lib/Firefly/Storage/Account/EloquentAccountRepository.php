<?php


namespace Firefly\Storage\Account;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Database\QueryException;

/**
 * Class EloquentAccountRepository
 *
 * @package Firefly\Storage\Account
 */
class EloquentAccountRepository implements AccountRepositoryInterface
{
    public $validator;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return \Auth::user()->accounts()->with('accounttype')->orderBy('name', 'ASC')->get();
    }

    /**
     * @return mixed
     */
    public function getBeneficiaries()
    {
        $list = \Auth::user()->accounts()->leftJoin(
            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
            ->where('account_types.description', 'Beneficiary account')->where('accounts.active', 1)

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
        return $list;
    }

    /**
     * @param $accountId
     *
     * @return mixed
     */
    public function find($accountId)
    {
        return \Auth::user()->accounts()->where('id', $accountId)->first();
    }

    /**
     * @param $ids
     *
     * @return array|mixed
     */
    public function getByIds($ids)
    {
        if (count($ids) > 0) {
            return \Auth::user()->accounts()->with('accounttype')->whereIn('id', $ids)->orderBy('name', 'ASC')->get();
        } else {
            return [];
        }
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
    }

    /**
     * @return mixed
     */
    public function getActiveDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')->where('accounts.active', 1)

            ->get(['accounts.*']);
    }

    /**
     * @return array|mixed
     */
    public function  getActiveDefaultAsSelectList()
    {
        $list = \Auth::user()->accounts()->leftJoin(
            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
            ->where('account_types.description', 'Default account')->where('accounts.active', 1)

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
        $return = [];
        foreach ($list as $entry) {
            $return[intval($entry->id)] = $entry->name;
        }
        return $return;
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return \Auth::user()->accounts()->count();

    }

    /**
     * @param        $data
     * @param Carbon $date
     * @param int    $amount
     *
     * @return \Account|mixed
     * @throws \Firefly\Exception\FireflyException
     */
    public function storeWithInitialBalance($data, Carbon $date, $amount = 0)
    {

        $account = $this->store($data);

        $initialBalanceAT = \AccountType::where('description', 'Initial balance account')->first();
        $initial = new \Account;
        $initial->accountType()->associate($initialBalanceAT);
        $initial->user()->associate(\Auth::user());
        $initial->name = $data['name'] . ' initial balance';
        $initial->active = 0;
        try {
            $initial->save();
        } catch (QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new FireflyException('Could not save counterbalance account for ' . $data['name']);
        }

        // create new transaction journal (and transactions):
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $transactionJournal */
        $transactionJournal = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');

        $transactionJournal->createSimpleJournal(
            $initial, $account, 'Initial Balance for ' . $data['name'], $amount, $date
        );


        return $account;


    }

    /**
     * @param $data
     *
     * @return \Account|mixed
     * @throws \Firefly\Exception\FireflyException
     */
    public function store($data)
    {
        $defaultAT = \AccountType::where('description', 'Default account')->first();

        $at = isset($data['account_type']) ? $data['account_type'] : $defaultAT;

        $account = new \Account;
        $account->accountType()->associate($at);
        $account->user()->associate(\Auth::user());
        $account->name = $data['name'];
        $account->active = isset($data['active']) ? $data['active'] : 1;
        try {
            $account->save();
        } catch (QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new FireflyException('Could not save account ' . $data['name']);
        }

        return $account;
    }

    /**
     * @param $name
     *
     * @return \Account|mixed|null
     */
    public function createOrFindBeneficiary($name)
    {
        if (is_null($name) || strlen($name) == 0) {
            return null;
        }
        $type = \AccountType::where('description', 'Beneficiary account')->first();
        return $this->createOrFind($name, $type);
    }

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return \Account|mixed
     */
    public function createOrFind($name, \AccountType $type)
    {
        $beneficiary = $this->findByName($name);
        if (!$beneficiary) {
            $data = [
                'name'         => $name,
                'account_type' => $type
            ];
            return $this->store($data);
        }
        return $beneficiary;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name)
    {
        return \Auth::user()->accounts()->where('name', 'like', '%' . $name . '%')->first();
    }

    /**
     * @return mixed
     */
    public function getCashAccount()
    {
        $type = \AccountType::where('description', 'Cash account')->first();
        $cash = \Auth::user()->accounts()->where('account_type_id', $type->id)->first();
        return $cash;

    }

}