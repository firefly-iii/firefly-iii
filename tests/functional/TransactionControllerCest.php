<?php

/**
 * Class TransactionControllerCest
 */
class TransactionControllerCest
{
    /**
     * @param FunctionalTester $I
     */
    public function _after(FunctionalTester $I)
    {
    }

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedAs(['email' => 'thegrumpydictator@gmail.com', 'password' => 'james']);
    }

    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a transaction');
        $I->amOnPage('/transactions/create/withdrawal?account_id=1');
        $I->see('Add a new withdrawal');
    }

    public function deleteWithdrawal(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Rent for %')->first();
        $I->wantTo('delete a transaction');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->see('Delete withdrawal "' . $journal->description . '"');
    }

    public function destroyDeposit(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Salary for %')->first();
        $I->wantTo('destroy a deposit');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    public function destroyTransfer(FunctionalTester $I)
    {
        $I->wantTo('destroy a transfer');

        $journal = TransactionJournal::where('description', 'LIKE', '%Money for big expense in%')->first();

        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    public function destroyWithdrawal(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Rent for %')->first();
        $I->wantTo('destroy a withdrawal');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    public function edit(FunctionalTester $I)
    {
        $journal = TransactionJournal::whereDescription('Money for piggy')->first();
        $I->wantTo('edit a transaction');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see('Edit transfer &quot;Money for piggy&quot;');
    }

    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all withdrawals');
        $I->amOnPage('/transactions/withdrawal');
        $I->see('Expenses');
    }

    public function indexExpenses(FunctionalTester $I)
    {
        $I->wantTo('see all expenses');
        $I->amOnPage('/transactions/deposit');
        $I->see('Revenue, income and deposits');
    }

    public function indexTransfers(FunctionalTester $I)
    {
        $I->wantTo('see all transfers');
        $I->amOnPage('/transactions/transfers');
        $I->see('Transfers');
    }

    public function show(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Rent for %')->first();

        $I->wantTo('see a transaction');
        $I->amOnPage('/transaction/show/' . $journal->id);
        $I->see($journal->description);
        $I->see(intval($journal->getAmount()));
    }

    public function showGroupedJournal(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', 'Big expense in %')->first();


        $I->wantTo('see a grouped transaction');
        $I->amOnPage('/transaction/show/' . $journal->id);
        $I->see($journal->description);
        $I->see('Money for '.$journal->description);
    }

    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a transaction');
        $I->amOnPage('/transactions/create/withdrawal');
        $I->submitForm(
            '#store', [
                        'reminder'           => '',
                        'description'        => 'Test',
                        'account_id'         => 1,
                        'expense_account'    => 'Zomaar',
                        'amount'             => 100,
                        'date'               => '2014-12-30',
                        'budget_id'          => 3,
                        'category'           => 'Categorrr',
                        'post_submit_action' => 'store'
                    ]
        );
        $I->see('Transaction &quot;Test&quot; stored.');
    }

    public function storeAndFail(FunctionalTester $I)
    {
        $I->wantTo('store a transaction and fail');
        $I->amOnPage('/transactions/create/withdrawal');
        $I->submitForm(
            '#store', [
                        'reminder'           => '',
                        'description'        => '',
                        'account_id'         => 1,
                        'expense_account'    => 'Zomaar',
                        'amount'             => 100,
                        'date'               => '2014-12-30',
                        'budget_id'          => 3,
                        'category'           => 'Categorrr',
                        'post_submit_action' => 'store'
                    ]
        );
        $I->see('Could not store transaction: The description field is required.');
    }

    public function storeAndReturn(FunctionalTester $I)
    {
        $I->wantTo('store a transaction');
        $I->amOnPage('/transactions/create/withdrawal');
        $I->submitForm(
            '#store', [
                        'reminder'           => '',
                        'description'        => 'Test',
                        'account_id'         => 1,
                        'expense_account'    => 'Zomaar',
                        'amount'             => 100,
                        'date'               => '2014-12-30',
                        'budget_id'          => 3,
                        'category'           => 'Categorrr',
                        'post_submit_action' => 'create_another'
                    ]
        );
        $I->see('Transaction &quot;Test&quot; stored.');
    }

    public function update(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Salary for %')->first();

        $I->wantTo('update a transaction');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see($journal->description);
        $I->submitForm(
            '#update', [
                         'description'        => $journal->description . '!',
                         'account_id'         => 1,
                         'expense_account'    => 'Portaal',
                         'amount'             => 500,
                         'date'               => $journal->date->format('Y-m-d'),
                         'budget_id'          => is_null($journal->budgets()->first()) ? 0 : $journal->budgets()->first()->id,
                         'category'           => is_null($journal->categories()->first()) ? '' : $journal->categories()->first()->id,
                         'post_submit_action' => 'update'
                     ]
        );
        $I->see($journal->description . '!');
    }

    public function updateAndFail(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Salary for %')->first();

        $I->wantTo('update a transaction and fail');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see($journal->description);
        $I->submitForm(
            '#update', [
                         'description'        => '',
                         'account_id'         => 1,
                         'expense_account'    => 'Portaal',
                         'amount'             => 500,
                         'date'               => '2014-01-01',
                         'budget_id'          => 2,
                         'category'           => 'House',
                         'post_submit_action' => 'update'
                     ]
        );
        $I->see('Could not update transaction: The description field is required.');
    }

    public function updateAndReturn(FunctionalTester $I)
    {
        $journal = TransactionJournal::where('description', 'LIKE', '%Salary for %')->first();
        $I->wantTo('update a transaction and return to the edit screen');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see($journal->description);
        $I->submitForm(
            '#update', [
                         'description'        => $journal->description . '!',
                         'account_id'         => 1,
                         'expense_account'    => 'Portaal',
                         'amount'             => 500,
                         'date'               => $journal->date->format('Y-m-d'),
                         'budget_id'          => is_null($journal->budgets()->first()) ? 0 : $journal->budgets()->first()->id,
                         'category'           => is_null($journal->categories()->first()) ? '' : $journal->categories()->first()->id,
                         'post_submit_action' => 'return_to_edit'
                     ]
        );
        $I->see($journal->description . '!');
    }


}
