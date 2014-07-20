<?php


namespace Firefly\Storage\Account;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public $validator;

    public function __construct()
    {
    }

    public function get()
    {
        return \Auth::user()->accounts()->with('accounttype')->orderBy('name', 'ASC')->get();
    }

    public function getBeneficiaries()
    {
        $list = \Auth::user()->accounts()->leftJoin(
            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
            ->where('account_types.description', 'Beneficiary account')->where('accounts.active', 1)

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
        return $list;
    }

    public function find($id)
    {
        return \Auth::user()->accounts()->where('id', $id)->first();
    }

    public function getByIds($ids)
    {
        if (count($ids) > 0) {
            return \Auth::user()->accounts()->with('accounttype')->whereIn('id', $ids)->orderBy('name', 'ASC')->get();
        } else {
            return [];
        }
    }

    public function getDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
    }

    public function getActiveDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')->where('accounts.active', 1)

            ->get(['accounts.*']);
    }

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

    public function count()
    {
        return \Auth::user()->accounts()->count();

    }

    public function storeWithInitialBalance($data, \Carbon\Carbon $date, $amount = 0)
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
        } catch (\Illuminate\Database\QueryException $e) {
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
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new \Firefly\Exception\FireflyException('Could not save account ' . $data['name']);
        }

        return $account;
    }

    public function createOrFindBeneficiary($name)
    {
        if (is_null($name) || strlen($name) == 0) {
            return null;
        }
        $type = \AccountType::where('description', 'Beneficiary account')->first();
        return $this->createOrFind($name, $type);
    }

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

    public function findByName($name)
    {
        return \Auth::user()->accounts()->where('name', 'like', '%' . $name . '%')->first();
    }

    public function getCashAccount()
    {
        $type = \AccountType::where('description', 'Cash account')->first();
        $cash = \Auth::user()->accounts()->where('account_type_id', $type->id)->first();
        return $cash;

    }

}