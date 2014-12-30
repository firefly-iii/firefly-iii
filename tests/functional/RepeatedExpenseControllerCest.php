<?php

/**
 * Class RepeatedExpenseControllerCest
 */
class RepeatedExpenseControllerCest
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
        $I->wantTo('create a recurring transaction');
        $I->amOnPage('/repeatedexpenses/create');
        $I->see('Create new repeated expense');
    }

    public function delete(FunctionalTester $I)
    {
        $I->wantTo('delete a recurring transaction');
        $I->amOnPage('/repeatedexpenses/delete/4');
        $I->see('Delete "Nieuwe kleding"');
    }

    public function destroy(FunctionalTester $I)
    {
        $I->wantTo('destroy a recurring transaction');
        $I->amOnPage('/repeatedexpenses/delete/4');
        $I->submitForm('#destroy', []);
        $I->dontSeeInDatabase('piggy_banks', ['id' => 5]);
    }

    public function edit(FunctionalTester $I)
    {
        $I->wantTo('edit a recurring transaction');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->see('Edit repeated expense "Nieuwe kleding"');

    }

    public function index(FunctionalTester $I)
    {
        $I->wantTo('see all recurring transactions');
        $I->amOnPage('/repeatedexpenses');
        $I->see('Overview');
        $I->see('Nieuwe kleding');
    }

    public function show(FunctionalTester $I)
    {
        $I->wantTo('view a recurring transaction');
        $I->amOnPage('/repeatedexpenses/show/4');
        $I->see('Nieuwe kleding');
    }

    public function store(FunctionalTester $I)
    {
        $I->wantTo('store a recurring transaction');
        $I->amOnPage('/repeatedexpenses/create');
        $I->submitForm(
            '#store', [
                        'name'               => 'TestRepeatedExpense',
                        'account_id'         => 1,
                        'targetamount'       => 1000,
                        'targetdate'         => '2014-05-01',
                        'rep_length'         => 'month',
                        'rep_every'          => 0,
                        'rep_times'          => 0,
                        'remind_me'          => 1,
                        'reminder'           => 'month',
                        'post_submit_action' => 'store',
                    ]
        );
        $I->see('Piggy bank "TestRepeatedExpense" stored.');
    }

    public function update(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => 'Nieuwe kleding!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'update',
                     ]
        );
        $I->see('Repeated expense &quot;Nieuwe kleding!&quot; updated.');
    }

    public function updateAndReturnToEdit(FunctionalTester $I)
    {
        $I->wantTo('update a recurring transaction and return to edit screen');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => 'Nieuwe kleding!',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'return_to_edit',
                     ]
        );
        $I->see('Repeated expense &quot;Nieuwe kleding!&quot; updated.');
    }

    public function updateFail(FunctionalTester $I)
    {
        $I->wantTo('try to update a recurring transaction and fail');
        $I->amOnPage('/repeatedexpenses/edit/4');
        $I->submitForm(
            '#update', [
                         'name'               => '',
                         'account_id'         => 2,
                         'targetamount'       => 1000.00,
                         'targetdate'         => '2014-12-30',
                         'rep_length'         => 'month',
                         'rep_every'          => 0,
                         'rep_times'          => 0,
                         'remind_me'          => 1,
                         'reminder'           => 'month',
                         'post_submit_action' => 'update',
                     ]
        );
        $I->see('The name field is required.');
    }
}