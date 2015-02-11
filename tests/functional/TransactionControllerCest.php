<?php

use FireflyIII\Models\TransactionType;
use FireflyIII\Models\TransactionJournal;

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

    /**
     * @param FunctionalTester $I
     */
    public function create(FunctionalTester $I)
    {
        $I->wantTo('create a transaction');
        $I->amOnPage('/transactions/create/withdrawal?account_id=1');
        $I->see('Add a new withdrawal');
    }

    /**
     * @param FunctionalTester $I
     */
    public function deleteWithdrawal(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Withdrawal')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $I->wantTo('delete a transaction');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->see('Delete withdrawal "' . $journal->description . '"');
    }

    /**
     * @param FunctionalTester $I
     */
    public function destroyDeposit(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Deposit')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $I->wantTo('destroy a deposit');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function destroyTransfer(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Transfer')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

        $I->wantTo('destroy a transfer');

        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function destroyTransferWithEvent(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $row       = DB::table('piggy_bank_events')->whereNotNull('transaction_journal_id')->first();
        $journalId = $row->transaction_journal_id;
        $journal   = TransactionJournal::find($journalId);

        $I->wantTo('destroy a transfer connected to a piggy bank');

        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function destroyWithdrawal(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Withdrawal')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

        $I->wantTo('destroy a withdrawal');
        $I->amOnPage('/transaction/delete/' . $journal->id);
        $I->submitForm('#destroy', []);
        $I->see('Transaction &quot;' . $journal->description . '&quot; destroyed.');

    }

    /**
     * @param FunctionalTester $I
     */
    public function edit(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Transfer')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

        $I->wantTo('edit a transaction');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see('Edit transfer &quot;' . $journal->description . '&quot;');
    }

    /**
     * @param FunctionalTester $I
     */
    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all withdrawals');
        $I->amOnPage('/transactions/withdrawal');
        $I->see('Expenses');
    }

    /**
     * @param FunctionalTester $I
     */
    public function indexExpenses(FunctionalTester $I)
    {
        $I->wantTo('see all expenses');
        $I->amOnPage('/transactions/deposit');
        $I->see('Revenue, income and deposits');
    }

    /**
     * @param FunctionalTester $I
     */
    public function indexTransfers(FunctionalTester $I)
    {
        $I->wantTo('see all transfers');
        $I->amOnPage('/transactions/transfers');
        $I->see('Transfers');
    }

    /**
     * @param FunctionalTester $I
     */
    public function show(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Withdrawal')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

        $I->wantTo('see a transaction');
        $I->amOnPage('/transaction/show/' . $journal->id);
        $I->see($journal->description);
        $I->see(intval($journal->getAmount()));
    }

    /**
     * @param FunctionalTester $I
     */
    public function showGroupedJournal(FunctionalTester $I)
    {
        $groupRow = DB::table('transaction_group_transaction_journal')->select('transaction_journal_id')->first(['transaction_journal_id']);

        $id = $groupRow->transaction_journal_id;

        // get a grouped journal:
        $journal = TransactionJournal::find($id);


        $I->wantTo('see a grouped transaction');
        $I->amOnPage('/transaction/show/' . $journal->id);
        $I->see($journal->description);
    }

    /**
     * @param FunctionalTester $I
     */
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

    /**
     * @param FunctionalTester $I
     */
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

    /**
     * @param FunctionalTester $I
     */
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

    /**
     * @param FunctionalTester $I
     */
    public function storeValidate(FunctionalTester $I)
    {
        $I->wantTo('validate a transaction');
        $I->amOnPage('/transactions/create/withdrawal');
        $I->submitForm(
            '#store', [
                        'reminder'           => '',
                        'description'        => 'TestValidateMe',
                        'account_id'         => 1,
                        'expense_account'    => 'Zomaar',
                        'amount'             => 100,
                        'date'               => '2014-12-30',
                        'budget_id'          => 3,
                        'category'           => 'CategorrXXXXr',
                        'post_submit_action' => 'validate_only'
                    ]
        );
        $I->see('OK');
        $I->seeInSession('successes');
        $I->dontSeeRecord('transaction_journals', ['description' => 'TestValidateMe']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function update(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Deposit')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

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

    /**
     * @param FunctionalTester $I
     */
    public function updateAndFail(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Deposit')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

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

    /**
     * @param FunctionalTester $I
     */
    public function updateAndReturn(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Deposit')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();
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

    /**
     * @param FunctionalTester $I
     */
    public function updateValidate(FunctionalTester $I)
    {
        // get withdrawal transaction type id:
        $type = TransactionType::whereType('Deposit')->first();

        // get a journal
        $journal = TransactionJournal::where('transaction_type_id', $type->id)->first();

        $I->wantTo('validate an updated transaction');
        $I->amOnPage('/transaction/edit/' . $journal->id);
        $I->see($journal->description);
        $I->submitForm(
            '#update', [
                         'description'        => $journal->description . 'XYZ',
                         'account_id'         => 1,
                         'expense_account'    => 'Portaal',
                         'amount'             => 500,
                         'date'               => $journal->date->format('Y-m-d'),
                         'budget_id'          => is_null($journal->budgets()->first()) ? 0 : $journal->budgets()->first()->id,
                         'category'           => is_null($journal->categories()->first()) ? '' : $journal->categories()->first()->id,
                         'post_submit_action' => 'validate_only'
                     ]
        );
        $I->see($journal->description . 'XYZ');
        $I->see('OK');
        $I->seeInSession('successes');
    }


}
