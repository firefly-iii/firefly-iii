<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Auth;
use Carbon\Carbon;
use Config;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Steam;


/**
 *
 * Class AccountRepository
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountRepository implements AccountRepositoryInterface
{

    /** @var array Valid field names of account_meta */
    private $validFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber'];

    /**
     * @param array $types
     *
     * @return int
     */
    public function countAccounts(array $types): int
    {
        $count = Auth::user()->accounts()->accountTypeIn($types)->count();

        return $count;
    }

    /**
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo = null): bool
    {
        if (!is_null($moveTo)) {
            // update all transactions:
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);
        }

        $account->delete();

        return true;
    }

    /**
     * @deprecated
     *
     * @param int $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account
    {
        return Auth::user()->accounts()->findOrNew($accountId);
    }

    /**
     * Gets all the accounts by ID, for a given set.
     *
     * @param array $ids
     *
     * @return Collection
     */
    public function get(array $ids): Collection
    {
        return Auth::user()->accounts()->whereIn('id', $ids)->get(['accounts.*']);
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccounts(array $types): Collection
    {
        /** @var Collection $result */
        $result = Auth::user()->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        )->accountTypeIn($types)->get(['accounts.*']);

        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * This method returns the users credit cards, along with some basic information about the
     * balance they have on their CC. To be used in the JSON boxes on the front page that say
     * how many bills there are still left to pay. The balance will be saved in field "balance".
     *
     * To get the balance, the field "date" is necessary.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getCreditCards(Carbon $date): Collection
    {
        $set = Auth::user()->accounts()
                   ->hasMetaValue('accountRole', 'ccAsset')
                   ->hasMetaValue('ccType', 'monthlyFull')
                   ->leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                   ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                   ->whereNull('transactions.deleted_at')
                   ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                   ->groupBy('accounts.id')
                   ->get(
                       [
                           'accounts.*',
                           'ccType.data as ccType',
                           'accountRole.data as accountRole',
                           DB::raw('SUM(`transactions`.`amount`) AS `balance`'),
                       ]
                   );

        return $set;
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account): Transaction
    {
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();

        return $transaction;
    }

    /**
     * @param Preference $preference
     *
     * @return Collection
     */
    public function getFrontpageAccounts(Preference $preference): Collection
    {
        $query = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account']);

        if (count($preference->data) > 0) {
            $query->whereIn('accounts.id', $preference->data);
        }

        $result = $query->get(['accounts.*']);

        return $result;
    }

    /**
     * This method is used on the front page where (in turn) its viewed journals-tiny.php which (in turn)
     * is almost the only place where formatJournal is used. Aka, we can use some custom querying to get some specific.
     * fields using left joins.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function getFrontpageTransactions(Account $account, Carbon $start, Carbon $end): Collection
    {
        $set = Auth::user()
                   ->transactionjournals()
                   ->with(['transactions'])
                   ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                   ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')->where('accounts.id', $account->id)
                   ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transaction_journals.transaction_currency_id')
                   ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                   ->before($end)
                   ->after($start)
                   ->orderBy('transaction_journals.date', 'DESC')
                   ->orderBy('transaction_journals.order', 'ASC')
                   ->orderBy('transaction_journals.id', 'DESC')
                   ->take(10)
                   ->get(['transaction_journals.*', 'transaction_currencies.symbol', 'transaction_types.type']);

        return $set;
    }

    /**
     * @param Account $account
     * @param int     $page
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Account $account, $page): LengthAwarePaginator
    {
        $offset = ($page - 1) * 50;
        $query  = Auth::user()
                      ->transactionJournals()
                      ->withRelevantData()
                      ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                      ->where('transactions.account_id', $account->id)
                      ->orderBy('transaction_journals.date', 'DESC')
                      ->orderBy('transaction_journals.order', 'ASC')
                      ->orderBy('transaction_journals.id', 'DESC');

        $count     = $query->count();
        $set       = $query->take(50)->offset($offset)->get(['transaction_journals.*']);
        $paginator = new LengthAwarePaginator($set, $count, 50, $page);

        return $paginator;


    }

    /**
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @return Collection
     */
    public function getPiggyBankAccounts(): Collection
    {
        $start      = clone session('start', new Carbon);
        $end        = clone session('end', new Carbon);
        $collection = new Collection(DB::table('piggy_banks')->distinct()->get(['piggy_banks.account_id']));
        $ids        = $collection->pluck('account_id')->toArray();
        $accounts   = new Collection;

        $ids = array_unique($ids);
        if (count($ids) > 0) {
            $accounts = Auth::user()->accounts()->whereIn('id', $ids)->where('accounts.active', 1)->get();
        }
        bcscale(2);

        $accounts->each(
            function (Account $account) use ($start, $end) {
                $account->startBalance = Steam::balance($account, $start, true);
                $account->endBalance   = Steam::balance($account, $end, true);
                $account->piggyBalance = '0';
                /** @var PiggyBank $piggyBank */
                foreach ($account->piggyBanks as $piggyBank) {
                    $account->piggyBalance = bcadd($piggyBank->currentRelevantRep()->currentamount, $account->piggyBalance);
                }
                // sum of piggy bank amounts on this account:
                // diff between endBalance and piggyBalance.
                // then, percentage.
                $difference          = bcsub($account->endBalance, $account->piggyBalance);
                $account->difference = $difference;
                $account->percentage = $difference != 0 && $account->endBalance != 0 ? round((($difference / $account->endBalance) * 100)) : 100;

            }
        );

        return $accounts;

    }

    /**
     * Get savings accounts and the balance difference in the period.
     *
     * @return Collection
     */
    public function getSavingsAccounts(): Collection
    {
        $accounts = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->orderBy('accounts.name', 'ASC')
                        ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                        ->where('account_meta.name', 'accountRole')
                        ->where('accounts.active', 1)
                        ->where('account_meta.data', '"savingAsset"')
                        ->get(['accounts.*']);
        $start    = clone session('start', new Carbon);
        $end      = clone session('end', new Carbon);

        bcscale(2);

        $accounts->each(
            function (Account $account) use ($start, $end) {
                $account->startBalance = Steam::balance($account, $start);
                $account->endBalance   = Steam::balance($account, $end);

                // diff (negative when lost, positive when gained)
                $diff = bcsub($account->endBalance, $account->startBalance);

                if ($diff < 0 && $account->startBalance > 0) {
                    // percentage lost compared to start.
                    $pct = (($diff * -1) / $account->startBalance) * 100;
                } else {
                    if ($diff >= 0 && $account->startBalance > 0) {
                        $pct = ($diff / $account->startBalance) * 100;
                    } else {
                        $pct = 100;
                    }
                }
                $pct                 = $pct > 100 ? 100 : $pct;
                $account->difference = $diff;
                $account->percentage = round($pct);

            }
        );

        return $accounts;
    }

    /**
     * @param Account $account
     * @param Carbon  $date
     *
     * @return string
     */
    public function leftOnAccount(Account $account, Carbon $date): string
    {

        $balance = Steam::balance($account, $date, true);
        /** @var PiggyBank $p */
        foreach ($account->piggyBanks()->get() as $p) {
            $balance = bcsub($p->currentRelevantRep()->currentamount, $balance);
        }

        return $balance;

    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function openingBalanceTransaction(Account $account): TransactionJournal
    {
        $journal = TransactionJournal
            ::orderBy('transaction_journals.date', 'ASC')
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionType::OPENING_BALANCE])
            ->orderBy('created_at', 'ASC')
            ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data): Account
    {
        $newAccount = $this->storeAccount($data);
        if (!is_null($newAccount)) {
            $this->storeMetadata($newAccount, $data);
        }


        // continue with the opposing account:
        if ($data['openingBalance'] != 0) {
            $opposingData = [
                'user'           => $data['user'],
                'accountType'    => 'initial',
                'virtualBalance' => 0,
                'name'           => $data['name'] . ' initial balance',
                'active'         => false,
                'iban'           => '',
            ];
            $opposing     = $this->storeAccount($opposingData);
            if (!is_null($opposing) && !is_null($newAccount)) {
                $this->storeInitialBalance($newAccount, $opposing, $data);
            }

        }

        return $newAccount;

    }

    /**
     * @return string
     */
    public function sumOfEverything(): string
    {
        return strval(Auth::user()->transactions()->sum('amount'));
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // need the complexity.
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        // update the account:
        $account->name            = $data['name'];
        $account->active          = $data['active'] == '1' ? true : false;
        $account->virtual_balance = $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        $this->updateMetadata($account, $data);
        $openingBalance = $this->openingBalanceTransaction($account);
        if ($data['openingBalance'] != 0) {
            if ($openingBalance) {
                $this->updateInitialBalance($account, $openingBalance, $data);
            } else {
                $type         = $data['openingBalance'] < 0 ? 'expense' : 'revenue';
                $opposingData = [
                    'user'           => $data['user'],
                    'accountType'    => $type,
                    'name'           => $data['name'] . ' initial balance',
                    'active'         => false,
                    'iban'           => '',
                    'virtualBalance' => 0,
                ];
                $opposing     = $this->storeAccount($opposingData);
                if (!is_null($opposing)) {
                    $this->storeInitialBalance($account, $opposing, $data);
                }
            }

        } else {
            if ($openingBalance) { // opening balance is zero, should we delete it?
                $openingBalance->delete(); // delete existing opening balance.
            }
        }

        return $account;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    private function storeAccount(array $data): Account
    {
        $type        = Config::get('firefly.accountTypeByIdentifier.' . $data['accountType']);
        $accountType = AccountType::whereType($type)->first();
        $newAccount  = new Account(
            [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtualBalance'],
                'active'          => $data['active'] === true ? true : false,
                'iban'            => $data['iban'],
            ]
        );

        if (!$newAccount->isValid()) {
            // does the account already exist?
            $searchData      = [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'virtual_balance' => $data['virtualBalance'],
                'name'            => $data['name'],
                'iban'            => $data['iban'],
            ];
            $existingAccount = Account::firstOrNullEncrypted($searchData);
            if (!$existingAccount) {
                Log::error('Account create error: ' . $newAccount->getErrors()->toJson());
                abort(500);
            }
            $newAccount = $existingAccount;

        }
        $newAccount->save();

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param Account $opposing
     * @param array   $data
     *
     * @return TransactionJournal
     */
    private function storeInitialBalance(Account $account, Account $opposing, array $data): TransactionJournal
    {
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $journal         = TransactionJournal::create(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'bill_id'                 => null,
                'transaction_currency_id' => $data['openingBalanceCurrency'],
                'description'             => 'Initial balance for "' . $account->name . '"',
                'completed'               => true,
                'date'                    => $data['openingBalanceDate'],
                'encrypted'               => true,
            ]
        );

        if ($data['openingBalance'] < 0) {
            $firstAccount  = $opposing;
            $secondAccount = $account;
            $firstAmount   = $data['openingBalance'] * -1;
            $secondAmount  = $data['openingBalance'];
        } else {
            $firstAccount  = $account;
            $secondAccount = $opposing;
            $firstAmount   = $data['openingBalance'];
            $secondAmount  = $data['openingBalance'] * -1;
        }

        $one = new Transaction(['account_id' => $firstAccount->id, 'transaction_journal_id' => $journal->id, 'amount' => $firstAmount]);
        $one->save();// first transaction: from

        $two = new Transaction(['account_id' => $secondAccount->id, 'transaction_journal_id' => $journal->id, 'amount' => $secondAmount]);
        $two->save(); // second transaction: to

        return $journal;

    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return bool
     */
    private function storeMetadata(Account $account, array $data): bool
    {        
        foreach ($this->validFields as $field) {
            if (isset($data[$field])) {
                $metaData = new AccountMeta(
                    [
                        'account_id' => $account->id,
                        'name'       => $field,
                        'data'       => $data[$field],
                    ]
                );
                $metaData->save();
            }
        }

        return true;
    }

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    private function updateInitialBalance(Account $account, TransactionJournal $journal, array $data): TransactionJournal
    {
        $journal->date = $data['openingBalanceDate'];
        $journal->save();

        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($account->id == $transaction->account_id) {
                $transaction->amount = $data['openingBalance'];
                $transaction->save();
            }
            if ($account->id != $transaction->account_id) {
                $transaction->amount = $data['openingBalance'] * -1;
                $transaction->save();
            }
        }

        return $journal;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return bool
     */
    private function updateMetadata(Account $account, array $data): bool
    {
        foreach ($this->validFields as $field) {
            $entry = $account->accountMeta()->where('name', $field)->first();

            if (isset($data[$field])) {
                // update if new data is present:
                if (!is_null($entry)) {
                    $entry->data = $data[$field];
                    $entry->save();
                } else {
                    $metaData = new AccountMeta(
                        [
                            'account_id' => $account->id,
                            'name'       => $field,
                            'data'       => $data[$field],
                        ]
                    );
                    $metaData->save();
                }
            }
        }

        return true;

    }
}
