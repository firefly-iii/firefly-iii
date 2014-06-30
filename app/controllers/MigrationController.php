<?php

class MigrationController extends BaseController
{
    public function index()
    {
        // check if database connection is present.
        $configValue = Config::get('database.connections.old-firefly');
        if (is_null($configValue)) {
            return View::make('migrate.index');
        }

        // try to connect to it:
        $error = '';
        try {
            DB::connection('old-firefly')->select('SELECT * from `users`;');
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return View::make('migrate.index')->with('error', $error);
        }

        return Redirect::route('migrate.select-user');


    }

    public function selectUser()
    {
        $oldUsers = [];
        try {
            $oldUsers = DB::connection('old-firefly')->select('SELECT * from `users`;');
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return View::make('migrate.index')->with('error', $error);
        }

        return View::make('migrate.select-user')->with('oldUsers', $oldUsers);
    }

    public function postSelectUser()
    {
        $userID = Input::get('user');
        $count = DB::connection('old-firefly')->table('users')->where('id', $userID)->count();
        if ($count == 1) {
            return Redirect::route('migrate.migrate', $userID);
        } else {
            return View::make('error')->with('message', 'No such user!');
        }
    }

    public function migrate($userID)
    {
        // import the data.
        $user = Auth::user();
        $previousUser = DB::connection('old-firefly')->table('users')->where('id', $userID)->first();


        // current user already migrated?
        if ($user->migrated) {
            return View::make('error')->with('message', 'This user has already been migrated.');
        }

        // a map to keep the connections intact:
        $map = [
            'accounts'      => [],
            'beneficiaries' => [],
            'categories'    => [],
            'budgets'       => [],
        ];

        // messages to show in result screen:
        $messages = [];

        // grab account types:
        $defaultAT = AccountType::where('description', 'Default account')->first();
        $initialBalanceAT = AccountType::where('description', 'Initial balance account')->first();
        $beneficiaryAT = AccountType::where('description', 'Beneficiary account')->first();

        // grab transaction types:
        $initialBalanceTT = TransactionType::where('type', 'Opening balance')->first();
        $depositTT = TransactionType::where('type', 'Deposit')->first();
        $withdrawalTT = TransactionType::where('type', 'Withdrawal')->first();
        $transferTT = TransactionType::where('type', 'Transfer')->first();

        // grab currency:
        $currency = TransactionCurrency::where('code', 'EUR')->first();

        // grab component types:
        $categoryType = ComponentType::where('type', 'category')->first();
        $budgetType = ComponentType::where('type', 'budget')->first();

        // create a special cash account for this user:
        $cashAccount = new Account;
        $cashAccount->name = 'Cash account';
        $cashAccount->active = true;
        $cashAccount->accountType()->associate($beneficiaryAT);
        $cashAccount->user()->associate($user);
        $cashAccount->save();
        $messages[] = 'Created "cash" account.';

        // get the old accounts:
        $accounts = DB::connection('old-firefly')->table('accounts')->where('user_id', $previousUser->id)->get();
        foreach ($accounts as $account) {

            // already had one?
            $existing = Account::where('name', $account->name)->where('account_type_id', $defaultAT->id)->where(
                'user_id', $previousUser->id
            )->first();

            if (!is_null($existing)) {
                $map['accounts'][$account->id] = $existing;
                $messages[] = 'Skipped creating account ' . $account->name . ' because it already exists.';
                continue;
            }
            unset($existing);
            // create account:
            $current = new Account;
            $current->name = $account->name;
            $current->active = true;
            $current->accountType()->associate($defaultAT);
            $current->user()->associate($user);
            $current->save();

            // map and information
            $map['accounts'][$account->id] = $current;
            $messages[] = 'Account "' . $current->name . '" recreated.';

            // recreate opening balance, if relevant:
            if (floatval($account->openingbalance) != 0) {

                // now create another account, and create the first transfer indicating
                $initial = new Account;
                $initial->name = $account->name . ' initial balance';
                $initial->accountType()->associate($initialBalanceAT);
                $initial->active = 1;
                $initial->user()->associate($user);
                $initial->save();

                // create a journal, and two transfers:
                $journal = new TransactionJournal;
                $journal->transactionType()->associate($initialBalanceTT);
                $journal->transactionCurrency()->associate($currency);
                $journal->description = $account->name . ' opening balance';
                $journal->date = $account->openingbalancedate;
                $journal->save();

                // coming from a virtual account is:
                $credit = new Transaction;
                $credit->account()->associate($current);
                $credit->transactionJournal()->associate($journal);
                $credit->description = null;
                $credit->amount = $account->openingbalance;
                $credit->save();

                // transfer into this new account
                $debet = new Transaction;
                $debet->account()->associate($initial);
                $debet->transactionJournal()->associate($journal);
                $debet->description = null;
                $debet->amount = $account->openingbalance * -1;
                $debet->save();
                $messages[]
                    = 'Saved initial balance for ' . $current->name . ' (' . mf($account->openingbalance, true) . ')';
                unset($initial, $journal, $credit, $debet);
            }
            unset($current);
        }

        // save all old components
        $components = DB::connection('old-firefly')->table('components')->leftJoin(
            'types', 'types.id', '=', 'components.type_id'
        )->where('user_id', $previousUser->id)->get(['components.*', 'types.type']);

        foreach ($components as $component) {
            $id = $component->id;
            switch ($component->type) {
                case 'beneficiary':
                    if (!isset($map['beneficiaries'][$id])) {
                        // if it exists, skip:
                        $existing = Account::where('name', $component->name)->where('user_id', $user->id)->where(
                            'account_type_id', $beneficiaryAT->id
                        )->first();
                        if (!is_null($existing)) {
                            $map['beneficiaries'][$id] = $existing;
                            $messages[]
                                = 'Skipped creating beneficiary "' . $component->name . '" because it already exists.';
                            unset($existing);
                            continue;
                        }


                        // new account for this beneficiary
                        $beneficiary = new Account;
                        $beneficiary->name = $component->name;
                        $beneficiary->accountType()->associate($beneficiaryAT);
                        $beneficiary->user()->associate($user);
                        $beneficiary->active = 1;
                        $beneficiary->save();
                        $map['beneficiaries'][$id] = $beneficiary;
                        $messages[] = 'Recreated beneficiary "' . $beneficiary->name . '".';
                        unset($beneficiary);
                    }
                    break;
                case 'category':
                    // if it exists, skip:
                    $existing = Component::where('name', $component->name)->where('user_id', $user->id)->where(
                        'component_type_id', $categoryType->id
                    )->first();
                    if (!is_null($existing)) {
                        $map['categories'][$id] = $existing;
                        $messages[] = 'Skipped creating category "' . $component->name . '" because it already exists.';
                        unset($existing);
                        continue;
                    }

                    // new component for this category:
                    $category = new Component;
                    $category->componentType()->associate($categoryType);
                    $category->name = $component->name;
                    $category->user()->associate($user);
                    $category->save();
                    $map['categories'][$id] = $category;
                    $messages[] = 'Recreated category "' . $category->name . '".';
                    unset($category);
                    break;
                case 'budget':
                    // if it exists, skip:
                    $existing = Component::where('name', $component->name)->where('user_id', $user->id)->where(
                        'component_type_id', $budgetType->id
                    )->first();
                    if (!is_null($existing)) {
                        $map['budgets'][$id] = $existing;
                        $messages[] = 'Skipped creating budget "' . $component->name . '" because it already exists.';
                        unset($existing);
                        continue;
                    }

                    // new component for this budget:
                    $budget = new Component;
                    $budget->componentType()->associate($budgetType);
                    $budget->user()->associate($user);
                    $budget->name = $component->name;
                    $budget->save();
                    $map['budgets'][$id] = $budget;
                    $messages[] = 'Recreated budget "' . $budget->name . '".';
                    unset($budget);
                    break;
            }
        }

        // grab all old transactions:
        $transactions = DB::connection('old-firefly')->table('transactions')->where('user_id', $user->id)->orderBy(
            'date'
        )
            ->get();

        foreach ($transactions as $transaction) {
            $accountID = $transaction->account_id;

            // grab the components for this transaction
            $components = DB::connection('old-firefly')->table('component_transaction')
                ->leftJoin('components', 'components.id', '=', 'component_transaction.component_id')
                ->leftJoin('types', 'types.id', '=', 'components.type_id')
                ->where('transaction_id', $transaction->id)
                ->get(['components.id', 'types.type']);

            $beneficiary = null;
            $budget = null;
            $category = null;

            // loop components, get the right id's:
            foreach ($components as $component) {
                $id = $component->id;
                switch ($component->type) {
                    case 'beneficiary':
                        $beneficiary = $map['beneficiaries'][$id];
                        break;
                    case 'budget':
                        $budget = $map['budgets'][$id];
                        break;
                    case 'category':
                        $category = $map['categories'][$id];
                        break;
                }
            }
            // get a fall back for empty beneficiaries:
            if (is_null($beneficiary)) {
                $beneficiary = $cashAccount;
            }

            // create the transaction journal:
            $journal = new TransactionJournal;
            if ($transaction->amount < 0) {
                $journal->transactionType()->associate($withdrawalTT);
            } else {
                $journal->transactionType()->associate($depositTT);
            }
            $journal->transactionCurrency()->associate($currency);
            $journal->description = $transaction->description;
            $journal->date = $transaction->date;
            $journal->save();

            // create two transactions:

            if ($transaction->amount < 0) {
                // credit the beneficiary
                $credit = new Transaction;
                $credit->account()->associate($beneficiary);
                $credit->transactionJournal()->associate($journal);
                $credit->description = null;
                $credit->amount = floatval($transaction->amount) * -1;
                $credit->save();
                // add budget / category:
                if (!is_null($budget)) {
                    $credit->components()->attach($budget);
                }
                if (!is_null($category)) {
                    $credit->components()->attach($category);
                }

                // debet ourselves:
                $debet = new Transaction;
                $debet->account()->associate($map['accounts'][$accountID]);
                $debet->transactionJournal()->associate($journal);
                $debet->description = null;
                $debet->amount = floatval($transaction->amount);
                $debet->save();
                if (!is_null($budget)) {
                    $debet->components()->attach($budget);
                }
                if (!is_null($category)) {
                    $debet->components()->attach($category);
                }
                $messages[]
                    = 'Recreated expense "' . $transaction->description . '" (' . mf($transaction->amount, true) . ')';

            } else {
                $credit = new Transaction;
                $credit->account()->associate($map['accounts'][$accountID]);
                $credit->transactionJournal()->associate($journal);
                $credit->description = null;
                $credit->amount = floatval($transaction->amount);
                $credit->save();
                if (!is_null($budget)) {
                    $credit->components()->attach($budget);
                }
                if (!is_null($category)) {
                    $credit->components()->attach($category);
                }

                // from whoever!
                $debet = new Transaction;
                $debet->account()->associate($beneficiary);
                $debet->transactionJournal()->associate($journal);
                $debet->description = null;
                $debet->amount = floatval($transaction->amount) * -1;
                $debet->save();
                if (!is_null($budget)) {
                    $debet->components()->attach($budget);
                }
                if (!is_null($category)) {
                    $debet->components()->attach($category);
                }
                $messages[]
                    = 'Recreated income "' . $transaction->description . '" (' . mf($transaction->amount, true) . ')';

            }


        }
        unset($transaction);

        // recreate the transfers:
        $transfers = DB::connection('old-firefly')->table('transfers')->where('user_id', $user->id)->get();
        foreach ($transfers as $transfer) {

            // if it exists already, we don't need to recreate it:
            $existingJournal = TransactionJournal::where('description', $transfer->description)->where(
                'date', $transfer->date
            )->where('transaction_type_id', $transferTT->id)->first();
            if (!is_null($existingJournal)) {
                // grab transaction from journal to make sure:
                $firstTransaction = $existingJournal->transactions()->first();
                if ($firstTransaction->amount == $transfer->amount
                    || $firstTransaction->amount == $transfer->amount * -1
                ) {
                    // probably the same:
                    $messages[] = 'Skipped transfer "' . $transfer->description . '" because it already exists.';
                    unset($existingJournal, $firstTransaction);
                    continue;
                }
            }


            $fromID = $transfer->accountfrom_id;
            $toID = $transfer->accountto_id;
            // create a journak:
            $journal = new TransactionJournal;
            $journal->transactionType()->associate($transferTT);
            $journal->transactionCurrency()->associate($currency);
            $journal->description = $transfer->description;
            $journal->date = $transfer->date;
            $journal->save();

            // from account (debet) to another account (credit)
            $debet = new Transaction;
            $debet->account()->associate($map['accounts'][$fromID]);
            $debet->transactionJournal()->associate($journal);
            $debet->description = null;
            $debet->amount = floatval($transfer->amount * -1);
            $debet->save();

            // to another account!
            $credit = new Transaction;
            $credit->account()->associate($map['accounts'][$toID]);
            $credit->transactionJournal()->associate($journal);
            $credit->description = null;
            $credit->amount = floatval($transfer->amount);
            $credit->save();
            $messages[] = 'Recreated transfer "' . $transfer->description . '" (' . mf($transfer->amount) . ')';
        }
        $user->migrated = true;
        $user->save();

        return View::make('migrate.result')->with('messages', $messages);
    }
}